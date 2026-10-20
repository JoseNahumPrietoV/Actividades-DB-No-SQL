<?php
/**
 * ============================================================
 * BACKEND PRINCIPAL: process.php (versión MongoDB)
 * ============================================================
 * Proyecto: TechnoGames
 * Maneja todas las acciones AJAX del frontend
 * 
 * Acciones soportadas:
 *   - login               → autenticar usuario
 *   - register            → registrar usuario (user o dev)
 *   - logout              → cerrar sesión
 *   - get_games           → listar juegos aprobados
 *   - search_games        → buscar juegos
 *   - add_to_cart         → añadir al carrito
 *   - get_cart            → obtener carrito
 *   - get_cart_count      → contador del carrito
 *   - remove_from_cart    → eliminar del carrito
 *   - clear_cart          → vaciar carrito
 *   - purchase_game       → compra directa
 *   - get_library         → biblioteca del usuario
 *   - add_review          → añadir reseña
 *   - get_reviews         → obtener reseñas de un juego
 */

require_once __DIR__ . '/config/helpers.php';
configureSession();
session_start();

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\Regex;

// ============================================
// ROUTER
// ============================================

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    jsonError('Acción no especificada');
}

try {
    switch ($action) {
        case 'login':              loginUser();           break;
        case 'register':           registerUser();        break;
        case 'logout':             logoutUser();          break;
        case 'get_games':          getGames();            break;
        case 'search_games':       searchGames();         break;
        case 'add_to_cart':        addToCart();           break;
        case 'get_cart':           getCart();             break;
        case 'get_cart_count':     getCartCount();        break;
        case 'remove_from_cart':   removeFromCart();      break;
        case 'clear_cart':         clearCart();           break;
        case 'purchase_game':      purchaseGame();        break;
        case 'get_library':        getLibrary();          break;
        case 'add_review':         addReview();           break;
        case 'get_reviews':        getReviews();          break;
        default:
            jsonError("Acción '$action' no válida");
    }
} catch (Exception $e) {
    error_log("process.php error [$action]: " . $e->getMessage());
    jsonError('Error interno: ' . $e->getMessage(), 500);
}

// ============================================
// AUTENTICACIÓN
// ============================================

function loginUser(): void
{
    $email = cleanEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        jsonError('Complete todos los campos');
    }
    
    $db = Database::getInstance();
    $usuarios = $db->getCollection('usuarios');
    
    $user = $usuarios->findOne(['email' => $email, 'is_active' => true]);
    
    if (!$user) {
        auditLog('login_failed', ['email' => $email], null);
        jsonError('Credenciales incorrectas');
    }
    
    // Cuenta bloqueada
    if (!empty($user['locked_until']) && $user['locked_until']->toDateTime() > new DateTime()) {
        jsonError('Cuenta bloqueada temporalmente. Intenta más tarde.');
    }
    
    // Verificar contraseña
    if (!password_verify($password, $user['password_hash'])) {
        $intentos = ($user['login_attempts'] ?? 0) + 1;
        $update = ['$set' => ['login_attempts' => $intentos]];
        
        if ($intentos >= 5) {
            $update['$set']['locked_until'] = new UTCDateTime((time() + 900) * 1000);
        }
        
        $usuarios->updateOne(['_id' => $user['_id']], $update);
        auditLog('login_failed', ['email' => $email, 'attempts' => $intentos], (string)$user['_id']);
        
        jsonError('Credenciales incorrectas');
    }
    
    // Login exitoso
    $usuarios->updateOne(
        ['_id' => $user['_id']],
        ['$set' => [
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login' => new UTCDateTime(),
        ]]
    );
    
    session_regenerate_id(true);
    $_SESSION['user_id'] = (string)$user['_id'];
    $_SESSION['user_name'] = $user['nombre_usuario'];
    $_SESSION['user_role'] = $user['rol'];
    $_SESSION['login_time'] = time();
    
    auditLog('login_success', [], (string)$user['_id']);
    
    jsonSuccess([
        'user' => [
            'id' => (string)$user['_id'],
            'name' => $user['nombre_usuario'],
            'role' => $user['rol'],
        ]
    ], 'Login exitoso');
}

