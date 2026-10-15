<?php
// 1. INCLUIR EL ARCHIVO CON EL CÓDIGO DE CONEXIÓN Y CONSULTA
include '../conexion/pantalla_usuario_cliente.php';

// --- 2. OBTENER TODOS LOS DATOS DEL CARRITO ANTES DE MOSTRAR EL HTML ---
$items_del_carrito = [];
$total_carrito = 0.00;

$sql_get_cart = "SELECT 
                    p.idproducto, 
                    p.nombreproducto, 
                    p.imagen, 
                    dc.cantidad, 
                    dc.precio,
                    (dc.cantidad * dc.precio) AS subtotal_producto
                 FROM detallecarrito dc
                 JOIN carrito c ON dc.idcarrito = c.idcarrito
                 JOIN producto p ON dc.idproducto = p.idproducto
                 WHERE c.idcliente = ?";

$stmt_get_cart = $conn->prepare($sql_get_cart);
$stmt_get_cart->bind_param("i", $idClienteLogueado);
$stmt_get_cart->execute();
$resultado_carrito = $stmt_get_cart->get_result();

if ($resultado_carrito->num_rows > 0) {
    while ($item = $resultado_carrito->fetch_assoc()) {
        $items_del_carrito[] = $item;
        $total_carrito += $item['subtotal_producto'];
    }
}
$stmt_get_cart->close();

// --- 3. CALCULAR TOTALES ---
// $envio = ($total_carrito > 0) ? 15.00 : 0.00;
// $impuestos = $total_carrito * 0.16;
// $total_final = $total_carrito + $envio + $impuestos;
// 

$total_final = $total_carrito; // <-- EL TOTAL AHORA ES IGUAL AL SUBTOTAL
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
    <link rel="shortcut icon" href="../img/logo - snackver.png" type="image/x-icon">
</head>

<body>

    <header>
        <div class="container header-content">
            <a href="../../Back-end/cliente/dashboard_cliente.php" class="logo">
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
            <?php
            $mensaje_carrito = $_SESSION['mensaje_carrito'] ?? '';
            unset($_SESSION['mensaje_carrito']);
            ?>

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
                                        <!-- ===== CORRECCIÓN 3: RUTA DEL PRODUCTO (subir 2 niveles) ===== -->
                                        <img src="../../<?php echo htmlspecialchars($item['imagen']); ?>" alt="<?php echo htmlspecialchars($item['nombreproducto']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                    </div>
                                    <div class="item-details">
                                        <h3><?php echo htmlspecialchars($item['nombreproducto']); ?></h3>
                                        <div class="item-price" data-precio-unitario="<?php echo $item['precio']; ?>">
                                            $<?php echo number_format($item['precio'], 2); ?>
                                        </div>
                                        <div class="item-actions">
                                            <div class="quantity-controls">
                                                <button class="quantity-btn quantity-btn-menos">-</button>
                                                <input type="text" class="quantity-input" value="<?php echo $item['cantidad']; ?>" readonly>
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

    <?php
    // Cerramos la conexión principal al final de todo
    $conn->close();
    ?>


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
                            
                            // Verificar si el carrito quedó vacío
                            if (document.querySelectorAll('.cart-item').length === 0) {
                                location.reload(); // Recargar para mostrar mensaje de "vacío"
                            }
                        } else {
                            // Solo actualizamos números
                            const inputCantidad = itemElement.querySelector('.quantity-input');
                            const subtotalElement = itemElement.querySelector('.item-subtotal');
                            const precioUnitario = itemElement.querySelector('.item-price').dataset.precioUnitario; // Asegúrate de tener este atributo en el HTML

                            inputCantidad.value = data.nuevaCantidad;
                            // Si tienes el precio guardado en data-atributo, úsalo, si no, calculalo
                            // let nuevoSubtotal = ...
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
    }
});
</script>

</body>

</html>












</body>

</html>