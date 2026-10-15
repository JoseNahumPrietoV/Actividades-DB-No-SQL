<?php

// 💡 1. INCLUIR EL ARCHIVO CON EL CÓDIGO DE CONEXIÓN Y CONSULTA
// Asume que 'pantalla_usuario_cliente.php' está en el mismo directorio (o ajusta la ruta si es diferente, e.g., include '../conexion/pantalla_usuario_cliente.php';)
include '../conexion/pantalla_usuario_cliente.php';

// Mostrar mensaje de resultado de la sesión (si existe)
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? ''; // Mantengo 'mensaje_perfil' si ya lo usas para mostrar el resultado
unset($_SESSION['mensaje_perfil']); // Limpiar la variable después de mostrar


$mensaje_perfil = '';

// --- PROCESAR EL FORMULARIO (GUARDAR RESEÑA) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_vendedor = $_POST['idvendedor'];
    $id_producto = $_POST['idproducto'];
    $calificacion = isset($_POST['calificacion']) ? $_POST['calificacion'] : null;
    $comentario = $_POST['comentario'];
    $fecha = date('Y-m-d H:i:s');

    // Validamos que haya seleccionado producto y calificación
    if (!empty($id_producto) && !empty($calificacion)) {

        // Insertar en la tabla 'resena'
        $sql_insert = "INSERT INTO resena (idproducto, idcliente, calificacion, comentario, fecharesena) VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("iiiss", $id_producto, $idClienteLogueado, $calificacion, $comentario, $fecha);

        if ($stmt->execute()) {
            $mensaje_perfil = "¡Gracias! Tu reseña ha sido publicada con éxito.";
        } else {
            $mensaje_perfil = "Error al guardar: " . $conn->error;
        }
        $stmt->close();
    } else {
        $mensaje_perfil = "Por favor selecciona una calificación y un producto.";
    }
}

// --- OBTENER VENDEDORES PARA EL SELECT ---
$sql_vendedores = "SELECT idvendedor, nombre_negocio, nombre_vendedor, apellido_vendedor FROM vendedor";
$result_vendedores = $conn->query($sql_vendedores);
?>


<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cliente - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-cliente.css">
    <link rel="stylesheet" href="../css/resumen-cliente.css">
    <link rel="shortcut icon" href="../img/logo - snackver.png" type="image/x-icon">
</head>

<body>

    <header>
        <div class="container header-content">
            <a href="../../Back-end/cliente/dashboard_cliente.php" class="logo">
                <img src="../img/logo - snackver.png" alt="Logo de Snackver"> Snackver
            </a>
            <div class="auth-buttons">
                <a href="../cliente/carrito.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Carrito
                </a>
            </div>
        </div>
    </header>


    <main class="dashboard-main-content">
        <div class="container">

            <?php if (!empty($mensaje_perfil)): ?>
                <div class="alert" style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <?php echo htmlspecialchars($mensaje_perfil); ?>
                </div>
            <?php endif; ?>

            <div class="welcome-header">
                <h1>Escribir una Reseña</h1>
                <p>Comparte tu opinión sobre la comida para ayudar a otros estudiantes.</p>
            </div>

            <div class="dashboard-grid">
                <aside class="dashboard-sidebar">
                    <div class="profile-summary">
                        <h4><?php echo htmlspecialchars($datos_cliente['nombre_cliente']); ?></h4>
                        <p>Estudiante</p>
                    </div>
                    <nav class="sidebar-nav">
                        <a href="../cliente/dashboard_cliente.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../cliente/MisPedidos.php"><i class="fas fa-receipt"></i> Mis Pedidos</a>
                        <a href="../cliente/BuscarTienda.php"><i class="fas fa-location-dot"></i> Buscar Tiendas</a>
                        <a href="../cliente/resenas.php" class="active"><i class="fas fa-pen-nib"></i> Reseñas</a>
                        <a href="../cliente/Miperfil.php"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-content">
                    <div class="dashboard-card">
                        <h3>Nueva Reseña</h3>

                        <form action="" method="POST">

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="select-vendedor" style="font-weight: bold; display: block; margin-bottom: 5px;">¿A quién le compraste?</label>
                                <select id="select-vendedor" name="idvendedor" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                                    <option value="">Selecciona un vendedor/tienda...</option>
                                    <?php
                                    if ($result_vendedores->num_rows > 0) {
                                        while ($v = $result_vendedores->fetch_assoc()) {
                                            $nombre_mostrar = !empty($v['nombre_negocio']) ? $v['nombre_negocio'] : $v['nombre_vendedor'] . ' ' . $v['apellido_vendedor'];
                                            echo '<option value="' . $v['idvendedor'] . '">' . htmlspecialchars($nombre_mostrar) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="select-producto" style="font-weight: bold; display: block; margin-bottom: 5px;">¿Qué producto compraste?</label>
                                <select id="select-producto" name="idproducto" required disabled style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; background-color: #f9f9f9;">
                                    <option value="">Primero selecciona un vendedor...</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="calificacion" style="font-weight: bold; display: block; margin-bottom: 5px;">Calificación (1 al 5):</label>
                                <select name="calificacion" id="calificacion" required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                                    <option value="" disabled selected>Selecciona una nota...</option>
                                    <option value="5">5 - Excelente (★★★★★)</option>
                                    <option value="4">4 - Muy bueno (★★★★)</option>
                                    <option value="3">3 - Regular (★★★)</option>
                                    <option value="2">2 - Malo (★★)</option>
                                    <option value="1">1 - Pésimo (★)</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="comentario" style="font-weight: bold; display: block; margin-bottom: 5px;">Tu opinión:</label>
                                <textarea name="comentario" id="comentario" rows="4" placeholder="Escribe aquí qué te pareció la comida..." required style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1.1em;">Publicar Reseña</button>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('select-vendedor').addEventListener('change', function() {
            var idVendedor = this.value;
            var selectProducto = document.getElementById('select-producto');

            selectProducto.innerHTML = '<option value="">Cargando productos...</option>';
            selectProducto.disabled = true;

            if (idVendedor) {
                fetch('../../Back-end/cliente/obtener_productos.php?idvendedor=' + idVendedor)
                    .then(response => response.json())
                    .then(data => {
                        selectProducto.innerHTML = '<option value="">Selecciona un producto...</option>';

                        if (data.length > 0) {
                            data.forEach(producto => {
                                var option = document.createElement('option');
                                option.value = producto.idproducto;
                                option.textContent = producto.nombreproducto;
                                selectProducto.appendChild(option);
                            });
                            selectProducto.disabled = false;
                            selectProducto.style.backgroundColor = "#fff";
                        } else {
                            selectProducto.innerHTML = '<option value="">Este vendedor no tiene productos.</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        selectProducto.innerHTML = '<option value="">Error al cargar.</option>';
                    });
            } else {
                selectProducto.innerHTML = '<option value="">Primero selecciona un vendedor...</option>';
                selectProducto.style.backgroundColor = "#f9f9f9";
            }
        });
    </script>

</body>

</html>
<?php $conn->close(); ?>