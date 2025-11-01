<?php
/**
 * Servicio de Email - Sistema de Asistencia
 * Maneja el envío de correos usando SMTP de Hostinger
 */

class EmailService {
    private $config;
    private $socket;

    public function __construct() {
        $this->config = include __DIR__ . '/../config/email.php';
    }

    /**
     * Envía un email usando SMTP
     */
    public function send($to, $subject, $body, $recipientName = '', $isHtml = true) {
        try {
            // En modo test, solo registrar el email
            if ($this->config['environment']['test_mode']) {
                return $this->logEmail($to, $subject, $body);
            }

            // Conectar al servidor SMTP
            $smtp = $this->config['smtp'];
            $this->socket = fsockopen($smtp['host'], $smtp['port'], $errno, $errstr, $smtp['timeout']);

            if (!$this->socket) {
                throw new Exception("No se pudo conectar al servidor SMTP: $errstr ($errno)");
            }

            // Leer respuesta inicial
            $response = $this->readResponse();
            if (!$this->checkResponse($response, '220')) {
                throw new Exception("Respuesta inicial SMTP inválida: $response");
            }

            // EHLO
            $this->sendCommand("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $this->readMultilineResponse('250');

            // STARTTLS para puerto 587
            if ($smtp['port'] == 587) {
                $this->sendCommand("STARTTLS");
                if (!$this->checkResponse($this->readResponse(), '220')) {
                    throw new Exception("Error iniciando TLS");
                }

                // Habilitar cifrado
                if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new Exception("No se pudo habilitar cifrado TLS");
                }

                // EHLO de nuevo después de TLS
                $this->sendCommand("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
                $this->readMultilineResponse('250');
            }

            // Autenticación
            $this->sendCommand("AUTH LOGIN");
            if (!$this->checkResponse($this->readResponse(), '334')) {
                throw new Exception("Error iniciando autenticación");
            }

            // Enviar usuario y contraseña codificados en base64
            $this->sendCommand(base64_encode($smtp['username']));
            if (!$this->checkResponse($this->readResponse(), '334')) {
                throw new Exception("Error enviando usuario");
            }

            $this->sendCommand(base64_encode($smtp['password']));
            if (!$this->checkResponse($this->readResponse(), '235')) {
                throw new Exception("Error de autenticación");
            }

            // MAIL FROM
            $this->sendCommand("MAIL FROM: <{$smtp['from_email']}>");
            if (!$this->checkResponse($this->readResponse(), '250')) {
                throw new Exception("Error en MAIL FROM");
            }

            // RCPT TO
            $this->sendCommand("RCPT TO: <$to>");
            if (!$this->checkResponse($this->readResponse(), '250')) {
                throw new Exception("Error en RCPT TO");
            }

            // DATA
            $this->sendCommand("DATA");
            if (!$this->checkResponse($this->readResponse(), '354')) {
                throw new Exception("Error iniciando DATA");
            }

            // Construir headers y body
            $headers = $this->buildHeaders($to, $subject, $recipientName, $isHtml);
            $message = $headers . "\r\n" . $body;

            // Enviar mensaje
            $this->sendCommand($message . "\r\n.");
            if (!$this->checkResponse($this->readResponse(), '250')) {
                throw new Exception("Error enviando mensaje");
            }

            // QUIT
            $this->sendCommand("QUIT");
            $this->readResponse();

            fclose($this->socket);

            return true;

        } catch (Exception $e) {
            if ($this->socket) {
                fclose($this->socket);
            }
            error_log("Error enviando email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Construye los headers del email
     */
    private function buildHeaders($to, $subject, $recipientName, $isHtml) {
        $smtp = $this->config['smtp'];
        $fromName = $smtp['from_name'];
        $fromEmail = $smtp['from_email'];

        $headers = "From: \"$fromName\" <$fromEmail>\r\n";
        $headers .= "To: " . ($recipientName ? "\"$recipientName\" " : "") . "<$to>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: <" . uniqid() . "@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\n";

        if ($isHtml) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }

        $headers .= "X-Mailer: Sistema de Asistencia AlpeFresh\r\n";

        return $headers;
    }

    /**
     * Envía un comando SMTP
     */
    private function sendCommand($command) {
        fwrite($this->socket, $command . "\r\n");
    }

    /**
     * Lee la respuesta del servidor
     */
    private function readResponse() {
        return fgets($this->socket, 512);
    }

    /**
     * Lee respuestas multi-línea
     */
    private function readMultilineResponse($expectedCode) {
        $response = $this->readResponse();
        if (!$this->checkResponse($response, $expectedCode)) {
            throw new Exception("Respuesta inesperada: $response");
        }

        // Leer líneas adicionales si hay
        while (substr($response, 3, 1) == '-') {
            $response = $this->readResponse();
        }

        return true;
    }

    /**
     * Verifica el código de respuesta
     */
    private function checkResponse($response, $expectedCode) {
        return substr($response, 0, 3) == $expectedCode;
    }

    /**
     * Registra emails en modo test/debug
     */
    private function logEmail($to, $subject, $body) {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/emails_' . date('Y-m-d') . '.log';
        $logEntry = sprintf(
            "[%s] To: %s | Subject: %s | Length: %d chars\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            strlen($body)
        );

        file_put_contents($logFile, $logEntry, FILE_APPEND);
        return true;
    }

    /**
     * Envía email de bienvenida a nuevo usuario
     */
    public function sendWelcomeEmail($email, $name, $isPending = true) {
        $template = $this->config['templates'][$isPending ? 'account_pending' : 'welcome'];

        $body = $this->getWelcomeTemplate($name, $isPending);

        return $this->send($email, $template['subject'], $body, $name);
    }

    /**
     * Envía email de restablecimiento de contraseña
     */
    public function sendPasswordResetEmail($email, $name, $resetToken) {
        $template = $this->config['templates']['password_reset'];

        $resetUrl = 'https://asistencia.alpefresh.app/reset-password.php?token=' . $resetToken;
        $body = $this->getPasswordResetTemplate($name, $resetUrl);

        return $this->send($email, $template['subject'], $body, $name);
    }

    /**
     * Envía notificación a admin sobre nuevo registro
     */
    public function sendNewRegistrationNotification($adminEmail, $userName, $userEmail) {
        $template = $this->config['templates']['admin_new_registration'];

        $body = $this->getNewRegistrationTemplate($userName, $userEmail);

        return $this->send($adminEmail, $template['subject'], $body, 'Administrador');
    }

    /**
     * Envía email de cuenta aprobada
     */
    public function sendAccountApprovedEmail($email, $name) {
        $template = $this->config['templates']['account_approved'];

        $body = $this->getAccountApprovedTemplate($name);

        return $this->send($email, $template['subject'], $body, $name);
    }

    /**
     * Plantilla HTML para email de bienvenida
     */
    private function getWelcomeTemplate($name, $isPending) {
        $message = $isPending
            ? "Tu registro ha sido recibido exitosamente. Tu cuenta está pendiente de aprobación por un administrador."
            : "¡Bienvenido al Sistema de Asistencia de AlpeFresh!";

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #003366 0%, #004080 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 12px 30px; background: #fdb714; color: #003366; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Sistema de Asistencia AlpeFresh</h1>
                </div>
                <div class='content'>
                    <h2>Hola $name,</h2>
                    <p>$message</p>
                    " . ($isPending ? "
                    <p>Te notificaremos por correo electrónico una vez que tu cuenta sea aprobada.</p>
                    <p>Este proceso generalmente toma menos de 24 horas hábiles.</p>
                    " : "
                    <p>Ya puedes acceder al sistema con tu correo electrónico y contraseña.</p>
                    <a href='https://asistencia.alpefresh.app/login.php' class='button'>Iniciar Sesión</a>
                    ") . "
                    <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
                    <p>Saludos,<br>El equipo de AlpeFresh</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " AlpeFresh. Todos los derechos reservados.</p>
                    <p>Este es un correo automático, por favor no responder.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Plantilla para restablecimiento de contraseña
     */
    private function getPasswordResetTemplate($name, $resetUrl) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #003366 0%, #004080 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 12px 30px; background: #fdb714; color: #003366; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Restablecer Contraseña</h1>
                </div>
                <div class='content'>
                    <h2>Hola $name,</h2>
                    <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
                    <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
                    <center>
                        <a href='$resetUrl' class='button'>Restablecer Contraseña</a>
                    </center>
                    <div class='warning'>
                        <strong>⚠️ Importante:</strong> Este enlace expirará en 1 hora por seguridad.
                    </div>
                    <p>Si no solicitaste restablecer tu contraseña, puedes ignorar este correo.</p>
                    <p>Saludos,<br>El equipo de AlpeFresh</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " AlpeFresh. Todos los derechos reservados.</p>
                    <p>Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                    <small>$resetUrl</small></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Plantilla para notificación de nuevo registro
     */
    private function getNewRegistrationTemplate($userName, $userEmail) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px; }
                .info-box { background: white; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .button { display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⚠️ Nuevo Registro Pendiente</h1>
                </div>
                <div class='content'>
                    <h2>Administrador,</h2>
                    <p>Se ha registrado un nuevo usuario que requiere aprobación:</p>
                    <div class='info-box'>
                        <p><strong>Nombre:</strong> $userName</p>
                        <p><strong>Email:</strong> $userEmail</p>
                        <p><strong>Fecha:</strong> " . date('d/m/Y H:i') . "</p>
                    </div>
                    <center>
                        <a href='https://asistencia.alpefresh.app/admin/pending-users.php' class='button'>Revisar Solicitud</a>
                    </center>
                    <p>Por favor, revisa y aprueba o rechaza esta solicitud lo antes posible.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Plantilla para cuenta aprobada
     */
    private function getAccountApprovedTemplate($name) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; padding: 12px 30px; background: #fdb714; color: #003366; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ ¡Cuenta Aprobada!</h1>
                </div>
                <div class='content'>
                    <h2>Hola $name,</h2>
                    <p>¡Excelentes noticias! Tu cuenta ha sido aprobada y ya puedes acceder al Sistema de Asistencia de AlpeFresh.</p>
                    <p>Puedes iniciar sesión con tu correo electrónico y la contraseña que creaste durante el registro.</p>
                    <center>
                        <a href='https://asistencia.alpefresh.app/login.php' class='button'>Iniciar Sesión Ahora</a>
                    </center>
                    <p>Si olvidaste tu contraseña, puedes restablecerla desde la página de login.</p>
                    <p>¡Bienvenido al equipo!</p>
                    <p>Saludos,<br>El equipo de AlpeFresh</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " AlpeFresh. Todos los derechos reservados.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}