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

// --- 3. LÓGICA PARA PROCESAR EL FORMULARIO ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recoger datos del formulario
    $nombre = $_POST['nombre_platillo'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $idcategoria = $_POST['idcategoria'];

    // --- NUEVO: Recibir el stock del formulario (si no envían nada, por defecto 1) ---
    $disponible = isset($_POST['disponible']) ? intval($_POST['disponible']) : 1;

    // --- Lógica de subida de imagen ---
    $uploadOk = 1;
    $target_dir = dirname(dirname(__DIR__)) . "/img/";
    $file_name = time() . '_' . basename($_FILES["imagen_platillo"]["name"]);
    $target_file = $target_dir . $file_name;
    $db_path = "img/" . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Verificar imagen
    $check = getimagesize($_FILES["imagen_platillo"]["tmp_name"]);
    if ($check === false) {
        $mensaje_formulario = "El archivo no es una imagen.";
        $uploadOk = 0;
    }

    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        $mensaje_formulario = "Lo sentimos, solo se permiten archivos JPG, JPEG y PNG.";
        $uploadOk = 0;
    }

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["imagen_platillo"]["tmp_name"], $target_file)) {

            // INSERTAR EN LA BASE DE DATOS
            $sql_insert = "INSERT INTO producto 
                             (idvendedor, idcategoria, descripcion, nombreproducto, disponible, imagen, precio) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt_insert = $conn->prepare($sql_insert);

            // i=int, i=int, s=string, s=string, i=int (stock), s=string, d=double (precio)
            $stmt_insert->bind_param(
                "iissssd",
                $id_vendedor_logueado,
                $idcategoria,
                $descripcion,
                $nombre,
                $disponible, // <--- Aquí pasamos el stock real
                $db_path,
                $precio
            );

            if ($stmt_insert->execute()) {
                $_SESSION['mensaje_perfil'] = "¡Platillo agregado con éxito!";
                header("Location: Misplatillos.php");
                exit;
            } else {
                $mensaje_formulario = "Error al guardar en la base de datos: " . $conn->error;
            }
            $stmt_insert->close();
        } else {
            $mensaje_formulario = "Error al subir la imagen. Verifique los permisos de la carpeta.";
        }
    }
}

// --- 4. OBTENER CATEGORÍAS ---
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
    <title>Agregar Platillo - Snackver</title>
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
            <?php if (!empty($mensaje_perfil)): ?>
                <div class="alert" style="color: white; background-color: #4CAF50; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($mensaje_perfil); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-grid">

                <div class="dashboard-content">
                    <div class="dashboard-card">

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3>Agregar Nuevo Platillo</h3>
                            <a href="Misplatillos.php" class="btn btn-secondary" style="text-decoration: none;">&larr; Volver a Mis Platillos</a>
                        </div>

                        <?php if (!empty($mensaje_formulario)): ?>
                            <div class="alert" style="color: white; background-color: #e74c3c; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                                <?php echo htmlspecialchars($mensaje_formulario); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="nuevoplatillo.php" enctype="multipart/form-data">

                            <div class="form-group">
                                <label for="product-name">Nombre del Platillo</label>
                                <input type="text" id="product-name" name="nombre_platillo" class="form-control" placeholder="Ej: Camisa Rosada" required>
                            </div>

                            <div class="form-group">
                                <label for="product-description">Descripción</label>
                                <textarea id="product-description" name="descripcion" class="form-control" rows="3" placeholder="Describe tu producto" required></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="product-price">Precio</label>
                                    <input type="number" id="product-price" name="precio" class="form-control" placeholder="0.00" step="0.01" required>
                                </div>

                                <div class="form-group">
                                    <label for="product-stock">Stock Inicial</label>
                                    <input type="number" id="product-stock" name="disponible" class="form-control" placeholder="Ej: 10" min="1" value="1" required>
                                </div>

                                <div class="form-group">
                                    <label for="product-category">Categoría</label>
                                    <select id="product-category" name="idcategoria" class="form-control" required>
                                        <option value="">Seleccione una categoría...</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?php echo $cat['idcategoria']; ?>">
                                                <?php echo htmlspecialchars($cat['nombrecategoria']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Imagen del Producto</label>

                                <div style="margin-bottom: 15px; background: #f9f9f9; padding: 10px; border-radius: 8px; border: 1px dashed #ccc; display: inline-block;">
                                    <img id="preview-image"
                                        src="../../<?php echo htmlspecialchars($producto_a_editar['imagen']); ?>"
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

                            <button type="submit" class="btn btn-primary">Guardar Platillo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const realFileBtn = document.getElementById("real-file");
            const customTxt = document.getElementById("file-chosen");
            const previewImage = document.getElementById("preview-image"); // Seleccionamos la imagen por su ID

            if (realFileBtn) {
                realFileBtn.addEventListener("change", function() {
                    if (realFileBtn.value) {
                        // 1. Actualizar el texto con el nombre del archivo
                        customTxt.innerHTML = realFileBtn.value.match(/[\/\\]([\w\d\s\.\-\(\)]+)$/)[1];
                        customTxt.style.color = "#333";
                        customTxt.style.fontStyle = "normal";

                        // 2. Lógica para la VISTA PREVIA de la imagen
                        const file = this.files[0]; // Obtener el primer archivo seleccionado
                        if (file) {
                            const reader = new FileReader(); // Crear un lector de archivos

                            // Definir qué hacer cuando se termine de leer el archivo
                            reader.onload = function(e) {
                                if (previewImage) {
                                    // Establecer el resultado de la lectura como la nueva fuente de la imagen
                                    previewImage.src = e.target.result;

                                    // Opcional: Cambiar el texto pequeño de abajo para indicar que es una vista previa
                                    const smallText = previewImage.nextElementSibling;
                                    if (smallText) {
                                        smallText.textContent = "Vista previa de la nueva imagen";
                                    }
                                }
                            };

                            // Leer el archivo como una URL de datos (esto dispara el evento onload)
                            reader.readAsDataURL(file);
                        }

                    } else {
                        customTxt.innerHTML = "No has seleccionado una nueva imagen";
                        customTxt.style.color = "#888";
                        customTxt.style.fontStyle = "italic";
                    }
                });
            }
        });
    </script>

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