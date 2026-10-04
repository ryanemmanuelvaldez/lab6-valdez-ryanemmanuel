<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AdminApi extends Controller
{
    private $api;

    public function __construct()
    {
        parent::__construct();
        $this->call->database();
        $this->call->library('session');
        $this->api = $this->call->library('api');
        $this->set_cors_headers();
    }

    private function set_cors_headers()
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed = [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
        ];
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $scheme = $this->request_scheme();
        if ($host !== '') {
            $allowed[] = $scheme . '://' . $host;
        }
        if ($origin !== '' && in_array($origin, $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
            header('Vary: Origin');
        }
    }

    private function request_scheme()
    {
        $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
        if (in_array($forwardedProto, ['http', 'https'], true)) {
            return $forwardedProto;
        }

        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    }

    private function input()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    private function respond($data, $status = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $this->api->respond($data, $status);
    }

    private function require_same_origin()
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin === '') {
            return;
        }

        $allowed = [
            'http://localhost:5173',
            'http://127.0.0.1:5173',
        ];
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $scheme = $this->request_scheme();
        if ($host !== '') {
            $allowed[] = $scheme . '://' . $host;
        }

        if (!in_array($origin, $allowed, true)) {
            $this->respond(['error' => 'Request origin is not allowed.'], 403);
        }
    }

    private function admin()
    {
        $adminId = (int) $this->session->userdata('admin_id');
        if ($adminId < 1) {
            $this->respond(['error' => 'Please sign in as an administrator.'], 401);
        }

        $stmt = $this->db->raw(
            'SELECT id, username, email, role, is_active FROM users WHERE id = ? LIMIT 1',
            [$adminId]
        );
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$admin || $admin['role'] !== 'admin' || (int) $admin['is_active'] !== 1) {
            $this->session->sess_destroy();
            $this->respond(['error' => 'Administrator access is no longer active.'], 401);
        }

        return $admin;
    }

    private function admin_count()
    {
        return (int) $this->db->raw("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    }

    private function public_user($user)
    {
        $user['id'] = (int) $user['id'];
        $user['is_active'] = (bool) $user['is_active'];
        return $user;
    }

    private function product_row($product)
    {
        $product['id'] = (int) $product['id'];
        $product['price'] = (float) $product['price'];
        $product['quantity'] = (int) $product['quantity'];
        $product['name'] = $product['product_name'];
        $product['stock'] = $product['quantity'];
        return $product;
    }

    public function bootstrap_status()
    {
        $hasAdmin = $this->admin_count() > 0;
        $keyConfigured = (string) getenv('ADMIN_BOOTSTRAP_KEY') !== '';
        $this->respond([
            'available' => !$hasAdmin && $keyConfigured,
            'admin_exists' => $hasAdmin,
            'key_configured' => $keyConfigured,
        ]);
    }

    public function bootstrap()
    {
        $this->require_same_origin();
        if ($this->admin_count() > 0) {
            $this->respond(['error' => 'An administrator already exists.'], 409);
        }

        $data = $this->input();
        $bootstrapKey = (string) getenv('ADMIN_BOOTSTRAP_KEY');
        $providedKey = (string) ($data['bootstrap_key'] ?? '');
        if ($bootstrapKey === '' || !hash_equals($bootstrapKey, $providedKey)) {
            $this->respond(['error' => 'The admin bootstrap key is invalid or not configured.'], 403);
        }

        $username = trim((string) ($data['username'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9._-]{3,100}$/', $username)
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 12) {
            $this->respond(['error' => 'Enter a valid username and email, and a password with at least 12 characters.'], 422);
        }

        try {
            $this->db->raw(
                'INSERT INTO users (username, email, password, role, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())',
                [$username, $email, password_hash($password, PASSWORD_DEFAULT), 'admin']
            );
        } catch (Throwable $error) {
            $this->respond(['error' => 'That username or email may already be in use.'], 409);
        }

        $user = $this->db->raw('SELECT id, username, email, role, is_active FROM users WHERE id = LAST_INSERT_ID()')->fetch(PDO::FETCH_ASSOC);
        $this->session->sess_regenerate(true);
        $this->session->set_userdata('admin_id', (int) $user['id']);
        $this->respond(['user' => $this->public_user($user)], 201);
    }

    public function login()
    {
        $this->require_same_origin();
        $data = $this->input();
        $identity = trim((string) ($data['identity'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $stmt = $this->db->raw(
            'SELECT id, username, email, password, role, is_active FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$identity, strtolower($identity)]
        );
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !in_array($user['role'], ['admin', 'moderator', 'user'], true)
            || (int) $user['is_active'] !== 1 || !password_verify($password, $user['password'])) {
            $this->respond(['error' => 'Invalid username or password, or this account is inactive.'], 401);
        }

        $this->session->sess_regenerate(true);
        if ($user['role'] === 'admin') {
            $this->session->unset_userdata('account_user_id');
            $this->session->set_userdata('admin_id', (int) $user['id']);
        } else {
            $this->session->unset_userdata('admin_id');
            $this->session->set_userdata('account_user_id', (int) $user['id']);
        }
        unset($user['password']);
        $this->respond(['user' => $this->public_user($user)]);
    }

    public function me()
    {
        $adminId = (int) $this->session->userdata('admin_id');
        if ($adminId < 1) {
            $accountUserId = (int) $this->session->userdata('account_user_id');
            if ($accountUserId < 1) {
                $this->respond(['user' => null]);
            }

            $accountUser = $this->db->raw(
                'SELECT id, username, email, role, is_active FROM users WHERE id = ? LIMIT 1',
                [$accountUserId]
            )->fetch(PDO::FETCH_ASSOC);
            if (!$accountUser || !in_array($accountUser['role'], ['user', 'moderator'], true)
                || (int) $accountUser['is_active'] !== 1) {
                $this->session->unset_userdata('account_user_id');
                $this->respond(['user' => null]);
            }
            $this->respond(['user' => $this->public_user($accountUser)]);
        }
        $stmt = $this->db->raw('SELECT id, username, email, role, is_active FROM users WHERE id = ? LIMIT 1', [$adminId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || $user['role'] !== 'admin' || (int) $user['is_active'] !== 1) {
            $this->session->sess_destroy();
            $this->respond(['user' => null]);
        }
        $this->respond(['user' => $this->public_user($user)]);
    }

    public function logout()
    {
        $this->require_same_origin();
        $this->session->sess_destroy();
        $this->respond(['ok' => true]);
    }

    public function dashboard()
    {
        $this->admin();
        $users = $this->db->raw('SELECT COUNT(*) FROM users')->fetchColumn();
        $activeUsers = $this->db->raw('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
        $products = $this->db->raw('SELECT COUNT(*) FROM products')->fetchColumn();
        $activeProducts = $this->db->raw("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
        $lowStock = $this->db->raw('SELECT COUNT(*) FROM products WHERE stock <= 5 AND status <> ?', ['archived'])->fetchColumn();
        $inventoryValue = $this->db->raw("SELECT COALESCE(SUM(price * stock), 0) FROM products WHERE status <> 'archived'")->fetchColumn();
        $this->respond([
            'users' => (int) $users,
            'active_users' => (int) $activeUsers,
            'products' => (int) $products,
            'active_products' => (int) $activeProducts,
            'low_stock' => (int) $lowStock,
            'inventory_value' => (float) $inventoryValue,
        ]);
    }

    public function users()
    {
        $this->admin();
        $stmt = $this->db->raw('SELECT id, username, email, role, is_active, created_at, updated_at FROM users ORDER BY id DESC');
        $this->respond(['users' => array_map([$this, 'public_user'], $stmt->fetchAll(PDO::FETCH_ASSOC))]);
    }

    public function create_user()
    {
        $this->admin();
        $this->require_same_origin();
        $data = $this->input();
        $username = trim((string) ($data['username'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $role = (string) ($data['role'] ?? 'user');
        if (!preg_match('/^[a-zA-Z0-9._-]{3,100}$/', $username)
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($password) < 12
            || !in_array($role, ['admin', 'moderator', 'user'], true)) {
            $this->respond(['error' => 'Check the username, email, password (12 characters minimum), and role.'], 422);
        }

        try {
            $this->db->raw(
                'INSERT INTO users (username, email, password, role, is_active, created_at) VALUES (?, ?, ?, ?, 1, NOW())',
                [$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]
            );
        } catch (Throwable $error) {
            $this->respond(['error' => 'That username or email is already in use.'], 409);
        }
        $user = $this->db->raw('SELECT id, username, email, role, is_active, created_at FROM users WHERE id = LAST_INSERT_ID()')->fetch(PDO::FETCH_ASSOC);
        $this->respond(['user' => $this->public_user($user)], 201);
    }

    public function update_user($id)
    {
        $admin = $this->admin();
        $this->require_same_origin();
        $data = $this->input();
        $id = (int) $id;
        $stmt = $this->db->raw('SELECT id, role, is_active FROM users WHERE id = ? LIMIT 1', [$id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            $this->respond(['error' => 'User not found.'], 404);
        }
        $role = $data['role'] ?? $existing['role'];
        $active = array_key_exists('is_active', $data) ? (int) (bool) $data['is_active'] : (int) $existing['is_active'];
        if (!in_array($role, ['admin', 'moderator', 'user'], true)) {
            $this->respond(['error' => 'Unsupported user role.'], 422);
        }
        if ($existing['role'] === 'admin' && ((string) $role !== 'admin' || $active !== 1) && $this->admin_count() <= 1) {
            $this->respond(['error' => 'The last administrator cannot be demoted or deactivated.'], 409);
        }
        $username = trim((string) ($data['username'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($username !== '' && !preg_match('/^[a-zA-Z0-9._-]{3,100}$/', $username)) {
            $this->respond(['error' => 'Username must be 3 to 100 letters, numbers, dots, underscores, or hyphens.'], 422);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respond(['error' => 'Enter a valid email address.'], 422);
        }
        try {
            $this->db->raw(
                'UPDATE users SET username = COALESCE(?, username), email = COALESCE(?, email), role = ?, is_active = ?, updated_at = NOW() WHERE id = ?',
                [$username !== '' ? $username : null, $email !== '' ? $email : null, $role, $active, $id]
            );
        } catch (Throwable $error) {
            $this->respond(['error' => 'That username or email is already in use.'], 409);
        }
        $user = $this->db->raw('SELECT id, username, email, role, is_active, created_at, updated_at FROM users WHERE id = ?', [$id])->fetch(PDO::FETCH_ASSOC);
        $this->respond(['user' => $this->public_user($user)]);
    }

    public function delete_user($id)
    {
        $admin = $this->admin();
        $this->require_same_origin();
        $id = (int) $id;
        if ((int) $admin['id'] === $id) {
            $this->respond(['error' => 'You cannot remove your own administrator account.'], 409);
        }
        $stmt = $this->db->raw('SELECT id, role FROM users WHERE id = ? LIMIT 1', [$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $this->respond(['error' => 'User not found.'], 404);
        }
        if ($user['role'] === 'admin' && $this->admin_count() <= 1) {
            $this->respond(['error' => 'The last administrator cannot be removed.'], 409);
        }
        $this->db->raw('DELETE FROM users WHERE id = ?', [$id]);
        $this->respond(['ok' => true]);
    }

    private function normalize_product($data, $existing = null)
    {
        $name = trim((string) ($data['product_name'] ?? $data['name'] ?? $existing['product_name'] ?? $existing['name'] ?? ''));
        $sku = strtoupper(trim((string) ($data['sku'] ?? $existing['sku'] ?? '')));
        if ($sku === '') {
            $sku = 'API-' . strtoupper(bin2hex(random_bytes(8)));
        }
        $category = trim((string) ($data['category'] ?? $existing['category'] ?? 'General'));
        $description = trim((string) ($data['description'] ?? $existing['description'] ?? ''));
        $price = $data['price'] ?? $existing['price'] ?? null;
        $quantity = $data['quantity'] ?? $data['stock'] ?? $existing['quantity'] ?? $existing['stock'] ?? null;
        $status = (string) ($data['status'] ?? $existing['status'] ?? 'active');
        if ($name === '' || strlen($name) > 100 || $sku === '' || strlen($sku) > 80
            || $category === '' || strlen($category) > 100
            || !is_numeric($price) || (float) $price < 0 || (float) $price > 99999999.99
            || filter_var($quantity, FILTER_VALIDATE_INT) === false
            || (int) $quantity < 0 || (float) $quantity > 4294967295
            || !in_array($status, ['active', 'draft', 'archived'], true)) {
            $this->respond(['error' => 'Enter a product name (up to 100 characters), non-negative price and quantity, and a valid status.'], 422);
        }
        return [
            'product_name' => $name,
            'sku' => $sku,
            'category' => $category,
            'description' => $description,
            'price' => (float) $price,
            'quantity' => (int) $quantity,
            'status' => $status,
        ];
    }

    public function products()
    {
        $this->admin();
        $stmt = $this->db->raw('SELECT id, product_name, sku, category, description, price, quantity, status, created_at, updated_at FROM products ORDER BY id DESC');
        $this->respond(['products' => array_map([$this, 'product_row'], $stmt->fetchAll(PDO::FETCH_ASSOC))]);
    }

    public function create_product()
    {
        $this->admin();
        $this->require_same_origin();
        $fields = $this->normalize_product($this->input());
        try {
            $this->db->raw(
                'INSERT INTO products (product_name, name, sku, category, description, price, quantity, stock, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [
                    $fields['product_name'],
                    $fields['product_name'],
                    $fields['sku'],
                    $fields['category'],
                    $fields['description'],
                    $fields['price'],
                    $fields['quantity'],
                    $fields['quantity'],
                    $fields['status'],
                ]
            );
        } catch (Throwable $error) {
            $this->respond(['error' => 'Could not save the product. Check that its SKU is unique.'], 409);
        }
        $product = $this->db->raw('SELECT id, product_name, sku, category, description, price, quantity, status, created_at FROM products WHERE id = LAST_INSERT_ID()')->fetch(PDO::FETCH_ASSOC);
        $this->respond(['product' => $this->product_row($product)], 201);
    }

    public function update_product($id)
    {
        $this->admin();
        $this->require_same_origin();
        $id = (int) $id;
        $existing = $this->db->raw('SELECT * FROM products WHERE id = ? LIMIT 1', [$id])->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            $this->respond(['error' => 'Product not found.'], 404);
        }
        $fields = $this->normalize_product($this->input(), $existing);
        try {
            $this->db->raw(
                'UPDATE products SET product_name = ?, name = ?, sku = ?, category = ?, description = ?, price = ?, quantity = ?, stock = ?, status = ?, updated_at = NOW() WHERE id = ?',
                [
                    $fields['product_name'],
                    $fields['product_name'],
                    $fields['sku'],
                    $fields['category'],
                    $fields['description'],
                    $fields['price'],
                    $fields['quantity'],
                    $fields['quantity'],
                    $fields['status'],
                    $id,
                ]
            );
        } catch (Throwable $error) {
            $this->respond(['error' => 'Could not update the product. Check that its SKU is unique.'], 409);
        }
        $product = $this->db->raw('SELECT id, product_name, sku, category, description, price, quantity, status, created_at, updated_at FROM products WHERE id = ?', [$id])->fetch(PDO::FETCH_ASSOC);
        $this->respond(['product' => $this->product_row($product)]);
    }

    public function delete_product($id)
    {
        $this->admin();
        $this->require_same_origin();
        $deleted = $this->db->raw('DELETE FROM products WHERE id = ?', [(int) $id])->rowCount();
        if (!$deleted) {
            $this->respond(['error' => 'Product not found.'], 404);
        }
        $this->respond(['ok' => true]);
    }

    public function migrations()
    {
        $this->admin();
        $applied = $this->db->raw('SELECT migration FROM migrations')->fetchAll(PDO::FETCH_COLUMN);
        $files = glob(APP_DIR . 'migrations' . DIRECTORY_SEPARATOR . '*.php') ?: [];
        $items = [];
        foreach ($files as $file) {
            $version = (int) substr(basename($file), 0, 3);
            $items[] = [
                'version' => $version,
                'file' => basename($file),
                'applied' => in_array($version, array_map('intval', $applied), true),
            ];
        }
        $this->respond(['migrations' => $items]);
    }

    public function run_migrations()
    {
        $this->admin();
        $this->require_same_origin();
        ob_start();
        try {
            $this->call->library('migration');
            $this->migration->migrate();
            $output = trim(strip_tags(ob_get_clean()));
            $this->respond(['output' => $output]);
        } catch (Throwable $error) {
            ob_end_clean();
            $this->respond(['error' => 'Migration run failed. Check the server logs and database configuration.'], 500);
        }
    }
}
