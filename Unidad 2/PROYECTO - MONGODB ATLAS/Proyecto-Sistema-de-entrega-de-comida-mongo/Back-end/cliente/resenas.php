<?php

// 💡 1. INCLUIR EL ARCHIVO CON EL CÓDIGO DE CONEXIÓN Y CONSULTA
require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php';

// Mostrar mensaje de resultado de la sesión (si existe)
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? ''; // Mantengo 'mensaje_perfil' si ya lo usas para mostrar el resultado
unset($_SESSION['mensaje_perfil']); // Limpiar la variable después de mostrar

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// --- PROCESAR EL FORMULARIO (GUARDAR RESEÑA EN MONGODB ATLAS) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_vendedor = $_POST['idvendedor'] ?? '';
    $id_producto = $_POST['idproducto'] ?? '';
    $calificacion = isset($_POST['calificacion']) ? (int)$_POST['calificacion'] : 0;
    $comentario = trim($_POST['comentario'] ?? '');
    $es_anonimo = !empty($_POST['es_anonimo']);
    $esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if (!empty($id_producto) && $calificacion > 0) {
        try {
            $mongoDb = MongoConexion::getConexion();
            $colResenas = $mongoDb->selectCollection('resenas');

            $docResena = [
                'id_producto'  => (preg_match('/^[a-f\d]{24}$/i', $id_producto)) ? new MongoDB\BSON\ObjectId($id_producto) : $id_producto,
                'id_cliente'   => (preg_match('/^[a-f\d]{24}$/i', $idClienteLogueado)) ? new MongoDB\BSON\ObjectId($idClienteLogueado) : $idClienteLogueado,
                'calificacion' => $calificacion,
                'comentario'   => $comentario,
                'es_anonimo'   => (bool)$es_anonimo,
                'fecharesena'  => new MongoDB\BSON\UTCDateTime()
            ];

            $colResenas->insertOne($docResena);

            // Invalidar caché de reseñas de la página principal para reflejar el cambio de inmediato
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            unset($_SESSION['snackver_cache_resenas'], $_SESSION['snackver_cache_resenas_time']);
            session_write_close();

            $mensaje_perfil = "¡Gracias! Tu reseña ha sido publicada con éxito.";
            if ($esAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'success', 'message' => $mensaje_perfil]);
                exit;
            }
        } catch (Exception $e) {
            $mensaje_perfil = "Error al guardar la reseña: " . $e->getMessage();
            if ($esAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => $mensaje_perfil]);
                exit;
            }
        }
    } else {
        $mensaje_perfil = "Por favor selecciona una calificación y un producto.";
        if ($esAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'warning', 'message' => $mensaje_perfil]);
            exit;
        }
    }
}

// --- OBTENER VENDEDORES PARA EL SELECT DESDE MONGODB ATLAS ---
$vendedores = [];
try {
    $mongoDb = MongoConexion::getConexion();
    $colUsuarios = $mongoDb->selectCollection('usuarios');
    $cursorV = $colUsuarios->find(
        ['rol' => 'vendedor', 'estado' => 'activo'],
        ['projection' => [
            'tienda'   => 1,
            'nombre'   => 1,
            'apellido' => 1
        ]]
    );
    foreach ($cursorV as $v) {
        $tienda = $v['tienda'] ?? [];
        $nombreMostrar = !empty($tienda['nombre_negocio']) ? $tienda['nombre_negocio'] : trim(($v['nombre'] ?? '') . ' ' . ($v['apellido'] ?? ''));
        $vendedores[] = [
            'idvendedor'     => (string)$v['_id'],
            'nombre_mostrar' => $nombreMostrar
        ];
    }
} catch (Exception $e) {
    error_log("Error cargando vendedores en resenas.php: " . $e->getMessage());
}
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
    <link rel="icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="../img/logo%20-%20snackver.png?v=2.2">
</head>

<body>

    <header>
        <div class="container header-content">
            <a href="dashboard_cliente.php" class="logo">
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
                        <h4><?php echo htmlspecialchars($datos_cliente['nombre_completo'] ?? 'Cliente'); ?></h4>
                        <p><?php echo htmlspecialchars($datos_cliente['rol_label'] ?? 'Estudiante - Cliente'); ?></p>
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
                                    if (!empty($vendedores)) {
                                        foreach ($vendedores as $v) {
                                            echo '<option value="' . htmlspecialchars($v['idvendedor']) . '">' . htmlspecialchars($v['nombre_mostrar']) . '</option>';
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

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 500; color: #2c3e50; user-select: none;">
                                    <input type="checkbox" name="es_anonimo" id="es_anonimo" value="1" style="width: 18px; height: 18px; cursor: pointer; accent-color: #1A4F8B;">
                                    <span>Publicar como anónimo (no mostrar mi nombre)</span>
                                </label>
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
                fetch('obtener_productos.php?idvendedor=' + idVendedor)
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

        // Publicar reseña en 0ms sin recargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const formResena = document.querySelector('.dashboard-card form');
            if (!formResena) return;

            formResena.addEventListener('submit', function(e) {
                e.preventDefault();
                const btnSubmit = formResena.querySelector('button[type="submit"]');
                const textoOriginal = btnSubmit ? btnSubmit.textContent : 'Publicar Reseña';
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.textContent = 'Publicando...';
                }

                const formData = new FormData(formResena);
                fetch('', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    const container = document.querySelector('main.dashboard-main-content .container');
                    let alerta = container ? container.querySelector('.alert') : null;
                    if (!alerta && container) {
                        alerta = document.createElement('div');
                        alerta.className = 'alert';
                        alerta.style.cssText = 'background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;';
                        container.insertBefore(alerta, container.firstChild);
                    }
                    if (alerta) {
                        alerta.textContent = data.message || 'Reseña procesada.';
                    }

                    if (data.status === 'success') {
                        formResena.reset();
                        const selectProd = document.getElementById('select-producto');
                        if (selectProd) {
                            selectProd.innerHTML = '<option value="">Primero selecciona un vendedor...</option>';
                            selectProd.disabled = true;
                            selectProd.style.backgroundColor = '#f9f9f9';
                        }
                    }
                })
                .catch(err => {
                    console.error('Error publicando reseña:', err);
                })
                .finally(() => {
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        btnSubmit.textContent = textoOriginal;
                    }
                });
            });
        });
    </script>
    <script src="../../Front-end/js/tiempo_real.js?v=2.3"></script>

</body>

</html>