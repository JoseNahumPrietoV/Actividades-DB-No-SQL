<?php
// index.php

// --- 1. INCLUIR BASE DE DATOS ---
// Asegúrate de que este archivo 'data.php' tenga la conexión a la BD y cargue $categorias_db
require_once 'Front-end/categorias.php';

// Si 'data.php' no crea la variable $conexion, debemos crearla aquí para las reseñas
if (!isset($conexion)) {
    $conexion = new mysqli("127.0.0.1", "root", "2884", "sistemawebsnackver");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snackver - Comida entre universitarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="Front-end/img/logo - snackver.png" type="image/x-icon">
    <link rel="stylesheet" href="Front-end/css/index.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="#" class="logo">
                    <img src="Front-end/img/logo - snackver.png" alt="Snackver" class="logo-image">Snackver
                </a>

                <div class="auth-buttons">
                    <a href="Front-end/login.php" class="btn btn-outline">Iniciar sesión</a>
                    <a href="Front-end/registro.php" class="btn btn-primary">Registrarse</a>
                </div>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Comida universitaria hecha por estudiantes</h1>
                <p>Compra y vende comida deliciosa dentro de tu campus universitario</p>

                <form action="Front-end/resultados_busqueda.php" method="GET" class="search-bar">
                    <input type="text" name="busqueda" placeholder="¿Qué antojo tienes hoy?" required>
                    <button type="submit" class="search-btn">Buscar</button>
                </form>
            </div>
        </div>
    </section>

    <section class="categories">
        <div class="container">
            <h2 class="section-title">Categorías</h2>
            <div class="categories-grid">
                <?php if (isset($categorias_db)): ?>
                    <?php foreach ($categorias_db as $categoria): ?>
                        <a href="Front-end/Productos.php?id_categoria=<?php echo $categoria['idcategoria']; ?>" class="category-link">
                            <div class="category-card">
                                <div class="category-icon">
                                    <i class="fas fa-<?php
                                                        switch ($categoria['nombrecategoria']) {
                                                            default:
                                                                echo 'store';
                                                        }
                                                        ?>">
                                    </i>
                                </div>
                                <h3><?php echo htmlspecialchars($categoria['nombrecategoria']); ?></h3>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">¿Cómo funciona?</h2>

            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Regístrate</h3>
                    <p>Crea tu cuenta como cliente o vendedor con tu correo electronico</p>
                </div>

                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Encarga o publica</h3>
                    <p>Busca comida disponible cerca de ti o publica lo que quieres vender</p>
                </div>

                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Coordina la entrega</h3>
                    <p>Acuerda el lugar y horario de entrega dentro del campus</p>
                </div>

                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Disfruta</h3>
                    <p>¡Disfruta de deliciosa comida!!!</p>
                </div>
            </div>
        </div>
    </section>



   <section class="resenas">
        <div class="container">
            <h2 class="section-title">Reseñas de estudiantes</h2>
            
            <div class="carousel-container">
                <button class="carousel-btn prev-btn" id="btnPrev">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <div class="carousel-viewport" id="carouselViewport">
                    <div class="carousel-track">
                        
                        <?php
                        // 1. CONSULTA SQL COMPLETA
                        $sql_resenas = "SELECT 
                                            r.calificacion, 
                                            r.comentario, 
                                            r.fecharesena,
                                            c.nombre_cliente, 
                                            c.apellido_cliente,
                                            p.nombreproducto,
                                            v.nombre_negocio,       
                                            v.nombre_vendedor,      
                                            v.apellido_vendedor     
                                        FROM resena r
                                        JOIN cliente c ON r.idcliente = c.idCliente
                                        JOIN producto p ON r.idproducto = p.idproducto
                                        JOIN vendedor v ON p.idvendedor = v.idvendedor 
                                        ORDER BY r.fecharesena DESC 
                                        LIMIT 9";

                        $result_resenas = $conexion->query($sql_resenas);

                        if ($result_resenas && $result_resenas->num_rows > 0) {
                            while ($row = $result_resenas->fetch_assoc()) {
                                // Datos básicos
                                $nombreCompleto = htmlspecialchars($row["nombre_cliente"] . " " . $row["apellido_cliente"]);
                                $producto = htmlspecialchars($row["nombreproducto"]);
                                $calificacion = intval($row["calificacion"]);
                                $comentario = htmlspecialchars($row["comentario"]);
                                $fecha = date("d/m/Y", strtotime($row["fecharesena"])); 

                                // Lógica del Vendedor
                                $vendedor = "Vendedor desconocido";
                                if (!empty($row['nombre_negocio'])) {
                                    $vendedor = htmlspecialchars($row['nombre_negocio']);
                                } else {
                                    $vendedor = htmlspecialchars($row['nombre_vendedor'] . ' ' . $row['apellido_vendedor']);
                                }
                                
                                // Estrellas
                                $estrellas = str_repeat('<i class="fas fa-star" style="color: #f1c40f;"></i>', $calificacion);
                                $estrellas_vacías = str_repeat('<i class="far fa-star" style="color: #ccc;"></i>', 5 - $calificacion);
                                ?>

                                <div class="carousel-card">
                                    <div class="resena-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                                        
                                        <div style="width: 65%;">
                                            <h3 style="margin:0; font-size:1rem; color:#2c3e50; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo $producto; ?>">
                                                <?php echo $producto; ?>
                                            </h3>
                                            
                                            <small style="display: block; color: #7f8c8d; font-size: 0.8rem; margin-top: 2px;">
                                                <i class="fas fa-store" style="font-size: 0.75rem;"></i> <?php echo $vendedor; ?>
                                            </small>
                                        </div>
                                        
                                        <div class="calificacion" style="font-size:0.8rem; min-width: 80px; text-align: right;">
                                            <?php echo $estrellas . $estrellas_vacías; ?>
                                        </div>
                                    </div>
                                    
                                    <p class="comentario" style="font-style:italic; color:#555; margin-bottom:15px; font-size: 0.9rem; min-height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                        "<?php echo $comentario; ?>"
                                    </p>
                                    
                                    <div class="info-autor" style="text-align:right; font-size:0.8rem; color:#888; border-top:1px solid #eee; padding-top:10px;">
                                        <strong><?php echo $nombreCompleto; ?></strong> <br>
                                        <small><?php echo $fecha; ?></small>
                                    </div>
                                </div>

                        <?php
                            } 
                        } else {
                            echo "<div style='text-align:center; width:100%; padding: 20px;'>Aún no hay reseñas disponibles.</div>";
                        }
                        ?>
                    </div>
                </div>

                <button class="carousel-btn next-btn" id="btnNext">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>
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
                        <li><a href="Front-end/footer/acerca-nosotros.php">Acerca de nosotros</a></li>
                        <li><a href="Front-end/footer/preguntas-frecuentes.php">Preguntas frecuentes</a></li>
                        <li><a href="Front-end/footer/politicas-privacidad.php">Políticas de privacidad</a></li>
                        <li><a href="Front-end/footer/terminos y condiciones.php">Términos y condiciones</a></li>
                       
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>¿Eres vendedor?</h3>
                    <ul class="footer-links">
                        <li><a href="Front-end/footer/registrar-vendedor.php">Registrate como vendedor</a></li>
                        <li><a href="Front-end/footer/guia-vendedor.php">Guía para vendedores</a></li>
                        <li><a href="Front-end/footer/recomendaciones-empaque.php">Recomendaciones de empaque</a></li>
                        <li><a href="Front-end/footer/preguntas-vendedores.php">Preguntas frecuentes para vendedores</a></li>
                    </ul>
                </div>
            </div>

            <div class="copyright">
                <p>&copy; 2025 Snackver. Todos los derechos reservados. Hecho con ❤️ para la comunidad universitaria.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Lógica del Carrusel ---
            const viewport = document.getElementById('carouselViewport');
            const btnNext = document.getElementById('btnNext');
            const btnPrev = document.getElementById('btnPrev');
            const scrollStep = 320; // Ancho de tarjeta + gap

            if (btnNext && viewport) {
                btnNext.addEventListener('click', () => {
                    viewport.scrollBy({
                        left: scrollStep,
                        behavior: 'smooth'
                    });
                });
            }

            if (btnPrev && viewport) {
                btnPrev.addEventListener('click', () => {
                    viewport.scrollBy({
                        left: -scrollStep,
                        behavior: 'smooth'
                    });
                });
            }

            // --- Lógica del Header Scroll ---
            const header = document.querySelector('header');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });
        });
    </script>
</body>

</html>
<?php
// Cerrar conexión si está abierta
if (isset($conexion)) $conexion->close();
?>