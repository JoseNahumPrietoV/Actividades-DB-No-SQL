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
// OBTENER BIBLIOTECA DEL USUARIO
// ============================================
$library = [];
$categories = [];

$biblioteca = $db->getCollection('bibliotecas')->findOne(['usuario_id' => $userObjId]);

if ($biblioteca && !empty($biblioteca['juegos'])) {
    $juegosCollection = $db->getCollection('juegos');
    $resenasCollection = $db->getCollection('resenas');
    $categoryCount = [];
    
    foreach ($biblioteca['juegos'] as $item) {
        // Obtener el juego completo
        $juego = $juegosCollection->findOne(['_id' => $item['juego_id']]);
        
        if (!$juego) continue;
        
        // Contar reseñas del usuario para este juego
        $tieneResena = $resenasCollection->countDocuments([
            'juego_id' => $item['juego_id'],
            'usuario_id' => $userObjId,
        ]);
        
        // Rating promedio del juego
        $ratingAgg = $resenasCollection->aggregate([
            ['$match' => ['juego_id' => $item['juego_id']]],
            ['$group' => [
                '_id' => null,
                'promedio' => ['$avg' => '$calificacion'],
            ]],
        ])->toArray();
        
        $ratingPromedio = !empty($ratingAgg) ? (float)$ratingAgg[0]['promedio'] : 0;
        
        // Convertir BSON a array
        $juegoArray = (array)$juego;
        $juegoArray['id'] = (string)$juego['_id'];
        $juegoArray['fecha_adquisicion'] = $item['fecha_adquisicion']->toDateTime()->format('Y-m-d H:i:s');
        $juegoArray['tiene_resena'] = $tieneResena;
        $juegoArray['rating_promedio'] = $ratingPromedio;
        
        // Limpiar campos no necesarios para la vista
        unset($juegoArray['_id']);
        
        $library[] = bsonToArray($juegoArray);
        
        // Contar categorías
        $cat = $juego['categoria'] ?? 'Sin categoría';
        $categoryCount[$cat] = ($categoryCount[$cat] ?? 0) + 1;
    }
    
    // Ordenar biblioteca por fecha de adquisición (descendente)
    usort($library, function($a, $b) {
        return strcmp($b['fecha_adquisicion'], $a['fecha_adquisicion']);
    });
    
    // Construir array de categorías
    arsort($categoryCount);
    foreach ($categoryCount as $cat => $count) {
        $categories[] = [
            'categoria' => $cat,
            'cantidad' => $count,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Biblioteca - TechnoGames</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .library-container {
            padding: 20px 5%;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .library-header {
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
        
        /* Buscador */
        .search-box {
            position: relative;
            flex: 1;
            max-width: 500px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border-radius: 30px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            backdrop-filter: blur(10px);
        }
        
        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 20px;
        }
        
        /* Filtros */
        .library-filters {
            margin-bottom: 30px;
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .filter-section {
            margin-bottom: 20px;
        }
        
        .filter-section:last-child {
            margin-bottom: 0;
        }
        
        .filter-section h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .filter-tag {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .filter-tag:hover {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: white;
        }
        
        .filter-tag.active {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: white;
        }
        
        /* Grid de juegos */
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        
        /* Tarjeta de juego mejorada */
        .library-game-card {
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .library-game-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }
        
        .library-game-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .library-game-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: linear-gradient(135deg, var(--accent-primary), #0066CC);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            z-index: 2;
        }
        
        .library-game-info {
            padding: 20px;
        }
        
        .library-game-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
            line-height: 1.3;
        }
        
        .library-game-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .library-game-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .library-game-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
            color: gold;
        }
        
        .library-game-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .play-btn {
            flex: 1;
            background: linear-gradient(135deg, var(--accent-primary), #0066CC);
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .play-btn:hover {
            background: linear-gradient(135deg, #0066CC, #0052a3);
            transform: translateY(-2px);
        }
        
        .review-btn {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .review-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
        }
        
        .review-btn.reviewed {
            background: rgba(0, 204, 102, 0.2);
            color: #00CC66;
            border-color: #00CC66;
        }
        
        /* Estado vacío */
        .library-empty {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            border-radius: 12px;
            margin: 40px 0;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .library-empty i {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--accent-primary);
        }
        
        .library-empty h2 {
            font-size: 28px;
            margin-bottom: 15px;
            color: var(--text-primary);
        }
        
        .library-empty p {
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
        
        /* Contador */
        .game-count {
            background: var(--accent-primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-left: 10px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .library-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                max-width: 100%;
                width: 100%;
            }
            
            .games-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 480px) {
            .games-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-tags {
                justify-content: center;
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
            <button id="logoutBtn">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </button>
        </div>
    </header>

    <div class="library-container">
        <!-- Header con título y buscador -->
        <div class="library-header">
            <div class="header-title">
                <i class="fas fa-book"></i>
                <h1>Mi Biblioteca</h1>
                <span class="game-count"><?php echo count($library); ?> juegos</span>
            </div>
            
            <div class="search-box">
                <input type="text" id="librarySearch" placeholder="Buscar en tu biblioteca...">
                <i class="fas fa-search"></i>
            </div>
        </div>

        <!-- Filtros -->
        <?php if (count($library) > 0): ?>
        <div class="library-filters">
            <div class="filter-section">
                <h3><i class="fas fa-filter"></i> Filtrar por categoría</h3>
                <div class="filter-tags">
                    <div class="filter-tag active" data-filter="all">Todos</div>
                    <?php foreach ($categories as $category): ?>
                        <div class="filter-tag" data-filter="category-<?php echo strtolower($category['categoria']); ?>">
                            <?php echo htmlspecialchars($category['categoria']); ?> (<?php echo $category['cantidad']; ?>)
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="filter-section">
                <h3><i class="fas fa-sort-amount-down"></i> Ordenar por</h3>
                <div class="filter-tags">
                    <div class="filter-tag active" data-sort="reciente">Más reciente</div>
                    <div class="filter-tag" data-sort="antiguo">Más antiguo</div>
                    <div class="filter-tag" data-sort="nombre">Nombre A-Z</div>
                    <div class="filter-tag" data-sort="precio">Precio</div>
                    <div class="filter-tag" data-sort="rating">Rating</div>
                </div>
            </div>
        </div>

        <!-- Grid de juegos -->
        <div class="games-grid" id="gamesGrid">
            <?php foreach ($library as $game): ?>
                <div class="library-game-card" 
                     data-category="category-<?php echo strtolower($game['categoria']); ?>"
                     data-name="<?php echo htmlspecialchars(strtolower($game['titulo'])); ?>"
                     data-date="<?php echo strtotime($game['fecha_adquisicion']); ?>"
                     data-price="<?php echo $game['precio']; ?>"
                     data-rating="<?php echo $game['rating_promedio'] ?? 0; ?>">
                    
                    <div class="library-game-image" style="background-image: url('<?php echo $game['imagen_url']; ?>')">
                        <div class="library-game-badge">
                            <?php echo htmlspecialchars($game['categoria']); ?>
                        </div>
                    </div>
                    
                    <div class="library-game-info">
                        <div class="library-game-title"><?php echo htmlspecialchars($game['titulo']); ?></div>
                        
                        <div class="library-game-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($game['fecha_adquisicion'])); ?></span>
                            <span><i class="fas fa-dollar-sign"></i> $<?php echo number_format($game['precio'], 2); ?></span>
                        </div>
                        
                        <?php if ($game['rating_promedio'] > 0): ?>
                            <div class="library-game-rating">
                                <?php 
                                $rating = round($game['rating_promedio']);
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <i class="<?php echo $i <= $rating ? 'fas' : 'far'; ?> fa-star"></i>
                                <?php endfor; ?>
                                <span style="color: var(--text-secondary); margin-left: 5px; font-size: 12px;">
                                    (<?php echo number_format($game['rating_promedio'], 1); ?>)
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="library-game-actions">
                            <button class="play-btn" onclick="window.location.href='game_detail.php?id=<?php echo $game['id']; ?>'">
                                <i class="fas fa-info-circle"></i> Ver Detalles
                            </button>
                            <button class="review-btn <?php echo $game['tiene_resena'] > 0 ? 'reviewed' : ''; ?>" 
                                    onclick="window.location.href='game_detail.php?id=<?php echo $game['id']; ?>#reseñas'">
                                <i class="fas fa-star"></i> 
                                <?php echo $game['tiene_resena'] > 0 ? 'Reseñado' : 'Reseñar'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
            <!-- Estado vacío -->
            <div class="library-empty">
                <i class="fas fa-book-open"></i>
                <h2>Tu biblioteca está vacía</h2>
                <p>¡Aún no tienes juegos en tu biblioteca! Descubre nuevos títulos y añádelos para empezar a jugar.</p>
                <button class="cta-button" onclick="window.location.href='index.php'">
                    <i class="fas fa-store"></i> Explorar Tienda
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', () => {
            window.location.href = 'index.php?logout=true';
        });

        // Funcionalidad de búsqueda
        const searchInput = document.getElementById('librarySearch');
        const gameCards = document.querySelectorAll('.library-game-card');
        
        searchInput?.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            gameCards.forEach(card => {
                const gameName = card.getAttribute('data-name');
                const shouldShow = gameName.includes(searchTerm);
                card.style.display = shouldShow ? 'block' : 'none';
            });
        });

        // Funcionalidad de filtros
        const filterTags = document.querySelectorAll('.filter-tag[data-filter]');
        const sortTags = document.querySelectorAll('.filter-tag[data-sort]');
        
        filterTags.forEach(tag => {
            tag.addEventListener('click', function() {
                // Remover clase active de todos
                filterTags.forEach(t => t.classList.remove('active'));
                // Agregar clase active al clickeado
                this.classList.add('active');
                
                const filter = this.getAttribute('data-filter');
                
                gameCards.forEach(card => {
                    if (filter === 'all') {
                        card.style.display = 'block';
                    } else {
                        const category = card.getAttribute('data-category');
                        card.style.display = category === filter ? 'block' : 'none';
                    }
                });
            });
        });

        // Funcionalidad de ordenamiento
        sortTags.forEach(tag => {
            tag.addEventListener('click', function() {
                // Remover clase active de todos
                sortTags.forEach(t => t.classList.remove('active'));
                // Agregar clase active al clickeado
                this.classList.add('active');
                
                const sortBy = this.getAttribute('data-sort');
                const container = document.getElementById('gamesGrid');
                const cards = Array.from(gameCards);
                
                cards.sort((a, b) => {
                    switch(sortBy) {
                        case 'reciente':
                            return parseInt(b.getAttribute('data-date')) - parseInt(a.getAttribute('data-date'));
                        case 'antiguo':
                            return parseInt(a.getAttribute('data-date')) - parseInt(b.getAttribute('data-date'));
                        case 'nombre':
                            return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
                        case 'precio':
                            return parseFloat(b.getAttribute('data-price')) - parseFloat(a.getAttribute('data-price'));
                        case 'rating':
                            return parseFloat(b.getAttribute('data-rating')) - parseFloat(a.getAttribute('data-rating'));
                        default:
                            return 0;
                    }
                });
                
                // Reorganizar tarjetas en el contenedor
                cards.forEach(card => {
                    container.appendChild(card);
                });
            });
        });

        // Animación para las tarjetas al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.library-game-card');
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
