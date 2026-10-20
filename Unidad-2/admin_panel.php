<?php
require_once __DIR__ . '/config/helpers.php';
configureSession();
session_start();

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// Verificar sesión y rol admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance();
$adminObjId = new ObjectId($_SESSION['user_id']);

// ============================================
// PROCESAR ACCIONES (APROBAR / RECHAZAR)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $gameObjId = toObjectId($_POST['game_id'] ?? '');
    
    if ($gameObjId) {
        if ($action === 'approve') {
            $db->getCollection('juegos')->updateOne(
                ['_id' => $gameObjId, 'estado' => 'pendiente'],
                ['$set' => [
                    'estado' => 'aprobado',
                    'fecha_aprobacion' => new UTCDateTime(),
                    'aprobado_por' => $adminObjId,
                ]]
            );
            auditLog('game_approved', ['game_id' => (string)$gameObjId], (string)$adminObjId);
        } elseif ($action === 'reject') {
            $db->getCollection('juegos')->updateOne(
                ['_id' => $gameObjId, 'estado' => 'pendiente'],
                ['$set' => [
                    'estado' => 'rechazado',
                    'fecha_rechazo' => new UTCDateTime(),
                    'rechazado_por' => $adminObjId,
                ]]
            );
            auditLog('game_rejected', ['game_id' => (string)$gameObjId], (string)$adminObjId);
        }
    }
    
    // PRG: redirigir para evitar reenvío del formulario
    header('Location: admin_panel.php');
    exit();
}

// ============================================
// JUEGOS PENDIENTES DE REVISIÓN
// ============================================
$pendingGames = [];
try {
    $cursor = $db->getCollection('juegos')->find(
        ['estado' => 'pendiente'],
        ['sort' => ['fecha_actualizacion' => -1, 'fecha_agregado' => -1]]
    );
    
    foreach ($cursor as $doc) {
        $game = (array)$doc;
        $game['id'] = (string)$doc['_id'];
        $game['desarrollador'] = $game['desarrollador_nombre'] ?? 'Desconocido';
        unset($game['_id']);
        $pendingGames[] = bsonToArray($game);
    }
} catch (Exception $e) {
    error_log("Error obteniendo pendientes: " . $e->getMessage());
}

// ============================================
// ESTADÍSTICAS BÁSICAS
// ============================================
$stats = [
    'total_ventas' => 0,
    'ingresos_totales' => 0,
    'comisiones_totales' => 0,
];

try {
    $agg = $db->getCollection('ordenes')->aggregate([
        ['$unwind' => '$items'],
        ['$group' => [
            '_id' => null,
            'total_ventas' => ['$sum' => 1],
            'ingresos_totales' => ['$sum' => '$items.precio_final'],
            'comisiones_totales' => ['$sum' => '$items.comision'],
        ]],
    ])->toArray();
    
    if (!empty($agg)) {
        $stats['total_ventas'] = (int)$agg[0]['total_ventas'];
        $stats['ingresos_totales'] = round((float)$agg[0]['ingresos_totales'], 2);
        $stats['comisiones_totales'] = round((float)$agg[0]['comisiones_totales'], 2);
    }
} catch (Exception $e) {
    error_log("Error obteniendo stats: " . $e->getMessage());
}

// ============================================
// USUARIOS POR ROL
// ============================================
$usersByRole = [];
try {
    $agg = $db->getCollection('usuarios')->aggregate([
        ['$group' => ['_id' => '$rol', 'cantidad' => ['$sum' => 1]]],
        ['$sort' => ['cantidad' => -1]],
    ])->toArray();
    
    foreach ($agg as $item) {
        $usersByRole[] = [
            'rol' => $item['_id'],
            'cantidad' => (int)$item['cantidad'],
        ];
    }
} catch (Exception $e) {
    error_log("Error obteniendo usuarios por rol: " . $e->getMessage());
}

// Total de usuarios (para la card)
$totalUsers = array_sum(array_column($usersByRole, 'cantidad'));

// ============================================
// JUEGOS POR CATEGORÍA
// ============================================
$gamesByCategory = [];
try {
    $agg = $db->getCollection('juegos')->aggregate([
        ['$match' => ['estado' => 'aprobado']],
        ['$group' => ['_id' => '$categoria', 'cantidad' => ['$sum' => 1]]],
        ['$sort' => ['cantidad' => -1]],
    ])->toArray();
    
    foreach ($agg as $item) {
        $gamesByCategory[] = [
            'categoria' => $item['_id'] ?? 'Sin categoría',
            'cantidad' => (int)$item['cantidad'],
        ];
    }
} catch (Exception $e) {
    error_log("Error obteniendo juegos por categoría: " . $e->getMessage());
}

