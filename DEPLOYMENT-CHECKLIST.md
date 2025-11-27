# 🚀 Despliegue en Hostinger - Checklist

## 📦 Archivos a Subir

### Raíz del Proyecto (public_html)
- [ ] `database-tasks.sql`
- [ ] `install-tasks.html`
- [ ] `install-tasks.php`
- [ ] `HOSTINGER-INSTALL.html` (esta guía)

### Carpeta `api/`
- [ ] `tasks.php` (NUEVO)
- [ ] `projects.php` (existente)
- [ ] `clients.php` (existente)
- [ ] `custom-fields.php` (existente)
- [ ] `auth.php` (existente)

### Carpeta `admin/`
- [ ] `board-view.html` (NUEVO)
- [ ] `board-view.js` (NUEVO - actualizado para Hostinger)
- [ ] `board-styles.css` (NUEVO)
- [ ] `index.php` (existente - actualizado)
- [ ] `admin-styles.css` (existente - actualizado)

### Carpeta `config/`
- [ ] `config.php` (verificar credenciales)
- [ ] `auth-middleware.php` (existente)

---

## 🔧 Pasos de Instalación

### 1. Verificar Configuración

**Archivo: `config/config.php`**
```php
// Asegúrate de tener estas credenciales correctas
define('DB_HOST', 'localhost');
define('DB_NAME', 'u569129255_brodevlab'); // Tu base de datos
define('DB_USER', 'u569129255_brodevlab'); // Tu usuario
define('DB_PASSWORD', 'tu_password_real'); // Tu contraseña
```

### 2. Subir Archivos por FTP

**Opción A: File Manager de Hostinger**
1. Login en hPanel de Hostinger
2. Ir a "Files" → "File Manager"
3. Navegar a `public_html`
4. Subir archivos según la lista arriba

**Opción B: FileZilla (FTP)**
```
Host: ftp.grey-squirrel-133805.hostingersite.com
Puerto: 21
Usuario: [tu usuario FTP]
Contraseña: [tu contraseña]
```

### 3. Verificar Permisos

**Archivos PHP:** 644
**Carpetas:** 755

```bash
# Si tienes acceso SSH
chmod 644 *.php
chmod 755 api/ admin/ config/
chmod 644 api/*.php admin/*.php config/*.php
```

### 4. Instalar Base de Datos

1. Abrir: https://grey-squirrel-133805.hostingersite.com/install-tasks.html
2. Click "Instalar Sistema de Tareas"
3. Esperar 15-20 segundos
4. Verificar: "✅ Instalación Completada"

**Tablas que se crearán:**
- `tasks` - Tareas principales (8 ejemplos)
- `task_comments` - Comentarios por tarea
- `task_activity` - Audit trail
- `subtasks` - Subtareas
- `task_tags` - Etiquetas
- `task_tag_relations` - Relación tareas-tags

### 5. Probar Board View

**URL:** https://grey-squirrel-133805.hostingersite.com/admin/board-view.html

**Checklist de Pruebas:**
- [ ] Se ven 4 columnas (Por Hacer, En Progreso, En Revisión, Completado)
- [ ] Aparecen 8 tareas de ejemplo
- [ ] Puedes arrastrar tareas entre columnas
- [ ] Click "➕ Nueva Tarea" abre modal
- [ ] Puedes crear una tarea nueva
- [ ] Click en tarea permite editar
- [ ] Click en 🗑️ elimina tarea
- [ ] Búsqueda funciona (Ctrl+K)
- [ ] Aparecen notificaciones toast

### 6. Verificar API

**Probar Endpoint:**
https://grey-squirrel-133805.hostingersite.com/api/tasks.php?action=by-status

**Respuesta Esperada:**
```json
{
  "success": true,
  "data": {
    "todo": [...],
    "in_progress": [...],
    "review": [...],
    "done": [...]
  },
  "total": 8
}
```

---

## 🐛 Troubleshooting

### Error: "Failed to fetch"

**Causa:** API no responde o CORS bloqueado

