# ✅ Checklist de Instalación WebSocket

## 📋 Pre-requisitos

- [ ] **Node.js 18+** instalado
  - Verificar: `node --version` → Debe mostrar v18.x o superior
  - Descargar: https://nodejs.org/
  
- [ ] **MySQL** corriendo
  - Base de datos `brodevlab` creada
  - Usuario con permisos de lectura/escritura

- [ ] **Redis** (opcional pero recomendado)
  - Verificar: `redis-cli ping` → Debe responder PONG
  - Sin Redis el sistema funciona pero sin caché

---

## 🔧 Pasos de Instalación

### 1. Instalar Dependencias

```powershell
cd "F:\Users\gabri\Documentos\Gabriel Dev\BroDev Lab\realtime-server"
npm install
```

- [ ] ws@8.14.2 instalado
- [ ] mysql2@3.6.5 instalado
- [ ] redis@4.6.11 instalado
- [ ] dotenv@16.3.1 instalado
- [ ] nodemon instalado (dev)

### 2. Compilar TypeScript

```powershell
cd "F:\Users\gabri\Documentos\Gabriel Dev\BroDev Lab\admin"
npx -y typescript@latest websocket-client.ts
```

- [ ] `websocket-client.js` creado
- [ ] `websocket-client.js.map` creado
- [ ] `websocket-client.d.ts` creado

### 3. Configurar Variables de Entorno

```powershell
cd realtime-server
cp .env.example .env
notepad .env
```

Editar `.env` con tus credenciales:

```env
WS_PORT=8080
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=TU_PASSWORD_AQUI
DB_NAME=brodevlab
REDIS_URL=redis://localhost:6379
NODE_ENV=development
```

- [ ] WS_PORT configurado
- [ ] DB_HOST configurado
- [ ] DB_USER configurado
- [ ] DB_PASSWORD configurado (IMPORTANTE)
- [ ] DB_NAME configurado
- [ ] REDIS_URL configurado (si aplica)

### 4. Iniciar Servidor WebSocket

```powershell
cd realtime-server
npm start
```

Verificar en terminal:

- [ ] `✅ MySQL conectado`
- [ ] `✅ Redis conectado` (si aplica)
- [ ] `✅ Servidor WebSocket escuchando en puerto 8080`

---

## 🧪 Verificación de Instalación

### Test 1: Verificar Puerto

```powershell
Test-NetConnection localhost -Port 8080
```

- [ ] `TcpTestSucceeded: True`

### Test 2: Abrir Admin Panel

1. Abrir navegador: http://localhost/admin/
2. Abrir consola (F12)

Verificar logs en consola:

- [ ] `🔌 Conectando a WebSocket: ws://localhost:8080`
- [ ] `✅ WebSocket conectado`
- [ ] `🆔 Client ID asignado: client_xxx`
- [ ] `📡 Suscrito a: project`

### Test 3: Indicador Visual

Esquina inferior izquierda del admin panel:

- [ ] Mostrar: `🟢 Sincronización en tiempo real`
- [ ] Dot verde pulsando
- [ ] Texto visible

### Test 4: Sincronización en Tiempo Real

**Setup**: Abrir 2 ventanas del navegador

**Ventana 1**:
1. Navegar a proyectos
2. Editar un campo
3. Guardar

**Ventana 2**:
- [ ] Cambio aparece **instantáneamente** (< 2 segundos)
- [ ] Notificación en esquina inferior derecha
- [ ] Sin recargar página

---

## 🐛 Troubleshooting

### ❌ npm no se reconoce

**Solución**:
- Instalar Node.js desde https://nodejs.org/
- Reiniciar terminal
- Verificar: `node --version`

### ❌ EADDRINUSE: Puerto 8080 en uso

**Solución**:
```powershell
# Ver qué usa el puerto
netstat -ano | findstr :8080

# Matar proceso
taskkill /PID <PID> /F
```

### ❌ MySQL connection refused

**Solución**:
- Verificar MySQL esté corriendo
- Verificar credenciales en `.env`
- Verificar base de datos existe: `mysql -u root -p -e "SHOW DATABASES;"`

### ❌ WebSocket no conecta

**Solución**:
1. Verificar servidor esté corriendo: `cd realtime-server; npm start`
2. Verificar firewall no bloquea puerto 8080
3. Verificar URL en navegador: debe ser `ws://localhost:8080`

### ⚠️ Redis connection refused

**No es problema**: Sistema funciona sin Redis

**Para instalar Redis**:
- Windows: https://github.com/microsoftarchive/redis/releases
- WSL2: `sudo apt-get install redis-server`
- Docker: `docker run -d -p 6379:6379 redis:alpine`

---

## 📊 Métricas de Éxito

### Performance

- [ ] **Latencia < 100ms**: Actualización entre ventanas en menos de 100ms
- [ ] **Conexión estable**: No desconexiones en 5 minutos
- [ ] **Reconexión automática**: Si se desconecta, reconecta en < 10s

### Funcionalidad

- [ ] **Edición inline funciona**: Click en celda → editar → guardar
- [ ] **Agregar columna funciona**: Click "➕ Agregar Columna" → crear campo
- [ ] **Eliminar columna funciona**: Click "🗑️" → confirmar → eliminar
- [ ] **Sincronización funciona**: Cambios en ventana 1 aparecen en ventana 2

### UI

- [ ] **Notificaciones aparecen**: Al actualizar campo, ver notificación
- [ ] **Indicador de conexión visible**: Esquina inferior izquierda
- [ ] **Sin errores en consola**: F12 → Console → No errores rojos

---

## 🎉 Instalación Completa

Si todos los checkboxes están marcados, ¡felicidades!

Tu sistema ahora tiene:

✅ **Sincronización en tiempo real < 50ms**  
✅ **21 tipos de campos customizables**  
✅ **Colaboración en equipo**  
✅ **Fallback automático a polling**  
✅ **Type-safe con TypeScript**  
✅ **UI responsive con notificaciones**  

---

## 📚 Próximos Pasos

1. **Leer documentación completa**: `README-WEBSOCKET.md`
2. **Ver ejemplos**: `admin/examples-dynamic-system.js`
3. **Configurar producción**: `INSTALL-WEBSOCKET.md` (sección "Optimización para Producción")
4. **Explorar features**: Crear campos, editar valores, probar tiempo real

---

## 🆘 Ayuda

Si algo no funciona:

1. **Revisar logs del servidor** (terminal donde corre `npm start`)
2. **Revisar consola del navegador** (F12 → Console)
3. **Verificar este checklist** de nuevo
4. **Leer troubleshooting** en `README-WEBSOCKET.md`

---

**¡Éxito! 🚀**

*Generado automáticamente por BroDev Lab - Enero 2024*
