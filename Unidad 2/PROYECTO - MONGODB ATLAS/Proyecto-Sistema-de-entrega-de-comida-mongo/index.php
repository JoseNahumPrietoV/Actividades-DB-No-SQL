<?php
// index.php - Snackver pure MongoDB Atlas
define('SNACKVER_PORTADA', true);
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
require_once __DIR__ . '/Back-end/conexion/conexion_mongo.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snackver - Comida entre universitarios</title>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="Front-end/img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="Front-end/img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="Front-end/img/logo%20-%20snackver.png?v=2.2">
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
                <?php
                $categorias_db = [];
                try {
                    $mongoDb = MongoConexion::getConexion();
                    $colCategorias = $mongoDb->selectCollection('categorias');
                    $cursorCat = $colCategorias->find(
                        ['estado' => 'activo'],
                        [
                            'projection' => ['nombrecategoria' => 1, 'descripcion' => 1, 'imagen' => 1],
                            'sort'       => ['nombrecategoria' => 1]
                        ]
                    );
                    foreach ($cursorCat as $catDoc) {
                        $categorias_db[] = [
                            'idcategoria'     => (string)$catDoc['_id'],
                            'nombrecategoria' => $catDoc['nombrecategoria'] ?? '',
                            'descripcion'     => $catDoc['descripcion'] ?? '',
                            'imagen'          => $catDoc['imagen'] ?? ''
                        ];
                    }
                    // Sincronizar inmediatamente la verdad de Atlas en la sesión
                    $_SESSION['snackver_cache_categorias'] = $categorias_db;
                    $_SESSION['snackver_cache_categorias_time'] = time();
                } catch (Exception $e) {
                    error_log("Error cargando categorías en index.php: " . $e->getMessage());
                    $categorias_db = $_SESSION['snackver_cache_categorias'] ?? [];
                }

                if (!empty($categorias_db)):
                    foreach ($categorias_db as $categoria): ?>
                        <a href="Front-end/Productos.php?id_categoria=<?php echo $categoria['idcategoria']; ?>" class="category-link">
                            <div class="category-card">
                                <div class="category-icon">
                                    <i class="fas fa-store"></i>
                                </div>
                                <h3><?php echo htmlspecialchars($categoria['nombrecategoria']); ?></h3>
                            </div>
                        </a>
                    <?php endforeach;
                endif; ?>
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
                        $resenas_list = [];
                        $cacheResKey = 'snackver_cache_resenas';
                        $cacheResTime = 'snackver_cache_resenas_time';
                        $ahora = time();

                        // 1. Usar micro-caché si existe, NO está vacía y tiene menos de 2 minutos (120 s)
                        if (!empty($_SESSION[$cacheResKey]) && isset($_SESSION[$cacheResTime]) && ($ahora - $_SESSION[$cacheResTime] < 120)) {
                            $resenas_list = $_SESSION[$cacheResKey];
                        } else {
                            try {
                                $mongoDb = MongoConexion::getConexion();
                                $colResenas = $mongoDb->selectCollection('resenas');
                                $colUsuarios = $mongoDb->selectCollection('usuarios');
                                $colProductos = $mongoDb->selectCollection('productos');

                                $cursorR = $colResenas->find([], ['sort' => ['fecharesena' => -1], 'limit' => 9])->toArray();

                                if (!empty($cursorR)) {
                                    $clientIds = [];
                                    $productIds = [];

                                    // Recolectar IDs de clientes y productos
                                    foreach ($cursorR as $rDoc) {
                                        $idCli = $rDoc['id_cliente'] ?? null;
                                        if ($idCli) {
                                            $clientIds[] = (is_string($idCli) && preg_match('/^[a-f\d]{24}$/i', $idCli)) 
                                                ? new MongoDB\BSON\ObjectId($idCli) : $idCli;
                                        }
                                        $idProd = $rDoc['id_producto'] ?? null;
                                        if ($idProd) {
                                            $productIds[] = (is_string($idProd) && preg_match('/^[a-f\d]{24}$/i', $idProd)) 
                                                ? new MongoDB\BSON\ObjectId($idProd) : $idProd;
                                        }
                                    }

                                    // Consulta masiva en lote ($in) para Clientes
                                    $clientesMap = [];
                                    if (!empty($clientIds)) {
                                        $uniqueCli = array_values(array_unique($clientIds, SORT_REGULAR));
                                        $docsCli = $colUsuarios->find(['_id' => ['$in' => $uniqueCli]]);
                                        foreach ($docsCli as $u) {
                                            $clientesMap[(string)$u['_id']] = $u;
                                        }
                                    }

                                    // Consulta masiva en lote ($in) para Productos
                                    $productosMap = [];
                                    $vendorIds = [];
                                    if (!empty($productIds)) {
                                        $uniqueProd = array_values(array_unique($productIds, SORT_REGULAR));
                                        $docsProd = $colProductos->find(['_id' => ['$in' => $uniqueProd]]);
                                        foreach ($docsProd as $p) {
                                            $productosMap[(string)$p['_id']] = $p;
                                            $idVen = $p['id_vendedor'] ?? null;
                                            if ($idVen) {
                                                $vendorIds[] = (is_string($idVen) && preg_match('/^[a-f\d]{24}$/i', $idVen)) 
                                                    ? new MongoDB\BSON\ObjectId($idVen) : $idVen;
                                            }
                                        }
                                    }

                                    // Consulta masiva en lote ($in) para Vendedores
                                    $vendedoresMap = [];
                                    if (!empty($vendorIds)) {
                                        $uniqueVen = array_values(array_unique($vendorIds, SORT_REGULAR));
                                        $docsVen = $colUsuarios->find(['_id' => ['$in' => $uniqueVen]]);
                                        foreach ($docsVen as $v) {
                                            $vendedoresMap[(string)$v['_id']] = $v;
                                        }
                                    }

                                    // Mapear resultados en memoria al instante
                                    foreach ($cursorR as $rDoc) {
                                        $esAnonimo = !empty($rDoc['es_anonimo']);
                                        $nomCliente = "Estudiante";
                                        if ($esAnonimo) {
                                            $nomCliente = "Estudiante Anónimo";
                                        } else {
                                            $idCliStr = isset($rDoc['id_cliente']) ? (string)$rDoc['id_cliente'] : '';
                                            if (isset($clientesMap[$idCliStr])) {
                                                $uDoc = $clientesMap[$idCliStr];
                                                $nomCliente = trim(($uDoc['nombre'] ?? '') . ' ' . ($uDoc['apellido'] ?? '')) ?: 'Estudiante';
                                            }
                                        }

                                        $nomProd = "Platillo";
                                        $nomVend = "Snackver";
                                        $idProdStr = isset($rDoc['id_producto']) ? (string)$rDoc['id_producto'] : '';
                                        if (isset($productosMap[$idProdStr])) {
                                            $pDoc = $productosMap[$idProdStr];
                                            $nomProd = $pDoc['nombreproducto'] ?? 'Platillo';
                                            $idVenStr = isset($pDoc['id_vendedor']) ? (string)$pDoc['id_vendedor'] : '';
                                            if (isset($vendedoresMap[$idVenStr])) {
                                                $vDoc = $vendedoresMap[$idVenStr];
                                                $tienda = $vDoc['tienda'] ?? [];
                                                $nomVend = !empty($tienda['nombre_negocio']) ? $tienda['nombre_negocio'] : trim(($vDoc['nombre'] ?? '') . ' ' . ($vDoc['apellido'] ?? ''));
                                            }
                                        }

                                        $fechaStr = date('d/m/Y');
                                        if (isset($rDoc['fecharesena']) && $rDoc['fecharesena'] instanceof MongoDB\BSON\UTCDateTime) {
                                            $fechaStr = $rDoc['fecharesena']->toDateTime()->format('d/m/Y');
                                        } elseif (!empty($rDoc['fecharesena'])) {
                                            $fechaStr = date('d/m/Y', strtotime((string)$rDoc['fecharesena']));
                                        }

                                        $resenas_list[] = [
                                            'calificacion'   => (int)($rDoc['calificacion'] ?? 5),
                                            'comentario'     => $rDoc['comentario'] ?? '',
                                            'nombreCompleto' => $nomCliente,
                                            'producto'       => $nomProd,
                                            'vendedor'       => $nomVend,
                                            'fecha'          => $fechaStr
                                        ];
                                    }
                                }

                                $_SESSION[$cacheResKey] = $resenas_list;
                                $_SESSION[$cacheResTime] = $ahora;
                            } catch (Exception $e) {
                                error_log("Error cargando reseñas en index.php: " . $e->getMessage());
                            }
                        }

                        // Liberar candado de sesión para permitir recargas y peticiones concurrentes
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            session_write_close();
                        }

                        if (!empty($resenas_list)) {
                            foreach ($resenas_list as $row) {
                                $nombreCompleto = htmlspecialchars($row["nombreCompleto"]);
                                $producto = htmlspecialchars($row["producto"]);
                                $calificacion = intval($row["calificacion"]);
                                $comentario = htmlspecialchars($row["comentario"]);
                                $fecha = htmlspecialchars($row["fecha"]);
                                $vendedor = htmlspecialchars($row["vendedor"]);

                                $estrellas = str_repeat('<i class="fas fa-star" style="color: #f1c40f;"></i>', $calificacion);
                                $estrellas_vacias = str_repeat('<i class="far fa-star" style="color: #ccc;"></i>', max(0, 5 - $calificacion));
                                ?>

                                <div class="carousel-card">
                                    <div class="resena-header">
                                        <div class="producto-info">
                                            <h3 title="<?php echo $producto; ?>">
                                                <?php echo $producto; ?>
                                            </h3>
                                            <small>
                                                <i class="fas fa-store"></i> <?php echo $vendedor; ?>
                                            </small>
                                        </div>
                                        <div class="calificacion">
                                            <?php echo $estrellas . $estrellas_vacias; ?>
                                        </div>
                                    </div>
                                    
                                    <p class="comentario">
                                        "<?php echo $comentario; ?>"
                                    </p>
                                    
                                    <div class="info-autor">
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
                        <li><a href="Front-end/footer/terminos-y-condiciones.php">Términos y condiciones</a></li>
                       
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>¿Eres vendedor?</h3>
                    <ul class="footer-links">
                        <li><a href="Front-end/footer/guia-vendedor.php">Guía para vendedores</a></li>
                        <li><a href="Front-end/footer/recomendaciones-empaque.php">Recomendaciones de empaque</a></li>
                        <li><a href="Front-end/footer/preguntas-vendedores.php">Preguntas frecuentes para vendedores</a></li>
                    </ul>
                </div>
            </div>

            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Snackver. Todos los derechos reservados. Hecho con ❤️ para la comunidad universitaria.</p>
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
    <script src="Front-end/js/tiempo_real.js?v=2.3"></script>
</body>

</html>