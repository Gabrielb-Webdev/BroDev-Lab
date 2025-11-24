# 🚀 Guía de Instalación - BroDev Lab

## 📋 Requisitos Previos

Antes de comenzar, asegúrate de tener instalado:

### En tu PC Local:
- ✅ **XAMPP** (o WAMP/MAMP) con:
  - PHP 7.4 o superior
  - MySQL 5.7 o MariaDB 10.3+
  - Apache Server
- ✅ **Git** (para clonar el repositorio)
- ✅ Navegador web moderno (Chrome, Firefox, Edge)

### En tu Servidor (Hostinger/Producción):
- ✅ PHP 7.4 o superior
- ✅ MySQL/MariaDB
- ✅ Acceso cPanel o SSH
- ✅ Certificado SSL (HTTPS recomendado)

---

## 🔧 Instalación Local (Desarrollo)

### Paso 1: Instalar XAMPP

1. Descarga XAMPP desde: https://www.apachefriends.org/
2. Instala XAMPP en `C:\xampp` (Windows) o `/Applications/XAMPP` (Mac)
3. Abre XAMPP Control Panel
4. Inicia los servicios:
   - ✅ Apache
   - ✅ MySQL

### Paso 2: Clonar el Proyecto

```bash
# Navega a la carpeta htdocs de XAMPP
cd C:\xampp\htdocs

# Clona el repositorio
git clone https://github.com/Gabrielb-Webdev/BroDev-Lab.git

# Entra al directorio
cd BroDev-Lab
```

### Paso 3: Configurar Base de Datos

#### Opción A: Instalación Automática (Recomendado)

```bash
# Ejecuta el script de instalación
php install.php
```

El script te preguntará:
- Contraseña de MySQL (por defecto vacía en XAMPP)
- Si deseas actualizar config.php automáticamente

#### Opción B: Instalación Manual

1. **Abre phpMyAdmin**: http://localhost/phpmyadmin
2. **Crea la base de datos**:
   - Click en "Nueva" (New)
   - Nombre: `brodevlab_portal`
   - Cotejamiento: `utf8mb4_unicode_ci`
   - Click "Crear"

3. **Importa el SQL**:
   - Selecciona la base de datos `brodevlab_portal`
   - Click en la pestaña "Importar"
   - Click "Seleccionar archivo"
   - Elige `database.sql` del proyecto
   - Click "Continuar"

### Paso 4: Configurar Credenciales

1. **Copia el archivo de configuración**:
```bash
# En Windows
copy config\config.example.php config\config.php

# En Linux/Mac
cp config/config.example.php config/config.php
```

2. **Edita `config/config.php`**:
```php
// Para XAMPP local, usa estos valores:
define('DB_HOST', 'localhost');
define('DB_NAME', 'brodevlab_portal');
define('DB_USER', 'root');
define('DB_PASS', '');  // Vacío en XAMPP por defecto

define('SITE_URL', 'http://localhost/BroDev-Lab');
```

### Paso 5: Probar la Instalación

1. **Abre el navegador** y ve a:
   - Panel Admin: `http://localhost/BroDev-Lab/admin/login.html`
   - Portal Cliente: `http://localhost/BroDev-Lab/portal/`

2. **Login Admin**:
   - Usuario: `admin`
   - Contraseña: `Admin123!`

3. **Si todo funciona** ✅:
   - Deberías ver el panel de administración
   - Puedes crear clientes y proyectos
   - El sistema está listo para usar

---

## 🌐 Instalación en Servidor (Hostinger/Producción)

### Opción 1: Usando Git (Recomendado)

Si tu Hostinger tiene Git habilitado:

1. **Conecta vía SSH** o usa el terminal de Hostinger
2. **Navega a public_html**:
```bash
cd public_html
```

3. **Clona el repositorio**:
```bash
git clone https://github.com/Gabrielb-Webdev/BroDev-Lab.git .
```

4. **Configura la base de datos** (ver Paso 2 abajo)

### Opción 2: Subida Manual (FTP)

1. **Descarga el proyecto** desde GitHub (Download ZIP)
2. **Extrae el ZIP** en tu computadora
3. **Conéctate por FTP** a tu hosting:
   - Usa FileZilla, WinSCP, o el File Manager de Hostinger
   - Sube todos los archivos a `public_html`

### Paso 1: Crear Base de Datos en Hostinger

1. **Accede a cPanel** de Hostinger
2. **Ve a "MySQL Databases"** o "Bases de datos MySQL"
3. **Crea nueva base de datos**:
   - Nombre: `tu_usuario_brodevlab` (Hostinger agrega prefijo automático)
   - Crea un usuario con contraseña segura
   - Asigna todos los privilegios al usuario
   - **Guarda estos datos** (los necesitarás)

### Paso 2: Importar SQL

1. **Accede a phpMyAdmin** desde cPanel
2. **Selecciona tu base de datos**
3. **Importa** el archivo `database.sql`
4. **Verifica** que las tablas se crearon correctamente

### Paso 3: Configurar config.php

1. **Edita** `config/config.php` con los datos de Hostinger:

```php
// Datos que te dio Hostinger
define('DB_HOST', 'localhost'); // o el host específico
define('DB_NAME', 'tu_usuario_brodevlab');
define('DB_USER', 'tu_usuario_db');
define('DB_PASS', 'tu_password_segura');

// URL de tu sitio
define('SITE_URL', 'https://tudominio.com');

// IMPORTANTE: Cambia a false en producción
define('DEBUG_MODE', false);
```

### Paso 4: Configurar Webhooks (Opcional)

Para que los cambios de GitHub se actualicen automáticamente:

