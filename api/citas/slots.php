<?php
require_once __DIR__ . '/../bootstrap.php';

$date = $_GET['date'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_out(['error' => 'Fecha inválida'], 400);
}

$tz = new DateTimeZone(CONFIG['clinic']['timezone']);
$dt = DateTime::createFromFormat('Y-m-d', $date, $tz);
$today = new DateTime('today', $tz);
if (!$dt || $dt < $today) {
    json_out(['slots' => []]);
}

$dow = (int)$dt->format('w');
$hours = CONFIG['clinic']['business_hours'][$dow] ?? null;
if (!$hours) {
    json_out(['slots' => []]);
}

[$openTime, $closeTime] = $hours;
$duration = CONFIG['clinic']['appointment_duration_minutes'];

$slotCursor = new DateTime("$date $openTime", $tz);
$closeDt = new DateTime("$date $closeTime", $tz);

$db = get_db();
$stmt = $db->prepare("SELECT start_datetime, end_datetime FROM appointments WHERE status = 'confirmed' AND date(start_datetime) = ?");
$stmt->execute([$date]);
$busyRanges = array_map(
    fn($b) => [strtotime($b['start_datetime']), strtotime($b['end_datetime'])],
    $stmt->fetchAll(PDO::FETCH_ASSOC)
);

if (GoogleCalendar::isConnected()) {
    $busyRanges = array_merge($busyRanges, GoogleCalendar::getBusyIntervals($date));
}

$slots = [];
$now = time();
$minLeadTime = $now + 3600; // al menos 1 hora de anticipación

while (($slotCursor->getTimestamp() + $duration * 60) <= $closeDt->getTimestamp()) {
    $slotStartTs = $slotCursor->getTimestamp();
    $slotEndTs = $slotStartTs + $duration * 60;

    $overlaps = false;
    foreach ($busyRanges as [$bStart, $bEnd]) {
        if ($slotStartTs < $bEnd && $slotEndTs > $bStart) {
            $overlaps = true;
            break;
        }
    }

    if ($slotStartTs >= $minLeadTime && !$overlaps) {
        $slots[] = $slotCursor->format('H:i');
    }

    $slotCursor->modify("+$duration minutes");
}

json_out(['slots' => $slots]);
