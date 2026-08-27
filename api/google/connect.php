<?php
require_once __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../auth_guard.php';

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

header('Location: ' . GoogleCalendar::getAuthUrl($state));
exit;