function registerUser(): void
{
    $username = clean($_POST['username'] ?? '');
    $email = cleanEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    // Validaciones
    if (empty($username) || empty($email) || empty($password)) {
        jsonError('Complete todos los campos');
    }
    if ($password !== $confirm) {
        jsonError('Las contraseñas no coinciden');
    }
    if (strlen($password) < 8) {
        jsonError('La contraseña debe tener al menos 8 caracteres');
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        jsonError('La contraseña debe incluir mayúscula, minúscula y número');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Email inválido');
    }
    
    // Rol permitido (nunca permitir admin desde registro público)
    $allowedRoles = ['user', 'dev'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'user';
    }
    
    $db = Database::getInstance();
    $usuarios = $db->getCollection('usuarios');
    
    // Verificar duplicados
    $existing = $usuarios->findOne([
        '$or' => [
            ['email' => $email],
            ['nombre_usuario' => $username],
        ]
    ]);
    
    if ($existing) {
        if ($existing['email'] === $email) {
            jsonError('El email ya está registrado');
        }
        jsonError('El nombre de usuario ya existe');
    }
    
    // Crear usuario
    $result = $usuarios->insertOne([
        'nombre_usuario' => $username,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
        'rol' => $role,
        'created_at' => new UTCDateTime(),
        'last_login' => null,
        'is_active' => true,
        'login_attempts' => 0,
        'locked_until' => null,
        'stats' => [
            'total_purchases' => 0,
            'total_spent' => 0.0,
            'wishlist_count' => 0,
        ],
    ]);
    
    $userId = (string)$result->getInsertedId();
    
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $username;
    $_SESSION['user_role'] = $role;
    
    auditLog('register', ['email' => $email, 'role' => $role], $userId);
    
    $message = $role === 'dev'
        ? '¡Cuenta de desarrollador creada! Ya puedes subir tus juegos.'
        : '¡Cuenta creada exitosamente! Bienvenido a TechnoGames.';
    
    jsonSuccess([
        'user' => ['id' => $userId, 'name' => $username, 'role' => $role]
    ], $message);
}

function logoutUser(): void
{
    if (isset($_SESSION['user_id'])) {
        auditLog('logout', [], $_SESSION['user_id']);
    }
    session_destroy();
    jsonSuccess(null, 'Sesión cerrada');
}

// ============================================
// JUEGOS
// ============================================

function getGames(): void
{
    $category = $_POST['category'] ?? 'all';
    $limit = (int)($_POST['limit'] ?? 0);
    $sort = $_POST['sort'] ?? 'recent';
    
    $db = Database::getInstance();
    $juegos = $db->getCollection('juegos');
    
    $filter = ['estado' => 'aprobado'];
    if ($category !== 'all') {
        $filter['categoria'] = $category;
    }
    
    $sortOption = match($sort) {
        'price_asc'   => ['precio_final' => 1],
        'price_desc'  => ['precio_final' => -1],
        'name_asc'    => ['titulo' => 1],
        'discount'    => ['descuento' => -1],
        default       => ['fecha_agregado' => -1],
    };
    
    $options = ['sort' => $sortOption];
    if ($limit > 0) $options['limit'] = $limit;
    
    $cursor = $juegos->find($filter, $options);
    $games = [];
    
    foreach ($cursor as $g) {
        $g['id'] = (string)$g['_id'];
        unset($g['_id']);
        $games[] = bsonToArray($g);
    }
    
    jsonSuccess(['games' => $games]);
}

function searchGames(): void
{
    $searchTerm = clean($_POST['search_term'] ?? '');
    
    if (empty($searchTerm)) {
        jsonError('Término de búsqueda vacío');
    }
    
    $db = Database::getInstance();
    $juegos = $db->getCollection('juegos');
    
    $regex = new Regex(preg_quote($searchTerm), 'i');
    
    $cursor = $juegos->find([
        'estado' => 'aprobado',
        '$or' => [
            ['titulo' => $regex],
            ['descripcion' => $regex],
            ['categoria' => $regex],
            ['tags' => $regex],
        ]
    ], ['limit' => 50]);
    
    $games = [];
    foreach ($cursor as $g) {
        $g['id'] = (string)$g['_id'];
        unset($g['_id']);
        $games[] = bsonToArray($g);
    }
    
    jsonSuccess(['games' => $games]);
}

// ============================================
// CARRITO
// ============================================

