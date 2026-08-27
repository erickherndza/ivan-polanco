<?php
require_once __DIR__ . '/../bootstrap.php';

if (!rate_limit_check('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 8, 900)) {
    json_out(['error' => 'Demasiados intentos. Espera unos minutos.'], 429);
}

$data = json_input();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

$db = get_db();
$stmt = $db->prepare('SELECT * FROM admin_users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    usleep(400000);
    json_out(['error' => 'Usuario o contraseña incorrectos'], 401);
}

session_regenerate_id(true);
$_SESSION['admin_id'] = $user['id'];
$_SESSION['admin_username'] = $user['username'];

json_out(['ok' => true, 'username' => $user['username']]);
