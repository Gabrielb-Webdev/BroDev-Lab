<?php
/**
 * API de tareas SIMPLIFICADO - Sin Auth (solo para debug)
 * v0.5
 */

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Headers CORS primero
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Manejar OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Incluir config
    require_once '../config/config.php';
    
    $db = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    // Solo manejar GET por ahora
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }
    
    // Ejecutar acción
    if ($action === 'by-status') {
        getTasksByStatus($db);
    } else {
        getAllTasks($db);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    exit;
}

function getAllTasks($db) {
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
            ORDER BY 
                CASE t.priority
                    WHEN 'urgent' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'normal' THEN 3
                    WHEN 'low' THEN 4
                END,
                t.created_at DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $tasks,
            'total' => count($tasks)
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error en query: ' . $e->getMessage(),
            'sql_error' => $e->errorInfo
        ]);
    }
}

function getTasksByStatus($db) {
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
            ORDER BY t.status, t.created_at DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agrupar por estado
        $grouped = [
            'todo' => [],
            'in_progress' => [],
            'review' => [],
            'done' => []
        ];
        
        foreach ($tasks as $task) {
            $status = $task['status'] ?? 'todo';
            if (isset($grouped[$status])) {
                $grouped[$status][] = $task;
            }
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $grouped,
            'total' => count($tasks)
        ], JSON_PRETTY_PRINT);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error en query: ' . $e->getMessage(),
            'sql_state' => $e->errorInfo[0] ?? 'unknown',
            'sql_code' => $e->errorInfo[1] ?? 0,
            'sql_message' => $e->errorInfo[2] ?? 'Sin detalles'
        ]);
    }
}
