<?php
/**
 * Instalador de base de datos para sistema de tareas
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config/config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['sql'])) {
        throw new Exception('No SQL statement provided');
    }
    
    $sql = trim($data['sql']);
    
    if (empty($sql)) {
        echo json_encode(['success' => true, 'message' => 'Empty statement skipped']);
        exit;
    }
    
    $db = getDBConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Statement executed successfully',
        'affected_rows' => $stmt->rowCount()
    ]);
    
} catch (PDOException $e) {
    $error = $e->getMessage();
    
    // No considerar como error si la tabla ya existe
    if (strpos($error, 'already exists') !== false) {
        echo json_encode([
            'success' => true,
            'error' => $error,
            'warning' => true
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $error
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
