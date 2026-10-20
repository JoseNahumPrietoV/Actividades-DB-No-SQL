<?php
require_once __DIR__ . '/config/helpers.php';
configureSession();
session_start();

use MongoDB\BSON\ObjectId;

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$orderId = clean($_GET['id']);
$userObjId = new ObjectId($_SESSION['user_id']);

$db = Database::getInstance();

// ============================================
// OBTENER LA ORDEN
// ============================================
$orderDoc = $db->getCollection('ordenes')->findOne([
    'order_id' => $orderId,
    'usuario_id' => $userObjId,
]);

if (!$orderDoc) {
    header('Location: orders.php');
    exit();
}

// Convertir a array para usar en el HTML
$order = (array)$orderDoc;
$order['order_id'] = $orderDoc['order_id'];
$order['status'] = $order['estado'] ?? 'completada';
$order['payment_method'] = $order['metodo_pago'] ?? 'desconocido';
$order['created_at'] = isset($order['fecha_compra'])
    ? $order['fecha_compra']->toDateTime()->format('Y-m-d H:i:s')
    : date('Y-m-d H:i:s');
$order['tax'] = (float)($order['impuesto'] ?? 0);
$order['subtotal'] = (float)($order['subtotal'] ?? 0);
$order['total'] = (float)($order['total'] ?? 0);

// ============================================
// OBTENER ITEMS DE LA ORDEN (ya vienen embebidos)
// ============================================
$items = [];

foreach ($orderDoc['items'] ?? [] as $item) {
    // Obtener información completa del juego (imagen, categoría)
    $juego = $db->getCollection('juegos')->findOne(
        ['_id' => $item['juego_id']],
        ['projection' => ['imagen_url' => 1, 'categoria' => 1]]
    );
    
    $items[] = [
        'game_id' => (string)$item['juego_id'],
        'titulo' => $item['titulo'] ?? 'Sin título',
        'quantity' => (int)($item['cantidad'] ?? 1),
        'unit_price' => (float)($item['precio_unitario'] ?? 0),
        'discount' => (int)($item['descuento'] ?? 0),
        'final_price' => (float)($item['precio_final'] ?? 0),
        'imagen_url' => $juego['imagen_url'] ?? '',
        'categoria' => $juego['categoria'] ?? '',
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Pedido - TechnoGames</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .order-details-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .order-header {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .order-title {
            font-size: 28px;
            color: var(--text-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .meta-label {
            font-size: 14px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .meta-value {
            font-size: 16px;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-completed {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #FFC107;
        }
        
        .status-cancelled {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
        }
        
        .items-section {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .section-title {
            font-size: 22px;
            color: var(--text-primary);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .item-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .order-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: 10px;
            align-items: center;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            background-size: cover;
            background-position: center;
        }
        
        .item-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .item-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .item-meta {
            display: flex;
            gap: 15px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .item-price {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
        }
        
        .unit-price {
            font-size: 16px;
            color: var(--text-secondary);
        }
        
        .item-total {
            font-size: 18px;
            font-weight: bold;
            color: var(--price-color);
        }
        
        .order-summary {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-size: 20px;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--text-secondary);
            text-decoration: none;
            margin-bottom: 20px;
            padding: 10px 0;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: var(--accent-primary);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
        }
        
        .btn-primary {
            background: var(--accent-primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0066CC;
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--accent-primary);
        }
        
        @media (max-width: 768px) {
            .order-item {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 15px;
            }
            
            .item-image {
                margin: 0 auto;
            }
            
            .item-price {
                align-items: center;
            }
            
            .action-buttons {
                flex-direction: column;
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
            <button onclick="window.location.href='orders.php'">
                <i class="fas fa-arrow-left"></i> Volver a Pedidos
            </button>
            <button onclick="window.location.href='index.php'">
                <i class="fas fa-store"></i> Tienda
            </button>
        </div>
</header>

    <div class="order-details-container">
        <a href="orders.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Volver a Mis Pedidos
        </a>
        
        <!-- Encabezado del pedido -->
        <div class="order-header">
            <h1 class="order-title">
                <i class="fas fa-receipt"></i> Pedido #<?php echo htmlspecialchars($order['order_id']); ?>
            </h1>
            
            <div class="order-meta">
                <div class="meta-item">
                    <span class="meta-label">Fecha</span>
                    <span class="meta-value"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                </div>
                
                <div class="meta-item">
                    <span class="meta-label">Estado</span>
                    <span class="meta-value">
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php 
                            $statusText = [
                                'pending' => 'Pendiente',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado'
                            ];
                            echo $statusText[$order['status']] ?? ucfirst($order['status']);
                            ?>
                        </span>
                    </span>
                </div>
                
                <div class="meta-item">
                    <span class="meta-label">Método de Pago</span>
                    <span class="meta-value">
                        <?php 
                        $paymentMethods = [
                            'tarjeta' => 'Tarjeta de Crédito',
                            'paypal' => 'PayPal',
                            'transferencia' => 'Transferencia'
                        ];
                        echo $paymentMethods[$order['payment_method']] ?? $order['payment_method'];
                        ?>
                    </span>
                </div>
                
                <div class="meta-item">
                    <span class="meta-label">Total</span>
                    <span class="meta-value" style="font-size: 18px; font-weight: bold; color: var(--price-color);">
                        $<?php echo number_format($order['total'], 2); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Items del pedido -->
        <div class="items-section">
            <h2 class="section-title">
                <i class="fas fa-shopping-bag"></i> Productos
            </h2>
            
            <div class="item-list">
                <?php 
                $itemsTotal = 0;
                foreach ($items as $item): 
                    $itemTotal = $item['final_price'] * $item['quantity'];
                    $itemsTotal += $itemTotal;
                ?>
                <div class="order-item">
                    <div class="item-image" 
                         style="background-image: url('<?php echo $item['imagen_url']; ?>')"
                         onclick="window.location.href='game_detail.php?id=<?php echo $item['game_id']; ?>'">
                    </div>
                    
                    <div class="item-info">
                        <div class="item-name">
                            <?php echo htmlspecialchars($item['titulo']); ?>
                        </div>
                        <div class="item-meta">
                            <span>Categoría: <?php echo htmlspecialchars($item['categoria']); ?></span>
                            <span>Cantidad: <?php echo $item['quantity']; ?></span>
                            <?php if ($item['discount'] > 0): ?>
                            <span>Descuento: -<?php echo $item['discount']; ?>%</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="item-price">
                        <div class="unit-price">
                            $<?php echo number_format($item['final_price'], 2); ?> c/u
                        </div>
                        <div class="item-total">
                            $<?php echo number_format($itemTotal, 2); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Resumen del pedido -->
        <div class="order-summary">
            <h2 class="section-title">
                <i class="fas fa-calculator"></i> Resumen del Pago
            </h2>
            
            <div class="summary-row">
                <span>Subtotal</span>
                <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
            </div>
            
            <div class="summary-row">
                <span>IVA (16%)</span>
                <span>$<?php echo number_format($order['tax'], 2); ?></span>
            </div>
            
            <div class="summary-row">
                <span>Total</span>
                <span><strong>$<?php echo number_format($order['total'], 2); ?></strong></span>
            </div>
            
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir Recibo
                </button>
                <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                    <i class="fas fa-store"></i> Seguir Comprando
                </button>
            </div>
        </div>
    </div>
</body>
</html>
