<?php
// 1. INCLUIR CONEXIÓN Y SESIÓN
require_once __DIR__ . '/../conexion/pantalla_usuario_vendedor.php';
require_once dirname(__DIR__) . '/servicios/CloudinaryService.php';

use Snackver\Servicios\CloudinaryService;

$id_vendedor_logueado = $_SESSION['id_vendedor'];
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? "";
unset($_SESSION['mensaje_perfil']);

// --- 2. PROCESAR EL FORMULARIO (MONGODB ATLAS) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_negocio = trim($_POST['nombre_negocio'] ?? '');
    $descripcion    = trim($_POST['descripcion'] ?? '');
    $direccion      = trim($_POST['direccion'] ?? '');
    $telefono       = trim($_POST['telefono_vendedor'] ?? '');

    $nombre         = trim($_POST['nombre_vendedor'] ?? '');
    $apellido       = trim($_POST['apellido_vendedor'] ?? '');
    $email          = strtolower(trim($_POST['email_vendedor'] ?? ''));

    // Subir foto del negocio a Cloudinary si se seleccionó archivo
    $foto_negocio_url = null;
    if (isset($_FILES['foto_negocio']) && $_FILES['foto_negocio']['error'] === UPLOAD_ERR_OK && !empty($_FILES['foto_negocio']['tmp_name'])) {
        try {
            $resultadoSubida = CloudinaryService::subirImagen($_FILES['foto_negocio']['tmp_name'], 'snackver_tiendas');
            $foto_negocio_url = $resultadoSubida['url'];
        } catch (Exception $e) {
            $mensaje_perfil = "Error al subir la imagen del negocio: " . $e->getMessage();
        }
    }

    try {
        $mongoDb = MongoConexion::getConexion();
        $colUsuarios = $mongoDb->selectCollection('usuarios');
        $filtro = [];
        if (is_string($id_vendedor_logueado) && preg_match('/^[a-f\d]{24}$/i', $id_vendedor_logueado)) {
            $filtro = ['_id' => new MongoDB\BSON\ObjectId($id_vendedor_logueado)];
        } else {
            $filtro = ['$or' => [['_id' => (int)$id_vendedor_logueado], ['_id' => (string)$id_vendedor_logueado]]];
        }

        $camposUpdate = [
            'nombre'                => $nombre,
            'apellido'              => $apellido,
            'email'                 => $email,
            'telefono'              => $telefono,
            'tienda.nombre_negocio' => $nombre_negocio,
            'tienda.descripcion'    => $descripcion,
            'tienda.direccion'      => $direccion
        ];

        if (!empty($foto_negocio_url)) {
            $camposUpdate['tienda.foto_negocio'] = $foto_negocio_url;
            $datos_vendedor['foto_negocio'] = $foto_negocio_url;
        }

        $colUsuarios->updateOne($filtro, ['$set' => $camposUpdate]);

        $mensaje_perfil = "¡Datos de tu tienda actualizados correctamente!";

        // Actualizar variables visuales en PHP
        $datos_vendedor['nombre_negocio']    = $nombre_negocio;
        $datos_vendedor['descripcion']       = $descripcion;
        $datos_vendedor['dirección']         = $direccion;
        $datos_vendedor['telefono_vendedor'] = $telefono;
        $datos_vendedor['nombre_vendedor']   = $nombre;
        $datos_vendedor['apellido_vendedor'] = $apellido;
        $datos_vendedor['nombre_completo']   = trim("$nombre $apellido") ?: 'Vendedor';
        $datos_vendedor['email_vendedor']    = $email;

        $_SESSION['nombre_vendedor']   = $nombre;
        $_SESSION['apellido_vendedor'] = $apellido;
        $_SESSION['datos_vendedor']    = $datos_vendedor;
    } catch (Exception $e) {
        $mensaje_perfil = "Error al actualizar: " . $e->getMessage();
    }
}