function addToCart(): void
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
    
    // Verificar que el juego existe y está aprobado
    $juego = $juegos->findOne(['_id' => $gameObjId, 'estado' => 'aprobado']);
    if (!$juego) {
        jsonError('Juego no disponible');
    }
    
    // Verificar si ya está en la biblioteca del usuario
    $biblioteca = $bibliotecas->findOne([
        'usuario_id' => $user['_id'],
        'juegos.juego_id' => $gameObjId,
    ]);
    if ($biblioteca) {
        jsonError('Ya posees este juego en tu biblioteca');
    }
    
    // Buscar o crear carrito del usuario
    $carrito = $carritos->findOne(['usuario_id' => $user['_id']]);
    
    // Verificar si el juego ya está en el carrito
    if ($carrito) {
        foreach ($carrito['items'] as $item) {
            if ((string)$item['juego_id'] === (string)$gameObjId) {
                jsonError('Este juego ya está en tu carrito');
            }
        }
    }
    
    // Calcular precio final
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
    
    if ($carrito) {
        // Actualizar carrito existente
        $carritos->updateOne(
            ['_id' => $carrito['_id']],
            [
                '$push' => ['items' => $newItem],
                '$set' => ['updated_at' => new UTCDateTime()],
            ]
        );
    } else {
        // Crear nuevo carrito
        $carritos->insertOne([
            'usuario_id' => $user['_id'],
            'items' => [$newItem],
            'updated_at' => new UTCDateTime(),
        ]);
    }
    
    auditLog('add_to_cart', ['game_id' => $gameId], (string)$user['_id']);
    
    jsonSuccess(null, '¡Juego agregado al carrito!');
}

function getCart(): void
{
    $user = requireLogin();
    
    $db = Database::getInstance();
    $carritos = $db->getCollection('carritos');
    
    $carrito = $carritos->findOne(['usuario_id' => $user['_id']]);
    
    if (!$carrito || empty($carrito['items'])) {
        jsonSuccess(['cart_items' => [], 'total' => 0]);
    }
    
    $items = [];
    $total = 0;
    
    foreach ($carrito['items'] as $item) {
        $precioFinal = (float)$item['precio_final'];
        $cantidad = 1;
        $itemTotal = $precioFinal * $cantidad;
        $total += $itemTotal;
        
        $items[] = [
            'id' => (string)$item['juego_id'],
            'game_id' => (string)$item['juego_id'],
            'title' => $item['titulo'],
            'final_price' => $precioFinal,
            'quantity' => $cantidad,
            'item_total' => $itemTotal,
        ];
    }
    
    jsonSuccess(['cart_items' => $items, 'total' => round($total, 2)]);
}

function getCartCount(): void
{
    if (!isset($_SESSION['user_id'])) {
        jsonSuccess(['count' => 0]);
    }
    
    $db = Database::getInstance();
    $carrito = $db->getCollection('carritos')->findOne([
        'usuario_id' => toObjectId($_SESSION['user_id'])
    ]);
    
    $count = $carrito ? count($carrito['items'] ?? []) : 0;
    jsonSuccess(['count' => $count]);
}

function removeFromCart(): void
{
    $user = requireLogin();
    $gameId = clean($_POST['game_id'] ?? $_POST['cart_id'] ?? '');
    
    $gameObjId = toObjectId($gameId);
    if (!$gameObjId) {
        jsonError('ID de juego no válido');
    }
    
    $db = Database::getInstance();
    $carritos = $db->getCollection('carritos');
    
    $result = $carritos->updateOne(
        ['usuario_id' => $user['_id']],
        [
            '$pull' => ['items' => ['juego_id' => $gameObjId]],
            '$set' => ['updated_at' => new UTCDateTime()],
        ]
    );
    
    if ($result->getModifiedCount() === 0) {
        jsonError('El juego no estaba en tu carrito');
    }
    
    jsonSuccess(null, 'Juego eliminado del carrito');
}

function clearCart(): void
{
    $user = requireLogin();
    
    $db = Database::getInstance();
    $carritos = $db->getCollection('carritos');
    
    $carritos->updateOne(
        ['usuario_id' => $user['_id']],
        [
            '$set' => [
                'items' => [],
                'updated_at' => new UTCDateTime(),
            ]
        ]
    );
    
    jsonSuccess(null, 'Carrito vaciado exitosamente');
}

// ============================================
// COMPRAS
// ============================================

