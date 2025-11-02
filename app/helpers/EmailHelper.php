<?php
/**
 * Email Helper Class
 * Handles sending emails via SMTP
 */
class EmailHelper {
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromAddress;
    private $fromName;
    private $encryption;

    public function __construct() {
        $this->host = MAIL_HOST;
        $this->port = MAIL_PORT;
        $this->username = MAIL_USERNAME;
        $this->password = MAIL_PASSWORD;
        $this->fromAddress = MAIL_FROM_ADDRESS;
        $this->fromName = MAIL_FROM_NAME;
        $this->encryption = MAIL_ENCRYPTION;
    }

    /**
     * Send email using SMTP
     */
    public function send($to, $subject, $body, $isHtml = true) {
        try {
            // Create SMTP connection
            $socket = $this->connectToSMTP();
            if (!$socket) {
                throw new Exception('Could not connect to SMTP server');
            }

            // SMTP handshake
            $this->smtpCommand($socket, "EHLO " . $_SERVER['SERVER_NAME']);

            // Start TLS if required
            if (strtolower($this->encryption) === 'tls') {
                $this->smtpCommand($socket, "STARTTLS");
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->smtpCommand($socket, "EHLO " . $_SERVER['SERVER_NAME']);
            }

            // Authenticate
            $this->smtpCommand($socket, "AUTH LOGIN");
            $this->smtpCommand($socket, base64_encode($this->username));
            $this->smtpCommand($socket, base64_encode($this->password));

            // Send email
            $this->smtpCommand($socket, "MAIL FROM: <{$this->fromAddress}>");
            $this->smtpCommand($socket, "RCPT TO: <{$to}>");
            $this->smtpCommand($socket, "DATA");

            // Build email headers
            $headers = $this->buildHeaders($to, $subject, $isHtml);
            $message = $headers . "\r\n" . $body . "\r\n.";

            fputs($socket, $message . "\r\n");
            $this->getResponse($socket);

            // Close connection
            $this->smtpCommand($socket, "QUIT");
            fclose($socket);

            return true;
        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
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
     * Connect to SMTP server
     */
    private function connectToSMTP() {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $socket = stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            throw new Exception("SMTP Connection failed: $errstr ($errno)");
        }

        $this->getResponse($socket);
        return $socket;
    }

    /**
     * Send SMTP command
     */
    private function smtpCommand($socket, $command) {
        fputs($socket, $command . "\r\n");
        return $this->getResponse($socket);
    }

    /**
     * Get SMTP response
     */
    private function getResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }

    /**
     * Build email headers
     */
    private function buildHeaders($to, $subject, $isHtml) {
        $headers = [];
        $headers[] = "From: {$this->fromName} <{$this->fromAddress}>";
        $headers[] = "To: {$to}";
        $headers[] = "Subject: {$subject}";
        $headers[] = "Date: " . date('r');
        $headers[] = "MIME-Version: 1.0";

        if ($isHtml) {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }

        return implode("\r\n", $headers);
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
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #003366; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 30px; }
        .button { display: inline-block; padding: 12px 30px; background: #003366; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . $data['site_name'] . '</h1>
        </div>
        <div class="content">
            <h2>¡Bienvenido, ' . htmlspecialchars($data['nombre']) . '!</h2>
            <p>Gracias por registrarte en nuestro sistema de control de asistencia.</p>
            <p>Para activar tu cuenta, por favor verifica tu dirección de email haciendo clic en el siguiente botón:</p>
            <div style="text-align: center;">
                <a href="' . $data['verification_url'] . '" class="button">Verificar mi cuenta</a>
            </div>
            <p>O copia y pega este enlace en tu navegador:</p>
            <p style="word-break: break-all; color: #003366;">' . $data['verification_url'] . '</p>
            <p><strong>Este enlace expirará en 24 horas.</strong></p>
            <p>Si no creaste esta cuenta, puedes ignorar este mensaje.</p>
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' Alpe Fresh Mexico. Todos los derechos reservados.</p>
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
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #003366; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 30px; }
        .button { display: inline-block; padding: 12px 30px; background: #003366; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . $data['site_name'] . '</h1>
        </div>
        <div class="content">
            <h2>Recuperación de PIN</h2>
            <p>Hola, ' . htmlspecialchars($data['nombre']) . '</p>
            <p>Recibimos una solicitud para restablecer tu PIN de seguridad.</p>
            <p>Para continuar con el proceso, haz clic en el siguiente botón:</p>
            <div style="text-align: center;">
                <a href="' . $data['reset_url'] . '" class="button">Restablecer mi PIN</a>
            </div>
            <p>O copia y pega este enlace en tu navegador:</p>
            <p style="word-break: break-all; color: #003366;">' . $data['reset_url'] . '</p>
            <p><strong>Este enlace expirará en 24 horas.</strong></p>
            <p>Si no solicitaste este cambio, puedes ignorar este mensaje de forma segura.</p>
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
