<?php
require_once __DIR__ . '/config/helpers.php';
configureSession();
session_start();

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_message'] = 'Debes iniciar sesión para acceder al carrito';
    header('Location: index.php');
    exit();
}

$db = Database::getInstance();
$userObjId = new ObjectId($_SESSION['user_id']);
$carritosCollection = $db->getCollection('carritos');

// ============================================
// PROCESAR ACCIONES POST
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eliminar item del carrito
    if (isset($_POST['remove_item'])) {
        $gameId = clean($_POST['cart_id'] ?? $_POST['game_id'] ?? '');
        $gameObjId = toObjectId($gameId);
        
        if ($gameObjId) {
            $carritosCollection->updateOne(
                ['usuario_id' => $userObjId],
                [
                    '$pull' => ['items' => ['juego_id' => $gameObjId]],
                    '$set' => ['updated_at' => new UTCDateTime()],
                ]
            );
        }
        
        header('Location: cart.php');
        exit();
    }
}

// ============================================
// OBTENER ITEMS DEL CARRITO
// ============================================

$cartItems = [];
$subtotal = 0;

$carrito = $carritosCollection->findOne(['usuario_id' => $userObjId]);

if ($carrito && !empty($carrito['items'])) {
    foreach ($carrito['items'] as $item) {
        $precioFinal = (float)$item['precio_final'];
        $subtotal += $precioFinal;
        
        $cartItems[] = [
            'id' => (string)$item['juego_id'],
            'game_id' => (string)$item['juego_id'],
            'title' => $item['titulo'],
            'price' => $precioFinal,          // Precio unitario final (con descuento)
            'discount' => 0,                  // Ya está aplicado en precio_final
            'final_price' => $precioFinal,
            'quantity' => 1,
            'item_total' => $precioFinal,
            'image' => '',                    // Se rellenará abajo
        ];
    }
    
    // Enriquecer con imagen y precio original desde la colección de juegos
    $juegosCollection = $db->getCollection('juegos');
    foreach ($cartItems as &$item) {
        $juego = $juegosCollection->findOne(['_id' => toObjectId($item['game_id'])]);
        if ($juego) {
            $item['image'] = $juego['imagen_url'] ?? '';
            $item['price'] = (float)$juego['precio'];
            $item['discount'] = (int)($juego['descuento'] ?? 0);
        }
    }
    unset($item);
}

