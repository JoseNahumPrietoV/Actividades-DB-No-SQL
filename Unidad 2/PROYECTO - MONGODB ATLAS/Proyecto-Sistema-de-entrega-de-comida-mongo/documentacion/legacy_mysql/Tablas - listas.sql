use sistemawebsnackver;

-- 1. CLIENTE (PADRE)
CREATE TABLE cliente (
    idcliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre_cliente VARCHAR(100) NOT NULL,
    apellido_cliente VARCHAR(100) NOT NULL,
    telefono_cliente VARCHAR(15),
    email_cliente VARCHAR(100) UNIQUE NOT NULL,
    password_cliente VARCHAR(255) NOT NULL,
    confirm_password_cliente VARCHAR(255) NOT NULL, -- (¡Recuerda eliminar en producción!)
    fecharegistro DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- 2. VENDEDOR (PADRE)
CREATE TABLE vendedor (
    idvendedor INT AUTO_INCREMENT PRIMARY KEY,
    nombre_vendedor VARCHAR(100) NOT NULL,
    apellido_vendedor VARCHAR(100) NOT NULL,
    telefono_vendedor VARCHAR(15),
    email_vendedor VARCHAR(100) UNIQUE NOT NULL,
    password_vendedor VARCHAR(255) NOT NULL,
    confirm_password_vendedor VARCHAR(255) NOT NULL, 
    nombre_negocio VARCHAR(150),
    descripcion VARCHAR(500) NOT NULL,
    dirección VARCHAR(500) NOT NULL,
    fecharegistro DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- 3. CATEGORÍA DE PRODUCTO (PADRE)
CREATE TABLE categoriaproducto (
    idcategoria INT AUTO_INCREMENT PRIMARY KEY,
    nombrecategoria VARCHAR(50) NOT NULL,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- 4. PRODUCTO
CREATE TABLE producto (
    idproducto INT AUTO_INCREMENT PRIMARY KEY,
    idvendedor INT,
    idcategoria INT,
    descripcion TEXT,
    nombreproducto VARCHAR(100),
    disponible BOOLEAN DEFAULT TRUE,
    imagen VARCHAR(255),
    precio DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (idvendedor) REFERENCES vendedor(idvendedor),
    FOREIGN KEY (idcategoria) REFERENCES categoriaproducto(idcategoria)
);

-- 5. PEDIDO
CREATE TABLE pedido (
    idpedido INT AUTO_INCREMENT PRIMARY KEY,
    idcliente INT,
    idvendedor INT,
    fechapedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    fechaentrega DATETIME NULL,
    total DECIMAL(10, 2),
    estadopedido ENUM('Pendiente', 'En preparación','Listo','Entregado','Rechazado','Cancelado') DEFAULT 'Pendiente',
    metodopago ENUM('Efectivo','Transferencia') DEFAULT 'Efectivo',
    referenciapago VARCHAR(100),
    notificacion_cliente INT,
    notificacion_vendedor INT,
    FOREIGN KEY (idcliente) REFERENCES cliente(idcliente),
    FOREIGN KEY (idvendedor) REFERENCES vendedor(idvendedor)
);

-- 6. CARRO (carrito)
CREATE TABLE carrito (
    idcarrito INT AUTO_INCREMENT PRIMARY KEY,
    idcliente INT,
    subtotal DECIMAL(10, 2),
    fechaagregado DATETIME,
    FOREIGN KEY (idcliente) REFERENCES cliente(idcliente)
);

-- 7. DETALLE DEL PEDIDO
CREATE TABLE detallepedido (
    iddetallepedido INT AUTO_INCREMENT PRIMARY KEY,
    idpedido INT,
    idproducto INT,
    cantidad INT,
    precio_unitario DECIMAL(10, 2) NOT NULL,
    descuento_porcentaje DECIMAL(5, 2) DEFAULT 0.00,
    importe DECIMAL(10, 2),
    FOREIGN KEY (idpedido) REFERENCES pedido(idpedido),
    FOREIGN KEY (idproducto) REFERENCES producto(idproducto)
);

-- 8. DETALLE DEL CARRO (detallecarrito)
CREATE TABLE detallecarrito (
    iddetallecarrito INT AUTO_INCREMENT PRIMARY KEY,
    idcarrito INT NOT NULL,
    idproducto INT NOT NULL,
    cantidad INT NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (idcarrito) REFERENCES carrito(idcarrito),
    FOREIGN KEY (idproducto) REFERENCES producto(idproducto)
);

-- 9. RESEÑA
CREATE TABLE resena (
    idresena INT AUTO_INCREMENT PRIMARY KEY,
    idcliente INT,
    idproducto INT,
    calificacion TINYINT CHECK (calificacion BETWEEN 1 AND 5),
    comentario TEXT NULL,
    fecharesena DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idproducto) REFERENCES producto(idproducto),
    FOREIGN KEY (idcliente) REFERENCES cliente(idcliente)
);

-- 10. NOTIFICACIONES
CREATE TABLE notificaciones (
    idnotificacion INT AUTO_INCREMENT PRIMARY KEY,
    idcliente INT,
    idvendedor INT,
    mensaje ENUM ('Selecciona', 'Tu pedido está en camino','Tu pedido fue cancelado','Tu pedido fue entregado', 'Tu pedido se atrasó') DEFAULT 'Selecciona', 
    fechaenvio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idcliente) REFERENCES cliente(idcliente),
    FOREIGN KEY (idvendedor) REFERENCES vendedor(idvendedor)
);




