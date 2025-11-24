# 🔐 Sistema de Autenticación - BroDev Lab

## ✨ Características Implementadas

### 🎯 Sistema de Roles
- **Super Admin**: Acceso completo + gestión de usuarios admin
- **Admin**: Gestión de proyectos, clientes y time tracking
- **Editor**: Ver y editar proyectos (sin eliminar)
- **Cliente**: Solo ver sus propios proyectos

### 🔒 Seguridad
- ✅ Contraseñas hasheadas con bcrypt
- ✅ Sesiones seguras con tokens únicos
- ✅ Validación de permisos en todas las APIs
- ✅ Protección contra acceso no autorizado
- ✅ Sesiones con expiración automática
- ✅ Los clientes solo ven sus propios proyectos

---

## 📥 Instalación y Configuración

### 1. Actualizar Base de Datos

Ejecuta el script SQL actualizado:

```bash
mysql -u root -p brodevlab_portal < database.sql
```

Esto creará:
- Tabla `admin_users` (usuarios administradores)
- Tabla `user_sessions` (sesiones activas)
- Usuario admin por defecto

### 2. Credenciales por Defecto

**Usuario Admin:**
- **Usuario**: `admin`
- **Email**: `admin@brodevlab.com`
- **Contraseña**: `Admin123!`

⚠️ **IMPORTANTE**: Cambia esta contraseña después del primer login en producción.

### 3. Acceso al Sistema

#### Para Administradores:
```
URL: https://tudominio.com/admin/login.html
```

Inicia sesión con las credenciales de admin. Una vez autenticado, accederás al panel completo.

#### Para Clientes:
```
URL: https://tudominio.com/portal/
```

Los clientes inician sesión con su código de acceso (generado automáticamente al crear el cliente).

---

## 👥 Gestión de Usuarios Admin

### Crear Nuevos Administradores

Solo un **Super Admin** puede crear nuevos usuarios admin. Tienes dos opciones:

#### Opción A: Desde la Base de Datos (MySQL)

```sql
-- Crear nuevo admin
INSERT INTO admin_users (username, email, password_hash, full_name, role, status)
VALUES (
    'juan.perez',
    'juan@brodevlab.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Usar password_hash() de PHP
    'Juan Pérez',
    'admin', -- Opciones: 'super_admin', 'admin', 'editor'
    'active'
);
```

#### Opción B: API (Desde el Panel Admin)

```javascript
// Llamada desde el navegador (solo super_admin)
fetch('../api/auth.php?action=register-admin', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        username: 'juan.perez',
        email: 'juan@brodevlab.com',
        password: 'MiPassword123!',
        full_name: 'Juan Pérez',
        role: 'admin'
    })
})
.then(res => res.json())
.then(data => console.log(data));
```

### Generar Hash de Contraseña

Para generar un hash manualmente en PHP:

```php
<?php
$password = 'TuPasswordAqui';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash;
?>
```

---

## 🔐 Flujo de Autenticación

### Para Administradores

1. **Login**: `admin/login.html`
   - Ingresa usuario/email y contraseña
   - Se valida contra `admin_users`
   - Se crea sesión en `user_sessions`
   - Se redirige a `admin/index.html`

2. **Verificación**: 
   - Cada página verifica la sesión al cargar
   - Si no hay sesión válida, redirige al login
   - Las APIs validan permisos antes de responder

3. **Logout**:
   - Elimina la sesión de la base de datos
   - Limpia la sesión PHP
   - Redirige al login

### Para Clientes

1. **Login**: `portal/index.html`
   - Ingresa código de acceso
   - Se valida contra `clients`
   - Se crea sesión en `user_sessions`
   - Se carga el dashboard con su proyecto

2. **Restricciones**:
   - Solo pueden ver sus propios proyectos
   - No pueden ver listas de otros clientes
   - No pueden modificar información

---

## 🛡️ Protección de APIs

Todas las APIs están protegidas con middleware de autenticación:

### `projects.php`
- ✅ **GET todos**: Requiere admin
- ✅ **GET por ID**: Requiere autenticación + verificación de acceso
- ✅ **GET por access_code**: Público (para clientes)
- ✅ **POST/PUT/DELETE**: Solo admins

