<?php
/**
 * ============================================================
 * GESTIÓN DE JUEGOS: process_game.php (versión MongoDB)
 * ============================================================
 * Proyecto: TechnoGames
 * 
 * Acciones para DESARROLLADORES:
 *   - submit_game          → crear o editar juego
 *   - delete_game          → eliminar juego
 *   - get_game             → obtener datos de un juego
 *   - get_developer_stats  → estadísticas del dev
 *   - get_my_games         → listar mis juegos
 * 
 * Acciones para ADMIN:
 *   - get_pending_games    → listar juegos pendientes
 *   - approve_game         → aprobar juego
 *   - reject_game          → rechazar juego
 */

require_once __DIR__ . '/config/helpers.php';
configureSession();
session_start();

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// ============================================
// ROUTER
// ============================================

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    jsonError('Acción no especificada');
}

try {
    switch ($action) {
        // Desarrollador
        case 'submit_game':           submitGame();           break;
        case 'delete_game':           deleteGame();           break;
        case 'get_game':              getGame();              break;
        case 'get_developer_stats':   getDeveloperStats();    break;
        case 'get_my_games':          getMyGames();           break;
        
        // Admin
        case 'get_pending_games':     getPendingGames();      break;
        case 'approve_game':          approveGame();          break;
        case 'reject_game':           rejectGame();           break;
        case 'get_admin_stats':       getAdminStats();        break;
        
        default:
            jsonError("Acción '$action' no válida");
    }
} catch (Exception $e) {
    error_log("process_game error [$action]: " . $e->getMessage());
    jsonError('Error interno: ' . $e->getMessage(), 500);
}

// ============================================
// ACCIONES DE DESARROLLADOR
// ============================================

function submitGame(): void
{
    $user = requireRole('dev', 'admin');
    
    $titulo = clean($_POST['titulo'] ?? '');
    $descripcion = clean($_POST['descripcion'] ?? '');
    $precio = (float)($_POST['precio'] ?? 0);
    $descuento = (int)($_POST['descuento'] ?? 0);
    $categoria = clean($_POST['categoria'] ?? '');
    $imagen_url = clean($_POST['imagen_url'] ?? '');
    $requisitos = clean($_POST['requisitos'] ?? '');
    $gameId = clean($_POST['game_id'] ?? '');
    
    // Validaciones
    if (empty($titulo) || empty($descripcion) || empty($categoria)) {
        jsonError('Complete todos los campos obligatorios');
    }
    if ($precio <= 0) {
        jsonError('El precio debe ser mayor a 0');
    }
    if ($descuento < 0 || $descuento > 100) {
        jsonError('El descuento debe estar entre 0 y 100');
    }
    
    // Parsear requisitos
    $requisitosObj = parseRequisitos($requisitos);
    
    $db = Database::getInstance();
    $juegos = $db->getCollection('juegos');
    
    $doc = [
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'precio' => $precio,
        'descuento' => $descuento,
        'precio_final' => round($descuento > 0 ? $precio * (1 - $descuento / 100) : $precio, 2),
        'imagen_url' => $imagen_url,
        'categoria' => $categoria,
        'requisitos' => $requisitosObj,
        'desarrollador_id' => $user['_id'],
        'desarrollador_nombre' => $user['nombre_usuario'],
        'estado' => 'pendiente',
        'tags' => extractTags($titulo, $categoria),
        'fecha_actualizacion' => new UTCDateTime(),
    ];
    
    // EDITAR vs CREAR
    if (!empty($gameId)) {
        // Editar juego existente
        $gameObjId = toObjectId($gameId);
        if (!$gameObjId) {
            jsonError('ID de juego no válido');
        }
        
        // Verificar que sea del desarrollador
        $existing = $juegos->findOne([
            '_id' => $gameObjId,
            'desarrollador_id' => $user['_id'],
        ]);
        
        if (!$existing) {
            jsonError('No tienes permiso para editar este juego');
        }
        
        // Actualizar (vuelve a estado pendiente)
        $result = $juegos->updateOne(
            ['_id' => $gameObjId],
            ['$set' => $doc]
        );
        
        auditLog('game_updated', ['game_id' => $gameId, 'titulo' => $titulo], (string)$user['_id']);
        
        jsonSuccess([
            'game_id' => $gameId,
            'action' => 'updated',
        ], 'Juego actualizado. Debe ser aprobado nuevamente.');
        
    } else {
        // Verificar duplicados
        $duplicate = $juegos->findOne([
            'titulo' => $titulo,
            'desarrollador_id' => $user['_id'],
        ]);
        
        if ($duplicate) {
            jsonError("Ya tienes un juego con el título '$titulo'");
        }
        
        // Insertar nuevo
        $doc['fecha_agregado'] = new UTCDateTime();
        $doc['rating_promedio'] = 0;
        $doc['total_resenas'] = 0;
        $doc['total_ventas'] = 0;
        
        $result = $juegos->insertOne($doc);
        
        auditLog('game_submitted', [
            'game_id' => (string)$result->getInsertedId(),
            'titulo' => $titulo,
        ], (string)$user['_id']);
        
        jsonSuccess([
            'game_id' => (string)$result->getInsertedId(),
            'action' => 'created',
        ], 'Juego enviado para revisión');
    }
}

