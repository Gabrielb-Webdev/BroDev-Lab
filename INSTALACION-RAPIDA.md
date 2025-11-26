# 🚀 INSTALACIÓN RÁPIDA - BroDev Lab

## ⚡ La Forma Más Fácil (1 Minuto)

### 1️⃣ Configurar Base de Datos

Edita `config/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tu_base_datos');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 2️⃣ Ejecutar Instalador Automático

Visita en tu navegador:

```
https://tudominio.com/install-updates.php
```

O localmente:

```
http://localhost/BroDev-Lab/install-updates.php
```

### 3️⃣ Hacer Clic en el Botón

Presiona **"🚀 Instalar Actualizaciones"**

### 4️⃣ ¡Listo!

Ya puedes usar el sistema completo.

---

## 🎯 Páginas de Inicio Rápido

### Desarrollo Local:
- **Instalador**: http://localhost/BroDev-Lab/install-updates.php
- **Inicio Rápido**: http://localhost/BroDev-Lab/quick-start.html
- **Panel Admin**: http://localhost/BroDev-Lab/admin/index.php

### Producción (Hostinger):
- **Instalador**: https://tudominio.com/install-updates.php
- **Inicio Rápido**: https://tudominio.com/quick-start.html
- **Panel Admin**: https://tudominio.com/admin/index.php

---

## ✅ ¿Qué Hace el Instalador Automático?

El instalador se encarga de:

- ✅ Crear todas las tablas necesarias
- ✅ Configurar 10 estados de proyectos con emojis
- ✅ Migrar datos antiguos automáticamente
- ✅ Configurar zona horaria de Argentina
- ✅ Verificar integridad del sistema
- ✅ Optimizar registro de tiempo
- ✅ Crear archivo de bloqueo (install.lock)

**Todo esto en un solo clic. Sin SQL manual. Sin errores.**

---

## 🔐 Primer Acceso

### Usuario Admin por Defecto:

```
Usuario: admin
Contraseña: admin123
```

⚠️ **IMPORTANTE**: Cambia la contraseña después del primer login.

---

## 🆘 ¿Problemas?

### El instalador dice "Ya Instalado"

- Es normal si ya ejecutaste el instalador antes
- Puedes forzar reinstalación con el botón "⚠️ Forzar Reinstalación"

### Error de Conexión a Base de Datos

1. Verifica que MySQL esté corriendo
2. Revisa los datos en `config/config.php`
3. Asegúrate que la base de datos existe

### Permisos de Archivo

Si no puede crear `install.lock`:

```bash
chmod 755 /ruta/a/BroDev-Lab
```

---

## 📚 Documentación Completa

Para instalación manual o configuración avanzada, consulta:

- `INSTALL.md` - Guía completa de instalación
- `README.md` - Documentación general
- `database.sql` - Estructura de base de datos (referencia)

---

## 🎉 Ya Instalado

Una vez instalado:

1. Ve al **Panel de Administración**
2. Crea tu primer **Cliente**
3. Crea tu primer **Proyecto**
4. Agrega **Fases** al proyecto
5. Usa el **Timer** para registrar tiempo

**¡Disfruta tu sistema de gestión de clientes!** 🚀
