<?php
// Front-end/cambiar_password.php
// Zona de Seguridad: Módulo exclusivo de cambio de contraseña para usuarios autenticados
session_start();

$id_cliente = $_SESSION['id_cliente'] ?? null;
$id_vendedor = $_SESSION['id_vendedor'] ?? null;
$id_usuario = $_SESSION['id_usuario'] ?? null;
$rol = $_SESSION['rol'] ?? null;

// 1. Validar que exista una sesión activa de Cliente o Vendedor
if (empty($id_cliente) && empty($id_vendedor) && empty($id_usuario)) {
    $_SESSION['login_error'] = "Debes iniciar sesión para acceder a la Zona de Seguridad.";
    header('Location: login.php');
    exit();
}

// 2. Determinar el rol y la URL de regreso ("Atrás")
$esVendedor = !empty($id_vendedor) || $rol === 'vendedor';
$esCliente = !empty($id_cliente) || $rol === 'cliente';

if ($esVendedor) {
    $urlRegreso = '../Back-end/vendedor/MiTienda.php';
    $textoRegreso = 'Volver a Mi Tienda';
} else {
    $urlRegreso = '../Back-end/cliente/Miperfil.php';
    $textoRegreso = 'Volver a Mi Perfil';
}

// 3. Detección automática del correo electrónico en la sesión activa
$emailDetectado = '';
if (!empty($_SESSION['email'])) {
    $emailDetectado = $_SESSION['email'];
} elseif (!empty($_SESSION['email_cliente'])) {
    $emailDetectado = $_SESSION['email_cliente'];
} elseif (!empty($_SESSION['email_vendedor'])) {
    $emailDetectado = $_SESSION['email_vendedor'];
} elseif (!empty($_SESSION['datos_cliente']['email_cliente'])) {
    $emailDetectado = $_SESSION['datos_cliente']['email_cliente'];
} elseif (!empty($_SESSION['datos_vendedor']['email_vendedor'])) {
    $emailDetectado = $_SESSION['datos_vendedor']['email_vendedor'];
}

// Si no estuviera en memoria de sesión, consultar MongoDB Atlas como respaldo
if (empty($emailDetectado)) {
    try {
        require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';
        $mongoDb = MongoConexion::getConexion();
        $colUsuarios = $mongoDb->selectCollection('usuarios');
        
        $filtro = [];
        $idBuscar = $id_cliente ?? $id_vendedor ?? $id_usuario;
        if (is_string($idBuscar) && preg_match('/^[a-f\d]{24}$/i', $idBuscar)) {
            $filtro = ['_id' => new MongoDB\BSON\ObjectId($idBuscar)];
        } else {
            $filtro = ['$or' => [['_id' => (int)$idBuscar], ['_id' => (string)$idBuscar]]];
        }
        $uDoc = $colUsuarios->findOne($filtro, ['projection' => ['email' => 1]]);
        if ($uDoc && !empty($uDoc['email'])) {
            $emailDetectado = $uDoc['email'];
            $_SESSION['email'] = $emailDetectado;
        }
    } catch (\Throwable $e) {
        error_log("Error obteniendo correo en cambiar_password.php: " . $e->getMessage());
    }
}

