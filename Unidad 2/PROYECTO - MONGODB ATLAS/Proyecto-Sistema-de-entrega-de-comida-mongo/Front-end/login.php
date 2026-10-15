<?php
// Front-end/login.php
session_start();

$mensaje_alerta = '';
$alerta_tipo = 'error';
$auto_abrir_otp = '';
$otp_modal_error = '';

if (!empty($_SESSION['abrir_modal_otp'])) {
    $auto_abrir_otp = $_SESSION['abrir_modal_otp'];
    unset($_SESSION['abrir_modal_otp']);
}

if (!empty($_SESSION['error_otp'])) {
    $otp_modal_error = $_SESSION['error_otp'];
    unset($_SESSION['error_otp']);
}

if (isset($_GET['pendiente'])) {
    $mensaje_alerta = "Tu cuenta está pendiente de verificación. Ingresa tu correo y contraseña para validar tu código.";
    $alerta_tipo = 'warning';
} elseif (isset($_SESSION['login_error']) && !empty($_SESSION['login_error'])) {
    $mensaje_alerta = $_SESSION['login_error'];
    $alerta_tipo = 'error';
    unset($_SESSION['login_error']);
} elseif (isset($_SESSION['login_success']) && !empty($_SESSION['login_success'])) {
    $mensaje_alerta = $_SESSION['login_success'];
    $alerta_tipo = 'success';
    unset($_SESSION['login_success']);
}

// Liberar candado de sesión inmediatamente para permitir navegación fluida y peticiones paralelas
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snackver - Comida entre universitarios</title>

    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/mostrarcontrasenalogin.css">
    <link rel="stylesheet" href="css/verificar_codigo.css">
    <script>
        window.autoOpenOtpEmail = "<?php echo htmlspecialchars($auto_abrir_otp); ?>";
    </script>
    <script src="js/login.js?v=2.2" defer></script>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="../index.php" class="logo">
                    <img src="img/logo - snackver.png" alt="Snackver" class="logo-image">Snackver
                </a>
                <div class="auth-buttons">
                    <a href="registro.php" class="btn btn-primary">Registro</a>
                </div>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="register-container">
                    <h2>Iniciar Sesión</h2>
                    
                    <div id="loginAlertBox" class="alert-otp alert-<?php echo $alerta_tipo; ?>" style="<?php echo !empty($mensaje_alerta) ? 'display:flex; margin-bottom:20px;' : 'display:none; margin-bottom:20px;'; ?>">
                        <i class="fa-solid <?php echo ($alerta_tipo === 'success') ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                        <span><?php echo htmlspecialchars($mensaje_alerta); ?></span>
                    </div>

                    <form id="login-form" method="POST" action="../Back-end/conexion/procesar_login.php">

                        <div class="input-group">
                            <label for="email">Correo electrónico *</label>
                            <input type="email" id="email" name="email" placeholder="Ingrese su correo electrónico" required autocomplete="email">
                        </div>

                        <div class="input-group">
                            <label for="password">Contraseña *</label>
                            <div class="password-wrapper">
                                <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required autocomplete="current-password">
                                <button class="btn" type="button" onclick="mostrarPassword('password', this)" aria-label="Mostrar u ocultar contraseña">
                                    <i class="fa fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="input-group">
                            <button type="submit" id="btnLoginSubmit" class="login-button">Iniciar Sesión</button>
                        </div>  
                        <div class="links">
                            <a href="recuperacion_password.php">¿No puedes acceder a tu cuenta?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal Flotante de Verificación OTP -->
    <div id="otpModalOverlay" class="otp-modal-overlay">
        <div class="otp-modal-card">
            <button type="button" class="otp-modal-close" id="btnCloseOtpModal" aria-label="Cerrar">&times;</button>
            
            <div class="otp-icon-badge">
                <i class="fa-solid fa-envelope"></i>
            </div>

            <h3 class="otp-title">Verifica tu correo</h3>
            <p class="otp-subtitle">
                Hemos enviado un código de 6 dígitos a <br>
                <span id="otpDisplayEmail" class="otp-highlight-email">tu correo</span>.
            </p>

            <div id="otpAlertBox" style="<?php echo !empty($otp_modal_error) ? 'display:flex;' : 'display:none;'; ?>" class="alert-otp alert-error">
                <?php if (!empty($otp_modal_error)): ?>
                    <i class="fa-solid fa-circle-exclamation"></i> <span><?php echo htmlspecialchars($otp_modal_error); ?></span>
                <?php endif; ?>
            </div>

            <form id="formVerifyOtpModal">
                <input type="hidden" id="otpHiddenEmail" name="email" value="">
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