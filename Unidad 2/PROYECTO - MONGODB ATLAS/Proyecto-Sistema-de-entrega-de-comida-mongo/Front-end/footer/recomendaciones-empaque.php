<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recomendaciones de Empaque - Snackver</title>
    
    <link rel="stylesheet" href="../css/recomendaciones-empaque.css">
    
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
                <h2 class="section-title">Recomendaciones de Empaque</h2>
                <div class="content-card">
                    <p>La forma en que empacas tu comida es tan importante como su sabor. Un buen empaque asegura que la comida llegue segura, con la temperatura correcta y deje una excelente primera impresión. ¡Aquí te dejamos unos tips!</p>
                    
                    <h3>🚫 1. Cero Derrames: ¡La Regla de Oro!</h3>
                    <p>Nadie quiere recibir una sopa en una bolsa. Para líquidos, sopas o platillos con mucha salsa, usa <strong>contenedores con tapa de rosca o sellado hermético</strong>. Un truco extra es envolver la tapa con un poco de film plástico antes de cerrarla para un doble seguro.</p>
                    
                    <h3>🌡️ 2. Mantén la Temperatura Ideal</h3>
                    <p>
                        <strong>Para lo caliente:</strong> Envuelve el contenedor en una o dos capas de <strong>papel aluminio</strong>. Esto ayuda a conservar el calor por más tiempo. Si son varios productos, juntarlos en una bolsa de papel mantendrá el calor general.
                        <br>
                        <strong>Para lo frío:</strong> Para bebidas, postres fríos o ensaladas, asegúrate de que el contenedor esté frío antes de empacar. Entregarlos en una bolsa separada de los platillos calientes es fundamental.
                    </p>
                    
                    <h3>✨ 3. La Presentación Cuenta Mucho</h3>
                    <p>Un empaque limpio y ordenado habla bien de ti. Usa contenedores del tamaño adecuado para que la comida no se mueva y se desordene. Un pequeño <strong>sticker con tu nombre de vendedor o un simple "¡Gracias!"</strong> escrito en la tapa puede marcar una gran diferencia y fidelizar a tus clientes.</p>
                    
                    <h3>💰 4. Empaques Económicos y Efectivos</h3>
                    <p>No necesitas gastar una fortuna. Puedes encontrar <strong>contenedores de unicel, plástico o cartón a buen precio</strong> en tiendas de materias primas o mercados locales. Comprar en paquetes grandes suele ser más económico. ¡La clave es que sea limpio, seguro y funcional!</p>
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