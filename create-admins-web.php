<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nuevos Admins - BroDev Lab</title>
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
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 2.5rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .step {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .step h3 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        
        .credentials {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
            margin-top: 20px;
        }
        
        .credentials h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        
        .user-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .user-box h4 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .credential-row {
            display: flex;
            margin: 5px 0;
            font-size: 0.95rem;
        }
        
        .credential-row strong {
            min-width: 100px;
            color: #666;
        }
        
        .credential-row span {
            color: #333;
            font-family: 'Courier New', monospace;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            color: #856404;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5568d3;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            color: #e83e8c;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Crear Nuevos Usuarios Admin</h1>
        <p class="subtitle">BroDev Lab - Sistema de Gestión de Clientes</p>
        
        <?php
        require_once 'config/config.php';
        
        $errors = [];
        $success = false;
        
        try {
            $db = getDBConnection();
            
            // Generar hashes de contraseña
            $gabrielPassword = 'Gabriel2024!';
            $lautaroPassword = 'Lautaro2024!';
            
            $gabrielHash = password_hash($gabrielPassword, PASSWORD_DEFAULT);
            $lautaroHash = password_hash($lautaroPassword, PASSWORD_DEFAULT);
            
            // Paso 1: Eliminar usuario admin existente
            echo '<div class="step">';
            echo '<h3>📋 Paso 1: Eliminar usuario anterior</h3>';
            try {
                $stmt = $db->prepare("DELETE FROM admin_users WHERE username = 'admin'");
                $stmt->execute();
                $rowCount = $stmt->rowCount();
                echo "<p>✅ Usuario 'admin' eliminado ($rowCount registro(s) afectado(s))</p>";
            } catch (PDOException $e) {
                echo "<p>⚠️ Advertencia: " . $e->getMessage() . "</p>";
            }
            echo '</div>';
            
            // Paso 2: Crear usuario Gabriel Bustos
            echo '<div class="step">';
            echo '<h3>👤 Paso 2: Crear usuario Gabriel Bustos</h3>';
            try {
                $stmt = $db->prepare("
                    INSERT INTO admin_users (username, email, password_hash, full_name, role, status) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    'gabriel',
                    'gabriel@brodevlab.com',
                    $gabrielHash,
                    'Gabriel Bustos',
                    'super_admin',
                    'active'
                ]);
                echo '<p>✅ Usuario <strong>Gabriel Bustos</strong> creado exitosamente</p>';
                echo '<ul>';
                echo '<li>Username: <code>gabriel</code></li>';
                echo '<li>Email: <code>gabriel@brodevlab.com</code></li>';
                echo '<li>Role: <code>super_admin</code></li>';
                echo '</ul>';
            } catch (PDOException $e) {
                echo "<p class='error'>❌ Error al crear usuario Gabriel: " . $e->getMessage() . "</p>";
                $errors[] = $e->getMessage();
            }
            echo '</div>';
            
            // Paso 3: Crear usuario Lautaro Magliano
            echo '<div class="step">';
            echo '<h3>👤 Paso 3: Crear usuario Lautaro Magliano</h3>';
            try {
                $stmt = $db->prepare("
                    INSERT INTO admin_users (username, email, password_hash, full_name, role, status) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    'lautaro',
                    'lautaro@brodevlab.com',
                    $lautaroHash,
                    'Lautaro Magliano',
                    'super_admin',
                    'active'
                ]);
                echo '<p>✅ Usuario <strong>Lautaro Magliano</strong> creado exitosamente</p>';
                echo '<ul>';
                echo '<li>Username: <code>lautaro</code></li>';
                echo '<li>Email: <code>lautaro@brodevlab.com</code></li>';
                echo '<li>Role: <code>super_admin</code></li>';
                echo '</ul>';
            } catch (PDOException $e) {
                echo "<p class='error'>❌ Error al crear usuario Lautaro: " . $e->getMessage() . "</p>";
                $errors[] = $e->getMessage();
            }
            echo '</div>';
            
            // Paso 4: Verificar usuarios
            echo '<div class="step success">';
            echo '<h3>✅ Paso 4: Verificación de usuarios</h3>';
            $stmt = $db->query("SELECT id, username, email, full_name, role, status, created_at FROM admin_users ORDER BY id");
            $users = $stmt->fetchAll();
            
            if (count($users) > 0) {
                echo '<table>';
                echo '<thead>';
                echo '<tr><th>ID</th><th>Username</th><th>Email</th><th>Nombre Completo</th><th>Role</th><th>Estado</th></tr>';
                echo '</thead>';
                echo '<tbody>';
                foreach ($users as $user) {
                    echo '<tr>';
                    echo '<td>' . $user['id'] . '</td>';
                    echo '<td><code>' . htmlspecialchars($user['username']) . '</code></td>';
                    echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['full_name']) . '</td>';
                    echo '<td><strong>' . $user['role'] . '</strong></td>';
                    echo '<td>' . $user['status'] . '</td>';
                    echo '</tr>';
                }
                echo '</tbody>';
                echo '</table>';
            }
            echo '</div>';
            
            if (empty($errors)) {
                $success = true;
            }
            
        } catch (PDOException $e) {
            echo '<div class="step error">';
            echo '<h3>❌ Error de Conexión</h3>';
            echo '<p>' . $e->getMessage() . '</p>';
            echo '</div>';
        }
        
        if ($success) {
            ?>
            <div class="credentials">
                <h3>🔑 Credenciales de Acceso</h3>
                <p style="margin-bottom: 20px;">Los usuarios han sido creados exitosamente. Guarda estas credenciales en un lugar seguro.</p>
                
                <div class="user-box">
                    <h4>👤 Usuario 1: Gabriel Bustos</h4>
                    <div class="credential-row">
                        <strong>URL:</strong>
                        <span><?php echo SITE_URL; ?>/admin/login.php</span>
                    </div>
                    <div class="credential-row">
                        <strong>Username:</strong>
                        <span>gabriel</span>
                    </div>
                    <div class="credential-row">
                        <strong>Email:</strong>
                        <span>gabriel@brodevlab.com</span>
                    </div>
                    <div class="credential-row">
                        <strong>Password:</strong>
                        <span>Gabriel2024!</span>
                    </div>
                    <div class="credential-row">
                        <strong>Role:</strong>
                        <span>super_admin</span>
                    </div>
                </div>
                
                <div class="user-box">
                    <h4>👤 Usuario 2: Lautaro Magliano</h4>
                    <div class="credential-row">
                        <strong>URL:</strong>
                        <span><?php echo SITE_URL; ?>/admin/login.php</span>
                    </div>
                    <div class="credential-row">
                        <strong>Username:</strong>
                        <span>lautaro</span>
                    </div>
                    <div class="credential-row">
                        <strong>Email:</strong>
                        <span>lautaro@brodevlab.com</span>
                    </div>
                    <div class="credential-row">
                        <strong>Password:</strong>
                        <span>Lautaro2024!</span>
                    </div>
                    <div class="credential-row">
                        <strong>Role:</strong>
                        <span>super_admin</span>
                    </div>
                </div>
            </div>
            
            <div class="warning">
                <strong>⚠️ IMPORTANTE:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>Guarda estas credenciales en un gestor de contraseñas seguro</li>
                    <li>Considera cambiar las contraseñas después del primer inicio de sesión</li>
                    <li>Este archivo puede ser eliminado después de guardar las credenciales</li>
                </ul>
            </div>
            
            <a href="admin/login.php" class="btn">🚀 Ir al Panel de Login</a>
            <?php
        }
        ?>
    </div>
</body>
</html>
