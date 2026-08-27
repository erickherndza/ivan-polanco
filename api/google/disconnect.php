<?php
require_once __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../auth_guard.php';

$data = json_input();
if (!csrf_verify($data['csrf'] ?? null)) {
    json_out(['error' => 'Sesión expirada, recarga la página e intenta de nuevo'], 403);
}

$db = get_db();
$db->exec('DELETE FROM google_account WHERE id = 1');

json_out(['ok' => true]);
