<?php

session_start();

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Falta api/config.php. Copia config.php.example a config.php y completa los valores.']);
    exit;
}

define('CONFIG', require $configPath);
date_default_timezone_set(CONFIG['clinic']['timezone']);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/GoogleCalendar.php';
require_once __DIR__ . '/Mailer.php';

header('Content-Type: application/json; charset=utf-8');

function json_input(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_out($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function rate_limit_check(string $key, int $max, int $windowSeconds): bool {
    $dir = __DIR__ . '/../storage/rate';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $file = $dir . '/' . preg_replace('/[^a-zA-Z0-9_.]/', '_', $key) . '.json';
    $now = time();
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    if (!is_array($data)) $data = [];
    $data = array_values(array_filter($data, fn($t) => $t > $now - $windowSeconds));
    if (count($data) >= $max) return false;
    $data[] = $now;
    file_put_contents($file, json_encode($data));
    return true;
}
