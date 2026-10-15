<?php
// 1. INCLUIR EL ARCHIVO DE SESIÓN
include '../conexion/pantalla_usuario_vendedor.php';

// --- 2. RECONECTAR A LA BASE DE DATOS ---
$host = "127.0.0.1";
$db_user = "root";
$db_pass = "2884";
$db_name = "sistemawebsnackver";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtenemos el ID del vendedor de la sesión
$id_vendedor_logueado = $_SESSION['id_vendedor'];
$mensaje_formulario = '';
$producto_a_editar = null;

// --- 3. LÓGICA PARA PROCESAR EL FORMULARIO (CUANDO SE ENVÍA) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recoger datos del formulario
    $idproducto = $_POST['idproducto'];
    $nombre = $_POST['nombre_platillo'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $disponible = $_POST['disponible']; // <--- NUEVO DATO RECIBIDO (STOCK)
    $idcategoria = $_POST['idcategoria'];
    $ruta_imagen_actual = $_POST['imagen_actual'];

    $db_path = $ruta_imagen_actual;
    $uploadOk = 1;

    // --- Revisar si se subió una NUEVA imagen ---
    if (isset($_FILES["imagen_platillo"]) && $_FILES["imagen_platillo"]["size"] > 0) {

        $target_dir = dirname(dirname(__DIR__)) . "/img/";
        $file_name = time() . '_' . basename($_FILES["imagen_platillo"]["name"]);
        $target_file = $target_dir . $file_name;
        $db_path_nueva = "img/" . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["imagen_platillo"]["tmp_name"]);
        if ($check === false) {
            $mensaje_formulario = "El archivo nuevo no es una imagen.";
            $uploadOk = 0;
        }
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
            $mensaje_formulario = "Solo se permiten archivos JPG, JPEG y PNG.";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["imagen_platillo"]["tmp_name"], $target_file)) {
                $db_path = $db_path_nueva;

                if ($ruta_imagen_actual && file_exists(dirname(dirname(__DIR__)) . "/" . $ruta_imagen_actual)) {
                    unlink(dirname(dirname(__DIR__)) . "/" . $ruta_imagen_actual);
                }
            } else {
                $mensaje_formulario = "Error al subir la nueva imagen.";
                $uploadOk = 0;
            }
        }
    }

    // Si todo está bien, actualizamos la BD
    if ($uploadOk == 1) {
        // AGREGAMOS 'disponible = ?' A LA CONSULTA
        $sql_update = "UPDATE producto SET 
                         nombreproducto = ?, 
                         descripcion = ?, 
                         precio = ?, 
                         disponible = ?,  
                         idcategoria = ?, 
                         imagen = ? 
                       WHERE idproducto = ? AND idvendedor = ?";

        $stmt_update = $conn->prepare($sql_update);

        // AÑADIMOS EL TIPO 'i' (entero) para el stock
        // Tipos: s (string), s (string), d (double), i (int-stock), i (int-cat), s (string), i (id), i (vendedor)
        $stmt_update->bind_param(
            "ssdiisii",
            $nombre,
            $descripcion,
            $precio,
            $disponible, // <--- Aquí pasamos el stock
            $idcategoria,
            $db_path,
            $idproducto,
            $id_vendedor_logueado
        );

        if ($stmt_update->execute()) {
            $_SESSION['mensaje_perfil'] = "¡Platillo actualizado con éxito!";
            header("Location: Misplatillos.php");
            exit;
        } else {
            $mensaje_formulario = "Error al actualizar la base de datos: " . $conn->error;
        }
        $stmt_update->close();
    }
}

// --- 4. LÓGICA PARA CARGAR DATOS (CUANDO SE ENTRA POR GET) ---
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $id_producto_editar = $_GET['id'] ?? 0;
    if ($id_producto_editar == 0) {
        die("ID de platillo no válido.");
    }

    $sql_get = "SELECT * FROM producto WHERE idproducto = ? AND idvendedor = ?";
    $stmt_get = $conn->prepare($sql_get);
    $stmt_get->bind_param("ii", $id_producto_editar, $id_vendedor_logueado);
    $stmt_get->execute();
    $resultado = $stmt_get->get_result();

    if ($resultado->num_rows == 1) {
        $producto_a_editar = $resultado->fetch_assoc();
    } else {
        die("Platillo no encontrado o no te pertenece.");
    }
    $stmt_get->close();
}

