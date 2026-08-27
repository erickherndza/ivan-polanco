<?php
// Ejecutar SOLO por línea de comandos: php api/setup_admin.php
// Crea o actualiza la contraseña de un usuario del panel de administración.

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Este script solo se puede ejecutar por línea de comandos.');
}

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    fwrite(STDERR, "Falta api/config.php. Copia config.php.example primero.\n");
    exit(1);
}
define('CONFIG', require $configPath);
require_once __DIR__ . '/db.php';

fwrite(STDOUT, "Usuario admin: ");
$username = trim((string)fgets(STDIN));

fwrite(STDOUT, "Contraseña (mínimo 8 caracteres): ");
system('stty -echo');
$password = trim((string)fgets(STDIN));
system('stty echo');
fwrite(STDOUT, PHP_EOL);

if (strlen($username) < 3) {
    fwrite(STDERR, "El usuario debe tener al menos 3 caracteres.\n");
    exit(1);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "La contraseña debe tener al menos 8 caracteres.\n");
    exit(1);
}

$db = get_db();
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare(
    'INSERT INTO admin_users (username, password_hash, created_at) VALUES (?, ?, ?)
     ON CONFLICT(username) DO UPDATE SET password_hash = excluded.password_hash'
);
$stmt->execute([$username, $hash, date('Y-m-d H:i:s')]);

fwrite(STDOUT, "Listo: usuario '$username' creado/actualizado.\n");
