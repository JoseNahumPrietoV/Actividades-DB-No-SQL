<?php
namespace Snackver\Servicios;

// Cargar autoload de composer buscando en Back-end o en la raíz
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../Back-end/vendor/autoload.php',
    dirname(dirname(__DIR__)) . '/Back-end/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php'
];
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Exception;

class EmailService
{
    private static $inicializado = false;
    private static $host;
    private static $port;
    private static $secure;
    private static $user;
    private static $pass;
    private static $fromName;
    private static $httpRelayUrl;

    private static function inicializar()
    {
        if (self::$inicializado) {
            return;
        }

        // Cachear inicialización de Dotenv para evitar lecturas de disco si las variables de entorno ya existen en $_ENV
        if (empty($_ENV['SMTP_USER']) && !getenv('SMTP_USER')) {
            $envPaths = [
                __DIR__ . '/..',
                dirname(__DIR__),
                dirname(dirname(__DIR__)) . '/Back-end'
            ];
            foreach ($envPaths as $envDir) {
                if (file_exists($envDir . '/.env')) {
                    $dotenv = Dotenv::createImmutable($envDir);
                    $dotenv->safeLoad();
                    break;
                }
            }
        }

        self::$host         = $_ENV['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: ($_SERVER['SMTP_HOST'] ?? 'smtp.gmail.com');
        self::$port         = (int)($_ENV['SMTP_PORT'] ?? getenv('SMTP_PORT') ?: ($_SERVER['SMTP_PORT'] ?? 465));
        self::$secure       = $_ENV['SMTP_SECURE'] ?? getenv('SMTP_SECURE') ?: ($_SERVER['SMTP_SECURE'] ?? 'ssl');
        self::$user         = $_ENV['SMTP_USER'] ?? getenv('SMTP_USER') ?: ($_SERVER['SMTP_USER'] ?? '');
        self::$pass         = str_replace(' ', '', $_ENV['SMTP_PASS'] ?? getenv('SMTP_PASS') ?: ($_SERVER['SMTP_PASS'] ?? ''));
        self::$fromName     = $_ENV['SMTP_FROM_NAME'] ?? getenv('SMTP_FROM_NAME') ?: ($_SERVER['SMTP_FROM_NAME'] ?? 'Snackver');
        self::$httpRelayUrl = $_ENV['SMTP_HTTP_RELAY_URL'] ?? getenv('SMTP_HTTP_RELAY_URL') ?: ($_SERVER['SMTP_HTTP_RELAY_URL'] ?? 'https://script.google.com/macros/s/AKfycbzu6-9hv1y8GOj2F6XtQoZcON8S8i64dUYy-Mb2hm2DqBkdDMFWBKmUbf8FhvKRKY3TGQ/exec');

        self::$inicializado = true;
    }

    /**
     * Construye una plantilla HTML responsiva con estilos inline completos compatible con Gmail, Outlook y móviles,
     * idéntica al diseño de referencia institucional de Snackver.
     */
    private static function generarPlantillaHtml($tipo, $nombre, $codigoOtp)
    {
        $nombreEscapado = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        
        $esRecuperacion = ($tipo === 'recuperacion');
        $tituloHeader = $esRecuperacion ? 'Restablecer Contraseña' : 'Verificación de Cuenta';
        $subtituloHeader = 'Comida entre universitarios';
        $textoCuerpo = $esRecuperacion 
            ? 'Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en Snackver. Para continuar de forma segura, ingresa el siguiente código de confirmación:' 
            : 'Gracias por registrarte en la plataforma Snackver. Para continuar de forma segura, ingresa el siguiente código de confirmación:';
        
        $textoSeguridad = $esRecuperacion
            ? 'Este código expirará en <span style="color: #1A4F8B; font-weight: bold;">15 minutos</span>. Si tú no solicitaste este cambio, puedes ignorar este correo con total seguridad y tu contraseña actual no será modificada.'
            : 'Este código expirará en <span style="color: #1A4F8B; font-weight: bold;">15 minutos</span>. Si tú no realizaste este registro, puedes ignorar este correo con total seguridad.';

        $preheader = ($esRecuperacion ? 'Tu código de recuperación es: ' : 'Tu código de verificación es: ') . $codigoOtp . ' (válido por 15 min)';

        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $tituloHeader . ' - Snackver</title>
</head>
<body style="margin:0; padding:0; background-color:#edf2f7; font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing:antialiased;">
    <!-- Preheader invisible para preview en bandeja de entrada Gmail -->
    <div style="display:none; font-size:1px; line-height:1px; max-height:0px; max-width:0px; opacity:0; overflow:hidden; mso-hide:all;">
        ' . $preheader . '
    </div>

    <!-- Contenedor exterior -->
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#edf2f7; padding:40px 15px; margin:0; width:100%;">
        <tr>
            <td align="center">
                <!-- Tarjeta Principal con bordes redondeados y sombra suave -->
                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width:480px; background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 12px 30px rgba(15,23,42,0.08); border:none;">
                    
                    <!-- Header Azul Institucional Snackver -->
                    <tr>
                        <td align="center" style="background-color:#1A4F8B; padding:32px 24px 28px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:32px; font-weight:bold; letter-spacing:-0.5px; line-height:1.2; font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif;">
                                Snackver
                            </h1>
                            <p style="margin:6px 0 0; color:#ffffff; font-size:14px; font-weight:normal; letter-spacing:0.2px;">
                                ' . $subtituloHeader . '
                            </p>
                        </td>
                    </tr>

                    <!-- Cuerpo del Mensaje -->
                    <tr>
                        <td style="padding:38px 34px 30px; text-align:center; background-color:#ffffff;">
                            <h2 style="margin:0 0 16px; color:#111827; font-size:22px; font-weight:bold; text-align:center;">
                                ¡Hola, ' . $nombreEscapado . '!
                            </h2>
                            <p style="margin:0 0 24px; color:#374151; font-size:14px; font-weight:normal; line-height:1.6; text-align:center;">
                                ' . $textoCuerpo . '
                            </p>

                            <!-- Caja Destacada del Código OTP (Borde punteado azul, números grandes espaciados) -->
                            <table role="presentation" border="0" cellspacing="0" cellpadding="0" style="margin:0 auto 24px; text-align:center;">
                                <tr>
                                    <td style="border:2px dashed #1A4F8B; border-radius:12px; padding:14px 28px; background-color:#ffffff; text-align:center;">
                                        <span style="font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Courier New\', monospace; font-size:34px; font-weight:bold; letter-spacing:12px; color:#1A4F8B; display:inline-block; padding-left:12px;">
                                            ' . $codigoOtp . '
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Información de Expiración y Seguridad -->
                            <p style="margin:0; color:#374151; font-size:13px; font-weight:normal; line-height:1.6; text-align:center;">
                                ' . $textoSeguridad . '
                            </p>
                        </td>
                    </tr>

                    <!-- Footer Corporativo -->
                    <tr>
                        <td style="background-color:#f8fafc; border-top:1px solid #f1f5f9; padding:22px 20px; text-align:center;">
                            <p style="margin:0; color:#64748b; font-size:12px; font-weight:normal; line-height:1.6;">
                                © 2026 Snackver. Plataforma universitaria<br>
                                Seguridad y confianza para la comunidad estudiantil.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Despacha el correo a través del puente HTTPS (Puerto 443) en Google Apps Script,
     * sorteando el bloqueo de puertos SMTP (25/465/587) en entornos cloud como Render Free.
     */
    private static function enviarViaHttpsRelay($destinatario, $nombre, $subject, $htmlBody, $altBody)
    {
        if (empty(self::$httpRelayUrl) || !function_exists('curl_init')) {
            return null;
        }

        $payload = json_encode([
            'to'       => $destinatario,
            'name'     => $nombre,
            'subject'  => $subject,
            'html'     => $htmlBody,
            'altBody'  => $altBody,
            'fromName' => self::$fromName ?: 'Snackver'
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(self::$httpRelayUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode    = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize  = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $curlErr     = curl_error($ch);
        curl_close($ch);

        if ($rawResponse === false || $httpCode === 0) {
            error_log("Aviso: Puente HTTPS no disponible ($curlErr), intentando fallback SMTP...");
            return null;
        }

        $headers = substr($rawResponse, 0, $headerSize);
        $body    = substr($rawResponse, $headerSize);

        // Google Apps Script ejecuta doPost() en la petición inicial y responde HTTP 302 hacia script.googleusercontent.com
        if (($httpCode === 302 || $httpCode === 301) && preg_match('/^Location:\s*(.+)$/mi', $headers, $matches)) {
            $redirectUrl = trim($matches[1]);
            $chGet = curl_init($redirectUrl);
            curl_setopt_array($chGet, [
                CURLOPT_HTTPGET        => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0
            ]);
            $body = curl_exec($chGet);
            $httpCode = (int)curl_getinfo($chGet, CURLINFO_HTTP_CODE);
            curl_close($chGet);
        }

        if ($httpCode >= 200 && $httpCode < 400) {
            $decoded = json_decode((string)$body, true);
            if (is_array($decoded) && isset($decoded['status']) && $decoded['status'] === 'error') {
                error_log("Error reportado por puente HTTPS: " . ($decoded['message'] ?? 'desconocido'));
                return null;
            }
            return ['status' => 'success', 'message' => 'Correo enviado exitosamente.'];
        }

        return null;
    }

    /**
     * Envía un código OTP de 6 dígitos con diseño HTML corporativo para Snackver.
     *
     * @param string $destinatario Email del usuario.
     * @param string $nombre Nombre del usuario.
     * @param string $codigoOtp Código numérico de 6 dígitos.
     * @return array [status => 'success'|'error', 'message' => ...]
     */
    public static function enviarCodigoVerificacion($destinatario, $nombre, $codigoOtp)
    {
        self::inicializar();

        if (empty($destinatario) || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'El correo electrónico no es válido.'];
        }

        $asunto    = 'Tu código de verificación de Snackver: ' . $codigoOtp;
        $cuerpoHtml = self::generarPlantillaHtml('verificacion', $nombre, $codigoOtp);
        $cuerpoAlt  = "Hola $nombre,\n\nTu código de verificación de Snackver es: $codigoOtp\n\nEste código es válido por 15 minutos.\n\nSnackver Campus";

        // 1. Envío prioritario por Puente HTTPS (Puerto 443, compatible con Render Free y local)
        $resRelay = self::enviarViaHttpsRelay($destinatario, $nombre, $asunto, $cuerpoHtml, $cuerpoAlt);
        if ($resRelay !== null) {
            return $resRelay;
        }

        // Si aún no se han configurado credenciales reales de SMTP
        if (empty(self::$user) || empty(self::$pass) || self::$pass === 'xxxx' || self::$user === 'tu_correo@gmail.com') {
            return [
                'status' => 'simulated',
                'message' => 'Configura SMTP_USER y SMTP_PASS en tu archivo .env. Código simulado: ' . $codigoOtp,
                'codigo' => $codigoOtp
            ];
        }

        $mail = new PHPMailer(true);

        try {
            // Configuración del Servidor SMTP de alta velocidad (SMTPS directo)
            $mail->isSMTP();
            $mail->Host          = self::$host;
            $mail->Hostname      = 'snackver.me';
            $mail->SMTPAuth      = true;
            $mail->Username      = self::$user;
            $mail->Password      = self::$pass;
            $mail->SMTPSecure    = (self::$secure === 'ssl' || self::$port === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port          = self::$port;
            $mail->CharSet       = 'UTF-8';
            $mail->Timeout       = 7;
            $mail->SMTPAutoTLS   = false;
            $mail->SMTPKeepAlive = false;

            // Soporte explícito TLS 1.3 / 1.2 directo y compatibilidad SSL
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }

            // Opciones SSL para compatibilidad con Windows / XAMPP / TLS
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                    'crypto_method'     => $cryptoMethod
                ]
            ];

            // Destinatarios
            $mail->setFrom(self::$user, self::$fromName);
            $mail->addAddress($destinatario, $nombre);

            // Contenido del Correo
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpoHtml;
            $mail->AltBody = $cuerpoAlt;

            $mail->send();
            return ['status' => 'success', 'message' => 'Correo enviado exitosamente.'];

        } catch (PHPMailerException $e) {
            error_log("Error PHPMailer al enviar verificación a $destinatario: " . $mail->ErrorInfo);
            return ['status' => 'error', 'message' => 'No se pudo enviar el correo: ' . $mail->ErrorInfo];
        } catch (Exception $e) {
            error_log("Error inesperado al enviar verificación a $destinatario: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error inesperado: ' . $e->getMessage()];
        }
    }

    /**
     * Envía un código OTP de 6 dígitos para restablecer la contraseña en Snackver.
     *
     * @param string $destinatario Email del usuario.
     * @param string $nombre Nombre del usuario.
     * @param string $codigoOtp Código numérico de 6 dígitos.
     * @return array [status => 'success'|'error', 'message' => ...]
     */
    public static function enviarCodigoRecuperacion($destinatario, $nombre, $codigoOtp)
    {
        self::inicializar();

        if (empty($destinatario) || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'error', 'message' => 'El correo electrónico no es válido.'];
        }

        $asunto     = 'Restablecer contraseña de Snackver: ' . $codigoOtp;
        $cuerpoHtml = self::generarPlantillaHtml('recuperacion', $nombre, $codigoOtp);
        $cuerpoAlt  = "Hola $nombre,\n\nTu código para restablecer contraseña en Snackver es: $codigoOtp\n\nEste código expira en 15 minutos. Si no solicitaste este cambio, puedes ignorar este mensaje.\n\nSnackver Campus";

        // 1. Envío prioritario por Puente HTTPS (Puerto 443, compatible con Render Free y local)
        $resRelay = self::enviarViaHttpsRelay($destinatario, $nombre, $asunto, $cuerpoHtml, $cuerpoAlt);
        if ($resRelay !== null) {
            return $resRelay;
        }

        if (empty(self::$user) || empty(self::$pass) || self::$pass === 'xxxx' || self::$user === 'tu_correo@gmail.com') {
            return [
                'status' => 'simulated',
                'message' => 'Configura SMTP_USER y SMTP_PASS en tu archivo .env. Código simulado: ' . $codigoOtp,
                'codigo' => $codigoOtp
            ];
        }

        $mail = new PHPMailer(true);

        try {
            // Configuración del Servidor SMTP de alta velocidad (SMTPS directo)
            $mail->isSMTP();
            $mail->Host          = self::$host;
            $mail->Hostname      = 'snackver.me';
            $mail->SMTPAuth      = true;
            $mail->Username      = self::$user;
            $mail->Password      = self::$pass;
            $mail->SMTPSecure    = (self::$secure === 'ssl' || self::$port === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port          = self::$port;
            $mail->CharSet       = 'UTF-8';
            $mail->Timeout       = 7;
            $mail->SMTPAutoTLS   = false;
            $mail->SMTPKeepAlive = false;

            // Soporte explícito TLS 1.3 / 1.2 directo y compatibilidad SSL
            $cryptoMethod = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }

            // Opciones SSL para compatibilidad con Windows / XAMPP / TLS
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                    'crypto_method'     => $cryptoMethod
                ]
            ];

            $mail->setFrom(self::$user, self::$fromName);
            $mail->addAddress($destinatario, $nombre);

            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $cuerpoHtml;
            $mail->AltBody = $cuerpoAlt;

            $mail->send();
            return ['status' => 'success', 'message' => 'Correo de recuperación enviado exitosamente.'];

        } catch (PHPMailerException $e) {
            error_log("Error PHPMailer al enviar recuperación a $destinatario: " . $mail->ErrorInfo);
            return ['status' => 'error', 'message' => 'No se pudo enviar el correo: ' . $mail->ErrorInfo];
        } catch (Exception $e) {
            error_log("Error inesperado al enviar recuperación a $destinatario: " . $e->getMessage());
            return ['status' => 'error', 'message' => 'Error inesperado: ' . $e->getMessage()];
        }
    }
}
