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
    <link rel="stylesheet" href="css/registro.css">
    <link rel="stylesheet" href="css/mostrarcontreseñalogin.css">
    <script src="js/Formulario.js"></script>
    <script src="js/formularioculto.js"></script>
    <script src="js/registro.js"></script>

</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="../index.php" class="logo">
                    <img src="img/logo - snackver.png" alt="Snackver" class="logo-image">Snackver
                </a>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-primary">Iniciar sesión</a>
                </div>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="register-container">
                    <h2>Crea tu cuenta</h2>
                    <p>Únete a la comunidad de Snackver</p>

                    <form action="../Back-end/conexion/procesar_registro.php" method="POST" id="register-form">

                        <div class="user-type-selector">
                            <input type="radio" id="cliente" name="user_type" value="cliente" checked>
                            <label for="cliente">Soy Cliente</label>

                            <input type="radio" id="vendedor" name="user_type" value="vendedor">
                            <label for="vendedor">Soy Vendedor</label>
                        </div>

                        <div id="form-cliente">
                            <div class="input-group">
                                <label for="nombre_cliente">Nombre</label>
                                <input type="text" id="nombre_cliente" name="nombre_cliente" required>
                            </div>

                            <div class="input-group">
                                <label for="apellido_cliente">Apellido</label>
                                <input type="text" id="apellido_cliente" name="apellido_cliente" required>
                            </div>

                            <div class="input-group">
                                <label for="telefono_cliente">Telefono</label>
                                <input type="text" id="telefono_cliente" name="telefono_cliente" required>
                            </div>

                            <div class="input-group">
                                <label for="email_cliente">Email</label>
                                <input type="text" id="email_cliente" name="email_cliente" required>
                            </div>

                            <div class="input-group">
                                <label for="password_cliente">Contraseña</label>
                                <div class="password-wrapper">
                                    <input type="password" id="password_cliente" name="password_cliente" required>
                                    <button class="btn" type="button" onclick="mostrarPassword('password_cliente', this)">
                                        <i class="fa fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="confirm_password_cliente">Confirmar Contraseña</label>
                                <div class="password-wrapper">
                                    <input type="password" id="confirm_password_cliente" name="confirm_password_cliente" required>
                                    <button class="btn" type="button" onclick="mostrarPassword('confirm_password_cliente', this)">
                                        <i class="fa fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>

                        </div>

                        <div id="form-vendedor" class="hidden">
                            <div class="input-group">
                                <label for="nombre_vendedor">Nombre</label>
                                <input type="text" id="nombre_vendedor" name="nombre_vendedor" required>
                            </div>
                            <div class="input-group">
                                <label for="apellido_vendedor">Apellido</label>
                                <input type="text" id="apellido_vendedor" name="apellido_vendedor" required>
                            </div>

                            <div class="input-group">
                                <label for="nombre_negocio">Nombre de tu Negocio</label>
                                <input type="text" id="nombre_negocio" name="nombre_negocio" required>
                            </div>

                            <div class="input-group">
                                <label for="telefono_vendedor">Telefono</label>
                                <input type="text" id="telefono_vendedor" name="telefono_vendedor" required>
                            </div>

                            <div class="input-group">
                                <label for="email_vendedor">Email</label>
                                <input type="text" id="email_vendedor" name="email_vendedor" required>
                            </div>

                            <div class="input-group">
                                <label for="descripcion">Descripcion</label>
                                <input type="text" id="descripcion" name="descripcion" required>
                            </div>

                            <div class="input-group">
                                <label for="dirección">Dirección</label>
                                <input type="text" id="dirección" name="dirección" required>
                            </div>

                            <div class="input-group">
                                <label for="password_vendedor">Contraseña</label>
                                <div class="password-wrapper">
                                    <input type="password" id="password_vendedor" name="password_vendedor" required>
                                    <button class="btn" type="button" onclick="mostrarPassword('password_vendedor', this)">
                                        <i class="fa fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="input-group">
                                <label for="confirm_password_vendedor">Confirmar Contraseña</label>
                                <div class="password-wrapper">
                                    <input type="password" id="confirm_password_vendedor" name="confirm_password_vendedor" required>
                                    <button class="btn" type="button" onclick="mostrarPassword('confirm_password_vendedor', this)">
                                        <i class="fa fa-eye-slash"></i>
                                    </button>
                                </div>
                            </div>

                        </div>

                        <button type="submit" class="btn-register">Registrarse</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</body>

</html>