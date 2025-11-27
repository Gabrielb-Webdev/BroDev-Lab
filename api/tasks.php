<?php
/**
 * API para gestión de tareas v0.4
 * Endpoint: /api/tasks.php
 * Corregido: nombres de columnas de tabla projects
 */

// Habilitar errores para debug en desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar en producción
ini_set('log_errors', 1);

try {
    require_once '../config/config.php';
    require_once '../config/auth-middleware.php';
    
    setCorsHeaders();
    
    $db = getDBConnection();
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de inicialización: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    exit;
}

switch ($method) {
    case 'GET':
        if ($action === 'by-status') {
            getAllTasksByStatus($db);
        } elseif ($action === 'by-project') {
            getTasksByProject($db, $_GET['project_id'] ?? null);
        } elseif (isset($_GET['id'])) {
            getTaskById($db, $_GET['id']);
        } else {
            getAllTasks($db);
        }
        break;
        
    case 'POST':
        requireAuth();
        createTask($db);
        break;
        
    case 'PUT':
        requireAuth();
        updateTask($db);
        break;
        
    case 'DELETE':
        requireAuth();
        deleteTask($db);
        break;
        
    default:
        sendJsonResponse(['error' => 'Método no permitido'], 405);
}

function getAllTasks($db) {
    try {
        $query = "
            SELECT 
                t.*,
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
        
        sendJsonResponse([
            'success' => true,
            'data' => $tasks
        ]);
        
    } catch (PDOException $e) {
        error_log("Error fetching tasks: " . $e->getMessage());
        sendJsonResponse(['error' => 'Error al obtener tareas'], 500);
    }
}

function getAllTasksByStatus($db) {
    try {
        $query = "
            SELECT 
                t.*,
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
            if (!isset($grouped[$status])) {
                $grouped[$status] = [];
            }
            $grouped[$status][] = $task;
        }
        
        sendJsonResponse([
            'success' => true,
            'data' => $grouped,
            'total' => count($tasks)
        ]);
        
    } catch (PDOException $e) {
        error_log("Error fetching tasks by status: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'error' => 'Error al obtener tareas: ' . $e->getMessage(),
            'query_error' => $e->errorInfo
        ], 500);
    } catch (Exception $e) {
        error_log("Error general: " . $e->getMessage());
        sendJsonResponse([
            'success' => false,
            'error' => 'Error general: ' . $e->getMessage()
        ], 500);
    }
}

function getTasksByProject($db, $projectId) {
    if (!$projectId) {
        sendJsonResponse(['error' => 'Project ID requerido'], 400);
        return;
    }
    
    try {
        $query = "
            SELECT 
                t.*,
                p.project_name,
                p.status as project_status,
                u.username as assignee_name,
                u.full_name as assignee_full_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN admins u ON t.assignee_id = u.id
            WHERE t.project_id = :project_id
            ORDER BY t.created_at DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendJsonResponse([
            'success' => true,
            'data' => $tasks
        ]);
        
    } catch (PDOException $e) {
        error_log("Error fetching tasks by project: " . $e->getMessage());
        sendJsonResponse(['error' => 'Error al obtener tareas'], 500);
    }
}

function getTaskById($db, $id) {
    try {
        $query = "
            SELECT 
                t.*,
                p.project_name,
                p.status as project_status,
                u.username as assignee_name,
                u.full_name as assignee_full_name
            FROM tasks t
            LEFT JOIN projects p ON t.project_id = p.id
            LEFT JOIN admins u ON t.assignee_id = u.id
            WHERE t.id = :id
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task) {
            sendJsonResponse([
                'success' => true,
                'data' => $task
            ]);
        } else {
            sendJsonResponse(['error' => 'Tarea no encontrada'], 404);
        }
        
    } catch (PDOException $e) {
        error_log("Error fetching task: " . $e->getMessage());
        sendJsonResponse(['error' => 'Error al obtener tarea'], 500);
    }
}