**Solución:**
1. Verificar que `api/tasks.php` existe
2. Abrir directamente: `https://grey-squirrel-133805.hostingersite.com/api/tasks.php`
3. Debe mostrar JSON o error específico
4. Revisar archivo `.htaccess` no bloquea API

### Error: 500 Internal Server Error

**Causa:** Error en PHP

**Solución:**
1. Habilitar display_errors temporalmente:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
2. Revisar logs en hPanel → "Error Logs"
3. Verificar sintaxis PHP (PHP 7.4+ requerido)

### Error: "Connection refused"

**Causa:** Credenciales de base de datos incorrectas

**Solución:**
1. Ir a hPanel → "Databases"
2. Verificar nombre de base de datos
3. Verificar usuario y contraseña
4. Actualizar `config/config.php`

### Error: CSS no carga

**Causa:** Ruta incorrecta o caché

**Solución:**
1. Verificar que `admin/board-styles.css` existe
2. Limpiar caché: Ctrl+Shift+R
3. Abrir directamente: `https://grey-squirrel-133805.hostingersite.com/admin/board-styles.css`

### Error: Drag & drop no funciona

**Causa:** JavaScript no carga

**Solución:**
1. Abrir consola (F12)
2. Ver errores en rojo
3. Verificar que SortableJS CDN carga
4. Verificar que `board-view.js` carga

---

## 📊 Estructura de Archivos en Servidor

```
public_html/
├── database-tasks.sql
├── install-tasks.html
├── install-tasks.php
├── HOSTINGER-INSTALL.html
│
├── api/
│   ├── tasks.php          ← NUEVO
│   ├── projects.php
│   ├── clients.php
│   ├── custom-fields.php
│   └── auth.php
│
├── admin/
│   ├── board-view.html    ← NUEVO
│   ├── board-view.js      ← NUEVO
│   ├── board-styles.css   ← NUEVO
│   ├── index.php
│   └── admin-styles.css
│
└── config/
    ├── config.php
    └── auth-middleware.php
```

---

## ✅ Verificación Final

**Una vez instalado, verifica:**

1. **Base de Datos:**
   - [ ] Tabla `tasks` existe
   - [ ] Tabla tiene 8 registros
   - [ ] Tabla `task_comments` existe

2. **API:**
   - [ ] GET `/api/tasks.php` responde JSON
   - [ ] GET `/api/tasks.php?action=by-status` agrupa por estado
   - [ ] POST crea tareas (probar desde Board)

3. **Board View:**
   - [ ] Interfaz se ve correctamente
   - [ ] Drag & drop funciona
   - [ ] CRUD completo (crear, leer, actualizar, eliminar)
   - [ ] Notificaciones toast aparecen

4. **Performance:**
   - [ ] Carga en < 2 segundos
   - [ ] No errores en consola
   - [ ] Responsive en móvil

---

## 🚀 Próximos Pasos

Una vez que todo funcione:

1. **WebSocket Real-Time:**
   - Configurar servidor Node.js en Hostinger (VPS necesario)
   - O usar servicio externo (Pusher, Ably, Socket.io hosted)

2. **Vista Calendario:**
   - Integrar FullCalendar.js
   - Mostrar tareas por fecha

3. **Sistema de Comentarios:**
   - CRUD de comentarios
   - Menciones @usuario
   - Tiempo real con WebSocket

4. **Optimizaciones:**
   - Minificar JS/CSS
   - Lazy loading de imágenes
   - Service Worker para offline

---

## 📞 Enlaces Útiles

- **Sitio:** https://grey-squirrel-133805.hostingersite.com
- **Board View:** https://grey-squirrel-133805.hostingersite.com/admin/board-view.html
- **Instalador:** https://grey-squirrel-133805.hostingersite.com/install-tasks.html
- **API Test:** https://grey-squirrel-133805.hostingersite.com/api/tasks.php?action=by-status
- **hPanel:** https://hpanel.hostinger.com

---

**✨ ¡Listo para subir a producción!**
