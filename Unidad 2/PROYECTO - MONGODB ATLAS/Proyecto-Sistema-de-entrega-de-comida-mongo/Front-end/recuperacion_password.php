<?php
// Front-end/recuperacion_password.php
session_start();

$mensaje_error = '';
if (isset($_SESSION['resultado_cambio'])) {
    $mensaje_error = $_SESSION['resultado_cambio'];
    unset($_SESSION['resultado_cambio']);
}

// Liberar candado de sesión inmediatamente
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snackver - Restablecer Contraseña</title>
    <link rel="icon" type="image/png" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/mostrarcontrasena.css?v=2.3">
    <link rel="stylesheet" href="css/verificar_codigo.css?v=2.3">
    <link rel="stylesheet" href="css/cambiar_password.css?v=2.3">
    <link rel="stylesheet" href="css/recuperacion_password.css?v=2.3">
    <script src="js/recuperacion_wizard.js?v=2.3" defer></script>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="../index.php" class="logo" title="Ir al inicio">
                    <img src="img/logo - snackver.png" alt="Snackver" class="logo-image">Snackver
                </a>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                
                <!-- PASO 1: Ventana Restablecer Contraseña (Solicitud con Correo) -->
                <div class="register-container" id="card-paso1-email">
                    <div class="security-header">
                        <div class="security-badge-icon">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <h2>Restablecer Contraseña</h2>
                        <p>Ingresa tu correo electrónico registrado y te enviaremos un código de verificación de 6 dígitos.</p>
                    </div>

                    <?php if (!empty($mensaje_error)): ?>
                        <div class="alert-otp alert-error" style="display: flex; margin-bottom: 20px;">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span><?= htmlspecialchars($mensaje_error) ?></span>
                        </div>
                    <?php endif; ?>

                    <div id="alertaPaso1" class="alert-otp" style="display: none; margin-bottom: 20px;"></div>

                    <form id="form-solicitar-codigo" novalidate>
                        <div class="input-group">
                            <label for="email_recuperacion">Correo Electrónico *</label>
                            <input 
                                type="email" 
                                id="email_recuperacion" 
                                name="email_recuperacion" 
                                placeholder="ejemplo@correo.com" 
                                required 
                                autocomplete="email"
                            >
                        </div>

                        <button type="submit" id="btnSolicitarOtp" class="login-button">
                            <i class="fa-solid fa-paper-plane"></i> Enviar código de verificación
                        </button>

                        <a href="login.php" class="btn-cancel-action">
                            <i class="fa-solid fa-arrow-left"></i> Cancelar y regresar al inicio de sesión
                        </a>
                    </form>
                </div>

                <!-- PASO 3: Ventana Confirmación de Contraseña (Nueva Contraseña) -->
                <div class="register-container" id="card-paso2-password" style="display: none;">
                    <div class="security-header">
                        <div class="security-badge-icon">
                            <i class="fa-solid fa-key"></i>
                        </div>
                        <h2>Nueva Contraseña</h2>
                        <p>Ingresa tu nueva contraseña para acceder a tu cuenta. Debe contener al menos 6 caracteres.</p>
                    </div>

                    <div id="alertaPaso3" class="alert-otp" style="display: none; margin-bottom: 20px;"></div>

                    <form id="form-cambiar-password" action="../Back-end/conexion/procesar_cambiar_password.php" method="POST">
                        <input type="hidden" id="email_confirmacion" name="email_recuperacion" value="">
                        <input type="hidden" id="recuperacion_token" name="recuperacion_token" value="">

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
                            <i class="fa-solid fa-circle-check"></i> Restablecer Contraseña
                        </button>

                        <a href="login.php" class="btn-cancel-action">
                            <i class="fa-solid fa-arrow-left"></i> Cancelar y regresar al inicio de sesión
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
                <span id="otpDisplayEmail" class="otp-highlight-email">tu correo</span>.
            </p>

            <div id="otpAlertBox" style="display: none;" class="alert-otp"></div>

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