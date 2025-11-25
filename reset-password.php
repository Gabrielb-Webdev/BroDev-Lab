<?php
/**
 * Reset Password - Cambiar contraseña del admin
 */

require_once 'config/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<style>
    body { font-family: Arial; padding: 20px; background: #1a1a2e; color: #fff; }
    .success { color: #10b981; }
    .error { color: #ef4444; }
    .box { background: #16213e; padding: 20px; margin: 10px 0; border-radius: 12px; border: 2px solid #7C3AED; }
    input { padding: 10px; border-radius: 8px; border: 2px solid #7C3AED; background: #0a0a0a; color: #fff; font-size: 16px; width: 300px; }
    button { padding: 12px 24px; background: linear-gradient(135deg, #7C3AED, #EC4899); border: none; border-radius: 8px; color: white; font-size: 16px; cursor: pointer; font-weight: bold; }
    button:hover { opacity: 0.9; }
    pre { background: #0a0a0a; padding: 10px; border-radius: 8px; }
</style>";

echo "<h1>🔐 Reset de Contraseña Admin</h1>";

try {
    $db = getDBConnection();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
        $username = $_POST['username'] ?? 'admin';
        $newPassword = $_POST['new_password'] ?? 'Admin123!';
        
        echo "<div class='box'>";
        echo "<h2>Cambiando contraseña...</h2>";
        
        // Verificar que el usuario existe
        $stmt = $db->prepare("SELECT id, username, email FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo "<p class='error'>✗ Usuario '$username' no encontrado</p>";
            echo "<p>Creando usuario admin...</p>";
            
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO admin_users (username, email, password_hash, full_name, role, status)
                VALUES (?, 'admin@brodevlab.com', ?, 'Administrador Principal', 'super_admin', 'active')
            ");
            $stmt->execute([$username, $passwordHash]);
            
            echo "<p class='success'>✓ Usuario creado exitosamente</p>";
        } else {
            echo "<p class='success'>✓ Usuario encontrado: {$user['username']} ({$user['email']})</p>";
            
            // Actualizar contraseña
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?");
            $stmt->execute([$passwordHash, $username]);
            
            echo "<p class='success'>✓ Contraseña actualizada</p>";
        }
        
        // Mostrar hash para verificar
        echo "<p><strong>Nueva contraseña:</strong> <code style='background: #0a0a0a; padding: 4px 8px; border-radius: 4px;'>$newPassword</code></p>";
        echo "<p><strong>Hash generado:</strong></p>";
        echo "<pre style='font-size: 10px; word-wrap: break-word;'>$passwordHash</pre>";
        
        // Probar la contraseña
        echo "<h3>🧪 Probando contraseña...</h3>";
        $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $storedHash = $stmt->fetchColumn();
        
        if (password_verify($newPassword, $storedHash)) {
            echo "<p class='success'>✓ Verificación exitosa: La contraseña funciona correctamente</p>";
        } else {
            echo "<p class='error'>✗ ERROR: La contraseña NO coincide con el hash</p>";
        }
        
        echo "</div>";
        
        echo "<div class='box'>";
        echo "<h2>✅ Proceso completado</h2>";
        echo "<p><strong>Ahora puedes iniciar sesión con:</strong></p>";
        echo "<p>Usuario: <code style='background: #0a0a0a; padding: 4px 8px; border-radius: 4px;'>$username</code></p>";
        echo "<p>Contraseña: <code style='background: #0a0a0a; padding: 4px 8px; border-radius: 4px;'>$newPassword</code></p>";
        echo "<p style='margin-top: 20px;'><a href='admin/login.php' style='display: inline-block; background: linear-gradient(135deg, #7C3AED, #EC4899); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Ir al Login →</a></p>";
        echo "</div>";
        
    } else {
        // Mostrar usuarios actuales
        echo "<div class='box'>";
        echo "<h2>📋 Usuarios Admin Actuales</h2>";
        
        $stmt = $db->query("SELECT id, username, email, role, status, created_at FROM admin_users");
        $users = $stmt->fetchAll();
        
        if (empty($users)) {
            echo "<p class='error'>⚠️ No hay usuarios en la base de datos</p>";
        } else {
            echo "<table style='width: 100%; border-collapse: collapse;'>";
            echo "<tr style='background: #0a0a0a;'><th style='padding: 10px; text-align: left;'>ID</th><th style='padding: 10px; text-align: left;'>Usuario</th><th style='padding: 10px; text-align: left;'>Email</th><th style='padding: 10px; text-align: left;'>Rol</th><th style='padding: 10px; text-align: left;'>Estado</th></tr>";
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
        echo "</div>";
        
        // Formulario
        echo "<div class='box'>";
        echo "<h2>🔄 Cambiar Contraseña</h2>";
        echo "<form method='POST'>";
        echo "<p><label>Usuario:</label><br><input type='text' name='username' value='admin' required></p>";
        echo "<p><label>Nueva Contraseña:</label><br><input type='text' name='new_password' value='Admin123!' required></p>";
        echo "<p><button type='submit' name='reset'>🔐 Cambiar Contraseña</button></p>";
        echo "</form>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='box'>";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr style='border: 1px solid #333; margin: 30px 0;'>";
echo "<p style='text-align: center; color: #666;'>Elimina este archivo (reset-password.php) después de usarlo por seguridad.</p>";
?>
