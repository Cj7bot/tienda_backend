#!/bin/bash

echo "🔍 Probando Conexiones a Base de Datos"
echo "====================================="

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

show_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

show_error() {
    echo -e "${RED}❌ $1${NC}"
}

show_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

# Función para probar conexión
test_connection() {
    local user=$1
    local password=$2
    local description=$3
    
    echo ""
    show_info "Probando: $description"
    
    if [ -z "$password" ]; then
        # Sin contraseña
        mysql -u "$user" -e "SELECT 1;" 2>/dev/null
    else
        # Con contraseña
        mysql -u "$user" -p"$password" -e "SELECT 1;" 2>/dev/null
    fi
    
    if [ $? -eq 0 ]; then
        show_success "Conexión exitosa: $description"
        return 0
    else
        show_error "Conexión fallida: $description"
        return 1
    fi
}

# Verificar si MariaDB/MySQL está ejecutándose
echo "🔍 Verificando estado del servicio..."
if systemctl is-active --quiet mariadb; then
    show_success "MariaDB está ejecutándose"
elif systemctl is-active --quiet mysql; then
    show_success "MySQL está ejecutándose"
else
    show_error "Ni MariaDB ni MySQL están ejecutándose"
    echo ""
    echo "Para iniciar MariaDB/MySQL:"
    echo "sudo systemctl start mariadb"
    echo "# o"
    echo "sudo systemctl start mysql"
    exit 1
fi

# Probar diferentes configuraciones comunes
echo ""
echo "🧪 Probando configuraciones comunes..."

# Configuraciones a probar
test_connection "root" "" "root sin contraseña"
test_connection "root" "root" "root con contraseña 'root'"
test_connection "root" "password" "root con contraseña 'password'"
test_connection "root" "admin" "root con contraseña 'admin'"
test_connection "root" "123456" "root con contraseña '123456'"

# Si ninguna funciona, solicitar credenciales manualmente
echo ""
echo "Si ninguna configuración funcionó, ingresa tus credenciales:"
read -p "Usuario de MySQL/MariaDB: " DB_USER
read -s -p "Contraseña (presiona Enter si no tiene): " DB_PASSWORD
echo ""

if test_connection "$DB_USER" "$DB_PASSWORD" "credenciales personalizadas"; then
    echo ""
    show_success "¡Credenciales correctas encontradas!"
    
    # Actualizar .env.dev
    if [ -z "$DB_PASSWORD" ]; then
        # Sin contraseña
        NEW_URL="mysql://$DB_USER:@localhost:3306/tienda_db?serverVersion=mariadb-10.6.12&charset=utf8mb4"
    else
        # Con contraseña
        NEW_URL="mysql://$DB_USER:$DB_PASSWORD@localhost:3306/tienda_db?serverVersion=mariadb-10.6.12&charset=utf8mb4"
    fi
    
    # Hacer backup del archivo original
    cp .env.dev .env.dev.backup
    
    # Actualizar DATABASE_URL
    sed -i "s|DATABASE_URL=\"mysql://.*\"|DATABASE_URL=\"$NEW_URL\"|" .env.dev
    
    show_success "Archivo .env.dev actualizado"
    echo "Backup guardado como .env.dev.backup"
    
    # Probar desde Symfony
    echo ""
    echo "🧪 Probando conexión desde Symfony..."
    php bin/console doctrine:query:sql "SELECT 1 as test" 2>/dev/null
    if [ $? -eq 0 ]; then
        show_success "Symfony puede conectarse correctamente"
    else
        show_error "Symfony aún no puede conectarse"
    fi
else
    show_error "No se pudo establecer conexión con las credenciales proporcionadas"
fi

echo ""
echo "📝 Configuraciones probadas guardadas en .env.dev"