// --- 5. OBTENER CATEGORÍAS ---
$categorias = [];
$sql_cat = "SELECT idcategoria, nombrecategoria FROM categoriaproducto WHERE estado = 'activo' ORDER BY nombrecategoria";
$result_cat = $conn->query($sql_cat);
if ($result_cat && $result_cat->num_rows > 0) {
    while ($row = $result_cat->fetch_assoc()) {
        $categorias[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Platillo - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-vendedor.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/resumen-vendedor.css?v=<?php echo time(); ?>">
    <link rel="shortcut icon" href="../img/logo - snackver.png" type="image/x-icon">
</head>

<body>
        <header>
        <div class="container header-content">
            <a href="../../Back-end/vendedor/dashboard_vendedor.php" class="logo">
                <img src="../img/logo - snackver.png" alt="Logo de Snackver"> Snackver
            </a>
            <nav></nav>
            <div class="auth-buttons">

                <div class="notification-bell" id="bellIcon">
                    <i class="fas fa-bell"></i>

                    <span id="notification-badge"></span>

                    <div id="notification-dropdown">
                        <div class="dropdown-header">Notificaciones</div>
                        <div id="notification-list">
                            <p class="no-notif">No hay notificaciones nuevas</p>
                        </div>
                    </div>
                </div>

                <a href="../conexion/cerrar_sesion.php" class="btn btn-primary" title="Cerrar Sesión">
                    <i class="fas fa-power-off"></i>
                </a>

            </div>
        </div>
    </header>

    <main class="dashboard-main-content">
        <div class="container">
            <div class="dashboard-grid">
               
                <div class="dashboard-content">
                    <div class="dashboard-card">

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3>Editando: <?php echo htmlspecialchars($producto_a_editar['nombreproducto']); ?></h3>
                            <a href="Misplatillos.php" class="btn btn-secondary" style="text-decoration: none;">&larr; Volver a Mis Platillos</a>
                        </div>

                        <?php if (!empty($mensaje_formulario)): ?>
                            <div class="alert" style="color: white; background-color: #e74c3c; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                                <?php echo htmlspecialchars($mensaje_formulario); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data">

                            <input type="hidden" name="idproducto" value="<?php echo $producto_a_editar['idproducto']; ?>">
                            <input type="hidden" name="imagen_actual" value="<?php echo $producto_a_editar['imagen']; ?>">

                            <div class="form-group">
                                <label for="product-name">Nombre del Platillo</label>
                                <input type="text" id="product-name" name="nombre_platillo" class="form-control"
                                    value="<?php echo htmlspecialchars($producto_a_editar['nombreproducto']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="product-description">Descripción</label>
                                <input id="product-description" name="descripcion" class="form-control" rows="4" value="<?php echo htmlspecialchars($producto_a_editar['descripcion']); ?>" required>
                            </div>


                            <div class="form-row">
                                <div class="form-group">
                                    <label for="product-price">Precio</label>
                                    <input type="number" id="product-price" name="precio" class="form-control"
                                        value="<?php echo htmlspecialchars($producto_a_editar['precio']); ?>" step="0.01" required>
                                </div>

                                <div class="form-group">
                                    <label for="product-stock">Stock Disponible</label>
                                    <input type="number" id="product-stock" name="disponible" class="form-control"
                                        value="<?php echo htmlspecialchars($producto_a_editar['disponible']); ?>" min="0" required>
                                </div>

                                <div class="form-group">
                                    <label for="product-category">Categoría</label>
                                    <select id="product-category" name="idcategoria" class="form-control" required>
                                        <option value="">Seleccione una categoría...</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?php echo $cat['idcategoria']; ?>"
                                                <?php if ($cat['idcategoria'] == $producto_a_editar['idcategoria']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($cat['nombrecategoria']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Imagen del Producto</label>

                                <div style="margin-bottom: 15px; background: #f9f9f9; padding: 10px; border-radius: 8px; border: 1px dashed #ccc; display: inline-block;">
                                    <img src="../../<?php echo htmlspecialchars($producto_a_editar['imagen']); ?>"
                                        alt="Imagen actual"
                                        style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px; display: block; margin-bottom: 5px;">
                                    <small style="color: #666;">Imagen actual</small>
                                </div>

                                <div class="custom-file-upload">
                                    <input type="file" id="real-file" name="imagen_platillo" hidden>

                                    <label for="real-file" class="btn-file">
                                        <i class="fas fa-camera"></i> Cambiar Imagen
                                    </label>

                                    <span id="file-chosen" style="margin-left: 10px; color: #888; font-style: italic;">No has seleccionado una nueva imagen</span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

      <script>
    document.addEventListener('DOMContentLoaded', function() {

        // 1. Referencias a los elementos
        const bellIcon = document.getElementById('bellIcon');
        const badge = document.getElementById('notification-badge');
        const dropdown = document.getElementById('notification-dropdown');
        const lista = document.getElementById('notification-list');

        // 2. Lógica del menú desplegable
        if (bellIcon && dropdown) {
            bellIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                // Alternar visibilidad
                if (dropdown.style.display === 'block') {
                    dropdown.style.display = 'none';
                } else {
                    dropdown.style.display = 'block';
                    // Ocultar la burbuja roja al abrir
                    if (badge) badge.style.display = 'none';
                }
            });

            // Cerrar si se hace clic fuera
            document.addEventListener('click', function() {
                dropdown.style.display = 'none';
            });
        }

        // 3. Función de consulta (Limpia)
        function consultarNotificaciones() {
            const timestamp = new Date().getTime(); // Evitar caché
            
            // Usamos la ruta relativa directa que te funcionó
            fetch('verificar_notificaciones_vendedor.php?t=' + timestamp)
                .then(response => {
                    if (!response.ok) {
                        // Silencioso: si falla la red, simplemente no hacemos nada
                        return null; 
                    }
                    return response.json();
                })
                .then(data => {
                    // Si data es null o no es array, salimos
                    if (!data || !Array.isArray(data)) return;

                    if (data.length > 0) {
                        
                        // A. Mostrar puntito rojo
                        if (badge) {
                            badge.style.display = 'block';
                            badge.textContent = data.length;
                        }

                        // B. Llenar la lista
                        if (lista) {
                            lista.innerHTML = ''; // Limpiar lista anterior

                            data.forEach(notif => {
                                const item = document.createElement('div');
                                item.className = 'notif-item';
                                
                                // Elegir color del icono según el tipo de mensaje
                                let colorIcono = '#f39c12'; // Naranja por defecto
                                let claseIcono = 'fa-bell';

                                if (notif.mensaje.includes('cancelado')) {
                                    colorIcono = '#e74c3c'; // Rojo
                                    claseIcono = 'fa-exclamation-triangle';
                                } else if (notif.mensaje.includes('pedido')) {
                                    colorIcono = '#2ecc71'; // Verde
                                    claseIcono = 'fa-receipt';
                                }

                                item.innerHTML = `
                                    <a href="../vendedor/Gestion_platillos.php" style="text-decoration:none; color:#333; display:flex; align-items:center; gap:10px; padding:10px; border-bottom:1px solid #eee;">
                                        <i class="fas ${claseIcono}" style="color:${colorIcono};"></i> 
                                        <span style="font-size:13px;">${notif.mensaje}</span>
                                    </a>
                                `;
                                lista.appendChild(item);
                            });
                        }
                    }
                })
                .catch(error => {
                    // Dejamos solo errores críticos en consola, pero quitamos el spam
                    // console.error(error); 
                });
        }

        // Ejecutar cada 2 segundos
        setInterval(consultarNotificaciones, 2000);

        // Ejecutar inmediatamente al cargar
        consultarNotificaciones();
    });
</script>


</body>

</html>
