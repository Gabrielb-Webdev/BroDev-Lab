<?php
/**
 * Instalador Mejorado de Tasks v0.3
 * Ejecuta el SQL con todas las dependencias + datos de ejemplo
 */

require_once 'config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Usar el archivo SQL completo que incluye todas las dependencias Y datos de ejemplo
    $sqlFile = __DIR__ . '/database-complete-tasks-with-data.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception('Archivo database-complete-tasks-with-data.sql no encontrado');
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remover comentarios
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Dividir por ; pero solo al final de línea
    $statements = array_filter(
        array_map('trim', preg_split('/;\s*\n/s', $sql)),
        function($stmt) {
            return !empty($stmt) && strlen($stmt) > 10;
        }
    );
    
    $results = [];
    $successCount = 0;
    $errorCount = 0;
    
    // Ejecutar cada statement (CREATE TABLE e INSERT)
    foreach ($statements as $stmt) {
        $stmtUpper = strtoupper($stmt);
        
        // Saltar statements vacíos
        if (empty($stmt)) continue;
        
        try {
            // Ejecutar el statement
            $pdo->exec($stmt);
            
            // Determinar tipo y extraer info
            if (stripos($stmt, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $stmt, $matches);
                $tableName = $matches[1] ?? 'tabla';
                
                $results[] = [
                    'success' => true,
                    'message' => "✅ Tabla '$tableName' creada",
                    'type' => 'table'
                ];
                $successCount++;
                
            } elseif (stripos($stmt, 'INSERT') !== false) {
                // Extraer info del INSERT
                if (stripos($stmt, 'clients') !== false) {
                    $item = 'Cliente demo';
                } elseif (stripos($stmt, 'admins') !== false) {
                    $item = 'Admin demo';
                } elseif (stripos($stmt, 'projects') !== false) {
                    $item = 'Proyecto demo';
                } elseif (stripos($stmt, 'task_tags') !== false) {
                    $item = 'Etiquetas (8)';
                } elseif (stripos($stmt, 'task_tag_relations') !== false) {
                    $item = 'Relaciones de tags';
                } elseif (stripos($stmt, 'tasks') !== false) {
                    $item = 'Tareas demo (8)';
                } else {
                    $item = 'Datos';
                }
                
                $results[] = [
                    'success' => true,
                    'message' => "✅ $item insertado",
                    'type' => 'data'
                ];
                $successCount++;
            }
            
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            
            // Manejar errores comunes sin fallar
            if (strpos($errorMsg, 'already exists') !== false) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $stmt, $matches);
                $tableName = $matches[1] ?? 'tabla';
                $results[] = [
                    'success' => true,
                    'warning' => true,
                    'message' => "⚠️ Tabla '$tableName' ya existe",
                    'type' => 'warning'
                ];
                $successCount++;
                
            } elseif (strpos($errorMsg, 'Duplicate entry') !== false) {
                $results[] = [
                    'success' => true,
                    'warning' => true,
                    'message' => "⚠️ Datos ya existen, omitido",
                    'type' => 'warning'
                ];
                $successCount++;
                
            } else {
                // Error real
                $results[] = [
                    'success' => false,
                    'message' => "❌ " . $errorMsg,
                    'type' => 'error'
                ];
                $errorCount++;
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'summary' => [
            'total' => count($results),
            'success' => $successCount,
            'errors' => $errorCount
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
