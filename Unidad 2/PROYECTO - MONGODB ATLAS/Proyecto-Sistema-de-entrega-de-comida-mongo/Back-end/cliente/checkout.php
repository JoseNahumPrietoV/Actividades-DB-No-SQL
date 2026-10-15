<?php
// 1. INCLUIR EL ARCHIVO DE CONEXIÓN Y SESIÓN
// Esto nos da $conn y $idClienteLogueado
require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php';

// Liberar candado de sesión inmediatamente para evitar bloqueos
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// 2. OBTENER LOS ITEMS Y TOTALES (MONGODB ATLAS)
$items_del_carrito = [];
$total_carrito = 0.00;

try {
    $mongoDb = MongoConexion::getConexion();
    $colCarritos = $mongoDb->selectCollection('carritos');
    $filtroCli = [];
    if (is_string($idClienteLogueado) && preg_match('/^[a-f\d]{24}$/i', $idClienteLogueado)) {
        $filtroCli = ['$or' => [
            ['id_cliente' => new MongoDB\BSON\ObjectId($idClienteLogueado)],
            ['id_cliente' => (string)$idClienteLogueado],
            ['id_cliente' => (int)$idClienteLogueado]
        ]];
    } else {
        $filtroCli = ['$or' => [
            ['id_cliente' => (int)$idClienteLogueado],
            ['id_cliente' => (string)$idClienteLogueado]
        ]];
    }

    $cartDoc = $colCarritos->findOne($filtroCli);
    if ($cartDoc && !empty($cartDoc['items'])) {
        foreach ($cartDoc['items'] as $item) {
            $cant = (int)($item['cantidad'] ?? 1);
            $prec = (float)($item['precio'] ?? 0);
            $subt = $cant * $prec;
            $items_del_carrito[] = [
                'idproducto'        => (string)$item['idproducto'],
                'nombreproducto'    => $item['nombreproducto'] ?? 'Platillo',
                'imagen'            => $item['imagen'] ?? '',
                'cantidad'          => $cant,
                'precio'            => $prec,
                'subtotal_producto' => $subt
            ];
            $total_carrito += $subt;
        }
    } else {
        header('Location: carrito.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: carrito.php');
    exit;
}

// 3. CALCULAR TOTALES (igual que en carrito.php)
// $envio = ($total_carrito > 0) ? 15.00 : 0.00;
// $impuestos = $total_carrito * 0.16;
// $total_final = $total_carrito + $envio + $impuestos;
$total_final = $total_carrito;

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Pedido - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-cliente.css">
    <link rel="stylesheet" href="../css/carrito.css">
    <link rel="icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="../img/logo%20-%20snackver.png?v=2.2">
</head>

<body>
    <header>
        <div class="container header-content">
            <a href="dashboard_cliente.php" class="logo">
                <img src="../img/logo - snackver.png" alt="Logo de Snackver"> Snackver
            </a>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Confirmar Pedido</h1>
                <p>Último paso. Confirma tu método de pago.</p>
            </div>

            <div class="cart-layout">
                <div class="cart-items">
                    <h2>Detalles de Pago</h2>

                    <form action="procesar_pedido.php" method="POST" id="checkout-form">

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="metodopago" style="display: block; margin-bottom: 8px; font-weight: bold;">Método de Pago</label>

                            <select name="metodopago" id="metodopago" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;" onchange="mostrarInfoPago()">
                                <option value="Efectivo">Efectivo (Pagar al recibir)</option>
                                <option value="Transferencia">Transferencia Bancaria</option>
                            </select>
                        </div>

                        <div id="datos-transferencia" style="display: none; background-color: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 20px;">
                            <h4 style="margin-top: 0; color: #2c3e50;">Datos para Transferencia:</h4>
                            <p style="margin: 5px 0;"><strong>Banco:</strong> BBVA Bancomer</p>
                            <p style="margin: 5px 0;"><strong>CLABE:</strong> 012345678901234567</p>
                            <p style="margin: 5px 0;"><strong>Beneficiario:</strong> Snackver Inc.</p>
                            <p style="margin: 5px 0; font-size: 0.9em; color: #e67e22;">
                                * Por favor envía tu comprobante al repartidor o por WhatsApp.
                            </p>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label for="referenciapago" style="display: block; margin-bottom: 8px; font-weight: bold;">Referencia / Notas</label>
                            <input type="text" name="referenciapago" id="referenciapago"
                                placeholder="Ej: Pagaré con billete de $200 / Ya transferí"
                                style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                        </div>

                        <input type="hidden" name="total_final_pedido" value="<?php echo $total_final; ?>">

                    </form>
                </div>

                <div class="order-summary">
                    <h2>Resumen del Pedido</h2>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal">$<?php echo number_format($total_carrito, 2); ?></span>
                    </div>

                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="total">$<?php echo number_format($total_final, 2); ?></span>
                    </div>

                    <button type="submit" form="checkout-form" class="checkout-btn">
                        Confirmar y Realizar Pedido
                    </button>
                    <a href="carrito.php" class="continue-shopping">Volver al Carrito</a>
                </div>
            </div>
        </div>
    </main>

    <script>
    function mostrarInfoPago() {
        const select = document.getElementById('metodopago');
        const info = document.getElementById('datos-transferencia');
        
        if (select.value === 'Transferencia') {
            info.style.display = 'block';
        } else {
            info.style.display = 'none';
        }
    }
</script>
</body>

</html>