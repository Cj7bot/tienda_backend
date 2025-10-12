<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240227002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Actualiza la estructura de la tabla pedidos con nombres en español';
    }

    public function up(Schema $schema): void
    {
        // Crear tabla clientes primero
        $this->addSql('
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
                roles JSON DEFAULT (\'["ROLE_USER"]\')
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Crear tabla categorias
        $this->addSql('
            CREATE TABLE IF NOT EXISTS categorias (
                id_categoria INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL UNIQUE,
                descripcion TEXT,
                imagen_categoria VARCHAR(255),
                estado BOOLEAN DEFAULT TRUE,
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Crear tabla productos
        $this->addSql('
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
                FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria) ON DELETE RESTRICT ON UPDATE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Crear tabla pedido (corregida para usar clientes)
        $this->addSql('
            CREATE TABLE IF NOT EXISTS pedido (
                id_pedido INT AUTO_INCREMENT PRIMARY KEY,
                id_cliente INT NOT NULL,
                fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
                total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                estado ENUM("pendiente", "procesando", "enviado", "entregado", "cancelado") DEFAULT "pendiente",
                direccion_envio TEXT,
                notas TEXT,
                FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Crear tabla detalle_pedido
        $this->addSql('
            CREATE TABLE IF NOT EXISTS detalle_pedido (
                id_detalle INT AUTO_INCREMENT PRIMARY KEY,
                id_pedido INT NOT NULL,
                id_producto INT NOT NULL,
                cantidad INT NOT NULL,
                precio_unitario DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE RESTRICT ON UPDATE CASCADE,
                UNIQUE KEY unique_pedido_producto (id_pedido, id_producto)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Crear tabla pagos
        $this->addSql('
            CREATE TABLE IF NOT EXISTS pagos (
                id_pago INT AUTO_INCREMENT PRIMARY KEY,
                id_cliente INT NOT NULL,
                id_pedido INT,
                metodo_pago ENUM("efectivo", "tarjeta", "transferencia", "paypal") NOT NULL,
                monto DECIMAL(10,2) NOT NULL,
                estado ENUM("pendiente", "completado", "fallido", "reembolsado") DEFAULT "pendiente",
                fecha_pago DATETIME DEFAULT CURRENT_TIMESTAMP,
                referencia_transaccion VARCHAR(255),
                FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
                FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE SET NULL ON UPDATE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Crear tabla devoluciones
        $this->addSql('
            CREATE TABLE IF NOT EXISTS devoluciones (
                id_devolucion INT AUTO_INCREMENT PRIMARY KEY,
                id_cliente INT NOT NULL,
                id_pedido INT NOT NULL,
                id_producto INT NOT NULL,
                cantidad INT NOT NULL,
                motivo TEXT NOT NULL,
                estado ENUM("solicitada", "aprobada", "rechazada", "procesada") DEFAULT "solicitada",
                fecha_solicitud DATETIME DEFAULT CURRENT_TIMESTAMP,
                fecha_procesamiento DATETIME,
                FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE,
                FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido) ON DELETE RESTRICT ON UPDATE CASCADE,
                FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE RESTRICT ON UPDATE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Crear tabla detalle_boletas
        $this->addSql('
            CREATE TABLE IF NOT EXISTS detalle_boletas (
                id_detalle_boleta INT AUTO_INCREMENT PRIMARY KEY,
                id_cliente INT NOT NULL,
                numero_boleta VARCHAR(50) NOT NULL UNIQUE,
                fecha_emision DATETIME DEFAULT CURRENT_TIMESTAMP,
                subtotal DECIMAL(10,2) NOT NULL,
                igv DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                total DECIMAL(10,2) NOT NULL,
                estado ENUM("emitida", "anulada") DEFAULT "emitida",
                FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Crear tabla detalle_facturas
        $this->addSql('
            CREATE TABLE IF NOT EXISTS detalle_facturas (
                id_detalle_factura INT AUTO_INCREMENT PRIMARY KEY,
                id_cliente INT NOT NULL,
                numero_factura VARCHAR(50) NOT NULL UNIQUE,
                ruc VARCHAR(20) NOT NULL,
                razon_social VARCHAR(255) NOT NULL,
                fecha_emision DATETIME DEFAULT CURRENT_TIMESTAMP,
                subtotal DECIMAL(10,2) NOT NULL,
                igv DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                total DECIMAL(10,2) NOT NULL,
                estado ENUM("emitida", "anulada") DEFAULT "emitida",
                FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Crear tabla orders (para compatibilidad con API Platform)
        $this->addSql('
            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_cliente INT NOT NULL,
                total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                estado VARCHAR(50) DEFAULT "pending",
                fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT ON UPDATE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Insertar datos iniciales
        $this->addSql('
            INSERT IGNORE INTO categorias (nombre, descripcion) VALUES
            ("Electrónicos", "Dispositivos electrónicos y gadgets"),
            ("Ropa", "Vestimenta y accesorios"),
            ("Hogar", "Artículos para el hogar"),
            ("Deportes", "Equipamiento deportivo")
        ');

        // Usuario administrador (password: admin123)
        $this->addSql('
            INSERT IGNORE INTO clientes (nombre, apellido, email, password, roles) VALUES
            ("Admin", "Sistema", "admin@pureinkafoods.com", "$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi", \'["ROLE_ADMIN", "ROLE_USER"]\')
        ');
    }

    public function down(Schema $schema): void
    {
        // Eliminar tablas en orden inverso para evitar problemas de foreign keys
        $this->addSql('DROP TABLE IF EXISTS detalle_pedido');
        $this->addSql('DROP TABLE IF EXISTS devoluciones');
        $this->addSql('DROP TABLE IF EXISTS pagos');
        $this->addSql('DROP TABLE IF EXISTS detalle_boletas');
        $this->addSql('DROP TABLE IF EXISTS detalle_facturas');
        $this->addSql('DROP TABLE IF EXISTS orders');
        $this->addSql('DROP TABLE IF EXISTS pedido');
        $this->addSql('DROP TABLE IF EXISTS productos');
        $this->addSql('DROP TABLE IF EXISTS categorias');
        $this->addSql('DROP TABLE IF EXISTS clientes');
    }
}