function createTask($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar campos requeridos
        if (empty($data['title'])) {
            sendJsonResponse(['error' => 'El título es requerido'], 400);
            return;
        }
        
        $query = "
            INSERT INTO tasks (
                title, description, status, priority,
                project_id, assignee_id, due_date, created_at
            ) VALUES (
                :title, :description, :status, :priority,
                :project_id, :assignee_id, :due_date, NOW()
            )
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindValue(':status', $data['status'] ?? 'todo');
        $stmt->bindValue(':priority', $data['priority'] ?? 'normal');
        $stmt->bindValue(':project_id', $data['project_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':assignee_id', $data['assignee_id'] ?? null, PDO::PARAM_INT);
        $stmt->bindValue(':due_date', $data['due_date'] ?? null);
        
        $stmt->execute();
        $taskId = $db->lastInsertId();
        
        // Registrar en sync_log para WebSocket
        logSyncEvent($db, 'task', 'task_created', $taskId, [
            'title' => $data['title'],
            'status' => $data['status'] ?? 'todo'
        ]);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Tarea creada exitosamente',
            'data' => ['id' => $taskId]
        ], 201);
        
    } catch (PDOException $e) {
        error_log("Error creating task: " . $e->getMessage());
        sendJsonResponse(['error' => 'Error al crear tarea'], 500);
    }
}

function updateTask($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            sendJsonResponse(['error' => 'ID de tarea requerido'], 400);
            return;
        }
        
        // Obtener tarea actual para comparación
        $currentQuery = "SELECT * FROM tasks WHERE id = :id";
        $currentStmt = $db->prepare($currentQuery);
        $currentStmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        $currentStmt->execute();
        $currentTask = $currentStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentTask) {
            sendJsonResponse(['error' => 'Tarea no encontrada'], 404);
            return;
        }
        
        // Construir query dinámicamente
        $updates = [];
        $params = [':id' => $data['id']];
        
        $allowedFields = ['title', 'description', 'status', 'priority', 'project_id', 'assignee_id', 'due_date'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            sendJsonResponse(['error' => 'No hay campos para actualizar'], 400);
            return;
        }
        
        $query = "UPDATE tasks SET " . implode(', ', $updates) . " WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        // Registrar cambios en sync_log
        $changes = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && $currentTask[$field] != $data[$field]) {
                $changes[$field] = [
                    'old' => $currentTask[$field],
                    'new' => $data[$field]
                ];
            }
        }
        
        if (!empty($changes)) {
            logSyncEvent($db, 'task', 'task_updated', $data['id'], $changes);
        }
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Tarea actualizada exitosamente'
        ]);
        
    } catch (PDOException $e) {
        error_log("Error updating task: " . $e->getMessage());
        sendJsonResponse(['error' => 'Error al actualizar tarea'], 500);
    }
}

function deleteTask($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            sendJsonResponse(['error' => 'ID de tarea requerido'], 400);
            return;
        }
        
        $query = "DELETE FROM tasks WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Registrar en sync_log
            logSyncEvent($db, 'task', 'task_deleted', $data['id'], []);
            
            sendJsonResponse([
                'success' => true,
                'message' => 'Tarea eliminada exitosamente'
            ]);
        } else {
            sendJsonResponse(['error' => 'Tarea no encontrada'], 404);
        }
        
    } catch (PDOException $e) {
        error_log("Error deleting task: " . $e->getMessage());
        sendJsonResponse(['error' => 'Error al eliminar tarea'], 500);
    }
}

function logSyncEvent($db, $entityType, $action, $entityId, $changes) {
    try {
        $query = "
            INSERT INTO sync_log (entity_type, action, entity_id, changed_fields, changed_at)
            VALUES (:entity_type, :action, :entity_id, :changed_fields, NOW())
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':entity_type', $entityType);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':entity_id', $entityId, PDO::PARAM_INT);
        $stmt->bindValue(':changed_fields', json_encode($changes));
        $stmt->execute();
        
    } catch (PDOException $e) {
        error_log("Error logging sync event: " . $e->getMessage());
    }
}

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
