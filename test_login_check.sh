#!/bin/bash

echo "======================================"
echo "Probando endpoint /api/login_check"
echo "======================================"
echo ""

# Colores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# URL base
BASE_URL="http://localhost:8001"

echo -e "${YELLOW}Test 1: Login exitoso${NC}"
echo "--------------------------------------"
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/api/login_check" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "test@example.com",
    "password": "password123"
  }')

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo "HTTP Status: $HTTP_CODE"
echo "Response Body:"
echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
echo ""

if [ "$HTTP_CODE" = "200" ]; then
    echo -e "${GREEN}✓ Login exitoso devuelve 200${NC}"
    if echo "$BODY" | jq -e '.token' > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Response contiene 'token'${NC}"
    else
        echo -e "${RED}✗ Response NO contiene 'token'${NC}"
    fi
    if echo "$BODY" | jq -e '.username' > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Response contiene 'username'${NC}"
    else
        echo -e "${RED}✗ Response NO contiene 'username'${NC}"
    fi
else
    echo -e "${RED}✗ Login exitoso NO devuelve 200${NC}"
fi

echo ""
echo -e "${YELLOW}Test 2: Login fallido (credenciales incorrectas)${NC}"
echo "--------------------------------------"
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/api/login_check" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "wrong@example.com",
    "password": "wrongpassword"
  }')

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

echo "HTTP Status: $HTTP_CODE"
echo "Response Body:"
echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"
echo ""

if [ "$HTTP_CODE" = "401" ]; then
    echo -e "${GREEN}✓ Login fallido devuelve 401${NC}"
    if echo "$BODY" | jq -e '.error' > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Response contiene 'error'${NC}"
    else
        echo -e "${RED}✗ Response NO contiene 'error'${NC}"
    fi
    if echo "$BODY" | jq -e '.success == false' > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Response contiene 'success: false'${NC}"
    else
        echo -e "${RED}✗ Response NO contiene 'success: false'${NC}"
    fi
else
    echo -e "${RED}✗ Login fallido NO devuelve 401${NC}"
fi

echo ""
echo "======================================"
echo "Tests completados"
echo "======================================"
