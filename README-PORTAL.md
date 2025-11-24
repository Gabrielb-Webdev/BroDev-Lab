# 🚀 Portal de Cliente - BroDev Lab

Sistema completo de gestión de proyectos con portal de cliente y time tracking.

## 📋 Tabla de Contenidos
1. [Instalación](#instalación)
2. [Configuración](#configuración)
3. [Estructura de Archivos](#estructura)
4. [Funcionalidades](#funcionalidades)
5. [API Endpoints](#api-endpoints)
6. [Uso](#uso)

---

## 🔧 Instalación

### Requisitos
- **PHP 7.4+** con PDO habilitado
- **MySQL 5.7+** o **MariaDB 10.3+**
- **Servidor Web** (Apache/Nginx) o usar PHP built-in server
- **Extensiones PHP necesarias**: pdo_mysql, json, mbstring

### Pasos de Instalación

1. **Crear la Base de Datos**
```bash
mysql -u root -p
```
```sql
CREATE DATABASE brodevlab_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

2. **Importar el esquema**
```bash
mysql -u root -p brodevlab_portal < database.sql
```

3. **Configurar credenciales**

Editar `config/config.php` y cambiar:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'brodevlab_portal');
define('DB_USER', 'root');
define('DB_PASS', 'tu_password');
```

4. **Configurar Email (opcional)**

Para notificaciones por email, editar en `config/config.php`:
```php
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_USER', 'apikey');
define('SMTP_PASS', 'tu_api_key_sendgrid');
```

5. **Iniciar servidor local (para desarrollo)**
```bash
cd "f:\Users\gabri\Documentos\Gabriel Dev\Gabriel Page"
php -S localhost:8000
```

6. **Acceder a los paneles**
- Portal Cliente: `http://localhost:8000/portal/`
- Panel Admin: `http://localhost:8000/admin/`
- Panel Admin (usuario por defecto):
  - Usuario: `admin`
  - Password: `admin123` (⚠️ CAMBIAR INMEDIATAMENTE)

---

## ⚙️ Configuración

### Cambiar Password del Administrador

```sql
UPDATE admins 
SET password_hash = '$2y$10$NUEVO_HASH' 
WHERE username = 'admin';
```

Para generar un nuevo hash:
```php
<?php
echo password_hash('tu_nuevo_password', PASSWORD_DEFAULT);
?>
```

### Configurar Zona Horaria

Editar en `config/config.php`:
```php
date_default_timezone_set('America/Mexico_City'); // Tu zona horaria
```

---

## 📁 Estructura de Archivos

```
Gabriel Page/
├── index.html                 # Página principal del sitio
├── pages/                     # Páginas del sitio público
│   ├── services.html
│   ├── portfolio.html
│   ├── about.html
│   ├── contact.html
│   ├── privacy-policy.html
│   └── terms-of-service.html
├── portal/                    # Portal del Cliente
│   ├── index.html
│   ├── portal-styles.css
│   └── portal-script.js
├── admin/                     # Panel de Administración
│   ├── index.html
│   ├── admin-styles.css
│   └── admin-script.js
├── api/                       # Backend API REST
│   ├── projects.php          # CRUD de proyectos
│   ├── clients.php           # CRUD de clientes
│   └── time-tracking.php     # Sistema de time tracking
├── config/
│   └── config.php            # Configuración global
├── database.sql              # Esquema de base de datos
├── styles.css                # Estilos globales
└── script.js                 # Scripts globales
```

---

## 🎯 Funcionalidades

### Portal del Cliente
✅ Login con código de acceso único
✅ Ver estado del proyecto en tiempo real
✅ Timeline visual de fases
✅ Ver tiempo invertido por fase
✅ Timer en vivo cuando se trabaja en su proyecto
✅ Historial de actividades
✅ Cálculo automático de costos
✅ Diseño responsive
✅ Actualización automática cada 30 segundos

### Panel de Administración
✅ Dashboard con estadísticas
✅ Gestión de proyectos (CRUD)
✅ Gestión de clientes (CRUD)
✅ Sistema de time tracking con timer
✅ Iniciar/pausar/detener sesiones de trabajo
✅ Ver sesiones activas
✅ Historial completo de tiempo
✅ Generación automática de códigos de acceso
✅ Creación automática de fases predeterminadas

### Sistema de Time Tracking
✅ Timer en tiempo real
✅ Seguimiento por fase de proyecto
✅ Cálculo automático de duración
✅ Actualización automática del tiempo total
✅ Actualización del progreso del proyecto
✅ Notificaciones automáticas al cliente
✅ Historial de todas las sesiones

---

## 🔌 API Endpoints

### Proyectos (`api/projects.php`)

**GET** - Obtener proyectos
```
GET /api/projects.php              # Todos los proyectos
GET /api/projects.php?id=1         # Proyecto específico
GET /api/projects.php?access_code=ABC123  # Por código de acceso
GET /api/projects.php?client_id=1  # Proyectos de un cliente
```

**POST** - Crear proyecto
```json
{
  "client_id": 1,
  "project_name": "Website E-commerce",
  "project_type": "ecommerce",
  "description": "Tienda online completa",
  "budget": 2000,
  "hourly_rate": 50,
  "start_date": "2025-01-01",
  "estimated_end_date": "2025-02-15"
}
```

**PUT** - Actualizar proyecto
```json
{
  "id": 1,
  "status": "in_progress",
  "progress_percentage": 45.5
}
```

### Clientes (`api/clients.php`)

**GET** - Obtener clientes
```
GET /api/clients.php                    # Todos los clientes
GET /api/clients.php?id=1               # Cliente específico
GET /api/clients.php?access_code=ABC123 # Por código de acceso
```

**POST** - Crear cliente
```json
{
  "name": "Juan Pérez",
  "email": "juan@email.com",
  "phone": "+52 555 1234",
  "company": "Tech Solutions"
}
```

### Time Tracking (`api/time-tracking.php`)

**GET** - Obtener sesiones
```
GET /api/time-tracking.php?project_id=1  # Sesiones de un proyecto
GET /api/time-tracking.php?phase_id=1    # Sesiones de una fase
GET /api/time-tracking.php?active=1      # Sesiones activas
```

**POST** - Iniciar sesión
```json
POST /api/time-tracking.php?action=start
{
  "project_id": 1,
  "phase_id": 2,
  "description": "Desarrollo del header"
}
```

**POST** - Detener sesión
```json
POST /api/time-tracking.php?action=stop
{
  "session_id": 5,
  "notes": "Completado el header responsive",
  "complete_phase": false
}
```

---

## 📖 Uso

### Flujo Completo: Crear Proyecto para Cliente

#### 1. Crear Cliente
1. Ir al Panel Admin (`/admin/`)
2. Clic en "Clientes" en el menú
3. Clic en "+ Nuevo Cliente"
4. Completar formulario:
   - Nombre: Juan Pérez
   - Email: juan@email.com
   - Teléfono: +52 555 1234
   - Empresa: TechStore
5. Guardar → Se genera código de acceso automáticamente (ej: `F4A8B2C1D5E6`)

#### 2. Crear Proyecto
1. Ir a "Proyectos"
2. Clic en "+ Nuevo Proyecto"
3. Completar:
   - Cliente: Juan Pérez
   - Nombre: Website E-commerce TechStore
   - Tipo: E-commerce
   - Presupuesto: $2,000 USD
   - Tarifa/hora: $50 USD
   - Fecha inicio: 2025-01-15
   - Fecha entrega: 2025-02-28
4. Guardar → Se crean automáticamente 6 fases:
   - Análisis y Planeación (4h estimadas)
   - Diseño UI/UX (10h estimadas)
   - Desarrollo Frontend (16h estimadas)
   - Desarrollo Backend (10h estimadas)
   - Testing y QA (6h estimadas)
   - Deployment y Entrega (3h estimadas)

#### 3. Enviar Acceso al Cliente
Enviar email al cliente con:
```
Hola Juan,

Tu proyecto "Website E-commerce TechStore" ha sido registrado.

Puedes ver el progreso en tiempo real aquí:
http://tudominio.com/portal/

Tu código de acceso: F4A8B2C1D5E6

¡Gracias por confiar en BroDev Lab!
```

#### 4. Iniciar Trabajo (Time Tracking)
1. Ir a "Time Tracking"
2. Seleccionar proyecto: "Website E-commerce TechStore"
3. Seleccionar fase: "Fase 1: Análisis y Planeación"
4. Descripción: "Reunión con cliente + documentación"
5. Clic en "▶️ Iniciar Sesión"
6. El timer comienza a correr
7. **Automáticamente**:
   - Se actualiza estado de la fase a "En Progreso"
   - Se notifica al cliente por email
   - El cliente ve el timer en vivo en su portal

#### 5. Cliente Verifica Progreso
1. Cliente accede a `http://tudominio.com/portal/`
2. Ingresa código: `F4A8B2C1D5E6`
3. Ve:
   - Progreso general: 0%
   - Fase 1 con timer en vivo: "2h 35m 12s"
   - Estado: "🔄 Trabajando ahora..."
   - Última actividad: "Reunión con cliente + documentación"

#### 6. Detener Sesión y Completar Fase
1. Cuando termines, ir a "Time Tracking"
2. Clic en "⏹️ Detener"
3. Agregar notas finales (opcional)
4. Marcar "Completar fase" si terminaste
5. **Automáticamente**:
   - Se calcula tiempo total: 4h 35m
   - Se actualiza costo: $227.50 USD (4.58h × $50)
   - Se marca fase como completada
   - Se calcula progreso: 16.7% (1/6 fases)
   - Se notifica al cliente
   - Se habilita siguiente fase

#### 7. Continuar con Siguientes Fases
Repetir pasos 4-6 para cada fase del proyecto.

---

## 🎨 Personalización

### Cambiar Fases Predeterminadas

Editar en `api/projects.php`, función `createDefaultPhases()`:
```php
$defaultPhases = [
    ['name' => 'Tu Fase 1', 'estimated_hours' => 5],
    ['name' => 'Tu Fase 2', 'estimated_hours' => 8],
    // ...
];
```

### Cambiar Tarifa por Hora por Defecto

Editar en `config/config.php`:
```php
define('DEFAULT_HOURLY_RATE', 50.00);
```

### Personalizar Colores

Editar en `styles.css` o `portal-styles.css`:
```css
:root {
    --primary: #7C3AED;        /* Tu color primario */
    --secondary: #EC4899;      /* Tu color secundario */
}
```

---

## 🔒 Seguridad

### Recomendaciones para Producción

1. **Cambiar password del admin**
2. **Deshabilitar DEBUG_MODE** en `config/config.php`:
   ```php
   define('DEBUG_MODE', false);
   ```
3. **Usar HTTPS** (certificado SSL)
4. **Configurar .htaccess** para proteger archivos sensibles:
   ```apache
   <Files "config.php">
     Order allow,deny
     Deny from all
   </Files>
   ```
5. **Implementar rate limiting** en API
6. **Validar y sanitizar** todos los inputs
7. **Usar prepared statements** (ya implementado)
8. **Hacer backups regulares** de la base de datos

---

## 🐛 Troubleshooting

### Error: "Connection failed"
- Verificar credenciales de MySQL en `config/config.php`
- Verificar que MySQL esté corriendo
- Verificar que la base de datos exista

### Error: "Access Code Invalid"
- Verificar que el cliente exista en la base de datos
- Verificar que el código de acceso sea correcto (case-sensitive)
- Verificar que el proyecto esté asignado a ese cliente

### Timer no actualiza en tiempo real
- Verificar que JavaScript esté habilitado
- Verificar conexión a internet
- Abrir consola del navegador para ver errores
- Verificar que la API esté respondiendo

### No llegan notificaciones por email
- Verificar configuración SMTP en `config/config.php`
- Verificar que el servidor permita envío de emails
- Verificar logs del servidor
- Probar con servicio externo (SendGrid, Mailgun)

---

## 📞 Soporte

Para soporte o consultas:
- Email: admin@brodevlab.com
- Website: https://brodevlab.com

---

## 📝 Licencia

Este sistema es propiedad de BroDev Lab.
Todos los derechos reservados © 2024-2025
