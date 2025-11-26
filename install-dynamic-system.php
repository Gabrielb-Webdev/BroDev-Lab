<?php
/**
 * Ejecutor de SQL para instalación del sistema dinámico
 */

require_once 'config/config.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['sql']) || empty($data['sql'])) {
        throw new Exception('SQL statement es requerido');
    }
    
    $sql = trim($data['sql']);
    
    if (empty($sql)) {
        echo json_encode(['success' => true, 'message' => 'Statement vacío, omitiendo']);
        exit;
    }
    
    $pdo = getDBConnection();
    
    // Ejecutar statement
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Statement ejecutado correctamente'
    ]);
    
} catch (PDOException $e) {
    // Algunos errores son esperados (tabla ya existe, etc.)
    $errorMessage = $e->getMessage();
    
    echo json_encode([
        'success' => false,
        'error' => $errorMessage
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
