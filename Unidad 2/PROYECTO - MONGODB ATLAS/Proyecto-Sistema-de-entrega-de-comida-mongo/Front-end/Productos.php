<?php
// Front-end/Productos.php - Pure MongoDB Atlas
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!isset($_GET['id_categoria']) || empty($_GET['id_categoria'])) {
    header("Location: ../index.php");
    exit();
}

$id_categoria = trim($_GET['id_categoria']);
$featured_items = [];
$nombre_de_la_categoria = "Categoría";
$categoriaEncontrada = false;

// 1. Resolver el nombre de la categoría en 0 ms desde la sesión sincronizada
if (!empty($_SESSION['snackver_cache_categorias']) && is_array($_SESSION['snackver_cache_categorias'])) {
    foreach ($_SESSION['snackver_cache_categorias'] as $cat) {
        if ($cat['idcategoria'] === $id_categoria) {
            $nombre_de_la_categoria = $cat['nombrecategoria'];
            $categoriaEncontrada = true;
            break;
        }
    }
}

// 2. Comprobar micro-caché de productos de la categoría (vigencia 60s)
$cacheProdKey = 'snackver_cat_prods_' . $id_categoria;
$cacheProdTime = 'snackver_cat_time_' . $id_categoria;

if ($categoriaEncontrada && isset($_SESSION[$cacheProdKey]) && isset($_SESSION[$cacheProdTime]) && (time() - $_SESSION[$cacheProdTime] < 60)) {
    // ¡HIT DE MICRO-CACHÉ! 0 ms de red (sin conexión a Atlas)
    $featured_items = $_SESSION[$cacheProdKey];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
} else {
    // Si no está en caché o la categoría no estaba en sesión, consultar Atlas
    require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';

    try {
        $mongoDb = MongoConexion::getConexion();
        $colCategorias = $mongoDb->selectCollection('categorias');
        $colProductos  = $mongoDb->selectCollection('productos');
        $colUsuarios   = $mongoDb->selectCollection('usuarios');

        if (!$categoriaEncontrada) {
            $filtroCat = (preg_match('/^[a-f\d]{24}$/i', $id_categoria)) 
                ? ['_id' => new MongoDB\BSON\ObjectId($id_categoria), 'estado' => 'activo'] 
                : ['$or' => [['_id' => (int)$id_categoria], ['_id' => (string)$id_categoria]], 'estado' => 'activo'];

            $catDoc = $colCategorias->findOne($filtroCat, ['projection' => ['nombrecategoria' => 1]]);
            if ($catDoc) {
                $nombre_de_la_categoria = $catDoc['nombrecategoria'] ?? 'Categoría';
                $categoriaEncontrada = true;
            }
        }

        if ($categoriaEncontrada) {
            $filtroProd = [
                '$and' => [
                    [
                        '$or' => [
                            ['id_categoria' => (preg_match('/^[a-f\d]{24}$/i', $id_categoria) ? new MongoDB\BSON\ObjectId($id_categoria) : $id_categoria)],
                            ['id_categoria' => (string)$id_categoria],
                            ['id_categoria' => (int)$id_categoria]
                        ]
                    ],
                    [
                        '$or' => [
                            ['disponible' => ['$gt' => 0]],
                            ['stock_disponible' => ['$gt' => 0]],
                            ['disponible' => ['$exists' => false]]
                        ]
                    ]
                ]
            ];

            $cursorProd = $colProductos->find($filtroProd, [
                'projection' => [
                    'id_vendedor'      => 1,
                    'imagen'           => 1,
                    'stock_disponible' => 1,
                    'disponible'       => 1,
                    'nombreproducto'   => 1,
                    'descripcion'      => 1,
                    'precio'           => 1
                ]
            ])->toArray();

            // 1. Recolectar IDs de vendedores únicos para una sola consulta masiva en lote
            $vendorIds = [];
            foreach ($cursorProd as $fila) {
                $idVen = $fila['id_vendedor'] ?? null;
                if ($idVen) {
                    $vendorIds[] = (is_string($idVen) && preg_match('/^[a-f\d]{24}$/i', $idVen)) 
                        ? new MongoDB\BSON\ObjectId($idVen) : $idVen;
                }
            }

            $vendedoresMap = [];
            if (!empty($vendorIds)) {
                $uniqueVen = array_values(array_unique($vendorIds, SORT_REGULAR));
                $docsVen = $colUsuarios->find(
                    ['_id' => ['$in' => $uniqueVen]],
                    ['projection' => [
                        'tienda'   => 1,
                        'nombre'   => 1,
                        'apellido' => 1
                    ]]
                );
                foreach ($docsVen as $v) {
                    $vendedoresMap[(string)$v['_id']] = $v;
                }
            }

            // 2. Mapear platillos con sus vendedores en memoria instantáneamente
            foreach ($cursorProd as $fila) {
                $idVen = $fila['id_vendedor'] ?? null;
                $seller_business = "Vendedor Snackver";
                $personal_name = "Estudiante Vendedor";

                if ($idVen) {
                    $idVenStr = (string)$idVen;
                    if (isset($vendedoresMap[$idVenStr])) {
                        $vendedor = $vendedoresMap[$idVenStr];
                        $tienda = $vendedor['tienda'] ?? [];
                        $seller_business = !empty($tienda['nombre_negocio']) ? $tienda['nombre_negocio'] : trim(($vendedor['nombre'] ?? '') . ' ' . ($vendedor['apellido'] ?? ''));
                        $personal_name = trim(($vendedor['nombre'] ?? '') . ' ' . ($vendedor['apellido'] ?? ''));
                    }
                }

                $imgRaw = $fila['imagen'] ?? '';
                if (!empty($imgRaw)) {
                    $ruta_imagen = str_starts_with($imgRaw, 'http') ? $imgRaw : "../" . $imgRaw;
                } else {
                    $ruta_imagen = "../img/default-food.png";
                }

                $stock = isset($fila['stock_disponible']) ? (int)$fila['stock_disponible'] : (int)($fila['disponible'] ?? 0);

                $featured_items[] = [
                    'id'            => (string)$fila['_id'],
                    'image'         => $ruta_imagen,
                    'name'          => htmlspecialchars($fila['nombreproducto'] ?? ''),
                    'desc'          => htmlspecialchars($fila['descripcion'] ?? ''),
                    'seller'        => htmlspecialchars($seller_business),
                    'personal_name' => htmlspecialchars($personal_name),
                    'price'         => '$' . number_format((float)($fila['precio'] ?? 0), 2),
                    'stock'         => $stock
                ];
            }

            // Guardar en micro-caché para siguientes peticiones instantáneas
            $_SESSION[$cacheProdKey] = $featured_items;
            $_SESSION[$cacheProdTime] = time();
        }
    } catch (Exception $e) {
        error_log("Error al cargar productos en Productos.php: " . $e->getMessage());
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nombre_de_la_categoria); ?> - Snackver</title>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="stylesheet" href="css/index.css?v=2.3">
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="../index.php" class="logo">
                    <img src="img/logo - snackver.png" alt="Snackver" class="logo-image">Snackver
                </a>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-outline">Iniciar sesión</a>
                    <a href="registro.php" class="btn btn-primary">Registrarse</a>
                </div>
            </div>
        </div>
    </header>

    <section class="featured">
        <div class="container">

            <div class="dashboard-card" style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">

                <h2 class="section-title"><?php echo htmlspecialchars($nombre_de_la_categoria); ?></h2>

                <?php if (!$categoriaEncontrada): ?>

                    <div style="text-align: center; padding: 50px; color: #777;">
                        <p>Esta categoría ya no está disponible o ha sido retirada del catálogo.</p>
                        <a href="../index.php" class="btn-primary" style="display:inline-block; margin-top:10px; padding:10px 20px; text-decoration:none;">Ver otras categorías</a>
                    </div>

                <?php elseif (empty($featured_items)): ?>

                    <div style="text-align: center; padding: 50px; color: #777;">
                        <p>No hay productos disponibles en esta categoría por el momento.</p>
                        <a href="../index.php" class="btn-primary" style="display:inline-block; margin-top:10px; padding:10px 20px; text-decoration:none;">Ver otras categorías</a>
                    </div>

                <?php else: ?>

                    <div class="products-grid">
                        <?php foreach ($featured_items as $item): ?>

                            <div class="product-card">
                                <div class="item-image">
                                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" onerror="this.src='../img/default-food.png'">
                                </div>
                                <div class="item-content">

                                    <h3 class="item-title"><?php echo $item['name']; ?></h3>

                                    <div style="margin-bottom: 10px;">
                                        <span class="item-seller" style="color: #2c3e50; font-weight: bold; font-size: 0.9em; display: block;">
                                            <i class="fas fa-store"></i> <?php echo $item['seller']; ?>
                                        </span>

                                        <span style="color: #7f8c8d; font-size: 0.85em; display: block; margin-top: 3px;">
                                            <?php echo $item['personal_name']; ?>
                                        </span>
                                    </div>

                                    <div class="item-meta" style="display: flex; justify-content: space-between; align-items: center; margin-top: auto; border-top: 1px solid #eee; padding-top: 10px;">

                                        <div>
                                            <span class="item-price" style="color: #27ae60; font-weight: bold; font-size: 1.1em;">
                                                <?php echo $item['price']; ?>
                                            </span>

                                            <span class="stock-badge">
                                                <i class="fas fa-box-open"></i> <?php echo $item['stock']; ?>
                                            </span>
                                        </div>

                                    </div>
                                </div>

                            </div>

                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>

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
                        <li><a href="footer/acerca-nosotros.php">Acerca de nosotros</a></li>
                        <li><a href="footer/preguntas-frecuentes.php">Preguntas frecuentes</a></li>
                        <li><a href="footer/politicas-privacidad.php">Políticas de privacidad</a></li>
                        <li><a href="footer/terminos-y-condiciones.php">Términos y condiciones</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>¿Eres vendedor?</h3>
                    <ul class="footer-links">
                        <li><a href="footer/guia-vendedor.php">Guía para vendedores</a></li>
                        <li><a href="footer/recomendaciones-empaque.php">Recomendaciones de empaque</a></li>
                        <li><a href="footer/preguntas-vendedores.php">Preguntas frecuentes para vendedores</a></li>
                    </ul>
                </div>
            </div>

            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Snackver. Todos los derechos reservados. Hecho con ❤️ para la comunidad universitaria.</p>
            </div>
        </div>
    </footer>

    <script src="js/tiempo_real.js?v=2.3"></script>
</body>

</html>