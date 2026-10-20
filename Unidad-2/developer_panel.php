<?php
require_once __DIR__ . '/config/helpers.php';
configureSession();
session_start();

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// ============================================
// MENSAJES DE SESIÓN
// ============================================
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// ============================================
// VERIFICAR SESIÓN Y ROL
// ============================================
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'dev') {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance();
$devObjId = new ObjectId($_SESSION['user_id']);

// ============================================
// PROCESAR ENVÍO / EDICIÓN DE JUEGO
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_game'])) {
    $titulo = clean($_POST['titulo'] ?? '');
    $descripcion = clean($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $descuento = (int)($_POST['descuento'] ?? 0);
    $categoria = clean($_POST['categoria'] ?? '');
    $imagen_url = clean($_POST['imagen_url'] ?? '');
    $requisitos = clean($_POST['requisitos'] ?? '');
    $game_id_from_post = clean($_POST['game_id'] ?? '');
    
    $descuento = max(0, min(100, $descuento));
    
    // Validar campos obligatorios
    if (empty($titulo) || empty($descripcion) || empty($categoria) || $precio <= 0) {
        $_SESSION['error_message'] = "Por favor, complete todos los campos obligatorios correctamente";
        header('Location: developer_panel.php');
        exit();
    }
    
    // Parsear requisitos (texto → objeto)
    $requisitosObj = parseRequisitosDev($requisitos);
    
    $precioFinal = $descuento > 0 ? round($precio * (1 - $descuento / 100), 2) : $precio;
    
    $juegos = $db->getCollection('juegos');
    
    // MODO: UPDATE
    if (!empty($game_id_from_post)) {
        $gameObjId = toObjectId($game_id_from_post);
        
        if (!$gameObjId) {
            $_SESSION['error_message'] = "ID de juego no válido";
            header('Location: developer_panel.php');
            exit();
        }
        
        // Verificar que el juego le pertenezca
        $existing = $juegos->findOne([
            '_id' => $gameObjId,
            'desarrollador_id' => $devObjId,
        ]);
        
        if (!$existing) {
            $_SESSION['error_message'] = "No tienes permiso para editar este juego";
            header('Location: developer_panel.php');
            exit();
        }
        
        // Actualizar y volver a estado pendiente
        $juegos->updateOne(
            ['_id' => $gameObjId],
            ['$set' => [
                'titulo' => $titulo,
                'descripcion' => $descripcion,
                'precio' => $precio,
                'descuento' => $descuento,
                'precio_final' => $precioFinal,
                'categoria' => $categoria,
                'imagen_url' => $imagen_url,
                'requisitos' => $requisitosObj,
                'estado' => 'pendiente',
                'fecha_actualizacion' => new UTCDateTime(),
                'tags' => extractTagsDev($titulo, $categoria),
            ]]
        );
        
        auditLog('game_updated', ['game_id' => $game_id_from_post], (string)$devObjId);
        $_SESSION['success_message'] = "Juego actualizado. Debe ser aprobado nuevamente.";
        
    } else {
        // MODO: INSERT
        // Verificar duplicado
        $duplicate = $juegos->findOne([
            'titulo' => $titulo,
            'desarrollador_id' => $devObjId,
        ]);
        
        if ($duplicate) {
            $_SESSION['error_message'] = "Ya tienes un juego con el título '$titulo'";
            header('Location: developer_panel.php');
            exit();
        }
        
        // Insertar nuevo juego
        $juegos->insertOne([
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'descuento' => $descuento,
            'precio_final' => $precioFinal,
            'imagen_url' => $imagen_url,
            'categoria' => $categoria,
            'requisitos' => $requisitosObj,
            'estado' => 'pendiente',
            'desarrollador_id' => $devObjId,
            'desarrollador_nombre' => $_SESSION['user_name'],
            'rating_promedio' => 0,
            'total_resenas' => 0,
            'total_ventas' => 0,
            'fecha_agregado' => new UTCDateTime(),
            'tags' => extractTagsDev($titulo, $categoria),
        ]);
        
        auditLog('game_submitted', ['titulo' => $titulo], (string)$devObjId);
        $_SESSION['success_message'] = "Juego enviado para revisión exitosamente.";
    }
    
    header('Location: developer_panel.php');
    exit();
}

// ============================================
// OBTENER MIS JUEGOS
// ============================================
$myGames = [];
try {
    $cursor = $db->getCollection('juegos')->find(
        ['desarrollador_id' => $devObjId],
        ['sort' => ['fecha_agregado' => -1]]
    );
    
    foreach ($cursor as $doc) {
        $game = (array)$doc;
        $game['id'] = (string)$doc['_id'];
        unset($game['_id']);
        $myGames[] = bsonToArray($game);
    }
} catch (Exception $e) {
    error_log("Error obteniendo mis juegos: " . $e->getMessage());
}

// ============================================
// ESTADÍSTICAS DEL DESARROLLADOR
// ============================================
$stats = [
    'total_ventas' => 0,
    'ganancias' => 0,
    'promedio_ganancia' => 0,
];

try {
    $agg = $db->getCollection('ordenes')->aggregate([
        ['$unwind' => '$items'],
        ['$match' => ['items.desarrollador_id' => $devObjId]],
        ['$group' => [
            '_id' => null,
            'total_ventas' => ['$sum' => 1],
            'ganancias' => ['$sum' => ['$subtract' => ['$items.precio_final', '$items.comision']]],
            'promedio_ganancia' => ['$avg' => ['$subtract' => ['$items.precio_final', '$items.comision']]],
        ]],
    ])->toArray();
    
    if (!empty($agg)) {
        $stats['total_ventas'] = (int)$agg[0]['total_ventas'];
        $stats['ganancias'] = round((float)$agg[0]['ganancias'], 2);
        $stats['promedio_ganancia'] = round((float)$agg[0]['promedio_ganancia'], 2);
    }
} catch (Exception $e) {
    error_log("Error obteniendo stats dev: " . $e->getMessage());
}

// ============================================
// TOP 5 JUEGOS MÁS VENDIDOS
// ============================================
$topSoldGames = [];
try {
    $agg = $db->getCollection('ordenes')->aggregate([
        ['$unwind' => '$items'],
        ['$match' => ['items.desarrollador_id' => $devObjId]],
        ['$group' => [
            '_id' => '$items.juego_id',
            'titulo' => ['$first' => '$items.titulo'],
            'ventas' => ['$sum' => 1],
            'ganancias' => ['$sum' => ['$subtract' => ['$items.precio_final', '$items.comision']]],
        ]],
        ['$sort' => ['ventas' => -1]],
        ['$limit' => 5],
    ])->toArray();
    
    foreach ($agg as $item) {
        $topSoldGames[] = [
            'titulo' => $item['titulo'],
            'ventas' => (int)$item['ventas'],
            'ganancias' => round((float)$item['ganancias'], 2),
        ];
    }
} catch (Exception $e) {
    error_log("Error obteniendo top vendidos: " . $e->getMessage());
}

// ============================================
// VENTAS POR MES (ÚLTIMOS 6 MESES)
// ============================================
$salesByMonth = [];
try {
    $sixMonthsAgo = new UTCDateTime((time() - 6 * 30 * 86400) * 1000);
    
    $agg = $db->getCollection('ordenes')->aggregate([
        ['$match' => ['fecha_compra' => ['$gte' => $sixMonthsAgo]]],
        ['$unwind' => '$items'],
        ['$match' => ['items.desarrollador_id' => $devObjId]],
        ['$group' => [
            '_id' => ['$dateToString' => ['format' => '%Y-%m', 'date' => '$fecha_compra']],
            'ventas' => ['$sum' => 1],
            'ganancias' => ['$sum' => ['$subtract' => ['$items.precio_final', '$items.comision']]],
        ]],
        ['$sort' => ['_id' => 1]],
    ])->toArray();
    
    foreach ($agg as $item) {
        $salesByMonth[] = [
            'mes' => $item['_id'],
            'ventas' => (int)$item['ventas'],
            'ganancias' => round((float)$item['ganancias'], 2),
        ];
    }
} catch (Exception $e) {
    error_log("Error obteniendo ventas por mes: " . $e->getMessage());
}

// ============================================
// HELPERS LOCALES
// ============================================
function parseRequisitosDev(string $texto): array {
    if (empty($texto)) return [];
    
    $requisitos = [];
    $lines = explode("\n", $texto);
    $mapping = [
        'so' => 'os', 'sistema operativo' => 'os',
        'procesador' => 'processor', 'cpu' => 'processor',
        'memoria' => 'memory', 'ram' => 'memory',
        'gráficos' => 'graphics', 'graficos' => 'graphics', 'gpu' => 'graphics',
        'almacenamiento' => 'storage', 'disco' => 'storage',
    ];
    
    foreach ($lines as $line) {
        if (strpos($line, ':') !== false) {
            list($key, $value) = explode(':', $line, 2);
            $key = strtolower(trim($key));
            foreach ($mapping as $pattern => $field) {
                if (strpos($key, $pattern) !== false) {
                    $requisitos[$field] = trim($value);
                    break;
                }
            }
        }
    }
    return $requisitos;
}

function extractTagsDev(string $titulo, string $categoria): array {
    $tags = [strtolower($categoria)];
    $keywords = ['rpg', 'fps', 'open world', 'multiplayer', 'single player', 'action', 'adventure', 'strategy', 'indie', 'aaa'];
    $tituloLower = strtolower($titulo);
    foreach ($keywords as $kw) {
        if (strpos($tituloLower, $kw) !== false) $tags[] = $kw;
    }
    return array_values(array_unique($tags));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Desarrollador - TechnoGames</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dev-container {
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
        .form-container {
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 40px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        .my-games {
            margin-top: 40px;
        }
        .game-item {
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-left: 4px solid;
        }
        .game-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        .game-item.pendiente { border-left-color: #FF9800; }
        .game-item.aprobado { border-left-color: #00CC66; }
        .game-item.rechazado { border-left-color: #FF3333; }
        .game-main-info {
            flex: 1;
        }
        .game-secondary-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }
        .game-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        .game-meta {
            display: flex;
            gap: 15px;
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 10px;
        }
        .game-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .game-stats {
            display: flex;
            gap: 15px;
            margin-top: 10px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        .stat-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .game-actions-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .edit-btn {
            background: linear-gradient(135deg, #FF9800, #f57c00);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .edit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }
        .delete-btn {
            background: linear-gradient(135deg, #FF3333, #cc2929);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .delete-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 51, 51, 0.3);
        }
        .view-details-btn {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .view-details-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }
        .notification {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .notification.show {
            display: block;
        }
        .notification.error {
            background-color: rgba(255, 51, 51, 0.2);
            color: #ff3333;
            border-left: 4px solid #ff3333;
        }
        .notification.success {
            background-color: rgba(0, 204, 102, 0.2);
            color: #00cc66;
            border-left: 4px solid #00cc66;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pendiente { background-color: #FF9800; color: black; }
        .status-aprobado { background-color: #00CC66; color: white; }
        .status-rechazado { background-color: #FF3333; color: white; }
        .stat-icon {
            font-size: 24px;
            color: var(--accent-primary);
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--accent-primary);
        }
        .game-details-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .game-details-content {
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .game-details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .game-details-body {
            line-height: 1.6;
        }
        .detail-section {
            margin-bottom: 20px;
        }
        .detail-section h4 {
            color: var(--accent-primary);
            margin-bottom: 8px;
            font-size: 16px;
        }
        .detail-section p {
            color: var(--text-secondary);
            background: rgba(255, 255, 255, 0.05);
            padding: 12px;
            border-radius: 6px;
            white-space: pre-wrap;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .modal-content {
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        /* Sección de gráficas */
        .charts-section {
            margin: 40px 0;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
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
        .top-games-list {
            margin-top: 15px;
        }
        .top-game-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .top-game-item:last-child {
            border-bottom: none;
        }
        .top-game-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .top-game-title {
            font-weight: 500;
            color: var(--text-primary);
        }
        .top-game-sales {
            font-size: 12px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .top-game-earnings {
            font-weight: 600;
            color: var(--accent-primary);
        }
        .no-charts {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }
        .no-charts i {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--accent-primary);
        }
        @media (max-width: 900px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Estilos para el control de descuento */
        .discount-container {
            margin-top: 5px;
        }
        
        .discount-display {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
        }
        
        #discountValue {
            font-weight: bold;
            color: var(--accent-primary);
            font-size: 16px;
        }
        
        .final-price {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .final-price.highlight {
            color: var(--success);
            font-weight: bold;
            font-size: 16px;
        }
        
        input[type="range"] {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            outline: none;
            -webkit-appearance: none;
        }
        
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--accent-primary);
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0, 116, 228, 0.4);
        }
        
        input[type="range"]::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--accent-primary);
            cursor: pointer;
            border: none;
        }
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

    <div class="dev-container">
        <h1 style="display: flex; align-items: center; gap: 15px; margin-bottom: 30px;">
            <i class="fas fa-code" style="color: var(--accent-primary);"></i> 
            Panel de Desarrollador
        </h1>
        
        <!-- Notificaciones -->
        <?php if (!empty($success_message)): ?>
            <div class="notification success show"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="notification error show"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Estadísticas principales -->
        <h2 style="margin-bottom: 20px; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-chart-line"></i> Mis Estadísticas
        </h2>
        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-shopping-cart stat-icon"></i> Ventas Totales</h3>
                <div class="stat-value"><?php echo $stats['total_ventas'] ?? 0; ?></div>
                <div class="stat-subtext">Juegos vendidos</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-money-bill-wave stat-icon"></i> Ganancias</h3>
                <div class="stat-value">$<?php echo number_format($stats['ganancias'] ?? 0, 2); ?></div>
                <div class="stat-subtext">Después de comisiones</div>
            </div>
            <div class="stat-card">
                <h3><i class="fas fa-gamepad stat-icon"></i> Juegos Publicados</h3>
                <div class="stat-value"><?php echo count($myGames); ?></div>
                <div class="stat-subtext">Total en plataforma</div>
            </div>
        </div>

        <!-- Sección de Gráficas -->
        <div class="charts-section">
            <h2 style="margin-bottom: 20px; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-chart-bar"></i> Análisis de Ventas
            </h2>
            
            <?php if (count($topSoldGames) > 0 || count($salesByMonth) > 0): ?>
                <div class="charts-grid">
                    <!-- Juegos más vendidos -->
                    <div class="chart-container">
                        <h3><i class="fas fa-trophy"></i> Juegos Más Vendidos</h3>
                        <?php if (count($topSoldGames) > 0): ?>
                            <div class="top-games-list">
                                <?php foreach ($topSoldGames as $index => $game): ?>
                                    <div class="top-game-item">
                                        <div class="top-game-info">
                                            <span class="top-game-title">
                                                <?php echo ($index + 1) . '. ' . htmlspecialchars($game['titulo']); ?>
                                            </span>
                                            <span class="top-game-sales">
                                                <i class="fas fa-shopping-cart"></i> 
                                                <?php echo $game['ventas']; ?> ventas
                                            </span>
                                        </div>
                                        <span class="top-game-earnings">
                                            $<?php echo number_format($game['ganancias'], 2); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-charts">
                                <i class="fas fa-chart-line"></i>
                                <p>No hay juegos vendidos aún</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Gráfico de ventas por mes -->
                    <div class="chart-container">
                        <h3><i class="fas fa-calendar-alt"></i> Ventas por Mes (Últimos 6 meses)</h3>
                        <?php if (count($salesByMonth) > 0): ?>
                            <canvas id="salesChart" height="250"></canvas>
                        <?php else: ?>
                            <div class="no-charts">
                                <i class="fas fa-calendar"></i>
                                <p>No hay ventas en los últimos 6 meses</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-charts">
                    <i class="fas fa-chart-line"></i>
                    <h3>No hay datos de ventas disponibles</h3>
                    <p>Cuando tengas ventas, aquí verás tus estadísticas.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Formulario para enviar/editar juego -->
        <div class="form-container" id="newGameForm">
            <h2 style="margin-bottom: 25px; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-upload"></i> Enviar Nuevo Juego
            </h2>
            
            <form method="POST" id="gameForm">
                <!-- CAMPO OCULTO CRÍTICO: game_id se llena cuando editamos -->
                 <input type="hidden" name="action" value="edit_game">
                <input type="hidden" name="game_id" id="editGameId" value="">
    
    <!-- Agregar un campo oculto para submit_game -->
                <input type="hidden" name="submit_game" value="1"> 
                      <div class="form-group">
        <label for="titulo">Título del Juego *</label>
        <input type="text" id="titulo" name="titulo" required placeholder="Ej: Cyberpunk 2077">
    </div>
                    <div class="form-group">
                        <label for="categoria">Categoría *</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Seleccionar categoría</option>
                            <option value="RPG">RPG</option>
                            <option value="FPS">FPS</option>
                            <option value="Aventura">Aventura</option>
                            <option value="Acción-Aventura">Acción-Aventura</option>
                            <option value="Estrategia">Estrategia</option>
                            <option value="Deportes">Deportes</option>
                            <option value="Simulación">Simulación</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="precio">Precio ($) *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0.01" required placeholder="0.00" oninput="calculateFinalPrice()">
                    </div>
                    <div class="form-group">
                        <label for="descuento">Descuento (%)</label>
                        <div class="discount-container">
                            <input type="range" id="descuento" name="descuento" min="0" max="100" step="5" value="0" oninput="updateDiscountValue(this.value)">
                            <div class="discount-display">
                                <span id="discountValue">0%</span>
                                <span id="finalPriceDisplay" class="final-price">Sin descuento aplicado</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="imagen_url">URL de la Imagen *</label>
                        <input type="url" id="imagen_url" name="imagen_url" required placeholder="https://ejemplo.com/imagen.jpg">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label for="descripcion">Descripción *</label>
                    <textarea id="descripcion" name="descripcion" rows="4" required placeholder="Describe tu juego..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="requisitos">Requisitos del Sistema *</label>
                    <textarea id="requisitos" name="requisitos" rows="4" required placeholder="Mínimo:
SO: Windows 10
Procesador: Intel Core i5
Memoria: 8 GB RAM
Gráficos: NVIDIA GTX 1060"></textarea>
                </div>
                
                 <div style="display: flex; gap: 15px; margin-top: 20px;">
        <button type="submit" class="form-submit" style="flex: 1; padding: 15px; font-size: 16px;">
            <i class="fas fa-paper-plane"></i> <span id="submitButtonText">Enviar para Revisión</span>
        </button>
        <button type="button" id="cancelEdit" class="form-submit" style="flex: 0.5; padding: 15px; font-size: 16px; background: #666; display: none;">
            <i class="fas fa-times"></i> Cancelar
        </button>
    </div>
</form>
        </div>

        <!-- Mis Juegos -->
        <div class="my-games">
            <h2 style="margin-bottom: 25px; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-list"></i> Mis Juegos (<?php echo count($myGames); ?>)
                <span style="font-size: 14px; color: var(--text-secondary); margin-left: auto;">
                    <i class="fas fa-info-circle"></i> Haz clic en un juego para ver detalles
                </span>
            </h2>
            
            <?php if (count($myGames) > 0): ?>
                <?php foreach ($myGames as $game): 
                    // Calcular precio final dentro del bucle
                    $precioFinal = $game['descuento'] > 0 ? 
                        $game['precio'] * (1 - $game['descuento'] / 100) : 
                        $game['precio'];
                ?>
                    <div class="game-item <?php echo $game['estado']; ?>">
                        <div class="game-main-info">
                            <div class="game-title"><?php echo htmlspecialchars($game['titulo']); ?></div>
                            <div class="game-meta">
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($game['categoria']); ?></span>
                                <span><i class="fas fa-dollar-sign"></i> 
                                    <?php if ($game['descuento'] > 0): ?>
                                        <!-- Precio con descuento -->
                                        <span style="text-decoration: line-through; color: var(--text-secondary);">
                                            $<?php echo number_format($game['precio'], 2); ?>
                                        </span>
                                        <span style="color: var(--accent-primary); font-weight: bold; margin-left: 5px;">
                                            $<?php echo number_format($precioFinal, 2); ?>
                                        </span>
                                        <span style="color: #FF4C4C; font-weight: bold; margin-left: 5px;">
                                            (-<?php echo $game['descuento']; ?>%)
                                        </span>
                                    <?php else: ?>
                                        <!-- Precio normal -->
                                        <span style="font-weight: bold;">
                                            $<?php echo number_format($game['precio'], 2); ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($game['fecha_agregado'])); ?></span>
                            </div>
                            <div class="game-stats">
                                <div class="stat-item">
                                    <i class="fas fa-eye"></i> ID: <?php echo $game['id']; ?>
                                </div>
                                <div class="stat-item">
                                    <span class="status-badge status-<?php echo $game['estado']; ?>">
                                        <?php echo ucfirst($game['estado']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="game-secondary-info">
                            <div class="game-actions-container">
                                <button class="edit-btn" 
                                    data-game-id="<?php echo $game['id']; ?>"
                                    data-game-title="<?php echo htmlspecialchars($game['titulo'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-game-description="<?php echo htmlspecialchars($game['descripcion'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-game-price="<?php echo $game['precio']; ?>"
                                    data-game-discount="<?php echo $game['descuento']; ?>"
                                    data-game-category="<?php echo htmlspecialchars($game['categoria'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-game-image="<?php echo htmlspecialchars($game['imagen_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-game-requirements="<?php echo htmlspecialchars($game['requisitos'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="delete-btn"
                                    data-game-id="<?php echo $game['id']; ?>"
                                    data-game-title="<?php echo htmlspecialchars($game['titulo'], ENT_QUOTES); ?>">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                            <button class="view-details-btn"
                                data-game-id="<?php echo $game['id']; ?>"
                                data-game-title="<?php echo htmlspecialchars($game['titulo'], ENT_QUOTES); ?>"
                                data-game-description="<?php echo htmlspecialchars($game['descripcion'], ENT_QUOTES); ?>"
                                data-game-price="<?php echo $game['precio']; ?>"
                                data-game-discount="<?php echo $game['descuento']; ?>"
                                data-game-category="<?php echo htmlspecialchars($game['categoria'], ENT_QUOTES); ?>"
                                data-game-image="<?php echo htmlspecialchars($game['imagen_url'], ENT_QUOTES); ?>"
                                data-game-requirements="<?php echo htmlspecialchars($game['requisitos'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-game-status="<?php echo $game['estado']; ?>"
                                data-game-date="<?php echo $game['fecha_agregado']; ?>">
                                <i class="fas fa-info-circle"></i> Ver Detalles
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-gamepad"></i>
                    <h3>No has enviado ningún juego todavía</h3>
                    <p>Usa el formulario arriba para enviar tu primer juego.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de detalles del juego -->
    <div class="game-details-modal" id="gameDetailsModal">
        <div class="game-details-content">
            <div class="game-details-header">
                <h2 id="detailGameTitle"></h2>
                <button class="close-edit-modal" onclick="closeGameDetails()">&times;</button>
            </div>
            <div class="game-details-body" id="detailGameBody"></div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal" id="deleteConfirmModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirmar Eliminación</h2>
                <button class="close-modal" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar el juego <strong id="deleteGameName"></strong>?</p>
                <p style="color: #ff3333; margin-top: 10px;">
                    <i class="fas fa-exclamation-triangle"></i> Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer" style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="closeDeleteModal()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Cancelar
                </button>
                <button onclick="deleteGame()" style="padding: 10px 20px; background: #ff3333; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Eliminar
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript específico para el panel de desarrollador -->
    <script>
        // Variables globales
        let gameToDelete = null;
        let isSubmitting = false;

       function editGame(gameId, titulo, descripcion, precio, descuento, categoria, imagen_url, requisitos) {
    try {
        console.log('=== EDITANDO JUEGO ID:', gameId, '===');
        
        // Llenar formulario con datos del juego
        document.getElementById('editGameId').value = gameId;
        document.getElementById('titulo').value = titulo || '';
        document.getElementById('descripcion').value = descripcion || '';
        document.getElementById('precio').value = precio || 0;
        document.getElementById('descuento').value = descuento || 0;
        document.getElementById('categoria').value = categoria || '';
        document.getElementById('imagen_url').value = imagen_url || '';
        document.getElementById('requisitos').value = requisitos || '';
        
        // IMPORTANTE: Cambiar el campo action a 'edit_game'
        const actionInput = document.querySelector('input[name="action"]');
        if (actionInput) {
            actionInput.value = 'edit_game';
        }
        
        // Actualizar display del descuento
        updateDiscountValue(descuento);
        
        // Cambiar texto del botón
        document.getElementById('submitButtonText').textContent = 'Actualizar Juego';
        
        // Mostrar botón cancelar
        document.getElementById('cancelEdit').style.display = 'block';
        
        // Scroll al formulario
        document.getElementById('newGameForm').scrollIntoView({ behavior: 'smooth' });
        
        // Cambiar título del formulario
        const formTitle = document.querySelector('#newGameForm h2');
        if (formTitle) {
            formTitle.innerHTML = '<i class="fas fa-edit"></i> Editar Juego';
        }
        
        console.log('Formulario listo para edición. game_id establecido:', gameId);
        
    } catch (error) {
        console.error('Error en editGame:', error);
        alert('Error al cargar el formulario de edición: ' + error.message);
    }
}

        function showGameDetails(gameId, titulo, descripcion, precio, descuento, categoria, imagen_url, requisitos, estado, fecha) {
            try {
                document.getElementById('detailGameTitle').textContent = titulo;
                
                const detailBody = document.getElementById('detailGameBody');
                const precioFinal = descuento > 0 ? 
                    precio * (1 - descuento / 100) : 
                    precio;
                
                detailBody.innerHTML = `
                    <div class="detail-section">
                        <h4><i class="fas fa-info-circle"></i> Información General</h4>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px;">
                            <div><strong>Categoría:</strong> ${categoria}</div>
                            <div><strong>Precio:</strong> 
                                ${descuento > 0 ? 
                                    `<s>$${parseFloat(precio).toFixed(2)}</s> $${precioFinal.toFixed(2)} 
                                    <span style="color: #FF4C4C; font-weight: bold;">(-${descuento}%)</span>` : 
                                    `$${parseFloat(precio).toFixed(2)}`
                                }
                            </div>
                            <div><strong>Estado:</strong> <span class="status-badge status-${estado}">${estado}</span></div>
                            <div><strong>Fecha:</strong> ${new Date(fecha).toLocaleDateString()}</div>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h4><i class="fas fa-align-left"></i> Descripción</h4>
                        <p>${descripcion.replace(/\n/g, '<br>')}</p>
                    </div>
                    
                    <div class="detail-section">
                        <h4><i class="fas fa-desktop"></i> Requisitos del Sistema</h4>
                        <p>${requisitos.replace(/\n/g, '<br>')}</p>
                    </div>
                    
                    <div class="detail-section">
                        <h4><i class="fas fa-image"></i> URL de Imagen</h4>
                        <p><a href="${imagen_url}" target="_blank">${imagen_url}</a></p>
                    </div>
                `;
                
                document.getElementById('gameDetailsModal').style.display = 'flex';
            } catch (error) {
                console.error('Error en showGameDetails:', error);
                alert('Error al mostrar detalles del juego');
            }
        }

        function closeGameDetails() {
            document.getElementById('gameDetailsModal').style.display = 'none';
        }

        function showDeleteConfirm(gameId, gameName) {
            try {
                gameToDelete = gameId;
                document.getElementById('deleteGameName').textContent = gameName;
                document.getElementById('deleteConfirmModal').style.display = 'flex';
            } catch (error) {
                console.error('Error en showDeleteConfirm:', error);
            }
        }

        function closeDeleteModal() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
            gameToDelete = null;
        }

        function deleteGame() {
            if (!gameToDelete) return;
            
            // Redirigir a página de eliminación
            window.location.href = 'delete_game.php?id=' + gameToDelete;
        }

        function updateDiscountValue(value) {
            document.getElementById('discountValue').textContent = value + '%';
            calculateFinalPrice();
        }

        function calculateFinalPrice() {
            const precioInput = document.getElementById('precio');
            const descuentoInput = document.getElementById('descuento');
            const finalPriceDisplay = document.getElementById('finalPriceDisplay');
            
            const precio = parseFloat(precioInput.value) || 0;
            const descuento = parseInt(descuentoInput.value) || 0;
            
            if (precio > 0 && descuento > 0) {
                const precioFinal = precio * (1 - descuento / 100);
                finalPriceDisplay.textContent = `Precio final: $${precioFinal.toFixed(2)}`;
                finalPriceDisplay.classList.add('highlight');
            } else if (descuento > 0) {
                finalPriceDisplay.textContent = `Ingresa un precio para ver el descuento`;
                finalPriceDisplay.classList.remove('highlight');
            } else {
                finalPriceDisplay.textContent = `Sin descuento aplicado`;
                finalPriceDisplay.classList.remove('highlight');
            }
        }

        function resetGameForm() {
            try {
                document.getElementById('gameForm').reset();
                document.getElementById('editGameId').value = '';
                document.getElementById('submitButtonText').textContent = 'Enviar para Revisión';
                document.getElementById('cancelEdit').style.display = 'none';
                
                // Resetear display del descuento
                updateDiscountValue(0);
                
                // Restaurar título del formulario
                const formTitle = document.querySelector('#newGameForm h2');
                if (formTitle) {
                    formTitle.innerHTML = '<i class="fas fa-upload"></i> Enviar Nuevo Juego';
                }
                
                console.log('Formulario reseteado para nuevo juego');
            } catch (error) {
                console.error('Error en resetGameForm:', error);
            }
        }

        // Prevenir doble envío del formulario
        function setupFormSubmission() {
            const gameForm = document.getElementById('gameForm');
            if (gameForm) {
                gameForm.addEventListener('submit', function(e) {
                    if (isSubmitting) {
                        e.preventDefault();
                        alert('Por favor espera, el formulario se está procesando...');
                        return;
                    }
                    
                    isSubmitting = true;
                    
                    // Deshabilitar el botón de envío
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                        
                        // Restaurar después de 5 segundos por si hay error
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                            isSubmitting = false;
                        }, 5000);
                    }
                    
                    // Verificar si es edición o nuevo
                    const gameId = document.getElementById('editGameId').value;
                    console.log('Enviando formulario. game_id:', gameId || 'NUEVO');
                    
                    // El formulario se enviará normalmente
                });
            }
        }

        // Inicializar gráfico de ventas
        function initSalesChart() {
            const salesCtx = document.getElementById('salesChart');
            if (salesCtx) {
                const salesChart = new Chart(salesCtx, {
                    type: 'bar',
                    data: {
                        labels: [
                            <?php foreach ($salesByMonth as $month): ?>
                                '<?php echo date('M Y', strtotime($month['mes'] . '-01')); ?>',
                            <?php endforeach; ?>
                        ],
                        datasets: [{
                            label: 'Ventas',
                            data: [
                                <?php foreach ($salesByMonth as $month): ?>
                                    <?php echo $month['ventas']; ?>,
                                <?php endforeach; ?>
                            ],
                            backgroundColor: '#0074E4',
                            borderColor: '#0066CC',
                            borderWidth: 1
                        }, {
                            label: 'Ganancias ($)',
                            data: [
                                <?php foreach ($salesByMonth as $month): ?>
                                    <?php echo $month['ganancias']; ?>,
                                <?php endforeach; ?>
                            ],
                            backgroundColor: '#00CC66',
                            borderColor: '#00a352',
                            borderWidth: 1,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        stacked: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: '#FFFFFF'
                                }
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Número de Ventas',
                                    color: '#B3B3B3'
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                ticks: {
                                    color: '#B3B3B3',
                                    beginAtZero: true
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Ganancias ($)',
                                    color: '#B3B3B3'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                ticks: {
                                    color: '#B3B3B3',
                                    beginAtZero: true
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
            }
        }

        // Inicializar cuando el DOM esté cargado
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Panel de desarrollador cargado');
            
            // Inicializar gráfico
            initSalesChart();
            
            // Configurar formulario
            setupFormSubmission();
            
            // Botón cancelar edición
            const cancelEditBtn = document.getElementById('cancelEdit');
            if (cancelEditBtn) {
                cancelEditBtn.addEventListener('click', resetGameForm);
            }

            // Botón cerrar sesión
            const logoutBtn = document.getElementById('logoutBtn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function() {
                    window.location.href = 'index.php?logout=true';
                });
            }

            // Configurar eventos de botones
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    editGame(
                        this.getAttribute('data-game-id'),
                        this.getAttribute('data-game-title'),
                        this.getAttribute('data-game-description'),
                        this.getAttribute('data-game-price'),
                        this.getAttribute('data-game-discount'),
                        this.getAttribute('data-game-category'),
                        this.getAttribute('data-game-image'),
                        this.getAttribute('data-game-requirements')
                    );
                });
            });
            
            document.querySelectorAll('.view-details-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    showGameDetails(
                        this.getAttribute('data-game-id'),
                        this.getAttribute('data-game-title'),
                        this.getAttribute('data-game-description'),
                        this.getAttribute('data-game-price'),
                        this.getAttribute('data-game-discount'),
                        this.getAttribute('data-game-category'),
                        this.getAttribute('data-game-image'),
                        this.getAttribute('data-game-requirements'),
                        this.getAttribute('data-game-status'),
                        this.getAttribute('data-game-date')
                    );
                });
            });
            
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    showDeleteConfirm(
                        this.getAttribute('data-game-id'),
                        this.getAttribute('data-game-title')
                    );
                });
            });

            // Cerrar modales al hacer clic fuera
            window.addEventListener('click', (event) => {
                if (event.target === document.getElementById('gameDetailsModal')) {
                    closeGameDetails();
                }
                if (event.target === document.getElementById('deleteConfirmModal')) {
                    closeDeleteModal();
                }
            });
        });
    </script>
</body>
</html>
