<?php
require_once __DIR__ . '/config/helpers.php';
configureSession();
session_start();

// Configuración de la base de datos (solo si necesitas estadísticas)
$servername = "localhost";
$username = "tienda_user";
$password = "password123";
$dbname = "tienda_videojuegos";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);
$conn_error = $conn->connect_error;

// Obtener estadísticas para la página (opcional)
$stats = [];
if (!$conn_error) {
    // Contar juegos
    $result = $conn->query("SELECT COUNT(*) as total FROM juegos WHERE estado = 'aprobado'");
    if ($result) $stats['juegos'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Contar usuarios
    $result = $conn->query("SELECT COUNT(*) as total FROM usuarios");
    if ($result) $stats['usuarios'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Obtener fecha fundación (puedes cambiar esta fecha)
    $stats['fundacion'] = '2023';
    
    $conn->close();
} else {
    // Valores por defecto si hay error en la conexión
    $stats = [
        'juegos' => '1000+',
        'usuarios' => '5000+',
        'fundacion' => '2023'
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acerca de - TechnoGames</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .about-container {
            padding: 20px 5%;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .about-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 0;
            background: linear-gradient(135deg, rgba(42, 42, 42, 0.8), rgba(30, 30, 30, 0.9));
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .about-header h1 {
            font-size: 48px;
            margin-bottom: 15px;
            background: linear-gradient(90deg, var(--accent-primary), var(--accent-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .about-header p {
            font-size: 18px;
            color: var(--text-secondary);
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        /* Stats Section */
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid var(--accent-primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }
        
        .stat-icon {
            font-size: 40px;
            color: var(--accent-primary);
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        /* Content Sections */
        .content-section {
            margin-bottom: 50px;
            background: linear-gradient(135deg, #2A2A2A, #1E1E1E);
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .section-header i {
            font-size: 28px;
            color: var(--accent-primary);
        }
        
        .section-header h2 {
            font-size: 28px;
            color: var(--text-primary);
            margin: 0;
        }
        
        .section-content {
            color: var(--text-secondary);
            line-height: 1.8;
            font-size: 16px;
        }
        
        .section-content p {
            margin-bottom: 20px;
        }
        
        .section-content ul, .section-content ol {
            margin-left: 20px;
            margin-bottom: 20px;
        }
        
        .section-content li {
            margin-bottom: 10px;
        }
        
        /* Contact Info */
        .contact-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        
        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            transition: background 0.3s;
        }
        
        .contact-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .contact-icon {
            font-size: 24px;
            color: var(--accent-primary);
            flex-shrink: 0;
        }
        
        .contact-details h4 {
            margin: 0 0 8px 0;
            color: var(--text-primary);
        }
        
        .contact-details p {
            margin: 0;
            color: var(--text-secondary);
        }
        
        /* Team Section */
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        
        .team-member {
            text-align: center;
            padding: 25px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        
        .team-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 40px;
            color: white;
        }
        
        .team-name {
            font-size: 18px;
            color: var(--text-primary);
            margin-bottom: 5px;
        }
        
        .team-role {
            color: var(--accent-primary);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        /* FAQ Section */
        .faq-item {
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 20px;
        }
        
        .faq-item:last-child {
            border-bottom: none;
        }
        
        .faq-question {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        
        .faq-question i {
            color: var(--accent-primary);
        }
        
        .faq-answer {
            color: var(--text-secondary);
            line-height: 1.6;
            padding-left: 30px;
        }
        
        /* Back to Home Button */
        .back-home {
            text-align: center;
            margin-top: 50px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--accent-primary), #0066CC);
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 116, 228, 0.3);
            background: linear-gradient(135deg, #0066CC, #0052a3);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .about-header h1 {
                font-size: 36px;
                flex-direction: column;
                gap: 10px;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .content-section {
                padding: 25px;
            }
            
            .section-header h2 {
                font-size: 24px;
            }
        }
        
        @media (max-width: 480px) {
            .about-header {
                padding: 30px 20px;
            }
            
            .about-header h1 {
                font-size: 28px;
            }
            
            .contact-info {
                grid-template-columns: 1fr;
            }
        }
        
        /* Header minimalista igual que las otras páginas */
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
    </style>
</head>
<body>
    <!-- Header Minimalista -->
    <header class="header-minimal">
        <div class="logo-minimal">
            <i class="fas fa-gamepad"></i>
            <span>TechnoGames</span>
        </div>
        
       <div class="user-actions-minimal">
    <button onclick="window.location.href='index.php'">
        <i class="fas fa-home"></i> <span>Inicio</span>
    </button>
    <button onclick="window.location.href='explore.php'">
        <i class="fas fa-compass"></i> <span>Explorar</span>
    </button>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <button onclick="window.location.href='library.php'">
            <i class="fas fa-book"></i> <span>Biblioteca</span>
        </button>
        <button onclick="window.location.href='wishlist.php'">
            <i class="fas fa-heart"></i> <span>Deseos</span>
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
        
        <button onclick="window.location.href='index.php?logout=true'">
            <i class="fas fa-sign-out-alt"></i> <span>Salir</span>
        </button>
    <?php else: ?>
        <button onclick="window.location.href='index.php#login'">
            <i class="fas fa-sign-in-alt"></i> <span>Entrar</span>
        </button>
        <button onclick="window.location.href='index.php#register'" style="background: var(--accent-primary);">
            <i class="fas fa-user-plus"></i> <span>Registrarse</span>
        </button>
    <?php endif; ?>
</div>
    </header>
    
    <!-- Contenido Principal -->
    <div class="about-container">
        <!-- Header -->
        <div class="about-header">
            <h1><i class="fas fa-info-circle"></i> Acerca de TechnoGames</h1>
            <p>Tu destino definitivo para descubrir, comprar y disfrutar de los mejores videojuegos del mundo. Desde <?php echo $stats['fundacion']; ?> conectando a gamers con sus próximas aventuras favoritas.</p>
        </div>
        
        <!-- Estadísticas -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-gamepad"></i>
                </div>
                <div class="stat-number"><?php echo $stats['juegos']; ?></div>
                <div class="stat-label">Juegos Disponibles</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $stats['usuarios']; ?></div>
                <div class="stat-label">Jugadores Activos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number">4.8/5</div>
                <div class="stat-label">Rating de Usuarios</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="stat-number">24/7</div>
                <div class="stat-label">Disponibilidad</div>
            </div>
        </div>
        
        <!-- Sobre Nosotros -->
        <section class="content-section">
            <div class="section-header">
                <i class="fas fa-building"></i>
                <h2>Sobre Nosotros</h2>
            </div>
            <div class="section-content">
                <p>TechnoGames nació en <?php echo $stats['fundacion']; ?> con una misión simple: crear el mercado de videojuegos más accesible, seguro y emocionante del mundo. Somos apasionados por los videojuegos y creemos que cada jugador merece tener acceso a las mejores experiencias gaming.</p>
                
                <p><strong>Nuestra visión:</strong> Ser la plataforma líder global para la distribución de videojuegos, donde desarrolladores y jugadores se conecten en un ecosistema próspero y diverso.</p>
                
                <p><strong>Nuestros valores:</strong></p>
                <ul>
                    <li><strong>Pasión por los juegos:</strong> Amamos los videojuegos tanto como tú</li>
                    <li><strong>Innovación constante:</strong> Siempre buscamos mejorar tu experiencia</li>
                    <li><strong>Comunidad primero:</strong> Los jugadores son el corazón de todo lo que hacemos</li>
                    <li><strong>Transparencia:</strong> Precios claros y políticas justas</li>
                </ul>
            </div>
        </section>
        
        <!-- Contacto -->
        <section class="content-section">
            <div class="section-header">
                <i class="fas fa-envelope"></i>
                <h2>Contacto</h2>
            </div>
            <div class="section-content">
                <p>¿Tienes preguntas, sugerencias o necesitas ayuda? Estamos aquí para asistirte. Puedes contactarnos a través de los siguientes medios:</p>
                
                <div class="contact-info">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Correo Electrónico</h4>
                            <p>soporte@technogames.com</p>
                            <p>ventas@technogames.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Teléfono</h4>
                            <p>+1 (800) 123-4567</p>
                            <p>Lunes a Viernes: 9:00 - 18:00</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Oficina Principal</h4>
                            <p>123 Gaming Street</p>
                            <p>Veracruz, 94107</p>
                            <p>Mexico</p>
                        </div>
                    </div>
                </div>
                
                <p style="margin-top: 25px;"><strong>Tiempos de respuesta:</strong></p>
                <ul>
                    <li><strong>Soporte general:</strong> 24-48 horas</li>
                    <li><strong>Soporte técnico urgente:</strong> Menos de 12 horas</li>
                    <li><strong>Consultas de desarrolladores:</strong> 2-3 días hábiles</li>
                </ul>
            </div>
        </section>
        
        <!-- Términos de Servicio -->
        <section class="content-section">
            <div class="section-header">
                <i class="fas fa-file-contract"></i>
                <h2>Términos de Servicio</h2>
            </div>
            <div class="section-content">
                <p><strong>Última actualización:</strong> Diciembre 2024</p>
                
                <p>Al utilizar TechnoGames, aceptas los siguientes términos y condiciones:</p>
                
                <h4>1. Uso de la Plataforma</h4>
                <p>TechnoGames es una plataforma para la compra y distribución de videojuegos digitales. El acceso está permitido para mayores de 13 años o la edad mínima requerida en su jurisdicción.</p>
                
                <h4>2. Cuentas de Usuario</h4>
                <p>Eres responsable de mantener la confidencialidad de tu cuenta y contraseña. Todas las actividades realizadas desde tu cuenta son tu responsabilidad.</p>
                
                <h4>3. Compras y Pagos</h4>
                <p>Los precios se muestran en dólares estadounidenses (USD) e incluyen impuestos aplicables. Todas las compras son finales, excepto donde aplique nuestra política de reembolsos.</p>
                
                <h4>4. Licencias de Juegos</h4>
                <p>Al comprar un juego, adquieres una licencia personal, no transferible, para usar el software según los términos del desarrollador.</p>
                
                <h4>5. Contenido del Usuario</h4>
                <p>Eres responsable del contenido que publicas (reseñas, comentarios, etc.). Nos reservamos el derecho de eliminar contenido inapropiado.</p>
                
                <p><em>Estos términos pueden actualizarse periódicamente. Te notificaremos sobre cambios significativos.</em></p>
            </div>
        </section>
        
        <!-- Política de Privacidad -->
        <section class="content-section">
            <div class="section-header">
                <i class="fas fa-shield-alt"></i>
                <h2>Política de Privacidad</h2>
            </div>
            <div class="section-content">
                <p>En TechnoGames nos tomamos muy en serio tu privacidad. Esta política explica cómo recopilamos, usamos y protegemos tu información.</p>
                
                <h4>Información que Recopilamos</h4>
                <ul>
                    <li><strong>Información personal:</strong> Nombre, email, fecha de nacimiento</li>
                    <li><strong>Información de pago:</strong> Procesada de forma segura por nuestros socios</li>
                    <li><strong>Datos de uso:</strong> Juegos que ves, compras, preferencias</li>
                    <li><strong>Información técnica:</strong> Dirección IP, tipo de dispositivo, navegador</li>
                </ul>
                
                <h4>Cómo Usamos tu Información</h4>
                <ol>
                    <li>Proveer y mejorar nuestros servicios</li>
                    <li>Procesar tus compras y pagos</li>
                    <li>Personalizar tu experiencia</li>
                    <li>Enviar actualizaciones importantes (no spam)</li>
                    <li>Cumplir con obligaciones legales</li>
                </ol>
                
                <h4>Protección de Datos</h4>
                <p>Implementamos medidas de seguridad técnicas y organizativas para proteger tu información, incluyendo encriptación SSL, firewalls y acceso restringido.</p>
                
                <h4>Tus Derechos</h4>
                <p>Tienes derecho a acceder, corregir o eliminar tu información personal. Puedes hacerlo desde tu cuenta o contactando a nuestro equipo de soporte.</p>
                
                <p><em>No vendemos ni compartimos tu información personal con terceros para marketing.</em></p>
            </div>
        </section>
        
        <!-- Soporte -->
        <section class="content-section">
            <div class="section-header">
                <i class="fas fa-headset"></i>
                <h2>Soporte</h2>
            </div>
            <div class="section-content">
                <p>Nuestro equipo de soporte está disponible para ayudarte con cualquier problema o pregunta que puedas tener.</p>
                
                <h4>Centro de Ayuda</h4>
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        ¿Cómo compro un juego?
                    </div>
                    <div class="faq-answer">
                        1. Encuentra el juego que quieres en la tienda<br>
                        2. Haz clic en "Agregar al carrito"<br>
                        3. Ve a tu carrito y completa el proceso de pago<br>
                        4. El juego aparecerá automáticamente en tu biblioteca
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        ¿Cómo obtengo un reembolso?
                    </div>
                    <div class="faq-answer">
                        Ofrecemos reembolsos dentro de los 14 días posteriores a la compra si:<br>
                        • El juego no funciona en tu sistema<br>
                        • No has jugado más de 2 horas<br>
                        • Hay un problema técnico que no podemos resolver<br>
                        Contacta a soporte con tu número de orden.
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <i class="fas fa-question-circle"></i>
                        ¿Los juegos son para siempre?
                    </div>
                    <div class="faq-answer">
                        Sí, cuando compras un juego en TechnoGames, es tuyo para siempre. Puedes descargarlo y jugarlo cuando quieras, sin límites de tiempo.
                    </div>
                </div>
                
                <h4>Canales de Soporte</h4>
                <div class="contact-info">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Chat en Vivo</h4>
                            <p>Disponible 24/7 en tu cuenta</p>
                            <p>Tiempo de respuesta: < 5 minutos</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Sistema de Tickets</h4>
                            <p>Para problemas complejos</p>
                            <p>Respuesta en 24 horas o menos</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Equipo (opcional) -->
        <section class="content-section">
            <div class="section-header">
                <i class="fas fa-users"></i>
                <h2>Nuestro Equipo</h2>
            </div>
            <div class="section-content">
                <p>Detrás de TechnoGames hay un equipo apasionado de gamers, desarrolladores y expertos en tecnología trabajando para ofrecerte la mejor experiencia posible.</p>
                
                <div class="team-grid">
                    <div class="team-member">
                        <div class="team-avatar">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="team-name">Alberto Leo</div>
                        <div class="team-role">CEO & Fundador</div>
                        <p>"Creé TechnoGames para unir a la comunidad gamer global."</p>
                    </div>
                    <div class="team-member">
                        <div class="team-avatar">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="team-name">Luis Martinez</div>
                        <div class="team-role">CEO & Fundador</div>
                        <p>"Creé TechnoGames porque los videojuegos me apasionan."</p>
                    </div>
                   
                </div>
            </div>
        </section>
        
        <!-- Botón para volver -->
        <div class="back-home">
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Volver a la Tienda Principal
            </a>
        </div>
    </div>
    
    <script>
        // Funcionalidad para preguntas frecuentes
        document.addEventListener('DOMContentLoaded', function() {
            const faqQuestions = document.querySelectorAll('.faq-question');
            
            faqQuestions.forEach(question => {
                question.addEventListener('click', function() {
                    const answer = this.nextElementSibling;
                    const icon = this.querySelector('i');
                    
                    // Alternar visibilidad
                    if (answer.style.display === 'block') {
                        answer.style.display = 'none';
                        icon.className = 'fas fa-question-circle';
                    } else {
                        answer.style.display = 'block';
                        icon.className = 'fas fa-chevron-down';
                    }
                });
            });
            
            // Navegación del header
            document.querySelectorAll('.user-actions-minimal button').forEach(button => {
                button.addEventListener('click', function() {
                    const action = this.getAttribute('onclick');
                    if (action) {
                        // Extraer la URL del onclick
                        const urlMatch = action.match(/window\.location\.href='([^']+)'/);
                        if (urlMatch) {
                            window.location.href = urlMatch[1];
                        }
                    }
                });
            });
        });
        
        // Animación suave para las secciones
        function animateOnScroll() {
            const sections = document.querySelectorAll('.content-section, .stat-card');
            
            sections.forEach(section => {
                const sectionTop = section.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (sectionTop < windowHeight - 100) {
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }
            });
        }
        
        // Inicializar animaciones
        document.querySelectorAll('.content-section, .stat-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        });
        
        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('load', animateOnScroll);
    </script>
</body>
</html>
