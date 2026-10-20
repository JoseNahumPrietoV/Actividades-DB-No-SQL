<?php
require_once __DIR__ . '/config/helpers.php';
configureSession();
session_start();

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// Verificar si se proporcionó un ID de juego
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$gameObjId = toObjectId($_GET['id']);
if (!$gameObjId) {
    header('Location: index.php');
    exit();
}

$db = Database::getInstance();

// ============================================
// OBTENER INFORMACIÓN DEL JUEGO
// ============================================
$gameDoc = $db->getCollection('juegos')->findOne([
    '_id' => $gameObjId,
    'estado' => 'aprobado',
]);

if (!$gameDoc) {
    header('Location: index.php');
    exit();
}

// Convertir a array para compatibilidad con el HTML
$game = (array)$gameDoc;
$game['id'] = (string)$gameDoc['_id'];
// Desarrollador ya viene desnormalizado en el documento como 'desarrollador_nombre'
$game['desarrollador'] = $game['desarrollador_nombre'] ?? 'Desconocido';

// ============================================
// OBTENER RESEÑAS DEL JUEGO
// ============================================
$reviews = [];
$cursor = $db->getCollection('resenas')->find(
    ['juego_id' => $gameObjId],
    ['sort' => ['fecha_creacion' => -1]]
);

foreach ($cursor as $r) {
    $review = (array)$r;
    $review['id'] = (string)$r['_id'];
    $review['nombre_usuario'] = $review['usuario_nombre'] ?? 'Anónimo';
    $review['fecha_creacion'] = isset($review['fecha_creacion'])
        ? $review['fecha_creacion']->toDateTime()->format('Y-m-d H:i:s')
        : date('Y-m-d H:i:s');
    unset($review['_id']);
    $reviews[] = $review;
}

// ============================================
// CALCULAR CALIFICACIÓN PROMEDIO
// ============================================
$averageRating = 0;
if (count($reviews) > 0) {
    $totalRating = 0;
    foreach ($reviews as $review) {
        $totalRating += $review['calificacion'];
    }
    $averageRating = round($totalRating / count($reviews), 1);
}

// ============================================
// OBTENER JUEGOS SIMILARES (misma categoría)
// ============================================
$similarGames = [];
try {
    $cursor = $db->getCollection('juegos')->aggregate([
        ['$match' => [
            'categoria' => $game['categoria'],
            'estado' => 'aprobado',
            '_id' => ['$ne' => $gameObjId],
        ]],
        ['$sample' => ['size' => 4]],
    ]);
    
    foreach ($cursor as $doc) {
        $similar = (array)$doc;
        $similar['id'] = (string)$doc['_id'];
        unset($similar['_id']);
        $similarGames[] = bsonToArray($similar);
    }
} catch (Exception $e) {
    error_log("Error obteniendo juegos similares: " . $e->getMessage());
}

// ============================================
// VERIFICAR SI EL USUARIO TIENE EL JUEGO
// ============================================
$inLibrary = false;
if (isset($_SESSION['user_id'])) {
    $userObjId = new ObjectId($_SESSION['user_id']);
    $biblioteca = $db->getCollection('bibliotecas')->findOne([
        'usuario_id' => $userObjId,
        'juegos.juego_id' => $gameObjId,
    ]);
    $inLibrary = $biblioteca !== null;
}