function deleteGame(): void
{
    $user = requireRole('dev', 'admin');
    $gameId = clean($_POST['game_id'] ?? $_GET['game_id'] ?? '');
    
    $gameObjId = toObjectId($gameId);
    if (!$gameObjId) {
        jsonError('ID de juego no válido');
    }
    
    $db = Database::getInstance();
    $juegos = $db->getCollection('juegos');
    
    // Verificar que sea del desarrollador (o admin)
    $filter = ['_id' => $gameObjId];
    if ($user['rol'] !== 'admin') {
        $filter['desarrollador_id'] = $user['_id'];
    }
    
    $juego = $juegos->findOne($filter);
    if (!$juego) {
        jsonError('Juego no encontrado o sin permisos');
    }
    
    // Eliminar dependencias en todas las colecciones
    $collections = ['resenas', 'bibliotecas', 'carritos', 'wishlists', 'ordenes'];
    foreach ($collections as $colName) {
        $col = $db->getCollection($colName);
        
        if ($colName === 'bibliotecas' || $colName === 'carritos' || $colName === 'wishlists') {
            // Array de items embebidos
            $field = match($colName) {
                'bibliotecas' => 'juegos',
                'carritos' => 'items',
                'wishlists' => 'juegos',
            };
            $col->updateMany(
                ["{$field}.juego_id" => $gameObjId],
                ['$pull' => [$field => ['juego_id' => $gameObjId]]]
            );
        } elseif ($colName === 'resenas') {
            // Documentos directos con juego_id
            $col->deleteMany(['juego_id' => $gameObjId]);
        } elseif ($colName === 'ordenes') {
            // Array de items en órdenes (no eliminamos la orden, solo el item)
            $col->updateMany(
                ['items.juego_id' => $gameObjId],
                ['$pull' => ['items' => ['juego_id' => $gameObjId]]]
            );
        }
    }
    
    // Finalmente, eliminar el juego
    $juegos->deleteOne(['_id' => $gameObjId]);
    
    auditLog('game_deleted', [
        'game_id' => $gameId,
        'titulo' => $juego['titulo'],
    ], (string)$user['_id']);
    
    jsonSuccess(['titulo' => $juego['titulo']], "Juego '{$juego['titulo']}' eliminado exitosamente");
}

function getGame(): void
{
    $user = requireRole('dev', 'admin');
    $gameId = clean($_POST['game_id'] ?? '');
    
    $gameObjId = toObjectId($gameId);
    if (!$gameObjId) {
        jsonError('ID de juego no válido');
    }
    
    $db = Database::getInstance();
    $juegos = $db->getCollection('juegos');
    
    $filter = ['_id' => $gameObjId];
    if ($user['rol'] !== 'admin') {
        $filter['desarrollador_id'] = $user['_id'];
    }
    
    $juego = $juegos->findOne($filter);
    if (!$juego) {
        jsonError('Juego no encontrado', 404);
    }
    
    jsonSuccess(['game' => bsonToArray($juego)]);
}

function getMyGames(): void
{
    $user = requireRole('dev', 'admin');
    
    $db = Database::getInstance();
    $juegos = $db->getCollection('juegos');
    
    $cursor = $juegos->find(
        ['desarrollador_id' => $user['_id']],
        ['sort' => ['fecha_agregado' => -1]]
    );
    
    $games = [];
    foreach ($cursor as $g) {
        $game = (array)$g;
        $game['id'] = (string)$g['_id'];
        unset($game['_id']);
        
        // Convertir fechas y ObjectIds
        $games[] = bsonToArray($game);
    }
    
    jsonSuccess(['games' => $games]);
}

