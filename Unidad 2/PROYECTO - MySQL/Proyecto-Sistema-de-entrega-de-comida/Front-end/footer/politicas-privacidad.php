<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Políticas de Privacidad - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/politicas-privacidad.css">
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
                <h2 class="section-title">Políticas de Privacidad</h2>
                <div style="max-width: 800px; margin: 0 auto; text-align: justify; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                    <p><strong>Última actualización: 05 de octubre de 2025</strong></p>
                    <p>En Snackver, la confianza es nuestro ingrediente principal. Como estudiantes, valoramos tu privacidad y nos comprometemos a proteger tus datos. Esta política explica de forma clara cómo usamos tu información.</p>
                    
                    <h3 style="color: var(--uni-blue); margin-top: 25px;">1. ¿Qué información recopilamos?</h3>
                    <p>Recopilamos solo lo necesario para que la plataforma funcione: tu nombre, correo institucional (para validar que eres estudiante), datos de tus pedidos y, si eres vendedor, la información de tus platillos y datos para los pagos.</p>

                    <h3 style="color: var(--uni-blue); margin-top: 25px;">2. ¿Cómo usamos tu información?</h3>
                    <p>Tu información se usa para:
                        <ul>
                            <li>Procesar tus compras y ventas de forma segura.</li>
                            <li>Permitir la comunicación entre comprador y vendedor para coordinar la entrega.</li>
                            <li>Verificar tu identidad como miembro de la comunidad universitaria.</li>
                            <li>Mejorar la plataforma y solucionar problemas técnicos.</li>
                        </ul>
                    </p>

                    <h3 style="color: var(--uni-blue); margin-top: 25px;">3. ¿Compartimos tus datos?</h3>
                    <p><strong>Nunca venderemos tus datos a terceros.</strong> Solo compartimos la información estrictamente necesaria entre comprador y vendedor para que la transacción se complete (ej. tu nombre para la entrega). No compartimos datos de contacto directo como tu teléfono o correo personal.</p>
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