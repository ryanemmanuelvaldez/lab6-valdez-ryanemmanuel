<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AccountApi extends Controller
{
    private $api;

    public function __construct()
    {
        parent::__construct();
        $this->call->database();
        $this->call->library('session');
        $this->api = $this->call->library('api');
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
        if ($origin === '') return;

        $host = $_SERVER['HTTP_HOST'] ?? '';
        $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
        $scheme = in_array($forwardedProto, ['http', 'https'], true)
            ? $forwardedProto
            : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
        $allowed = config_item('allow_origin');
        $allowed = is_array($allowed) ? $allowed : [];
        $allowed[] = $scheme . '://' . $host;
        if (!in_array($origin, $allowed, true)) {
            $this->respond(['error' => 'Request origin is not allowed.'], 403);
        }
    }

    private function current_user()
    {
        $userId = (int) $this->session->userdata('account_user_id');
        if ($userId < 1) {
            $this->respond(['error' => 'Please sign in with a user or moderator account.'], 401);
        }

        $user = $this->db->raw(
            'SELECT id, username, email, role, is_active, created_at FROM users WHERE id = ? LIMIT 1',
            [$userId]
        )->fetch(PDO::FETCH_ASSOC);
        if (!$user || !in_array($user['role'], ['user', 'moderator'], true) || (int) $user['is_active'] !== 1) {
            $this->session->unset_userdata('account_user_id');
            $this->respond(['error' => 'This account is no longer active.'], 401);
        }
        $user['id'] = (int) $user['id'];
        $user['is_active'] = (bool) $user['is_active'];
        return $user;
    }

    public function login()
    {
        $this->require_same_origin();
        $data = $this->input();
        $identity = trim((string) ($data['identity'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $user = $this->db->raw(
            'SELECT id, username, email, password, role, is_active, created_at FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$identity, strtolower($identity)]
        )->fetch(PDO::FETCH_ASSOC);

        if (!$user || !in_array($user['role'], ['user', 'moderator'], true)
            || (int) $user['is_active'] !== 1 || !password_verify($password, $user['password'])) {
            $this->respond(['error' => 'Invalid user credentials, or this account is inactive.'], 401);
        }

        $this->session->unset_userdata('admin_id');
        $this->session->sess_regenerate(true);
        $this->session->set_userdata('account_user_id', (int) $user['id']);
        unset($user['password']);
        $user['id'] = (int) $user['id'];
        $user['is_active'] = (bool) $user['is_active'];
        $this->respond(['user' => $user]);
    }

    public function me()
    {
        $userId = (int) $this->session->userdata('account_user_id');
        if ($userId < 1) {
            $this->respond(['user' => null]);
        }

        $user = $this->db->raw(
            'SELECT id, username, email, role, is_active, created_at FROM users WHERE id = ? LIMIT 1',
            [$userId]
        )->fetch(PDO::FETCH_ASSOC);
        if (!$user || !in_array($user['role'], ['user', 'moderator'], true) || (int) $user['is_active'] !== 1) {
            $this->session->unset_userdata('account_user_id');
            $this->respond(['user' => null]);
        }
        $user['id'] = (int) $user['id'];
        $user['is_active'] = (bool) $user['is_active'];
        $this->respond(['user' => $user]);
    }

    public function logout()
    {
        $this->require_same_origin();
        $this->session->unset_userdata('account_user_id');
        $this->respond(['ok' => true]);
    }

    public function catalog()
    {
        $user = $this->current_user();
        $products = $this->db->raw(
            "SELECT id, name, sku, category, description, price, stock FROM products WHERE status = 'active' ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as &$product) {
            $product['id'] = (int) $product['id'];
            $product['price'] = (float) $product['price'];
            $product['stock'] = (int) $product['stock'];
        }
        unset($product);
        $this->respond(['user' => $user, 'products' => $products]);
    }
}
