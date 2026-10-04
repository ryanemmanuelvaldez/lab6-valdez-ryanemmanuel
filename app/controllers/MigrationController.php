<?php


defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');


class MigrationController extends Controller

{

public function __construct()

{

parent::__construct();

if (!(defined('IS_CLI') && IS_CLI)) {
$this->call->library('session');
$this->call->database();
$admin_id = (int) $this->session->userdata('admin_id');
$admin = $admin_id > 0
? $this->db->raw('SELECT role, is_active FROM users WHERE id = ? LIMIT 1', [$admin_id])->fetch(PDO::FETCH_ASSOC)
: null;

if (!$admin || $admin['role'] !== 'admin' || (int) $admin['is_active'] !== 1) {
show_error('403 Forbidden', 'Administrator sign-in required.', 'error_general', 403);
}
}

$this->call->library('migration');

}


public function create_migration($migration_class)

{

$this->migration->create_migration($migration_class);

}


public function migrate()

{

$this->migration->migrate();

}


public function rollback()

{

$this->migration->rollback();

}


public function rollback_all()

{

$this->migration->rollback_all();

}


public function refresh()

{

$this->migration->refresh();

}


public function status()

{

$this->migration->status();

}

}