#!/bin/bash

# Script de prueba para la API con JWT
BASE_URL="http://localhost:8001/api"

echo "🔧 Probando API de autenticación JWT"
echo "=================================="

# Test 1: Registro de usuario
echo "📝 Test 1: Registro de usuario"
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/register" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "nombre": "Usuario Test",
    "apellido": "Apellido Test"
  }')

echo "Respuesta de registro:"
echo "$REGISTER_RESPONSE" | jq '.' 2>/dev/null || echo "$REGISTER_RESPONSE"
echo ""

# Test 2: Login y obtención de token
echo "🔐 Test 2: Login y obtención de token"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/login" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "test@example.com",
    "password": "password123"
  }')

echo "Respuesta de login:"
echo "$LOGIN_RESPONSE" | jq '.' 2>/dev/null || echo "$LOGIN_RESPONSE"

# Extraer token si existe
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.token' 2>/dev/null)
echo ""

if [ "$TOKEN" != "null" ] && [ "$TOKEN" != "" ]; then
    echo "✅ Token obtenido: ${TOKEN:0:50}..."
    
    # Test 3: Acceso a ruta protegida
    echo ""
    echo "🔒 Test 3: Acceso a ruta protegida (/api/me)"
    ME_RESPONSE=$(curl -s -X GET "$BASE_URL/me" \
      -H "Authorization: Bearer $TOKEN")
    
    echo "Respuesta de /api/me:"
    echo "$ME_RESPONSE" | jq '.' 2>/dev/null || echo "$ME_RESPONSE"
    echo ""
    
    # Test 4: Refresh token
    echo "🔄 Test 4: Refresh token"
    REFRESH_RESPONSE=$(curl -s -X POST "$BASE_URL/refresh-token" \
      -H "Authorization: Bearer $TOKEN")
    
    echo "Respuesta de refresh-token:"
    echo "$REFRESH_RESPONSE" | jq '.' 2>/dev/null || echo "$REFRESH_RESPONSE"
    echo ""
else
    echo "❌ No se pudo obtener el token"
fi

# Test 5: Acceso sin token (debe fallar)
echo "🚫 Test 5: Acceso sin token (debe fallar)"
NO_TOKEN_RESPONSE=$(curl -s -X GET "$BASE_URL/me")

echo "Respuesta sin token:"
echo "$NO_TOKEN_RESPONSE" | jq '.' 2>/dev/null || echo "$NO_TOKEN_RESPONSE"
echo ""

# Test 6: CORS preflight
echo "🌐 Test 6: CORS preflight"
CORS_RESPONSE=$(curl -s -X OPTIONS "$BASE_URL/login" \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type" \
  -I)

echo "Headers de CORS:"
echo "$CORS_RESPONSE"
echo ""

echo "✅ Pruebas completadas"
