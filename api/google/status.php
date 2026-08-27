<?php
require_once __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../auth_guard.php';

$db = get_db();
$row = $db->query('SELECT google_email, calendar_id, updated_at, refresh_token FROM google_account WHERE id = 1')->fetch(PDO::FETCH_ASSOC);

json_out([
    'connected' => !empty($row['refresh_token']) && GoogleCalendar::isConnected(),
    'email' => $row['google_email'] ?? null,
    'updated_at' => $row['updated_at'] ?? null,
]);
