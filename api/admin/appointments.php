<?php
require_once __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../auth_guard.php';

$db = get_db();
$filter = $_GET['filter'] ?? 'upcoming';

// datetime('now') de SQLite siempre es UTC; start_datetime se guarda en la
// hora local del consultorio (CONFIG['clinic']['timezone']), así que
// comparamos contra un "ahora" calculado por PHP en esa misma zona.
$now = date('Y-m-d H:i:s');

if ($filter === 'past') {
    $stmt = $db->prepare('SELECT * FROM appointments WHERE start_datetime < ? ORDER BY start_datetime DESC LIMIT 150');
    $stmt->execute([$now]);
} elseif ($filter === 'cancelled') {
    $stmt = $db->query("SELECT * FROM appointments WHERE status = 'cancelled' ORDER BY start_datetime DESC LIMIT 150");
} elseif ($filter === 'all') {
    $stmt = $db->query('SELECT * FROM appointments ORDER BY start_datetime DESC LIMIT 300');
} else {
    $stmt = $db->prepare("SELECT * FROM appointments WHERE start_datetime >= ? AND status = 'confirmed' ORDER BY start_datetime ASC LIMIT 150");
    $stmt->execute([$now]);
}

json_out(['appointments' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
