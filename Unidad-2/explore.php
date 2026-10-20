<?php
require_once __DIR__ . '/config/helpers.php';
configureSession();
session_start();

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;

// Manejar cierre de sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

$db = Database::getInstance();

// ============================================
// INICIALIZAR VARIABLES
// ============================================
$cartCount = 0;
$wishlistCount = 0;

// ============================================
// PARÁMETROS DE FILTRO
// ============================================
$category = $_GET['category'] ?? 'all';
$sort = $_GET['sort'] ?? 'recientes';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$search = $_GET['search'] ?? '';

// ============================================
// OBTENER CATEGORÍAS DISPONIBLES
// ============================================
$categories = [];
try {
    $categories = $db->getCollection('juegos')->distinct('categoria', ['estado' => 'aprobado']);
    sort($categories);
} catch (Exception $e) {
    error_log("Error obteniendo categorías: " . $e->getMessage());
}

// ============================================
// CONSTRUIR FILTRO DE JUEGOS
// ============================================
$filter = ['estado' => 'aprobado'];

if ($category !== 'all') {
    $filter['categoria'] = $category;
}

if (!empty($search)) {
    $regex = new Regex(preg_quote($search, '/'), 'i');
    $filter['$or'] = [
        ['titulo' => $regex],
        ['descripcion' => $regex],
        ['categoria' => $regex],
    ];
}

if (!empty($price_min) || !empty($price_max)) {
    $filter['precio_final'] = [];
    if (!empty($price_min)) $filter['precio_final']['$gte'] = (float)$price_min;
    if (!empty($price_max)) $filter['precio_final']['$lte'] = (float)$price_max;
}

// ============================================
// ORDENAMIENTO
// ============================================
$sortOptions = match($sort) {
    'precio_asc'   => ['precio_final' => 1],
    'precio_desc'  => ['precio_final' => -1],
    'nombre_asc'   => ['titulo' => 1],
    'nombre_desc'  => ['titulo' => -1],
    'descuento'    => ['descuento' => -1],
    default        => ['fecha_agregado' => -1],
};

// ============================================
// EJECUTAR CONSULTA
// ============================================
$games = [];
try {
    $cursor = $db->getCollection('juegos')->find($filter, [
        'sort' => $sortOptions,
        'limit' => 100,
    ]);
    
    foreach ($cursor as $doc) {
        $game = (array)$doc;
        $game['id'] = (string)$doc['_id'];
        unset($game['_id']);
        $games[] = bsonToArray($game);
    }
} catch (Exception $e) {
    error_log("Error obteniendo juegos: " . $e->getMessage());
}

// ============================================
// ESTADÍSTICAS DE PRECIOS
// ============================================
$minPrice = 0;
$maxPrice = 100;
try {
    $statsAgg = $db->getCollection('juegos')->aggregate([
        ['$match' => ['estado' => 'aprobado']],
        ['$group' => [
            '_id' => null,
            'min_price' => ['$min' => '$precio_final'],
            'max_price' => ['$max' => '$precio_final'],
        ]],
    ])->toArray();
    
    if (!empty($statsAgg)) {
        $minPrice = (float)($statsAgg[0]['min_price'] ?? 0);
        $maxPrice = (float)($statsAgg[0]['max_price'] ?? 100);
    }
} catch (Exception $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
}

