<?php
// Asegúrate de que session_start() está al inicio del archivo login.php
session_start();

// Inicializamos el script que vamos a insertar
$mensaje_js = '';

// 1. Verificamos si existe un mensaje de error en la sesión
if (isset($_SESSION['login_error']) && !empty($_SESSION['login_error'])) {

    // Escapamos el mensaje de error para que sea seguro en JavaScript
    $mensaje_alerta = addslashes($_SESSION['login_error']);

    // 2. Generamos el código JavaScript para mostrar la alerta
    $mensaje_js = "
        <script>
            // Muestra la alerta
            alert('{$mensaje_alerta}'); 
        </script>
    ";

    // 3. Limpiamos la variable de sesión para que la alerta no se repita
    unset($_SESSION['login_error']);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snackver - Comida entre universitarios</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="shortcut icon" href="img/logo - snackver.png" type="image/x-icon">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/mostrarcontreseñalogin.css">
    <script src="js/login.js"></script>
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
                    <?php echo $mensaje_js; ?>
                    <form method="POST" action="../Back-end/conexion/procesar_login.php">

                        <div class="input-group">
                            <label for="email">Correo electrónico *</label>
                            <input type="email" id="email" name="email" placeholder="Ingrese su correo electrónico" required>
                        </div>

                        <div class="input-group">
                            <label for="password">Contraseña *</label>
                            <div class="password-wrapper">
                                <input type="password" id="password" name="password" placeholder="Ingrese su contraseña" required>
                                <button class="btn" type="button" onclick="mostrarPassword('password', this)">
                                    <i class="fa fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="input-group">
                            <button type="submit" class="login-button" ">Login</button>
                          </div>  
                        <div class=" links">
                                <a href="recuperacion_password.php">¿No puedes acceder a tu cuenta?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</body>

</html>

<?php
ob_end_flush(); // Envía el buffer de salida al navegador
?>