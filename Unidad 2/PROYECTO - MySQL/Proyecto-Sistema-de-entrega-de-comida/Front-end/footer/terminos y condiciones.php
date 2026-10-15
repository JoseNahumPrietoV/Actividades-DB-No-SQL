<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Términos y Condiciones - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/terminos y condiciones.css">
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
                <h2 class="section-title">Términos y Condiciones</h2>
                <div class="content-card">
                    <p>¡Bienvenido a Snackver! Al usar nuestra plataforma, aceptas estas reglas del juego, diseñadas para mantener una comunidad segura y respetuosa entre estudiantes.</p>

                    <h3>1. Nuestro Rol</h3>
                    <p>Snackver es un intermediario tecnológico que conecta a estudiantes compradores con estudiantes vendedores. No preparamos ni entregamos la comida. La calidad, seguridad e higiene de los alimentos son <strong>responsabilidad total del vendedor.</strong></p>

                    <h3>2. Responsabilidad del Usuario</h3>
                    <p>Te comprometes a proporcionar información real y a tratar con respeto a los demás miembros de la comunidad. Cualquier actividad fraudulenta o comportamiento irrespetuoso, tanto de compradores como de vendedores, resultará en la <strong>suspensión inmediata de la cuenta.</strong></p>

                    <h3>3. Para Vendedores</h3>
                    <p>Como vendedor, garantizas que cumples con las normas básicas de higiene y que la descripción de tus platillos es precisa (incluyendo posibles alérgenos). Eres responsable de fijar tus precios y coordinar la entrega de manera clara y puntual.</p>
                    
                    <h3>4. Disputas</h3>
                    <p>En caso de un problema con un pedido (ej. no coincide con la descripción, no se entregó), Snackver ofrecerá un sistema de mediación para ayudar a resolver la disputa entre las partes de la forma más justa posible.</p>
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