<?php
/**
 * Email Helper Class
 * Handles sending emails via SMTP using PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class EmailHelper {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }

    /**
     * Configure SMTP settings
     */
    private function configureSMTP() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = MAIL_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = MAIL_USERNAME;
            $this->mailer->Password = MAIL_PASSWORD;
            $this->mailer->SMTPSecure = MAIL_ENCRYPTION;
            $this->mailer->Port = MAIL_PORT;
            $this->mailer->CharSet = 'UTF-8';

            // Sender info
            $this->mailer->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);

            // Debug settings (disable in production)
            if (ENVIRONMENT === 'development') {
                $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
            }
        } catch (Exception $e) {
            error_log("EmailHelper Configuration Error: " . $e->getMessage());
        }
    }

    /**
     * Send email
     */
    public function send($to, $subject, $body, $isHtml = true) {
        try {
            // Recipients
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);

            // Content
            $this->mailer->isHTML($isHtml);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;

            if ($isHtml) {
                // Also set plain text version
                $this->mailer->AltBody = strip_tags($body);
            }

            $result = $this->mailer->send();

            // Log successful send
            if ($result && ENVIRONMENT === 'development') {
                error_log("Email sent successfully to: $to");
            }

            return $result;
        } catch (Exception $e) {
            error_log("Email Error: {$this->mailer->ErrorInfo}");
            return false;
        }
    }

    /**
     * Send verification email
     */
    public function sendVerificationEmail($email, $nombre, $token) {
        $verificationUrl = url('auth/verify/' . $token);

        $subject = "Verifica tu cuenta - " . SITE_NAME;

        $body = $this->getEmailTemplate('verification', [
            'nombre' => $nombre,
            'verification_url' => $verificationUrl,
            'site_name' => SITE_NAME
        ]);

        return $this->send($email, $subject, $body);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email, $nombre, $token) {
        $resetUrl = url('auth/reset-password/' . $token);

        $subject = "Recuperar PIN - " . SITE_NAME;

        $body = $this->getEmailTemplate('password-reset', [
            'nombre' => $nombre,
            'reset_url' => $resetUrl,
            'site_name' => SITE_NAME
        ]);

        return $this->send($email, $subject, $body);
    }

    /**
     * Send welcome email
     */
    public function sendWelcomeEmail($email, $nombre, $numeroEmpleado) {
        $subject = "Bienvenido a " . SITE_NAME;

        $body = $this->getEmailTemplate('welcome', [
            'nombre' => $nombre,
            'numero_empleado' => $numeroEmpleado,
            'site_name' => SITE_NAME,
            'login_url' => url('auth/login')
        ]);

        return $this->send($email, $subject, $body);
    }

    /**
     * Get email template
     */
    private function getEmailTemplate($template, $data = []) {
        $templates = [
            'verification' => '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
            background: white;
        }
        .content h2 {
            color: #003366;
            margin-top: 0;
            font-size: 22px;
        }
        .button {
            display: inline-block;
            padding: 14px 40px;
            background: #003366;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        .button:hover {
            background: #004080;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: #666;
            background: #f9f9f9;
            border-top: 1px solid #eee;
        }
        .url-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            word-break: break-all;
            color: #003366;
            font-size: 13px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #fdb714;
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . htmlspecialchars($data['site_name']) . '</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Sistema de Control de Asistencia</p>
        </div>
        <div class="content">
            <h2>¡Bienvenido, ' . htmlspecialchars($data['nombre']) . '!</h2>
            <p>Gracias por registrarte en nuestro sistema de control de asistencia.</p>
            <p>Para activar tu cuenta y comenzar a usar el sistema, necesitas verificar tu dirección de email.</p>

            <div class="button-container">
                <a href="' . $data['verification_url'] . '" class="button">Verificar mi cuenta</a>
            </div>

            <p style="font-size: 14px; color: #666;">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
            <div class="url-box">' . $data['verification_url'] . '</div>

            <div class="warning">
                <strong>⏱ Este enlace expirará en 24 horas.</strong>
            </div>

            <p style="font-size: 14px; color: #666; margin-top: 30px;">Si no creaste esta cuenta, puedes ignorar este mensaje de forma segura.</p>
        </div>
        <div class="footer">
            <p style="margin: 5px 0;">&copy; ' . date('Y') . ' Alpe Fresh Mexico</p>
            <p style="margin: 5px 0; color: #999;">Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>
            ',

            'password-reset' => '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
            background: white;
        }
        .content h2 {
            color: #003366;
            margin-top: 0;
            font-size: 22px;
        }
        .button {
            display: inline-block;
            padding: 14px 40px;
            background: #003366;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: #666;
            background: #f9f9f9;
            border-top: 1px solid #eee;
        }
        .url-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            word-break: break-all;
            color: #003366;
            font-size: 13px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #fdb714;
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        .security-notice {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . htmlspecialchars($data['site_name']) . '</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Sistema de Control de Asistencia</p>
        </div>
        <div class="content">
            <h2>Recuperación de PIN</h2>
            <p>Hola, ' . htmlspecialchars($data['nombre']) . '</p>
            <p>Recibimos una solicitud para restablecer tu PIN de seguridad.</p>

            <div class="button-container">
                <a href="' . $data['reset_url'] . '" class="button">Restablecer mi PIN</a>
            </div>

            <p style="font-size: 14px; color: #666;">Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
            <div class="url-box">' . $data['reset_url'] . '</div>

            <div class="warning">
                <strong>⏱ Este enlace expirará en 24 horas.</strong>
            </div>

            <div class="security-notice">
                <strong>🔒 Nota de seguridad:</strong> Si no solicitaste este cambio, puedes ignorar este mensaje de forma segura. Tu PIN permanecerá sin cambios.
            </div>
        </div>
        <div class="footer">
            <p style="margin: 5px 0;">&copy; ' . date('Y') . ' Alpe Fresh Mexico</p>
            <p style="margin: 5px 0; color: #999;">Todos los derechos reservados</p>
        </div>
    </div>
</body>
</html>
            ',

            'welcome' => '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .content {
            padding: 40px 30px;
        }
        .button {
            display: inline-block;
            padding: 14px 40px;
            background: #003366;
            color: white !important;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .info-box {
            background: #f0f8ff;
            border-left: 4px solid #003366;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: #666;
            background: #f9f9f9;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Bienvenido a Alpe Fresh!</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">Sistema de Control de Asistencia</p>
        </div>
        <div class="content">
            <h2>¡Tu cuenta ha sido verificada exitosamente!</h2>
            <p>Hola <strong>' . htmlspecialchars($data['nombre']) . '</strong>,</p>
            <p>Tu cuenta ha sido activada y ya puedes acceder al sistema de control de asistencia.</p>

            <div class="info-box">
                <strong>Tu número de empleado:</strong> ' . htmlspecialchars($data['numero_empleado']) . '
            </div>

            <p>Ahora puedes:</p>
            <ul>
                <li>✓ Registrar tu entrada y salida</li>
                <li>✓ Consultar tu historial de asistencia</li>
                <li>✓ Ver tus horas trabajadas</li>
                <li>✓ Actualizar tu perfil</li>
            </ul>

            <div class="button-container">
                <a href="' . $data['login_url'] . '" class="button">Iniciar Sesión</a>
            </div>
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' Alpe Fresh Mexico. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
            '
        ];

        return $templates[$template] ?? '';
    }
}
