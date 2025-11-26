# 🔐 Sistema de Autenticación Restaurado

## ✅ El panel admin ahora requiere autenticación

El acceso al panel de administración está protegido y solo usuarios con roles `admin` o `super_admin` pueden acceder.

## 🔑 Credenciales de Acceso

### Usuario 1: Gabriel Bustos
```
URL: https://grey-squirrel-133805.hostingersite.com/admin/login.php
Username: gabriel
Email: gabriel@brodevlab.com
Password: Gabriel2024!
Role: super_admin
```

### Usuario 2: Lautaro Magliano
```
URL: https://grey-squirrel-133805.hostingersite.com/admin/login.php
Username: lautaro
Email: lautaro@brodevlab.com
Password: Lautaro2024!
Role: super_admin
```

## 🛡️ Sistema de Seguridad

### Verificaciones Implementadas:

1. **Autenticación de Sesión**
   - Verificación de sesión PHP válida
   - Verificación de token de sesión en base de datos
   - Expiración automática después de 24 horas

2. **Control de Roles**
   - Solo usuarios con `user_type = 'admin'` pueden acceder
   - Solo usuarios con `role IN ('admin', 'super_admin')` son aceptados
   - Verificación en cada petición a las APIs

3. **Protección de APIs**
   - `/api/projects.php` - Requiere rol admin
   - `/api/clients.php` - Requiere rol admin
   - `/api/time-tracking.php` - Requiere rol admin

## 🌐 URLs del Sistema

```
Login Admin:
https://grey-squirrel-133805.hostingersite.com/admin/login.php

Panel Admin (Protegido):
https://grey-squirrel-133805.hostingersite.com/admin/index.php

Portal Cliente (Sin protección):
https://grey-squirrel-133805.hostingersite.com/portal/
```

## 🔄 Flujo de Autenticación

1. Usuario accede a `/admin/login.php`
2. Ingresa credenciales (username/email + password)
3. Sistema verifica en tabla `admin_users`:
   - Usuario existe y está activo
   - Contraseña es correcta
   - Rol es `admin` o `super_admin`
4. Se crea sesión en tabla `user_sessions`
5. Se establecen cookies de sesión (24 horas)
6. Usuario es redirigido a `/admin/index.php`
7. Cada petición verifica la sesión activa

## 📋 Tabla de Roles

| Role | Permisos |
|------|----------|
| `super_admin` | Acceso completo al sistema |
| `admin` | Acceso completo al sistema |
| `editor` | (No implementado aún) |

## 🔧 Funcionalidades Protegidas

### Panel Admin
- ✅ Dashboard con estadísticas
- ✅ Gestión de proyectos (CRUD completo)
- ✅ Gestión de clientes (CRUD completo)
- ✅ Time tracking (iniciar/detener sesiones)
- ✅ Todas las operaciones requieren autenticación

### APIs Protegidas
```php
// Todas requieren autenticación válida

GET    /api/projects.php      - Listar proyectos (admin)
POST   /api/projects.php      - Crear proyecto (admin)
PUT    /api/projects.php      - Actualizar proyecto (admin)
DELETE /api/projects.php?id=1 - Eliminar proyecto (admin)

GET    /api/clients.php       - Listar clientes (admin)
POST   /api/clients.php       - Crear cliente (admin)
PUT    /api/clients.php       - Actualizar cliente (admin)
DELETE /api/clients.php?id=1  - Eliminar cliente (admin)

GET    /api/time-tracking.php          - Listar sesiones (auth)
POST   /api/time-tracking.php?action=start - Iniciar sesión (admin)
POST   /api/time-tracking.php?action=stop  - Detener sesión (admin)
```

## 🔒 Configuración de Sesiones

```php
// Configuración en auth.php y auth-middleware.php
session_set_cookie_params([
    'lifetime' => 86400,    // 24 horas
    'path' => '/',
    'domain' => '',
    'secure' => $isSecure,  // Auto-detecta HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);
```

## 🗄️ Base de Datos

### Tabla: admin_users
Contiene los usuarios administradores:
- `id` - ID único
- `username` - Nombre de usuario
- `email` - Email único
- `password_hash` - Hash bcrypt de la contraseña
- `full_name` - Nombre completo
- `role` - Rol (super_admin, admin, editor)
- `status` - Estado (active, inactive)

### Tabla: user_sessions
Contiene las sesiones activas:
- `id` - ID único
- `user_id` - ID del usuario
- `user_type` - Tipo (admin, client)
- `session_token` - Token único de sesión
- `expires_at` - Fecha de expiración
- `ip_address` - IP del usuario
- `user_agent` - Navegador del usuario

## 🛠️ Herramientas de Debug

Para verificar el estado de tu sesión:
```
https://grey-squirrel-133805.hostingersite.com/session-debug.php
```

Este archivo muestra:
- Estado de la sesión PHP
- Variables de sesión activas
- Cookies del navegador
- Sesión en base de datos
- Configuración del servidor

## ⚠️ Seguridad

### Implementado:
- ✅ Contraseñas hasheadas con bcrypt
- ✅ Cookies HttpOnly (no accesibles por JavaScript)
- ✅ Cookies Secure en HTTPS
- ✅ SameSite=Lax (protección CSRF)
- ✅ Tokens de sesión únicos y aleatorios
- ✅ Expiración automática de sesiones
- ✅ Verificación de IP y User Agent

### Recomendaciones:
- 🔸 Cambiar contraseñas después del primer login
- 🔸 Usar gestor de contraseñas
- 🔸 No compartir credenciales
- 🔸 Cerrar sesión al terminar
- 🔸 Acceder solo desde redes seguras

## 📞 Soporte

Para cualquier problema de acceso:
- Verificar credenciales correctas
- Limpiar cookies del navegador
- Usar herramienta de debug (session-debug.php)
- Verificar que el usuario esté en la base de datos

---

**Fecha de actualización**: 26 de Noviembre, 2025
**Versión**: 3.0 (Con Autenticación Completa)
**Estado**: ✅ Sistema Seguro y Funcional
