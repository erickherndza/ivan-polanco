<?php
require_once __DIR__ . '/../bootstrap.php';

if (!rate_limit_check('booking_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 3600)) {
    json_out(['error' => 'Demasiados intentos. Intenta de nuevo más tarde o llámanos directamente.'], 429);
}

$data = json_input();

// Honeypot: los bots suelen llenar cualquier campo oculto.
if (!empty($data['website'])) {
    json_out(['ok' => true]);
}

$name = trim((string)($data['name'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$phone = trim((string)($data['phone'] ?? ''));
$motivo = trim((string)($data['motivo'] ?? ''));
$date = (string)($data['date'] ?? '');
$time = (string)($data['time'] ?? '');

if (mb_strlen($name) < 3 || !preg_match('/^[\p{L}\s.]+$/u', $name)) {
    json_out(['error' => 'Ingresa tu nombre completo'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_out(['error' => 'Correo electrónico inválido'], 422);
}
if (!preg_match('/^[\d\s\-\+\(\)]{7,20}$/', $phone)) {
    json_out(['error' => 'Teléfono inválido'], 422);
}
if (mb_strlen($motivo) < 5) {
    json_out(['error' => 'Cuéntanos brevemente el motivo de tu consulta'], 422);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
    json_out(['error' => 'Selecciona fecha y horario'], 422);
}

$tz = new DateTimeZone(CONFIG['clinic']['timezone']);
$start = DateTime::createFromFormat('Y-m-d H:i', "$date $time", $tz);
if (!$start || $start < new DateTime('+1 hour', $tz)) {
    json_out(['error' => 'Ese horario ya no está disponible, elige otro'], 409);
}

$duration = CONFIG['clinic']['appointment_duration_minutes'];
$end = (clone $start)->modify("+$duration minutes");

$db = get_db();

// Revalida disponibilidad justo antes de insertar (evita doble reserva simultánea).
$stmt = $db->prepare(
    "SELECT COUNT(*) FROM appointments
     WHERE status = 'confirmed' AND start_datetime < ? AND end_datetime > ?"
);
$stmt->execute([$end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')]);
if ((int)$stmt->fetchColumn() > 0) {
    json_out(['error' => 'Ese horario ya fue reservado, elige otro'], 409);
}

$now = date('Y-m-d H:i:s');
$stmt = $db->prepare(
    "INSERT INTO appointments
     (patient_name, patient_email, patient_phone, motivo, start_datetime, end_datetime, status, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, 'confirmed', ?, ?)"
);
$stmt->execute([$name, $email, $phone, $motivo, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $now, $now]);
$id = (int)$db->lastInsertId();

$appt = [
    'id' => $id,
    'patient_name' => $name,
    'patient_email' => $email,
    'patient_phone' => $phone,
    'motivo' => $motivo,
    'start_datetime' => $start->format('Y-m-d H:i:s'),
    'end_datetime' => $end->format('Y-m-d H:i:s'),
];

$eventId = GoogleCalendar::createEvent($appt);
if ($eventId) {
    $db->prepare('UPDATE appointments SET google_event_id = ? WHERE id = ?')->execute([$eventId, $id]);
}

Mailer::appointmentConfirmation($appt);

json_out(['ok' => true, 'id' => $id]);
