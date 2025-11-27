<?php
/**
 * Test simple del API de tareas
 */

// Habilitar errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once '../config/config.php';
    
    $db = getDBConnection();
    
    $query = "SELECT COUNT(*) as total FROM tasks";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa',
        'total_tasks' => $result['total'],
        'database' => DB_NAME
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
