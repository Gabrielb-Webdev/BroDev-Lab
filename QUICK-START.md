# ⚡ Quick Start - Instalación Rápida

## 🎯 Instalación en 5 Minutos

### ✅ Paso 1: Instalar Node.js (REQUERIDO)

**Windows:**
1. Descargar Node.js 20.x LTS: https://nodejs.org/
2. Ejecutar instalador (siguiente, siguiente...)
3. Verificar instalación:
   ```powershell
   node --version
   # Debería mostrar: v20.x.x
   
   npm --version
   # Debería mostrar: 10.x.x
   ```

### ⚡ Paso 2: Ejecutar Script de Instalación

```powershell
# Ir al directorio del proyecto
cd "F:\Users\gabri\Documentos\Gabriel Dev\BroDev Lab"

# Ejecutar instalador automático
.\install-websocket.ps1
```

El script automáticamente:
- ✅ Compila TypeScript
- ✅ Instala dependencias de Node.js
- ✅ Crea archivo .env
- ✅ Inicia servidor WebSocket

### 🚀 Paso 3: Verificar Instalación

1. **Abrir navegador**: http://localhost/admin/
2. **Revisar consola** (F12):
   ```
   ✅ WebSocket conectado
   🆔 Client ID: client_xxx
   📡 Suscrito a: project
   ```
3. **Ver indicador**: Esquina inferior izquierda debe mostrar "🟢 Sincronización en tiempo real"

### 🧪 Paso 4: Probar Tiempo Real

**Test rápido:**
1. Abre dos ventanas del navegador con el admin panel
2. En ventana 1: Edita un campo de un proyecto
3. En ventana 2: **El cambio aparece instantáneamente** (< 50ms)
4. Notificación: "✏️ Campo actualizado"

---

## 🛠️ Comandos Útiles

### Iniciar Servidor WebSocket
```powershell
cd realtime-server
npm start
```

### Modo Desarrollo (auto-reload)
```powershell
cd realtime-server
npm run dev
```

### Ver Logs
```powershell
# Logs del servidor en tiempo real
cd realtime-server
npm start
```

### Detener Servidor
```
Ctrl + C en la terminal donde está corriendo
```

---

## 📋 ¿Qué Tecnología Instalaste?

- **Node.js WebSocket Server**: Sincronización < 50ms
- **TypeScript Client**: Type-safe, compilado a JavaScript
- **Redis** (opcional): Caché y Pub/Sub
- **Hybrid Fallback**: Si WebSocket falla, usa polling automático

---

## 🐛 Problemas Comunes

### "npm no se reconoce"
**Causa**: Node.js no instalado  
**Solución**: Instalar desde https://nodejs.org/

### "Puerto 8080 en uso"
**Causa**: Otra aplicación usa el puerto  
**Solución**:
```powershell
# Cambiar puerto en realtime-server/.env
WS_PORT=8081
```

### "WebSocket no conecta"
**Causa**: Servidor no está corriendo  
**Solución**:
```powershell
cd realtime-server
npm start
```

### "Redis connection refused"
**No es problema**: El sistema funciona sin Redis  
**Solución (opcional)**: Instalar Redis para mejor performance

---

## 📚 Documentación Completa

- **Instalación detallada**: `INSTALL-WEBSOCKET.md`
- **Configuración avanzada**: `README-WEBSOCKET.md`
- **Ejemplos de uso**: `admin/examples-dynamic-system.js`
- **Arquitectura**: `realtime-server/server.js` (líneas 1-50)

---

## 🎉 ¡Listo!

Tu sistema ahora tiene:
- ⚡ Sincronización en tiempo real (< 50ms)
- 🔄 Fallback automático a polling
- 📊 21 tipos de campos customizables
- 👥 Colaboración en equipo
- 📱 Responsive design
- 🔒 Type-safe con TypeScript

**Próximo paso**: Abre el admin panel y empieza a crear campos dinámicos.
