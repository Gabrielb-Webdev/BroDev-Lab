<?php
/**
 * API para gestión de proyectos
 * Endpoint: /api/projects.php
 */

require_once '../config/config.php';
require_once '../config/auth-middleware.php';

setCorsHeaders();

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Obtener proyectos o un proyecto específico
        if (isset($_GET['id'])) {
            getProjectById($db, $_GET['id']);
        } elseif (isset($_GET['access_code'])) {
            getProjectByAccessCode($db, $_GET['access_code']);
        } elseif (isset($_GET['client_id'])) {
            getProjectsByClient($db, $_GET['client_id']);
        } else {
            getAllProjects($db);
        }
        break;
        
    case 'POST':
        createProject($db);
        break;
        
    case 'PUT':
        updateProject($db);
        break;
        
    case 'DELETE':
        deleteProject($db);
        break;
        
    default:
        sendJsonResponse(['error' => 'Método no permitido'], 405);
}

function getAllProjects($db) {
    try {
        $stmt = $db->query("
            SELECT 
                p.*,
                c.name as client_name,
                c.email as client_email,
                c.company,
                (p.total_time_seconds / 3600.0) * p.hourly_rate as total_cost
            FROM projects p
            JOIN clients c ON p.client_id = c.id
            ORDER BY p.created_at DESC
        ");
        $projects = $stmt->fetchAll();
        sendJsonResponse(['success' => true, 'data' => $projects]);
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function getProjectById($db, $id) {
    try {
        $stmt = $db->prepare("
            SELECT 
                p.*,
                c.name as client_name,
                c.email as client_email,
                c.phone as client_phone,
                c.company,
                (p.total_time_seconds / 3600.0) * p.hourly_rate as total_cost
            FROM projects p
            JOIN clients c ON p.client_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        
        if ($project) {
            // Obtener fases del proyecto
            $stmtPhases = $db->prepare("
                SELECT * FROM project_phases 
                WHERE project_id = ? 
                ORDER BY phase_number ASC
            ");
            $stmtPhases->execute([$id]);
            $project['phases'] = $stmtPhases->fetchAll();
            
            // Obtener actividades recientes
            $stmtActivities = $db->prepare("
                SELECT * FROM project_activities 
                WHERE project_id = ? 
                ORDER BY created_at DESC 
                LIMIT 10
            ");
            $stmtActivities->execute([$id]);
            $project['activities'] = $stmtActivities->fetchAll();
            
            sendJsonResponse(['success' => true, 'data' => $project]);
        } else {
            sendJsonResponse(['error' => 'Proyecto no encontrado'], 404);
        }
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function getProjectByAccessCode($db, $accessCode) {
    try {
        $stmt = $db->prepare("
            SELECT 
                p.*,
                c.name as client_name,
                c.email as client_email,
                c.phone as client_phone,
                c.company,
                c.access_code,
                (p.total_time_seconds / 3600.0) * p.hourly_rate as total_cost
            FROM projects p
            JOIN clients c ON p.client_id = c.id
            WHERE c.access_code = ?
        ");
        $stmt->execute([$accessCode]);
        $project = $stmt->fetch();
        
        if ($project) {
            // Obtener fases del proyecto
            $stmtPhases = $db->prepare("
                SELECT * FROM project_phases 
                WHERE project_id = ? 
                ORDER BY phase_number ASC
            ");
            $stmtPhases->execute([$project['id']]);
            $project['phases'] = $stmtPhases->fetchAll();
            
            // Obtener actividades recientes
            $stmtActivities = $db->prepare("
                SELECT * FROM project_activities 
                WHERE project_id = ? 
                ORDER BY created_at DESC 
                LIMIT 20
            ");
            $stmtActivities->execute([$project['id']]);
            $project['activities'] = $stmtActivities->fetchAll();
            
            sendJsonResponse(['success' => true, 'data' => $project]);
        } else {
            sendJsonResponse(['error' => 'Código de acceso inválido'], 404);
        }
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function createProject($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar datos requeridos
        if (!isset($data['client_id']) || !isset($data['project_name'])) {
            sendJsonResponse(['error' => 'Datos incompletos'], 400);
        }
        
        $stmt = $db->prepare("
            INSERT INTO projects 
            (client_id, project_name, project_type, description, budget, hourly_rate, assigned_to, start_date, estimated_end_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['client_id'],
            $data['project_name'],
            $data['project_type'] ?? null,
            $data['description'] ?? null,
            $data['budget'] ?? null,
            $data['hourly_rate'] ?? 50.00,
            $data['assigned_to'] ?? 'Gabriel Dev',
            $data['start_date'] ?? date('Y-m-d'),
            $data['estimated_end_date'] ?? null
        ]);
        
        $projectId = $db->lastInsertId();
        
        // Crear fases predeterminadas
        createDefaultPhases($db, $projectId);
        
        // Crear notificación
        createNotification($db, $data['client_id'], $projectId, 'project_created', 
            'Proyecto Creado', 'Tu proyecto ha sido registrado exitosamente.');
        
        sendJsonResponse(['success' => true, 'project_id' => $projectId, 'message' => 'Proyecto creado']);
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function createDefaultPhases($db, $projectId) {
    $defaultPhases = [
        ['name' => 'Análisis y Planeación', 'estimated_hours' => 4],
        ['name' => 'Diseño UI/UX', 'estimated_hours' => 10],
        ['name' => 'Desarrollo Frontend', 'estimated_hours' => 16],
        ['name' => 'Desarrollo Backend', 'estimated_hours' => 10],
        ['name' => 'Testing y QA', 'estimated_hours' => 6],
        ['name' => 'Deployment y Entrega', 'estimated_hours' => 3]
    ];
    
    $stmt = $db->prepare("
        INSERT INTO project_phases 
        (project_id, phase_number, phase_name, estimated_hours, assigned_to) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($defaultPhases as $index => $phase) {
        $stmt->execute([
            $projectId,
            $index + 1,
            $phase['name'],
            $phase['estimated_hours'],
            'Gabriel Dev'
        ]);
    }
}

function createNotification($db, $clientId, $projectId, $type, $title, $message) {
    $stmt = $db->prepare("
        INSERT INTO notifications 
        (client_id, project_id, notification_type, title, message) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$clientId, $projectId, $type, $title, $message]);
}

function updateProject($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            sendJsonResponse(['error' => 'ID de proyecto requerido'], 400);
        }
        
        $fields = [];
        $values = [];
        
        $allowedFields = ['project_name', 'project_type', 'description', 'budget', 
                         'hourly_rate', 'status', 'progress_percentage', 'estimated_end_date'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            sendJsonResponse(['error' => 'No hay datos para actualizar'], 400);
        }
        
        $values[] = $data['id'];
        $sql = "UPDATE projects SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        
        sendJsonResponse(['success' => true, 'message' => 'Proyecto actualizado']);
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function deleteProject($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            sendJsonResponse(['error' => 'ID de proyecto requerido'], 400);
        }
        
        $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        sendJsonResponse(['success' => true, 'message' => 'Proyecto eliminado']);
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}
?>
