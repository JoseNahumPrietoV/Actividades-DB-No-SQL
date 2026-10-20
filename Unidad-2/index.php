<?php
require_once __DIR__ . '/config/helpers.php';
configureSession();
session_start();

use MongoDB\BSON\ObjectId;

// ============================================
// MANEJO DE LOGOUT
// ============================================
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Mensaje de redirección si existe
if (isset($_SESSION['redirect_message'])) {
    $redirectMessage = $_SESSION['redirect_message'];
    unset($_SESSION['redirect_message']);
}

// ============================================
// CONEXIÓN A MONGODB
// ============================================
$db = Database::getInstance();

// ============================================
// VARIABLES INICIALES
// ============================================
$cartCount = 0;
$wishlistCount = 0;
$popularGames = [];

// ============================================
// JUEGOS POPULARES (CARRUSEL)
// ============================================
try {
    $cursor = $db->getCollection('juegos')->aggregate([
        ['$match' => ['estado' => 'aprobado']],
        ['$sample' => ['size' => 5]],
    ]);
    
    foreach ($cursor as $doc) {
        $game = (array)$doc;
        $game['id'] = (string)$doc['_id'];
        unset($game['_id']);
        $popularGames[] = bsonToArray($game);
    }
} catch (Exception $e) {
    error_log("Error obteniendo juegos populares: " . $e->getMessage());
}

// ============================================
// CONTADORES DEL USUARIO
// ============================================
if (isset($_SESSION['user_id'])) {
    try {
        $userObjId = new ObjectId($_SESSION['user_id']);
        
        // Carrito
        $carrito = $db->getCollection('carritos')->findOne(['usuario_id' => $userObjId]);
        if ($carrito && !empty($carrito['items'])) {
            $cartCount = count($carrito['items']);
        }
        
        // Wishlist
        $wishlist = $db->getCollection('wishlists')->findOne(['usuario_id' => $userObjId]);
        if ($wishlist && !empty($wishlist['juegos'])) {
            $wishlistCount = count($wishlist['juegos']);
        }
        
        // Email del usuario
        $user = $db->getCollection('usuarios')->findOne(
            ['_id' => $userObjId],
            ['projection' => ['email' => 1]]
        );
        if ($user) {
            $_SESSION['user_email'] = $user['email'];
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
    <title>TechnoGames - Tu Tienda de Videojuegos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Estilos adicionales para el carrusel */
        .hero-slider {
            position: relative;
            height: 500px;
            overflow: hidden;
            margin-bottom: 60px;
        }
        
        .slides-container {
            display: flex;
            transition: transform 0.5s ease-in-out;
            height: 100%;
        }
        
        .slide {
            min-width: 100%;
            height: 100%;
            position: relative;
            background-size: cover;
            background-position: center;
        }
        
        .slide-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.9));
            padding: 40px 5%;
            color: white;
        }
        
        .slide h2 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .slide p {
            font-size: 1.1rem;
            margin-bottom: 20px;
            opacity: 0.9;
            max-width: 600px;
        }
        
        .slide-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent-primary);
        }
        
        .slide-badge {
            background: var(--sale);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        .slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 2;
        }
        
        .slider-nav button {
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5rem;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }
        
        .slider-nav button:hover {
            background: var(--accent-primary);
            transform: scale(1.1);
        }
        
        .slider-dots {
            position: absolute;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .dot.active {
            background: var(--accent-primary);
            transform: scale(1.2);
        }
        
        /* Header simplificado */
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
        
        /* Footer minimalista */
        .footer-minimal {
            background: var(--bg-secondary);
            padding: 40px 5%;
            text-align: center;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--accent-primary);
        }
        
        .copyright {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        /* Search bar mejorada */
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
        
        /* Responsive */
        @media (max-width: 1024px) {
            .hero-slider {
                height: 400px;
            }
            
            .slide h2 {
                font-size: 2rem;
            }
            
            .user-actions-minimal button span {
                display: none;
            }
            
            .user-actions-minimal button {
                padding: 10px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-slider {
                height: 350px;
            }
            
            .slide h2 {
                font-size: 1.8rem;
            }
            
            .search-bar-minimal {
                display: none;
            }
            
            .header-minimal {
                flex-wrap: wrap;
                gap: 10px;
            }
        }
        
        <!-- Agregar estos estilos en el <style> -->
<style>
    .checkbox-group {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-top: 10px;
    }
    
    .checkbox-group input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin-top: 3px;
    }
    
    .checkbox-group label {
        font-size: 14px;
        line-height: 1.4;
    }
    
    #devRegisterModal .modal-content {
        max-width: 500px;
    }
