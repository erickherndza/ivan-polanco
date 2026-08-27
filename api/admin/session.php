<?php
require_once __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../auth_guard.php';

json_out([
    'ok' => true,
    'username' => $_SESSION['admin_username'] ?? null,
    'csrf' => csrf_token(),
]);
