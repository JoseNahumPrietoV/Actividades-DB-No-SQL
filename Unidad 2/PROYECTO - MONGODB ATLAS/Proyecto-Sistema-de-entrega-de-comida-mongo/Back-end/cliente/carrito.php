<?php
// 1. INCLUIR EL ARCHIVO CON EL CÓDIGO DE CONEXIÓN Y CONSULTA
require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php';

$mensaje_carrito = $_SESSION['mensaje_carrito'] ?? '';
unset($_SESSION['mensaje_carrito']);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// --- 2. OBTENER TODOS LOS DATOS DEL CARRITO (MONGODB ATLAS) ---
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
    }
} catch (Exception $e) {
    error_log("Error cargando carrito de MongoDB: " . $e->getMessage());
}

$total_final = $total_carrito;
?>



<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Carrito - Snackver</title>
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
                <h1>Tu Carrito de Compras</h1>
                <p>Revisa y modifica tus productos antes de finalizar tu pedido</p>
            </div>

            <?php if (!empty($mensaje_carrito)): ?>
                <div class="alert alert-success" style="color: black; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($mensaje_carrito); ?>
                </div>
            <?php endif; ?>

            <div class="cart-layout">
                <div class="cart-items">
                    <h2>Productos en tu carrito</h2>

                    <div id="cart-items-container">
                        <?php if (count($items_del_carrito) > 0): ?>
                            <?php foreach ($items_del_carrito as $item): ?>

                                <div class="cart-item" data-idproducto="<?php echo $item['idproducto']; ?>">

                                    <div class="item-image">
                                        <?php 
                                        $imgSrc = $item['imagen'] ?? '';
                                        $rutaCarritoImg = str_starts_with($imgSrc, 'http') ? $imgSrc : "../../" . $imgSrc;
                                        ?>
                                        <img src="<?php echo htmlspecialchars($rutaCarritoImg); ?>" alt="<?php echo htmlspecialchars($item['nombreproducto']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                    <div class="item-details">
                                        <h3><?php echo htmlspecialchars($item['nombreproducto']); ?></h3>
                                        <div class="item-price" data-precio-unitario="<?php echo $item['precio']; ?>">
                                            $<?php echo number_format($item['precio'], 2); ?>
                                        </div>
                                        <div class="item-actions">
                                            <div class="quantity-controls">
                                                <button class="quantity-btn quantity-btn-menos">-</button>
                                                <input type="number" class="quantity-input" value="<?php echo $item['cantidad']; ?>" min="1" max="99">
                                                <button class="quantity-btn quantity-btn-mas">+</button>
                                            </div>
                                            <button class="remove-btn">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="item-subtotal" style="text-align: right; font-weight: bold;">
                                        $<?php echo number_format($item['subtotal_producto'], 2); ?>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-cart-message" style="display: block; text-align: center; padding: 40px 0;">
                                <i class="fas fa-shopping-cart" style="font-size: 60px; color: #ddd; margin-bottom: 20px;"></i>
                                <h3>Tu carrito está vacío</h3>
                                <p>Agrega productos deliciosos a tu carrito para continuar</p>
                            </div>
                        <?php endif; ?>
                    </div>
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
                    <div class="promo-code">
                        
                    </div>

                    <a href="checkout.php" class="checkout-btn"
                        style="display: block; text-decoration: none; text-align: center; box-sizing: border-box;">
                        Proceder al Pago
                    </a>
                    <a href="BuscarTienda.php" class="continue-shopping">Continuar Comprando</a>
                </div>
            </div>

            <!-- <div class="suggested-products">
                <h2>Productos que te pueden gustar</h2>
                <div class="products-grid" id="suggested-products">
                </div>
            </div> -->
        </div>
    </main>

    <script>
