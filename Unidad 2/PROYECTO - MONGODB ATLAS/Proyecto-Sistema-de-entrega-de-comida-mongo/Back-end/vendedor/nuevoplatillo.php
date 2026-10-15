<?php
// 1. INCLUIR EL ARCHIVO DE SESIÓN
require_once __DIR__ . '/../conexion/pantalla_usuario_vendedor.php';
require_once dirname(__DIR__) . '/servicios/CloudinaryService.php';
require_once dirname(__DIR__) . '/conexion/conexion_mongo.php';

use Snackver\Servicios\CloudinaryService;

// Obtenemos el ID del vendedor de la sesión
$id_vendedor_logueado = $_SESSION['id_vendedor'];
$mensaje_formulario = '';

// --- 2. LÓGICA PARA PROCESAR EL FORMULARIO (MONGODB ATLAS) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recoger datos del formulario
    $nombre = trim($_POST['nombre_platillo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $idcategoria = $_POST['idcategoria'] ?? null;
    $disponible = isset($_POST['disponible']) ? intval($_POST['disponible']) : 1;

    // --- Subida de imagen a la Nube con Cloudinary ---
    if (!isset($_FILES["imagen_platillo"]) || $_FILES["imagen_platillo"]["error"] !== UPLOAD_ERR_OK) {
        $mensaje_formulario = "Por favor selecciona una imagen para el platillo.";
    } else {
        try {
            $resultadoSubida = CloudinaryService::subirImagen($_FILES["imagen_platillo"]["tmp_name"], 'snackver_platillos');
            $db_path = $resultadoSubida['url'];

            // Guardar 100% en MongoDB Atlas
            $mongoDb = MongoConexion::getConexion();
            $colProductos = $mongoDb->selectCollection('productos');
            $docProducto = [
                'id_vendedor'      => $id_vendedor_logueado,
                'id_categoria'     => $idcategoria,
                'nombreproducto'   => $nombre,
                'descripcion'      => $descripcion,
                'precio'           => $precio,
                'stock_disponible' => $disponible,
                'imagen'           => $db_path,
                'activo'           => ($disponible > 0),
                'fechacreacion'    => new MongoDB\BSON\UTCDateTime()
            ];

            $colProductos->insertOne($docProducto);

            // Invalidar micro-caché de productos de categorías
            foreach ($_SESSION as $k => $v) {
                if (str_starts_with($k, 'snackver_cat_prods_')) {
                    unset($_SESSION[$k]);
                }
            }

            $_SESSION['mensaje_perfil'] = "¡Platillo agregado con éxito!";
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            header("Location: Misplatillos.php");
            exit;

        } catch (Exception $e) {
            $mensaje_formulario = "Error al procesar el platillo: " . $e->getMessage();
        }
    }
}

// Liberar bloqueo de sesión inmediatamente para peticiones GET
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// --- 3. OBTENER CATEGORÍAS DESDE MONGODB ATLAS ---
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
    error_log("Error cargando categorías de MongoDB: " . $e->getMessage());
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
    <title>Agregar Platillo - Snackver</title>
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
                        <div class="dropdown-header">
                            <span>Notificaciones</span>
                            <button type="button" class="btn-leer-todas" id="btnMarcarTodasLeidas" title="Marcar todas como leídas">
                                <i class="fas fa-check-double"></i> Leer todas
                            </button>
                        </div>
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

                                <div id="preview-container" style="display: none; margin-bottom: 15px; background: #f9f9f9; padding: 10px; border-radius: 8px; border: 1px dashed #ccc; width: fit-content;">
                                    <img id="preview-image"
                                        src=""
                                        alt="Vista previa"
                                        style="width: 110px; height: 110px; object-fit: cover; border-radius: 6px; display: block; margin-bottom: 6px;">
                                    <small style="color: #27ae60; font-weight: 600;"><i class="fas fa-check-circle"></i> Imagen seleccionada</small>
                                </div>

                                <div class="custom-file-upload">
                                    <input type="file" id="real-file" name="imagen_platillo" accept="image/*" hidden required>
                                    <label for="real-file" class="btn-file">
                                        <i class="fas fa-camera"></i> Seleccionar Imagen
                                    </label>
                                    <span id="file-chosen" style="margin-left: 10px; color: #888; font-style: italic;">No has seleccionado ninguna imagen</span>
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
            const previewContainer = document.getElementById("preview-container");
            const previewImage = document.getElementById("preview-image");
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
                realFileBtn.addEventListener("change", function() {
                    if (realFileBtn.files && realFileBtn.files[0]) {
                        const file = realFileBtn.files[0];
                        customTxt.textContent = file.name;
                        customTxt.style.color = "#333";
                        customTxt.style.fontStyle = "normal";

                        if (currentObjectUrl) {
                            URL.revokeObjectURL(currentObjectUrl);
                        }
                        // Vista previa instantánea en 0ms
                        currentObjectUrl = URL.createObjectURL(file);
                        if (previewImage) {
                            previewImage.src = currentObjectUrl;
                        }
                        if (previewContainer) {
                            previewContainer.style.display = "inline-block";
                        }

                        // Comprimir en segundo plano para subida ultra-rápida
                        comprimirImagenEnMemoria(file, realFileBtn);
                    } else {
                        customTxt.textContent = "No has seleccionado ninguna imagen";
                        customTxt.style.color = "#888";
                        customTxt.style.fontStyle = "italic";
                        if (previewContainer) {
                            previewContainer.style.display = "none";
                        }
                    }
                });
            }
        });
    </script>
    <script src="../js/notificaciones.js?v=2.3"></script>



</body>

</html>