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
// OBTENER CARRITO DEL USUARIO
// ============================================

$cartItems = [];
$subtotal = 0;

$carrito = $db->getCollection('carritos')->findOne(['usuario_id' => $userObjId]);

if ($carrito && !empty($carrito['items'])) {
    $juegosCollection = $db->getCollection('juegos');
    
    foreach ($carrito['items'] as $item) {
        $precioFinal = (float)$item['precio_final'];
        $subtotal += $precioFinal;
        
        // Obtener imagen y datos actuales del juego
        $juego = $juegosCollection->findOne(['_id' => $item['juego_id']]);
        
        $cartItems[] = [
            'id' => (string)$item['juego_id'],
            'game_id' => (string)$item['juego_id'],
            'title' => $item['titulo'],
            'price' => $juego ? (float)$juego['precio'] : $precioFinal,
            'discount' => $juego ? (int)($juego['descuento'] ?? 0) : 0,
            'final_price' => $precioFinal,
            'quantity' => 1,
            'item_total' => $precioFinal,
            'image' => $juego ? ($juego['imagen_url'] ?? '') : '',
        ];
    }
}

// Calcular impuestos
$impuesto = round($subtotal * 0.16, 2);
$total = round($subtotal + $impuesto, 2);

// Si el carrito está vacío, redirigir
if (empty($cartItems)) {
    header('Location: cart.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - TechnoGames</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 40px;
        }
        
        .checkout-section {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .section-title {
            font-size: 22px;
            margin-bottom: 25px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        input, select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(0, 116, 228, 0.2);
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .payment-method {
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: var(--bg-secondary);
        }
        
        .payment-method:hover {
            border-color: var(--accent-primary);
            background: rgba(0, 116, 228, 0.1);
        }
        
        .payment-method.selected {
            border-color: var(--accent-primary);
            background: rgba(0, 116, 228, 0.15);
        }
        
        .payment-icon {
            font-size: 28px;
            margin-bottom: 10px;
            color: var(--text-secondary);
        }
        
        .payment-method.selected .payment-icon {
            color: var(--accent-primary);
        }
        
        .order-summary {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 30px;
            position: sticky;
            top: 20px;
            height: fit-content;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        }
        
        .order-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 25px;
            padding-right: 10px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .item-image {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            background-size: cover;
            background-position: center;
        }
        
        .item-details h4 {
            font-size: 15px;
            margin-bottom: 5px;
        }
        
        .item-details p {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .order-totals {
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            padding-top: 20px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: var(--text-secondary);
        }
        
        .total-row.final {
            font-size: 18px;
            font-weight: bold;
            color: var(--text-primary);
            margin-top: 15px;
            padding-top: 15px;
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
        
        .btn-checkout:hover:not(:disabled) {
            background: #0066CC;
        }
        
        .btn-checkout:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .secure-payment {
            text-align: center;
            margin-top: 15px;
            color: var(--text-secondary);
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .cart-empty {
            text-align: center;
            padding: 50px;
            background: var(--bg-card);
            border-radius: 12px;
            margin-top: 50px;
        }
        
        @media (max-width: 992px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            
            .order-summary {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .payment-methods {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .payment-methods {
                grid-template-columns: 1fr;
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
            <button onclick="window.location.href='index.php'">Volver a Tienda</button>
            <button onclick="window.location.href='cart.php'">
                <i class="fas fa-shopping-cart"></i> Carrito
            </button>
        </div>
    </header>

    <div class="checkout-container">
        <!-- Columna izquierda: Formulario -->
        <div class="checkout-column">
            <h1 style="margin-bottom: 30px; color: var(--text-primary);">
                <i class="fas fa-shopping-bag"></i> Finalizar Compra
            </h1>
            
            <!-- Información de envío -->
            <div class="checkout-section">
                <h2 class="section-title"><i class="fas fa-user"></i> Información Personal</h2>
                <form id="checkoutForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre *</label>
                            <input type="text" id="nombre" name="nombre" required>
                        </div>
                        <div class="form-group">
                            <label for="apellido">Apellido *</label>
                            <input type="text" id="apellido" name="apellido" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Correo Electrónico *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_SESSION['user_email']) ? $_SESSION['user_email'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono *</label>
                        <input type="tel" id="telefono" name="telefono" required>
                    </div>
                </form>
            </div>
            
            <!-- Método de pago -->
            <div class="checkout-section">
                <h2 class="section-title"><i class="fas fa-credit-card"></i> Método de Pago</h2>
                
                <div class="payment-methods">
                    <div class="payment-method" data-method="tarjeta">
                        <div class="payment-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div>Tarjeta de Crédito</div>
                    </div>
                    
                    <div class="payment-method" data-method="paypal">
                        <div class="payment-icon">
                            <i class="fab fa-paypal"></i>
                        </div>
                        <div>PayPal</div>
                    </div>
                    
                    <div class="payment-method" data-method="transferencia">
                        <div class="payment-icon">
                            <i class="fas fa-university"></i>
                        </div>
                        <div>Transferencia</div>
                    </div>
                </div>
                
                <div id="paymentDetails" style="margin-top: 25px; display: none;">
                    <!-- Los detalles del pago se cargarán dinámicamente -->
                </div>
            </div>
            
            <!-- Términos y condiciones -->
            <div class="checkout-section">
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="terms" style="width: auto;">
                        <span>Acepto los <a href="terms.php" style="color: var(--accent-primary);">Términos y Condiciones</a> y la <a href="privacy.php" style="color: var(--accent-primary);">Política de Privacidad</a></span>
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Columna derecha: Resumen del pedido -->
        <div class="checkout-column">
            <div class="order-summary">
                <h2 class="section-title"><i class="fas fa-receipt"></i> Resumen del Pedido</h2>
                
                <div class="order-items">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="order-item">
                        <div class="item-info">
                            <div class="item-image" style="background-image: url('<?php echo $item['image']; ?>')"></div>
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                                <p><?php echo $item['quantity']; ?> x $<?php echo number_format($item['final_price'], 2); ?></p>
                            </div>
                        </div>
                        <div class="item-total">
                            $<?php echo number_format($item['item_total'], 2); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <div class="total-row">
                        <span>IVA (16%)</span>
                        <span>$<?php echo number_format($impuesto, 2); ?></span>
                    </div>
                    
                    <div class="total-row final">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
                
                <button id="submitOrder" class="btn-checkout" disabled>
                    <i class="fas fa-lock"></i> Confirmar Compra
                </button>
                
                <div class="secure-payment">
                    <i class="fas fa-shield-alt"></i>
                    <span>Pago 100% seguro</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div class="modal" id="confirmationModal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fas fa-check-circle" style="color: #4CAF50;"></i> ¡Compra Exitosa!</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center; padding: 30px;">
                <div style="font-size: 72px; color: #4CAF50; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 style="margin-bottom: 15px; color: var(--text-primary);">
                    ¡Gracias por tu compra!
                </h3>
                <p style="color: var(--text-secondary); margin-bottom: 25px;">
                    Tu pedido ha sido procesado exitosamente. Los juegos han sido añadidos a tu biblioteca.
                </p>
                <p style="margin-bottom: 10px;">
                    <strong>ID de Pedido:</strong> <span id="orderId">####</span>
                </p>
                <p style="margin-bottom: 30px;">
                    <strong>Fecha:</strong> <?php echo date('d/m/Y H:i'); ?>
                </p>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button onclick="window.location.href='library.php'" class="btn-primary">
                        <i class="fas fa-book"></i> Ver Biblioteca
                    </button>
                    <button onclick="window.location.href='index.php'" class="btn-secondary">
                        <i class="fas fa-shopping-bag"></i> Seguir Comprando
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Seleccionar método de pago
        const paymentMethods = document.querySelectorAll('.payment-method');
        let selectedMethod = null;
        
        paymentMethods.forEach(method => {
            method.addEventListener('click', () => {
                paymentMethods.forEach(m => m.classList.remove('selected'));
                method.classList.add('selected');
                selectedMethod = method.getAttribute('data-method');
                showPaymentDetails(selectedMethod);
            });
        });
        // Función para validar el formulario
function validateForm() {
    const nombre = document.getElementById('nombre').value.trim();
    const apellido = document.getElementById('apellido').value.trim();
    const email = document.getElementById('email').value.trim();
    const telefono = document.getElementById('telefono').value.trim();
    
    // Validar campos requeridos
    if (!nombre || !apellido || !email || !telefono) {
        showError('Por favor, completa todos los campos requeridos');
        return false;
    }
    
    // Validar email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showError('Por favor, ingresa un correo electrónico válido');
        return false;
    }
    
    // Validar teléfono (mínimo 10 dígitos)
    const cleanPhone = telefono.replace(/\D/g, '');
    if (cleanPhone.length < 10) {
        showError('Por favor, ingresa un número de teléfono válido (mínimo 10 dígitos)');
        return false;
    }
    
    // Validar detalles de pago según método
    if (selectedMethod === 'tarjeta') {
        const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
        const cardExpiry = document.getElementById('cardExpiry').value;
        const cardCVC = document.getElementById('cardCVC').value;
        const cardName = document.getElementById('cardName').value.trim();
        
        // Validar número de tarjeta (16 dígitos)
        if (cardNumber.length < 16) {
            showError('Por favor, ingresa un número de tarjeta válido (16 dígitos)');
            return false;
        }
        
        // Validar fecha de expiración (MM/AA)
        const expiryRegex = /^(0[1-9]|1[0-2])\/\d{2}$/;
        if (!expiryRegex.test(cardExpiry)) {
            showError('Por favor, ingresa una fecha de expiración válida (MM/AA)');
            return false;
        }
        
        // Validar CVC (3-4 dígitos)
        if (cardCVC.length < 3 || cardCVC.length > 4) {
            showError('Por favor, ingresa un código CVC válido (3-4 dígitos)');
            return false;
        }
        
        // Validar nombre en tarjeta
        if (!cardName) {
            showError('Por favor, ingresa el nombre que aparece en la tarjeta');
            return false;
        }
    }
    
    return true;
}

// Función para mostrar errores
function showError(message) {
    // Crear notificación de error
    const errorDiv = document.createElement('div');
    errorDiv.className = 'notification error';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <span>${message}</span>
    `;
    
    // Estilos para la notificación
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: rgba(255, 51, 51, 0.9);
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10000;
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(errorDiv);
    
    // Remover después de 5 segundos
    setTimeout(() => {
        errorDiv.style.animation = 'slideOut 0.3s ease-in';
        setTimeout(() => errorDiv.remove(), 300);
    }, 5000);
}

// Animaciones CSS para las notificaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
        // Mostrar detalles según método de pago
        function showPaymentDetails(method) {
            const container = document.getElementById('paymentDetails');
            
            let html = '';
            switch(method) {
                case 'tarjeta':
                    html = `
                        <h4 style="margin-bottom: 20px; color: var(--text-primary);">
                            <i class="fas fa-credit-card"></i> Información de Tarjeta
                        </h4>
                        <div class="form-group">
                            <label for="cardNumber">Número de Tarjeta *</label>
                            <input type="text" id="cardNumber" name="cardNumber" 
                                   placeholder="1234 5678 9012 3456" maxlength="19" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cardExpiry">Fecha de Expiración *</label>
                                <input type="text" id="cardExpiry" name="cardExpiry" 
                                       placeholder="MM/AA" maxlength="5" required>
                            </div>
                            <div class="form-group">
                                <label for="cardCVC">CVC *</label>
                                <input type="text" id="cardCVC" name="cardCVC" 
                                       placeholder="123" maxlength="4" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="cardName">Nombre en la Tarjeta *</label>
                            <input type="text" id="cardName" name="cardName" required>
                        </div>
                    `;
                    break;
                    
                case 'paypal':
                    html = `
                        <div style="text-align: center; padding: 20px;">
                            <i class="fab fa-paypal" style="font-size: 48px; color: #003087; margin-bottom: 20px;"></i>
                            <p style="color: var(--text-secondary); margin-bottom: 20px;">
                                Serás redirigido a PayPal para completar el pago de manera segura.
                            </p>
                            <button type="button" id="paypalBtn" class="btn-primary" style="background: #003087;">
                                <i class="fab fa-paypal"></i> Continuar con PayPal
                            </button>
                        </div>
                    `;
                    break;
                    
                case 'transferencia':
                    html = `
                        <div style="background: var(--bg-secondary); padding: 20px; border-radius: 8px;">
                            <h4 style="margin-bottom: 15px; color: var(--text-primary);">
                                <i class="fas fa-university"></i> Datos Bancarios
                            </h4>
                            <p style="margin-bottom: 10px;"><strong>Banco:</strong> TechnoGames Bank</p>
                            <p style="margin-bottom: 10px;"><strong>Cuenta:</strong> 1234-5678-9012-3456</p>
                            <p style="margin-bottom: 10px;"><strong>CLABE:</strong> 012180012345678901</p>
                            <p style="margin-bottom: 15px;"><strong>Beneficiario:</strong> TechnoGames S.A. de C.V.</p>
                            <p style="font-size: 14px; color: var(--text-secondary);">
                                <i class="fas fa-info-circle"></i> Una vez realizada la transferencia, envía tu comprobante a pagos@technogames.com
                            </p>
                        </div>
                    `;
                    break;
            }
            
            container.innerHTML = html;
            container.style.display = 'block';
        }
        
        // Validar términos y condiciones
        const termsCheckbox = document.getElementById('terms');
        const submitBtn = document.getElementById('submitOrder');
        
        termsCheckbox.addEventListener('change', () => {
            submitBtn.disabled = !termsCheckbox.checked;
        });
        
        // Formatear número de tarjeta
        document.addEventListener('input', (e) => {
            if (e.target.id === 'cardNumber') {
                let value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
                e.target.value = value.substring(0, 19);
            }
            
            if (e.target.id === 'cardExpiry') {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value.substring(0, 5);
            }
        });
        
        // Procesar orden
        submitBtn.addEventListener('click', processOrder);
        
       async function processOrder() {
    if (!selectedMethod) {
        alert('Por favor, selecciona un método de pago');
        return;
    }
    
    if (!validateForm()) {
        alert('Por favor, completa todos los campos requeridos');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    
    try {
        // Recolectar datos del formulario
        const formData = new URLSearchParams({
            action: 'process_order',
            payment_method: selectedMethod,
            nombre: document.getElementById('nombre').value,
            apellido: document.getElementById('apellido').value,
            email: document.getElementById('email').value,
            telefono: document.getElementById('telefono').value
        });
        
        // Agregar datos de tarjeta si es necesario
        if (selectedMethod === 'tarjeta') {
            formData.append('card_number', document.getElementById('cardNumber').value.replace(/\s/g, ''));
            formData.append('card_expiry', document.getElementById('cardExpiry').value);
            formData.append('card_cvc', document.getElementById('cardCVC').value);
            formData.append('card_name', document.getElementById('cardName').value);
        }
        
        // Enviar orden al servidor
        const response = await fetch('process_checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Mostrar confirmación
            document.getElementById('orderId').textContent = data.order_id;
            document.getElementById('confirmationModal').style.display = 'flex';
            
            // Actualizar contadores
            if (window.updateCounters) {
                window.updateCounters(0, 0); // Carrito vacío
            }
        } else {
            throw new Error(data.message || 'Error al procesar la orden');
        }
        
    } catch (error) {
        alert('Error: ' + error.message);
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-lock"></i> Confirmar Compra';
    }
}
        // Cerrar modal
        document.querySelector('.close-modal').addEventListener('click', () => {
            document.getElementById('confirmationModal').style.display = 'none';
            window.location.href = 'library.php';
        });
    </script>
</body>
</html>
