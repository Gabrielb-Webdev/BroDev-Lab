# 🚀 WebSocket Server - Instalación y Configuración

## 📋 Requisitos Previos

### Software Necesario

1. **Node.js 18+** (recomendado: 20.x LTS)
   ```bash
   # Verificar versión
   node --version
   
   # Descargar: https://nodejs.org/
   ```

2. **MySQL 5.7+** (ya instalado)

3. **Redis** (opcional pero recomendado para producción)
   ```bash
   # Windows: Descargar desde https://github.com/microsoftarchive/redis/releases
   # O usar WSL2 con: sudo apt-get install redis-server
   
   # Verificar instalación
   redis-cli ping
   # Debería responder: PONG
   ```

## 🔧 Instalación Paso a Paso

### Paso 1: Instalar Dependencias

```powershell
# Navegar al directorio del servidor
cd "F:\Users\gabri\Documentos\Gabriel Dev\BroDev Lab\realtime-server"

# Instalar dependencias de Node.js
npm install
```

**Paquetes instalados:**
- `ws` - Servidor WebSocket
- `mysql2` - Driver MySQL con soporte de promesas
- `redis` - Cliente Redis para caché y pub/sub
- `dotenv` - Variables de entorno
- `nodemon` - Auto-reload en desarrollo

### Paso 2: Configurar Variables de Entorno

```powershell
# Copiar archivo de ejemplo
cp .env.example .env

# Editar .env con tus credenciales
notepad .env
```

**Contenido de `.env`:**
```env
# Puerto del servidor WebSocket
WS_PORT=8080

# Credenciales de MySQL
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=tu_password_aqui
DB_NAME=brodevlab

# Redis (opcional)
REDIS_URL=redis://localhost:6379

# Ambiente
NODE_ENV=development
```

### Paso 3: Iniciar Redis (Opcional)

**Windows con WSL2:**
```bash
# Iniciar Redis
sudo service redis-server start

# Verificar
redis-cli ping
```

**Windows nativo:**
```powershell
# Descargar Redis desde:
# https://github.com/microsoftarchive/redis/releases

# Ejecutar
redis-server.exe
```

**Sin Redis:**
El servidor funciona sin Redis, pero no tendrás:
- Caché de consultas (menos performance)
- Pub/Sub para clusters multi-servidor

### Paso 4: Iniciar Servidor WebSocket

**Desarrollo:**
```powershell
npm run dev
```

**Producción:**
```powershell
npm start
```

**Salida esperada:**
```
🚀 Iniciando servidor WebSocket BroDev Lab...
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ MySQL conectado
✅ Redis conectado
✅ Redis Pub/Sub configurado
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Servidor WebSocket escuchando en puerto 8080
🌐 Conecta desde el cliente: ws://localhost:8080
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### Paso 5: Compilar TypeScript del Cliente

```powershell
# Navegar al directorio admin
cd "F:\Users\gabri\Documentos\Gabriel Dev\BroDev Lab\admin"

# Instalar TypeScript (si no está instalado)
npm install -g typescript

# Compilar websocket-client.ts
tsc websocket-client.ts
```

Esto generará `websocket-client.js` listo para usar.

### Paso 6: Actualizar Admin Panel

Editar `admin/index.php`, agregar antes del cierre de `</body>`:

```html
<!-- WebSocket Client -->
<script type="module" src="websocket-client.js?v=1.0"></script>
<script type="module" src="realtime-websocket.js?v=1.0"></script>
```

### Paso 7: Probar Conexión

1. Abrir Admin Panel: `http://localhost/admin/`
2. Abrir consola del navegador (F12)
3. Deberías ver:
   ```
   🔌 Conectando a WebSocket: ws://localhost:8080
   ✅ WebSocket conectado
   🆔 Client ID asignado: client_1732604123456_abc123
   📡 Suscrito a: project
   ✅ Sincronización WebSocket activa
   ```
4. En la esquina inferior izquierda verás: **🟢 Sincronización en tiempo real**

## ✅ Verificación de Instalación

### Test 1: Ping-Pong
```javascript
// En consola del navegador
wsClient.ws.send(JSON.stringify({ type: 'ping' }))
// Deberías ver en logs del servidor: pong
```

### Test 2: Actualización en Tiempo Real

**Navegador 1:**
1. Abre Admin Panel
2. Edita un campo de un proyecto

**Navegador 2:**
1. Abre Admin Panel en otra ventana/navegador
2. En < 50ms verás el cambio reflejado automáticamente
3. Notificación: "✏️ Campo actualizado en project"

### Test 3: Crear Columna

**Navegador 1:**
```javascript
// Click en "➕ Agregar Columna"
// Crea campo "test_websocket"
```

**Navegador 2:**
- La nueva columna aparece instantáneamente
- Notificación: "➕ Nueva columna agregada: test_websocket"

## 📊 Monitoreo

### Logs del Servidor

El servidor WebSocket muestra logs en tiempo real:

```
🔌 Cliente conectado: client_abc123 (Total: 3)
📡 Cliente client_abc123 suscrito a: project
✅ Campo 45 actualizado para entidad 12
📡 Broadcast a 2 clientes: field-updated
💚 Health: 3 clientes, 3 suscripciones
```

### Estadísticas en Redis

```bash
# Ver estadísticas del servidor
redis-cli GET ws-stats

# Salida (JSON):
{
  "connectedClients": 3,
  "totalSubscriptions": 3,
  "uptime": 3600,
  "memory": {...}
}
```

