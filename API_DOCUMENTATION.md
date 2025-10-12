# 📚 Documentación API para Frontend Svelte

## 🚀 Base URL
```
http://localhost:8001/api
```

## 🔧 Configuración CORS
La API está configurada para aceptar requests desde:
- `http://localhost:5173` (Vite dev server)
- `http://localhost:5174` (Vite dev server alternativo)

## 📋 Endpoints Disponibles

### 1. 📝 Registro de Cliente
**Endpoint:** `POST /api/register`

**Request Body:**
```json
{
  "nombre": "Juan Pérez",
  "email": "juan@ejemplo.com",
  "password": "MiPassword123!"
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Cliente registrado exitosamente",
  "cliente_id": 123
}
```

**Response Error (400):**
```json
{
  "success": false,
  "error": "El email ya está registrado"
}
```

**Ejemplo de uso en JavaScript:**
```javascript
const response = await fetch('http://localhost:8001/api/register', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    nombre: 'Juan Pérez',
    email: 'juan@ejemplo.com',
    password: 'MiPassword123!'
  })
});

const data = await response.json();
if (data.success) {
  console.log('Cliente registrado:', data.cliente_id);
} else {
  console.error('Error:', data.error);
}
```

---

### 2. 🔐 Login de Cliente
**Endpoint:** `POST /api/login_check`

**Request Body:**
```json
{
  "username": "juan@ejemplo.com",
  "password": "MiPassword123!"
}
```

**Response Success (200):**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "username": "Juan Pérez"
}
```

**Response Error (401):**
```json
{
  "success": false,
  "error": "Credenciales inválidas"
}
```

**Ejemplo de uso en JavaScript:**
```javascript
const response = await fetch('http://localhost:8001/api/login_check', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    username: 'juan@ejemplo.com',
    password: 'MiPassword123!'
  })
});

const data = await response.json();
if (data.token) {
  // Guardar token en localStorage o store
  localStorage.setItem('jwt_token', data.token);
  console.log('Login exitoso:', data.username);
} else {
  console.error('Error de login:', data.error);
}
```

---

### 3. 👤 Obtener Perfil de Cliente
**Endpoint:** `GET /api/profile`
**Headers:** `Authorization: Bearer {token}`

**Response Success (200):**
```json
{
  "id": 123,
  "username": "Juan Pérez",
  "email": "juan@ejemplo.com"
}
```

**Response Error (401):**
```json
{
  "success": false,
  "error": "No autenticado"
}
```

**Ejemplo de uso en JavaScript:**
```javascript
const token = localStorage.getItem('jwt_token');
const response = await fetch('http://localhost:8001/api/profile', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
});

const data = await response.json();
if (data.id) {
  console.log('Perfil del usuario:', data);
} else {
  console.error('Error:', data.error);
}
```

---

### 4. ✏️ Actualizar Perfil
**Endpoint:** `PUT /api/profile/update`
**Headers:** `Authorization: Bearer {token}`

**Request Body:**
```json
{
  "username": "Juan Carlos Pérez",
  "telefono": "123456789",
  "direccion": "Calle Principal 123",
  "dni": "12345678"
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Perfil actualizado exitosamente",
  "cliente": {
    "id": 123,
    "username": "Juan Carlos Pérez",
    "email": "juan@ejemplo.com",
    "telefono": "123456789",
    "direccion": "Calle Principal 123",
    "dni": "12345678"
  }
}
```

**Ejemplo de uso en JavaScript:**
```javascript
const token = localStorage.getItem('jwt_token');
const response = await fetch('http://localhost:8001/api/profile/update', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    username: 'Juan Carlos Pérez',
    telefono: '123456789'
  })
});

const data = await response.json();
if (data.success) {
  console.log('Perfil actualizado:', data.cliente);
} else {
  console.error('Error:', data.error);
}
```

---

### 5. 🚪 Logout
**Endpoint:** `POST /api/logout`
**Headers:** `Authorization: Bearer {token}`

**Response Success (200):**
```json
{
  "success": true,
  "message": "Logout exitoso"
}
```

**Ejemplo de uso en JavaScript:**
```javascript
const token = localStorage.getItem('jwt_token');
const response = await fetch('http://localhost:8001/api/logout', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
});

const data = await response.json();
if (data.success) {
  // Eliminar token del almacenamiento local
  localStorage.removeItem('jwt_token');
  console.log('Logout exitoso');
}
```

---

## 🛡️ Manejo de Autenticación en Frontend

### Ejemplo de Store de Autenticación (Svelte)
```javascript
// stores/auth.js
import { writable } from 'svelte/store';

export const user = writable(null);
export const token = writable(localStorage.getItem('jwt_token'));

export const auth = {
  async login(username, password) {
    const response = await fetch('http://localhost:8001/api/login_check', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });
    
    const data = await response.json();
    
    if (data.token) {
      localStorage.setItem('jwt_token', data.token);
      token.set(data.token);
      user.set({ username: data.username });
      return { success: true };
    } else {
      return { success: false, error: data.error };
    }
  },

  async register(nombre, email, password) {
    const response = await fetch('http://localhost:8001/api/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nombre, email, password })
    });
    
    const data = await response.json();
    return data;
  },

  async getProfile() {
    const currentToken = localStorage.getItem('jwt_token');
    if (!currentToken) return null;

    const response = await fetch('http://localhost:8001/api/profile', {
      headers: { 'Authorization': `Bearer ${currentToken}` }
    });
    
    const data = await response.json();
    if (data.id) {
      user.set(data);
      return data;
    }
    return null;
  },

  logout() {
    localStorage.removeItem('jwt_token');
    token.set(null);
    user.set(null);
  }
};
```

---

## 🔍 Códigos de Estado HTTP

| Código | Descripción |
|--------|-------------|
| 200    | Éxito |
| 400    | Error en la petición (datos inválidos) |
| 401    | No autenticado |
| 409    | Conflicto (email ya registrado) |
| 500    | Error interno del servidor |

---

## 🧪 Testing

Puedes probar todos los endpoints usando el script incluido:
```bash
./test_frontend_api.sh
```

---

## 📝 Notas Importantes

1. **Tokens JWT**: Los tokens tienen una duración de 1 hora
2. **CORS**: Configurado para localhost:5173 y localhost:5174
3. **Seguridad**: Las contraseñas se hashean usando Symfony's password hasher
4. **Base de datos**: Usa la tabla `clientes` en MariaDB
5. **Campos opcionales**: `apellido`, `telefono`, `direccion`, `dni` son opcionales

---

## 🚀 Estado del Servidor

- ✅ Backend ejecutándose en `http://localhost:8001`
- ✅ Base de datos MariaDB conectada (`pureinkafoods`)
- ✅ JWT configurado y funcionando
- ✅ CORS configurado para desarrollo
- ✅ Todos los endpoints probados y funcionando