</style>
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
            <input type="text" id="searchInput" placeholder="Buscar juegos...">
            <i class="fas fa-search"></i>
        </div>
        
        
        
        <div class="user-actions-minimal">
    <!-- Botón Explorar - SOLO UNA VEZ -->
    <button id="exploreBtn">
        <i class="fas fa-compass"></i> <span>Explorar</span>
    </button>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Botones cuando usuario está logueado -->
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
            <button id="devPanelBtn">
                <i class="fas fa-code"></i> <span>Panel Dev</span>
            </button>
        <?php endif; ?>
        
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <button id="adminPanelBtn">
                <i class="fas fa-cog"></i> <span>Panel Admin</span>
            </button>
        <?php endif; ?>
          <button class="user-indicator-btn" style="cursor: default;">
        <i class="fas fa-user-circle"></i>
        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
    </button>
        <button id="logoutBtn">
            <i class="fas fa-sign-out-alt"></i> <span>Salir</span>
        </button>
    <?php else: ?>
        <!-- Botones cuando usuario NO está logueado -->
        <button id="cartBtn" style="position: relative;">
            <i class="fas fa-shopping-cart"></i> <span>Carrito</span>
            <span id="cartCount" class="count-badge">0</span>
        </button>
          <button id="distribuirBtn">
        <i class="fas fa-upload"></i> <span>Distribuir</span>
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
    
    <!-- Carrusel de Juegos Populares -->
    <div class="hero-slider" id="heroSlider">
        <div class="slides-container" id="slidesContainer">
            <?php if (!empty($popularGames)): ?>
                <?php foreach ($popularGames as $index => $game): 
                    $precio = floatval($game['precio']);
                    $descuento = intval($game['descuento']);
                    $precioFinal = $descuento > 0 ? $precio * (1 - $descuento / 100) : $precio;
                    $imagen = $game['imagen_url'] ?: 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80';
                ?>
                <div class="slide" 
                     style="background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.6)), url('<?php echo htmlspecialchars($imagen); ?>');"
                     data-game-id="<?php echo $game['id']; ?>">
                    <div class="slide-content">
                        <?php if ($descuento > 0): ?>
                            <div class="slide-badge">-<?php echo $descuento; ?>%</div>
                        <?php endif; ?>
                        <h2><?php echo htmlspecialchars($game['titulo']); ?></h2>
                        <p><?php echo htmlspecialchars(substr($game['descripcion'], 0, 150)) . '...'; ?></p>
                        <div class="slide-price">
                            <?php if ($descuento > 0): ?>
                                <span style="text-decoration: line-through; color: #ccc; margin-right: 10px;">
                                    $<?php echo number_format($precio, 2); ?>
                                </span>
                            <?php endif; ?>
                            $<?php echo number_format($precioFinal, 2); ?>
                        </div>
                        <button class="btn btn-primary view-game-btn" 
                                data-game-id="<?php echo $game['id']; ?>">
                            Ver Detalles <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="slide" style="background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1550745165-9bc0b252726f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');">
                    <div class="slide-content">
                        <h2>Bienvenido a TechnoGames</h2>
                        <p>Descubre los mejores videojuegos para PC y consolas</p>
                        <button class="btn btn-primary" onclick="window.location.href='#popularGames'">
                            Explorar Catálogo <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="slider-nav">
            <button id="prevSlide"><i class="fas fa-chevron-left"></i></button>
            <button id="nextSlide"><i class="fas fa-chevron-right"></i></button>
        </div>
        
        <div class="slider-dots" id="sliderDots">
            <!-- Los dots se generarán con JavaScript -->
        </div>
    </div>
    
    <!-- Juegos Populares -->
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-fire"></i> Juegos Destacados</h2>
            <a href="explore.php" class="view-all">Ver todos <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="games-grid" id="popularGames">
            <!-- Los juegos se cargarán dinámicamente -->
            <div class="loading">Cargando juegos...</div>
        </div>
    </div>
    
    <!-- Ofertas Especiales -->
    <div class="container">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-tag"></i> Ofertas Especiales</h2>
            <a href="#" class="view-all">Ver todas <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="games-grid" id="saleGames">
            <!-- Los juegos se cargarán dinámicamente -->
            <div class="loading">Cargando ofertas...</div>
        </div>
    </div>
    
    <footer class="footer-minimal">
    <div class="footer-content">
        <div class="footer-section">
            <h4><i class="fas fa-info-circle"></i> Información</h4>
            <ul>
                <li><a href="about.php"><i class="fas fa-building"></i> Sobre Nosotros</a></li>
                <li><a href="about.php#contacto"><i class="fas fa-envelope"></i> Contacto</a></li>
                <li><a href="about.php#soporte"><i class="fas fa-headset"></i> Soporte</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4><i class="fas fa-file-contract"></i> Legal</h4>
            <ul>
                <li><a href="about.php#terminos"><i class="fas fa-file-contract"></i> Términos de Servicio</a></li>
                <li><a href="about.php#privacidad"><i class="fas fa-shield-alt"></i> Política de Privacidad</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h4><i class="fas fa-gamepad"></i> Categorías</h4>
            <div class="category-tags">
                <span class="tag">Aventura</span>
                <span class="tag">Acción</span>
                <span class="tag">RPG</span>
                <span class="tag">Estrategia</span>
                <span class="tag">Deportes</span>
                <span class="tag">Simulación</span>
            </div>
        </div>
        
        <div class="footer-section">
            <h4><i class="fas fa-share-alt"></i> Conéctate</h4>
            <div class="social-icons">
                <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" title="Discord"><i class="fab fa-discord"></i></a>
                <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
            </div>
            <p style="margin-top: 15px; font-size: 14px; color: var(--text-secondary);">
                <i class="fas fa-envelope"></i> contacto@technogames.com
            </p>
        </div>
    </div>
    
    <div class="copyright">
        <p>&copy; <?php echo date('Y'); ?> TechnoGames. Todos los derechos reservados.</p>
        <p class="footer-note">Proyecto educativo - No es una tienda real de videojuegos.</p>
    </div>