// ============================================
// JUEGOS POR ESTADO
// ============================================
$gamesByStatus = [];
try {
    $agg = $db->getCollection('juegos')->aggregate([
        ['$group' => ['_id' => '$estado', 'cantidad' => ['$sum' => 1]]],
        ['$sort' => ['cantidad' => -1]],
    ])->toArray();
    
    foreach ($agg as $item) {
        $gamesByStatus[] = [
            'estado' => $item['_id'] ?? 'sin_estado',
            'cantidad' => (int)$item['cantidad'],
        ];
    }
} catch (Exception $e) {
    error_log("Error obteniendo juegos por estado: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - TechnoGames</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-container {
            padding: 20px 5%;
            max-width: 1400px;
            margin: 0 auto;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid var(--accent-primary);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        .stat-card h3 {
            margin-bottom: 15px;
            color: var(--text-secondary);
            font-size: 16px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        .stat-subtext {
            font-size: 14px;
            color: var(--text-secondary);
        }
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        @media (max-width: 1100px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        .chart-container {
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        .chart-container h3 {
            margin-bottom: 20px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
        }
        .distribution-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .distribution-card {
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        .distribution-list {
            margin-top: 15px;
        }
        .distribution-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .distribution-item:last-child {
            border-bottom: none;
        }
        .distribution-label {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .distribution-count {
            background-color: var(--accent-primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .pending-games {
            margin-top: 40px;
        }
        .game-review {
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            border-left: 4px solid #ff9800;
        }
        .game-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .btn-approve {
            background: linear-gradient(135deg, #00CC66, #00a352);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 204, 102, 0.3);
        }
        .btn-reject {
            background: linear-gradient(135deg, #FF3333, #cc2929);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 51, 51, 0.3);
        }
        .no-pending {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
            font-size: 18px;
        }
        .no-pending i {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--accent-primary);
        }
        .stat-icon {
            font-size: 24px;
            color: var(--accent-primary);
        }
        .role-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        .role-admin { background-color: #FF3333; color: white; }
        .role-dev { background-color: #0074E4; color: white; }
        .role-user { background-color: #00CC66; color: white; }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <i class="fas fa-gamepad"></i>
            <span>TechnoGames</span>
        </div>
        <div class="user-actions">
            <button onclick="window.location.href='index.php'">
                <i class="fas fa-store"></i> Volver a Tienda
            </button>
            <button id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </button>
        </div>
    </header>

    <div class="admin-container">
        <h1 style="display: flex; align-items: center; gap: 15px; margin-bottom: 30px;">
            <i class="fas fa-cog" style="color: var(--accent-primary);"></i> 
            Panel de Administración
        </h1>
        
        <!-- Estadísticas principales -->
        <h2 style="margin-bottom: 20px; color: var(--text-primary);">
            <i class="fas fa-chart-bar"></i> Resumen General
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-shopping-cart stat-icon"></i> Ventas Totales</h3>
                <div class="stat-value"><?php echo $stats['total_ventas'] ?? 0; ?></div>
                <div class="stat-subtext">Transacciones completadas</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-money-bill-wave stat-icon"></i> Ingresos Totales</h3>
                <div class="stat-value">$<?php echo number_format($stats['ingresos_totales'] ?? 0, 2); ?></div>
                <div class="stat-subtext">Ingresos brutos</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-percentage stat-icon"></i> Comisiones</h3>
                <div class="stat-value">$<?php echo number_format($stats['comisiones_totales'] ?? 0, 2); ?></div>
                <div class="stat-subtext">30% por venta</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-users stat-icon"></i> Usuarios Totales</h3>
                <div class="stat-value">
                    <?php 
                        $totalUsers = 0;
                        foreach ($usersByRole as $role) {
                            $totalUsers += $role['cantidad'];
                        }
                        echo $totalUsers;
                    ?>
                </div>
                <div class="stat-subtext">Registrados en la plataforma</div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="charts-grid">
            <div class="chart-container">
                <h3><i class="fas fa-chart-pie"></i> Distribución de Usuarios</h3>
                <canvas id="usersChart" height="250"></canvas>
            </div>
            <div class="chart-container">
                <h3><i class="fas fa-chart-bar"></i> Juegos por Categoría</h3>
                <canvas id="categoriesChart" height="250"></canvas>
            </div>
        </div>

        <!-- Distribuciones -->
        <div class="distribution-grid">
            <div class="distribution-card">
                <h3><i class="fas fa-user-tag"></i> Usuarios por Rol</h3>
                <div class="distribution-list">
                    <?php foreach ($usersByRole as $role): ?>
                    <div class="distribution-item">
                        <div class="distribution-label">
                            <span class="role-icon role-<?php echo $role['rol']; ?>">
                                <?php echo strtoupper(substr($role['rol'], 0, 1)); ?>
                            </span>
                            <span><?php echo ucfirst($role['rol']); ?></span>
                        </div>
                        <span class="distribution-count"><?php echo $role['cantidad']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="distribution-card">
                <h3><i class="fas fa-gamepad"></i> Juegos por Estado</h3>
                <div class="distribution-list">
                    <?php foreach ($gamesByStatus as $status): ?>
                    <div class="distribution-item">
                        <div class="distribution-label">
                            <i class="fas fa-circle" style="color: 
                                <?php 
                                    switch($status['estado']) {
                                        case 'aprobado': echo '#00CC66'; break;
                                        case 'pendiente': echo '#FF9800'; break;
                                        case 'rechazado': echo '#FF3333'; break;
                                        default: echo '#8f98a0';
                                    }
                                ?>;">
                            </i>
                            <span><?php echo ucfirst($status['estado']); ?></span>
                        </div>
                        <span class="distribution-count"><?php echo $status['cantidad']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Juegos Pendientes -->
        <div class="pending-games">
            <h2 style="display: flex; align-items: center; gap: 10px; margin-bottom: 25px;">
                <i class="fas fa-clock" style="color: #FF9800;"></i> 
                Juegos Pendientes de Revisión
                <span style="background-color: #FF9800; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: 500;">
                    <?php echo count($pendingGames); ?>
                </span>
            </h2>
            
            <?php if (count($pendingGames) > 0): ?>
                <?php foreach ($pendingGames as $game): ?>
                    <div class="game-review">
                        <h3 style="margin-bottom: 10px; color: var(--text-primary);"><?php echo htmlspecialchars($game['titulo']); ?></h3>
                        <p style="margin-bottom: 8px; color: var(--text-secondary);">
                            <strong>Desarrollador:</strong> <?php echo htmlspecialchars($game['desarrollador']); ?>
                        </p>
                        <p style="margin-bottom: 8px; color: var(--text-secondary);">
                            <strong>Categoría:</strong> <?php echo htmlspecialchars($game['categoria']); ?>
                        </p>
                        <p style="margin-bottom: 8px; color: var(--text-secondary);">
                            <strong>Precio:</strong> $<?php echo number_format($game['precio'], 2); ?>
                        </p>
                        <p style="margin-bottom: 15px; color: var(--text-secondary);">
                            <strong>Descripción:</strong> <?php echo htmlspecialchars(substr($game['descripcion'], 0, 200)) . '...'; ?>
                        </p>
                        
                        <div class="game-actions">
                            <form method="POST">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                <button type="submit" class="btn-approve">
                                    <i class="fas fa-check"></i> Aprobar
                                </button>
                            </form>
                            <form method="POST">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="game_id" value="<?php echo $game['id']; ?>">
                                <button type="submit" class="btn-reject">
                                    <i class="fas fa-times"></i> Rechazar
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-pending">
                    <i class="fas fa-check-circle"></i>
                    <p>No hay juegos pendientes de revisión.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', () => {
            window.location.href = 'index.php?logout=true';
        });

        // Gráfico de usuarios por rol
        const usersCtx = document.getElementById('usersChart').getContext('2d');
        const usersChart = new Chart(usersCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php foreach ($usersByRole as $role): ?>
                        '<?php echo ucfirst($role['rol']); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($usersByRole as $role): ?>
                            <?php echo $role['cantidad']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        '#FF3333', // admin
                        '#0074E4', // dev
                        '#00CC66'  // user
                    ],
                    borderWidth: 2,
                    borderColor: '#1E1E1E'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#FFFFFF',
                            padding: 20,
                            font: {
                                size: 14
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de juegos por categoría
        const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
        const categoriesChart = new Chart(categoriesCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($gamesByCategory as $cat): ?>
                        '<?php echo htmlspecialchars($cat['categoria']); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Juegos',
                    data: [
                        <?php foreach ($gamesByCategory as $cat): ?>
                            <?php echo $cat['cantidad']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: '#0074E4',
                    borderColor: '#0066CC',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#B3B3B3'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: '#B3B3B3'
                        }
                    }
                }
            }
        });

        // Actualizar estadísticas cada 60 segundos
        function updateStats() {
            fetch('process.php?action=get_admin_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Estadísticas actualizadas:', data.stats);
                    }
                });
        }
        
        
        
        // setInterval(updateStats, 60000); // Descomentar para actualización automática
    </script>
</body>
</html>
