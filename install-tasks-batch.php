<?php
/**
 * Instalador Mejorado de Tasks
 * Ejecuta el SQL directamente sin dividir por cliente
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
    
    // Leer archivo SQL de estructura
    $sqlFile = __DIR__ . '/database-tasks-simple.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception('Archivo database-tasks-simple.sql no encontrado');
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
    
    // Ejecutar cada CREATE TABLE
    foreach ($statements as $stmt) {
        if (stripos($stmt, 'CREATE TABLE') === false) continue;
        
        try {
            $pdo->exec($stmt);
            
            // Extraer nombre de tabla
            preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $stmt, $matches);
            $tableName = $matches[1] ?? 'tabla';
            
            $results[] = [
                'success' => true,
                'message' => "Tabla '$tableName' creada",
                'table' => $tableName
            ];
            $successCount++;
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $stmt, $matches);
                $tableName = $matches[1] ?? 'tabla';
                $results[] = [
                    'success' => true,
                    'warning' => true,
                    'message' => "Tabla '$tableName' ya existe",
                    'table' => $tableName
                ];
                $successCount++;
            } else {
                $results[] = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
                $errorCount++;
            }
        }
    }
    
    // Ahora insertar datos de ejemplo
    $dataFile = __DIR__ . '/database-tasks-data.sql';
    if (file_exists($dataFile)) {
        $sqlData = file_get_contents($dataFile);
        $sqlData = preg_replace('/--.*$/m', '', $sqlData);
        
        $dataStatements = array_filter(
            array_map('trim', preg_split('/;\s*\n/s', $sqlData)),
            function($stmt) {
                return !empty($stmt) && strlen($stmt) > 10;
            }
        );
        
        foreach ($dataStatements as $stmt) {
            if (stripos($stmt, 'INSERT') === false) continue;
            
            try {
                $pdo->exec($stmt);
                
                // Extraer título de tarea
                preg_match("/'([^']+)'/", $stmt, $matches);
                $title = $matches[1] ?? 'registro';
                
                $results[] = [
                    'success' => true,
                    'message' => "Insertado: $title"
                ];
                $successCount++;
                
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    $results[] = [
                        'success' => true,
                        'warning' => true,
                        'message' => "Registro ya existe, omitido"
                    ];
                } else {
                    $results[] = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                    $errorCount++;
                }
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