### `clients.php`
- ✅ **GET todos**: Solo admins
- ✅ **GET por ID**: Autenticación + verificación de acceso
- ✅ **POST/PUT/DELETE**: Solo admins

### `time-tracking.php`
- ✅ **GET**: Autenticación requerida
- ✅ **POST (start/stop)**: Solo admins

### `auth.php`
- ✅ **POST login**: Público
- ✅ **POST logout**: Autenticación requerida
- ✅ **GET verify**: Autenticación requerida
- ✅ **POST register-admin**: Solo super admins

---

## 🔧 Configuración Avanzada

### Tiempo de Expiración de Sesiones

Edita en `config/config.php`:

```php
define('SESSION_LIFETIME', 3600);      // Clientes: 1 hora
define('ADMIN_SESSION_LIFETIME', 7200); // Admins: 2 horas
```

### Limpiar Sesiones Expiradas

Ejecuta periódicamente (ej: cron job):

```php
<?php
require_once 'config/config.php';
require_once 'config/auth-middleware.php';
$db = getDBConnection();
cleanExpiredSessions($db);
?>
```

---

## 🧪 Probar el Sistema

### 1. Probar Login de Admin

```bash
# Login
curl -X POST http://tudominio.com/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "Admin123!",
    "user_type": "admin"
  }'
```

### 2. Probar Login de Cliente

```bash
# Login con access code
curl -X POST http://tudominio.com/api/auth.php?action=login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "ABC123XYZ",
    "user_type": "client"
  }'
```

### 3. Verificar Sesión

```bash
curl http://tudominio.com/api/auth.php?action=verify \
  --cookie "PHPSESSID=tu_session_id"
```

---

## 🚨 Seguridad en Producción

### ✅ Checklist de Seguridad

- [ ] Cambiar contraseña del admin por defecto
- [ ] Configurar HTTPS (certificado SSL)
- [ ] Cambiar `DEBUG_MODE` a `false` en `config.php`
- [ ] Configurar `session.cookie_secure = 1` en php.ini
- [ ] Configurar `session.cookie_httponly = 1` en php.ini
- [ ] Agregar rate limiting en el login
- [ ] Configurar backup automático de la base de datos
- [ ] Revisar permisos de archivos (no 777)
- [ ] Configurar logs de acceso
- [ ] Implementar 2FA para super admins (opcional)

### Configuración Recomendada de PHP

En `php.ini`:

```ini
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = Strict
session.use_only_cookies = 1
session.use_strict_mode = 1
```

---

## 📊 Monitoreo

### Ver Sesiones Activas

```sql
SELECT 
    s.id,
    s.user_type,
    CASE 
        WHEN s.user_type = 'admin' THEN a.username
        WHEN s.user_type = 'client' THEN c.name
    END as user_name,
    s.ip_address,
    s.created_at,
    s.expires_at
FROM user_sessions s
LEFT JOIN admin_users a ON s.user_id = a.id AND s.user_type = 'admin'
LEFT JOIN clients c ON s.user_id = c.id AND s.user_type = 'client'
WHERE s.expires_at > NOW()
ORDER BY s.created_at DESC;
```

### Ver Actividad de Usuarios Admin

```sql
SELECT 
    u.username,
    u.full_name,
    u.role,
    u.last_login,
    u.status
FROM admin_users u
ORDER BY u.last_login DESC;
```

---

## 🐛 Troubleshooting

### Error: "No autenticado"
- Verifica que las cookies estén habilitadas
- Revisa que `session_start()` se ejecute correctamente
- Verifica que la sesión no haya expirado

### Error: "Credenciales inválidas"
- Verifica el usuario y contraseña
- Revisa que el usuario esté activo (`status = 'active'`)
- Verifica el hash de la contraseña en la BD

### No se puede crear sesión
- Verifica permisos de escritura en `/tmp` o directorio de sesiones
- Revisa los logs de PHP
- Verifica conexión a la base de datos

---

## 📚 Documentación API

Ver `README-PORTAL.md` para documentación completa de los endpoints.

---

## 🆘 Soporte

Para problemas o dudas:
- Email: admin@brodevlab.com
- Revisa los logs en `error_log`
- Verifica la consola del navegador para errores JS

---

**¡Sistema de autenticación listo para producción!** 🎉
