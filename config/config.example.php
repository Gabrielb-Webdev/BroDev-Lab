<?php
/**
 * Configuración de Base de Datos - EJEMPLO
 * BroDev Lab - Client Portal
 * 
 * INSTRUCCIONES:
 * 1. Copia este archivo como config.php en la misma carpeta
 * 2. Actualiza los valores con tu configuración real
 * 3. NO subas config.php a Git (ya está en .gitignore)
 */

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================
define('DB_HOST', 'localhost');          // Host de MySQL (localhost en local, IP/dominio en servidor)
define('DB_NAME', 'brodevlab_portal');   // Nombre de tu base de datos
define('DB_USER', 'root');               // Usuario de MySQL (cambiar en producción)
define('DB_PASS', '');                   // Contraseña de MySQL (IMPORTANTE: cambiar en producción)
define('DB_CHARSET', 'utf8mb4');

// ============================================
// CONFIGURACIÓN DE LA APLICACIÓN
// ============================================
define('SITE_URL', 'http://localhost');  // URL base de tu sitio (cambiar en producción)
define('API_URL', SITE_URL . '/api');

// ============================================
// CONFIGURACIÓN DE EMAIL (OPCIONAL)
// ============================================
// Para notificaciones por email, configura un servicio SMTP
// Recomendado: SendGrid, Mailgun, o Gmail SMTP

define('SMTP_HOST', 'smtp.gmail.com');           // Servidor SMTP
define('SMTP_PORT', 587);                        // Puerto (587 para TLS, 465 para SSL)
define('SMTP_USER', 'tu-email@gmail.com');       // Usuario SMTP
define('SMTP_PASS', 'tu-password-de-app');       // Contraseña o App Password
define('SMTP_FROM_EMAIL', 'noreply@brodevlab.com');
define('SMTP_FROM_NAME', 'BroDev Lab');
define('SMTP_ENCRYPTION', 'tls');                // tls o ssl

// ============================================
// CONFIGURACIÓN DE SESIONES
// ============================================
define('SESSION_LIFETIME', 3600);                // Clientes: 1 hora (3600 segundos)
define('ADMIN_SESSION_LIFETIME', 7200);          // Admins: 2 horas (7200 segundos)

// ============================================
// CONFIGURACIÓN DE TIMEZONE
// ============================================
date_default_timezone_set('America/Mexico_City'); // Cambiar según tu zona horaria
// Otras opciones: America/New_York, America/Los_Angeles, Europe/Madrid, etc.

// ============================================
// MODO DEBUG (CAMBIAR A FALSE EN PRODUCCIÓN)
// ============================================
define('DEBUG_MODE', true);  // true = mostrar errores | false = ocultar errores

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// ============================================
// FUNCIÓN DE CONEXIÓN A BASE DE DATOS
// ============================================
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        } else {
            die("Error de conexión a la base de datos. Por favor contacta al administrador.");
        }
    }
}

// ============================================
// CONFIGURACIÓN DE CORS
// ============================================
function setCorsHeaders() {
    // Permitir acceso desde cualquier origen (cambiar en producción)
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Allow-Credentials: true');
    
    // Manejar preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// ============================================
// FUNCIONES AUXILIARES
// ============================================

/**
 * Generar código aleatorio para clientes
 */
function generateAccessCode($length = 12) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $code;
}

/**
 * Enviar respuesta JSON
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Sanitizar input
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Log de errores personalizado
 */
function logError($message, $context = []) {
    $logFile = __DIR__ . '/../logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] $message $contextStr\n";
    
    // Crear directorio de logs si no existe
    $logDir = dirname($logFile);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    error_log($logMessage, 3, $logFile);
}

// ============================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================

// Prevenir clickjacking
header('X-Frame-Options: SAMEORIGIN');

// Prevenir XSS
header('X-XSS-Protection: 1; mode=block');

// Prevenir MIME sniffing
header('X-Content-Type-Options: nosniff');

// Content Security Policy (opcional, ajustar según necesidades)
// header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");

?>
