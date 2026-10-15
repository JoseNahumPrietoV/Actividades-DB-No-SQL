<?php
session_start();

// --- 1. Manejo del Mensaje de Sesión ---
// Verifica si hay un mensaje de resultado de la operación anterior.
$mensaje_error = '';
if (isset($_SESSION['resultado_cambio'])) {
    $mensaje_error = $_SESSION['resultado_cambio'];
    // Borra el mensaje de la sesión para que no se muestre de nuevo al recargar
    unset($_SESSION['resultado_cambio']);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snackver - Cambiar Contraseña</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/mostrarcontraseña.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/Restaurarlogin.js"></script>
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="../index.php" class="logo">
                    <img src="img/logo - snackver.png" alt="Snackver" class="logo-image">Snackver
                </a>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="register-container">
                    <h2>Restablecer Contraseña</h2>


                    <form action="../Back-end/conexion/procesar_cambiar_password.php" method="post" onsubmit="return validarPasswords()">

                        <div class="input-group">
                            <label for="email_recuperacion">Correo Electrónico *</label>
                            <input type="email" id="email_recuperacion" name="email_recuperacion" placeholder="Ingrese su correo electrónico" required>
                        </div>
                        <div class="input-group">
                            <label for="nueva_password">Nueva Contraseña</label>
                            <div class="password-wrapper">
                                <input type="password" id="nueva_password" name="nueva_password" required />
                                <button class="btn fa fa-eye-slash" type="button" onclick="mostrarPassword('nueva_password', this)"></button>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="confirm_password">Confirmar Nueva Contraseña</label>
                            <div class="password-wrapper">
                                <input type="password" id="confirm_password" name="confirm_password" required />
                                <button class="btn fa fa-eye-slash" type="button" onclick="mostrarPassword('confirm_password', this)"></button>
                            </div>
                        </div>

                        <p id="error-message" style="color: red;"></p>
                        <button type="submit" class="login-button">Restablecer Contraseña</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</body>

</html>

<?php
ob_end_flush();
?>