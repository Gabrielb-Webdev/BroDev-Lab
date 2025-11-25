<?php
/**
 * Script de Instalación de Base de Datos
 * BroDev Lab - Client Portal
 * 
 * Este script crea la base de datos, tablas y datos iniciales
 */

// Configuración
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = ''; // Cambia esto por tu contraseña de MySQL
$DB_NAME = 'brodevlab_portal';

// Colores para terminal
$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$RESET = "\033[0m";

echo "╔══════════════════════════════════════════╗\n";
echo "║   BroDev Lab - Instalación de BD       ║\n";
echo "╚══════════════════════════════════════════╝\n\n";

try {
    // Conectar a MySQL sin seleccionar base de datos
    echo "→ Conectando a MySQL...\n";
    $pdo = new PDO("mysql:host=$DB_HOST", $DB_USER, $DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "{$GREEN}✓ Conexión exitosa{$RESET}\n\n";
    
    // Crear base de datos si no existe
    echo "→ Creando base de datos '$DB_NAME'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "{$GREEN}✓ Base de datos creada{$RESET}\n\n";
    
    // Conectar a la base de datos creada
    $pdo->exec("USE $DB_NAME");
    
    // Leer y ejecutar el archivo SQL
    echo "→ Ejecutando script SQL...\n";
    $sqlFile = __DIR__ . '/database.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("No se encontró el archivo database.sql");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir por comandos (separados por ;)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   $stmt !== '';
        }
    );
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        try {
            if (trim($statement)) {
                $pdo->exec($statement);
                $successCount++;
            }
        } catch (PDOException $e) {
            // Ignorar errores de "ya existe"
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "{$YELLOW}⚠ Advertencia: {$e->getMessage()}{$RESET}\n";
                $errorCount++;
            }
        }
    }
    
    echo "{$GREEN}✓ SQL ejecutado: $successCount comandos exitosos{$RESET}\n";
    if ($errorCount > 0) {
        echo "{$YELLOW}⚠ Advertencias: $errorCount{$RESET}\n";
    }
    echo "\n";
    
    // Verificar que las tablas se crearon
    echo "→ Verificando tablas creadas...\n";
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    $expectedTables = [
        'admin_users',
        'clients',
        'projects',
        'project_phases',
        'time_sessions',
        'project_activities',
        'messages',
        'notifications',
        'user_sessions'
    ];
    
    $missingTables = array_diff($expectedTables, $tables);
    
    if (empty($missingTables)) {
        echo "{$GREEN}✓ Todas las tablas fueron creadas correctamente{$RESET}\n";
        echo "  Tablas creadas: " . count($tables) . "\n\n";
    } else {
        echo "{$RED}✗ Faltan algunas tablas:{$RESET}\n";
        foreach ($missingTables as $table) {
            echo "  - $table\n";
        }
        echo "\n";
    }
    
    // Verificar usuario admin
    echo "→ Verificando usuario administrador...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE username = 'admin'");
    $adminExists = $stmt->fetchColumn() > 0;
    
    if ($adminExists) {
        echo "{$GREEN}✓ Usuario admin creado correctamente{$RESET}\n";
    } else {
        echo "{$YELLOW}⚠ Usuario admin no encontrado, creando...{$RESET}\n";
        
        // Crear usuario admin manualmente
        $passwordHash = password_hash('Admin123!', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO admin_users (username, email, password_hash, full_name, role, status)
            VALUES ('admin', 'admin@brodevlab.com', ?, 'Administrador Principal', 'super_admin', 'active')
        ");
        $stmt->execute([$passwordHash]);
        
        echo "{$GREEN}✓ Usuario admin creado{$RESET}\n";
    }
    
    echo "\n";
    echo "╔══════════════════════════════════════════╗\n";
    echo "║        ¡INSTALACIÓN COMPLETADA!         ║\n";
    echo "╚══════════════════════════════════════════╝\n\n";
    
    echo "📋 CREDENCIALES DE ACCESO:\n";
    echo "   Usuario: admin\n";
    echo "   Password: Admin123!\n";
    echo "   URL Admin: http://localhost/admin/login.php\n\n";
    
    echo "⚠️  IMPORTANTE:\n";
    echo "   1. Actualiza config/config.php con tus credenciales de BD\n";
    echo "   2. Cambia la contraseña del admin en producción\n";
    echo "   3. Configura el email SMTP si deseas notificaciones\n\n";
    
    // Actualizar config.php automáticamente
    echo "→ ¿Deseas actualizar config/config.php automáticamente? (s/n): ";
    $handle = fopen("php://stdin", "r");
    $response = trim(fgets($handle));
    
    if (strtolower($response) === 's' || strtolower($response) === 'y') {
        updateConfigFile($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS);
    }
    
    echo "\n{$GREEN}✓ Todo listo para usar el sistema!{$RESET}\n\n";
    
} catch (PDOException $e) {
    echo "{$RED}✗ Error de base de datos: {$e->getMessage()}{$RESET}\n";
    exit(1);
} catch (Exception $e) {
    echo "{$RED}✗ Error: {$e->getMessage()}{$RESET}\n";
    exit(1);
}

function updateConfigFile($host, $dbname, $user, $pass) {
    $configFile = __DIR__ . '/config/config.php';
    
    if (!file_exists($configFile)) {
        echo "{$RED}✗ No se encontró config/config.php{$RESET}\n";
        return;
    }
    
    $content = file_get_contents($configFile);
    
    // Actualizar valores
    $content = preg_replace(
        "/define\('DB_HOST',\s*'[^']*'\);/",
        "define('DB_HOST', '$host');",
        $content
    );
    
    $content = preg_replace(
        "/define\('DB_NAME',\s*'[^']*'\);/",
        "define('DB_NAME', '$dbname');",
        $content
    );
    
    $content = preg_replace(
        "/define\('DB_USER',\s*'[^']*'\);/",
        "define('DB_USER', '$user');",
        $content
    );
    
    $content = preg_replace(
        "/define\('DB_PASS',\s*'[^']*'\);/",
        "define('DB_PASS', '$pass');",
        $content
    );
    
    if (file_put_contents($configFile, $content)) {
        global $GREEN, $RESET;
        echo "{$GREEN}✓ config/config.php actualizado{$RESET}\n";
    } else {
        global $RED, $RESET;
        echo "{$RED}✗ Error al actualizar config/config.php{$RESET}\n";
    }
}
?>
