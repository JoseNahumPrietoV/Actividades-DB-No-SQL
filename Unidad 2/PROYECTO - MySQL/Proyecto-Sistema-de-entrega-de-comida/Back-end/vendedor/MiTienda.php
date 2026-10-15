<?php
// 1. INCLUIR CONEXIÓN Y SESIÓN
include '../conexion/pantalla_usuario_vendedor.php';

// --- 2. CONEXIÓN A BASE DE DATOS ---
$host = "127.0.0.1";
$db_user = "root";
$db_pass = "2884";
$db_name = "sistemawebsnackver";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

$id_vendedor_logueado = $_SESSION['id_vendedor'];
$mensaje_perfil = "";

// --- 3. PROCESAR EL FORMULARIO (GUARDAR DATOS) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recibir datos del formulario
    $nombre_negocio = $_POST['nombre_negocio'];
    $descripcion    = $_POST['descripcion'];
    $direccion      = $_POST['direccion'];
    $telefono       = $_POST['telefono_vendedor'];

    $nombre         = $_POST['nombre_vendedor'];
    $apellido       = $_POST['apellido_vendedor'];
    $email          = $_POST['email_vendedor'];

    // Consulta SQL (Sin categoría)
    $sql_update = "UPDATE vendedor SET 
                    nombre_negocio = ?,
                    descripcion = ?,
                    dirección = ?, 
                    telefono_vendedor = ?,
                    nombre_vendedor = ?,
                    apellido_vendedor = ?,
                    email_vendedor = ?
                   WHERE idvendedor = ?";

    $stmt = $conn->prepare($sql_update);

    // IMPORTANTE: 7 Strings (datos) + 1 Integer (ID) = 8 parámetros
    // "sssssssi"
    $stmt->bind_param("sssssssi", $nombre_negocio, $descripcion, $direccion, $telefono, $nombre, $apellido, $email, $id_vendedor_logueado);

    if ($stmt->execute()) {
        $mensaje_perfil = "¡Datos actualizados correctamente!";

        // Actualizar variables visuales en PHP para que se vea el cambio al instante sin recargar
        $datos_vendedor['nombre_negocio'] = $nombre_negocio;
        $datos_vendedor['descripcion'] = $descripcion;
        $datos_vendedor['dirección'] = $direccion;
        $datos_vendedor['telefono_vendedor'] = $telefono;
        $datos_vendedor['nombre_vendedor'] = $nombre;
        $datos_vendedor['apellido_vendedor'] = $apellido;
        $datos_vendedor['email_vendedor'] = $email;

        // Opcional: Actualizar nombre en sesión si se usa en el header
        $_SESSION['nombre_vendedor'] = $nombre;
    } else {
        $mensaje_perfil = "Error al actualizar: " . $conn->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Tienda - Snackver</title>
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
                                    <div class="store-logo-circle">
                                        <i class="fas fa-store fa-3x"></i>
                                    </div>

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
                            <a href="../../Front-end/recuperacion_password.php" class="btn btn-secondary">
                                <i class="fas fa-lock"></i> Cambiar Contraseña
                            </a>
                        </div>
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