function getDeveloperStats(): void
{
    $user = requireRole('dev', 'admin');
    
    $db = Database::getInstance();
    $juegos = $db->getCollection('juegos');
    $ordenes = $db->getCollection('ordenes');
    
    // 1. Estadísticas básicas
    $totalGames = $juegos->countDocuments(['desarrollador_id' => $user['_id']]);
    
    // 2. Juegos por estado
    $gamesByStatus = $juegos->aggregate([
        ['$match' => ['desarrollador_id' => $user['_id']]],
        ['$group' => ['_id' => '$estado', 'count' => ['$sum' => 1]]],
    ])->toArray();
    
    // 3. Ventas y ganancias totales
    $salesAgg = $ordenes->aggregate([
        ['$unwind' => '$items'],
        ['$match' => ['items.desarrollador_id' => $user['_id']]],
        ['$group' => [
            '_id' => null,
            'total_ventas' => ['$sum' => 1],
            'ganancias_netas' => ['$sum' => [
                '$subtract' => ['$items.precio_final', '$items.comision']
            ]],
            'promedio_venta' => ['$avg' => '$items.precio_final'],
        ]],
    ])->toArray();
    
    $stats = $salesAgg[0] ?? ['total_ventas' => 0, 'ganancias_netas' => 0, 'promedio_venta' => 0];
    
    // 4. Top 5 juegos más vendidos
    $topGames = $ordenes->aggregate([
        ['$unwind' => '$items'],
        ['$match' => ['items.desarrollador_id' => $user['_id']]],
        ['$group' => [
            '_id' => '$items.juego_id',
            'titulo' => ['$first' => '$items.titulo'],
            'ventas' => ['$sum' => 1],
            'ganancias' => ['$sum' => [
                '$subtract' => ['$items.precio_final', '$items.comision']
            ]],
        ]],
        ['$sort' => ['ventas' => -1]],
        ['$limit' => 5],
    ])->toArray();
    
    // 5. Ventas por mes (últimos 6 meses)
    $sixMonthsAgo = new UTCDateTime((time() - 6 * 30 * 86400) * 1000);
    $salesByMonth = $ordenes->aggregate([
        ['$match' => ['fecha_compra' => ['$gte' => $sixMonthsAgo]]],
        ['$unwind' => '$items'],
        ['$match' => ['items.desarrollador_id' => $user['_id']]],
        ['$group' => [
            '_id' => ['$dateToString' => ['format' => '%Y-%m', 'date' => '$fecha_compra']],
            'ventas' => ['$sum' => 1],
            'ganancias' => ['$sum' => [
                '$subtract' => ['$items.precio_final', '$items.comision']
            ]],
        ]],
        ['$sort' => ['_id' => 1]],
    ])->toArray();
    
    jsonSuccess([
        'total_games' => $totalGames,
        'games_by_status' => array_map(function($item) {
            return ['estado' => $item['_id'], 'count' => $item['count']];
        }, $gamesByStatus),
        'total_ventas' => (int)($stats['total_ventas'] ?? 0),
        'ganancias_netas' => round((float)($stats['ganancias_netas'] ?? 0), 2),
        'promedio_venta' => round((float)($stats['promedio_venta'] ?? 0), 2),
        'top_games' => array_map(function($item) {
            return [
                'titulo' => $item['titulo'],
                'ventas' => $item['ventas'],
                'ganancias' => round($item['ganancias'], 2),
            ];
        }, $topGames),
        'sales_by_month' => array_map(function($item) {
            return [
                'mes' => $item['_id'],
                'ventas' => $item['ventas'],
                'ganancias' => round($item['ganancias'], 2),
            ];
        }, $salesByMonth),
    ]);
}

// ============================================
// ACCIONES DE ADMIN
// ============================================

function getPendingGames(): void
{
    requireRole('admin');
    
    $db = Database::getInstance();
    $juegos = $db->getCollection('juegos');
    
    $cursor = $juegos->find(
        ['estado' => 'pendiente'],
        ['sort' => ['fecha_actualizacion' => -1, 'fecha_agregado' => -1]]
    );
    
    $games = [];
    foreach ($cursor as $g) {
        $game = (array)$g;
        $game['id'] = (string)$g['_id'];
        unset($game['_id']);
        $games[] = bsonToArray($game);
    }
    
    jsonSuccess(['games' => $games]);
}

