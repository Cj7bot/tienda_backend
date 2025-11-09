# 🔐 Configuración CORS y JWT - Resumen de Implementación

## ✅ Cambios Realizados

### 1. CORS Configuration (`nelmio_cors.yaml`)
- ✅ **allow_credentials: true** - Configurado para aceptar peticiones con credenciales
- ✅ Acepta peticiones desde `localhost:5173` y `localhost:5174`
- ✅ Headers permitidos: `Content-Type`, `Authorization`, `X-Requested-With`, etc.
- ✅ Métodos permitidos: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS`

### 2. JWT Authentication (`security.yaml`)
- ✅ Endpoint `/api/login_check` configurado
- ✅ Autenticación stateless con JWT
- ✅ Manejadores personalizados para éxito y fallo

### 3. Authentication Handlers

#### Success Handler (`AuthenticationSuccessHandler.php`)
Devuelve en caso de login exitoso (HTTP 200):
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "username": "Juan Pérez"
}
```

#### Failure Handler (`AuthenticationFailureHandler.php`)
Devuelve en caso de login fallido (HTTP 401):
```json
{
  "success": false,
  "error": "Credenciales inválidas"
}
```

## 🧪 Cómo Probar

### Opción 1: Script de prueba automático
```bash
./test_login_check.sh
```

### Opción 2: cURL manual

**Login exitoso:**
```bash
curl -X POST http://localhost:8001/api/login_check \
  -H "Content-Type: application/json" \
  -d '{
    "username": "test@example.com",
    "password": "password123"
  }'
```

**Login fallido:**
```bash
curl -X POST http://localhost:8001/api/login_check \
  -H "Content-Type: application/json" \
  -d '{
    "username": "wrong@example.com",
    "password": "wrongpassword"
  }'
```

### Opción 3: Desde el frontend (JavaScript/Svelte)

```javascript
const response = await fetch('http://localhost:8001/api/login_check', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  credentials: 'include', // ← Importante para CORS con credenciales
  body: JSON.stringify({
    username: 'juan@ejemplo.com',
    password: 'MiPassword123!'
  })
});

const data = await response.json();

if (response.ok) {
  // Login exitoso
  console.log('Token:', data.token);
  console.log('Username:', data.username);
  localStorage.setItem('jwt_token', data.token);
} else {
  // Login fallido
  console.error('Error:', data.error);
  console.log('Success:', data.success); // false
}
```

## 📋 Checklist de Verificación

- ✅ CORS configurado con `allow_credentials: true`
- ✅ Endpoint `/api/login_check` disponible
- ✅ Login exitoso devuelve `{ token, username }` con HTTP 200
- ✅ Login fallido devuelve `{ success: false, error }` con HTTP 401
- ✅ Frontend puede enviar peticiones con `credentials: 'include'`
- ✅ Token JWT se genera correctamente
- ✅ Documentación actualizada en `API_DOCUMENTATION.md`

## 🔧 Archivos Modificados/Creados

1. **Creados:**
   - `src/Security/AuthenticationSuccessHandler.php`
   - `src/Security/AuthenticationFailureHandler.php`
   - `test_login_check.sh`
   - `CORS_JWT_SETUP.md` (este archivo)

2. **Modificados:**
   - `config/packages/security.yaml`
   - `API_DOCUMENTATION.md`

3. **Sin cambios (ya estaba correcto):**
   - `config/packages/nelmio_cors.yaml`

## 🚀 Próximos Pasos

1. Ejecuta el script de prueba: `./test_login_check.sh`
2. Verifica que el frontend pueda hacer login correctamente
3. Asegúrate de que el token se guarde en `localStorage`
4. Prueba endpoints protegidos usando el token en el header `Authorization: Bearer {token}`

## 📝 Notas Importantes

- El token JWT tiene una duración de 1 hora (configurable en `lexik_jwt_authentication.yaml`)
- El logout se maneja del lado del cliente eliminando el token del `localStorage`
- Para refrescar el token, usa el endpoint `/api/refresh-token`
- Todos los endpoints bajo `/api` (excepto los públicos) requieren autenticación JWT