</footer>
    <!-- Modal de Inicio de Sesión -->
    <div class="modal" id="loginModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Iniciar Sesión</h2>
                <button class="close-modal">&times;</button>
            </div>
            <form id="loginForm">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="loginEmail">Correo electrónico</label>
                    <input type="email" id="loginEmail" name="email" required>
                </div>
                <div class="form-group">
                    <label for="loginPassword">Contraseña</label>
                    <input type="password" id="loginPassword" name="password" required>
                </div>
                <button type="submit" class="form-submit">Iniciar Sesión</button>
            </form>
            <div class="form-toggle">
                ¿No tienes una cuenta? <a id="switchToRegister">Regístrate aquí</a>
            </div>
        </div>
    </div>
    
    <div class="modal" id="registerModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-user"></i> Registro de Comprador</h2>
            <button class="close-modal">&times;</button>
        </div>
        <form id="registerForm">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="role" value="user">
            <input type="hidden" name="register_type" value="buyer">
            
            <div class="form-group">
                <label for="registerName"><i class="fas fa-user"></i> Nombre de usuario</label>
                <input type="text" id="registerName" name="username" required 
                       placeholder="Elije un nombre de usuario">
            </div>
            
            <div class="form-group">
                <label for="registerEmail"><i class="fas fa-envelope"></i> Correo electrónico</label>
                <input type="email" id="registerEmail" name="email" required 
                       placeholder="tu@email.com">
            </div>
            
            <div class="form-group">
                <label for="registerPassword"><i class="fas fa-lock"></i> Contraseña</label>
                <input type="password" id="registerPassword" name="password" required 
                       placeholder="Mínimo 8 caracteres">
            </div>
            
            <div class="form-group">
                <label for="registerConfirmPassword"><i class="fas fa-lock"></i> Confirmar contraseña</label>
                <input type="password" id="registerConfirmPassword" name="confirm_password" required 
                       placeholder="Repite tu contraseña">
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        Acepto los <a href="#" style="color: var(--accent-primary);">Términos y Condiciones</a>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="form-submit">
                <i class="fas fa-user-plus"></i> Crear Cuenta de Comprador
            </button>
        </form>
        
        <div class="form-toggle">
            ¿Ya tienes una cuenta? <a id="switchToLogin">Inicia sesión aquí</a>
        </div>
        
        <div class="form-toggle" style="margin-top: 10px;">
            ¿Eres desarrollador? <a id="switchToDevRegister">Regístrate aquí</a>
        </div>
    </div>
