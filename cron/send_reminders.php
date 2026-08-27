<?php
// Recordatorio de citas 48 horas antes.
// Ejecutar por CLI vía cron: php cron/send_reminders.php
// O, si el hosting solo permite cron por URL: https://tu-dominio.com/cron/send_reminders.php?token=TU_CRON_SECRET
// Se recomienda correrlo cada hora.

require_once __DIR__ . '/../api/bootstrap.php';

$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    $token = $_GET['token'] ?? '';
    if (!hash_equals(CONFIG['cron_secret'], $token)) {
        http_response_code(403);
        header('Content-Type: text/plain');
        echo 'Forbidden';
        exit;
    }
}

$tz = new DateTimeZone(CONFIG['clinic']['timezone']);
$windowStart = (new DateTime('+47 hours', $tz))->format('Y-m-d H:i:s');
$windowEnd = (new DateTime('+49 hours', $tz))->format('Y-m-d H:i:s');

$db = get_db();
$stmt = $db->prepare(
    "SELECT * FROM appointments
     WHERE status = 'confirmed' AND reminder_sent_at IS NULL AND start_datetime BETWEEN ? AND ?"
);
$stmt->execute([$windowStart, $windowEnd]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sent = 0;
foreach ($appointments as $appt) {
    Mailer::appointmentReminder($appt);
    $db->prepare('UPDATE appointments SET reminder_sent_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $appt['id']]);
    $sent++;
}

$message = "Recordatorios enviados: $sent" . PHP_EOL;

if ($isCli) {
    echo $message;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
}
