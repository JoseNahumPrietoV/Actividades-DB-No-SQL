<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acerca de Nosotros - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/acerca-nosotros.css">
    <link rel="icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="../img/logo%20-%20snackver.png?v=2.2">
    
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
                <h2 class="section-title">Sobre Nosotros</h2>
                <div style="max-width: 800px; margin: 0 auto; text-align: justify; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                    <p>
                        ¡Hola! Somos un grupo de estudiantes de <strong>Ingeniería en Sistemas Computacionales</strong> del
                        <strong>Instituto Tecnológico de Veracruz</strong>, apasionados por la tecnología y la increíble gastronomía de nuestra región.
                        Snackver nació como un proyecto académico con una misión clara: conectar el talento de las cocinas locales
                        con el apetito de nuestra comunidad a través de una plataforma digital fácil de usar, justa y eficiente.
                    </p>
                    <p>
                        Veracruz tiene una riqueza culinaria inmensa, desde el pequeño puesto de antojitos hasta el restaurante familiar
                        que ha pasado de generación en generación. Sin embargo, notamos que muchas de estas joyas locales no contaban con
                        las herramientas digitales para llegar a más gente. Como futuros ingenieros, decidimos aplicar nuestros
                        conocimientos para crear una solución hecha en casa, por y para la gente de Veracruz.
                    </p>
                    <h3 style="color: var(--uni-blue); margin-top: 25px; margin-bottom: 10px;">Nuestra Misión</h3>
                    <p>
                        Nuestra misión es doble. Por un lado, queremos ofrecer a los Estudiantes una manera sencilla y rápida de
                        disfrutar sus platillos favoritos sin salir de casa. Por otro, buscamos ser un aliado para los pequeños y
                        medianos negocios de comida, dándoles visibilidad y una nueva vía de ingresos sin las comisiones excesivas
                        de las grandes aplicaciones internacionales.
                    </p>
                    <p>
                        Este proyecto es el resultado de muchas horas de código, diseño y, por supuesto, ¡mucha comida para inspirarnos!
                        Gracias por apoyar el talento local y por ser parte de Snackver.
                    </p>
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
                        <li><a href="../footer/preguntas-frecuentes.php">Preguntas frecuentes</a></li>
                        <li><a href="politicas-privacidad.php">Políticas de privacidad</a></li>
                        <li><a href="terminos-y-condiciones.php">Términos y condiciones</a></li>
                        
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>¿Eres vendedor?</h3>
                    <ul class="footer-links">
                        <li><a href="guia-vendedor.php">Guía para vendedores</a></li>
                        <li><a href="recomendaciones-empaque.php">Recomendaciones de empaque</a></li>
                        <li><a href="preguntas-vendedores.php">Preguntas frecuentes para vendedores</a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Snackver. Todos los derechos reservados. Hecho con ❤️ para la comunidad universitaria.</p>
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