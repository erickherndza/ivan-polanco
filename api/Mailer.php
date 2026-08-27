<?php

$phpMailerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($phpMailerAutoload)) {
    require_once $phpMailerAutoload;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer {
    public static function send(string $toEmail, string $toName, string $subject, string $bodyHtml): bool {
        if (!class_exists(PHPMailer::class)) {
            error_log('Mailer: PHPMailer no está instalado. Ejecuta "composer install" dentro de api/.');
            return false;
        }

        $smtp = CONFIG['smtp'];
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $smtp['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['username'];
            $mail->Password = $smtp['password'];
            $mail->SMTPSecure = $smtp['secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtp['port'];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($smtp['from_email'], $smtp['from_name']);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log('Mailer error: ' . $mail->ErrorInfo);
            return false;
        }
    }

    public static function appointmentConfirmation(array $appt): void {
        $clinic = CONFIG['clinic']['name'];
        $when = self::formatDate($appt['start_datetime']);

        self::send($appt['patient_email'], $appt['patient_name'], "Cita confirmada - $clinic", self::wrap("
            <h2>Tu cita ha sido confirmada</h2>
            <p>Hola {$appt['patient_name']},</p>
            <p>Tu cita con <strong>$clinic</strong> quedó agendada para:</p>
            <p style='font-size:18px;font-weight:600;color:#00536D;'>$when</p>
            <p><strong>Motivo:</strong> {$appt['motivo']}</p>
            <p>Si necesitas reagendar o cancelar, contáctanos respondiendo este correo o llamando al consultorio.</p>
        "));

        $notify = CONFIG['clinic']['notify_email'] ?? null;
        if ($notify) {
            self::send($notify, $clinic, "Nueva cita: {$appt['patient_name']} - $when", self::wrap("
                <h2>Nueva cita agendada</h2>
                <p><strong>Paciente:</strong> {$appt['patient_name']}</p>
                <p><strong>Teléfono:</strong> {$appt['patient_phone']}</p>
                <p><strong>Email:</strong> {$appt['patient_email']}</p>
                <p><strong>Fecha:</strong> $when</p>
                <p><strong>Motivo:</strong> {$appt['motivo']}</p>
            "));
        }
    }

    public static function appointmentRescheduled(array $appt, string $oldWhen): void {
        $clinic = CONFIG['clinic']['name'];
        $newWhen = self::formatDate($appt['start_datetime']);

        self::send($appt['patient_email'], $appt['patient_name'], "Tu cita fue reprogramada - $clinic", self::wrap("
            <h2>Tu cita fue modificada</h2>
            <p>Hola {$appt['patient_name']},</p>
            <p>Tu cita con <strong>$clinic</strong> cambió de horario:</p>
            <p><strong>Antes:</strong> $oldWhen</p>
            <p><strong>Ahora:</strong> <span style='font-size:18px;font-weight:600;color:#00536D;'>$newWhen</span></p>
            <p>Si este nuevo horario no te funciona, contáctanos para coordinar otro.</p>
        "));
    }

    public static function appointmentCancelled(array $appt): void {
        $clinic = CONFIG['clinic']['name'];
        $when = self::formatDate($appt['start_datetime']);

        self::send($appt['patient_email'], $appt['patient_name'], "Tu cita fue cancelada - $clinic", self::wrap("
            <h2>Tu cita fue cancelada</h2>
            <p>Hola {$appt['patient_name']},</p>
            <p>Tu cita del $when con <strong>$clinic</strong> ha sido cancelada.</p>
            <p>Si deseas agendar una nueva fecha, visita nuestro sitio o contáctanos directamente.</p>
        "));
    }

    public static function appointmentReminder(array $appt): void {
        $clinic = CONFIG['clinic']['name'];
        $when = self::formatDate($appt['start_datetime']);

        self::send($appt['patient_email'], $appt['patient_name'], "Recordatorio: tu cita es en 48 horas - $clinic", self::wrap("
            <h2>Recordatorio de tu cita</h2>
            <p>Hola {$appt['patient_name']},</p>
            <p>Te recordamos que tienes una cita con <strong>$clinic</strong> en aproximadamente 48 horas:</p>
            <p style='font-size:18px;font-weight:600;color:#00536D;'>$when</p>
            <p><strong>Motivo:</strong> {$appt['motivo']}</p>
            <p>Si no puedes asistir, contáctanos con anticipación para reagendar.</p>
        "));
    }

    public static function formatDate(string $datetime): string {
        $dt = new DateTime($datetime, new DateTimeZone(CONFIG['clinic']['timezone']));
        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $dia = $dias[(int)$dt->format('w')];
        $mes = $meses[(int)$dt->format('n')];
        return "$dia " . $dt->format('j') . " de $mes, " . $dt->format('g:i A');
    }

    private static function wrap(string $inner): string {
        $clinic = htmlspecialchars(CONFIG['clinic']['name']);
        return "<div style='font-family:Arial,sans-serif;max-width:520px;margin:0 auto;padding:24px;color:#16313D;'>$inner<hr style='border:none;border-top:1px solid #DCE8EC;margin:24px 0;'><p style='font-size:12px;color:#5B7382;'>$clinic</p></div>";
    }
}
