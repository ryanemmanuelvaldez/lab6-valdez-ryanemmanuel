<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Welcome extends Controller {
	public function index() {
		header('Location: admin/');
		exit;
	}

	public function api_status() {
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
		echo json_encode([
			'status' => 'connected',
			'service' => 'LavaLust API',
		]);
	}
}
?>