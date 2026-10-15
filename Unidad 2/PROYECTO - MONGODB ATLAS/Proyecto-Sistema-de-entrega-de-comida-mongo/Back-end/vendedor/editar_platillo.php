<?php
// 1. INCLUIR EL ARCHIVO DE SESIÓN
require_once __DIR__ . '/../conexion/pantalla_usuario_vendedor.php';
require_once dirname(__DIR__) . '/servicios/CloudinaryService.php';
require_once dirname(__DIR__) . '/conexion/conexion_mongo.php';

use Snackver\Servicios\CloudinaryService;

// Obtenemos el ID del vendedor de la sesión
$id_vendedor_logueado = $_SESSION['id_vendedor'];
$mensaje_formulario = '';
$producto_a_editar = null;
$mongoDb = MongoConexion::getConexion();
$colProductos = $mongoDb->selectCollection('productos');

// Helper para crear filtro de producto
function obtenerFiltroProducto($idproducto, $id_vendedor) {
    $filtroId = [];
    if (is_string($idproducto) && preg_match('/^[a-f\d]{24}$/i', $idproducto)) {
        $filtroId = ['_id' => new MongoDB\BSON\ObjectId($idproducto)];
    } else {
        $filtroId = ['$or' => [['_id' => (int)$idproducto], ['_id' => (string)$idproducto]]];
    }
    return $filtroId;
}

// --- 2. LÓGICA PARA PROCESAR EL FORMULARIO (CUANDO SE ENVÍA) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recoger datos del formulario
    $idproducto = $_POST['idproducto'];
    $nombre = trim($_POST['nombre_platillo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $disponible = isset($_POST['disponible']) ? intval($_POST['disponible']) : 0;
    $idcategoria = $_POST['idcategoria'] ?? null;
    $ruta_imagen_actual = $_POST['imagen_actual'] ?? '';

    $db_path = $ruta_imagen_actual;
    $uploadOk = 1;

    // --- Revisar si se subió una NUEVA imagen a Cloudinary ---
    if (isset($_FILES["imagen_platillo"]) && $_FILES["imagen_platillo"]["size"] > 0) {
        try {
            $subida = CloudinaryService::subirImagen($_FILES["imagen_platillo"]["tmp_name"], 'snackver_platillos');
            $db_path = $subida['url'];
        } catch (Exception $e) {
            $mensaje_formulario = "Error al subir la nueva imagen: " . $e->getMessage();
            $uploadOk = 0;
        }
    }

    if ($uploadOk == 1) {
        try {
            $filtro = obtenerFiltroProducto($idproducto, $id_vendedor_logueado);
            $colProductos->updateOne(
                $filtro,
                ['$set' => [
                    'nombreproducto'   => $nombre,
                    'descripcion'      => $descripcion,
                    'precio'           => $precio,
                    'stock_disponible' => $disponible,
                    'disponible'       => $disponible,
                    'id_categoria'     => $idcategoria,
                    'imagen'           => $db_path,
                    'activo'           => ($disponible > 0),
                    'fecha_edicion'    => new MongoDB\BSON\UTCDateTime()
                ]]
            );

            // Invalidar micro-caché de productos de categorías
            foreach ($_SESSION as $k => $v) {
                if (str_starts_with($k, 'snackver_cat_prods_')) {
                    unset($_SESSION[$k]);
                }
            }

            $_SESSION['mensaje_perfil'] = "¡Platillo actualizado con éxito!";
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            header("Location: Misplatillos.php");
            exit;
        } catch (Exception $e) {
            $mensaje_formulario = "Error al actualizar el platillo: " . $e->getMessage();
        }
    }
}

// Liberar bloqueo de sesión inmediatamente para peticiones GET
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// --- 3. LÓGICA PARA CARGAR DATOS (CUANDO SE ENTRA POR GET) ---
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $id_producto_editar = $_GET['id'] ?? '';
    if (empty($id_producto_editar)) {
        die("ID de platillo no válido.");
    }

    try {
        $filtro = obtenerFiltroProducto($id_producto_editar, $id_vendedor_logueado);
        $doc = $colProductos->findOne($filtro);
        if ($doc) {
            $producto_a_editar = [
                'idproducto'     => (string)$doc['_id'],
                'nombreproducto' => $doc['nombreproducto'] ?? '',
                'descripcion'    => $doc['descripcion'] ?? '',
                'precio'         => (float)($doc['precio'] ?? 0),
                'disponible'     => (int)($doc['stock_disponible'] ?? ($doc['disponible'] ?? 0)),
                'idcategoria'    => $doc['id_categoria'] ?? '',
                'imagen'         => $doc['imagen'] ?? ''
            ];
        } else {
            die("Platillo no encontrado o no te pertenece.");
        }
    } catch (Exception $e) {
        die("Error cargando platillo: " . $e->getMessage());
    }
}

