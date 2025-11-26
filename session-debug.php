<?php
/**
 * Debug de Sesión - BroDev Lab
 * Verificar estado actual de la sesión
 */

// Configurar cookies
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;

if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
} else {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', $isSecure ? 1 : 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_lifetime', 86400);
}

session_start();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Sesión - BroDev Lab</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a2e;
            color: #eee;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #16213e;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        h1 {
            color: #00d4ff;
            margin-bottom: 20px;
            font-size: 2rem;
        }
        h2 {
            color: #00ff88;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        .section {
            background: #0f3460;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #00d4ff;
        }
        .key {
            color: #00ff88;
            font-weight: bold;
        }
        .value {
            color: #ffd93d;
            margin-left: 10px;
        }
        .row {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .row:last-child {
            border-bottom: none;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .status.success {
            background: #00ff88;
            color: #000;
        }
        .status.error {
            background: #ff6b6b;
            color: #fff;
        }
        .status.warning {
            background: #ffd93d;
            color: #000;
        }
        pre {
            background: #000;
            color: #0f0;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 0.9rem;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #00d4ff;
            color: #000;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 10px 5px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #00ff88;
            transform: translateY(-2px);
        }
        .btn-danger {
            background: #ff6b6b;
            color: #fff;
        }
        .btn-danger:hover {
            background: #ff4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug de Sesión - BroDev Lab</h1>
        
        <div class="section">
            <h2>📊 Estado de la Sesión PHP</h2>
            <?php
            $sessionStatus = session_status();
            $statusText = $sessionStatus === PHP_SESSION_ACTIVE ? 'ACTIVA' : 
                         ($sessionStatus === PHP_SESSION_NONE ? 'NINGUNA' : 'DESHABILITADA');
            $statusClass = $sessionStatus === PHP_SESSION_ACTIVE ? 'success' : 'error';
            ?>
            <div class="row">
                <span class="key">Estado:</span>
                <span class="status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
            </div>
            <div class="row">
                <span class="key">Session ID:</span>
                <span class="value"><?php echo session_id() ?: 'No hay sesión'; ?></span>
            </div>
            <div class="row">
                <span class="key">Session Name:</span>
                <span class="value"><?php echo session_name(); ?></span>
            </div>
        </div>

        <div class="section">
            <h2>🔐 Variables de Sesión ($_SESSION)</h2>
            <?php if (!empty($_SESSION)): ?>
                <?php foreach ($_SESSION as $key => $value): ?>
                    <div class="row">
                        <span class="key"><?php echo htmlspecialchars($key); ?>:</span>
                        <span class="value"><?php 
                            if (is_array($value) || is_object($value)) {
                                echo json_encode($value, JSON_PRETTY_PRINT);
                            } else {
                                echo htmlspecialchars($value);
                            }
                        ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="row">
                    <span class="status warning">No hay variables de sesión establecidas</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>🍪 Cookies</h2>
            <?php if (!empty($_COOKIE)): ?>
                <?php foreach ($_COOKIE as $key => $value): ?>
                    <div class="row">
                        <span class="key"><?php echo htmlspecialchars($key); ?>:</span>
                        <span class="value"><?php echo htmlspecialchars(substr($value, 0, 50)) . (strlen($value) > 50 ? '...' : ''); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="row">
                    <span class="status warning">No hay cookies</span>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>⚙️ Configuración de Cookies de Sesión</h2>
            <?php $cookieParams = session_get_cookie_params(); ?>
            <div class="row">
                <span class="key">Lifetime:</span>
                <span class="value"><?php echo $cookieParams['lifetime']; ?> segundos (<?php echo round($cookieParams['lifetime']/3600, 2); ?> horas)</span>
            </div>
            <div class="row">
                <span class="key">Path:</span>
                <span class="value"><?php echo $cookieParams['path']; ?></span>
            </div>
            <div class="row">
                <span class="key">Domain:</span>
                <span class="value"><?php echo $cookieParams['domain'] ?: '(default)'; ?></span>
            </div>
            <div class="row">
                <span class="key">Secure:</span>
                <span class="value"><?php echo $cookieParams['secure'] ? 'Sí' : 'No'; ?></span>
            </div>
            <div class="row">
                <span class="key">HTTPOnly:</span>
                <span class="value"><?php echo $cookieParams['httponly'] ? 'Sí' : 'No'; ?></span>
            </div>
            <div class="row">
                <span class="key">SameSite:</span>
                <span class="value"><?php echo $cookieParams['samesite'] ?? 'None'; ?></span>
            </div>
        </div>

        <?php
        // Verificar base de datos si hay sesión
        if (isset($_SESSION['session_token'])) {
            require_once 'config/config.php';
            try {
                $db = getDBConnection();
                $stmt = $db->prepare("
                    SELECT s.*, 
                           a.username, a.full_name, a.role, a.status as user_status
                    FROM user_sessions s
                    LEFT JOIN admin_users a ON s.user_id = a.id AND s.user_type = 'admin'
                    WHERE s.session_token = ?
                ");
                $stmt->execute([$_SESSION['session_token']]);
                $dbSession = $stmt->fetch();
                
                if ($dbSession) {
                    ?>
                    <div class="section">
                        <h2>💾 Sesión en Base de Datos</h2>
                        <div class="row">
                            <span class="key">User ID:</span>
                            <span class="value"><?php echo $dbSession['user_id']; ?></span>
                        </div>
                        <div class="row">
                            <span class="key">User Type:</span>
                            <span class="value"><?php echo $dbSession['user_type']; ?></span>
                        </div>
                        <div class="row">
                            <span class="key">Username:</span>
                            <span class="value"><?php echo $dbSession['username'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="row">
                            <span class="key">Full Name:</span>
                            <span class="value"><?php echo $dbSession['full_name'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="row">
                            <span class="key">Role:</span>
                            <span class="value"><?php echo $dbSession['role'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="row">
                            <span class="key">Expira:</span>
                            <span class="value"><?php echo $dbSession['expires_at']; ?></span>
                        </div>
                        <div class="row">
                            <span class="key">IP:</span>
                            <span class="value"><?php echo $dbSession['ip_address'] ?? 'N/A'; ?></span>
                        </div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="section">
                        <h2>💾 Sesión en Base de Datos</h2>
                        <div class="row">
                            <span class="status error">No se encontró la sesión en la base de datos</span>
                        </div>
                    </div>
                    <?php
                }
            } catch (PDOException $e) {
                ?>
                <div class="section">
                    <h2>💾 Sesión en Base de Datos</h2>
                    <div class="row">
                        <span class="status error">Error: <?php echo $e->getMessage(); ?></span>
                    </div>
                </div>
                <?php
            }
        }
        ?>

        <div class="section">
            <h2>🌐 Información del Servidor</h2>
            <div class="row">
                <span class="key">PHP Version:</span>
                <span class="value"><?php echo PHP_VERSION; ?></span>
            </div>
            <div class="row">
                <span class="key">Server:</span>
                <span class="value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
            </div>
            <div class="row">
                <span class="key">HTTPS:</span>
                <span class="value"><?php echo (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'Sí' : 'No'; ?></span>
            </div>
            <div class="row">
                <span class="key">URL Actual:</span>
                <span class="value"><?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></span>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="admin/login.php" class="btn">🔑 Ir al Login</a>
            <a href="admin/index.php" class="btn">🏠 Ir al Dashboard</a>
            <a href="?clear=1" class="btn btn-danger">🗑️ Limpiar Sesión</a>
        </div>
    </div>

    <?php
    // Opción para limpiar sesión
    if (isset($_GET['clear'])) {
        session_unset();
        session_destroy();
        echo '<script>alert("Sesión limpiada"); window.location.href = "session-debug.php";</script>';
    }
    ?>
</body>
</html>
