<?php
session_start();

// 1. SEGURIDAD: Verificar permisos de administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../Front-end/login.php");
    exit();
}

// Liberar candado de sesión inmediatamente
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

require_once __DIR__ . '/../conexion/conexion_mongo.php';

$historial = [];
$totalEventos = 0;

try {
    $mongoDb = MongoConexion::getConexion();
    $colHistorial = $mongoDb->selectCollection('historial_usuarios');
    $totalEventos = $colHistorial->countDocuments();

    $cursor = $colHistorial->find([], [
        'sort'       => ['fecha' => -1],
        'limit'      => 200,
        'projection' => [
            'id_usuario'  => 1,
            'email'       => 1,
            'rol'         => 1,
            'accion'      => 1,
            'ip'          => 1,
            'dispositivo' => 1,
            'detalles'    => 1,
            'fecha'       => 1
        ]
    ]);

    foreach ($cursor as $doc) {
        $fechaStr = 'Desconocida';
        if (isset($doc['fecha']) && $doc['fecha'] instanceof MongoDB\BSON\UTCDateTime) {
            $fechaStr = $doc['fecha']->toDateTime()->setTimezone(new DateTimeZone('America/Mexico_City'))->format('d/m/Y H:i:s');
        } elseif (isset($doc['fecha']) && is_numeric($doc['fecha'])) {
            $fechaStr = date('d/m/Y H:i:s', (int)($doc['fecha'] / 1000));
        }

        $idUsuarioStr = '';
        if (isset($doc['id_usuario'])) {
            $idUsuarioStr = (string)$doc['id_usuario'];
        }

        $detallesArray = [];
        if (isset($doc['detalles'])) {
            if (is_array($doc['detalles']) || is_object($doc['detalles'])) {
                foreach ($doc['detalles'] as $k => $v) {
                    if (is_bool($v)) {
                        $detallesArray[] = htmlspecialchars($k) . ': ' . ($v ? 'true' : 'false');
                    } elseif (is_scalar($v)) {
                        $detallesArray[] = htmlspecialchars($k) . ': ' . htmlspecialchars((string)$v);
                    } elseif ($v instanceof MongoDB\BSON\UTCDateTime) {
                        $detallesArray[] = htmlspecialchars($k) . ': ' . $v->toDateTime()->setTimezone(new DateTimeZone('America/Mexico_City'))->format('d/m/Y H:i');
                    } elseif (is_array($v) || is_object($v)) {
                        $detallesArray[] = htmlspecialchars($k) . ': ' . htmlspecialchars(json_encode($v, JSON_UNESCAPED_UNICODE));
                    }
                }
            }
        }

        $historial[] = [
            'id'          => (string)$doc['_id'],
            'id_usuario'  => $idUsuarioStr,
            'email'       => $doc['email'] ?? 'Sin correo',
            'rol'         => $doc['rol'] ?? 'usuario',
            'accion'      => $doc['accion'] ?? 'Evento',
            'ip'          => $doc['ip'] ?? '127.0.0.1',
            'dispositivo' => $doc['dispositivo'] ?? 'Desconocido',
            'detalles'    => $detallesArray,
            'fecha'       => $fechaStr
        ];
    }
} catch (Exception $e) {
    error_log("Error en historial_usuarios.php: " . $e->getMessage());
}

/**
 * Convierte un User-Agent técnico a una representación amigable y legible
 */
