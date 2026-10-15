<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guía para Vendedores - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/guia-vendedor.css">
    <link rel="shortcut icon" href="../img/logo - snackver.png" type="image/x-icon"
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
                <h2 class="section-title">Guía para Vendedores Exitosos</h2>
                <div class="content-card">
                    <p>¡Genial que te unas a Snackver! Aquí tienes unos consejos clave para que tus platillos sean un éxito en la comunidad.</p>

                    <h3>📸 1. ¡Una foto vale más que mil ventas!</h3>
                    <p>Usa luz natural, un fondo limpio y muestra tu comida de la forma más apetitosa posible. No necesitas una cámara profesional, ¡el celular es suficiente! Una buena foto es tu mejor publicidad.</p>

                    <h3>📝 2. Descripciones Claras y honestas</h3>
                    <p>Describe tu platillo con detalle. ¿Qué ingredientes lleva? ¿Es picante? ¿Es vegetariano? Menciona el tamaño de la porción. Ser claro evita malentendidos y genera confianza.</p>

                    <h3>💸 3. Precios Justos</h3>
                    <p>Recuerda que tu mercado son otros estudiantes. Ofrece precios accesibles que cubran tus costos y te dejen una ganancia justa. Un buen precio te traerá más clientes recurrentes.</p>

                    <h3>💬 4. Comunicación es Clave</h3>
                    <p>Responde rápido a las preguntas y mantén informado al comprador sobre su pedido. Si vas a tardar un poco más, avísale. ¡Un buen servicio crea clientes felices!</p>
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
            if (window.scrollY > 50) { header.classList.add('scrolled'); } 
            else { header.classList.remove('scrolled'); }
        });
    </script>

</body>
</html>