document.addEventListener('DOMContentLoaded', function() {

    const contenedorItems = document.getElementById('cart-items-container');

    if (contenedorItems) {
        contenedorItems.addEventListener('click', function(e) {

            // 1. Identificar qué botón se presionó
            // Usamos .closest() para asegurarnos de agarrar el botón aunque se haga clic en el ícono <i>
            const botonMas = e.target.closest('.quantity-btn-mas');
            const botonMenos = e.target.closest('.quantity-btn-menos');
            const botonEliminar = e.target.closest('.remove-btn'); // <--- ESTE ES EL IMPORTANTE

            let accion = null;
            
            if (botonMas) {
                accion = 'aumentar';
            } else if (botonMenos) {
                accion = 'disminuir';
            } else if (botonEliminar) {
                // Confirmación opcional para seguridad
                if(!confirm("¿Seguro que quieres eliminar este producto del carrito?")) return;
                accion = 'eliminar';
            }

            // Si no fue ninguno de los tres, no hacemos nada
            if (accion === null) return;

            // 2. Encontrar el elemento padre (la fila del producto)
            const itemElement = e.target.closest('.cart-item');
            if (!itemElement) return;

            const idProducto = itemElement.dataset.idproducto;

            // 3. Enviar la petición
            const formData = new FormData();
            formData.append('idproducto', idProducto);
            formData.append('accion', accion);

            // Deshabilitar visualmente para evitar doble clic
            itemElement.style.opacity = '0.5';

            fetch('actualizar_carrito.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    itemElement.style.opacity = '1';

                    if (data.status === 'success') {
                        
                        // Lógica visual actualizada
                        if (accion === 'eliminar' || data.nuevaCantidad === 0) {
                            // Si la acción fue ELIMINAR o la cantidad bajó a 0 -> Borramos del HTML
                            itemElement.remove();
                            
                            // Verificar si el carrito quedó vacío y mostrar estado vacío en 0ms sin recargar
                            if (document.querySelectorAll('.cart-item').length === 0) {
                                contenedorItems.innerHTML = `
                                    <div class="empty-cart-message" style="display: block; text-align: center; padding: 40px 0;">
                                        <i class="fas fa-shopping-cart" style="font-size: 60px; color: #ddd; margin-bottom: 20px;"></i>
                                        <h3>Tu carrito está vacío</h3>
                                        <p>Agrega productos deliciosos a tu carrito para continuar</p>
                                    </div>`;
                                document.getElementById('subtotal').textContent = '$0.00';
                                document.getElementById('total').textContent = '$0.00';
                            }
                        } else {
                            // Solo actualizamos números
                            const inputCantidad = itemElement.querySelector('.quantity-input');
                            const subtotalElement = itemElement.querySelector('.item-subtotal');
                            const precioEl = itemElement.querySelector('.item-price');
                            const precioUnitario = precioEl ? parseFloat(precioEl.dataset.precioUnitario || 0) : 0;

                            inputCantidad.value = data.nuevaCantidad;
                            if (subtotalElement && precioUnitario > 0) {
                                subtotalElement.textContent = '$' + (data.nuevaCantidad * precioUnitario).toFixed(2);
                            }
                        }

                        // Actualizar los totales generales
                        if (data.nuevosTotales) {
                            document.getElementById('subtotal').textContent = '$' + data.nuevosTotales.subtotal;
                            document.getElementById('total').textContent = '$' + data.nuevosTotales.total;
                        }

                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    itemElement.style.opacity = '1';
                    console.error(error);
                    alert('Error de conexión');
                });
        });

        // Permitir cambiar la cantidad escribiendo directamente desde el teclado
        contenedorItems.addEventListener('change', function(e) {
            const inputCantidad = e.target.closest('.quantity-input');
            if (!inputCantidad) return;

            const itemElement = inputCantidad.closest('.cart-item');
            if (!itemElement) return;

            let valor = parseInt(inputCantidad.value, 10);
            if (isNaN(valor) || valor < 1) {
                valor = 1;
                inputCantidad.value = 1;
            }

            const idProducto = itemElement.dataset.idproducto;
            const formData = new FormData();
            formData.append('idproducto', idProducto);
            formData.append('accion', 'fijar');
            formData.append('cantidad', valor);

            itemElement.style.opacity = '0.5';

            fetch('actualizar_carrito.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    itemElement.style.opacity = '1';

                    if (data.status === 'success') {
                        inputCantidad.value = data.nuevaCantidad;
                        const subtotalElement = itemElement.querySelector('.item-subtotal');
                        const precioEl = itemElement.querySelector('.item-price');
                        const precioUnitario = precioEl ? parseFloat(precioEl.dataset.precioUnitario || 0) : 0;

                        if (subtotalElement && precioUnitario > 0) {
                            subtotalElement.textContent = '$' + (data.nuevaCantidad * precioUnitario).toFixed(2);
                        }

                        if (data.nuevosTotales) {
                            document.getElementById('subtotal').textContent = '$' + data.nuevosTotales.subtotal;
                            document.getElementById('total').textContent = '$' + data.nuevosTotales.total;
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    itemElement.style.opacity = '1';
                    console.error(error);
                    alert('Error de conexión');
                });
        });
    }
});
</script>
<script src="../../Front-end/js/tiempo_real.js?v=2.3"></script>

</body>

</html>