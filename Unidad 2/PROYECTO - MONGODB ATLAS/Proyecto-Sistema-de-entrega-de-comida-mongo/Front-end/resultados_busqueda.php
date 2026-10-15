<?php
// Front-end/resultados_busqueda.php - Pure MongoDB Atlas
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';

$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$resultados_items = [];

if (!empty($busqueda)) {
    try {
        $mongoDb = MongoConexion::getConexion();
        $colProductos = $mongoDb->selectCollection('productos');
        $colUsuarios = $mongoDb->selectCollection('usuarios');

        $regex = new MongoDB\BSON\Regex($busqueda, 'i');
        $filtro = [
            '$and' => [
                [
                    '$or' => [
                        ['nombreproducto' => $regex],
                        ['descripcion' => $regex]
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

        $cursor = $colProductos->find($filtro, [
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

        // Recolectar IDs de vendedores para una sola consulta masiva en lote ($in)
        $vendorIds = [];
        foreach ($cursor as $fila) {
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
                    'nombre'         => 1,
                    'apellido'       => 1,
                    'tienda'         => 1,
                    'nombre_negocio' => 1
                ]]
            );
            foreach ($docsVen as $v) {
                $vendedoresMap[(string)$v['_id']] = $v;
            }
        }

        foreach ($cursor as $fila) {
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

            $resultados_items[] = [
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
    } catch (Exception $e) {
        error_log("Error en resultados_busqueda.php: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="img/logo%20-%20snackver.png?v=2.2">
    <link rel="stylesheet" href="css/index.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="../index.php" class="logo">
                    <img src="img/logo - snackver.png" alt="Snackver" class="logo-image">Snackver
                </a>
                <div class="auth-buttons">
                    <a href="../index.php" style="color: #ffffffff; text-decoration: none; font-weight: bold;  padding: 8px 15px; border-radius: 5px;">
                        <i class="fas fa-arrow-left"></i> Volver al Inicio
                    </a>
                </div>
            </div>
        </div>
    </header>

    <section class="featured">
        <div class="container">
            <div class="dashboard-card">

                <h2 class="section-title">Resultados para: "<em><?php echo htmlspecialchars($busqueda); ?></em>"</h2>

                <?php if (empty($resultados_items)): ?>
                    <div style="text-align: center; padding: 50px; color: #777;">
                        <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 20px; color: #ccc;"></i>
                        <h3>No encontramos lo que buscas :(</h3>
                        <p>Intenta buscar "Tacos", "Hamburguesa" o "Pizza".</p>
                        <a href="../index.php" class="btn-ver" style="display:inline-block; margin-top:15px;">Regresar al inicio</a>
                    </div>

                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($resultados_items as $item): ?>
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

                                    <p class="item-desc" style="font-size: 0.85em; color: #666; margin-bottom: 10px;">
                                        <?php echo substr($item['desc'], 0, 60) . '...'; ?>
                                    </p>

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