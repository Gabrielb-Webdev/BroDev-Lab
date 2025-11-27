<?php
/**
 * Debug Avanzado de tasks.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Debug de tasks.php</h1>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}pre{background:#fff;padding:15px;border-left:4px solid #7c3aed;overflow-x:auto;}</style>";

echo "<h2>Test 1: Incluir config.php</h2>";
try {
    require_once '../config/config.php';
    echo "✅ config.php incluido correctamente<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>Test 2: Conectar a BD</h2>";
try {
    $db = getDBConnection();
    echo "✅ Conexión exitosa<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>Test 3: Query básico</h2>";
try {
    $query = "SELECT COUNT(*) as total FROM tasks";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Total tareas: " . $result['total'] . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    die();
}

echo "<h2>Test 4: Query completo (como en tasks.php)</h2>";
try {
    $query = "
        SELECT 
            t.id,
            t.title,
            t.description,
            t.status,
            t.priority,
            t.project_id,
            t.assignee_id,
            t.due_date,
            t.created_at,
            t.updated_at,
            p.project_name,
            p.status as project_status,
            u.username as assignee_name,
            u.full_name as assignee_full_name
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
        LEFT JOIN admins u ON t.assignee_id = u.id
        ORDER BY t.created_at DESC
        LIMIT 2
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Query ejecutado correctamente<br>";
    echo "Tareas encontradas: " . count($tasks) . "<br><br>";
    echo "<pre>" . print_r($tasks, true) . "</pre>";
    
} catch (PDOException $e) {
    echo "❌ Error en query: " . $e->getMessage() . "<br>";
    echo "SQLSTATE: " . $e->getCode() . "<br>";
    if ($e->errorInfo) {
        echo "Error Info: <pre>" . print_r($e->errorInfo, true) . "</pre>";
    }
    die();
}

echo "<h2>Test 5: JSON encode</h2>";
try {
    $data = [
        'success' => true,
        'data' => $tasks,
        'total' => count($tasks)
    ];
    
    $json = json_encode($data);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON Error: ' . json_last_error_msg());
    }
    
    echo "✅ JSON codificado correctamente<br>";
    echo "Tamaño: " . strlen($json) . " bytes<br><br>";
    echo "<pre>" . htmlspecialchars(substr($json, 0, 500)) . "...</pre>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h2>Test 6: Simular respuesta API</h2>";
echo "<p>Abriendo en nueva pestaña...</p>";
echo "<script>
setTimeout(function() {
    window.open('/api/test-tasks.php', '_blank');
}, 1000);
</script>";

echo "<hr>";
echo "<h2>✅ Todos los tests pasaron</h2>";
echo "<p>El problema puede estar en:</p>";
echo "<ul>";
echo "<li>Headers duplicados en tasks.php</li>";
echo "<li>Función sendJsonResponse con problemas</li>";
echo "<li>Auth middleware interfiriendo</li>";
echo "</ul>";

echo "<p><strong>Solución:</strong> Usar <a href='/api/tasks-simple.php?action=by-status'>tasks-simple.php</a> que sabemos que funciona.</p>";
?>
