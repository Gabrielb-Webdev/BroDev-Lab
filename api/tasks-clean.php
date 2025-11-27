<?php
/**
 * API de Tareas - Versión Limpia v1.0
 * Sin middleware, sin complicaciones
 */

// Headers primero
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejo de OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Sin mostrar errores en producción
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Conexión a BD
    require_once __DIR__ . '/../config/config.php';
    $db = getDBConnection();
    
    // Determinar acción
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? null;
    
    // Solo GET por ahora
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Solo GET permitido por ahora']);
        exit;
    }
    
    // Enrutamiento
    if ($action === 'by-status') {
        getTasksByStatus($db);
    } elseif ($id) {
        getTaskById($db, $id);
    } else {
        getAllTasks($db);
    }
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}

/**
 * Obtener todas las tareas
 */
function getAllTasks($db) {
    $query = "
        SELECT 
            t.id, t.title, t.description, t.status, t.priority,
            t.project_id, t.assignee_id, t.due_date,
            t.created_at, t.updated_at,
            p.project_name,
            u.username as assignee_name,
            u.full_name as assignee_full_name
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
        LEFT JOIN admins u ON t.assignee_id = u.id
        ORDER BY 
            FIELD(t.priority, 'urgent', 'high', 'normal', 'low'),
            t.created_at DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $tasks,
        'total' => count($tasks)
    ]);
}

/**
 * Obtener tareas agrupadas por estado
 */
function getTasksByStatus($db) {
    $query = "
        SELECT 
            t.id, t.title, t.description, t.status, t.priority,
            t.project_id, t.assignee_id, t.due_date,
            t.created_at, t.updated_at,
            p.project_name,
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
    
    echo json_encode([
        'success' => true,
        'data' => $grouped,
        'total' => count($tasks)
    ]);
}

/**
 * Obtener una tarea por ID
 */
function getTaskById($db, $id) {
    $query = "
        SELECT 
            t.*, 
            p.project_name,
            u.username as assignee_name,
            u.full_name as assignee_full_name
        FROM tasks t
        LEFT JOIN projects p ON t.project_id = p.id
        LEFT JOIN admins u ON t.assignee_id = u.id
        WHERE t.id = ?
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        echo json_encode(['success' => true, 'data' => $task]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Tarea no encontrada']);
    }
}
