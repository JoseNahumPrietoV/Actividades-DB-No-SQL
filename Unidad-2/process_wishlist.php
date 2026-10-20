<?php
/**
 * ============================================================
 * WISHLIST: process_wishlist.php (versión MongoDB)
 * ============================================================
 * Proyecto: TechnoGames
 * 
 * Acciones:
 *   - add_to_wishlist        → añadir a lista de deseos
 *   - remove_from_wishlist   → eliminar de lista de deseos
 *   - get_wishlist           → obtener lista de deseos
 *   - check_wishlist_status  → verificar si un juego está en wishlist
 *   - move_to_cart           → mover juego al carrito
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
        case 'add_to_wishlist':       addToWishlist();        break;
        case 'remove_from_wishlist':  removeFromWishlist();   break;
        case 'get_wishlist':          getWishlist();          break;
        case 'check_wishlist_status': checkWishlistStatus();  break;
        case 'move_to_cart':          moveToCart();           break;
        default:
            jsonError("Acción '$action' no válida");
    }
} catch (Exception $e) {
    error_log("process_wishlist error [$action]: " . $e->getMessage());
    jsonError('Error interno: ' . $e->getMessage(), 500);
}

// ============================================
// ACCIONES
// ============================================

function addToWishlist(): void
{
    $user = requireLogin();
    $gameId = clean($_POST['game_id'] ?? '');
    
    $gameObjId = toObjectId($gameId);
    if (!$gameObjId) {
        jsonError('ID de juego no válido');
    }
    
    $db = Database::getInstance();
    $juegos = $db->getCollection('juegos');
    $wishlists = $db->getCollection('wishlists');
    
    // Verificar que el juego existe
    $juego = $juegos->findOne(['_id' => $gameObjId, 'estado' => 'aprobado']);
    if (!$juego) {
        jsonError('Juego no disponible');
    }
    
    // Verificar si ya está en la wishlist
    $wishlist = $wishlists->findOne(['usuario_id' => $user['_id']]);
    if ($wishlist && !empty($wishlist['juegos'])) {
        foreach ($wishlist['juegos'] as $item) {
            if ((string)$item['juego_id'] === (string)$gameObjId) {
                jsonError('Este juego ya está en tu lista de deseos');
            }
        }
    }
    
    // Calcular precio actual
    $precio = (float)$juego['precio'];
    $descuento = (int)($juego['descuento'] ?? 0);
    $precioFinal = $descuento > 0 ? $precio * (1 - $descuento / 100) : $precio;
    
    $newItem = [
        'juego_id' => $gameObjId,
        'titulo' => $juego['titulo'],
        'precio_actual' => round($precioFinal, 2),
        'descuento' => $descuento,
        'agregado_en' => new UTCDateTime(),
    ];
    
    if ($wishlist) {
        $wishlists->updateOne(
            ['_id' => $wishlist['_id']],
            [
                '$push' => ['juegos' => $newItem],
                '$inc' => ['total_juegos' => 1],
                '$set' => ['updated_at' => new UTCDateTime()],
            ]
        );
    } else {
        $wishlists->insertOne([
            'usuario_id' => $user['_id'],
            'juegos' => [$newItem],
            'total_juegos' => 1,
            'updated_at' => new UTCDateTime(),
        ]);
    }
    
    // Actualizar stats del usuario
    $db->getCollection('usuarios')->updateOne(
        ['_id' => $user['_id']],
        ['$inc' => ['stats.wishlist_count' => 1]]
    );
    
    jsonSuccess(null, '¡Añadido a tu lista de deseos!');
}

function removeFromWishlist(): void
{
    $user = requireLogin();
    $gameId = clean($_POST['game_id'] ?? '');
    
    $gameObjId = toObjectId($gameId);
    if (!$gameObjId) {
        jsonError('ID de juego no válido');
    }
    
    $db = Database::getInstance();
    $wishlists = $db->getCollection('wishlists');
    
    $result = $wishlists->updateOne(
        ['usuario_id' => $user['_id']],
        [
            '$pull' => ['juegos' => ['juego_id' => $gameObjId]],
            '$inc' => ['total_juegos' => -1],
            '$set' => ['updated_at' => new UTCDateTime()],
        ]
    );
    
    if ($result->getModifiedCount() === 0) {
        jsonError('El juego no estaba en tu lista de deseos');
    }
    
    // Actualizar stats del usuario
    $db->getCollection('usuarios')->updateOne(
        ['_id' => $user['_id']],
        ['$inc' => ['stats.wishlist_count' => -1]]
    );
    
    jsonSuccess(null, 'Eliminado de la lista de deseos');
}

function getWishlist(): void
{
    $user = requireLogin();
    
    $db = Database::getInstance();
    $wishlists = $db->getCollection('wishlists');
    $bibliotecas = $db->getCollection('bibliotecas');
    
    $wishlist = $wishlists->findOne(['usuario_id' => $user['_id']]);
    
    if (!$wishlist || empty($wishlist['juegos'])) {
        jsonSuccess(['wishlist' => []]);
    }
    
    // Ver qué juegos ya están en la biblioteca del usuario
    $biblioteca = $bibliotecas->findOne(['usuario_id' => $user['_id']]);
    $juegosEnBiblioteca = [];
    if ($biblioteca && !empty($biblioteca['juegos'])) {
        foreach ($biblioteca['juegos'] as $item) {
            $juegosEnBiblioteca[] = (string)$item['juego_id'];
        }
    }
    
    $items = [];
    foreach ($wishlist['juegos'] as $item) {
        $gameIdStr = (string)$item['juego_id'];
        $items[] = [
            'wishlist_id' => $gameIdStr, // Usamos el game_id como identificador
            'juego_id' => $gameIdStr,
            'game_id' => $gameIdStr,
            'titulo' => $item['titulo'],
            'precio_actual' => (float)$item['precio_actual'],
            'descuento' => (int)$item['descuento'],
            'agregado_en' => $item['agregado_en']->toDateTime()->format('Y-m-d H:i:s'),
            'en_biblioteca' => in_array($gameIdStr, $juegosEnBiblioteca, true) ? 1 : 0,
        ];
    }
    
    jsonSuccess(['wishlist' => $items]);
}

function checkWishlistStatus(): void
{
    if (!isset($_SESSION['user_id'])) {
        jsonSuccess(['in_wishlist' => false]);
    }
    
    $gameId = clean($_POST['game_id'] ?? '');
    $gameObjId = toObjectId($gameId);
    
    if (!$gameObjId) {
        jsonError('ID de juego no válido');
    }
    
    $db = Database::getInstance();
    $wishlists = $db->getCollection('wishlists');
    
    $wishlist = $wishlists->findOne([
        'usuario_id' => toObjectId($_SESSION['user_id']),
        'juegos.juego_id' => $gameObjId,
    ]);
    
    jsonSuccess(['in_wishlist' => $wishlist !== null]);
}

function moveToCart(): void
{
    $user = requireLogin();
    $gameId = clean($_POST['game_id'] ?? '');
    
    $gameObjId = toObjectId($gameId);
    if (!$gameObjId) {
        jsonError('ID de juego no válido');
    }
    
    $db = Database::getInstance();
    $juegos = $db->getCollection('juegos');
    $bibliotecas = $db->getCollection('bibliotecas');
    $carritos = $db->getCollection('carritos');
    $wishlists = $db->getCollection('wishlists');
    
    // Verificar que el juego existe
    $juego = $juegos->findOne(['_id' => $gameObjId, 'estado' => 'aprobado']);
    if (!$juego) {
        jsonError('Juego no disponible');
    }
    
    // Verificar que no lo tenga ya
    $biblioteca = $bibliotecas->findOne([
        'usuario_id' => $user['_id'],
        'juegos.juego_id' => $gameObjId,
    ]);
    if ($biblioteca) {
        jsonError('Ya posees este juego en tu biblioteca');
    }
    
    // Verificar que no esté ya en el carrito
    $carrito = $carritos->findOne(['usuario_id' => $user['_id']]);
    if ($carrito && !empty($carrito['items'])) {
        foreach ($carrito['items'] as $item) {
            if ((string)$item['juego_id'] === (string)$gameObjId) {
                jsonError('Este juego ya está en tu carrito');
            }
        }
    }
    
    // Calcular precio
    $precio = (float)$juego['precio'];
    $descuento = (int)($juego['descuento'] ?? 0);
    $precioFinal = $descuento > 0 ? $precio * (1 - $descuento / 100) : $precio;
    
    $newItem = [
        'juego_id' => $gameObjId,
        'titulo' => $juego['titulo'],
        'precio_final' => round($precioFinal, 2),
        'cantidad' => 1,
        'agregado_en' => new UTCDateTime(),
    ];
    
    // Añadir al carrito
    if ($carrito) {
        $carritos->updateOne(
            ['_id' => $carrito['_id']],
            [
                '$push' => ['items' => $newItem],
                '$set' => ['updated_at' => new UTCDateTime()],
            ]
        );
    } else {
        $carritos->insertOne([
            'usuario_id' => $user['_id'],
            'items' => [$newItem],
            'updated_at' => new UTCDateTime(),
        ]);
    }
    
    // Eliminar de wishlist
    $wishlists->updateOne(
        ['usuario_id' => $user['_id']],
        [
            '$pull' => ['juegos' => ['juego_id' => $gameObjId]],
            '$inc' => ['total_juegos' => -1],
            '$set' => ['updated_at' => new UTCDateTime()],
        ]
    );
    
    // Actualizar stats del usuario
    $db->getCollection('usuarios')->updateOne(
        ['_id' => $user['_id']],
        ['$inc' => ['stats.wishlist_count' => -1]]
    );
    
    jsonSuccess(null, '¡Juego movido al carrito exitosamente!');
}
