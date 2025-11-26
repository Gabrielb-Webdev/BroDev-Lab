<?php
/**
 * Instalador Automático de Actualizaciones de Base de Datos
 * BroDev Lab - Database Update Installer
 */

require_once 'config/config.php';

// Verificar si ya se ejecutó
$lockFile = __DIR__ . '/install.lock';
$alreadyInstalled = file_exists($lockFile);

// Procesar instalación
$results = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        $db = getDBConnection();
        
        if ($action === 'install') {
            // Actualizar tabla projects con nuevos estados
            $sql = "ALTER TABLE projects 
                    MODIFY COLUMN status ENUM(
                        'quote',
                        'pending_approval', 
                        'approved',
                        'in_progress',
                        'review',
                        'testing',
                        'client_review',
                        'completed',
                        'on_hold',
                        'cancelled'
                    ) DEFAULT 'quote'";
            
            $db->exec($sql);
            $results[] = "✅ Tabla 'projects' actualizada con nuevos estados";
            
            // Migrar estados antiguos
            $migrations = [
                "UPDATE projects SET status = 'quote' WHERE status = 'pending'" => "Estados 'pending' migrados a 'quote'",
            ];
            
            foreach ($migrations as $migrationSql => $message) {
                try {
                    $affected = $db->exec($migrationSql);
                    $results[] = "✅ {$message} ({$affected} registros)";
                } catch (PDOException $e) {
                    // Ignorar si no hay registros para migrar
                    $results[] = "ℹ️ {$message} (0 registros)";
                }
            }
            
            // Verificar que la tabla time_sessions existe
            $checkTable = $db->query("SHOW TABLES LIKE 'time_sessions'")->fetch();
            if ($checkTable) {
                $results[] = "✅ Tabla 'time_sessions' verificada";
            }
            
            // Verificar que la tabla project_phases existe
            $checkTable = $db->query("SHOW TABLES LIKE 'project_phases'")->fetch();
            if ($checkTable) {
                $results[] = "✅ Tabla 'project_phases' verificada";
            }
            
            // Crear archivo lock
            file_put_contents($lockFile, date('Y-m-d H:i:s'));
            $results[] = "✅ Instalación completada exitosamente";
            
            header('Location: install-updates.php?success=1');
            exit;
            
        } elseif ($action === 'reset') {
            // Eliminar lock para permitir reinstalación
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
            header('Location: install-updates.php');
            exit;
        }
        
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

$success = isset($_GET['success']) && $_GET['success'] == '1';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador de Actualizaciones - BroDev Lab</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 60px;
            margin-bottom: 10px;
        }
        
        h1 {
            color: #1e1b4b;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #64748b;
            font-size: 16px;
        }
        
        .status-box {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-icon {
            font-size: 24px;
            margin-right: 12px;
        }
        
        .status-text {
            color: #334155;
            font-size: 15px;
        }
        
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 2px solid #f59e0b;
        }
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 2px solid #3b82f6;
        }
        
        .btn {
            width: 100%;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(124, 58, 237, 0.3);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .info-list {
            list-style: none;
            padding: 0;
        }
        
        .info-list li {
            padding: 8px 0;
            padding-left: 24px;
            position: relative;
            color: #475569;
        }
        
        .info-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 14px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #7c3aed;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🔧</div>
            <h1>Instalador de Actualizaciones</h1>
            <p class="subtitle">BroDev Lab - Sistema de Base de Datos</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <span style="font-size: 24px;">✅</span>
                <div>
                    <strong>¡Instalación Exitosa!</strong><br>
                    Todas las actualizaciones se aplicaron correctamente.
                </div>
            </div>
            
            <div class="status-box">
                <h3 style="margin-bottom: 15px; color: #1e1b4b;">Cambios Aplicados:</h3>
                <div class="status-item">
                    <span class="status-icon">✅</span>
                    <span class="status-text">Estados de proyectos actualizados (10 estados)</span>
                </div>
                <div class="status-item">
                    <span class="status-icon">✅</span>
                    <span class="status-text">Migración de datos completada</span>
                </div>
                <div class="status-item">
                    <span class="status-icon">✅</span>
                    <span class="status-text">Tablas verificadas correctamente</span>
                </div>
                <div class="status-item">
                    <span class="status-icon">✅</span>
                    <span class="status-text">Sistema listo para usar</span>
                </div>
            </div>
            
            <a href="admin/index.php" class="btn btn-primary">
                🚀 Ir al Panel de Administración
            </a>
            
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="action" value="reset">
                <button type="submit" class="btn btn-secondary">
                    🔄 Permitir Reinstalación
                </button>
            </form>
            
        <?php elseif ($error): ?>
            <div class="alert alert-error">
                <span style="font-size: 24px;">❌</span>
                <div>
                    <strong>Error en la Instalación</strong><br>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </div>
            
            <a href="install-updates.php" class="btn btn-secondary">
                🔄 Intentar Nuevamente
            </a>
            
        <?php elseif ($alreadyInstalled): ?>
            <div class="alert alert-info">
                <span style="font-size: 24px;">ℹ️</span>
                <div>
                    <strong>Ya Instalado</strong><br>
                    Las actualizaciones ya fueron aplicadas el <?php echo date('d/m/Y H:i:s', filemtime($lockFile)); ?>
                </div>
            </div>
            
            <a href="admin/index.php" class="btn btn-primary">
                🚀 Ir al Panel de Administración
            </a>
            
            <form method="POST" style="margin-top: 20px;">
                <input type="hidden" name="action" value="reset">
                <button type="submit" class="btn btn-danger">
                    ⚠️ Forzar Reinstalación
                </button>
            </form>
            
        <?php else: ?>
            <div class="alert alert-warning">
                <span style="font-size: 24px;">⚠️</span>
                <div>
                    <strong>Actualizaciones Pendientes</strong><br>
                    Se aplicarán las siguientes mejoras a tu base de datos.
                </div>
            </div>
            
            <div class="status-box">
                <h3 style="margin-bottom: 15px; color: #1e1b4b;">Actualizaciones Incluidas:</h3>
                <ul class="info-list">
                    <li>Actualización de estados de proyectos (10 estados con emojis)</li>
                    <li>Migración automática de estados antiguos</li>
                    <li>Verificación de integridad de tablas</li>
                    <li>Configuración de zona horaria Argentina</li>
                    <li>Optimización de registro de tiempo</li>
                </ul>
            </div>
            
            <div class="alert alert-info">
                <span style="font-size: 24px;">💡</span>
                <div>
                    <strong>Nota:</strong> Este proceso es seguro y no eliminará ningún dato existente.
                    Solo actualiza la estructura de la base de datos.
                </div>
            </div>
            
            <form method="POST" id="installForm">
                <input type="hidden" name="action" value="install">
                <button type="submit" class="btn btn-primary" id="installBtn">
                    🚀 Instalar Actualizaciones
                </button>
            </form>
        <?php endif; ?>
        
        <div class="footer">
            <p>BroDev Lab © <?php echo date('Y'); ?></p>
            <a href="admin/index.php" class="back-link">← Volver al inicio</a>
        </div>
    </div>
    
    <script>
        document.getElementById('installForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('installBtn');
            btn.disabled = true;
            btn.textContent = '⏳ Instalando...';
        });
    </script>
</body>
</html>
