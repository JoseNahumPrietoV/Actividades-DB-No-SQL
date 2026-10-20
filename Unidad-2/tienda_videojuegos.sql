CREATE DATABASE IF NOT EXISTS tienda_videojuegos;
USE tienda_videojuegos;

-- Tabla de usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'dev', 'user') DEFAULT 'user',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de juegos
CREATE TABLE juegos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    descuento INT DEFAULT 0,
    imagen_url VARCHAR(255),
    categoria VARCHAR(50),
    estado ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente',
    desarrollador_id INT,
    requisitos TEXT,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (desarrollador_id) REFERENCES usuarios(id)
);

-- Tabla de carrito de compras
CREATE TABLE carrito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    juego_id INT NOT NULL,
    cantidad INT DEFAULT 1,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (juego_id) REFERENCES juegos(id)
);

-- Tabla de ventas
CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    juego_id INT NOT NULL,
    precio_venta DECIMAL(10,2) NOT NULL,
    comision DECIMAL(10,2) NOT NULL,
    fecha_compra TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (juego_id) REFERENCES juegos(id)
);

-- Tabla de reseñas
CREATE TABLE reseñas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    juego_id INT NOT NULL,
    calificacion INT NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
    comentario TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (juego_id) REFERENCES juegos(id),
    UNIQUE(usuario_id, juego_id)
);

-- Tabla de biblioteca
CREATE TABLE biblioteca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    juego_id INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (juego_id) REFERENCES juegos(id),
    UNIQUE(usuario_id, juego_id)
);

-- Tabla de lista de deseos
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    juego_id INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (juego_id) REFERENCES juegos(id),
    UNIQUE(usuario_id, juego_id)
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    game_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    discount INT DEFAULT 0,
    final_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES juegos(id) ON DELETE CASCADE,
    INDEX idx_order_items_order_id (order_id),
    INDEX idx_order_items_game_id (game_id)
);

-- Crear un trigger para prevenir múltiples compras del mismo juego
DELIMITER $$

CREATE TRIGGER before_ventas_insert
BEFORE INSERT ON ventas
FOR EACH ROW
BEGIN
    DECLARE game_in_library INT;
    
    -- Verificar si el usuario ya tiene el juego en su biblioteca
    SELECT COUNT(*) INTO game_in_library 
    FROM biblioteca 
    WHERE usuario_id = NEW.usuario_id 
    AND juego_id = NEW.juego_id;
    
    IF game_in_library > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El usuario ya posee este juego';
    END IF;
END$$

DELIMITER ;

-- Crear un trigger para el carrito también
DELIMITER $$

CREATE TRIGGER before_carrito_insert
BEFORE INSERT ON carrito
FOR EACH ROW
BEGIN
    DECLARE game_in_library INT;
    
    -- Verificar si el usuario ya tiene el juego en su biblioteca
    SELECT COUNT(*) INTO game_in_library 
    FROM biblioteca 
    WHERE usuario_id = NEW.usuario_id 
    AND juego_id = NEW.juego_id;
    
    IF game_in_library > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El usuario ya posee este juego';
    END IF;
END$$

DELIMITER ;

-- Insertar datos de ejemplo CON CONTRASEÑAS HASHEADAS
INSERT INTO usuarios (nombre_usuario, email, password, rol) VALUES 
('admin', 'admin@technogames.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'), -- password: admin123
('dev1', 'dev1@technogames.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dev'), -- password: dev1123
('user1', 'user1@technogames.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'); -- password: user1123