// ============================================
// PROCESAR ENVÍO DE RESEÑA
// ============================================
if (isset($_POST['submit_review']) && isset($_SESSION['user_id']) && $inLibrary) {
    $userObjId = new ObjectId($_SESSION['user_id']);
    $calificacion = (int)($_POST['calificacion'] ?? 0);
    $comentario = trim($_POST['comentario'] ?? '');
    
    if ($calificacion >= 1 && $calificacion <= 5 && !empty($comentario)) {
        try {
            // Obtener nombre del usuario
            $userDoc = $db->getCollection('usuarios')->findOne(
                ['_id' => $userObjId],
                ['projection' => ['nombre_usuario' => 1]]
            );
            $userName = $userDoc['nombre_usuario'] ?? 'Anónimo';
            
            // Upsert de la reseña
            $db->getCollection('resenas')->updateOne(
                [
                    'usuario_id' => $userObjId,
                    'juego_id' => $gameObjId,
                ],
                [
                    '$set' => [
                        'usuario_nombre' => $userName,
                        'calificacion' => $calificacion,
                        'comentario' => $comentario,
                        'fecha_creacion' => new UTCDateTime(),
                    ],
                    '$setOnInsert' => [
                        'usuario_id' => $userObjId,
                        'juego_id' => $gameObjId,
                        'votos_util' => 0,
                    ],
                ],
                ['upsert' => true]
            );
            
            // Actualizar contador de reseñas y rating promedio del juego
            $agg = $db->getCollection('resenas')->aggregate([
                ['$match' => ['juego_id' => $gameObjId]],
                ['$group' => [
                    '_id' => null,
                    'total' => ['$sum' => 1],
                    'promedio' => ['$avg' => '$calificacion'],
                ]],
            ])->toArray();
            
            if (!empty($agg)) {
                $db->getCollection('juegos')->updateOne(
                    ['_id' => $gameObjId],
                    ['$set' => [
                        'total_resenas' => (int)$agg[0]['total'],
                        'rating_promedio' => round((float)$agg[0]['promedio'], 2),
                    ]]
                );
            }
            
            header("Location: game_detail.php?id=" . urlencode($_GET['id']));
            exit();
        } catch (Exception $e) {
            error_log("Error guardando reseña: " . $e->getMessage());
            $reviewError = "Error al enviar la reseña.";
        }
    } else {
        $reviewError = "Por favor, proporciona una calificación y un comentario válidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($game['titulo']); ?> - TechnoGames</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .game-detail-container {
            padding: 20px 5%;
            max-width: 1400px;
            margin: 0 auto;
        }
        .game-hero {
            display: flex;
            gap: 40px;
            margin-bottom: 50px;
            background: linear-gradient(135deg, rgba(42, 42, 42, 0.8), rgba(30, 30, 30, 0.9));
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .game-image {
            flex: 0 0 45%;
            min-height: 400px;
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
        }
        .game-info {
            flex: 1;
        }
        .game-title {
            font-size: 36px;
            margin-bottom: 10px;
            font-weight: 700;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .game-developer {
            color: var(--text-secondary);
            margin-bottom: 20px;
            font-size: 18px;
        }
        .game-rating {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 10px;
        }
        .rating-stars-display {
            color: gold;
            font-size: 18px;
        }
        .rating-text {
            color: var(--text-secondary);
            font-size: 16px;
        }
        .game-price {
            font-size: 28px;
            font-weight: bold;
            color: var(--price-color);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .game-sale {
            background-color: var(--sale);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: bold;
        }
        .game-actions {
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
        }
        .game-description {
            margin-bottom: 30px;
            line-height: 1.6;
            color: var(--text-secondary);
        }
        .game-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        .meta-label {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .meta-value {
            font-size: 16px;
            font-weight: 500;
        }
        .game-requirements {
            background-color: var(--bg-card);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .requirements-title {
            font-size: 22px;
            margin-bottom: 20px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .requirements-content {
            background-color: var(--bg-secondary);
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .reviews-section {
            margin-top: 40px;
        }
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .review-form {
            background-color: var(--bg-card);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        .review-item {
            background-color: var(--bg-card);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            align-items: center;
        }
        .review-author {
            font-weight: bold;
            color: var(--accent-primary);
        }
        .review-rating {
            color: gold;
        }
        .review-date {
            color: var(--text-secondary);
            font-size: 14px;
        }
        .rating-stars {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        .rating-stars input {
            display: none;
        }
        .rating-stars label {
            cursor: pointer;
            color: #ccc;
            font-size: 28px;
            transition: color 0.2s;
        }
        .rating-stars input:checked ~ label {
            color: gold;
        }
        .rating-stars label:hover,
        .rating-stars label:hover ~ label {
            color: gold;
        }
        .similar-games {
            margin-top: 60px;
        }
        .similar-header {
            font-size: 24px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .similar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }
        .similar-game {
            background-color: var(--bg-card);
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .similar-game:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        .similar-image {
            height: 120px;
            background-size: cover;
            background-position: center;
        }
        .similar-info {
            padding: 15px;
        }
        .similar-title {
            font-size: 16px;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .similar-price {
            font-weight: bold;
            color: var(--text-primary);
        }
        .notification {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .notification.show {
            display: block;
        }
        .notification.error {
            background-color: rgba(255, 51, 51, 0.2);
            color: #ff3333;
            border-left: 4px solid #ff3333;
        }
        .notification.success {
            background-color: rgba(0, 204, 102, 0.2);
            color: #00cc66;
            border-left: 4px solid #00cc66;
        }
        .add-to-cart:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .btn-primary {
            background-color: var(--accent-primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover:not(:disabled) {
            background-color: #0066CC;
        }
        .btn-secondary {
            background-color: transparent;
            color: var(--text-primary);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }
        @media (max-width: 1024px) {
            .game-hero {
                flex-direction: column;
            }
            .game-image {
                min-height: 300px;
                margin-bottom: 20px;
            }
        }
        @media (max-width: 768px) {
            .game-meta {
                grid-template-columns: 1fr;
            }
            .game-actions {
                flex-direction: column;
            }
            .similar-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <button id="logoutBtn">Cerrar Sesión</button>
            <?php else: ?>
                <button id="loginBtn">Iniciar Sesión</button>
            <?php endif; ?>
        </div>
    </header>

    <div class="game-detail-container">
        <!-- Hero Section -->
        <div class="game-hero">
            <div class="game-image" style="background-image: url('<?php echo $game['imagen_url']; ?>')"></div>
            <div class="game-info">
                <h1 class="game-title"><?php echo htmlspecialchars($game['titulo']); ?></h1>
                <div class="game-developer">Desarrollado por: <?php echo htmlspecialchars($game['desarrollador']); ?></div>
                
                <div class="game-rating">
                    <div class="rating-stars-display">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="<?php echo $i <= round($averageRating) ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-text"><?php echo $averageRating; ?> (<?php echo count($reviews); ?> reseñas)</span>
                </div>
                
                <div class="game-price">
                    <?php if ($game['descuento'] > 0): ?>
                        <s>$<?php echo number_format($game['precio'], 2); ?></s>
                        $<?php echo number_format($game['precio'] * (1 - $game['descuento'] / 100), 2); ?>
                        <span class="game-sale">-<?php echo $game['descuento']; ?>%</span>
                    <?php else: ?>
                        $<?php echo number_format($game['precio'], 2); ?>
                    <?php endif; ?>
                </div>

               <div class="game-actions">
    <?php if ($inLibrary): ?>
        <button class="btn-primary" disabled>
            <i class="fas fa-check"></i> En tu biblioteca
        </button>
    <?php else: ?>
        <button class="btn-primary" onclick="addToCart(<?php echo $game['id']; ?>)">
            <i class="fas fa-shopping-cart"></i> Agregar al carrito
        </button>
    <?php endif; ?>
    <button class="btn-secondary" onclick="window.location.href='index.php'">
        <i class="fas fa-arrow-left"></i> Seguir comprando
    </button>
</div>

                <div class="game-description">
                    <h3>Descripción</h3>
                    <p><?php echo nl2br(htmlspecialchars($game['descripcion'])); ?></p>
                </div>

                <div class="game-meta">
                    <div class="meta-item">
                        <span class="meta-label">Categoría</span>
                        <span class="meta-value"><?php echo htmlspecialchars($game['categoria']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Fecha de lanzamiento</span>
                        <span class="meta-value"><?php echo date('d/m/Y', strtotime($game['fecha_agregado'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Desarrollador</span>
                        <span class="meta-value"><?php echo htmlspecialchars($game['desarrollador']); ?></span>
                    </div>
                    <div class="meta-item">
                 
                    </div>
                </div>
            </div>
        </div>

        <!-- Requisitos del sistema -->
        <div class="game-requirements">
            <h3 class="requirements-title"><i class="fas fa-desktop"></i> Requisitos del Sistema</h3>
            <div class="requirements-content"><?php 
                $req = $game["requisitos"] ?? [];
                if (is_object($req) || $req instanceof \MongoDB\Model\BSONDocument) {
                    $req = (array)$req;
                }
                if (is_array($req) && !empty($req)) {
                    $labels = ["os" => "SO", "processor" => "Procesador", "memory" => "Memoria", "graphics" => "Gráficos", "storage" => "Almacenamiento"];
                    foreach ($req as $key => $val) {
                        $label = $labels[$key] ?? ucfirst($key);
                        echo htmlspecialchars("$label: $val") . "<br>";
                    }
                } else {
                    echo htmlspecialchars((string)$req);
                }
            ?></div>
        </div>

        <!-- Reseñas -->
        <div class="reviews-section">
            <div class="reviews-header">
                <h2><i class="fas fa-star"></i> Reseñas</h2>
                <div class="rating-summary">
                    <span class="rating-text">Calificación promedio: <?php echo $averageRating; ?> / 5</span>
                </div>
            </div>
            
            <?php if ($inLibrary): ?>
                <!-- Formulario de reseña -->
                <div class="review-form">
                    <h3>Escribe tu reseña</h3>
                    <?php if (isset($reviewSuccess)): ?>
                        <div class="notification success show"><?php echo $reviewSuccess; ?></div>
                    <?php endif; ?>
                    <?php if (isset($reviewError)): ?>
                        <div class="notification error show"><?php echo $reviewError; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="rating-stars">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="calificacion" value="<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                        <div class="form-group">
                            <label for="comentario">Comentario</label>
                            <textarea id="comentario" name="comentario" rows="4" required placeholder="Comparte tu experiencia con este juego..."></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn-primary">Enviar Reseña</button>
                    </form>
                </div>
            <?php elseif (isset($_SESSION['user_id'])): ?>
                <div class="notification">
                    <p>Debes tener este juego en tu biblioteca para escribir una reseña.</p>
                </div>
            <?php else: ?>
                <div class="notification">
                    <p><a href="login.php" style="color: var(--accent-primary);">Inicia sesión</a> para escribir una reseña.</p>
                </div>
            <?php endif; ?>

            <!-- Lista de reseñas -->
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <div>
                                <div class="review-author"><?php echo htmlspecialchars($review['nombre_usuario']); ?></div>
                                <div class="review-date"><?php echo date('d/m/Y H:i', strtotime($review['fecha_creacion'])); ?></div>
                            </div>
                            <span class="review-rating">
                                <?php for ($i = 0; $i < $review['calificacion']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                                <?php for ($i = $review['calificacion']; $i < 5; $i++): ?>
                                    <i class="far fa-star"></i>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($review['comentario'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="notification">
                    <p>Este juego aún no tiene reseñas.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Juegos similares -->
        <?php if (count($similarGames) > 0): ?>
        <div class="similar-games">
            <h3 class="similar-header"><i class="fas fa-gamepad"></i> Juegos similares</h3>
            <div class="similar-grid">
                <?php foreach ($similarGames as $similar): ?>
                <div class="similar-game" onclick="window.location.href='game_detail.php?id=<?php echo $similar['id']; ?>'">
                    <div class="similar-image" style="background-image: url('<?php echo $similar['imagen_url']; ?>')"></div>
                    <div class="similar-info">
                        <div class="similar-title"><?php echo htmlspecialchars($similar['titulo']); ?></div>
                        <div class="similar-price">
                            <?php if ($similar['descuento'] > 0): ?>
                                $<?php echo number_format($similar['precio'] * (1 - $similar['descuento'] / 100), 2); ?>
                            <?php else: ?>
                                $<?php echo number_format($similar['precio'], 2); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Pasar información de sesión de PHP a JavaScript
        window.userSession = {
            isLoggedIn: <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>,
            userId: <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>,
            userName: '<?php echo isset($_SESSION['user_name']) ? addslashes($_SESSION['user_name']) : ''; ?>',
            userRole: '<?php echo isset($_SESSION['user_role']) ? $_SESSION['user_role'] : ''; ?>'
        };

        function addToCart(gameId) {
            if (window.userSession && window.userSession.isLoggedIn) {
                fetch('process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add_to_cart&game_id=${gameId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('¡Juego agregado al carrito!');
                        // Actualizar la página para mostrar el botón de biblioteca
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al agregar al carrito');
                });
            } else {
                alert('Debes iniciar sesión para agregar juegos al carrito');
                window.location.href = 'index.php';
            }
        }

        document.getElementById('logoutBtn')?.addEventListener('click', () => {
            window.location.href = 'index.php?logout=true';
        });

        document.getElementById('loginBtn')?.addEventListener('click', () => {
            window.location.href = 'index.php';
        });

        // Mejorar la experiencia de calificación con estrellas
        const starInputs = document.querySelectorAll('.rating-stars input');
        starInputs.forEach(input => {
            input.addEventListener('change', () => {
                const rating = input.value;
                // Aquí podrías añadir lógica adicional si es necesario
            });
        });
    </script>
</body>
</html>
