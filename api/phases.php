<?php
/**
 * API para gestión de fases de proyectos
 * Endpoint: /api/phases.php
 */

require_once '../config/config.php';
require_once '../config/auth-middleware.php';

setCorsHeaders();

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        requireAuth();
        if (isset($_GET['project_id'])) {
            getPhasesByProject($db, $_GET['project_id']);
        } elseif (isset($_GET['id'])) {
            getPhaseById($db, $_GET['id']);
        }
        break;
        
    case 'POST':
        requireAdmin();
        createPhase($db);
        break;
        
    case 'PUT':
        requireAdmin();
        updatePhase($db);
        break;
        
    case 'DELETE':
        requireAdmin();
        deletePhase($db);
        break;
        
    default:
        sendJsonResponse(['error' => 'Método no permitido'], 405);
}

function getPhasesByProject($db, $projectId) {
    try {
        $stmt = $db->prepare("
            SELECT 
                pp.*,
                COALESCE(SUM(ts.duration_seconds), 0) as total_time_seconds,
                COALESCE(SUM(ts.duration_seconds) / 3600, 0) as total_hours
            FROM project_phases pp
            LEFT JOIN time_sessions ts ON pp.id = ts.phase_id
            WHERE pp.project_id = ?
            GROUP BY pp.id
            ORDER BY pp.phase_number ASC
        ");
        $stmt->execute([$projectId]);
        $phases = $stmt->fetchAll();
        
        sendJsonResponse([
            'success' => true,
            'data' => $phases
        ]);
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function createPhase($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['project_id']) || !isset($data['phase_name'])) {
            sendJsonResponse(['error' => 'project_id y phase_name son requeridos'], 400);
            return;
        }
        
        // Obtener el siguiente número de fase
        $stmt = $db->prepare("SELECT COALESCE(MAX(phase_number), 0) + 1 as next_number FROM project_phases WHERE project_id = ?");
        $stmt->execute([$data['project_id']]);
        $phaseNumber = $stmt->fetch()['next_number'];
        
        $stmt = $db->prepare("
            INSERT INTO project_phases 
            (project_id, phase_number, phase_name, description, estimated_hours, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['project_id'],
            $phaseNumber,
            $data['phase_name'],
            $data['description'] ?? null,
            $data['estimated_hours'] ?? null,
            $data['status'] ?? 'not_started'
        ]);
        
        $phaseId = $db->lastInsertId();
        
        // Registrar actividad
        $stmt = $db->prepare("
            INSERT INTO project_activities (project_id, phase_id, activity_type, title, description)
            VALUES (?, ?, 'phase_started', ?, ?)
        ");
        $stmt->execute([
            $data['project_id'],
            $phaseId,
            "Nueva fase creada: {$data['phase_name']}",
            $data['description'] ?? ''
        ]);
        
        sendJsonResponse([
            'success' => true,
            'phase_id' => $phaseId,
            'message' => 'Fase creada exitosamente'
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function updatePhase($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            sendJsonResponse(['error' => 'ID es requerido'], 400);
            return;
        }
        
        $fields = [];
        $values = [];
        
        if (isset($data['phase_name'])) {
            $fields[] = "phase_name = ?";
            $values[] = $data['phase_name'];
        }
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $values[] = $data['description'];
        }
        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $values[] = $data['status'];
            
            // Si se marca como completada, registrar fecha
            if ($data['status'] === 'completed') {
                $fields[] = "end_date = NOW()";
            }
        }
        if (isset($data['estimated_hours'])) {
            $fields[] = "estimated_hours = ?";
            $values[] = $data['estimated_hours'];
        }
        
        if (empty($fields)) {
            sendJsonResponse(['error' => 'No hay campos para actualizar'], 400);
            return;
        }
        
        $values[] = $data['id'];
        
        $sql = "UPDATE project_phases SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Fase actualizada exitosamente'
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function deletePhase($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            sendJsonResponse(['error' => 'ID es requerido'], 400);
            return;
        }
        
        $stmt = $db->prepare("DELETE FROM project_phases WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Fase eliminada exitosamente'
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}
