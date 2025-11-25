<?php
/**
 * Instalador Web Automático - BroDev Lab
 * Accede a este archivo desde el navegador para instalar la base de datos
 * URL: https://grey-squirrel-133805.hostingersite.com/setup.php
 */

// Prevenir acceso después de instalación
$lockFile = __DIR__ . '/.installed';
if (file_exists($lockFile) && !isset($_GET['force'])) {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Ya Instalado</title>
        <style>
            body { font-family: Arial; display: flex; align-items: center; justify-content: center; height: 100vh; background: #1a1a2e; color: #fff; margin: 0; }
            .container { text-align: center; background: #16213e; padding: 40px; border-radius: 20px; box-shadow: 0 10px 50px rgba(0,0,0,0.5); }
            h1 { color: #7C3AED; margin-bottom: 20px; }
            a { display: inline-block; margin-top: 20px; padding: 12px 30px; background: #7C3AED; color: white; text-decoration: none; border-radius: 10px; }
            a:hover { background: #EC4899; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>✅ Sistema Ya Instalado</h1>
            <p>La base de datos ya fue configurada correctamente.</p>
            <a href="admin/login.php">Ir al Login Admin</a>
            <br><br>
            <small>Para reinstalar, agrega ?force=1 a la URL</small>
        </div>
    </body>
    </html>
    ');
}

// Cargar configuración
require_once __DIR__ . '/config/config.php';

$message = '';
$messageType = '';
$logs = [];

// Procesar instalación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        $logs[] = "🚀 Iniciando instalación...";
        
        // Conectar a MySQL
        $logs[] = "📡 Conectando a base de datos...";
        $db = getDBConnection();
        $logs[] = "✓ Conexión exitosa";
        
        // Leer archivo SQL
        $logs[] = "📄 Leyendo database.sql...";
        $sqlFile = __DIR__ . '/database.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception("❌ No se encontró database.sql");
        }
        
        $sql = file_get_contents($sqlFile);
        $logs[] = "✓ Archivo SQL cargado (" . strlen($sql) . " bytes)";
        
        // Limpiar SQL y dividir comandos
        $logs[] = "⚙️ Procesando comandos SQL...";
        
        // Remover comentarios y líneas vacías
        $sql = preg_replace('/^--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Dividir por punto y coma, pero mantener comandos completos
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && strlen(trim($stmt)) > 5;
            }
        );
        
        $successCount = 0;
        $errorCount = 0;
        
        // Primero: DROP de índices y vistas si existen
        $logs[] = "🧹 Limpiando estructuras previas...";
        $cleanupStatements = [
            "DROP VIEW IF EXISTS project_summary",
            "DROP TABLE IF EXISTS user_sessions",
            "DROP TABLE IF EXISTS notifications",
            "DROP TABLE IF EXISTS messages",
            "DROP TABLE IF EXISTS project_activities",
            "DROP TABLE IF EXISTS time_sessions",
            "DROP TABLE IF EXISTS project_phases",
            "DROP TABLE IF EXISTS projects",
            "DROP TABLE IF EXISTS clients",
            "DROP TABLE IF EXISTS admin_users"
        ];
        
        foreach ($cleanupStatements as $stmt) {
            try {
                $db->exec($stmt);
            } catch (PDOException $e) {
                // Ignorar errores de limpieza
            }
        }
        $logs[] = "✓ Limpieza completada";
        
        // Ejecutar cada comando SQL
        foreach ($statements as $index => $statement) {
            if (empty(trim($statement))) continue;
            
            try {
                $db->exec($statement);
                $successCount++;
                
                // Log para CREATE TABLE
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?`?(\w+)`?\s/i', $statement, $matches);
                    if (isset($matches[1])) {
                        $logs[] = "✓ Tabla creada: {$matches[1]}";
                    }
                }
            } catch (PDOException $e) {
                // Ignorar solo errores de "ya existe"
                if (strpos($e->getMessage(), 'already exists') !== false || 
                    strpos($e->getMessage(), 'Duplicate') !== false) {
                    continue;
                }
                $errorCount++;
                $logs[] = "⚠️ Error en comando " . ($index + 1) . ": " . substr($e->getMessage(), 0, 120);
            }
        }
        
        $logs[] = "✓ SQL ejecutado: $successCount comandos exitosos";
        if ($errorCount > 0) {
            $logs[] = "⚠️ Errores encontrados: $errorCount";
        }
        
        // Verificar tablas
        $logs[] = "🔍 Verificando tablas creadas...";
        $result = $db->query("SHOW TABLES");
        $tables = $result->fetchAll(PDO::FETCH_COLUMN);
        
        $expectedTables = [
            'admin_users', 'clients', 'projects', 'project_phases',
            'time_sessions', 'project_activities', 'messages',
            'notifications', 'user_sessions'
        ];
        
        $logs[] = "📊 Tablas encontradas: " . implode(', ', $tables);
        
        $missingTables = array_diff($expectedTables, $tables);
        
        if (empty($missingTables)) {
            $logs[] = "✓ Todas las tablas creadas: " . count($tables) . " tablas";
        } else {
            $logs[] = "⚠️ Faltan tablas: " . implode(', ', $missingTables);
        }
        
        // Verificar usuario admin
        $logs[] = "👤 Verificando usuario administrador...";
        
        // Verificar si la tabla admin_users existe antes de consultarla
        if (in_array('admin_users', $tables)) {
            $stmt = $db->query("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
            $adminExists = $stmt->fetchColumn() > 0;
        } else {
            $adminExists = false;
            $logs[] = "⚠️ Tabla admin_users no existe, se creará el usuario después";
        }
        
        if (!$adminExists) {
            $logs[] = "📝 Creando usuario admin...";
            $passwordHash = password_hash('Admin123!', PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO admin_users (username, email, password_hash, full_name, role, status)
                VALUES ('admin', 'admin@brodevlab.com', ?, 'Administrador Principal', 'super_admin', 'active')
            ");
            $stmt->execute([$passwordHash]);
            $logs[] = "✓ Usuario admin creado";
        } else {
            $logs[] = "✓ Usuario admin ya existe";
        }
        
        // Crear archivo de bloqueo
        file_put_contents($lockFile, date('Y-m-d H:i:s'));
        $logs[] = "🔒 Instalación bloqueada para seguridad";
        
        $message = "¡Instalación completada exitosamente! 🎉";
        $messageType = "success";
        
    } catch (PDOException $e) {
        $message = "Error de base de datos: " . $e->getMessage();
        $messageType = "error";
        $logs[] = "❌ ERROR: " . $e->getMessage();
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
        $logs[] = "❌ ERROR: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - BroDev Lab</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #7C3AED 0%, #EC4899 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .content {
            padding: 40px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #7C3AED;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        
        .info-box h3 {
            color: #7C3AED;
            margin-bottom: 10px;
        }
        
        .info-box ul {
            list-style: none;
            padding-left: 0;
        }
        
        .info-box li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }
        
        .info-box li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
        }
        
        .credentials {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .credentials h3 {
            color: #92400e;
            margin-bottom: 15px;
        }
        
        .credentials code {
            background: #fef3c7;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #92400e;
            font-weight: bold;
        }
        
        .btn-install {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #7C3AED 0%, #EC4899 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.4);
        }
        
        .btn-install:active {
            transform: translateY(0);
        }
        
        .message {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border: 2px solid #10b981;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 2px solid #ef4444;
        }
        
        .logs {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 20px;
        }
        
        .logs div {
            padding: 4px 0;
        }
        
        .next-steps {
            background: #e0e7ff;
            border-left: 4px solid #4f46e5;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .next-steps h3 {
            color: #4f46e5;
            margin-bottom: 15px;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn-secondary {
            flex: 1;
            padding: 12px;
            background: #6366f1;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn-secondary:hover {
            background: #4f46e5;
        }
        
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .warning strong {
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Instalador BroDev Lab</h1>
            <p>Configuración automática de base de datos</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($messageType === 'success'): ?>
                <div class="next-steps">
                    <h3>✅ ¡Instalación Completada!</h3>
                    <p><strong>Credenciales de Acceso:</strong></p>
                    <ul style="list-style: none; padding: 10px 0;">
                        <li>👤 Usuario: <code>admin</code></li>
                        <li>🔑 Password: <code>Admin123!</code></li>
                    </ul>
                    
                    <div class="btn-group">
                        <a href="admin/login.html" class="btn-secondary">🔐 Ir al Login Admin</a>
                        <a href="portal/" class="btn-secondary">👥 Portal de Clientes</a>
                    </div>
                    
                    <div class="warning" style="margin-top: 20px;">
                        <strong>⚠️ IMPORTANTE:</strong> Por seguridad, elimina este archivo <code>setup.php</code> después de la instalación.
                    </div>
                </div>
                
                <?php if (!empty($logs)): ?>
                    <div class="logs">
                        <?php foreach ($logs as $log): ?>
                            <div><?php echo htmlspecialchars($log); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="info-box">
                    <h3>📋 Lo que hará este instalador:</h3>
                    <ul>
                        <li>Crear todas las tablas necesarias</li>
                        <li>Configurar el usuario administrador</li>
                        <li>Preparar el sistema de autenticación</li>
                        <li>Configurar roles y permisos</li>
                    </ul>
                </div>
                
                <div class="credentials">
                    <h3>🔐 Credenciales que se crearán:</h3>
                    <p><strong>Usuario Admin:</strong> <code>admin</code></p>
                    <p><strong>Password:</strong> <code>Admin123!</code></p>
                    <p style="margin-top: 10px; font-size: 0.9rem; color: #92400e;">
                        ⚠️ Recuerda cambiar la contraseña después del primer login
                    </p>
                </div>
                
                <form method="POST">
                    <button type="submit" name="install" class="btn-install">
                        🚀 Instalar Base de Datos
                    </button>
                </form>
                
                <?php if (!empty($logs)): ?>
                    <div class="logs">
                        <?php foreach ($logs as $log): ?>
                            <div><?php echo htmlspecialchars($log); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