function parseDispositivoLegible($userAgent) {
    if (empty($userAgent) || $userAgent === 'Desconocido') {
        return ['icono' => 'fas fa-question-circle', 'nombre' => 'Desconocido'];
    }

    $so = 'Desconocido';
    $icono = 'fas fa-desktop';
    if (stripos($userAgent, 'Windows NT 10.0') !== false || stripos($userAgent, 'Windows NT 11.0') !== false) {
        $so = 'Windows 10/11';
        $icono = 'fab fa-windows';
    } elseif (stripos($userAgent, 'Windows') !== false) {
        $so = 'Windows';
        $icono = 'fab fa-windows';
    } elseif (stripos($userAgent, 'Android') !== false) {
        $so = 'Android';
        $icono = 'fab fa-android';
    } elseif (stripos($userAgent, 'iPhone') !== false || stripos($userAgent, 'iPad') !== false) {
        $so = 'iOS';
        $icono = 'fab fa-apple';
    } elseif (stripos($userAgent, 'Mac OS') !== false || stripos($userAgent, 'Macintosh') !== false) {
        $so = 'macOS';
        $icono = 'fab fa-apple';
    } elseif (stripos($userAgent, 'Linux') !== false) {
        $so = 'Linux';
        $icono = 'fab fa-linux';
    }

    $nav = '';
    if (stripos($userAgent, 'Edg') !== false) {
        $nav = 'Edge';
    } elseif (stripos($userAgent, 'OPR') !== false || stripos($userAgent, 'Opera') !== false) {
        $nav = 'Opera';
    } elseif (stripos($userAgent, 'Chrome') !== false) {
        $nav = 'Chrome';
    } elseif (stripos($userAgent, 'Firefox') !== false) {
        $nav = 'Firefox';
    } elseif (stripos($userAgent, 'Safari') !== false) {
        $nav = 'Safari';
    }

    $resumen = $nav ? "$so • $nav" : $so;
    return ['icono' => $icono, 'nombre' => $resumen];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Usuarios - Snackver Admin</title>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-admin.css?v=2.3">
    <link rel="icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="../img/logo%20-%20snackver.png?v=2.2">
    <style>
        .badge-accion-login { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-accion-registro { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .badge-accion-otp { background-color: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }
        .badge-accion-logout { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .badge-accion-delete { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-accion-default { background-color: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; }
        .badge-rol-admin { background-color: #fef3c7; color: #92400e; font-weight: 700; border: 1px solid #fde68a; }
        .badge-rol-cliente { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .badge-rol-vendedor { background-color: #fef08a; color: #854d0e; border: 1px solid #fef08a; }

        /* Contención estricta del Grid para evitar desbordamiento horizontal en cualquier pantalla */
        .dashboard-grid {
            align-items: start;
        }
        .dashboard-grid > *,
        .dashboard-card,
        .dashboard-content {
            min-width: 0;
            max-width: 100%;
        }

        .ip-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 0.85em;
            color: #334155;
            white-space: nowrap;
        }

        .device-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.85em;
            color: #475569;
            white-space: nowrap;
            cursor: help;
        }
        .device-pill i {
            color: #1A4F8B;
        }

        .filtros-historial {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .filtros-historial .filtro-busqueda {
            flex: 2 1 240px;
            min-width: 200px;
            position: relative;
        }
        .filtros-historial .filtro-select {
            flex: 1 1 160px;
            min-width: 150px;
        }

        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
            max-width: 100%;
            border-radius: 8px;
            background: #fff;
        }

        #tablaHistorial {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
        }

        #tablaHistorial th, #tablaHistorial td {
            vertical-align: middle;
            padding: 12px 14px;
        }

        .celda-email {
            font-weight: 600;
            color: #1e293b;
            word-break: break-word;
        }

        /* Adaptación para Tablets y Laptops compactas */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .dashboard-sidebar {
                margin-bottom: 0;
                padding: 14px;
            }
            .sidebar-nav {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
                gap: 8px;
            }
            .sidebar-nav a {
                margin-bottom: 0;
                padding: 10px 12px;
                font-size: 0.92rem;
            }
        }

        /* Adaptación para Tablets pequeñas y Móviles */
        @media (max-width: 768px) {
            .dashboard-main-content {
                padding: 20px 0;
            }
            .container {
                padding: 0 14px;
            }
            .welcome-header {
                margin-bottom: 16px;
            }
            .welcome-header h1 {
                font-size: clamp(1.45rem, 5vw, 1.9rem);
            }
            .dashboard-card {
                padding: 18px 14px;
            }
            .filtros-historial {
                flex-direction: column;
                align-items: stretch;
                padding: 12px;
                gap: 10px;
            }
            .filtros-historial > div {
                width: 100% !important;
                min-width: 100% !important;
                flex: none !important;
            }
        }

        /* Transformación nativa de Tabla a Tarjetas en Pantallas Móviles (0 scroll horizontal) */
        @media (max-width: 680px) {
            .sidebar-nav {
                grid-template-columns: repeat(2, 1fr);
            }
            .table-container {
                overflow-x: visible;
                background: transparent;
            }
            #tablaHistorial {
                min-width: 100%;
                display: block;
                background: transparent;
                box-shadow: none;
                margin-top: 0;
            }
            #tablaHistorial thead {
                display: none;
            }
            #tablaHistorial tbody {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }
            #tablaHistorial tr.fila-historial {
                display: block;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 12px 14px;
                box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            }
            #tablaHistorial tr.fila-historial[style*="none"] {
                display: none !important;
            }
            #tablaHistorial tr.fila-historial td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                padding: 8px 0;
                border-bottom: 1px solid #f1f5f9;
                text-align: right !important;
                white-space: normal !important;
            }
            #tablaHistorial tr.fila-historial td:last-child {
                border-bottom: none;
                padding-bottom: 2px;
            }
            #tablaHistorial tr.fila-historial td::before {
                content: attr(data-label);
                font-weight: 600;
                font-size: 0.82rem;
                color: #64748b;
                text-align: left;
                flex-shrink: 0;
            }
            .celda-fecha {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
                justify-content: flex-end;
            }
            .badge, .ip-tag, .device-pill {
                white-space: normal;
                text-align: right;
            }
            #sinHistorialInicial td,
            #filaSinCoincidencias td {
                display: block;
                width: 100%;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
            }
        }

        @media (max-width: 420px) {
            .sidebar-nav {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <h1>Auditoría y Seguridad</h1>
            </div>

            <div class="dashboard-grid">
                <aside class="dashboard-sidebar">
                    <nav class="sidebar-nav">
                        <a href="../admin/dashboard_admin.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../admin/clientes.php"><i class="fas fa-users"></i> Clientes </a>
                        <a href="../admin/vendedores.php"><i class="fas fa-store"></i> Vendedores </a>
                        <a href="../admin/categorias.php"><i class="fas fa-tags"></i> Categorías </a>
                        <a href="../admin/historial_usuarios.php" class="active"><i class="fas fa-history"></i> Historial de Usuarios</a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-card">
                    <div class="dashboard-content">

                        <div style="margin-bottom:20px;">
                            <h2 style="margin-bottom:4px;">Historial de Actividades</h2>
                            <p style="color:#64748b; font-size:0.95em;">Registro cronológico y bitácora de eventos de usuarios en la plataforma.</p>
                        </div>

                        <!-- Barra de Búsqueda y Filtros en Tiempo Real -->
                        <div class="filtros-historial">
                            <div class="filtro-busqueda">
                                <i class="fas fa-search" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                                <input type="text" id="busquedaInput" placeholder="Buscar por usuario, correo, acción o IP..." 
                                       style="width:100%; padding:9px 12px 9px 36px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.95em; outline:none;"
                                       oninput="filtrarHistorial()" onkeyup="filtrarHistorial()">
                            </div>
                            <div class="filtro-select">
                                <select id="filtroRol" onchange="filtrarHistorial()" 
                                        style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.95em; outline:none; background:#ffffff;">
                                    <option value="todos">Todos los roles</option>
                                    <option value="admin">Administrador</option>
                                    <option value="cliente">Cliente</option>
                                    <option value="vendedor">Vendedor</option>
                                </select>
                            </div>
                            <div class="filtro-select">
                                <select id="filtroAccion" onchange="filtrarHistorial()" 
                                        style="width:100%; padding:9px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.95em; outline:none; background:#ffffff;">
                                    <option value="todos">Todas las acciones</option>
                                    <option value="Inicio de sesión exitoso">Inicio de sesión</option>
                                    <option value="Registro de nueva cuenta">Registro de cuenta</option>
                                    <option value="Verificación 2FA completada">Verificación 2FA</option>
                                    <option value="Cierre de sesión">Cierre de sesión</option>
                                    <option value="Usuario eliminado">Usuario eliminado</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-container">
                            <table id="tablaHistorial">
                                <thead>
                                    <tr>
                                        <th>Fecha y Hora</th>
                                        <th>Usuario / Correo</th>
                                        <th style="text-align:center;">Rol</th>
                                        <th>Acción Realizada</th>
                                        <th style="text-align:center;">Dirección IP</th>
                                        <th>Dispositivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($historial)): ?>
                                        <?php foreach ($historial as $row):
                                            $email = htmlspecialchars($row['email']);
                                            $idUsuario = htmlspecialchars($row['id_usuario']);
                                            $rol = htmlspecialchars($row['rol']);
                                            $accion = htmlspecialchars($row['accion']);
                                            $ip = htmlspecialchars($row['ip']);
                                            $dispositivo = htmlspecialchars($row['dispositivo']);
                                            $fecha = htmlspecialchars($row['fecha']);
                                            $deviceInfo = parseDispositivoLegible($dispositivo);

                                            // Determinar clase de badge según la acción
                                            $badgeClase = 'badge-accion-default';
                                            $iconoAccion = 'fa-info-circle';
                                            if (stripos($accion, 'Inicio de sesión') !== false) {
                                                $badgeClase = 'badge-accion-login';
                                                $iconoAccion = 'fa-sign-in-alt';
                                            } elseif (stripos($accion, 'Registro') !== false) {
                                                $badgeClase = 'badge-accion-registro';
                                                $iconoAccion = 'fa-user-plus';
                                            } elseif (stripos($accion, '2FA') !== false) {
                                                $badgeClase = 'badge-accion-otp';
                                                $iconoAccion = 'fa-shield-alt';
                                            } elseif (stripos($accion, 'Cierre de sesión') !== false) {
                                                $badgeClase = 'badge-accion-logout';
                                                $iconoAccion = 'fa-sign-out-alt';
                                            } elseif (stripos($accion, 'eliminado') !== false) {
                                                $badgeClase = 'badge-accion-delete';
                                                $iconoAccion = 'fa-trash-alt';
                                            }

                                            // Determinar clase de rol
                                            $badgeRolClase = 'badge-rol-cliente';
                                            if ($rol === 'admin') $badgeRolClase = 'badge-rol-admin';
                                            elseif ($rol === 'vendedor') $badgeRolClase = 'badge-rol-vendedor';
                                        ?>
                                            <tr class="fila-historial"
                                                data-email="<?php echo htmlspecialchars(mb_strtolower($email, 'UTF-8')); ?>"
                                                data-id="<?php echo htmlspecialchars(mb_strtolower($idUsuario, 'UTF-8')); ?>"
                                                data-rol="<?php echo htmlspecialchars(mb_strtolower($rol, 'UTF-8')); ?>"
                                                data-accion="<?php echo htmlspecialchars($row['accion']); ?>"
                                                data-ip="<?php echo htmlspecialchars(mb_strtolower($ip, 'UTF-8')); ?>"
                                                data-dispositivo="<?php echo htmlspecialchars(mb_strtolower($deviceInfo['nombre'], 'UTF-8')); ?>">
                                                <td data-label="Fecha y Hora" style="font-size:0.88em; color:#475569; white-space:nowrap;">
                                                    <div class="celda-fecha">
                                                        <div style="font-weight:600; color:#1e293b;"><i class="far fa-calendar-alt" style="color:#94a3b8; margin-right:4px;"></i><?php echo substr($fecha, 0, 10); ?></div>
                                                        <div style="font-size:0.85em; color:#64748b;"><i class="far fa-clock" style="color:#94a3b8; margin-right:4px;"></i><?php echo substr($fecha, 11); ?></div>
                                                    </div>
                                                </td>
                                                <td data-label="Usuario / Correo">
                                                    <div class="celda-email"><?php echo $email; ?></div>
                                                </td>
                                                <td data-label="Rol" style="text-align:center; white-space:nowrap;">
                                                    <span class="badge <?php echo $badgeRolClase; ?>">
                                                        <?php echo ucfirst($rol); ?>
                                                    </span>
                                                </td>
                                                <td data-label="Acción Realizada" style="white-space:nowrap;">
                                                    <span class="badge <?php echo $badgeClase; ?>">
                                                        <i class="fas <?php echo $iconoAccion; ?>" style="margin-right:4px;"></i> <?php echo $accion; ?>
                                                    </span>
                                                </td>
                                                <td data-label="Dirección IP" style="text-align:center; white-space:nowrap;">
                                                    <span class="ip-tag">
                                                        <i class="fas fa-network-wired" style="color:#94a3b8;"></i>
                                                        <span><?php echo ($ip === '::1') ? '127.0.0.1 (Local)' : $ip; ?></span>
                                                    </span>
                                                </td>
                                                <td data-label="Dispositivo" title="<?php echo htmlspecialchars($dispositivo); ?>">
                                                    <span class="device-pill">
                                                        <i class="<?php echo $deviceInfo['icono']; ?>"></i>
                                                        <span><?php echo htmlspecialchars($deviceInfo['nombre']); ?></span>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <tr id="sinHistorialInicial" style="<?php echo empty($historial) ? '' : 'display:none;'; ?>">
                                        <td colspan="6" style="text-align:center; padding: 30px; color:#64748b;">
                                            <i class="fas fa-history" style="font-size:2em; margin-bottom:10px; display:block; color:#cbd5e1;"></i>
                                            Aún no hay actividades registradas en la bitácora de auditoría.
                                        </td>
                                    </tr>
                                    <tr id="filaSinCoincidencias" style="display:none;">
                                        <td colspan="6" style="text-align:center; padding: 25px; color:#64748b;">
                                            <i class="fas fa-search" style="font-size:1.5em; margin-bottom:8px; display:block;"></i>
                                            No se encontraron actividades que coincidan con la búsqueda o filtros seleccionados.
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
        function filtrarHistorial() {
            const query = document.getElementById('busquedaInput').value.toLowerCase().trim();
            const rolFiltro = document.getElementById('filtroRol').value.toLowerCase();
            const accionFiltro = document.getElementById('filtroAccion').value;
            const filas = document.querySelectorAll('.fila-historial');
            let visibles = 0;

            filas.forEach(fila => {
                const email = fila.getAttribute('data-email') || '';
                const idUser = fila.getAttribute('data-id') || '';
                const rol = fila.getAttribute('data-rol') || '';
                const accion = fila.getAttribute('data-accion') || '';
                const ip = fila.getAttribute('data-ip') || '';
                const dispositivo = fila.getAttribute('data-dispositivo') || '';

                const coincideTexto = !query || email.includes(query) || idUser.includes(query) || accion.toLowerCase().includes(query) || ip.includes(query) || dispositivo.includes(query);
                const coincideRol = (rolFiltro === 'todos') || (rol === rolFiltro);
                const coincideAccion = (accionFiltro === 'todos') || (accion === accionFiltro);

                if (coincideTexto && coincideRol && coincideAccion) {
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

            const sinInicial = document.getElementById('sinHistorialInicial');
            if (sinInicial) {
                sinInicial.style.display = (filas.length === 0) ? '' : 'none';
            }
        }
    </script>
    <script src="../../Front-end/js/tiempo_real.js?v=2.3"></script>

</body>

</html>
