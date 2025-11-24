<?php
/**
 * Configuración de Base de Datos
 * BroDev Lab - Client Portal
 */

// Configuración de Base de Datos - HOSTINGER
define('DB_HOST', 'localhost');
define('DB_NAME', 'u851317150_brodevlab');
define('DB_USER', 'u851317150_brodevlab');
define('DB_PASS', 'Lg030920.');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la Aplicación
define('SITE_URL', 'https://grey-squirrel-133805.hostingersite.com');
define('API_URL', SITE_URL . '/api');

// Configuración de Email (SendGrid/SMTP)
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_USER', 'apikey');
define('SMTP_PASS', 'TU_API_KEY_AQUI'); // Reemplazar con tu API key
define('SMTP_FROM_EMAIL', 'noreply@brodevlab.com');
define('SMTP_FROM_NAME', 'BroDev Lab');

// Configuración de Sesiones
define('SESSION_LIFETIME', 3600); // 1 hora
define('ADMIN_SESSION_LIFETIME', 7200); // 2 horas

// Configuración de Timezone
date_default_timezone_set('America/Mexico_City'); // Ajustar a tu zona horaria

// Mostrar errores (cambiar a false en producción)
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Función para conectar a la base de datos
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Error de conexión: " . $e->getMessage());
        } else {
            die("Error de conexión a la base de datos");
        }
    }
}

// Headers CORS para API
function setCorsHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json; charset=UTF-8');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Función para generar código de acceso único
function generateAccessCode($length = 12) {
    return strtoupper(bin2hex(random_bytes($length / 2)));
}

// Función para formatear tiempo en segundos a formato legible
function formatTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    return sprintf('%02dh %02dm %02ds', $hours, $minutes, $secs);
}

// Función para enviar respuesta JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}
?>
