<?php
require_once __DIR__ . '/../bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

function callback_error(string $message): void {
    http_response_code(400);
    echo '<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;padding:40px;color:#16313D;">'
        . '<p>' . htmlspecialchars($message) . '</p>'
        . '<p><a href="../../admin/index.html">Volver al panel</a></p></body>';
    exit;
}

$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if (!$code || !$state || empty($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], $state)) {
    callback_error('Solicitud inválida o expirada. Vuelve a intentarlo desde el panel de administración.');
}
unset($_SESSION['google_oauth_state']);

if (empty($_SESSION['admin_id'])) {
    callback_error('Debes iniciar sesión en el panel de administración antes de conectar Google Calendar.');
}

$tokens = GoogleCalendar::exchangeCode($code);
if (empty($tokens['access_token'])) {
    callback_error('No se pudo conectar con Google. Intenta de nuevo.');
}

$userInfo = GoogleCalendar::getUserInfo($tokens['access_token']);
$email = $userInfo['email'] ?? 'desconocido';

$db = get_db();
$expiry = date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600));
$refreshToken = $tokens['refresh_token'] ?? null;

// Google solo entrega refresh_token la primera vez que se autoriza (o al forzar prompt=consent,
// que ya pedimos). Si por algún motivo no llega uno nuevo, conservamos el anterior.
if (!$refreshToken) {
    $existing = $db->query('SELECT refresh_token FROM google_account WHERE id = 1')->fetchColumn();
    $refreshToken = $existing ?: null;
}

$db->exec('DELETE FROM google_account WHERE id = 1');
$stmt = $db->prepare(
    'INSERT INTO google_account (id, google_email, access_token, refresh_token, token_expiry, calendar_id, updated_at)
     VALUES (1, ?, ?, ?, ?, "primary", ?)'
);
$stmt->execute([$email, $tokens['access_token'], $refreshToken, $expiry, date('Y-m-d H:i:s')]);

header('Location: ' . rtrim(CONFIG['app_url'], '/') . '/admin/index.html?google=connected');
exit;
