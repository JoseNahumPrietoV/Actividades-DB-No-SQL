<?php
// 1. Iniciar sesión
session_start();

// 2. Conexión
$host = "127.0.0.1";
$db_user = "root";
$db_pass = "2884";
$db_name = "sistemawebsnackver";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// 3. Lógica del buscador
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$resultados_items = [];

if (!empty($busqueda)) {

    // CORRECCIÓN: Cambiamos "= 1" por "> 0" para que acepte cualquier stock positivo
    $sql = "SELECT 
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
                (p.nombreproducto LIKE ? OR p.descripcion LIKE ?)
                AND p.disponible > 0"; // <--- AQUÍ ESTABA EL ERROR

    $stmt = $conn->prepare($sql);
    $param = "%" . $busqueda . "%";
    $stmt->bind_param("ss", $param, $param);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($fila = $result->fetch_assoc()) {

            $seller_business = "Vendedor no disponible";
            if (!empty($fila['nombre_negocio'])) {
                $seller_business = htmlspecialchars($fila['nombre_negocio']);
            } else {
                $seller_business = htmlspecialchars($fila['nombre_vendedor'] . ' ' . $fila['apellido_vendedor']);
            }

            $personal_name = htmlspecialchars($fila['nombre_vendedor'] . ' ' . $fila['apellido_vendedor']);
            $ruta_imagen = !empty($fila['imagen']) ? "../" . $fila['imagen'] : "../img/default-food.png";

            $resultados_items[] = [
                'id'            => $fila['idproducto'],
                'image'         => $ruta_imagen,
                'name'          => htmlspecialchars($fila['nombreproducto']),
                'desc'          => htmlspecialchars($fila['descripcion']),
                'seller'        => $seller_business,
                'personal_name' => $personal_name,
                'price'         => '$' . number_format($fila['precio'], 2),
                'stock'         => intval($fila['disponible'])
            ];
        }
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados - Snackver</title>
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