function purchaseGame(): void
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
    
    // Verificar juego
    $juego = $juegos->findOne(['_id' => $gameObjId, 'estado' => 'aprobado']);
    if (!$juego) {
        jsonError('Juego no disponible');
    }
    
    // Verificar que no lo tenga ya
    $yaLoTiene = $bibliotecas->findOne([
        'usuario_id' => $user['_id'],
        'juegos.juego_id' => $gameObjId,
    ]);
    if ($yaLoTiene) {
        jsonError('Ya posees este juego');
    }
    
    // Calcular precio
    $precio = (float)$juego['precio'];
    $descuento = (int)($juego['descuento'] ?? 0);
    $precioFinal = $descuento > 0 ? $precio * (1 - $descuento / 100) : $precio;
    $comision = round($precioFinal * 0.3, 2);
    
    // Añadir a biblioteca (upsert)
    $bibliotecas->updateOne(
        ['usuario_id' => $user['_id']],
        [
            '$push' => ['juegos' => [
                'juego_id' => $gameObjId,
                'titulo' => $juego['titulo'],
                'fecha_adquisicion' => new UTCDateTime(),
            ]],
            '$inc' => ['total_juegos' => 1],
            '$setOnInsert' => ['usuario_id' => $user['_id']],
        ],
        ['upsert' => true]
    );
    
    // Eliminar del carrito si existe
    $carritos->updateOne(
        ['usuario_id' => $user['_id']],
        [
            '$pull' => ['items' => ['juego_id' => $gameObjId]],
            '$set' => ['updated_at' => new UTCDateTime()],
        ]
    );
    
    auditLog('purchase_game', [
        'game_id' => $gameId,
        'precio' => $precioFinal,
        'comision' => $comision,
    ], (string)$user['_id']);
    
    jsonSuccess(null, '¡Compra realizada con éxito!');
}

function getLibrary(): void
{
    $user = requireLogin();
    
    $db = Database::getInstance();
    $bibliotecas = $db->getCollection('bibliotecas');
    
    $biblioteca = $bibliotecas->findOne(['usuario_id' => $user['_id']]);
    
    $library = [];
    if ($biblioteca && !empty($biblioteca['juegos'])) {
        foreach ($biblioteca['juegos'] as $item) {
            $library[] = bsonToArray([
                'id' => (string)$item['juego_id'],
                'titulo' => $item['titulo'],
                'fecha_adquisicion' => $item['fecha_adquisicion'],
            ]);
        }
    }
    
    jsonSuccess(['library' => $library]);
}

// ============================================
// RESEÑAS
// ============================================

function addReview(): void
{
    $user = requireLogin();
    
    $gameId = clean($_POST['game_id'] ?? '');
    $calificacion = (int)($_POST['calificacion'] ?? 0);
    $comentario = clean($_POST['comentario'] ?? '');
    
    if ($calificacion < 1 || $calificacion > 5) {
        jsonError('Calificación debe ser entre 1 y 5');
    }
    if (empty($comentario)) {
        jsonError('El comentario no puede estar vacío');
    }
    
    $gameObjId = toObjectId($gameId);
    if (!$gameObjId) {
        jsonError('ID de juego no válido');
    }
    
    $db = Database::getInstance();
    $bibliotecas = $db->getCollection('bibliotecas');
    $resenas = $db->getCollection('resenas');
    
    // Verificar que el usuario tenga el juego
    $tieneJuego = $bibliotecas->findOne([
        'usuario_id' => $user['_id'],
        'juegos.juego_id' => $gameObjId,
    ]);
    
    if (!$tieneJuego) {
        jsonError('Debes tener el juego para poder reseñarlo');
    }
    
    // Upsert de reseña (una por usuario+juego)
    $resenas->updateOne(
        [
            'juego_id' => $gameObjId,
            'usuario_id' => $user['_id'],
        ],
        [
            '$set' => [
                'usuario_nombre' => $user['nombre_usuario'],
                'calificacion' => $calificacion,
                'comentario' => $comentario,
                'fecha_creacion' => new UTCDateTime(),
            ],
            '$setOnInsert' => [
                'juego_id' => $gameObjId,
                'usuario_id' => $user['_id'],
                'votos_util' => 0,
            ],
        ],
        ['upsert' => true]
    );
    
    jsonSuccess(null, 'Reseña guardada exitosamente');
}

function getReviews(): void
{
    $gameId = clean($_POST['game_id'] ?? '');
    $gameObjId = toObjectId($gameId);
    
    if (!$gameObjId) {
        jsonError('ID de juego no válido');
    }
    
    $db = Database::getInstance();
    $resenas = $db->getCollection('resenas');
    
    $cursor = $resenas->find(
        ['juego_id' => $gameObjId],
        ['sort' => ['fecha_creacion' => -1], 'limit' => 50]
    );
    
    $reviews = [];
    foreach ($cursor as $r) {
        $reviews[] = bsonToArray($r);
    }
    
    jsonSuccess(['reviews' => $reviews]);
}
