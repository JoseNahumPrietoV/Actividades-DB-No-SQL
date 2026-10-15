<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ para Vendedores - Snackver</title>

    <link rel="stylesheet" href="../css/preguntas-vendedores.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <h2 class="section-title">Preguntas Frecuentes (Vendedores)</h2>
                <div class="content-card">
                    <div class="faq-item">
                        <h3>¿Quién puede vender en Snackver?</h3>
                        <p>Cualquier estudiante activo de la comunidad universitaria puede registrarse como vendedor. Es una gran forma de ganar un dinero extra haciendo lo que te apasiona: ¡cocinar!</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Qué comisión cobra Snackver?</h3>
                        <p>Cobramos una pequeña comisión por cada venta para mantener la plataforma funcionando (costos de servidor, seguridad, etc.). Nuestra comisión es mucho más baja que la de las apps comerciales, porque nuestro objetivo es ayudar, no lucrar.</p>
                    </div>
                     <div class="faq-item">
                        <h3>¿Cómo recibo mis pagos?</h3>
                        <p>Una vez que el comprador confirma la entrega, el dinero de tu venta (menos la comisión) se acredita a tu cartera Snackver. Puedes retirar tus ganancias a tu cuenta bancaria una vez por semana.</p>
                    </div>
                    <div class="faq-item">
                        <h3>¿Cómo funciona la entrega?</h3>
                        <p>Tú defines el método de entrega. Puedes ofrecer "recoger en punto" (ej. en la cafetería, a la salida de una clase) o "entrega a domicilio" si tienes la posibilidad. Sé claro con tus opciones y posibles costos extra por entrega.</p>
                    </div>
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