// Calcular impuestos y total
$tax = round($subtotal * 0.16, 2);
$total = round($subtotal + $tax, 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - TechnoGames</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .cart-header {
            margin-bottom: 30px;
        }
        
        .cart-title {
            font-size: 32px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .cart-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 40px;
        }
        
        .cart-items {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .cart-empty {
            text-align: center;
            padding: 50px;
        }
        
        .empty-icon {
            font-size: 64px;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        
        .empty-message {
            color: var(--text-secondary);
            margin-bottom: 30px;
            font-size: 18px;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 20px;
            padding: 25px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            align-items: center;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            background-size: cover;
            background-position: center;
            cursor: pointer;
        }
        
        .item-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .item-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            cursor: pointer;
        }
        
        .item-title:hover {
            color: var(--accent-primary);
        }
        
        .item-price {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .original-price {
            text-decoration: line-through;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .final-price {
            font-size: 18px;
            font-weight: bold;
            color: var(--price-color);
        }
        
        .discount-badge {
            background: var(--sale);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .item-total {
            font-size: 18px;
            font-weight: bold;
            color: var(--text-primary);
            min-width: 100px;
            text-align: right;
        }
        
        .item-remove {
            color: var(--text-secondary);
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s;
        }
        
        .item-remove:hover {
            color: #ff3333;
        }
        
        .cart-summary {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            position: sticky;
            top: 20px;
            height: fit-content;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .summary-title {
            font-size: 20px;
            margin-bottom: 25px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            color: var(--text-secondary);
        }
        
        .summary-row.total {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-primary);
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-checkout {
            width: 100%;
            padding: 16px;
            background: var(--accent-primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-checkout:hover {
            background: #0066CC;
        }
        
        .btn-continue {
            width: 100%;
            padding: 12px;
            background: transparent;
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-continue:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--accent-primary);
        }
        
        .cart-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        @media (max-width: 992px) {
            .cart-content {
                grid-template-columns: 1fr;
            }
            
            .cart-summary {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 15px;
            }
            
            .item-image {
                margin: 0 auto;
            }
            
            .item-total {
                text-align: center;
            }
            
            .item-remove {
                position: absolute;
                top: 15px;
                right: 15px;
            }
            
            .cart-item {
                position: relative;
            }
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
                <i class="fas fa-store"></i> Seguir Comprando
            </button>
            <?php if (isset($_SESSION['user_id'])): ?>
                <button onclick="window.location.href='library.php'">
                    <i class="fas fa-book"></i> Mi Biblioteca
                </button>
            <?php endif; ?>
        </div>
    </header>

    <div class="cart-container">
        <div class="cart-header">
            <h1 class="cart-title">
                <i class="fas fa-shopping-cart"></i> Mi Carrito
            </h1>
        </div>
        
        <?php if (empty($cartItems)): ?>
            <div class="cart-empty">
                <div class="empty-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2 class="empty-message">Tu carrito está vacío</h2>
                <button onclick="window.location.href='index.php'" class="btn-checkout">
                    <i class="fas fa-store"></i> Explorar Juegos
                </button>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <!-- Lista de items -->
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <div class="item-image" 
                             style="background-image: url('<?php echo $item['image']; ?>')"
                             onclick="window.location.href='game_detail.php?id=<?php echo $item['game_id']; ?>'">
                        </div>
                        
                        <div class="item-details">
                            <h3 class="item-title" 
                                onclick="window.location.href='game_detail.php?id=<?php echo $item['game_id']; ?>'">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </h3>
                            
                            <div class="item-price">
                                <?php if ($item['discount'] > 0): ?>
                                    <span class="original-price">$<?php echo number_format($item['price'], 2); ?></span>
                                    <span class="discount-badge">-<?php echo $item['discount']; ?>%</span>
                                <?php endif; ?>
                                <span class="final-price">$<?php echo number_format($item['final_price'], 2); ?></span>
                            </div>
                        </div>
                        
                        <div class="item-total">
                            $<?php echo number_format($item['item_total'], 2); ?>
                        </div>
                        
                        <button class="item-remove" 
                                onclick="removeItem(<?php echo $item['id']; ?>)"
                                title="Eliminar del carrito">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-actions">
                        <button onclick="clearCart()" class="btn-continue" style="background: rgba(255, 51, 51, 0.1); color: #ff3333;">
                            <i class="fas fa-trash-alt"></i> Vaciar Carrito
                        </button>
                        <button onclick="window.location.href='index.php'" class="btn-continue">
                            <i class="fas fa-plus"></i> Agregar Más Juegos
                        </button>
                    </div>
                </div>
                
                <!-- Resumen del pedido -->
                <div class="cart-summary">
                    <h2 class="summary-title"><i class="fas fa-receipt"></i> Resumen</h2>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>IVA (16%)</span>
                        <span>$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <button onclick="window.location.href='checkout.php'" class="btn-checkout">
                        <i class="fas fa-lock"></i> Proceder al Pago
                    </button>
                    
                    <button onclick="window.location.href='index.php'" class="btn-continue">
                        <i class="fas fa-arrow-left"></i> Continuar Comprando
                    </button>
                    
                    <div style="margin-top: 20px; font-size: 14px; color: var(--text-secondary);">
                        <p><i class="fas fa-shield-alt"></i> Compra 100% segura</p>
                        <p><i class="fas fa-sync-alt"></i> Reembolsos en 30 días</p>
                        <p><i class="fas fa-headset"></i> Soporte 24/7</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Eliminar item
        function removeItem(cartId) {
            if (confirm('¿Estás seguro de que deseas eliminar este juego del carrito?')) {
                const formData = new FormData();
                formData.append('remove_item', '1');
                formData.append('cart_id', cartId);
                
                fetch('cart.php', {
                    method: 'POST',
                    body: formData
                })
                .then(() => {
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar el item');
                });
            }
        }
        
        // Vaciar carrito
        function clearCart() {
            if (confirm('¿Estás seguro de que deseas vaciar tu carrito?')) {
                fetch('process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=clear_cart'
                })
                .then(response => {
                    // Verificar si la respuesta es JSON válido
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error al vaciar el carrito');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al vaciar el carrito: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>