</div>
    
    <div class="modal" id="devRegisterModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-code"></i> Registro de Desarrollador</h2>
            <button class="close-modal">&times;</button>
        </div>
        <form id="devRegisterForm">
            <input type="hidden" name="action" value="register">
            <input type="hidden" name="role" value="dev">
            <input type="hidden" name="register_type" value="developer">
            
            <div class="form-group">
                <label for="devRegisterName"><i class="fas fa-user"></i> Nombre de usuario</label>
                <input type="text" id="devRegisterName" name="username" required 
                       placeholder="Nombre para mostrar en la plataforma">
            </div>
            
            <div class="form-group">
                <label for="devRegisterEmail"><i class="fas fa-envelope"></i> Correo electrónico</label>
                <input type="email" id="devRegisterEmail" name="email" required 
                       placeholder="tu@empresa.com">
            </div>
            
            <div class="form-group">
                <label for="devCompanyName"><i class="fas fa-building"></i> Nombre de la empresa/estudio</label>
                <input type="text" id="devCompanyName" name="company_name" 
                       placeholder="Opcional - Nombre de tu empresa">
            </div>
            
            <div class="form-group">
                <label for="devRegisterPassword"><i class="fas fa-lock"></i> Contraseña</label>
                <input type="password" id="devRegisterPassword" name="password" required 
                       placeholder="Mínimo 8 caracteres">
            </div>
            
            <div class="form-group">
                <label for="devRegisterConfirmPassword"><i class="fas fa-lock"></i> Confirmar contraseña</label>
                <input type="password" id="devRegisterConfirmPassword" name="confirm_password" required 
                       placeholder="Repite tu contraseña">
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="devTerms" name="terms" required>
                    <label for="devTerms">
                        Acepto los <a href="#" style="color: var(--accent-primary);">Términos y Condiciones</a> 
                        y <a href="#" style="color: var(--accent-primary);">Acuerdo de Distribución</a>
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <p style="font-size: 14px; color: var(--text-secondary);">
                    <i class="fas fa-info-circle"></i> Como desarrollador podrás subir tus juegos 
                    y recibir el 70% de las ventas.
                </p>
            </div>
            
            <button type="submit" class="form-submit" style="background: linear-gradient(135deg, #4CAF50, #2E7D32);">
                <i class="fas fa-user-plus"></i> Registrarme como Desarrollador
            </button>
        </form>
        
        <div class="form-toggle">
            ¿Ya tienes una cuenta? <a id="switchToDevLogin">Inicia sesión aquí</a>
        </div>
        
        <div class="form-toggle" style="margin-top: 10px;">
            ¿Quieres registrarte como comprador? <a id="switchToUserRegister">Registro para compradores</a>
        </div>
    </div>
