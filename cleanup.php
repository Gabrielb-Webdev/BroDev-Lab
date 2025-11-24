<?php
/**
 * Script de limpieza - Eliminar tabla duplicada 'admins'
 * Mantiene solo 'admin_users'
 */

require_once 'config/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<style>
    body { font-family: Arial; padding: 20px; background: #1a1a2e; color: #fff; }
    .success { color: #10b981; }
    .error { color: #ef4444; }
    .box { background: #16213e; padding: 15px; margin: 10px 0; border-radius: 8px; }
</style>";

echo "<h1>🧹 Limpieza de Base de Datos</h1>";

try {
    $db = getDBConnection();
    
    echo "<div class='box'>";
    echo "<h2>Verificando tablas...</h2>";
    
    $result = $db->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Tablas encontradas: " . implode(', ', $tables) . "</p>";
    
    if (in_array('admins', $tables) && in_array('admin_users', $tables)) {
        echo "<p class='error'>⚠️ Tienes dos tablas duplicadas: 'admins' y 'admin_users'</p>";
        echo "<p>Eliminando tabla 'admins'...</p>";
        
        $db->exec("DROP TABLE IF EXISTS admins");
        
        echo "<p class='success'>✓ Tabla 'admins' eliminada</p>";
        echo "<p class='success'>✓ Se mantiene 'admin_users' (tabla correcta)</p>";
    } else if (in_array('admin_users', $tables)) {
        echo "<p class='success'>✓ Solo existe 'admin_users' (correcto)</p>";
    } else if (in_array('admins', $tables)) {
        echo "<p class='error'>⚠️ Solo existe 'admins', debería ser 'admin_users'</p>";
        echo "<p>Renombrando tabla...</p>";
        
        $db->exec("RENAME TABLE admins TO admin_users");
        
        echo "<p class='success'>✓ Tabla renombrada a 'admin_users'</p>";
    }
    
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>Verificando usuarios...</h2>";
    
    $stmt = $db->query("SELECT username, email, role, status FROM admin_users");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p class='error'>⚠️ No hay usuarios en admin_users</p>";
        echo "<p>Creando usuario admin por defecto...</p>";
        
        $passwordHash = password_hash('Admin123!', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO admin_users (username, email, password_hash, full_name, role, status)
            VALUES ('admin', 'admin@brodevlab.com', ?, 'Administrador Principal', 'super_admin', 'active')
        ");
        $stmt->execute([$passwordHash]);
        
        echo "<p class='success'>✓ Usuario admin creado</p>";
        echo "<p><strong>Usuario:</strong> admin</p>";
        echo "<p><strong>Contraseña:</strong> Admin123!</p>";
    } else {
        echo "<p class='success'>✓ Usuarios encontrados: " . count($users) . "</p>";
        foreach ($users as $user) {
            echo "<p>• {$user['username']} ({$user['email']}) - Rol: {$user['role']}</p>";
        }
    }
    
    echo "</div>";
    
    echo "<div class='box'>";
    echo "<h2>✅ Limpieza completada</h2>";
    echo "<p><a href='admin/login.php' style='display: inline-block; background: linear-gradient(135deg, #7C3AED, #EC4899); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Ir al Login →</a></p>";
    echo "<p style='margin-top: 20px; color: #666;'>Puedes eliminar este archivo cleanup.php después de usarlo.</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='box'>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