// ============================================
// CONTADORES DEL USUARIO
// ============================================
if (isset($_SESSION['user_id'])) {
    try {
        $userObjId = new ObjectId($_SESSION['user_id']);
        
        $carrito = $db->getCollection('carritos')->findOne(['usuario_id' => $userObjId]);
        if ($carrito && !empty($carrito['items'])) {
            $cartCount = count($carrito['items']);
        }
        
        $wishlist = $db->getCollection('wishlists')->findOne(['usuario_id' => $userObjId]);
        if ($wishlist && !empty($wishlist['juegos'])) {
            $wishlistCount = count($wishlist['juegos']);
        }
    } catch (Exception $e) {
        error_log("Error obteniendo contadores: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar Juegos - TechnoGames</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Estilos específicos para la página de exploración */
        .explore-container {
            padding: 20px 5%;
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            gap: 30px;
        }
        
        /* Sidebar de filtros */
        .filters-sidebar {
            flex: 0 0 280px;
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            border-radius: 12px;
            padding: 25px;
            height: fit-content;
            position: sticky;
            top: 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .filters-sidebar h3 {
            font-size: 20px;
            margin-bottom: 25px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .filter-section {
            margin-bottom: 30px;
        }
        
        .filter-section h4 {
            font-size: 16px;
            margin-bottom: 15px;
            color: var(--text-secondary);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-tags {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .filter-tag {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--text-secondary);
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .filter-tag:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }
        
        .filter-tag.active {
            background: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
        }
        
        .filter-count {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        .filter-tag.active .filter-count {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Rango de precios */
        .price-range {
            margin-bottom: 15px;
        }
        
        .price-inputs {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .price-input {
            flex: 1;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 14px;
        }
        
        .price-input:focus {
            outline: none;
            border-color: var(--accent-primary);
        }
        
        /* Botones de filtro */
        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .filter-btn {
            flex: 1;
            padding: 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .filter-btn.apply {
            background: var(--accent-primary);
            color: white;
        }
        
        .filter-btn.apply:hover {
            background: #0066CC;
            transform: translateY(-2px);
        }
        
        .filter-btn.clear {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .filter-btn.clear:hover {
            background: rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
        }
        
        /* Contenido principal */
        .games-content {
            flex: 1;
        }
        
        .explore-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .explore-header h1 {
            font-size: 28px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        
        .results-count {
            background: var(--accent-primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Controladores de vista */
        .view-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .sort-select {
            padding: 10px 15px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 14px;
            cursor: pointer;
        }
        
        .sort-select:focus {
            outline: none;
            border-color: var(--accent-primary);
        }
        
        .view-toggle {
            display: flex;
            gap: 5px;
            background: rgba(255, 255, 255, 0.05);
            padding: 5px;
            border-radius: 8px;
        }
        
        .view-btn {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .view-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }
        
        .view-btn.active {
            background: var(--accent-primary);
            color: white;
        }
        
        /* Grid de juegos - Vista de cuadrícula */
        .games-grid-explore {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        /* Vista de lista */
        .games-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .game-list-item {
            display: flex;
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }
        
        .game-list-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.3);
        }
        
        .list-game-image {
            width: 200px;
            min-height: 150px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .list-game-info {
            flex: 1;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .list-game-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        
        .list-game-description {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .list-game-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .list-game-category {
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .list-game-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .action-btn.primary {
            background: var(--accent-primary);
            color: white;
        }
        
        .action-btn.primary:hover {
            background: #0066CC;
        }
        
        .action-btn.secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .action-btn.secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
        }
        
        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 40px;
        }
        
        .page-btn {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .page-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }
        
        .page-btn.active {
            background: var(--accent-primary);
            color: white;
            border-color: var(--accent-primary);
        }
        
        /* Mensaje sin resultados */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            border-radius: 12px;
            margin: 40px 0;
        }
        
        .no-results i {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--accent-primary);
        }
        
        .no-results h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--text-primary);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .explore-container {
                flex-direction: column;
            }
            
            .filters-sidebar {
                position: static;
                width: 100%;
            }
            
            .games-grid-explore {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .explore-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .view-controls {
                width: 100%;
                justify-content: space-between;
            }
            
            .game-list-item {
                flex-direction: column;
            }
            
            .list-game-image {
                width: 100%;
                min-height: 200px;
            }
        }
        
        @media (max-width: 480px) {
            .games-grid-explore {
                grid-template-columns: 1fr;
            }
            
            .list-game-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .filter-buttons {
                flex-direction: column;
            }
        }
        
        /* Header minimalista (igual que index.php) */
        .header-minimal {
            background: linear-gradient(135deg, var(--bg-primary), var(--bg-secondary));
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        }
        
        .logo-minimal {
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-minimal i {
            color: var(--accent-primary);
            font-size: 2rem;
        }
        
        .user-actions-minimal {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .user-actions-minimal button {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }
        
        .user-actions-minimal button:hover {
            background: var(--accent-primary);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 116, 228, 0.3);
        }
        
        .user-actions-minimal button.primary {
            background: var(--accent-primary);
        }
        
        .user-actions-minimal button.primary:hover {
            background: #0066CC;
        }
        
        .count-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--sale);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        /* Search bar */
        .search-bar-minimal {
            flex: 1;
            max-width: 500px;
            margin: 0 30px;
            position: relative;
        }
        
        .search-bar-minimal input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border-radius: 25px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }
        
        .search-bar-minimal input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 15px rgba(0, 116, 228, 0.2);
        }
        
        .search-bar-minimal i {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <!-- Header Minimalista -->
    <header class="header-minimal">
        <div class="logo-minimal">
            <i class="fas fa-gamepad"></i>
            <span>TechnoGames</span>
        </div>
        
        <div class="search-bar-minimal">
            <input type="text" id="globalSearch" placeholder="Buscar juegos..." value="<?php echo htmlspecialchars($search); ?>">
            <i class="fas fa-search"></i>
        </div>
        
       <div class="user-actions-minimal">
    <button onclick="window.location.href='index.php'">
        <i class="fas fa-home"></i> <span>Inicio</span>
    </button>
    
    <!-- Botón Explorar - SOLO UNA VEZ (en modo activo) -->
    <button style="background: var(--accent-primary);">
        <i class="fas fa-compass"></i> <span>Explorar</span>
    </button>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <button id="cartBtn" style="position: relative;">
            <i class="fas fa-shopping-cart"></i> <span>Carrito</span>
            <span id="cartCount" class="count-badge"><?php echo $cartCount; ?></span>
        </button>
        <button id="wishlistBtn" style="position: relative;">
            <i class="fas fa-heart"></i> <span>Deseos</span>
            <span id="wishlistCount" class="count-badge"><?php echo $wishlistCount; ?></span>
        </button>
        <button id="libraryBtn">
            <i class="fas fa-book"></i> <span>Biblioteca</span>
        </button>
        
        <!-- Botones de panel según el rol del usuario -->
        <?php if ($_SESSION['user_role'] === 'dev'): ?>
            <button onclick="window.location.href='developer_panel.php'">
                <i class="fas fa-code"></i> <span>Panel Dev</span>
            </button>
        <?php endif; ?>
        
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <button onclick="window.location.href='admin_panel.php'">
                <i class="fas fa-cog"></i> <span>Panel Admin</span>
            </button>
        <?php endif; ?>
        
        <button id="logoutBtn">
            <i class="fas fa-sign-out-alt"></i> <span>Salir</span>
        </button>
    <?php else: ?>
        <button id="cartBtn" style="position: relative;">
            <i class="fas fa-shopping-cart"></i> <span>Carrito</span>
            <span id="cartCount" class="count-badge">0</span>
        </button>
        <button id="loginBtn">
            <i class="fas fa-sign-in-alt"></i> <span>Entrar</span>
        </button>
        <button id="registerBtn" class="primary">
            <i class="fas fa-user-plus"></i> <span>Registrarse</span>
        </button>
    <?php endif; ?>
</div>
    </header>
    
    <!-- Contenido Principal -->
    <div class="explore-container">
        <!-- Sidebar de Filtros -->
        <aside class="filters-sidebar">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
            
            <!-- Categorías -->
            <div class="filter-section">
                <h4><i class="fas fa-tag"></i> Categoría</h4>
                <div class="filter-tags">
                    <div class="filter-tag <?php echo $category === 'all' ? 'active' : ''; ?>" data-category="all">
                        Todas las categorías
                        <span class="filter-count"><?php echo count($games); ?></span>
                    </div>
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        // Contar juegos por categoría (simplificado)
                        $count = 0;
                        foreach ($games as $game) {
                            if ($game['categoria'] === $cat) {
                                $count++;
                            }
                        }
                        ?>
                        <div class="filter-tag <?php echo $category === $cat ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($cat); ?>">
                            <?php echo htmlspecialchars($cat); ?>
                            <span class="filter-count"><?php echo $count; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Rango de Precio -->
            <div class="filter-section">
                <h4><i class="fas fa-dollar-sign"></i> Rango de Precio</h4>
                <div class="price-range">
                    <input type="range" id="priceSlider" min="0" max="<?php echo $maxPrice; ?>" 
                           value="<?php echo $price_max ?: $maxPrice; ?>" class="slider">
                </div>
                <div class="price-inputs">
                    <input type="number" id="priceMin" class="price-input" 
                           placeholder="Min" value="<?php echo $price_min; ?>">
                    <input type="number" id="priceMax" class="price-input" 
                           placeholder="Max" value="<?php echo $price_max; ?>">
                </div>
            </div>
            
            <!-- Ordenamiento -->
            <div class="filter-section">
                <h4><i class="fas fa-sort-amount-down"></i> Ordenar por</h4>
                <div class="filter-tags">
                    <div class="filter-tag <?php echo $sort === 'recientes' ? 'active' : ''; ?>" data-sort="recientes">
                        Más recientes
                    </div>
                    <div class="filter-tag <?php echo $sort === 'nombre_asc' ? 'active' : ''; ?>" data-sort="nombre_asc">
                        Nombre A-Z
                    </div>
                    <div class="filter-tag <?php echo $sort === 'nombre_desc' ? 'active' : ''; ?>" data-sort="nombre_desc">
                        Nombre Z-A
                    </div>
                    <div class="filter-tag <?php echo $sort === 'precio_asc' ? 'active' : ''; ?>" data-sort="precio_asc">
                        Precio: Menor a Mayor
                    </div>
                    <div class="filter-tag <?php echo $sort === 'precio_desc' ? 'active' : ''; ?>" data-sort="precio_desc">
                        Precio: Mayor a Menor
                    </div>
                    <div class="filter-tag <?php echo $sort === 'descuento' ? 'active' : ''; ?>" data-sort="descuento">
                        Mejores ofertas
                    </div>
                </div>
            </div>
            
            <!-- Botones de acción -->
            <div class="filter-buttons">
                <button class="filter-btn apply" id="applyFilters">
                    <i class="fas fa-check"></i> Aplicar
                </button>
                <button class="filter-btn clear" id="clearFilters">
                    <i class="fas fa-times"></i> Limpiar
                </button>
            </div>
        </aside>
        
        <!-- Contenido de Juegos -->
        <main class="games-content">
            <!-- Header de resultados -->
            <div class="explore-header">
                <div>
                    <h1><i class="fas fa-gamepad"></i> Explorar Juegos</h1>
                    <div class="results-count"><?php echo count($games); ?> juegos encontrados</div>
                </div>
                
                <div class="view-controls">
                    <select class="sort-select" id="sortSelect">
                        <option value="recientes" <?php echo $sort === 'recientes' ? 'selected' : ''; ?>>Más recientes</option>
                        <option value="nombre_asc" <?php echo $sort === 'nombre_asc' ? 'selected' : ''; ?>>Nombre A-Z</option>
                        <option value="nombre_desc" <?php echo $sort === 'nombre_desc' ? 'selected' : ''; ?>>Nombre Z-A</option>
                        <option value="precio_asc" <?php echo $sort === 'precio_asc' ? 'selected' : ''; ?>>Precio: Menor a Mayor</option>
                        <option value="precio_desc" <?php echo $sort === 'precio_desc' ? 'selected' : ''; ?>>Precio: Mayor a Menor</option>
                        <option value="descuento" <?php echo $sort === 'descuento' ? 'selected' : ''; ?>>Mejores ofertas</option>
                    </select>
                    
                    <div class="view-toggle">
                        <button class="view-btn active" data-view="grid" title="Vista de cuadrícula">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button class="view-btn" data-view="list" title="Vista de lista">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Juegos -->
            <?php if (count($games) > 0): ?>
                <!-- Vista de Cuadrícula (predeterminada) -->
                <div class="games-grid-explore" id="gridView">
                    <?php foreach ($games as $game): ?>
                        <?php
                        $precio = floatval($game['precio']);
                        $descuento = intval($game['descuento']);
                        $precioFinal = $descuento > 0 ? $precio * (1 - $descuento / 100) : $precio;
                        $imagen = $game['imagen_url'] ?: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80';
                        ?>
                        <div class="game-card">
                            <div class="game-image" style="background-image: url('<?php echo htmlspecialchars($imagen); ?>')">
                                <?php if ($descuento > 0): ?>
                                    <div class="discount-badge">-<?php echo $descuento; ?>%</div>
                                <?php endif; ?>
                                
                                <!-- Botón de wishlist -->
                                <div class="wishlist-icon" data-game-id="<?php echo $game['id']; ?>">
                                    <i class="far fa-heart"></i>
                                </div>
                                
                                <div class="game-overlay">
                                    <button class="add-to-cart-btn" data-game-id="<?php echo $game['id']; ?>" title="Agregar al carrito">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                    <button class="view-details-btn" data-game-id="<?php echo $game['id']; ?>" title="Ver detalles">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="game-info-simple">
                                <div class="game-title"><?php echo htmlspecialchars($game['titulo']); ?></div>
                                <div class="game-price-simple">
                                    <?php if ($descuento > 0): ?>
                                        <div class="price-with-discount">
                                            <span class="original-price">$<?php echo number_format($precio, 2); ?></span>
                                            <span class="current-price">$<?php echo number_format($precioFinal, 2); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="current-price">$<?php echo number_format($precio, 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 8px;">
                                    <span class="list-game-category"><?php echo htmlspecialchars($game['categoria']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Vista de Lista (oculta por defecto) -->
                <div class="games-list" id="listView" style="display: none;">
                    <?php foreach ($games as $game): ?>
                        <?php
                        $precio = floatval($game['precio']);
                        $descuento = intval($game['descuento']);
                        $precioFinal = $descuento > 0 ? $precio * (1 - $descuento / 100) : $precio;
                        $imagen = $game['imagen_url'] ?: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80';
                        ?>
                        <div class="game-list-item">
                            <div class="list-game-image" style="background-image: url('<?php echo htmlspecialchars($imagen); ?>')">
                                <?php if ($descuento > 0): ?>
                                    <div class="discount-badge">-<?php echo $descuento; ?>%</div>
                                <?php endif; ?>
                            </div>
                            <div class="list-game-info">
                                <div>
                                    <div class="list-game-title"><?php echo htmlspecialchars($game['titulo']); ?></div>
                                    <div class="list-game-description"><?php echo htmlspecialchars(substr($game['descripcion'], 0, 150)) . '...'; ?></div>
                                    <div class="list-game-meta">
                                        <span class="list-game-category"><?php echo htmlspecialchars($game['categoria']); ?></span>
                                        <span style="color: var(--text-secondary); font-size: 14px;">
                                            <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($game['fecha_agregado'])); ?>
                                        </span>
                                        <span style="font-weight: bold; color: var(--accent-primary);">
                                            <?php if ($descuento > 0): ?>
                                                <s style="color: var(--text-secondary); margin-right: 5px;">$<?php echo number_format($precio, 2); ?></s>
                                                $<?php echo number_format($precioFinal, 2); ?>
                                            <?php else: ?>
                                                $<?php echo number_format($precio, 2); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="list-game-actions">
                                    <button class="action-btn secondary wishlist-list-btn" data-game-id="<?php echo $game['id']; ?>">
                                        <i class="far fa-heart"></i> Lista de deseos
                                    </button>
                                    <button class="action-btn primary add-to-cart-list-btn" data-game-id="<?php echo $game['id']; ?>">
                                        <i class="fas fa-shopping-cart"></i> Agregar al carrito
                                    </button>
                                    <button class="action-btn secondary view-details-list-btn" data-game-id="<?php echo $game['id']; ?>">
                                        <i class="fas fa-info-circle"></i> Detalles
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginación (simplificada) -->
                <?php if (count($games) > 20): ?>
                    <div class="pagination">
                        <button class="page-btn active">1</button>
                        <button class="page-btn">2</button>
                        <button class="page-btn">3</button>
                        <button class="page-btn">...</button>
                        <button class="page-btn">10</button>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Sin resultados -->
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No se encontraron juegos</h3>
                    <p>No hay juegos que coincidan con tus criterios de búsqueda. Intenta con otros filtros.</p>
                    <button class="cta-button" onclick="clearFilters()" style="margin-top: 20px;">
                        <i class="fas fa-times"></i> Limpiar todos los filtros
                    </button>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Pasar información de sesión de PHP a JavaScript
        window.userSession = {
            isLoggedIn: <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>,
            userId: <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>,
            userName: '<?php echo isset($_SESSION['user_name']) ? addslashes($_SESSION['user_name']) : ''; ?>',
            userRole: '<?php echo isset($_SESSION['user_role']) ? $_SESSION['user_role'] : ''; ?>'
        };
        
        // Variables de filtro actuales
        let currentCategory = '<?php echo $category; ?>';
        let currentSort = '<?php echo $sort; ?>';
        let currentPriceMin = '<?php echo $price_min; ?>';
        let currentPriceMax = '<?php echo $price_max; ?>';
        let currentSearch = '<?php echo $search; ?>';
    </script>

    <script>
        // Inicialización cuando el DOM está listo
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar slider de precio
            const priceSlider = document.getElementById('priceSlider');
            const priceMin = document.getElementById('priceMin');
            const priceMax = document.getElementById('priceMax');
            
            if (priceSlider && priceMin && priceMax) {
                priceSlider.addEventListener('input', function() {
                    priceMax.value = this.value;
                });
                
                priceMin.addEventListener('change', function() {
                    if (parseFloat(this.value) > parseFloat(priceSlider.value)) {
                        priceSlider.value = this.value;
                        priceMax.value = this.value;
                    }
                });
                
                priceMax.addEventListener('change', function() {
                    priceSlider.value = this.value;
                });
            }
            
            // Manejar botones de filtro
            document.getElementById('applyFilters').addEventListener('click', applyFilters);
            document.getElementById('clearFilters').addEventListener('click', clearFilters);
            
            // Manejar selección de categoría
            document.querySelectorAll('.filter-tag[data-category]').forEach(tag => {
                tag.addEventListener('click', function() {
                    currentCategory = this.getAttribute('data-category');
                    applyFilters();
                });
            });
            
            // Manejar selección de ordenamiento
            document.querySelectorAll('.filter-tag[data-sort]').forEach(tag => {
                tag.addEventListener('click', function() {
                    currentSort = this.getAttribute('data-sort');
                    applyFilters();
                });
            });
            
            // Manejar select de ordenamiento
            document.getElementById('sortSelect').addEventListener('change', function() {
                currentSort = this.value;
                applyFilters();
            });
            
            // Búsqueda global
            document.getElementById('globalSearch').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    currentSearch = this.value;
                    applyFilters();
                }
            });
            
            // Toggle de vista
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const view = this.getAttribute('data-view');
                    
                    // Actualizar botones activos
                    document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Cambiar vista
                    if (view === 'grid') {
                        document.getElementById('gridView').style.display = 'grid';
                        document.getElementById('listView').style.display = 'none';
                    } else {
                        document.getElementById('gridView').style.display = 'none';
                        document.getElementById('listView').style.display = 'block';
                    }
                });
            });
            
            // Funcionalidad de wishlist para vista de cuadrícula
            document.querySelectorAll('.wishlist-icon').forEach(icon => {
                icon.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const gameId = this.getAttribute('data-game-id');
                    toggleWishlist(gameId, this);
                });
                
                // Verificar estado inicial
                const gameId = icon.getAttribute('data-game-id');
                checkWishlistStatus(gameId, function(inWishlist) {
                    if (inWishlist && icon) {
                        icon.classList.add('active');
                        icon.innerHTML = '<i class="fas fa-heart"></i>';
                    }
                });
            });
            
            // Funcionalidad de wishlist para vista de lista
            document.querySelectorAll('.wishlist-list-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const gameId = this.getAttribute('data-game-id');
                    toggleWishlist(gameId, this);
                });
            });
            
            // Agregar al carrito desde vista de cuadrícula
            document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const gameId = this.getAttribute('data-game-id');
                    addToCart(gameId);
                });
            });
            
            // Agregar al carrito desde vista de lista
            document.querySelectorAll('.add-to-cart-list-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const gameId = this.getAttribute('data-game-id');
                    addToCart(gameId);
                });
            });
            
            // Ver detalles desde vista de cuadrícula
            document.querySelectorAll('.view-details-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const gameId = this.getAttribute('data-game-id');
                    window.location.href = `game_detail.php?id=${gameId}`;
                });
            });
            
            // Ver detalles desde vista de lista
            document.querySelectorAll('.view-details-list-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const gameId = this.getAttribute('data-game-id');
                    window.location.href = `game_detail.php?id=${gameId}`;
                });
            });
            
            // Click en tarjeta de juego para ver detalles
            document.querySelectorAll('.game-card').forEach(card => {
                card.addEventListener('click', function() {
                    const gameId = this.querySelector('.add-to-cart-btn')?.getAttribute('data-game-id');
                    if (gameId) {
                        window.location.href = `game_detail.php?id=${gameId}`;
                    }
                });
            });
            
            // Navegación del header
            document.getElementById('loginBtn')?.addEventListener('click', () => {
                window.location.href = 'index.php#login';
            });
            
            document.getElementById('registerBtn')?.addEventListener('click', () => {
                window.location.href = 'index.php#register';
            });
            
            document.getElementById('cartBtn')?.addEventListener('click', () => {
                window.location.href = 'index.php#cart';
            });
            
            document.getElementById('wishlistBtn')?.addEventListener('click', () => {
                window.location.href = 'wishlist.php';
            });
            
            document.getElementById('libraryBtn')?.addEventListener('click', () => {
                window.location.href = 'library.php';
            });
            
            document.getElementById('logoutBtn')?.addEventListener('click', () => {
                window.location.href = 'index.php?logout=true';
            });
        });
        
        // Función para aplicar filtros
        function applyFilters() {
            const priceMin = document.getElementById('priceMin').value;
            const priceMax = document.getElementById('priceMax').value;
            const search = document.getElementById('globalSearch').value;
            
            // Construir URL con parámetros
            let url = 'explore.php?';
            const params = new URLSearchParams();
            
            if (currentCategory !== 'all') params.append('category', currentCategory);
            if (currentSort !== 'recientes') params.append('sort', currentSort);
            if (priceMin) params.append('price_min', priceMin);
            if (priceMax) params.append('price_max', priceMax);
            if (search) params.append('search', search);
            
            window.location.href = url + params.toString();
        }
        
        // Función para limpiar filtros
        function clearFilters() {
            window.location.href = 'explore.php';
        }
        
        // Función para toggle de wishlist (reutilizada de script.js)
        function toggleWishlist(gameId, button) {
            if (!window.userSession || !window.userSession.isLoggedIn) {
                alert('Debes iniciar sesión para usar la lista de deseos');
                window.location.href = 'index.php#login';
                return;
            }
            
            checkWishlistStatus(gameId, function(inWishlist) {
                if (inWishlist) {
                    removeFromWishlist(gameId, button);
                } else {
                    addToWishlist(gameId, button);
                }
            });
        }
        
        // Función para verificar estado de wishlist
        function checkWishlistStatus(gameId, callback) {
            if (!window.userSession || !window.userSession.isLoggedIn) {
                callback(false);
                return;
            }
            
            fetch('process_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=check_wishlist_status&game_id=${gameId}`
            })
            .then(response => response.json())
            .then(data => callback(data.in_wishlist))
            .catch(() => callback(false));
        }
        
        // Función para agregar a wishlist
        function addToWishlist(gameId, button) {
            fetch('process_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add_to_wishlist&game_id=${gameId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (button.classList.contains('wishlist-icon')) {
                        button.classList.add('active', 'added');
                        button.innerHTML = '<i class="fas fa-heart"></i>';
                        setTimeout(() => button.classList.remove('added'), 500);
                    } else if (button.classList.contains('wishlist-list-btn')) {
                        button.innerHTML = '<i class="fas fa-heart"></i> En lista';
                        button.classList.add('active');
                    }
                    alert(data.message);
                    updateWishlistCount();
                } else {
                    alert(data.message);
                }
            })
            .catch(() => alert('Error al agregar a lista de deseos'));
        }
        
        // Función para eliminar de wishlist
        function removeFromWishlist(gameId, button) {
            if (confirm('¿Estás seguro de que deseas eliminar este juego de tu lista de deseos?')) {
                fetch('process_wishlist.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=remove_from_wishlist&game_id=${gameId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (button.classList.contains('wishlist-icon')) {
                            button.classList.remove('active');
                            button.innerHTML = '<i class="far fa-heart"></i>';
                        } else if (button.classList.contains('wishlist-list-btn')) {
                            button.innerHTML = '<i class="far fa-heart"></i> Lista de deseos';
                            button.classList.remove('active');
                        }
                        alert(data.message);
                        updateWishlistCount();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(() => alert('Error al eliminar de lista de deseos'));
            }
        }
        
        // Función para actualizar contador de wishlist
        function updateWishlistCount() {
            if (!window.userSession || !window.userSession.isLoggedIn) return;
            
            fetch('process_wishlist.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_wishlist'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const wishlistCount = document.getElementById('wishlistCount');
                    if (wishlistCount) {
                        wishlistCount.textContent = data.wishlist ? data.wishlist.length : 0;
                        wishlistCount.style.animation = 'none';
                        setTimeout(() => {
                            wishlistCount.style.animation = 'pulse 0.5s ease-in-out';
                        }, 10);
                    }
                }
            });
        }
        
        // Función para agregar al carrito
        function addToCart(gameId) {
            if (!window.userSession || !window.userSession.isLoggedIn) {
                alert('Debes iniciar sesión para agregar juegos al carrito');
                window.location.href = 'index.php#login';
                return;
            }
            
            fetch('process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add_to_cart&game_id=${gameId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('¡Juego agregado al carrito!');
                    updateCartCount();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(() => alert('Error al agregar al carrito'));
        }
        
        // Función para actualizar contador del carrito
        function updateCartCount() {
            if (!window.userSession || !window.userSession.isLoggedIn) return;
            
            fetch('process.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_cart_count'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cartCount = document.getElementById('cartCount');
                    if (cartCount) {
                        cartCount.textContent = data.count || 0;
                        cartCount.style.animation = 'none';
                        setTimeout(() => {
                            cartCount.style.animation = 'pulse 0.5s ease-in-out';
                        }, 10);
                    }
                }
            });
        }
    </script>
</body>
</html>