// Si la foto del negocio no está cargada en datos_vendedor, consultar Atlas
if (empty($datos_vendedor['foto_negocio'])) {
    try {
        $mongoDb = MongoConexion::getConexion();
        $colUsuarios = $mongoDb->selectCollection('usuarios');
        $filtroFoto = [];
        if (is_string($id_vendedor_logueado) && preg_match('/^[a-f\d]{24}$/i', $id_vendedor_logueado)) {
            $filtroFoto = ['_id' => new MongoDB\BSON\ObjectId($id_vendedor_logueado)];
        } else {
            $filtroFoto = ['$or' => [['_id' => (int)$id_vendedor_logueado], ['_id' => (string)$id_vendedor_logueado]]];
        }
        $uDoc = $colUsuarios->findOne($filtroFoto, ['projection' => ['tienda.foto_negocio' => 1]]);
        if (!empty($uDoc['tienda']['foto_negocio'])) {
            $datos_vendedor['foto_negocio'] = $uDoc['tienda']['foto_negocio'];
            $_SESSION['datos_vendedor']['foto_negocio'] = $datos_vendedor['foto_negocio'];
        }
    } catch (\Throwable $e) {}
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
    <title>Mi Tienda - Snackver</title>
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

            <div class="welcome-header">
                <h1>Mi tienda</h1>

            </div>

            <div class="filter-section">
                <a href="dashboard_vendedor.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver al Panel
                </a>
            </div>


                
                <div class="dashboard-content">

                    <form action="" method="POST" enctype="multipart/form-data">

                        <div class="dashboard-card">
                            <h3><i class="fas fa-store-alt" style="margin-right: 10px; color: #f1c40f;"></i> Información del Negocio</h3>

                            <div class="store-header-section">
                                <div class="store-logo-container">
                                    <div class="store-logo-circle" style="overflow: hidden; position: relative;">
                                        <?php if (!empty($datos_vendedor['foto_negocio'])): ?>
                                            <img id="logo-preview" src="<?php echo htmlspecialchars($datos_vendedor['foto_negocio']); ?>" alt="Foto del Negocio" style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <i id="logo-icon-placeholder" class="fas fa-store fa-3x"></i>
                                            <img id="logo-preview" src="" alt="Foto del Negocio" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                        <?php endif; ?>
                                    </div>
                                    <label for="foto_negocio" class="btn-change-logo" style="display: block; text-align: center; cursor: pointer;">
                                        <i class="fas fa-camera"></i> Subir Foto
                                    </label>
                                    <input type="file" id="foto_negocio" name="foto_negocio" accept="image/*" style="display: none;" onchange="previewStoreLogo(this);">
                                </div>

                                <div class="store-fields">
                                    <div class="form-group">
                                        <label for="nombre_negocio">Nombre de la Tienda</label>
                                        <input type="text" id="nombre_negocio" name="nombre_negocio" class="form-control"
                                            value="<?php echo htmlspecialchars($datos_vendedor['nombre_negocio'] ?? ''); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="descripcion">Descripción Corta</label>
                                        <input type="text" id="descripcion" name="descripcion" class="form-control"
                                            value="<?php echo htmlspecialchars($datos_vendedor['descripcion'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row-grid">
                                <div class="form-group">
                                    <label for="telefono_vendedor">Teléfono de Contacto</label>
                                    <input type="text" id="telefono_vendedor" name="telefono_vendedor" class="form-control"
                                        value="<?php echo htmlspecialchars($datos_vendedor['telefono_vendedor'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="direccion">Ubicación / Dirección</label>
                                    <input type="text" id="direccion" name="direccion" class="form-control"
                                        value="<?php echo htmlspecialchars($datos_vendedor['dirección'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="dashboard-card">
                            <h3><i class="fas fa-user" style="margin-right: 10px; color: #f1c40f;"></i> Información Personal</h3>

                            <div class="form-row-grid">
                                <div class="form-group">
                                    <label for="nombre_vendedor">Nombre</label>
                                    <input type="text" id="nombre_vendedor" name="nombre_vendedor" class="form-control"
                                        value="<?php echo htmlspecialchars($datos_vendedor['nombre_vendedor'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="apellido_vendedor">Apellido</label>
                                    <input type="text" id="apellido_vendedor" name="apellido_vendedor" class="form-control"
                                        value="<?php echo htmlspecialchars($datos_vendedor['apellido_vendedor'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email_vendedor">Correo Electrónico</label>
                                <input type="email" id="email_vendedor" name="email_vendedor" class="form-control"
                                    value="<?php echo htmlspecialchars($datos_vendedor['email_vendedor'] ?? ''); ?>" required>
                            </div>
                            <div style="text-align: right; margin-bottom: 30px;">
                                <button type="submit" class="btn-save-all">
                                    <i class="fas fa-save"></i> Guardar Todos los Cambios
                                </button>
                            </div>
                        </div>



                    </form>

                    <div class="dashboard-card">
                        <h3>Zona de Seguridad</h3>
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                            <div>
                                <p style="margin: 0; color: #555; font-weight: 600;">Contraseña</p>
                                <p style="margin: 0; color: #888; font-size: 0.9rem;">Se recomienda cambiarla periódicamente.</p>
                            </div>
                            <a href="../../Front-end/cambiar_password.php" class="btn btn-secondary">
                                <i class="fas fa-lock"></i> Cambiar Contraseña
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        let currentLogoObjectUrl = null;

        function comprimirLogoEnMemoria(file, inputElement) {
            if (!file || !file.type.startsWith('image/') || file.size <= 250 * 1024) return;
            const img = new Image();
            const tempUrl = URL.createObjectURL(file);
            img.onload = function() {
                URL.revokeObjectURL(tempUrl);
                const maxDim = 900;
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
                }, 'image/jpeg', 0.85);
            };
            img.src = tempUrl;
        }

        function previewStoreLogo(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const preview = document.getElementById('logo-preview');
                const placeholder = document.getElementById('logo-icon-placeholder');

                if (currentLogoObjectUrl) {
                    URL.revokeObjectURL(currentLogoObjectUrl);
                }
                // Previsualización instantánea en 0ms desde memoria
                currentLogoObjectUrl = URL.createObjectURL(file);
                if (preview) {
                    preview.src = currentLogoObjectUrl;
                    preview.style.display = 'block';
                }
                if (placeholder) {
                    placeholder.style.display = 'none';
                }

                // Comprimir en segundo plano para que al guardar suba al instante
                comprimirLogoEnMemoria(file, input);
            }
        }
    </script>
    <script src="../js/notificaciones.js?v=2.3"></script>



</body>

</html>