// Liberar bloqueo de sesión
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zona de Seguridad - Cambiar Contraseña - Snackver</title>
    <link rel="icon" type="image/png" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/mostrarcontrasena.css?v=2.3">
    <link rel="stylesheet" href="css/verificar_codigo.css?v=2.3">
    <link rel="stylesheet" href="css/cambiar_password.css?v=2.3">
    <script src="js/cambiar_password.js?v=2.3" defer></script>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="<?php echo htmlspecialchars($urlRegreso); ?>" class="logo" title="Ir a tu panel">
                    <img src="img/logo - snackver.png" alt="Snackver" class="logo-image">Snackver
                </a>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                
                <!-- PASO 1: Tarjeta Zona de Seguridad con Correo Detectado -->
                <div class="register-container" id="card-paso1-email">
                    <div class="security-header">
                        <div class="security-badge-icon">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <h2>Zona de Seguridad</h2>
                        <p>Para proteger tu cuenta, enviaremos un código de verificación de 6 dígitos a tu correo registrado antes de cambiar tu contraseña.</p>
                    </div>

                    <div id="alertaPaso1" class="alert-otp" style="display: none;"></div>

                    <form id="form-solicitar-codigo" novalidate>
                        <div class="input-group">
                            <label for="email_recuperacion">Correo Electrónico Detectado</label>
                            <input 
                                type="email" 
                                id="email_recuperacion" 
                                name="email_recuperacion" 
                                value="<?php echo htmlspecialchars($emailDetectado); ?>" 
                                readonly 
                                class="input-readonly"
                                required
                            >
                        </div>

                        <button type="submit" id="btnSolicitarOtp" class="login-button">
                            <i class="fa-solid fa-paper-plane"></i> Enviar código de verificación
                        </button>

                        <a href="<?php echo htmlspecialchars($urlRegreso); ?>" class="btn-cancel-action">
                            <i class="fa-solid fa-arrow-left"></i> Cancelar y <?php echo htmlspecialchars($textoRegreso); ?>
                        </a>
                    </form>
                </div>

                <!-- PASO 3: Tarjeta de Nueva Contraseña -->
                <div class="register-container" id="card-paso2-password" style="display: none;">
                    <div class="security-header">
                        <div class="security-badge-icon">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <h2>Nueva Contraseña</h2>
                        <p>Crea tu nueva contraseña. Debe contener al menos 6 caracteres.</p>
                    </div>

                    <div id="alertaPaso3" class="alert-otp" style="display: none;"></div>

                    <form id="form-cambiar-password" action="../Back-end/conexion/procesar_cambiar_password.php" method="POST">
                        <input type="hidden" id="email_confirmacion" name="email_recuperacion" value="<?php echo htmlspecialchars($emailDetectado); ?>">
                        <input type="hidden" id="recuperacion_token" name="recuperacion_token" value="">
                        <input type="hidden" name="origen" value="seguridad">

                        <div class="input-group">
                            <label for="nueva_password">Nueva Contraseña</label>
                            <div class="password-wrapper">
                                <input 
                                    type="password" 
                                    id="nueva_password" 
                                    name="nueva_password" 
                                    placeholder="Mínimo 6 caracteres" 
                                    required 
                                    minlength="6"
                                    autocomplete="new-password"
                                >
                                <button class="btn" type="button" onclick="mostrarPassword('nueva_password', this)" aria-label="Mostrar u ocultar contraseña">
                                    <i class="fa fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="confirm_password">Confirmar Nueva Contraseña</label>
                            <div class="password-wrapper">
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    placeholder="Repite tu contraseña" 
                                    required 
                                    minlength="6"
                                    autocomplete="new-password"
                                >
                                <button class="btn" type="button" onclick="mostrarPassword('confirm_password', this)" aria-label="Mostrar u ocultar contraseña">
                                    <i class="fa fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" id="btnSubmitPassword" class="login-button">
                            <i class="fa-solid fa-circle-check"></i> Actualizar Contraseña
                        </button>

                        <a href="<?php echo htmlspecialchars($urlRegreso); ?>" class="btn-cancel-action">
                            <i class="fa-solid fa-arrow-left"></i> Cancelar y <?php echo htmlspecialchars($textoRegreso); ?>
                        </a>
                    </form>
                </div>

            </div>
        </div>
    </section>

    <!-- PASO 2: Modal Flotante de Verificación OTP -->
    <div id="otpModalOverlay" class="otp-modal-overlay">
        <div class="otp-modal-card">
            <button type="button" class="otp-modal-close" id="btnCloseOtpModal" aria-label="Cerrar">&times;</button>
            
            <div class="otp-icon-badge">
                <i class="fa-solid fa-envelope"></i>
            </div>

            <h3 class="otp-title">Verifica tu correo</h3>
            <p class="otp-subtitle">
                Hemos enviado un código de 6 dígitos a <br>
                <span id="otpDisplayEmail" class="otp-highlight-email"><?php echo htmlspecialchars($emailDetectado); ?></span>.
            </p>

            <div id="otpAlertBox" style="display: none;" class="alert-otp"></div>

            <form id="formVerifyOtpModal">
                <input type="hidden" id="otpHiddenEmail" name="email" value="<?php echo htmlspecialchars($emailDetectado); ?>">
                <div class="otp-input-wrap">
                    <input 
                        type="text" 
                        id="inputOtpCode" 
                        name="codigo_otp" 
                        class="otp-input" 
                        maxlength="6" 
                        placeholder="••••••" 
                        autocomplete="one-time-code"
                        inputmode="numeric"
                        required
                    >
                </div>

                <div class="timer-badge">
                    <i class="fa-regular fa-clock"></i> 
                    <span>Expira en: <strong id="otpTimer">15:00</strong></span>
                </div>

                <button type="submit" id="btnSubmitOtp" class="btn-verify">
                    <i class="fa-solid fa-circle-check"></i> Confirmar y Continuar
                </button>
            </form>

            <div class="resend-link">
                ¿No recibiste el código? 
                <a href="javascript:void(0)" id="btnResendOtp">Reenviar código</a>
            </div>
        </div>
    </div>
</body>

</html>
