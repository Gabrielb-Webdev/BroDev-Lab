<?php
/**
 * Debug de Sesión - Ver estado completo de la sesión PHP
 */

// Configurar cookies igual que auth.php
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

session_start();

header('Content-Type: application/json');

$response = [
    'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'NONE',
    'session_id' => session_id(),
    'session_name' => session_name(),
    'session_data' => $_SESSION,
    'cookies' => $_COOKIE,
    'has_session_cookie' => isset($_COOKIE[session_name()]),
    'cookie_params' => session_get_cookie_params(),
    'php_version' => PHP_VERSION,
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
