<?php

function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dbPath = __DIR__ . '/../storage/citas.sqlite';
    $isNew = !file_exists($dbPath);

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($isNew) {
        bootstrap_schema($pdo);
    }

    return $pdo;
}

function bootstrap_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE appointments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        patient_name TEXT NOT NULL,
        patient_email TEXT NOT NULL,
        patient_phone TEXT NOT NULL,
        motivo TEXT NOT NULL,
        start_datetime TEXT NOT NULL,
        end_datetime TEXT NOT NULL,
        status TEXT NOT NULL DEFAULT 'confirmed',
        google_event_id TEXT,
        reminder_sent_at TEXT,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )");
    $pdo->exec('CREATE INDEX idx_appt_start ON appointments(start_datetime)');
    $pdo->exec('CREATE INDEX idx_appt_status ON appointments(status)');

    $pdo->exec("CREATE TABLE google_account (
        id INTEGER PRIMARY KEY CHECK (id = 1),
        google_email TEXT,
        access_token TEXT,
        refresh_token TEXT,
        token_expiry TEXT,
        calendar_id TEXT DEFAULT 'primary',
        updated_at TEXT
    )");

    $pdo->exec("CREATE TABLE admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        created_at TEXT NOT NULL
    )");
}
