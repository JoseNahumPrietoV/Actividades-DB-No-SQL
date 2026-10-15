<?php
// --- 1. Conexión a la BD ---
$servidor = "127.0.0.1";
$usuario = "root";
$contrasena = "2884";
$base_de_datos = "sistemawebsnackver";

$conexion = new mysqli($servidor, $usuario, $contrasena, $base_de_datos);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// --- 2. Obtener y Validar el ID de la URL ---
if (!isset($_GET['id_categoria']) || !is_numeric($_GET['id_categoria'])) {
    die("Error: No se ha proporcionado una categoría válida.");
}
$id_categoria = (int)$_GET['id_categoria'];

// --- 3. Obtener el nombre de la Categoría ---
$sql_categoria = "SELECT nombrecategoria FROM categoriaproducto WHERE idcategoria = ?";
$stmt_cat = $conexion->prepare($sql_categoria);
$stmt_cat->bind_param("i", $id_categoria);
$stmt_cat->execute();
$resultado_cat = $stmt_cat->get_result();

if ($resultado_cat->num_rows === 0) {
    die("Error: Categoría no encontrada.");
}
$categoria_actual = $resultado_cat->fetch_assoc();
$nombre_de_la_categoria = $categoria_actual['nombrecategoria'];
$stmt_cat->close();

// --- 4. Obtener los Productos ---
$sql_productos = "
    SELECT 
        p.idproducto,
        p.nombreproducto, 
        p.imagen,
        p.descripcion, 
        p.precio, 
        p.disponible, 
        v.nombre_vendedor, 
        v.apellido_vendedor,
        v.nombre_negocio
    FROM 
        producto AS p
    LEFT JOIN 
        vendedor AS v ON p.idvendedor = v.idvendedor
    WHERE 
        p.idcategoria = ? AND p.disponible > 0
";

$stmt_prod = $conexion->prepare($sql_productos);
$stmt_prod->bind_param("i", $id_categoria);
$stmt_prod->execute();
$resultado_productos = $stmt_prod->get_result();

$featured_items = [];

while ($fila = $resultado_productos->fetch_assoc()) {

    // 1. Nombre Principal del Negocio
    $seller_business = "Vendedor no disponible";
    if (!empty($fila['nombre_negocio'])) {
        $seller_business = htmlspecialchars($fila['nombre_negocio']);
    } else {
        // Si no tiene negocio, usamos su nombre personal como principal
        $seller_business = htmlspecialchars($fila['nombre_vendedor'] . ' ' . $fila['apellido_vendedor']);
    }

    // 2. Nombre Personal (Nombre y Apellido real)
    $personal_name = htmlspecialchars($fila['nombre_vendedor'] . ' ' . $fila['apellido_vendedor']);

    // Validar imagen
    $ruta_imagen = !empty($fila['imagen']) ? "../" . $fila['imagen'] : "../img/default-food.png";

    $featured_items[] = [
        'id'            => $fila['idproducto'],
        'image'         => $ruta_imagen,
        'name'          => htmlspecialchars($fila['nombreproducto']),
        'desc'          => htmlspecialchars($fila['descripcion']),

        'seller'        => $seller_business, // Nombre de la Tienda (Aurrera)
        'personal_name' => $personal_name,   // Nombre del Estudiante (Bianca Patricia...)

        'price'         => '$' . number_format($fila['precio'], 2),
        'stock'         => intval($fila['disponible']),

    ];
}

$stmt_prod->close();
$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nombre_de_la_categoria); ?> - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="img/logo - snackver.png" type="image/x-icon">
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

                <?php if (empty($featured_items)): ?>

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
                        <li><a href="Front-end/footer/acerca-nosotros.php">Acerca de nosotros</a></li>
                        <li><a href="Front-end/footer/preguntas-frecuentes.php">Preguntas frecuentes</a></li>
                        <li><a href="Front-end/footer/politicas-privacidad.php">Políticas de privacidad</a></li>
                        <li><a href="Front-end/footer/terminos y condiciones.php">Términos y condiciones</a></li>
                        <li><a href="Front-end/footer/contacto.php">Contacto</a></li>
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

</body>

</html>