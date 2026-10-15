<?php
// 1. INCLUIR EL ARCHIVO DE SESIÓN
include '../conexion/pantalla_usuario_vendedor.php';

// Mostrar mensaje de resultado de la sesión (si existe)
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? '';
unset($_SESSION['mensaje_perfil']);

// --- 2. RECONECTAR A LA BASE DE DATOS ---
$host = "127.0.0.1";
$db_user = "root";
$db_pass = "2884";
$db_name = "sistemawebsnackver";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtenemos el ID del vendedor
$id_vendedor = $_SESSION['id_vendedor'];

// --- 3. CONSULTA DE PLATILLOS ---
$sql_productos = "SELECT idproducto, nombreproducto, descripcion, imagen, precio, disponible 
                  FROM producto 
                  WHERE idvendedor = ?"; // Filtramos por el vendedor dueño de la sesión

$stmt_productos = $conn->prepare($sql_productos);

// --- AQUÍ ESTABA EL ERROR ---
// Antes tenías $id_vendedor_logueado (que no existía).
// Lo cambiamos por $id_vendedor (que definimos en la línea 21).
$stmt_productos->bind_param("i", $id_vendedor); 

$stmt_productos->execute();
$resultado_productos = $stmt_productos->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Platillos - Snackver</title>

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
                <h1>Productos</h1>

            </div>

            <div class="filter-section">
                <a href="dashboard_vendedor.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver al Panel
                </a>
            </div>


                <div class="dashboard-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>Mis Platillos</h3>
                        <a href="nuevoplatillo.php" class="btn btn-primary" style="text-decoration: none;"><i class="fas fa-plus"></i> Agregar Platillo</a>
                    </div>

                    <div class="products-grid" id="products-grid-container">
                        <?php
                        if ($resultado_productos->num_rows > 0) {
                            while ($fila = $resultado_productos->fetch_assoc()) {

                                // Lógica visual para el stock
                                $stock = intval($fila['disponible']);
                                $clase_stock = 'stock-ok';
                                $texto_stock = $stock . ' disponibles';

                                if ($stock == 0) {
                                    $clase_stock = 'stock-out';
                                    $texto_stock = 'Agotado';
                                } elseif ($stock < 5) {
                                    $clase_stock = 'stock-low';
                                    $texto_stock = '¡Solo quedan ' . $stock . '!';
                                }
                        ?>
                                <div class="product-card" id="product-card-<?php echo $fila['idproducto']; ?>">
                                    <div class="product-image">
                                        <img src="../../<?php echo htmlspecialchars($fila['imagen']); ?>"
                                            alt="<?php echo htmlspecialchars($fila['nombreproducto']); ?>"
                                            onerror="this.src=''"
                                            style="width: 100%; height: 160px; object-fit: cover; border-radius: 6px 6px 0 0;">
                                    </div>
                                    <div class="product-info">
                                        <h4><?php echo htmlspecialchars($fila['nombreproducto']); ?></h4>

                                        <div style="margin-bottom: 5px;">
                                            <span class="badge-stock <?php echo $clase_stock; ?>">
                                                <i class="fas fa-box"></i> <?php echo $texto_stock; ?>
                                            </span>
                                        </div>

                                        <p><?php echo htmlspecialchars($fila['descripcion']); ?></p>

                                        <div class="product-price">$<?php echo number_format($fila['precio'], 2); ?></div>

                                        <div class="product-actions">
                                            <a href="editar_platillo.php?id=<?php echo $fila['idproducto']; ?>" class="btn btn-primary" style="text-decoration: none;">Editar</a>

                                        </div>
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo "<p style='grid-column: 1 / -1; text-align: center;'>No tienes platillos registrados. Haz clic en 'Agregar Platillo' para empezar.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </main>

    <div id="delete-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h4>Confirmar Eliminación</h4>
            <p>¿Estás seguro de que quieres eliminar el platillo "<strong id="modal-product-name"></strong>"?</p>
            <p style="color: #e74c3c; font-size: 0.9em;">Esta acción no se puede deshacer.</p>
            <div class="modal-actions">
                <button id="modal-btn-cancel" class="btn btn-secondary">Cancelar</button>
                <button id="modal-btn-confirm" class="btn btn-danger">Sí, Eliminar</button>
            </div>
        </div>
    </div>

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