### Dashboard de Métricas (Opcional)

Instalar PM2 para monitoreo avanzado:

```powershell
npm install -g pm2

# Iniciar con PM2
pm2 start server.js --name brodevlab-ws

# Ver logs
pm2 logs brodevlab-ws

# Ver métricas
pm2 monit

# Dashboard web
pm2 web
```

## 🔥 Optimización para Producción

### 1. Configurar PM2

```javascript
// ecosystem.config.js
module.exports = {
  apps: [{
    name: 'brodevlab-ws',
    script: './server.js',
    instances: 2, // 2 procesos
    exec_mode: 'cluster',
    env: {
      NODE_ENV: 'production',
      WS_PORT: 8080
    },
    error_file: './logs/err.log',
    out_file: './logs/out.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss'
  }]
};
```

```powershell
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

### 2. Nginx Reverse Proxy

```nginx
# /etc/nginx/sites-available/brodevlab

upstream websocket {
    server localhost:8080;
    server localhost:8081; # Si usas cluster
}

server {
    listen 443 ssl;
    server_name tu-dominio.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # WebSocket
    location /ws {
        proxy_pass http://websocket;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_read_timeout 86400;
    }
    
    # Admin Panel
    location / {
        proxy_pass http://localhost:80;
    }
}
```

### 3. Firewall

```powershell
# Windows Firewall
New-NetFirewallRule -DisplayName "WebSocket BroDev" -Direction Inbound -LocalPort 8080 -Protocol TCP -Action Allow
```

### 4. SSL/TLS (wss://)

Para producción, usar `wss://` en lugar de `ws://`:

**En cliente (realtime-websocket.js):**
```javascript
getWebSocketUrl() {
    const protocol = 'wss:'; // Siempre usar secure
    const host = window.location.hostname;
    const port = 443; // Puerto HTTPS
    return `${protocol}//${host}:${port}/ws`;
}
```

### 5. Límites de Conexiones

En `server.js`:

```javascript
const wss = new WebSocketServer({ 
    port: WS_PORT,
    perMessageDeflate: false, // Desactivar compresión para mejor performance
    maxPayload: 100 * 1024, // 100KB máximo por mensaje
    clientTracking: true
});

// Límite de clientes
wss.on('connection', (ws, req) => {
    if (clients.size >= 1000) { // Máximo 1000 clientes
        ws.close(1008, 'Servidor lleno');
        return;
    }
    // ... resto del código
});
```

## 🐛 Troubleshooting

### Error: "EADDRINUSE"
```
El puerto 8080 ya está en uso.
```

**Solución:**
```powershell
# Ver qué está usando el puerto
netstat -ano | findstr :8080

# Matar proceso
taskkill /PID <PID> /F

# O cambiar puerto en .env
WS_PORT=8081
```

### Error: "MySQL connection refused"
```
❌ Error conectando MySQL: ECONNREFUSED
```

**Solución:**
1. Verificar que MySQL esté corriendo
2. Verificar credenciales en `.env`
3. Verificar que la base de datos `brodevlab` exista
4. Verificar permisos del usuario MySQL

### Error: "Redis connection refused"
```
⚠️ Continuando sin Redis (sin caché)
```

**Solución:**
1. Iniciar Redis: `redis-server`
2. O desactivar Redis en código (ya funciona sin él)
3. Verificar URL en `.env`: `REDIS_URL=redis://localhost:6379`

### Error: "WebSocket no conecta desde navegador"
```
WebSocket connection failed
```

**Solución:**
1. Verificar que el servidor esté corriendo: `npm run dev`
2. Verificar URL en consola: ¿Dice `ws://localhost:8080`?
3. Verificar firewall no bloquea puerto 8080
4. Abrir `ws://localhost:8080` en navegador directamente (debería dar error pero confirma que está escuchando)

### Logs no aparecen

**Solución:**
```powershell
# Verificar que NODE_ENV esté en development
echo $env:NODE_ENV

# Establecer
$env:NODE_ENV="development"

# O en .env
NODE_ENV=development
```

## 📚 Comandos Útiles

```powershell
# Desarrollo con auto-reload
npm run dev

# Producción
npm start

# Ver logs de PM2
pm2 logs brodevlab-ws --lines 100

# Reiniciar servidor
pm2 restart brodevlab-ws

# Detener servidor
pm2 stop brodevlab-ws

# Ver métricas en tiempo real
pm2 monit

# Limpiar logs
pm2 flush

# Desinstalar PM2 startup
pm2 unstartup
```

## 🎯 Próximos Pasos

1. ✅ Servidor WebSocket instalado
2. ✅ Cliente TypeScript compilado
3. ⬜ Migrar a TypeScript el backend (Node.js con Express + TypeScript)
4. ⬜ Implementar Vue.js 3 para UI reactiva
5. ⬜ Agregar autenticación JWT para WebSocket
6. ⬜ Implementar rooms para equipos/workspaces
7. ⬜ Agregar compresión de mensajes
8. ⬜ Métricas con Prometheus + Grafana

## 📞 Soporte

**Documentación completa**: `README-WEBSOCKET.md`  
**Ejemplos**: `admin/examples-dynamic-system.js`  
**Código fuente**: `realtime-server/server.js`

---

**🎉 ¡Listo! Tu sistema ahora tiene sincronización en tiempo real < 50ms**
