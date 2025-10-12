-- =====================================================
-- ESQUEMA DE BASE DE DATOS OPTIMIZADO PARA TIENDA
-- Sin redundancia, con índices y restricciones apropiadas
-- =====================================================

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS pureinkafoods CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pureinkafoods;

-- =====================================================
-- TABLA: categorias
-- =====================================================
CREATE TABLE IF NOT EXISTS categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    imagen_categoria VARCHAR(255),
    estado BOOLEAN DEFAULT TRUE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_categoria_nombre (nombre),
    INDEX idx_categoria_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: productos
-- =====================================================
CREATE TABLE IF NOT EXISTS productos (
    id_producto INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NOT NULL,
    id_categoria INT NOT NULL,
    imagen_producto VARCHAR(255),
    precio DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    estado BOOLEAN DEFAULT TRUE,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_producto_nombre (nombre),
    INDEX idx_producto_categoria (id_categoria),
    INDEX idx_producto_precio (precio),
    INDEX idx_producto_stock (stock),
    INDEX idx_producto_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: clientes
-- =====================================================
CREATE TABLE IF NOT EXISTS clientes (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    dni VARCHAR(20),
    estado BOOLEAN DEFAULT TRUE,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    roles JSON DEFAULT ('["ROLE_USER"]'),
    
    INDEX idx_cliente_email (email),
    INDEX idx_cliente_dni (dni),
    INDEX idx_cliente_estado (estado),
    INDEX idx_cliente_nombre (nombre, apellido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: pedidos
-- =====================================================
CREATE TABLE IF NOT EXISTS pedidos (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('pendiente', 'procesando', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    direccion_envio TEXT,
    notas TEXT,
    
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_pedido_cliente (id_cliente),
    INDEX idx_pedido_fecha (fecha_pedido),
    INDEX idx_pedido_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: detalle_pedidos
-- =====================================================
CREATE TABLE IF NOT EXISTS detalle_pedidos (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_detalle_pedido (id_pedido),
    INDEX idx_detalle_producto (id_producto),
    
    UNIQUE KEY unique_pedido_producto (id_pedido, id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: pagos
-- =====================================================
CREATE TABLE IF NOT EXISTS pagos (
    id_pago INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_pedido INT,
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia', 'paypal') NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'completado', 'fallido', 'reembolsado') DEFAULT 'pendiente',
    fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
    referencia_transaccion VARCHAR(255),
    
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido) ON DELETE SET NULL ON UPDATE CASCADE,
    
    INDEX idx_pago_cliente (id_cliente),
    INDEX idx_pago_pedido (id_pedido),
    INDEX idx_pago_fecha (fecha_pago),
    INDEX idx_pago_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: devoluciones
-- =====================================================
CREATE TABLE IF NOT EXISTS devoluciones (
    id_devolucion INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_pedido INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    motivo TEXT NOT NULL,
    estado ENUM('solicitada', 'aprobada', 'rechazada', 'procesada') DEFAULT 'solicitada',
    fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_procesamiento DATETIME,
    
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_devolucion_cliente (id_cliente),
    INDEX idx_devolucion_pedido (id_pedido),
    INDEX idx_devolucion_estado (estado),
    INDEX idx_devolucion_fecha (fecha_solicitud)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: orders (para compatibilidad con API Platform)
-- =====================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado VARCHAR(50) DEFAULT 'pending',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_order_cliente (id_cliente),
    INDEX idx_order_estado (estado),
    INDEX idx_order_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: detalle_boletas (para facturación)
-- =====================================================
CREATE TABLE IF NOT EXISTS detalle_boletas (
    id_detalle_boleta INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    numero_boleta VARCHAR(50) NOT NULL,
    fecha_emision DATETIME DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL,
    igv DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('emitida', 'anulada') DEFAULT 'emitida',
    
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_boleta_cliente (id_cliente),
    INDEX idx_boleta_numero (numero_boleta),
    INDEX idx_boleta_fecha (fecha_emision),
    
    UNIQUE KEY unique_numero_boleta (numero_boleta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: detalle_facturas (para facturación empresarial)
-- =====================================================
CREATE TABLE IF NOT EXISTS detalle_facturas (
    id_detalle_factura INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    numero_factura VARCHAR(50) NOT NULL,
    ruc VARCHAR(20) NOT NULL,
    razon_social VARCHAR(255) NOT NULL,
    fecha_emision DATETIME DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(10,2) NOT NULL,
    igv DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('emitida', 'anulada') DEFAULT 'emitida',
    
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    INDEX idx_factura_cliente (id_cliente),
    INDEX idx_factura_numero (numero_factura),
    INDEX idx_factura_ruc (ruc),
    INDEX idx_factura_fecha (fecha_emision),
    
    UNIQUE KEY unique_numero_factura (numero_factura)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERTAR DATOS DE EJEMPLO (OPCIONAL)
-- =====================================================

-- Categorías de ejemplo
INSERT IGNORE INTO categorias (nombre, descripcion) VALUES
('Electrónicos', 'Dispositivos electrónicos y gadgets'),
('Ropa', 'Vestimenta y accesorios'),
('Hogar', 'Artículos para el hogar'),
('Deportes', 'Equipamiento deportivo');

-- Usuario administrador de ejemplo (password: admin123)
INSERT IGNORE INTO clientes (nombre, apellido, email, password, roles) VALUES
('Admin', 'Sistema', 'admin@tienda.com', '$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '["ROLE_ADMIN", "ROLE_USER"]');

-- =====================================================
-- TRIGGERS PARA MANTENER CONSISTENCIA
-- =====================================================

-- Trigger para actualizar el total del pedido cuando se modifica el detalle
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS update_pedido_total_after_insert
AFTER INSERT ON detalle_pedidos
FOR EACH ROW
BEGIN
    UPDATE pedidos 
    SET total = (
        SELECT SUM(subtotal) 
        FROM detalle_pedidos 
        WHERE id_pedido = NEW.id_pedido
    )
    WHERE id_pedido = NEW.id_pedido;
END$$

CREATE TRIGGER IF NOT EXISTS update_pedido_total_after_update
AFTER UPDATE ON detalle_pedidos
FOR EACH ROW
BEGIN
    UPDATE pedidos 
    SET total = (
        SELECT SUM(subtotal) 
        FROM detalle_pedidos 
        WHERE id_pedido = NEW.id_pedido
    )
    WHERE id_pedido = NEW.id_pedido;
END$$

CREATE TRIGGER IF NOT EXISTS update_pedido_total_after_delete
AFTER DELETE ON detalle_pedidos
FOR EACH ROW
BEGIN
    UPDATE pedidos 
    SET total = COALESCE((
        SELECT SUM(subtotal) 
        FROM detalle_pedidos 
        WHERE id_pedido = OLD.id_pedido
    ), 0.00)
    WHERE id_pedido = OLD.id_pedido;
END$$
DELIMITER ;

-- =====================================================
-- VISTAS ÚTILES PARA CONSULTAS FRECUENTES
-- =====================================================

-- Vista de productos con información de categoría
CREATE OR REPLACE VIEW vista_productos AS
SELECT 
    p.id_producto,
    p.nombre,
    p.descripcion,
    p.precio,
    p.stock,
    p.imagen_producto,
    p.estado,
    c.nombre AS categoria_nombre,
    c.id_categoria
FROM productos p
INNER JOIN categorias c ON p.id_categoria = c.id_categoria
WHERE p.estado = TRUE AND c.estado = TRUE;

-- Vista de pedidos con información del cliente
CREATE OR REPLACE VIEW vista_pedidos AS
SELECT 
    p.id_pedido,
    p.fecha_pedido,
    p.estado,
    p.total,
    CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre,
    c.email AS cliente_email,
    c.telefono AS cliente_telefono
FROM pedidos p
INNER JOIN clientes c ON p.id_cliente = c.id_cliente;

COMMIT;
