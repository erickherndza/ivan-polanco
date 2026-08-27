<?php

class GoogleCalendar {
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';
    private const CALENDAR_API = 'https://www.googleapis.com/calendar/v3';

    public static function getAuthUrl(string $state): string {
        $g = CONFIG['google'];
        $params = [
            'client_id' => $g['client_id'],
            'redirect_uri' => $g['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/calendar.readonly openid email',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ];
        return self::AUTH_URL . '?' . http_build_query($params);
    }

    public static function exchangeCode(string $code): array {
        $g = CONFIG['google'];
        return self::post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => $g['client_id'],
            'client_secret' => $g['client_secret'],
            'redirect_uri' => $g['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);
    }

    public static function refreshAccessToken(string $refreshToken): array {
        $g = CONFIG['google'];
        return self::post(self::TOKEN_URL, [
            'refresh_token' => $refreshToken,
            'client_id' => $g['client_id'],
            'client_secret' => $g['client_secret'],
            'grant_type' => 'refresh_token',
        ]);
    }

    public static function getUserInfo(string $accessToken): array {
        return self::curl(self::USERINFO_URL, 'GET', null, ['Authorization: Bearer ' . $accessToken]);
    }

    /** Devuelve un access token válido, refrescándolo si hace falta. Null si no hay cuenta conectada. */
    public static function getValidAccessToken(): ?string {
        $db = get_db();
        $row = $db->query('SELECT * FROM google_account WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
        if (!$row || empty($row['refresh_token'])) return null;

        $expiry = $row['token_expiry'] ? strtotime($row['token_expiry']) : 0;
        if (!empty($row['access_token']) && $expiry > time() + 60) {
            return $row['access_token'];
        }

        $tokens = self::refreshAccessToken($row['refresh_token']);
        if (empty($tokens['access_token'])) return null;

        $newExpiry = date('Y-m-d H:i:s', time() + ($tokens['expires_in'] ?? 3600));
        $db->prepare('UPDATE google_account SET access_token = ?, token_expiry = ?, updated_at = ? WHERE id = 1')
           ->execute([$tokens['access_token'], $newExpiry, date('Y-m-d H:i:s')]);

        return $tokens['access_token'];
    }

    public static function isConnected(): bool {
        return self::getValidAccessToken() !== null;
    }

    public static function createEvent(array $appointment): ?string {
        $token = self::getValidAccessToken();
        if (!$token) return null;

        $event = self::buildEvent($appointment);
        $url = self::CALENDAR_API . '/calendars/' . urlencode(self::calendarId()) . '/events?sendUpdates=none';
        $result = self::curl($url, 'POST', json_encode($event), self::jsonHeaders($token));
        return $result['id'] ?? null;
    }

    public static function updateEvent(string $eventId, array $appointment): bool {
        $token = self::getValidAccessToken();
        if (!$token) return false;

        $event = self::buildEvent($appointment);
        $url = self::CALENDAR_API . '/calendars/' . urlencode(self::calendarId()) . '/events/' . urlencode($eventId) . '?sendUpdates=none';
        $result = self::curl($url, 'PATCH', json_encode($event), self::jsonHeaders($token));
        return isset($result['id']);
    }

    public static function deleteEvent(string $eventId): bool {
        $token = self::getValidAccessToken();
        if (!$token) return false;

        $url = self::CALENDAR_API . '/calendars/' . urlencode(self::calendarId()) . '/events/' . urlencode($eventId) . '?sendUpdates=none';
        self::curl($url, 'DELETE', null, self::jsonHeaders($token));
        return true;
    }

    /** Intervalos ocupados [[inicio,fin], ...] (timestamps) para el día dado (Y-m-d). */
    public static function getBusyIntervals(string $date): array {
        $token = self::getValidAccessToken();
        if (!$token) return [];

        $tz = CONFIG['clinic']['timezone'];
        $body = [
            'timeMin' => self::toRfc3339("$date 00:00:00"),
            'timeMax' => self::toRfc3339("$date 23:59:59"),
            'timeZone' => $tz,
            'items' => [['id' => self::calendarId()]],
        ];

        $result = self::curl(self::CALENDAR_API . '/freeBusy', 'POST', json_encode($body), self::jsonHeaders($token));
        $busy = $result['calendars'][self::calendarId()]['busy'] ?? [];
        return array_map(fn($b) => [strtotime($b['start']), strtotime($b['end'])], $busy);
    }

    private static function buildEvent(array $appointment): array {
        return [
            'summary' => 'Cita: ' . $appointment['patient_name'],
            'description' => "Motivo: {$appointment['motivo']}\nTeléfono: {$appointment['patient_phone']}\nEmail: {$appointment['patient_email']}",
            'start' => ['dateTime' => self::toRfc3339($appointment['start_datetime']), 'timeZone' => CONFIG['clinic']['timezone']],
            'end' => ['dateTime' => self::toRfc3339($appointment['end_datetime']), 'timeZone' => CONFIG['clinic']['timezone']],
        ];
    }

    private static function calendarId(): string {
        $db = get_db();
        return $db->query('SELECT calendar_id FROM google_account WHERE id = 1')->fetchColumn() ?: 'primary';
    }

    private static function toRfc3339(string $datetime): string {
        $dt = new DateTime($datetime, new DateTimeZone(CONFIG['clinic']['timezone']));
        return $dt->format(DateTime::RFC3339);
    }

    private static function jsonHeaders(string $token): array {
        return ['Authorization: Bearer ' . $token, 'Content-Type: application/json'];
    }

    private static function post(string $url, array $params): array {
        return self::curl($url, 'POST', http_build_query($params), ['Content-Type: application/x-www-form-urlencoded']);
    }

    private static function curl(string $url, string $method, ?string $body, array $headers): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            error_log('GoogleCalendar cURL error: ' . $err);
            return [];
        }

        // Google responde 204 sin cuerpo en DELETE exitosos.
        if ($response === '' || $response === false) {
            if ($httpCode >= 200 && $httpCode < 300) return ['_status' => $httpCode];
            error_log("GoogleCalendar respuesta vacía, HTTP $httpCode");
            return [];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            error_log("GoogleCalendar respuesta inesperada (HTTP $httpCode): " . substr((string)$response, 0, 300));
            return [];
        }
        if (isset($decoded['error'])) {
            error_log("GoogleCalendar API error (HTTP $httpCode): " . json_encode($decoded['error']));
        }
        return $decoded;
    }
}
