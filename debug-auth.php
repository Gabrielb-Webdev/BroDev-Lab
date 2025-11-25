<?php
/**
 * Debug del API de autenticación
 * Ver errores detallados de auth.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<style>
    body { font-family: monospace; padding: 20px; background: #0a0a0a; color: #0f0; }
    .error { color: #f00; }
    .success { color: #0f0; }
    .warning { color: #ff0; }
    pre { background: #111; padding: 10px; border: 1px solid #333; }
</style>";

echo "<h1>🔍 Debug API auth.php</h1>";

echo "<h2>1. Verificando config.php</h2>";
if (file_exists('config/config.php')) {
    echo "<p class='success'>✓ config.php existe</p>";
    try {
        require_once 'config/config.php';
        echo "<p class='success'>✓ config.php cargado sin errores</p>";
        echo "<pre>DB_HOST: " . DB_HOST . "\nDB_NAME: " . DB_NAME . "\nDB_USER: " . DB_USER . "</pre>";
    } catch (Exception $e) {
        echo "<p class='error'>✗ Error al cargar config.php: " . $e->getMessage() . "</p>";
        exit;
    }
} else {
    echo "<p class='error'>✗ config.php NO existe</p>";
    exit;
}

echo "<h2>2. Probando conexión a BD</h2>";
try {
    $db = getDBConnection();
    echo "<p class='success'>✓ Conexión exitosa</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error de conexión: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>3. Verificando tabla user_sessions</h2>";
try {
    $check = $db->query("SHOW TABLES LIKE 'user_sessions'");
    if ($check->rowCount() > 0) {
        echo "<p class='success'>✓ Tabla user_sessions existe</p>";
    } else {
        echo "<p class='error'>✗ Tabla user_sessions NO existe</p>";
        echo "<p class='warning'>Necesitas ejecutar setup.php o cleanup.php</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Verificando tabla admin_users</h2>";
try {
    $check = $db->query("SHOW TABLES LIKE 'admin_users'");
    if ($check->rowCount() > 0) {
        echo "<p class='success'>✓ Tabla admin_users existe</p>";
        
        // Contar usuarios
        $stmt = $db->query("SELECT COUNT(*) as total FROM admin_users");
        $result = $stmt->fetch();
        echo "<p>Total de usuarios admin: {$result['total']}</p>";
        
        if ($result['total'] > 0) {
            $users = $db->query("SELECT username, email, role FROM admin_users")->fetchAll();
            echo "<pre>";
            foreach ($users as $u) {
                echo "Usuario: {$u['username']} | Email: {$u['email']} | Rol: {$u['role']}\n";
            }
            echo "</pre>";
        }
    } else {
        echo "<p class='error'>✗ Tabla admin_users NO existe</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Simulando llamada al API</h2>";
echo "<p>Iniciando sesión PHP...</p>";
session_start();
echo "<p class='success'>✓ Sesión iniciada</p>";

echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session token: " . ($_SESSION['session_token'] ?? 'No hay token') . "</p>";

echo "<h2>6. Probando función verifySession</h2>";
try {
    // Verificar que la tabla existe
    $tableCheck = $db->query("SHOW TABLES LIKE 'user_sessions'");
    if ($tableCheck->rowCount() === 0) {
        echo "<p class='warning'>⚠️ Tabla user_sessions no existe - API retornaría: authenticated=false</p>";
    } else {
        $sessionToken = $_SESSION['session_token'] ?? null;
        
        if (!$sessionToken) {
            echo "<p class='warning'>⚠️ No hay session_token - API retornaría: authenticated=false</p>";
        } else {
            echo "<p>Buscando sesión con token: $sessionToken</p>";
            $stmt = $db->prepare("SELECT user_id, user_type, expires_at FROM user_sessions WHERE session_token = ? AND expires_at > NOW()");
            $stmt->execute([$sessionToken]);
            $session = $stmt->fetch();
            
            if ($session) {
                echo "<p class='success'>✓ Sesión válida encontrada</p>";
                echo "<pre>" . print_r($session, true) . "</pre>";
            } else {
                echo "<p class='warning'>⚠️ No hay sesión válida - API retornaría: authenticated=false</p>";
            }
        }
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>7. Probando llamada real al API</h2>";
echo "<p>Haciendo petición GET a: /api/auth.php?action=verify</p>";

// Hacer una llamada interna al API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://' . $_SERVER['HTTP_HOST'] . '/api/auth.php?action=verify');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>Código HTTP: <strong>$httpCode</strong></p>";

if ($httpCode === 500) {
    echo "<p class='error'>✗ El API está devolviendo error 500</p>";
    echo "<h3>Respuesta del servidor:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else if ($httpCode === 200) {
    echo "<p class='success'>✓ El API responde correctamente</p>";
    echo "<h3>Respuesta:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
} else {
    echo "<p class='warning'>Código inesperado: $httpCode</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<hr>";
echo "<h2>🔧 Acciones recomendadas:</h2>";
echo "<ul>";
echo "<li><a href='cleanup.php' style='color: #0ff;'>Ejecutar cleanup.php</a> - Limpiar tablas duplicadas</li>";
echo "<li><a href='setup.php' style='color: #0ff;'>Ejecutar setup.php</a> - Reinstalar base de datos</li>";
echo "<li><a href='test-db.php' style='color: #0ff;'>Ver test-db.php</a> - Diagnóstico completo</li>";
echo "<li><a href='admin/login.php' style='color: #0ff;'>Ir al Login</a> - Intentar login</li>";
echo "</ul>";
?>
