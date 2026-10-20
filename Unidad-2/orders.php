<?php
require_once __DIR__ . '/config/helpers.php';
configureSession();
session_start();

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance();
$userObjId = new ObjectId($_SESSION['user_id']);

// ============================================
// OBTENER HISTORIAL DE PEDIDOS
// ============================================
$orders = [];

try {
    $cursor = $db->getCollection('ordenes')->find(
        ['usuario_id' => $userObjId],
        ['sort' => ['fecha_compra' => -1]]
    );
    
    foreach ($cursor as $doc) {
        $items = $doc['items'] ?? [];
        
        // Extraer detalles de items (títulos y precios)
        $itemsDetails = [];
        foreach ($items as $item) {
            $itemsDetails[] = [
                'titulo' => $item['titulo'] ?? 'Sin título',
                'precio_venta' => (float)($item['precio_final'] ?? 0),
            ];
        }
        
        $orders[] = [
            'order_id' => $doc['order_id'] ?? '',
            'fecha' => isset($doc['fecha_compra'])
                ? $doc['fecha_compra']->toDateTime()->format('Y-m-d H:i:s')
                : date('Y-m-d H:i:s'),
            'items' => count($items),
            'total' => (float)($doc['total'] ?? 0),
            'items_details' => $itemsDetails,
        ];
    }
} catch (Exception $e) {
    error_log("Error obteniendo órdenes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - TechnoGames</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .orders-header {
            margin-bottom: 30px;
        }
        
        .orders-title {
            font-size: 32px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .orders-empty {
            text-align: center;
            padding: 50px;
            background: var(--bg-card);
            border-radius: 12px;
            margin-top: 50px;
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
        
        .order-card {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .order-id {
            font-size: 18px;
            font-weight: bold;
            color: var(--accent-primary);
        }
        
        .order-date {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .order-total {
            font-size: 18px;
            font-weight: bold;
            color: var(--price-color);
        }
        
        .order-items {
            margin-top: 20px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            flex: 1;
            color: var(--text-primary);
        }
        
        .item-price {
            color: var(--text-secondary);
            min-width: 100px;
            text-align: right;
        }
        
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .items-count {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .btn-view-details {
            background: rgba(0, 116, 228, 0.1);
            color: var(--accent-primary);
            border: 1px solid rgba(0, 116, 228, 0.3);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-view-details:hover {
            background: rgba(0, 116, 228, 0.2);
            border-color: var(--accent-primary);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 20px;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: var(--accent-primary);
        }
        
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .order-footer {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
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
                <i class="fas fa-store"></i> Tienda
            </button>
            <button onclick="window.location.href='library.php'">
                <i class="fas fa-book"></i> Biblioteca
            </button>
        </div>
    </header>

    <div class="orders-container">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Volver a la tienda
        </a>
        
        <div class="orders-header">
            <h1 class="orders-title">
                <i class="fas fa-history"></i> Mis Pedidos
            </h1>
        </div>
        
        <?php if (empty($orders)): ?>
            <div class="orders-empty">
                <div class="empty-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h2 class="empty-message">Aún no has realizado ningún pedido</h2>
                <button onclick="window.location.href='index.php'" class="btn-checkout">
                    <i class="fas fa-store"></i> Explorar Juegos
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <div class="order-id">Pedido #<?php echo htmlspecialchars($order['order_id']); ?></div>
                        <div class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['fecha'])); ?></div>
                    </div>
                    <div class="order-total">$<?php echo number_format($order['total'], 2); ?></div>
                </div>
                
                <div class="order-items">
                    <?php foreach ($order['items_details'] as $item): ?>
                    <div class="order-item">
                        <div class="item-name"><?php echo htmlspecialchars($item['titulo']); ?></div>
                        <div class="item-price">$<?php echo number_format($item['precio_venta'], 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-footer">
                    <div class="items-count"><?php echo $order['items']; ?> producto(s)</div>
                    <div>
                        <button class="btn-view-details" onclick="window.location.href='order_details.php?id=<?php echo $order['order_id']; ?>'">
                            <i class="fas fa-eye"></i> Ver Detalles
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
