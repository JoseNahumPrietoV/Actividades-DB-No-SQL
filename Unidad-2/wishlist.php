<?php
require_once __DIR__ . '/config/helpers.php';
configureSession();
session_start();

use MongoDB\BSON\ObjectId;

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance();
$userObjId = new ObjectId($_SESSION['user_id']);

// ============================================
// OBTENER LISTA DE DESEOS DEL USUARIO
// ============================================

$wishlist = [];

$wishlistDoc = $db->getCollection('wishlists')->findOne(['usuario_id' => $userObjId]);

if ($wishlistDoc && !empty($wishlistDoc['juegos'])) {
    // Obtener la biblioteca del usuario para saber cuáles ya tiene
    $biblioteca = $db->getCollection('bibliotecas')->findOne(['usuario_id' => $userObjId]);
    $juegosEnBiblioteca = [];
    
    if ($biblioteca && !empty($biblioteca['juegos'])) {
        foreach ($biblioteca['juegos'] as $item) {
            $juegosEnBiblioteca[] = (string)$item['juego_id'];
        }
    }
    
    $juegosCollection = $db->getCollection('juegos');
    
    foreach ($wishlistDoc['juegos'] as $item) {
        $gameIdStr = (string)$item['juego_id'];
        
        // Obtener datos actuales del juego (precio, imagen, etc.)
        $juego = $juegosCollection->findOne(['_id' => $item['juego_id']]);
        
        if (!$juego) continue; // Juego eliminado
        
        // Calcular precio final actualizado
        $precio = (float)$juego['precio'];
        $descuento = (int)($juego['descuento'] ?? 0);
        $precioFinal = $descuento > 0 ? $precio * (1 - $descuento / 100) : $precio;
        
        $wishlist[] = [
            'wishlist_id' => $gameIdStr,          // Usamos el game_id como id
            'id' => $gameIdStr,                   // Para compatibilidad
            'titulo' => $juego['titulo'],
            'descripcion' => $juego['descripcion'] ?? '',
            'imagen_url' => $juego['imagen_url'] ?? '',
            'categoria' => $juego['categoria'] ?? '',
            'precio' => $precio,
            'descuento' => $descuento,
            'precio_final' => round($precioFinal, 2),
            'fecha_agregado' => $item['agregado_en']->toDateTime()->format('Y-m-d H:i:s'),
            'en_biblioteca' => in_array($gameIdStr, $juegosEnBiblioteca, true) ? 1 : 0,
        ];
    }
    
    // Ordenar por fecha de agregado (más reciente primero)
    usort($wishlist, function($a, $b) {
        return strcmp($b['fecha_agregado'], $a['fecha_agregado']);
    });
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Lista de Deseos - TechnoGames</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .wishlist-container {
            padding: 20px 5%;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .wishlist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-title i {
            color: var(--accent-primary);
            font-size: 32px;
        }
        
        .header-title h1 {
            font-size: 32px;
            margin: 0;
        }
        
        .game-count {
            background: var(--accent-primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .wishlist-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
        }
        
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        
        .wishlist-game-card {
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .wishlist-game-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }
        
        .wishlist-game-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .wishlist-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: linear-gradient(135deg, var(--sale), #ff3333);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            z-index: 2;
        }
        
        .wishlist-game-actions {
            position: absolute;
            top: 12px;
            right: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .wishlist-game-card:hover .wishlist-game-actions {
            opacity: 1;
        }
        
        .wishlist-action-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .wishlist-action-btn:hover {
            transform: scale(1.1);
            background: var(--accent-primary);
        }
        
        .remove-wishlist-btn {
            background: rgba(255, 51, 51, 0.7);
        }
        
        .move-to-cart-btn {
            background: rgba(0, 204, 102, 0.7);
        }
        
        .wishlist-game-info {
            padding: 20px;
        }
        
        .wishlist-game-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
            line-height: 1.3;
        }
        
        .wishlist-game-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .wishlist-game-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .wishlist-game-price {
            font-size: 20px;
            font-weight: 700;
            color: var(--accent-primary);
            margin-bottom: 15px;
        }
        
        .original-price {
            font-size: 14px;
            color: var(--text-secondary);
            text-decoration: line-through;
            margin-right: 8px;
        }
        
        .wishlist-empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            border-radius: 12px;
            margin: 40px 0;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .wishlist-empty i {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--accent-primary);
        }
        
        .wishlist-empty h2 {
            font-size: 28px;
            margin-bottom: 15px;
            color: var(--text-primary);
        }
        
        .wishlist-empty p {
            font-size: 16px;
            margin-bottom: 25px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }
        
        .cta-button {
            background: linear-gradient(135deg, var(--accent-primary), #0066CC);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 116, 228, 0.3);
        }
        
        .already-owned {
            background: linear-gradient(135deg, #00CC66, #00a352);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }
        
        .view-details-btn {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
            margin-top: 10px;
            transition: all 0.2s;
        }
        
        .view-details-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
        }
        
        @media (max-width: 768px) {
            .wishlist-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .wishlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .wishlist-game-actions {
                opacity: 1;
                flex-direction: row;
                top: auto;
                bottom: 15px;
                right: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .wishlist-grid {
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
            <button onclick="window.location.href='index.php'">
                <i class="fas fa-store"></i> Volver a Tienda
            </button>
            <button onclick="window.location.href='library.php'">
                <i class="fas fa-book"></i> Biblioteca
            </button>
            <button id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </button>
        </div>
    </header>

    <div class="wishlist-container">
        <!-- Header con título -->
        <div class="wishlist-header">
            <div class="header-title">
                <i class="fas fa-heart"></i>
                <h1>Mi Lista de Deseos</h1>
                <span class="game-count"><?php echo count($wishlist); ?> juegos</span>
            </div>
            
            <div class="wishlist-filters">
                <button class="filter-btn active" data-filter="all">Todos</button>
                <button class="filter-btn" data-filter="available">Disponibles</button>
                <button class="filter-btn" data-filter="sale">En Oferta</button>
            </div>
        </div>

        <?php if (count($wishlist) > 0): ?>
            <!-- Grid de juegos -->
            <div class="wishlist-grid" id="wishlistGrid">
                <?php foreach ($wishlist as $game): ?>
                    <div class="wishlist-game-card" 
                         data-category="<?php echo htmlspecialchars(strtolower($game['categoria'])); ?>"
                         data-price="<?php echo $game['precio']; ?>"
                         data-discount="<?php echo $game['descuento']; ?>"
                         data-owned="<?php echo $game['en_biblioteca']; ?>">
                        
                        <div class="wishlist-game-image" style="background-image: url('<?php echo $game['imagen_url']; ?>')">
                            <?php if ($game['descuento'] > 0): ?>
                                <div class="wishlist-badge">-<?php echo $game['descuento']; ?>%</div>
                            <?php endif; ?>
                            
                            <div class="wishlist-game-actions">
                                <button class="wishlist-action-btn move-to-cart-btn" 
                                        title="Mover al carrito"
                                        data-game-id="<?php echo $game['id']; ?>"
                                        data-wishlist-id="<?php echo $game['wishlist_id']; ?>">
                                    <i class="fas fa-shopping-cart"></i>
                                </button>
                                <button class="wishlist-action-btn remove-wishlist-btn" 
                                        title="Eliminar de lista de deseos"
                                        data-game-id="<?php echo $game['id']; ?>"
                                        data-wishlist-id="<?php echo $game['wishlist_id']; ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="wishlist-game-info">
                            <div class="wishlist-game-title"><?php echo htmlspecialchars($game['titulo']); ?></div>
                            
                            <div class="wishlist-game-meta">
                                <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($game['categoria']); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($game['fecha_agregado'])); ?></span>
                            </div>
                            
                            <div class="wishlist-game-price">
                                <?php if ($game['descuento'] > 0): ?>
                                    <span class="original-price">$<?php echo number_format($game['precio'], 2); ?></span>
                                    $<?php echo number_format($game['precio'] * (1 - $game['descuento'] / 100), 2); ?>
                                <?php else: ?>
                                    $<?php echo number_format($game['precio'], 2); ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($game['en_biblioteca'] > 0): ?>
                                <div class="already-owned">
                                    <i class="fas fa-check-circle"></i> Ya en tu biblioteca
                                </div>
                            <?php endif; ?>
                            
                            <button class="view-details-btn" onclick="window.location.href='game_detail.php?id=<?php echo $game['id']; ?>'">
                                <i class="fas fa-info-circle"></i> Ver Detalles
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Estado vacío -->
            <div class="wishlist-empty">
                <i class="fas fa-heart"></i>
                <h2>Tu lista de deseos está vacía</h2>
                <p>Guarda tus juegos favoritos aquí para no perderlos de vista. ¡Encuéntralos fácilmente cuando quieras comprarlos!</p>
                <button class="cta-button" onclick="window.location.href='index.php'">
                    <i class="fas fa-store"></i> Explorar Tienda
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', () => {
            window.location.href = 'index.php?logout=true';
        });

        // Funcionalidad de filtros
        const filterButtons = document.querySelectorAll('.filter-btn');
        const wishlistCards = document.querySelectorAll('.wishlist-game-card');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remover clase active de todos
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Agregar clase active al clickeado
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                
                wishlistCards.forEach(card => {
                    const discount = parseInt(card.getAttribute('data-discount'));
                    const owned = parseInt(card.getAttribute('data-owned'));
                    
                    switch(filter) {
                        case 'all':
                            card.style.display = 'block';
                            break;
                        case 'available':
                            card.style.display = owned > 0 ? 'none' : 'block';
                            break;
                        case 'sale':
                            card.style.display = discount > 0 ? 'block' : 'none';
                            break;
                    }
                });
            });
        });

        // Función para mostrar notificación
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification';
            notification.classList.add(type);
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        // Función para eliminar de la wishlist
        function removeFromWishlist(gameId, wishlistId) {
            const formData = new FormData();
            formData.append('action', 'remove_from_wishlist');
            if (wishlistId) formData.append('wishlist_id', wishlistId);
            if (gameId) formData.append('game_id', gameId);
            
            fetch('process_wishlist.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message);
                    // Recargar la página después de un breve retraso
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al eliminar de la lista de deseos', 'error');
            });
        }

        // Función para mover al carrito
        function moveToCart(gameId, wishlistId) {
            const formData = new FormData();
            formData.append('action', 'move_to_cart');
            if (wishlistId) formData.append('wishlist_id', wishlistId);
            if (gameId) formData.append('game_id', gameId);
            
            fetch('process_wishlist.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message);
                    // Recargar la página después de un breve retraso
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al mover al carrito', 'error');
            });
        }

        // Configurar event listeners para botones
        document.addEventListener('DOMContentLoaded', function() {
            // Botones de eliminar
            document.querySelectorAll('.remove-wishlist-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const gameId = this.getAttribute('data-game-id');
                    const wishlistId = this.getAttribute('data-wishlist-id');
                    
                    if (confirm('¿Estás seguro de que deseas eliminar este juego de tu lista de deseos?')) {
                        removeFromWishlist(gameId, wishlistId);
                    }
                });
            });
            
            // Botones de mover al carrito
            document.querySelectorAll('.move-to-cart-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const gameId = this.getAttribute('data-game-id');
                    const wishlistId = this.getAttribute('data-wishlist-id');
                    moveToCart(gameId, wishlistId);
                });
            });
            
            // Animación para las tarjetas al cargar
            const cards = document.querySelectorAll('.wishlist-game-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
