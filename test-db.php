<?php
/**
 * Test de Conexión a Base de Datos
 * Este archivo verifica la conexión y muestra información de diagnóstico
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Diagnóstico de Base de Datos - BroDev Lab</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #1a1a2e; color: #fff; }
    .success { color: #10b981; }
    .error { color: #ef4444; }
    .warning { color: #f59e0b; }
    .box { background: #16213e; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #7C3AED; }
    pre { background: #0f172a; padding: 10px; border-radius: 4px; overflow-x: auto; }
</style>";

echo "<div class='box'>";
echo "<h2>1️⃣ Verificando archivo de configuración...</h2>";

if (file_exists('config/config.php')) {
    echo "<p class='success'>✓ config.php existe</p>";
    require_once 'config/config.php';
    
    echo "<p>Base de datos: <strong>" . DB_NAME . "</strong></p>";
    echo "<p>Usuario: <strong>" . DB_USER . "</strong></p>";
    echo "<p>Host: <strong>" . DB_HOST . "</strong></p>";
} else {
    echo "<p class='error'>✗ config.php NO existe</p>";
    die("</div>");
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>2️⃣ Probando conexión a MySQL...</h2>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "<p class='success'>✓ Conexión exitosa a MySQL</p>";
} catch (PDOException $e) {
    echo "<p class='error'>✗ Error de conexión: " . htmlspecialchars($e->getMessage()) . "</p>";
    die("</div>");
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>3️⃣ Verificando tablas...</h2>";

try {
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<p class='warning'>⚠️ No hay tablas en la base de datos</p>";
        echo "<p><strong>Acción requerida:</strong> Ejecuta el instalador en <a href='setup.php' style='color: #7C3AED;'>setup.php</a></p>";
    } else {
        echo "<p class='success'>✓ Tablas encontradas: " . count($tables) . "</p>";
        echo "<pre>" . implode("\n", $tables) . "</pre>";
        
        $expectedTables = ['admin_users', 'clients', 'projects', 'project_phases', 'time_sessions', 'user_sessions'];
        $missing = array_diff($expectedTables, $tables);
        
        if (!empty($missing)) {
            echo "<p class='warning'>⚠️ Faltan tablas: " . implode(', ', $missing) . "</p>";
            echo "<p><strong>Acción requerida:</strong> Ejecuta el instalador en <a href='setup.php' style='color: #7C3AED;'>setup.php</a></p>";
        }
    }
} catch (PDOException $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>4️⃣ Verificando usuarios admin...</h2>";

try {
    if (in_array('admin_users', $tables)) {
        $stmt = $pdo->query("SELECT id, username, email, role, status, created_at FROM admin_users");
        $users = $stmt->fetchAll();
        
        if (empty($users)) {
            echo "<p class='warning'>⚠️ No hay usuarios admin</p>";
            echo "<p><strong>Acción requerida:</strong> Ejecuta el instalador en <a href='setup.php' style='color: #7C3AED;'>setup.php</a></p>";
        } else {
            echo "<p class='success'>✓ Usuarios admin encontrados: " . count($users) . "</p>";
            echo "<table style='width: 100%; border-collapse: collapse;'>";
            echo "<tr style='background: #0f172a;'><th style='padding: 10px; text-align: left;'>ID</th><th style='padding: 10px; text-align: left;'>Usuario</th><th style='padding: 10px; text-align: left;'>Email</th><th style='padding: 10px; text-align: left;'>Rol</th><th style='padding: 10px; text-align: left;'>Estado</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td style='padding: 10px;'>{$user['id']}</td>";
                echo "<td style='padding: 10px;'><strong>{$user['username']}</strong></td>";
                echo "<td style='padding: 10px;'>{$user['email']}</td>";
                echo "<td style='padding: 10px;'><span style='background: #7C3AED; padding: 4px 8px; border-radius: 4px;'>{$user['role']}</span></td>";
                echo "<td style='padding: 10px;'>{$user['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p class='error'>✗ Tabla admin_users no existe</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

echo "<div class='box'>";
echo "<h2>5️⃣ Verificando errores de PHP...</h2>";
echo "<p>Versión PHP: <strong>" . phpversion() . "</strong></p>";
echo "<p>PDO disponible: <strong>" . (extension_loaded('pdo') ? '✓ Sí' : '✗ No') . "</strong></p>";
echo "<p>PDO MySQL disponible: <strong>" . (extension_loaded('pdo_mysql') ? '✓ Sí' : '✗ No') . "</strong></p>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>✅ Resumen</h2>";
if (!empty($tables) && in_array('admin_users', $tables) && !empty($users)) {
    echo "<p class='success' style='font-size: 1.2em;'>🎉 Todo está configurado correctamente</p>";
    echo "<p><a href='admin/login.html' style='display: inline-block; background: linear-gradient(135deg, #7C3AED, #EC4899); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Ir al Login Admin →</a></p>";
    echo "<p style='margin-top: 20px;'><strong>Credenciales por defecto:</strong></p>";
    echo "<p>Usuario: <code style='background: #0f172a; padding: 4px 8px; border-radius: 4px;'>admin</code></p>";
    echo "<p>Contraseña: <code style='background: #0f172a; padding: 4px 8px; border-radius: 4px;'>Admin123!</code></p>";
} else {
    echo "<p class='warning' style='font-size: 1.2em;'>⚠️ La base de datos necesita ser instalada</p>";
    echo "<p><a href='setup.php' style='display: inline-block; background: linear-gradient(135deg, #7C3AED, #EC4899); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Ejecutar Instalador →</a></p>";
}
echo "</div>";

echo "<hr style='border: 1px solid #333; margin: 30px 0;'>";
echo "<p style='text-align: center; color: #666;'>Para eliminar este archivo de diagnóstico después de verificar, borra test-db.php</p>";
?>
