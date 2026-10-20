<?php
/**
 * ============================================================
 * PROCESAMIENTO DE CHECKOUT: process_checkout.php
 * ============================================================
 * Proyecto: TechnoGames (versión MongoDB)
 * 
 * Acciones:
 *   - process_order       → procesar la orden completa del carrito
 *   - get_order_history   → historial de órdenes del usuario
 *   - get_order_details   → detalles de una orden específica
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
        case 'process_order':      processOrder();        break;
        case 'get_order_history':  getOrderHistory();     break;
        case 'get_order_details':  getOrderDetails();     break;
        default:
            jsonError("Acción '$action' no válida");
    }
} catch (Exception $e) {
    error_log("process_checkout error [$action]: " . $e->getMessage());
    jsonError('Error interno: ' . $e->getMessage(), 500);
}

// ============================================
// PROCESAR ORDEN
// ============================================

function processOrder(): void
{
    $user = requireLogin();
    
    $paymentMethod = clean($_POST['payment_method'] ?? 'tarjeta');
    $nombre = clean($_POST['nombre'] ?? '');
    $apellido = clean($_POST['apellido'] ?? '');
    $email = cleanEmail($_POST['email'] ?? '');
    $telefono = clean($_POST['telefono'] ?? '');
    
    // Validaciones básicas
    if (empty($nombre) || empty($apellido) || empty($email) || empty($telefono)) {
        jsonError('Complete todos los campos del formulario');
    }
    
    // Validar método de pago
    $allowedMethods = ['tarjeta', 'paypal', 'transferencia'];
    if (!in_array($paymentMethod, $allowedMethods, true)) {
        jsonError('Método de pago no válido');
    }
    
    $db = Database::getInstance();
    $carritos = $db->getCollection('carritos');
    $bibliotecas = $db->getCollection('bibliotecas');
    $ordenes = $db->getCollection('ordenes');
    $juegos = $db->getCollection('juegos');
    
    // 1. Obtener carrito del usuario
    $carrito = $carritos->findOne(['usuario_id' => $user['_id']]);
    
    if (!$carrito || empty($carrito['items'])) {
        jsonError('Tu carrito está vacío');
    }
    
    // 2. Verificar que ningún juego ya esté en la biblioteca
    $biblioteca = $bibliotecas->findOne(['usuario_id' => $user['_id']]);
    $juegosEnBiblioteca = [];
    if ($biblioteca && !empty($biblioteca['juegos'])) {
        foreach ($biblioteca['juegos'] as $item) {
            $juegosEnBiblioteca[] = (string)$item['juego_id'];
        }
    }
    
    $items = [];
    $subtotal = 0;
    
    foreach ($carrito['items'] as $item) {
        $gameIdStr = (string)$item['juego_id'];
        
        // Verificar si ya lo tiene
        if (in_array($gameIdStr, $juegosEnBiblioteca, true)) {
            jsonError('Ya posees el juego: ' . $item['titulo']);
        }
        
        $precioFinal = (float)$item['precio_final'];
        $subtotal += $precioFinal;
        
        // Obtener info del juego para el desarrollador
        $juego = $juegos->findOne(['_id' => $item['juego_id']]);
        
        $items[] = [
            'juego_id' => $item['juego_id'],
            'titulo' => $item['titulo'],
            'cantidad' => 1,
            'precio_unitario' => $precioFinal,
            'descuento' => 0,
            'precio_final' => $precioFinal,
            'desarrollador_id' => $juego['desarrollador_id'] ?? null,
            'comision' => round($precioFinal * 0.3, 2),
        ];
    }
    
    // 3. Calcular impuestos y total
    $tax = round($subtotal * 0.16, 2);
    $total = round($subtotal + $tax, 2);
    
    // 4. Generar ID único de orden
    $orderId = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    
    // 5. Datos de pago (nunca guardar el número completo)
    $paymentInfo = [];
    if ($paymentMethod === 'tarjeta') {
        $cardNumber = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
        $paymentInfo = [
            'last_four' => substr($cardNumber, -4),
            'brand' => detectCardBrand($cardNumber),
        ];
    }
    
    // 6. Crear la orden
    try {
        $ordenes->insertOne([
            'order_id' => $orderId,
            'usuario_id' => $user['_id'],
            'usuario_email' => $user['email'],
            'usuario_nombre' => $user['nombre_usuario'],
            'items' => $items,
            'subtotal' => $subtotal,
            'impuesto' => $tax,
            'total' => $total,
            'metodo_pago' => $paymentMethod,
            'payment_info' => $paymentInfo,
            'estado' => 'completada',
            'cliente' => [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'telefono' => $telefono,
            ],
            'fecha_compra' => new UTCDateTime(),
        ]);
    } catch (Exception $e) {
        error_log("Error creando orden: " . $e->getMessage());
        jsonError('Error al crear la orden');
    }
    
    // 7. Añadir cada juego a la biblioteca del usuario
    foreach ($items as $item) {
        $bibliotecas->updateOne(
            ['usuario_id' => $user['_id']],
            [
                '$push' => ['juegos' => [
                    'juego_id' => $item['juego_id'],
                    'titulo' => $item['titulo'],
                    'fecha_adquisicion' => new UTCDateTime(),
                    'order_id' => $orderId,
                ]],
                '$inc' => ['total_juegos' => 1],
                '$setOnInsert' => ['usuario_id' => $user['_id']],
            ],
            ['upsert' => true]
        );
        
        // Actualizar contador de ventas del juego
        $juegos->updateOne(
            ['_id' => $item['juego_id']],
            ['$inc' => ['total_ventas' => 1]]
        );
    }
    
    // 8. Vaciar carrito
    $carritos->updateOne(
        ['usuario_id' => $user['_id']],
        ['$set' => ['items' => [], 'updated_at' => new UTCDateTime()]]
    );
    
    // 9. Registrar en auditoría
    auditLog('order_created', [
        'order_id' => $orderId,
        'total' => $total,
        'items_count' => count($items),
    ], (string)$user['_id']);
    
    jsonSuccess([
        'order_id' => $orderId,
        'total' => number_format($total, 2),
        'items_count' => count($items),
    ], '¡Compra realizada exitosamente!');
}

// ============================================
// HISTORIAL DE ÓRDENES
// ============================================

function getOrderHistory(): void
{
    $user = requireLogin();
    
    $db = Database::getInstance();
    $ordenes = $db->getCollection('ordenes');
    
    $cursor = $ordenes->find(
        ['usuario_id' => $user['_id']],
        ['sort' => ['fecha_compra' => -1]]
    );
    
    $orders = [];
    foreach ($cursor as $o) {
        $orders[] = [
            'order_id' => $o['order_id'],
            'total' => (float)$o['total'],
            'subtotal' => (float)$o['subtotal'],
            'impuesto' => (float)$o['impuesto'],
            'metodo_pago' => $o['metodo_pago'],
            'estado' => $o['estado'],
            'items_count' => count($o['items'] ?? []),
            'fecha_compra' => $o['fecha_compra']->toDateTime()->format('Y-m-d H:i:s'),
        ];
    }
    
    jsonSuccess(['orders' => $orders]);
}

function getOrderDetails(): void
{
    $user = requireLogin();
    $orderId = clean($_POST['order_id'] ?? '');
    
    if (empty($orderId)) {
        jsonError('ID de orden no válido');
    }
    
    $db = Database::getInstance();
    $ordenes = $db->getCollection('ordenes');
    
    $order = $ordenes->findOne([
        'order_id' => $orderId,
        'usuario_id' => $user['_id'],
    ]);
    
    if (!$order) {
        jsonError('Orden no encontrada', 404);
    }
    
    jsonSuccess(['order' => bsonToArray($order)]);
}

// ============================================
// HELPERS
// ============================================

function detectCardBrand(string $number): string
{
    $patterns = [
        'visa' => '/^4/',
        'mastercard' => '/^(5[1-5]|2[2-7])/',
        'amex' => '/^3[47]/',
        'discover' => '/^6/',
    ];
    
    foreach ($patterns as $brand => $pattern) {
        if (preg_match($pattern, $number)) {
            return $brand;
        }
    }
    
    return 'unknown';
}
