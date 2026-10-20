<?php
/**
 * ============================================================
 * SCRIPT DE MIGRACIÓN: MariaDB (SQL) → MongoDB Atlas (NoSQL)
 * ============================================================
 * Proyecto: TechnoGames
 * Práctica: Base de Datos NoSQL
 * 
 * Uso:
 *   php migration/migrate.php                    → Migración completa
 *   php migration/migrate.php --dry-run          → Simular sin insertar
 *   php migration/migrate.php --verbose          → Mostrar cada documento
 *   php migration/migrate.php --collection=X     → Migrar solo una colección
 *   php migration/migrate.php --clean            → Borrar colecciones primero
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// ============================================
// CONFIGURACIÓN
// ============================================

$options = getopt('', ['dry-run', 'verbose', 'clean', 'collection::']);
$dryRun = isset($options['dry-run']);
$verbose = isset($options['verbose']);
$clean = isset($options['clean']);
$targetCollection = $options['collection'] ?? 'all';

// Cargar .env
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die("❌ No se encontró el archivo .env\n");
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
    list($k, $v) = explode('=', $line, 2);
    putenv(trim($k) . '=' . trim($v));
}

// ============================================
// BANNER
// ============================================

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  MIGRACIÓN MariaDB → MongoDB Atlas                   ║\n";
echo "║  Proyecto: TechnoGames                               ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

if ($dryRun) echo "⚠️  MODO DRY-RUN: no se insertará nada en MongoDB\n";
if ($clean)  echo "🧹 MODO CLEAN: se borrarán las colecciones primero\n";
echo "\n";

// ============================================
// CONEXIONES
// ============================================

echo "🔌 Conectando a MariaDB...\n";
$mysql = new mysqli('localhost', 'tienda_user', 'password123', 'tienda_videojuegos');
if ($mysql->connect_error) {
    die("❌ Error MariaDB: " . $mysql->connect_error . "\n");
}
$mysql->set_charset('utf8mb4');
echo "   ✅ MariaDB conectado\n";

echo "🔌 Conectando a MongoDB Atlas...\n";
try {
    $mongo = new Client(getenv('MONGODB_URI'), [
        'tls' => true,
        'serverSelectionTimeoutMS' => 10000,
    ]);
    $mongo->selectDatabase('admin')->command(['ping' => 1]);
    $db = $mongo->selectDatabase('technogames');
    echo "   ✅ MongoDB Atlas conectado\n\n";
} catch (Exception $e) {
    die("❌ Error MongoDB: " . $e->getMessage() . "\n");
}

// ============================================
// LOGGER
// ============================================

$logFile = __DIR__ . '/logs/migration_' . date('Ymd_His') . '.log';

function log_msg($msg, $level = 'INFO') {
    global $logFile, $verbose;
    $line = '[' . date('H:i:s') . "][$level] $msg\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    if ($level === 'ERROR' || $verbose) {
        echo $line;
    }
}

log_msg("=== INICIO DE MIGRACIÓN ===");
log_msg("Modo: " . ($dryRun ? 'DRY-RUN' : 'REAL'));

// ============================================
// MAPA DE IDs
// ============================================

$idMap = [
    'usuarios' => [],
    'juegos' => [],
];

// ============================================
// EJECUCIÓN
// ============================================

$startTime = microtime(true);
$stats = [];

$migrations = [
    'usuarios'          => 'migrateUsuarios',
    'juegos'            => 'migrateJuegos',
    'carritos'          => 'migrateCarritos',
    'bibliotecas'       => 'migrateBibliotecas',
    'wishlists'         => 'migrateWishlists',
    'ordenes'           => 'migrateOrdenes',
    'resenas'           => 'migrateResenas',
];

foreach ($migrations as $collection => $function) {
    if ($targetCollection !== 'all' && $targetCollection !== $collection) {
        continue;
    }
    
    echo "─────────────────────────────────────────\n";
    echo "📦 Migrando: $collection\n";
    echo "─────────────────────────────────────────\n";
    
    if ($clean && !$dryRun) {
        echo "   🗑️  Limpiando colección $collection...\n";
        $db->selectCollection($collection)->drop();
    }
    
    try {
        $stats[$collection] = $function($mysql, $db, $dryRun, $idMap);
    } catch (Exception $e) {
        log_msg("Error migrando $collection: " . $e->getMessage(), 'ERROR');
        $stats[$collection] = -1;
    }
    echo "\n";
}

// Guardar mapa de IDs
if (!$dryRun) {
    $idMapFile = __DIR__ . '/logs/id_map.json';
    file_put_contents($idMapFile, json_encode($idMap, JSON_PRETTY_PRINT));
    log_msg("Mapa de IDs guardado en: $idMapFile");
}

// ============================================
// RESUMEN
// ============================================

$elapsed = round(microtime(true) - $startTime, 2);

echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  ✅ MIGRACIÓN COMPLETADA                             ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

echo "📊 Resumen:\n";
foreach ($stats as $collection => $count) {
    $icon = $count >= 0 ? '✅' : '❌';
    printf("   %s %-15s %d documentos\n", $icon, $collection . ':', $count);
}

echo "\n⏱️  Tiempo total: {$elapsed}s\n";
echo "📄 Log: $logFile\n\n";

if ($dryRun) {
    echo "⚠️  Recordatorio: fue DRY-RUN, no se insertó nada\n";
    echo "   Ejecuta sin --dry-run para migrar de verdad\n\n";
}

$mysql->close();

// ============================================
// FUNCIONES DE MIGRACIÓN
// ============================================

function migrateUsuarios($mysql, $db, $dryRun, &$idMap) {
    $result = $mysql->query("SELECT * FROM usuarios ORDER BY id");
    $collection = $db->selectCollection('usuarios');
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $mongoId = new ObjectId();
        $idMap['usuarios'][$row['id']] = (string)$mongoId;
        
        $doc = [
            '_id' => $mongoId,
            'mysql_id' => (int)$row['id'],
            'nombre_usuario' => $row['nombre_usuario'],
            'email' => $row['email'],
            'password_hash' => $row['password'],
            'rol' => $row['rol'],
            'created_at' => new UTCDateTime(strtotime($row['fecha_registro']) * 1000),
            'is_active' => true,
            'login_attempts' => 0,
            'locked_until' => null,
            'stats' => [
                'total_purchases' => 0,
                'total_spent' => 0.0,
                'wishlist_count' => 0
            ]
        ];
        
        if (!$dryRun) {
            try {
                $collection->insertOne($doc);
                $count++;
                log_msg("Usuario insertado: {$row['email']} → $mongoId");
            } catch (Exception $e) {
                log_msg("Error insertando usuario {$row['email']}: " . $e->getMessage(), 'ERROR');
            }
        } else {
            $count++;
            log_msg("DRY-RUN: Usuario {$row['email']} sería insertado");
        }
    }
    
    log_msg("Usuarios migrados: $count");
    return $count;
}

function migrateJuegos($mysql, $db, $dryRun, &$idMap) {
    $sql = "SELECT j.*, u.nombre_usuario as dev_nombre 
            FROM juegos j 
            LEFT JOIN usuarios u ON j.desarrollador_id = u.id 
            ORDER BY j.id";
    $result = $mysql->query($sql);
    $collection = $db->selectCollection('juegos');
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $mongoId = new ObjectId();
        $idMap['juegos'][$row['id']] = (string)$mongoId;
        
        $requisitos = parseRequisitos($row['requisitos']);
        
        $precio = (float)$row['precio'];
        $descuento = (int)$row['descuento'];
        $precioFinal = $descuento > 0 ? $precio * (1 - $descuento / 100) : $precio;
        
        $devObjectId = null;
        if ($row['desarrollador_id'] && isset($idMap['usuarios'][$row['desarrollador_id']])) {
            $devObjectId = new ObjectId($idMap['usuarios'][$row['desarrollador_id']]);
        }
        
        $doc = [
            '_id' => $mongoId,
            'mysql_id' => (int)$row['id'],
            'titulo' => $row['titulo'],
            'descripcion' => $row['descripcion'],
            'precio' => $precio,
            'descuento' => $descuento,
            'precio_final' => round($precioFinal, 2),
            'imagen_url' => $row['imagen_url'],
            'categoria' => $row['categoria'],
            'estado' => $row['estado'],
            'requisitos' => $requisitos,
            'desarrollador_id' => $devObjectId,
            'desarrollador_nombre' => $row['dev_nombre'],
            'rating_promedio' => 0,
            'total_resenas' => 0,
            'total_ventas' => 0,
            'fecha_agregado' => new UTCDateTime(strtotime($row['fecha_agregado']) * 1000),
            'tags' => extractTags($row['titulo'], $row['categoria'])
        ];
        
        if (!$dryRun) {
            try {
                $collection->insertOne($doc);
                $count++;
                log_msg("Juego insertado: {$row['titulo']}");
            } catch (Exception $e) {
                log_msg("Error insertando juego {$row['titulo']}: " . $e->getMessage(), 'ERROR');
            }
        } else {
            $count++;
        }
    }
    
    log_msg("Juegos migrados: $count");
    return $count;
}

function migrateCarritos($mysql, $db, $dryRun, &$idMap) {
    $sql = "SELECT c.*, j.titulo, j.precio, j.descuento 
            FROM carrito c 
            INNER JOIN juegos j ON c.juego_id = j.id 
            ORDER BY c.usuario_id";
    $result = $mysql->query($sql);
    $collection = $db->selectCollection('carritos');
    
    $carritos = [];
    while ($row = $result->fetch_assoc()) {
        $userId = $row['usuario_id'];
        if (!isset($carritos[$userId])) {
            $carritos[$userId] = ['usuario_id' => $userId, 'items' => []];
        }
        
        $precio = (float)$row['precio'];
        $descuento = (int)$row['descuento'];
        $precioFinal = $descuento > 0 ? $precio * (1 - $descuento / 100) : $precio;
        
        $carritos[$userId]['items'][] = [
            'juego_id' => isset($idMap['juegos'][$row['juego_id']]) 
                ? new ObjectId($idMap['juegos'][$row['juego_id']]) : null,
            'titulo' => $row['titulo'],
            'precio_final' => round($precioFinal, 2),
            'cantidad' => 1,
            'agregado_en' => new UTCDateTime(strtotime($row['fecha_agregado']) * 1000)
        ];
    }
    
    $count = 0;
    foreach ($carritos as $userId => $data) {
        $mongoUserId = isset($idMap['usuarios'][$userId]) 
            ? new ObjectId($idMap['usuarios'][$userId]) : null;
        
        $doc = [
            'usuario_id' => $mongoUserId,
            'items' => $data['items'],
            'updated_at' => new UTCDateTime()
        ];
        
        if (!$dryRun) {
            try {
                $collection->insertOne($doc);
                $count++;
            } catch (Exception $e) {
                log_msg("Error insertando carrito: " . $e->getMessage(), 'ERROR');
            }
        } else {
            $count++;
        }
    }
    
    log_msg("Carritos migrados: $count");
    return $count;
}

function migrateBibliotecas($mysql, $db, $dryRun, &$idMap) {
    $sql = "SELECT b.*, j.titulo 
            FROM biblioteca b 
            INNER JOIN juegos j ON b.juego_id = j.id 
            ORDER BY b.usuario_id";
    $result = $mysql->query($sql);
    $collection = $db->selectCollection('bibliotecas');
    
    $bibliotecas = [];
    while ($row = $result->fetch_assoc()) {
        $userId = $row['usuario_id'];
        if (!isset($bibliotecas[$userId])) {
            $bibliotecas[$userId] = ['usuario_id' => $userId, 'juegos' => []];
        }
        
        $bibliotecas[$userId]['juegos'][] = [
            'juego_id' => isset($idMap['juegos'][$row['juego_id']]) 
                ? new ObjectId($idMap['juegos'][$row['juego_id']]) : null,
            'titulo' => $row['titulo'],
            'fecha_adquisicion' => new UTCDateTime(strtotime($row['fecha_agregado']) * 1000)
        ];
    }
    
    $count = 0;
    foreach ($bibliotecas as $userId => $data) {
        $mongoUserId = isset($idMap['usuarios'][$userId]) 
            ? new ObjectId($idMap['usuarios'][$userId]) : null;
        
        $doc = [
            'usuario_id' => $mongoUserId,
            'juegos' => $data['juegos'],
            'total_juegos' => count($data['juegos'])
        ];
        
        if (!$dryRun) {
            try {
                $collection->insertOne($doc);
                $count++;
            } catch (Exception $e) {
                log_msg("Error insertando biblioteca: " . $e->getMessage(), 'ERROR');
            }
        } else {
            $count++;
        }
    }
    
    log_msg("Bibliotecas migradas: $count");
    return $count;
}

function migrateWishlists($mysql, $db, $dryRun, &$idMap) {
    $sql = "SELECT w.*, j.titulo, j.precio, j.descuento 
            FROM wishlist w 
            INNER JOIN juegos j ON w.juego_id = j.id 
            ORDER BY w.usuario_id";
    $result = $mysql->query($sql);
    $collection = $db->selectCollection('wishlists');
    
    $wishlists = [];
    while ($row = $result->fetch_assoc()) {
        $userId = $row['usuario_id'];
        if (!isset($wishlists[$userId])) {
            $wishlists[$userId] = ['usuario_id' => $userId, 'juegos' => []];
        }
        
        $precio = (float)$row['precio'];
        $descuento = (int)$row['descuento'];
        $precioFinal = $descuento > 0 ? $precio * (1 - $descuento / 100) : $precio;
        
        $wishlists[$userId]['juegos'][] = [
            'juego_id' => isset($idMap['juegos'][$row['juego_id']]) 
                ? new ObjectId($idMap['juegos'][$row['juego_id']]) : null,
            'titulo' => $row['titulo'],
            'precio_actual' => round($precioFinal, 2),
            'descuento' => $descuento,
            'agregado_en' => new UTCDateTime(strtotime($row['fecha_agregado']) * 1000)
        ];
    }
    
    $count = 0;
    foreach ($wishlists as $userId => $data) {
        $mongoUserId = isset($idMap['usuarios'][$userId]) 
            ? new ObjectId($idMap['usuarios'][$userId]) : null;
        
        $doc = [
            'usuario_id' => $mongoUserId,
            'juegos' => $data['juegos'],
            'total_juegos' => count($data['juegos'])
        ];
        
        if (!$dryRun) {
            try {
                $collection->insertOne($doc);
                $count++;
            } catch (Exception $e) {
                log_msg("Error insertando wishlist: " . $e->getMessage(), 'ERROR');
            }
        } else {
            $count++;
        }
    }
    
    log_msg("Wishlists migradas: $count");
    return $count;
}

function migrateOrdenes($mysql, $db, $dryRun, &$idMap) {
    $check = $mysql->query("SHOW TABLES LIKE 'orders'");
    if ($check->num_rows === 0) {
        log_msg("La tabla 'orders' no existe, saltando...", 'WARNING');
        return 0;
    }
    
    $sql = "SELECT o.*, u.email as usuario_email, u.nombre_usuario as usuario_nombre
            FROM orders o 
            LEFT JOIN usuarios u ON o.user_id = u.id 
            ORDER BY o.created_at DESC";
    $result = $mysql->query($sql);
    $collection = $db->selectCollection('ordenes');
    $count = 0;
    
    while ($order = $result->fetch_assoc()) {
        $itemStmt = $mysql->prepare("
            SELECT oi.*, j.titulo 
            FROM order_items oi 
            INNER JOIN juegos j ON oi.game_id = j.id 
            WHERE oi.order_id = ?
        ");
        $itemStmt->bind_param("s", $order['order_id']);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();
        
        $items = [];
        while ($item = $itemResult->fetch_assoc()) {
            $items[] = [
                'juego_id' => isset($idMap['juegos'][$item['game_id']]) 
                    ? new ObjectId($idMap['juegos'][$item['game_id']]) : null,
                'titulo' => $item['titulo'],
                'cantidad' => (int)$item['quantity'],
                'precio_unitario' => (float)$item['unit_price'],
                'descuento' => (int)$item['discount'],
                'precio_final' => (float)$item['final_price'],
                'comision' => round((float)$item['final_price'] * 0.3, 2)
            ];
        }
        $itemStmt->close();
        
        $doc = [
            'order_id' => $order['order_id'],
            'usuario_id' => isset($idMap['usuarios'][$order['user_id']]) 
                ? new ObjectId($idMap['usuarios'][$order['user_id']]) : null,
            'usuario_email' => $order['usuario_email'],
            'usuario_nombre' => $order['usuario_nombre'],
            'items' => $items,
            'subtotal' => (float)$order['subtotal'],
            'impuesto' => (float)$order['tax'],
            'total' => (float)$order['total'],
            'metodo_pago' => $order['payment_method'],
            'estado' => $order['status'],
            'fecha_compra' => new UTCDateTime(strtotime($order['created_at']) * 1000)
        ];
        
        if (!$dryRun) {
            try {
                $collection->insertOne($doc);
                $count++;
            } catch (Exception $e) {
                log_msg("Error insertando orden: " . $e->getMessage(), 'ERROR');
            }
        } else {
            $count++;
        }
    }
    
    log_msg("Órdenes migradas: $count");
    return $count;
}

function migrateResenas($mysql, $db, $dryRun, &$idMap) {
    $check = $mysql->query("SHOW TABLES LIKE 'reseñas'");
    if ($check->num_rows === 0) {
        log_msg("La tabla 'reseñas' no existe, saltando...", 'WARNING');
        return 0;
    }
    
    $sql = "SELECT r.*, u.nombre_usuario 
            FROM reseñas r 
            INNER JOIN usuarios u ON r.usuario_id = u.id 
            ORDER BY r.fecha_creacion DESC";
    $result = $mysql->query($sql);
    $collection = $db->selectCollection('resenas');
    $count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $doc = [
            'juego_id' => isset($idMap['juegos'][$row['juego_id']]) 
                ? new ObjectId($idMap['juegos'][$row['juego_id']]) : null,
            'usuario_id' => isset($idMap['usuarios'][$row['usuario_id']]) 
                ? new ObjectId($idMap['usuarios'][$row['usuario_id']]) : null,
            'usuario_nombre' => $row['nombre_usuario'],
            'calificacion' => (int)$row['calificacion'],
            'comentario' => $row['comentario'],
            'fecha_creacion' => new UTCDateTime(strtotime($row['fecha_creacion']) * 1000),
            'votos_util' => 0
        ];
        
        if (!$dryRun) {
            try {
                $collection->insertOne($doc);
                $count++;
            } catch (Exception $e) {
                log_msg("Error insertando reseña: " . $e->getMessage(), 'ERROR');
            }
        } else {
            $count++;
        }
    }
    
    log_msg("Reseñas migradas: $count");
    return $count;
}

// ============================================
// HELPERS
// ============================================

function parseRequisitos($texto) {
    if (empty($texto)) return [];
    
    $requisitos = [];
    $lines = explode("\n", $texto);
    
    $mapping = [
        'so' => 'os', 'sistema operativo' => 'os',
        'procesador' => 'processor', 'cpu' => 'processor',
        'memoria' => 'memory', 'ram' => 'memory',
        'gráficos' => 'graphics', 'graficos' => 'graphics', 'gpu' => 'graphics',
        'almacenamiento' => 'storage', 'disco' => 'storage'
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

function extractTags($titulo, $categoria) {
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
