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
        $this->addSql('
            CREATE TABLE IF NOT EXISTS pedidos (
                id_pedido VARCHAR(10) NOT NULL,
                id_usuario INT NOT NULL,
                fecha_pedido DATETIME NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                estado VARCHAR(20) NOT NULL DEFAULT "pendiente",
                notas TEXT DEFAULT NULL,
                PRIMARY KEY (id_pedido),
                CONSTRAINT FK_pedidos_usuarios FOREIGN KEY (id_usuario)
                    REFERENCES usuarios (id_usuario) ON DELETE RESTRICT
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        ');

        // Asegurarse de que las columnas existen y tienen el tipo correcto
        $this->addSql('
            ALTER TABLE pedidos
            MODIFY COLUMN estado VARCHAR(20) NOT NULL DEFAULT "pendiente",
            MODIFY COLUMN fecha_pedido DATETIME NOT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS pedidos');
    }
}