1. **En Hostinger**, ve a GIT en el menú
2. **Busca tu repositorio** BroDev-Lab
3. **Copia la URL del webhook**
4. **En GitHub**, ve a Settings → Webhooks
5. **Pega la URL** y guarda

### Paso 5: Seguridad en Producción

1. **Cambia la contraseña del admin**:
```sql
-- Conéctate a phpMyAdmin y ejecuta:
UPDATE admin_users 
SET password_hash = '$2y$10$TU_NUEVO_HASH_AQUI' 
WHERE username = 'admin';
```

Para generar el hash, usa este PHP:
```php
<?php
echo password_hash('TuNuevaPasswordSegura123!', PASSWORD_DEFAULT);
?>
```

2. **Configura HTTPS**:
   - En Hostinger, activa el certificado SSL gratuito
   - Fuerza HTTPS en `.htaccess`

3. **Protege archivos sensibles**:
```apache
# Agrega a .htaccess en la raíz
<FilesMatch "^(config\.php|database\.sql|install\.php)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## 🧪 Verificar la Instalación

### Checklist de Verificación

- [ ] **Base de datos creada** y tablas importadas
- [ ] **config.php configurado** con credenciales correctas
- [ ] **Usuario admin** puede hacer login
- [ ] **Crear un cliente de prueba** funciona
- [ ] **Crear un proyecto** funciona
- [ ] **Portal de cliente** accesible con access code
- [ ] **Time tracking** funciona correctamente
- [ ] **No hay errores** en consola del navegador

### Comandos de Verificación

```bash
# Verificar conexión a la base de datos
php -r "
require 'config/config.php';
try {
    \$db = getDBConnection();
    echo 'Conexión exitosa\n';
} catch (Exception \$e) {
    echo 'Error: ' . \$e->getMessage() . '\n';
}
"

# Ver tablas creadas
mysql -u root -p brodevlab_portal -e "SHOW TABLES;"

# Contar usuarios admin
mysql -u root -p brodevlab_portal -e "SELECT COUNT(*) FROM admin_users;"
```

---

## 🐛 Solución de Problemas

### Error: "Connection failed"

**Causa**: Credenciales de MySQL incorrectas

**Solución**:
1. Verifica `config/config.php`
2. Prueba la conexión manualmente en phpMyAdmin
3. Asegúrate que MySQL esté corriendo

```bash
# En XAMPP, verificar si MySQL está corriendo
netstat -an | find "3306"
```

### Error: "Table doesn't exist"

**Causa**: SQL no se importó correctamente

**Solución**:
1. Vuelve a importar `database.sql`
2. Verifica en phpMyAdmin que las tablas existan
3. Ejecuta `php install.php` para reinstalar

### Error: "Access denied"

**Causa**: Usuario MySQL sin permisos

**Solución**:
```sql
-- Otorgar permisos completos
GRANT ALL PRIVILEGES ON brodevlab_portal.* TO 'tu_usuario'@'localhost';
FLUSH PRIVILEGES;
```

### Error: "Headers already sent"

**Causa**: Archivo PHP tiene BOM o espacios antes de `<?php`

**Solución**:
1. Abre el archivo en un editor (VS Code)
2. Guarda con codificación "UTF-8 sin BOM"
3. Elimina espacios/saltos antes de `<?php`

### Admin login no funciona

**Pasos**:
1. Verifica que la tabla `admin_users` tenga el usuario admin
2. Regenera el password:
```sql
UPDATE admin_users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE username = 'admin';
```
3. Prueba con: `admin` / `Admin123!`

---

## 📊 Estructura de la Base de Datos

```
brodevlab_portal/
├── admin_users          # Usuarios administradores
├── clients              # Clientes
├── projects             # Proyectos
├── project_phases       # Fases de proyectos
├── time_sessions        # Sesiones de tiempo
├── project_activities   # Actividades/logs
├── messages             # Mensajes
├── notifications        # Notificaciones
└── user_sessions        # Sesiones activas
```

---

## 🔐 Crear Usuarios Adicionales

### Crear Admin desde MySQL

```sql
INSERT INTO admin_users (username, email, password_hash, full_name, role, status)
VALUES (
    'juan.perez',
    'juan@brodevlab.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Juan Pérez',
    'admin',
    'active'
);
```

### Generar Hash de Password

Crea un archivo `hash-password.php`:

```php
<?php
$password = $_GET['pass'] ?? 'Admin123!';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: $password\n";
echo "Hash: $hash\n";
?>
```

Úsalo: `php hash-password.php pass=MiPassword123`

---

## 📞 Soporte

Si tienes problemas:

1. **Revisa los logs**:
   - XAMPP: `C:\xampp\apache\logs\error.log`
   - Consola del navegador (F12)
   - Logs del servidor (cPanel)

2. **Verifica la documentación**:
   - `README.md` - Info general
   - `README-PORTAL.md` - Portal de clientes
   - `README-AUTH.md` - Sistema de autenticación

3. **Contacto**:
   - Email: admin@brodevlab.com
   - GitHub Issues: [Crear issue](https://github.com/Gabrielb-Webdev/BroDev-Lab/issues)

---

## ✅ ¡Listo!

Tu sistema BroDev Lab está instalado y listo para usar.

**Próximos pasos**:
1. Cambia la contraseña del admin
2. Crea tus primeros clientes
3. Configura el email SMTP (opcional)
4. Personaliza los estilos si lo deseas

**URLs importantes**:
- Admin: `https://tudominio.com/admin/login.html`
- Portal Cliente: `https://tudominio.com/portal/`
- API Docs: Ver `README-PORTAL.md`

🎉 **¡Bienvenido a BroDev Lab!**
