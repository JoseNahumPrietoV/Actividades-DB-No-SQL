<?php
session_start();

// 1. SEGURIDAD ADMIN
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../Front-end/login.php");
    exit();
}

// Liberar candado de sesión inmediatamente
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

require_once __DIR__ . '/../conexion/conexion_mongo.php';

$vendedores = [];
try {
    $mongoDb = MongoConexion::getConexion();
    $colUsuarios = $mongoDb->selectCollection('usuarios');
    $cursor = $colUsuarios->find(['rol' => 'vendedor'], [
        'sort' => ['fecharegistro' => -1],
        'projection' => [
            'tienda'         => 1,
            'nombre_negocio' => 1,
            'nombre'         => 1,
            'apellido'       => 1,
            'email'          => 1,
            'telefono'       => 1,
            'estado'         => 1,
            'fecharegistro'  => 1,
            'fecha_registro' => 1
        ]
    ]);

    foreach ($cursor as $row) {
        $tienda = $row['tienda'] ?? [];
        $fechaRegStr = 'No registrada';
        $fDoc = $row['fecharegistro'] ?? $row['fecha_registro'] ?? null;
        if ($fDoc instanceof MongoDB\BSON\UTCDateTime) {
            $fechaRegStr = $fDoc->toDateTime()->setTimezone(new DateTimeZone('America/Mexico_City'))->format('d/m/Y H:i');
        } elseif (is_numeric($fDoc)) {
            $fechaRegStr = date('d/m/Y H:i', (int)($fDoc / 1000));
        }

        $vendedores[] = [
            'id'            => (string)$row['_id'],
            'negocio'       => $tienda['nombre_negocio'] ?? ($row['nombre_negocio'] ?? 'Sin Negocio'),
            'nombre'        => trim(($row['nombre'] ?? '') . ' ' . ($row['apellido'] ?? '')),
            'email'         => $row['email'] ?? '',
            'telefono'      => $row['telefono'] ?? '',
            'estado'        => $row['estado'] ?? 'activo',
            'fecharegistro' => $fechaRegStr
        ];
    }
} catch (Exception $e) {
    error_log("Error en vendedores.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Vendedores - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-admin.css">
    <link rel="icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="../img/logo%20-%20snackver.png?v=2.2">
</head>

<body>

    <header>
        <div class="container header-content">
            <a href="dashboard_admin.php" class="logo">
                <img src="../img/logo - snackver.png" alt="Logo de Snackver"> Snackver Admin
            </a>
        </div>
    </header>

    <main class="dashboard-main-content">
        <div class="container">

            <div class="welcome-header">
                <h1>Bienvenido, Jefe.</h1>
            </div>

            <div class="dashboard-grid">
                <aside class="dashboard-sidebar">
                    <nav class="sidebar-nav">
                        <a href="../admin/dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../admin/clientes.php"><i class="fas fa-users"></i> Clientes </a>
                        <a href="../admin/vendedores.php" class="active"><i class="fas fa-store"></i> Vendedores </a>
                        <a href="../admin/categorias.php"><i class="fas fa-tags"></i> Categorías </a>
                        <a href="../admin/historial_usuarios.php"><i class="fas fa-history"></i> Historial de Usuarios</a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-content">
                    <div class="dashboard-card" style="background: white; padding: 25px; border-radius: 8px;">

                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:20px;">
                            <h2>Lista de Vendedores</h2>
                            <span id="contadorVendedores" style="background:#e8f1fa; color:#1A4F8B; padding:6px 14px; border-radius:20px; font-weight:600; font-size:0.9em;">
                                <i class="fas fa-store"></i> <?php echo count($vendedores); ?> vendedores registrados
                            </span>
                        </div>

                        <!-- Barra de Búsqueda y Filtros en Tiempo Real -->
                        <div style="display:flex; gap:12px; margin-bottom:20px; flex-wrap:wrap; background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0;">
                            <div style="flex:1; min-width:240px; position:relative;">
                                <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                                <input type="text" id="busquedaInput" placeholder="Buscar por negocio, dueño, correo o teléfono..." 
                                       style="width:100%; padding:9px 12px 9px 36px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.95em; outline:none; transition:border-color 0.2s;"
                                       onfocus="this.style.borderColor='#1A4F8B'" onblur="this.style.borderColor='#cbd5e1'" oninput="filtrarTabla()" onkeyup="filtrarTabla()">
                            </div>
                            <div style="min-width:180px;">
                                <select id="filtroEstado" onchange="filtrarTabla()" 
                                        style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.95em; outline:none; background:#ffffff;">
                                    <option value="todos">Todos los estados</option>
                                    <option value="activo">Activos</option>
                                    <option value="inactivo">Inactivos</option>
                                    <option value="pendiente_verificacion">Pendientes (2FA)</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-container">
                            <table id="tablaVendedores">
                                <thead>
                                    <tr>
                                        <th>Negocio</th>
                                        <th>Dueño</th>
                                        <th>Contacto</th>
                                        <th>Fecha de Registro</th>
                                        <th style="text-align:center;">Estado / 2FA</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($vendedores)): ?>
                                        <?php foreach ($vendedores as $row):
                                            $id = $row['id'];
                                            $negocio = htmlspecialchars($row['negocio']);
                                            $nombre = htmlspecialchars($row['nombre']);
                                            $email = htmlspecialchars($row['email']);
                                            $telefono = htmlspecialchars($row['telefono']);
                                            $estado = $row['estado'];
                                            $fecharegistro = htmlspecialchars($row['fecharegistro']);
                                        ?>
                                            <tr id="fila-<?php echo $id; ?>"
                                                class="fila-vendedor"
                                                data-negocio="<?php echo htmlspecialchars(mb_strtolower($negocio, 'UTF-8')); ?>"
                                                data-nombre="<?php echo htmlspecialchars(mb_strtolower($nombre, 'UTF-8')); ?>"
                                                data-email="<?php echo htmlspecialchars(mb_strtolower($email, 'UTF-8')); ?>"
                                                data-telefono="<?php echo htmlspecialchars(mb_strtolower($telefono, 'UTF-8')); ?>"
                                                data-estado="<?php echo htmlspecialchars($estado); ?>">
                                                <td>
                                                    <div style="font-weight:bold; color:#1A4F8B;"><i class="fas fa-store-alt" style="margin-right:4px;"></i> <?php echo $negocio; ?></div>
                                                </td>
                                                <td><?php echo $nombre; ?></td>
                                                <td style="font-size: 0.9em;">
                                                    <div><i class="fas fa-envelope" style="color:#64748b;"></i> <?php echo $email; ?></div>
                                                    <div><i class="fas fa-phone" style="color:#64748b;"></i> <?php echo $telefono ?: '<span style="color:#94a3b8;">Sin teléfono</span>'; ?></div>
                                                </td>
                                                <td style="font-size:0.9em; color:#475569;"><?php echo $fecharegistro; ?></td>
                                                <td style="text-align:center; white-space:nowrap;">
                                                    <?php if ($estado === 'activo'): ?>
                                                        <span id="badge-<?php echo $id; ?>" class="badge badge-active">
                                                             <i class="fas fa-check-circle"></i> Activo (2FA)
                                                        </span>
                                                    <?php elseif ($estado === 'pendiente_verificacion'): ?>
                                                        <span id="badge-<?php echo $id; ?>" class="badge badge-pending">
                                                             <i class="fas fa-clock"></i> Pendiente (2FA)
                                                        </span>
                                                    <?php else: ?>
                                                        <span id="badge-<?php echo $id; ?>" class="badge badge-inactive">
                                                             <i class="fas fa-ban"></i> Inactivo
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="columna-acciones">
                                                        <a href="editar_vendedor.php?id=<?php echo $id; ?>" class="btn-action btn-edit" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>

                                                        <?php if ($estado === 'activo'): ?>
                                                            <button class="btn-action" style="background-color:#f59e0b;" onclick="cambiarEstadoVendedor('<?php echo $id; ?>', 'inactivo')" title="Inactivar">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button class="btn-action btn-success" onclick="cambiarEstadoVendedor('<?php echo $id; ?>', 'activo')" title="Activar">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>

                                                        <button class="btn-action btn-danger" onclick="eliminarVendedor('<?php echo $id; ?>')" title="Eliminar">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr id="sinResultadosInicial" style="<?php echo empty($vendedores) ? '' : 'display:none;'; ?>">
                                        <td colspan="6" style="text-align:center; padding: 25px; color:#64748b;">
                                            <i class="fas fa-store" style="font-size:2em; margin-bottom:10px; display:block; color:#cbd5e1;"></i>
                                            No hay vendedores registrados en el sistema.
                                        </td>
                                    </tr>
                                    <tr id="filaSinCoincidencias" style="display:none;">
                                        <td colspan="6" style="text-align:center; padding: 25px; color:#64748b;">
                                            <i class="fas fa-search" style="font-size:1.5em; margin-bottom:8px; display:block;"></i>
                                            No se encontraron vendedores que coincidan con la búsqueda o filtro seleccionado.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function filtrarTabla() {
            const query = document.getElementById('busquedaInput').value.toLowerCase().trim();
            const estadoFiltro = document.getElementById('filtroEstado').value;
            const filas = document.querySelectorAll('.fila-vendedor');
            let visibles = 0;

            filas.forEach(fila => {
                const negocio = fila.getAttribute('data-negocio') || '';
                const nombre = fila.getAttribute('data-nombre') || '';
                const email = fila.getAttribute('data-email') || '';
                const telefono = fila.getAttribute('data-telefono') || '';
                const estado = fila.getAttribute('data-estado') || '';

                const coincideTexto = !query || negocio.includes(query) || nombre.includes(query) || email.includes(query) || telefono.includes(query);
                const coincideEstado = (estadoFiltro === 'todos') || (estado === estadoFiltro);

                if (coincideTexto && coincideEstado) {
                    fila.style.display = '';
                    visibles++;
                } else {
                    fila.style.display = 'none';
                }
            });

            const sinCoincidencias = document.getElementById('filaSinCoincidencias');
            if (sinCoincidencias) {
                sinCoincidencias.style.display = (visibles === 0 && filas.length > 0) ? '' : 'none';
            }

            const sinInicial = document.getElementById('sinResultadosInicial');
            if (sinInicial) {
                sinInicial.style.display = (filas.length === 0) ? '' : 'none';
            }

            const contador = document.getElementById('contadorVendedores');
            if (contador) {
                contador.innerHTML = `<i class="fas fa-store"></i> Mostrando ${visibles} de ${filas.length} vendedores`;
            }
        }

        function cambiarEstadoVendedor(idVendedor, nuevoEstado) {
            if (!confirm('¿Estás seguro de cambiar el estado de este vendedor a ' + nuevoEstado + '?')) return;

            const formData = new FormData();
            formData.append('id', idVendedor);
            formData.append('estado', nuevoEstado);

            fetch('actualizar_estado_vendedor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const fila = document.getElementById('fila-' + idVendedor);
                        const badge = document.getElementById('badge-' + idVendedor);
                        if (fila) {
                            fila.setAttribute('data-estado', nuevoEstado);
                            const colAcciones = fila.querySelector('.columna-acciones');
                            if (colAcciones) {
                                const btnToggle = colAcciones.querySelector('button[onclick*="cambiarEstadoVendedor"]');
                                if (btnToggle) {
                                    if (nuevoEstado === 'activo') {
                                        btnToggle.className = 'btn-action';
                                        btnToggle.style.backgroundColor = '#f59e0b';
                                        btnToggle.setAttribute('onclick', `cambiarEstadoVendedor('${idVendedor}', 'inactivo')`);
                                        btnToggle.setAttribute('title', 'Inactivar');
                                        btnToggle.innerHTML = '<i class="fas fa-ban"></i>';
                                    } else {
                                        btnToggle.className = 'btn-action btn-success';
                                        btnToggle.style.backgroundColor = '';
                                        btnToggle.setAttribute('onclick', `cambiarEstadoVendedor('${idVendedor}', 'activo')`);
                                        btnToggle.setAttribute('title', 'Activar');
                                        btnToggle.innerHTML = '<i class="fas fa-check"></i>';
                                    }
                                }
                            }
                        }
                        if (badge) {
                            if (nuevoEstado === 'activo') {
                                badge.className = 'badge badge-active';
                                badge.innerHTML = '<i class="fas fa-check-circle"></i> Activo (2FA)';
                            } else {
                                badge.className = 'badge badge-inactive';
                                badge.innerHTML = '<i class="fas fa-ban"></i> Inactivo';
                            }
                        }
                        filtrarTabla();
                        if (typeof window.sincronizarVistaTiempoReal === 'function') {
                            window.sincronizarVistaTiempoReal();
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                });
        }

        function eliminarVendedor(idVendedor) {
            if (!confirm('¿Estás seguro de eliminar permanentemente a este usuario? Esta acción no se puede deshacer.')) return;

            const formData = new FormData();
            formData.append('id', idVendedor);

            fetch('eliminar_vendedor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const fila = document.getElementById('fila-' + idVendedor);
                        if (fila) {
                            fila.remove();
                            filtrarTabla();
                        }
                        if (typeof window.sincronizarVistaTiempoReal === 'function') {
                            window.sincronizarVistaTiempoReal();
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión al eliminar el vendedor.');
                });
        }
    </script>
    <script src="../../Front-end/js/tiempo_real.js?v=2.3"></script>

</body>

</html>