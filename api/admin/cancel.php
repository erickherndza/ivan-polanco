<?php
require_once __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../auth_guard.php';

$data = json_input();

if (!csrf_verify($data['csrf'] ?? null)) {
    json_out(['error' => 'Sesión expirada, recarga la página e intenta de nuevo'], 403);
}

$id = (int)($data['id'] ?? 0);
if (!$id) {
    json_out(['error' => 'ID inválido'], 422);
}

$db = get_db();
$stmt = $db->prepare('SELECT * FROM appointments WHERE id = ?');
$stmt->execute([$id]);
$appt = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$appt) {
    json_out(['error' => 'Cita no encontrada'], 404);
}

$db->prepare("UPDATE appointments SET status = 'cancelled', updated_at = ? WHERE id = ?")
   ->execute([date('Y-m-d H:i:s'), $id]);

if (!empty($appt['google_event_id'])) {
    GoogleCalendar::deleteEvent($appt['google_event_id']);
}

Mailer::appointmentCancelled($appt);

json_out(['ok' => true]);
