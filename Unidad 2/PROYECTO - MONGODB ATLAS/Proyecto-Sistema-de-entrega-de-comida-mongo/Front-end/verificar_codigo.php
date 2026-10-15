<?php
// Front-end/verificar_codigo.php
session_start();

$email = $_GET['email'] ?? $_SESSION['email_verificacion'] ?? '';
$error = $_SESSION['error_otp'] ?? '';
$success = $_SESSION['success_otp'] ?? '';
unset($_SESSION['error_otp'], $_SESSION['success_otp']);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Si no hay correo pendiente por verificar, redirigir a login
if (empty($email)) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Cuenta - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="stylesheet" href="css/verificar_codigo.css">
</head>
<body>

    <header style="background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 15px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.04);">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center;">
            <a href="../index.php" style="display: flex; align-items: center; text-decoration: none; font-size: 22px; font-weight: bold; color: #1A4F8B;">
                <img src="img/logo - snackver.png" alt="Snackver" style="height: 38px; margin-right: 10px;">Snackver
            </a>
            <a href="login.php" style="color: #64748b; text-decoration: none; font-size: 14px; font-weight: 500;">
                <i class="fas fa-arrow-left"></i> Volver a Iniciar Sesión
            </a>
        </div>
    </header>

    <main class="otp-page-wrapper">
        <div class="otp-card">
            <div class="otp-icon-badge">
                <i class="fa-solid fa-envelope"></i>
            </div>
            <h2 class="otp-title">Verificación de Cuenta</h2>
            <p class="otp-subtitle">
                Hemos enviado un código de 6 dígitos a:<br>
                <strong class="otp-highlight-email"><?php echo htmlspecialchars($email); ?></strong>
            </p>

            <?php if (!empty($error)): ?>
                <div class="alert-otp alert-error">
                    <i class="fas fa-exclamation-circle"></i> <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert-otp alert-success">
                    <i class="fas fa-check-circle"></i> <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <form action="../Back-end/conexion/procesar_verificacion_otp.php" method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="otp-input-wrap">
                    <input type="text" 
                           name="codigo_otp" 
                           class="otp-input" 
                           maxlength="6" 
                           pattern="[0-9]{6}" 
                           placeholder="· · · · · ·" 
                           required 
                           autofocus 
                           autocomplete="one-time-code">
                </div>

                <div class="timer-badge">
                    <i class="fas fa-clock"></i> El código es válido por <span id="countdown"><strong>15:00</strong></span>
                </div>

                <button type="submit" class="btn-verify">
                    <i class="fas fa-check-circle"></i> Activar y Entrar
                </button>
            </form>

            <div class="resend-link">
                ¿No recibiste el código? 
                <a href="../Back-end/conexion/reenviar_otp.php?email=<?php echo urlencode($email); ?>">Reenviar código</a>
            </div>
        </div>
    </main>

    <script>
        // Contador regresivo visual de 15 minutos
        let tiempoRestante = 15 * 60;
        const countdownEl = document.getElementById('countdown');

        const timer = setInterval(() => {
            const minutos = Math.floor(tiempoRestante / 60);
            const segundos = tiempoRestante % 60;
            countdownEl.innerHTML = `<strong>${minutos}:${segundos < 10 ? '0' : ''}${segundos}</strong>`;
            
            if (tiempoRestante <= 0) {
                clearInterval(timer);
                countdownEl.innerHTML = '<strong style="color:#ef4444;">Expirado</strong>';
            }
            tiempoRestante--;
        }, 1000);
    </script>
</body>
</html>