// --- 4. OBTENER CATEGORÍAS DESDE MONGODB ---
$categorias = [];
try {
    $mongoDb = MongoConexion::getConexion();
    $colCategorias = $mongoDb->selectCollection('categorias');
    $cursorCat = $colCategorias->find(
        ['estado' => 'activo'],
        [
            'projection' => ['nombrecategoria' => 1],
            'sort'       => ['nombrecategoria' => 1]
        ]
    );
    foreach ($cursorCat as $cat) {
        $categorias[] = [
            'idcategoria'     => (string)$cat['_id'],
            'nombrecategoria' => $cat['nombrecategoria']
        ];
    }
} catch (Exception $e) {
    error_log("Error cargando categorías: " . $e->getMessage());
}

// Liberar bloqueo de sesión para peticiones concurrentes
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Platillo - Snackver</title>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-vendedor.css?v=2.3">
    <link rel="stylesheet" href="../css/resumen-vendedor.css?v=2.3">
    <link rel="icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="../img/logo%20-%20snackver.png?v=2.2">
</head>

<body>
        <header>
        <div class="container header-content">
            <a href="dashboard_vendedor.php" class="logo">
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
                                    <?php 
                                    $imgSrcEdit = $producto_a_editar['imagen'] ?? '';
                                    $rutaPreviewEdit = str_starts_with($imgSrcEdit, 'http') ? $imgSrcEdit : "../../" . $imgSrcEdit;
                                    ?>
                                    <img id="preview-image-edit" src="<?php echo htmlspecialchars($rutaPreviewEdit); ?>"
                                        alt="Imagen actual"
                                        decoding="async"
                                        onerror="this.src='../../img/default-food.png'"
                                        style="width: 100px; height: 100px; object-fit: cover; border-radius: 5px; display: block; margin-bottom: 5px;">
                                    <small id="preview-label-edit" style="color: #666;">Imagen actual</small>
                                </div>

                                <div class="custom-file-upload">
                                    <input type="file" id="real-file" name="imagen_platillo" accept="image/*" hidden>

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
            const realFileBtn = document.getElementById('real-file');
            const customTxt = document.getElementById('file-chosen');
            const previewImage = document.getElementById('preview-image-edit');
            const previewLabel = document.getElementById('preview-label-edit');
            let currentObjectUrl = null;

            function comprimirImagenEnMemoria(file, inputElement) {
                if (!file || !file.type.startsWith('image/') || file.size <= 250 * 1024) return;
                const img = new Image();
                const tempUrl = URL.createObjectURL(file);
                img.onload = function() {
                    URL.revokeObjectURL(tempUrl);
                    const maxDim = 1200;
                    let width = img.width;
                    let height = img.height;
                    if (width > maxDim || height > maxDim) {
                        if (width > height) {
                            height = Math.round((height * maxDim) / width);
                            width = maxDim;
                        } else {
                            width = Math.round((width * maxDim) / height);
                            height = maxDim;
                        }
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    canvas.toBlob(function(blob) {
                        if (blob && blob.size < file.size && typeof DataTransfer !== 'undefined') {
                            const nombreBase = file.name.replace(/\.[^/.]+$/, '');
                            const archivoOptimizado = new File([blob], nombreBase + '.jpg', {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            const dt = new DataTransfer();
                            dt.items.add(archivoOptimizado);
                            inputElement.files = dt.files;
                        }
                    }, 'image/jpeg', 0.84);
                };
                img.src = tempUrl;
            }

            if (realFileBtn) {
                realFileBtn.addEventListener('change', function() {
                    if (realFileBtn.files && realFileBtn.files[0]) {
                        const file = realFileBtn.files[0];
                        if (customTxt) {
                            customTxt.textContent = file.name;
                            customTxt.style.color = '#333';
                            customTxt.style.fontStyle = 'normal';
                        }
                        if (currentObjectUrl) {
                            URL.revokeObjectURL(currentObjectUrl);
                        }
                        // Vista previa instantánea en 0ms desde memoria RAM
                        currentObjectUrl = URL.createObjectURL(file);
                        if (previewImage) {
                            previewImage.src = currentObjectUrl;
                        }
                        if (previewLabel) {
                            previewLabel.innerHTML = '<i class="fas fa-check-circle"></i> Nueva imagen lista';
                            previewLabel.style.color = '#27ae60';
                            previewLabel.style.fontWeight = '600';
                        }
                        // Comprimir en segundo plano para subida instantánea
                        comprimirImagenEnMemoria(file, realFileBtn);
                    }
                });
            }
        });
    </script>
    <script src="../js/notificaciones.js?v=2.3"></script>
</body>

</html>