</div>
    
    <!-- Modal del Carrito -->
    <div class="modal" id="cartModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Tu Carrito de Compras</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div id="cartItems">
                <div class="empty-cart">Tu carrito está vacío</div>
            </div>
            <div class="cart-total">
                <strong>Total: $<span id="cartTotal">0.00</span></strong>
            </div>
            <button id="checkoutBtn" class="form-submit">Proceder al Pago</button>
        </div>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification"></div>

    <script>
        // Pasar información de sesión de PHP a JavaScript
        window.userSession = {
            isLoggedIn: <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>,
            userId: <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>,
            userName: '<?php echo isset($_SESSION['user_name']) ? addslashes($_SESSION['user_name']) : ''; ?>',
            userRole: '<?php echo isset($_SESSION['user_role']) ? $_SESSION['user_role'] : ''; ?>'
        };
        
        // Datos del carrusel desde PHP
        window.sliderGames = <?php echo json_encode($popularGames); ?>;
    </script>

    <script>
        // Carrusel de juegos
        let currentSlide = 0;
        const slidesContainer = document.getElementById('slidesContainer');
        const sliderDots = document.getElementById('sliderDots');
        const slides = document.querySelectorAll('.slide');
        
        // Crear dots para el carrusel
        function createDots() {
            sliderDots.innerHTML = '';
            slides.forEach((_, index) => {
                const dot = document.createElement('div');
                dot.className = 'dot' + (index === currentSlide ? ' active' : '');
                dot.addEventListener('click', () => goToSlide(index));
                sliderDots.appendChild(dot);
            });
        }
        
        // En el script, agregar:
document.getElementById('distribuirBtn')?.addEventListener('click', () => {
    document.getElementById('devRegisterModal').style.display = 'flex';
});

// Cambiar de registro de desarrollador a login
document.getElementById('switchToDevLogin')?.addEventListener('click', () => {
    document.getElementById('devRegisterModal').style.display = 'none';
    document.getElementById('loginModal').style.display = 'flex';
});

// Cambiar de registro de desarrollador a registro de usuario
document.getElementById('switchToUserRegister')?.addEventListener('click', () => {
    document.getElementById('devRegisterModal').style.display = 'none';
    document.getElementById('registerModal').style.display = 'flex';
});

// Cerrar modal de desarrollador
window.addEventListener('click', (event) => {
    if (event.target === document.getElementById('devRegisterModal')) {
        document.getElementById('devRegisterModal').style.display = 'none';
    }
});

document.getElementById('switchToDevRegister')?.addEventListener('click', () => {
    document.getElementById('registerModal').style.display = 'none';
    document.getElementById('devRegisterModal').style.display = 'flex';
});

document.getElementById('devRegisterForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('¡Cuenta de desarrollador creada exitosamente!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de conexión', 'error');
    });
});
        
        // Ir a slide específico
        function goToSlide(index) {
            currentSlide = index;
            updateSlider();
        }
        
        // Actualizar slider
        function updateSlider() {
            slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            // Actualizar dots
            document.querySelectorAll('.dot').forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }
        
        // Siguiente slide
        document.getElementById('nextSlide').addEventListener('click', () => {
            currentSlide = (currentSlide + 1) % slides.length;
            updateSlider();
        });
        
        // Slide anterior
        document.getElementById('prevSlide').addEventListener('click', () => {
            currentSlide = (currentSlide - 1 + slides.length) % slides.length;
            updateSlider();
        });
        
        // Auto-play del carrusel
        let sliderInterval = setInterval(() => {
            currentSlide = (currentSlide + 1) % slides.length;
            updateSlider();
        }, 5000);
        
        // Pausar auto-play al interactuar
        const heroSlider = document.getElementById('heroSlider');
        heroSlider.addEventListener('mouseenter', () => clearInterval(sliderInterval));
        heroSlider.addEventListener('mouseleave', () => {
            sliderInterval = setInterval(() => {
                currentSlide = (currentSlide + 1) % slides.length;
                updateSlider();
            }, 5000);
        });
        
        // Click en slide para ver detalles
        document.querySelectorAll('.view-game-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                const gameId = this.getAttribute('data-game-id');
                window.location.href = `game_detail.php?id=${gameId}`;
            });
        });
        
        // Click en slide para ver detalles
        document.querySelectorAll('.slide').forEach(slide => {
            slide.addEventListener('click', function() {
                const gameId = this.getAttribute('data-game-id');
                if (gameId) {
                    window.location.href = `game_detail.php?id=${gameId}`;
                }
            });
        });
        
        // Inicializar carrusel
        createDots();
        
        // Botones de navegación
        document.getElementById('wishlistBtn')?.addEventListener('click', () => {
            window.location.href = 'wishlist.php';
        });
        
        document.getElementById('libraryBtn')?.addEventListener('click', () => {
            window.location.href = 'library.php';
        });
        
        document.getElementById('logoutBtn')?.addEventListener('click', () => {
            window.location.href = 'index.php?logout=true';
        });
        
        // Configurar event listeners para los modales
        document.getElementById('loginBtn')?.addEventListener('click', () => {
            document.getElementById('loginModal').style.display = 'flex';
        });
        
        document.getElementById('registerBtn')?.addEventListener('click', () => {
            document.getElementById('registerModal').style.display = 'flex';
        });
        
        // En el script de index.php