function approveGame(): void
{
    $user = requireRole('admin');
    $gameId = clean($_POST['game_id'] ?? '');
    
    $gameObjId = toObjectId($gameId);
    if (!$gameObjId) {
        jsonError('ID de juego no válido');
    }
    
    $db = Database::getInstance();
    $juegos = $db->getCollection('juegos');
    
    $result = $juegos->updateOne(
        ['_id' => $gameObjId, 'estado' => 'pendiente'],
        ['$set' => [
            'estado' => 'aprobado',
            'fecha_aprobacion' => new UTCDateTime(),
            'aprobado_por' => $user['_id'],
        ]]
    );
    
    if ($result->getMatchedCount() === 0) {
        jsonError('Juego no encontrado o ya procesado');
    }
    
    auditLog('game_approved', ['game_id' => $gameId], (string)$user['_id']);
    
    jsonSuccess(null, 'Juego aprobado');
}

function rejectGame(): void
{
    $user = requireRole('admin');
    $gameId = clean($_POST['game_id'] ?? '');
    $motivo = clean($_POST['motivo'] ?? '');
    
    $gameObjId = toObjectId($gameId);
    if (!$gameObjId) {
        jsonError('ID de juego no válido');
    }
    
    $db = Database::getInstance();
    $juegos = $db->getCollection('juegos');
    
    $result = $juegos->updateOne(
        ['_id' => $gameObjId, 'estado' => 'pendiente'],
        ['$set' => [
            'estado' => 'rechazado',
            'fecha_rechazo' => new UTCDateTime(),
            'rechazado_por' => $user['_id'],
            'motivo_rechazo' => $motivo,
        ]]
    );
    
    if ($result->getMatchedCount() === 0) {
        jsonError('Juego no encontrado o ya procesado');
    }
    
    auditLog('game_rejected', ['game_id' => $gameId, 'motivo' => $motivo], (string)$user['_id']);
    
    jsonSuccess(null, 'Juego rechazado');
}

function getAdminStats(): void
{
    requireRole('admin');
    
    $db = Database::getInstance();
    
    // Total ventas globales
    $totalOrders = $db->getCollection('ordenes')->countDocuments();
    $totalUsers = $db->getCollection('usuarios')->countDocuments();
    $totalGames = $db->getCollection('juegos')->countDocuments(['estado' => 'aprobado']);
    
    // Ingresos y comisiones totales
    $revenueAgg = $db->getCollection('ordenes')->aggregate([
        ['$group' => [
            '_id' => null,
            'ingresos_totales' => ['$sum' => '$total'],
            'comisiones_totales' => ['$sum' => [
                '$sum' => '$items.comision'
            ]],
        ]],
    ])->toArray();
    
    $revenue = $revenueAgg[0] ?? ['ingresos_totales' => 0, 'comisiones_totales' => 0];
    
    // Usuarios por rol
    $usersByRole = $db->getCollection('usuarios')->aggregate([
        ['$group' => ['_id' => '$rol', 'count' => ['$sum' => 1]]],
        ['$sort' => ['count' => -1]],
    ])->toArray();
    
    // Juegos por estado
    $gamesByStatus = $db->getCollection('juegos')->aggregate([
        ['$group' => ['_id' => '$estado', 'count' => ['$sum' => 1]]],
    ])->toArray();
    
    // Juegos por categoría
    $gamesByCategory = $db->getCollection('juegos')->aggregate([
        ['$match' => ['estado' => 'aprobado']],
        ['$group' => ['_id' => '$categoria', 'count' => ['$sum' => 1]]],
        ['$sort' => ['count' => -1]],
    ])->toArray();
    
    jsonSuccess([
        'total_orders' => $totalOrders,
        'total_users' => $totalUsers,
        'total_games' => $totalGames,
        'ingresos_totales' => round((float)$revenue['ingresos_totales'], 2),
        'comisiones_totales' => round((float)$revenue['comisiones_totales'], 2),
        'users_by_role' => array_map(function($i) { return ['rol' => $i['_id'], 'count' => $i['count']]; }, $usersByRole),
        'games_by_status' => array_map(function($i) { return ['estado' => $i['_id'], 'count' => $i['count']]; }, $gamesByStatus),
        'games_by_category' => array_map(function($i) { return ['categoria' => $i['_id'], 'count' => $i['count']]; }, $gamesByCategory),
    ]);
}

// ============================================
// HELPERS
// ============================================

function parseRequisitos(string $texto): array
{
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

function extractTags(string $titulo, string $categoria): array
{
    $tags = [strtolower($categoria)];
    $keywords = ['rpg', 'fps', 'open world', 'multiplayer', 'single player',
                 'action', 'adventure', 'strategy', 'indie', 'aaa'];
    
    $tituloLower = strtolower($titulo);
    foreach ($keywords as $kw) {
        if (strpos($tituloLower, $kw) !== false) {
            $tags[] = $kw;
        }
    }
    
    return array_values(array_unique($tags));
}
