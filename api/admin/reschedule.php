<?php
require_once __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../auth_guard.php';

$data = json_input();

if (!csrf_verify($data['csrf'] ?? null)) {
    json_out(['error' => 'Sesión expirada, recarga la página e intenta de nuevo'], 403);
}

$id = (int)($data['id'] ?? 0);
$date = (string)($data['date'] ?? '');
$time = (string)($data['time'] ?? '');

if (!$id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
    json_out(['error' => 'Datos inválidos'], 422);
}

$db = get_db();
$stmt = $db->prepare('SELECT * FROM appointments WHERE id = ?');
$stmt->execute([$id]);
$appt = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$appt) {
    json_out(['error' => 'Cita no encontrada'], 404);
}

$tz = new DateTimeZone(CONFIG['clinic']['timezone']);
$oldWhen = Mailer::formatDate($appt['start_datetime']);

$newStart = DateTime::createFromFormat('Y-m-d H:i', "$date $time", $tz);
if (!$newStart) {
    json_out(['error' => 'Fecha u hora inválida'], 422);
}
$duration = CONFIG['clinic']['appointment_duration_minutes'];
$newEnd = (clone $newStart)->modify("+$duration minutes");

// Evitar chocar con otra cita confirmada (excluyendo esta misma).
$stmt = $db->prepare(
    "SELECT COUNT(*) FROM appointments
     WHERE status = 'confirmed' AND id != ? AND start_datetime < ? AND end_datetime > ?"
);
$stmt->execute([$id, $newEnd->format('Y-m-d H:i:s'), $newStart->format('Y-m-d H:i:s')]);
if ((int)$stmt->fetchColumn() > 0) {
    json_out(['error' => 'Ese horario ya está ocupado por otra cita'], 409);
}

$db->prepare('UPDATE appointments SET start_datetime = ?, end_datetime = ?, status = ?, reminder_sent_at = NULL, updated_at = ? WHERE id = ?')
   ->execute([$newStart->format('Y-m-d H:i:s'), $newEnd->format('Y-m-d H:i:s'), 'confirmed', date('Y-m-d H:i:s'), $id]);

$updated = array_merge($appt, [
    'start_datetime' => $newStart->format('Y-m-d H:i:s'),
    'end_datetime' => $newEnd->format('Y-m-d H:i:s'),
]);

if (!empty($appt['google_event_id'])) {
    GoogleCalendar::updateEvent($appt['google_event_id'], $updated);
}

Mailer::appointmentRescheduled($updated, $oldWhen);

json_out(['ok' => true]);