document.getElementById('cartBtn')?.addEventListener('click', () => {
    window.location.href = 'cart.php'; // Cambiar de modal a página
});
        
        // Botones de panel admin y dev
document.getElementById('adminPanelBtn')?.addEventListener('click', () => {
    window.location.href = 'admin_panel.php';
});

document.getElementById('devPanelBtn')?.addEventListener('click', () => {
    window.location.href = 'developer_panel.php';
});
        
        document.getElementById('cartBtn')?.addEventListener('click', () => {
            document.getElementById('cartModal').style.display = 'flex';
        });
        
        document.querySelectorAll('.close-modal').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
            });
        });
        
        // Cambiar entre modales de login y registro
        document.getElementById('switchToRegister')?.addEventListener('click', () => {
            document.getElementById('loginModal').style.display = 'none';
            document.getElementById('registerModal').style.display = 'flex';
        });
        
        document.getElementById('switchToLogin')?.addEventListener('click', () => {
            document.getElementById('registerModal').style.display = 'none';
            document.getElementById('loginModal').style.display = 'flex';
        });
        
        // Cerrar modal al hacer clic fuera del contenido
        window.addEventListener('click', (event) => {
            if (event.target === document.getElementById('loginModal')) {
                document.getElementById('loginModal').style.display = 'none';
            }
            if (event.target === document.getElementById('registerModal')) {
                document.getElementById('registerModal').style.display = 'none';
            }
            if (event.target === document.getElementById('cartModal')) {
                document.getElementById('cartModal').style.display = 'none';
            }
        });
        
        // Función para actualizar contadores
        function updateCounters(cartCount, wishlistCount) {
            const cartBadge = document.getElementById('cartCount');
            const wishlistBadge = document.getElementById('wishlistCount');
            
            if (cartBadge) {
                cartBadge.textContent = cartCount;
                cartBadge.style.animation = 'none';
                setTimeout(() => {
                    cartBadge.style.animation = 'pulse 0.5s ease-in-out';
                }, 10);
            }
            
            if (wishlistBadge) {
                wishlistBadge.textContent = wishlistCount;
                wishlistBadge.style.animation = 'none';
                setTimeout(() => {
                    wishlistBadge.style.animation = 'pulse 0.5s ease-in-out';
                }, 10);
            }
        }
        
        // Mostrar mensaje de redirección si existe
<?php if (isset($redirectMessage)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Mostrar modal de login con mensaje
        document.getElementById('loginModal').style.display = 'flex';
        
        // Crear notificación
        const notification = document.createElement('div');
        notification.className = 'notification show error';
        notification.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($redirectMessage); ?></span>
        `;
        
        // Estilos para la notificación
        notification.style.cssText = `
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
            z-index: 10001;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        // Remover después de 5 segundos
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    });
<?php endif; ?>
        
        // Exponer función globalmente
        window.updateCounters = updateCounters;
    </script>
    
    <script src="script.js"></script>
</body>
</html>
