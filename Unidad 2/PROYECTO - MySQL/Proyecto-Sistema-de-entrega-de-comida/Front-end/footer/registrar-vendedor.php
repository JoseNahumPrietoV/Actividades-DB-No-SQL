
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conviértete en Vendedor - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/registrar-vendedor.css">
    <link rel="shortcut icon" href="../img/logo - snackver.png" type="image/x-icon">

</head>

<body>

    <header>
        <div class="container header-content">
            <a href="../../index.php" class="logo">
                <img src="../img/logo - snackver.png" alt="Logo de Snackver">
                Snackver
            </a>

            <div class="auth-buttons">
                <a href="../login.php" class="btn btn-outline">Iniciar Sesión</a>
                <a href="../registro.php" class="btn btn-primary">Registrarse</a>
            </div>
        </div>
    </header>

    <main>
        <section class="featured">
            <div class="container">
                <h2 class="section-title">Conviértete en Vendedor</h2>
                <div class="content-card">
                    <p style="text-align: center; margin-bottom: 30px;">¿Te encanta cocinar? ¿Quieres ganar un dinero extra entre clases? ¡Únete a la comunidad de vendedores de Snackver! Es fácil, rápido y seguro.</p>

                    <form action="../procesar_registro.php" method="POST" class="form-vendedor">
                        <div class="form-group">
                            <label for="nombre_vendedor">Nombre</label>
                            <input type="text" id="nombre_vendedor" name="nombre_vendedor" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="apellido_vendedor">Apellido</label>
                            <input type="text" id="apellido_vendedor" name="apellido_vendedor" required>
                        </div>

                        <div class="form-group">
                            <label for="nombre_negocio">Nombre del Negocio</label>
                            <input type="text" id="nombre_negocio" name="nombre_negocio" required>
                        </div>

                        <div class="form-group">
                            <label for="telefono_vendedor">Teléfono</label>
                            <input type="tel" id="telefono_vendedor" name="telefono_vendedor" required>
                        </div>

                        <div class="form-group">
                            <label for="email_vendedor">Email</label>
                            <input type="email" id="email_vendedor" name="email_vendedor" required>
                        </div>

                        <div class="form-group">
                            <label for="password_vendedor">Contraseña</label>
                            <input type="password" id="password_vendedor" name="password_vendedor" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="confirm_password_vendedor">Confirmar Contraseña</label>
                            <input type="password" id="confirm_password_vendedor" name="confirm_password_vendedor" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="descripcion">Descripción de tu empresa:</label>
                            <textarea id="descripcion" name="descripcion" rows="4"></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="direccion">Dirección:</label>
                            <textarea type="text" id="direccion" name="dirección" rows='4'></textarea>
                        </div>

                        <input type="hidden" name="user_type" value="vendedor">

                        <button type="submit" class="btn-submit">Enviar Solicitud</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Snackver</h3>
                    <p>La plataforma de comida entre estudiantes universitarios. Hecha por estudiantes, para estudiantes.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                <div class="footer-column">
                    <h3>Enlaces útiles</h3>
                    <ul class="footer-links">
                        <li><a href="acerca-nosotros.php">Acerca de nosotros</a></li>
                        <li><a href="preguntas-frecuentes.php">Preguntas frecuentes</a></li>
                        <li><a href="politicas-privacidad.php">Políticas de privacidad</a></li>
                        <li><a href="terminos y condiciones.php">Términos y condiciones</a></li>
                        
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>¿Eres vendedor?</h3>
                    <ul class="footer-links">
                        <li><a href="registrar-vendedor.php">Registrate como vendedor</a></li>
                        <li><a href="guia-vendedor.php">Guía para vendedores</a></li>
                        <li><a href="recomendaciones-empaque.php">Recomendaciones de empaque</a></li>
                        <li><a href="preguntas-vendedores.php">Preguntas frecuentes para vendedores</a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                &copy; 2025 Snackver. Todos los derechos reservados. Hecho con ❤️ para la comunidad universitaria.
            </div>
        </div>
    </footer>

    <script>
        const header = document.querySelector('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
</body>

</html>

