#!/bin/bash

echo "🗄️  Configurando Base de Datos MariaDB para Tienda"
echo "=================================================="

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para mostrar mensajes
show_message() {
    echo -e "${GREEN}✅ $1${NC}"
}

show_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

show_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Verificar si MariaDB/MySQL está ejecutándose
if ! systemctl is-active --quiet mariadb && ! systemctl is-active --quiet mysql; then
    show_warning "MariaDB/MySQL no está ejecutándose. Intentando iniciar..."
    sudo systemctl start mariadb || sudo systemctl start mysql
    if [ $? -eq 0 ]; then
        show_message "MariaDB/MySQL iniciado correctamente"
    else
        show_error "No se pudo iniciar MariaDB/MySQL"
        exit 1
    fi
fi

# Solicitar credenciales de base de datos
echo ""
echo "Por favor, proporciona las credenciales de tu base de datos:"
read -p "Usuario de MySQL/MariaDB (default: root): " DB_USER
DB_USER=${DB_USER:-root}

read -s -p "Contraseña de MySQL/MariaDB: " DB_PASSWORD
echo ""

# Probar conexión
echo ""
echo "🔍 Probando conexión a la base de datos..."
mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1;" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    show_message "Conexión exitosa a la base de datos"
else
    show_error "No se pudo conectar a la base de datos. Verifica las credenciales."
    exit 1
fi

# Ejecutar el script SQL
echo ""
echo "📊 Creando esquema de base de datos..."
mysql -u "$DB_USER" -p"$DB_PASSWORD" < database_schema.sql
if [ $? -eq 0 ]; then
    show_message "Esquema de base de datos creado exitosamente"
else
    show_error "Error al crear el esquema de base de datos"
    exit 1
fi

# Actualizar archivo .env.dev con las credenciales correctas
echo ""
echo "⚙️  Actualizando configuración de Symfony..."

# Escapar caracteres especiales en la contraseña para URL
DB_PASSWORD_ESCAPED=$(printf '%s\n' "$DB_PASSWORD" | sed 's/[[\.*^$()+?{|]/\\&/g')

# Actualizar DATABASE_URL en .env.dev
sed -i "s|DATABASE_URL=\"mysql://.*\"|DATABASE_URL=\"mysql://$DB_USER:$DB_PASSWORD_ESCAPED@localhost:3306/tienda_db?serverVersion=mariadb-10.6.12\&charset=utf8mb4\"|" .env.dev

show_message "Configuración actualizada en .env.dev"

# Limpiar caché de Symfony
echo ""
echo "🧹 Limpiando caché de Symfony..."
php bin/console cache:clear
if [ $? -eq 0 ]; then
    show_message "Caché limpiada exitosamente"
else
    show_warning "Advertencia: No se pudo limpiar la caché"
fi

# Verificar conexión desde Symfony
echo ""
echo "🔗 Verificando conexión desde Symfony..."
php bin/console doctrine:query:sql "SELECT COUNT(*) as total_categorias FROM categorias" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    show_message "Symfony puede conectarse correctamente a la base de datos"
else
    show_error "Symfony no puede conectarse a la base de datos"
    exit 1
fi

# Mostrar información de las tablas creadas
echo ""
echo "📋 Tablas creadas en la base de datos:"
mysql -u "$DB_USER" -p"$DB_PASSWORD" -D tienda_db -e "SHOW TABLES;" 2>/dev/null | grep -v "Tables_in_tienda_db"

echo ""
show_message "¡Configuración de base de datos completada!"
echo ""
echo "📝 Información importante:"
echo "   - Base de datos: tienda_db"
echo "   - Usuario administrador: admin@tienda.com"
echo "   - Contraseña admin: admin123"
echo "   - Archivo de configuración: .env.dev"
echo ""
echo "🚀 Ahora puedes ejecutar el servidor con:"
echo "   php -S 0.0.0.0:8001 -t public"
