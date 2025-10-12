#!/bin/bash

# Script de prueba para los endpoints específicos del frontend
BASE_URL="http://localhost:8001/api"

echo "🎯 Probando API para Frontend Svelte"
echo "===================================="

# Test 1: Registro con formato específico
echo "📝 Test 1: Registro de cliente (formato frontend)"
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/register" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Cliente Frontend",
    "email": "frontend@ejemplo.com",
    "password": "password123"
  }')

echo "Respuesta de registro:"
echo "$REGISTER_RESPONSE" | jq '.' 2>/dev/null || echo "$REGISTER_RESPONSE"
echo ""

# Test 2: Login con login_check
echo "🔐 Test 2: Login con /api/login_check"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/login_check" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "frontend@ejemplo.com",
    "password": "password123"
  }')

echo "Respuesta de login_check:"
echo "$LOGIN_RESPONSE" | jq '.' 2>/dev/null || echo "$LOGIN_RESPONSE"

# Extraer token si existe
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.token' 2>/dev/null)
echo ""

if [ "$TOKEN" != "null" ] && [ "$TOKEN" != "" ]; then
    echo "✅ Token obtenido: ${TOKEN:0:50}..."
    
    # Test 3: Obtener perfil
    echo ""
    echo "👤 Test 3: Obtener perfil (/api/profile)"
    PROFILE_RESPONSE=$(curl -s -X GET "$BASE_URL/profile" \
      -H "Authorization: Bearer $TOKEN")
    
    echo "Respuesta de perfil:"
    echo "$PROFILE_RESPONSE" | jq '.' 2>/dev/null || echo "$PROFILE_RESPONSE"
    echo ""
    
    # Test 4: Actualizar perfil
    echo "✏️  Test 4: Actualizar perfil (/api/profile/update)"
    UPDATE_RESPONSE=$(curl -s -X PUT "$BASE_URL/profile/update" \
      -H "Authorization: Bearer $TOKEN" \
      -H "Content-Type: application/json" \
      -d '{
        "username": "Cliente Frontend Actualizado",
        "telefono": "123456789"
      }')
    
    echo "Respuesta de actualización:"
    echo "$UPDATE_RESPONSE" | jq '.' 2>/dev/null || echo "$UPDATE_RESPONSE"
    echo ""
    
    # Test 5: Logout
    echo "🚪 Test 5: Logout (/api/logout)"
    LOGOUT_RESPONSE=$(curl -s -X POST "$BASE_URL/logout" \
      -H "Authorization: Bearer $TOKEN")
    
    echo "Respuesta de logout:"
    echo "$LOGOUT_RESPONSE" | jq '.' 2>/dev/null || echo "$LOGOUT_RESPONSE"
    echo ""
else
    echo "❌ No se pudo obtener el token"
fi

# Test 6: Acceso sin token (debe fallar)
echo "🚫 Test 6: Acceso a perfil sin token (debe fallar)"
NO_TOKEN_RESPONSE=$(curl -s -X GET "$BASE_URL/profile")

echo "Respuesta sin token:"
echo "$NO_TOKEN_RESPONSE" | jq '.' 2>/dev/null || echo "$NO_TOKEN_RESPONSE"
echo ""

# Test 7: Registro con email duplicado
echo "⚠️  Test 7: Registro con email duplicado (debe fallar)"
DUPLICATE_RESPONSE=$(curl -s -X POST "$BASE_URL/register" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Otro Cliente",
    "email": "frontend@ejemplo.com",
    "password": "password456"
  }')

echo "Respuesta de email duplicado:"
echo "$DUPLICATE_RESPONSE" | jq '.' 2>/dev/null || echo "$DUPLICATE_RESPONSE"
echo ""

echo "✅ Pruebas de API para frontend completadas"
