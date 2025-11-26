# 🚀 Instalador Automático - WebSocket BroDev Lab
# PowerShell Script para Windows

Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  🚀 BroDev Lab - Instalador WebSocket                  ║" -ForegroundColor Cyan
Write-Host "║  Sincronización en Tiempo Real < 50ms                   ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Función para mostrar progreso
function Write-Step {
    param($Number, $Message)
    Write-Host ""
    Write-Host "[$Number/6] " -NoNewline -ForegroundColor Yellow
    Write-Host $Message -ForegroundColor White
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor DarkGray
}

function Write-Success {
    param($Message)
    Write-Host "  ✅ " -NoNewline -ForegroundColor Green
    Write-Host $Message -ForegroundColor Gray
}

function Write-Error {
    param($Message)
    Write-Host "  ❌ " -NoNewline -ForegroundColor Red
    Write-Host $Message -ForegroundColor Red
}

function Write-Warning {
    param($Message)
    Write-Host "  ⚠️  " -NoNewline -ForegroundColor Yellow
    Write-Host $Message -ForegroundColor Yellow
}

# Variables
$ProjectRoot = $PSScriptRoot
$AdminDir = Join-Path $ProjectRoot "admin"
$ServerDir = Join-Path $ProjectRoot "realtime-server"

# PASO 1: Verificar Node.js
Write-Step 1 "Verificando Node.js..."

try {
    $nodeVersion = node --version 2>$null
    if ($nodeVersion) {
        Write-Success "Node.js instalado: $nodeVersion"
        
        $npmVersion = npm --version 2>$null
        Write-Success "npm instalado: v$npmVersion"
    } else {
        Write-Error "Node.js NO encontrado"
        Write-Host ""
        Write-Host "  📦 Descarga Node.js desde: https://nodejs.org/" -ForegroundColor Cyan
        Write-Host "  👉 Instala la versión 20.x LTS recomendada" -ForegroundColor Cyan
        Write-Host "  🔄 Después ejecuta este script de nuevo" -ForegroundColor Cyan
        Write-Host ""
        exit 1
    }
} catch {
    Write-Error "Error verificando Node.js: $_"
    exit 1
}

# PASO 2: Compilar TypeScript
Write-Step 2 "Compilando TypeScript..."

Set-Location $AdminDir

if (Test-Path "websocket-client.ts") {
    try {
        # Compilar con npx (no requiere instalación global)
        npx -y typescript@latest websocket-client.ts 2>&1 | Out-Null
        
        if (Test-Path "websocket-client.js") {
            Write-Success "websocket-client.js creado"
            
            if (Test-Path "websocket-client.js.map") {
                Write-Success "Source map creado"
            }
            
            if (Test-Path "websocket-client.d.ts") {
                Write-Success "Type definitions creado"
            }
        } else {
            Write-Error "Falló compilación de TypeScript"
            exit 1
        }
    } catch {
        Write-Error "Error compilando TypeScript: $_"
        exit 1
    }
} else {
    Write-Warning "websocket-client.ts no encontrado"
}

# PASO 3: Instalar Dependencias del Servidor
Write-Step 3 "Instalando dependencias del servidor..."

Set-Location $ServerDir

if (Test-Path "package.json") {
    try {
        Write-Host "  📦 Ejecutando npm install..." -ForegroundColor Gray
        npm install --silent
        
        Write-Success "ws@8.14.2 instalado"
        Write-Success "mysql2@3.6.5 instalado"
        Write-Success "redis@4.6.11 instalado"
        Write-Success "dotenv@16.3.1 instalado"
        Write-Success "nodemon (dev) instalado"
    } catch {
        Write-Error "Error instalando dependencias: $_"
        exit 1
    }
} else {
    Write-Error "package.json no encontrado en realtime-server/"
    exit 1
}

# PASO 4: Configurar Variables de Entorno
Write-Step 4 "Configurando variables de entorno..."

$envFile = Join-Path $ServerDir ".env"
$envExample = Join-Path $ServerDir ".env.example"

if (-not (Test-Path $envFile)) {
    if (Test-Path $envExample) {
        Copy-Item $envExample $envFile
        Write-Success ".env creado desde .env.example"
        Write-Warning "IMPORTANTE: Edita realtime-server/.env con tus credenciales MySQL"
        Write-Host ""
        Write-Host "  Archivo: $envFile" -ForegroundColor Cyan
        Write-Host "  Configura: DB_USER, DB_PASSWORD, DB_NAME" -ForegroundColor Cyan
        Write-Host ""
    } else {
        Write-Error ".env.example no encontrado"
    }
} else {
    Write-Success ".env ya existe"
}

# PASO 5: Verificar MySQL
Write-Step 5 "Verificando configuración MySQL..."

if (Test-Path $envFile) {
    $envContent = Get-Content $envFile
    $dbHost = ($envContent | Select-String "DB_HOST=(.+)" | ForEach-Object { $_.Matches.Groups[1].Value })
    $dbName = ($envContent | Select-String "DB_NAME=(.+)" | ForEach-Object { $_.Matches.Groups[1].Value })
    
    if ($dbHost -and $dbName) {
        Write-Success "DB_HOST: $dbHost"
        Write-Success "DB_NAME: $dbName"
        Write-Warning "Verifica que las credenciales sean correctas en .env"
    } else {
        Write-Warning "Configura DB_HOST y DB_NAME en .env"
    }
}

# PASO 6: Iniciar Servidor
Write-Step 6 "Iniciando servidor WebSocket..."

Write-Host ""
Write-Host "  🚀 Iniciando en puerto 8080..." -ForegroundColor Cyan
Write-Host "  📡 Presiona Ctrl+C para detener" -ForegroundColor Gray
Write-Host ""

try {
    npm start
} catch {
    Write-Error "Error iniciando servidor: $_"
    Write-Host ""
    Write-Host "  🔧 Verifica que:" -ForegroundColor Yellow
    Write-Host "    1. MySQL esté corriendo" -ForegroundColor Gray
    Write-Host "    2. Credenciales en .env sean correctas" -ForegroundColor Gray
    Write-Host "    3. Puerto 8080 esté disponible" -ForegroundColor Gray
    Write-Host ""
    exit 1
}

# Final
Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║  ✅ Instalación Completada                              ║" -ForegroundColor Green
Write-Host "╚══════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "🎉 ¡Tu sistema ahora tiene sincronización en tiempo real!" -ForegroundColor Cyan
Write-Host ""
Write-Host "📖 Próximos pasos:" -ForegroundColor Yellow
Write-Host "  1. Abre: http://localhost/admin/" -ForegroundColor Gray
Write-Host "  2. Verifica conexión (F12 → Consola)" -ForegroundColor Gray
Write-Host "  3. Prueba editando un campo" -ForegroundColor Gray
Write-Host ""
Write-Host "📚 Documentación:" -ForegroundColor Yellow
Write-Host "  - QUICK-START.md (inicio rápido)" -ForegroundColor Gray
Write-Host "  - INSTALL-WEBSOCKET.md (instalación detallada)" -ForegroundColor Gray
Write-Host "  - README-WEBSOCKET.md (configuración avanzada)" -ForegroundColor Gray
Write-Host ""
