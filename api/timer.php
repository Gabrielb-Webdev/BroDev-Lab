<?php
/**
 * API para gestión de timer/sesiones de tiempo
 * Endpoint: /api/timer.php
 */

require_once '../config/config.php';
require_once '../config/auth-middleware.php';

setCorsHeaders();

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        requireAdmin();
        if ($action === 'active') {
            getActiveSession($db);
        } elseif ($action === 'history') {
            getTimerHistory($db, $_GET['project_id'] ?? null, $_GET['phase_id'] ?? null);
        }
        break;
        
    case 'POST':
        requireAdmin();
        if ($action === 'start') {
            startTimer($db);
        } elseif ($action === 'stop') {
            stopTimer($db);
        } elseif ($action === 'pause') {
            pauseTimer($db);
        } elseif ($action === 'adjust') {
            adjustTimer($db);
        } elseif ($action === 'update') {
            updateTimerSession($db);
        } elseif ($action === 'delete') {
            deleteTimerSession($db);
        }
        break;
        
    default:
        sendJsonResponse(['error' => 'Método no permitido'], 405);
}

function getActiveSession($db) {
    try {
        $stmt = $db->prepare("
            SELECT 
                ts.*,
                p.project_name,
                p.client_id,
                c.name as client_name,
                pp.phase_name
            FROM time_sessions ts
            INNER JOIN projects p ON ts.project_id = p.id
            INNER JOIN clients c ON p.client_id = c.id
            LEFT JOIN project_phases pp ON ts.phase_id = pp.id
            WHERE ts.is_active = 1
            ORDER BY ts.start_time DESC
            LIMIT 1
        ");
        $stmt->execute();
        $session = $stmt->fetch();
        
        if ($session) {
            // Calcular tiempo transcurrido
            $start = new DateTime($session['start_time']);
            $now = new DateTime();
            $elapsed = $now->getTimestamp() - $start->getTimestamp();
            $session['elapsed_seconds'] = $elapsed;
        }
        
        sendJsonResponse([
            'success' => true,
            'data' => $session ?: null
        ]);
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function startTimer($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['project_id'])) {
            sendJsonResponse(['error' => 'project_id es requerido'], 400);
            return;
        }
        
        // Verificar si hay una sesión activa
        $stmt = $db->prepare("SELECT id FROM time_sessions WHERE is_active = 1");
        $stmt->execute();
        if ($stmt->fetch()) {
            sendJsonResponse(['error' => 'Ya hay una sesión activa. Detén la sesión actual primero.'], 400);
            return;
        }
        
        // Crear nueva sesión
        $stmt = $db->prepare("
            INSERT INTO time_sessions 
            (project_id, phase_id, session_description, start_time, is_active)
            VALUES (?, ?, ?, NOW(), 1)
        ");
        
        $stmt->execute([
            $data['project_id'],
            $data['phase_id'] ?? null,
            $data['description'] ?? ''
        ]);
        
        $sessionId = $db->lastInsertId();
        
        // Actualizar estado de la fase si existe
        if (!empty($data['phase_id'])) {
            $stmt = $db->prepare("UPDATE project_phases SET status = 'in_progress', start_date = NOW() WHERE id = ? AND start_date IS NULL");
            $stmt->execute([$data['phase_id']]);
        }
        
        // Actualizar estado del proyecto
        $stmt = $db->prepare("UPDATE projects SET status = 'in_progress' WHERE id = ? AND status NOT IN ('completed', 'cancelled')");
        $stmt->execute([$data['project_id']]);
        
        sendJsonResponse([
            'success' => true,
            'session_id' => $sessionId,
            'message' => 'Timer iniciado'
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function stopTimer($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Obtener sesión activa
        $stmt = $db->prepare("SELECT * FROM time_sessions WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        $session = $stmt->fetch();
        
        if (!$session) {
            sendJsonResponse(['error' => 'No hay ninguna sesión activa'], 400);
            return;
        }
        
        // Calcular duración
        $start = new DateTime($session['start_time']);
        $end = new DateTime();
        $duration = $end->getTimestamp() - $start->getTimestamp();
        
        // Actualizar sesión
        $stmt = $db->prepare("
            UPDATE time_sessions 
            SET end_time = NOW(), 
                duration_seconds = ?,
                is_active = 0,
                notes = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $duration,
            $data['notes'] ?? null,
            $session['id']
        ]);
        
        // Actualizar tiempo total del proyecto
        $stmt = $db->prepare("
            UPDATE projects 
            SET total_time_seconds = total_time_seconds + ?
            WHERE id = ?
        ");
        $stmt->execute([$duration, $session['project_id']]);
        
        // Actualizar tiempo total de la fase
        if ($session['phase_id']) {
            $stmt = $db->prepare("
                UPDATE project_phases 
                SET actual_time_seconds = actual_time_seconds + ?
                WHERE id = ?
            ");
            $stmt->execute([$duration, $session['phase_id']]);
        }
        
        // Registrar actividad
        $hours = round($duration / 3600, 2);
        $stmt = $db->prepare("
            INSERT INTO project_activities 
            (project_id, phase_id, activity_type, title, description)
            VALUES (?, ?, 'milestone', ?, ?)
        ");
        $stmt->execute([
            $session['project_id'],
            $session['phase_id'],
            "Sesión de trabajo completada",
            "Duración: {$hours} horas" . ($data['notes'] ? " - " . $data['notes'] : "")
        ]);
        
        sendJsonResponse([
            'success' => true,
            'duration_seconds' => $duration,
            'duration_hours' => $hours,
            'message' => 'Timer detenido'
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function getTimerHistory($db, $projectId = null, $phaseId = null) {
    try {
        $sql = "
            SELECT 
                ts.*,
                p.project_name,
                pp.phase_name,
                c.name as client_name
            FROM time_sessions ts
            INNER JOIN projects p ON ts.project_id = p.id
            INNER JOIN clients c ON p.client_id = c.id
            LEFT JOIN project_phases pp ON ts.phase_id = pp.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($projectId) {
            $sql .= " AND ts.project_id = ?";
            $params[] = $projectId;
        }
        
        if ($phaseId) {
            $sql .= " AND ts.phase_id = ?";
            $params[] = $phaseId;
        }
        
        $sql .= " ORDER BY ts.start_time DESC LIMIT 100";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll();
        
        sendJsonResponse([
            'success' => true,
            'data' => $sessions
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function adjustTimer($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['session_id']) || !isset($data['elapsed_seconds'])) {
            sendJsonResponse(['error' => 'session_id y elapsed_seconds son requeridos'], 400);
            return;
        }
        
        $sessionId = $data['session_id'];
        $elapsedSeconds = intval($data['elapsed_seconds']);
        
        // Verificar que la sesión existe y está activa
        $stmt = $db->prepare("SELECT * FROM time_sessions WHERE id = ? AND is_active = 1");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if (!$session) {
            sendJsonResponse(['error' => 'Sesión no encontrada o no está activa'], 404);
            return;
        }
        
        // Calcular el nuevo start_time basado en el tiempo ajustado
        // Si queremos que elapsed_seconds sea X, entonces start_time debe ser NOW - X
        $stmt = $db->prepare("
            UPDATE time_sessions 
            SET start_time = DATE_SUB(NOW(), INTERVAL ? SECOND)
            WHERE id = ?
        ");
        
        $stmt->execute([$elapsedSeconds, $sessionId]);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Tiempo ajustado correctamente',
            'elapsed_seconds' => $elapsedSeconds
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function updateTimerSession($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['session_id']) || !isset($data['duration_seconds'])) {
            sendJsonResponse(['error' => 'session_id y duration_seconds son requeridos'], 400);
            return;
        }
        
        $sessionId = $data['session_id'];
        $newDuration = intval($data['duration_seconds']);
        
        if ($newDuration <= 0) {
            sendJsonResponse(['error' => 'La duración debe ser mayor a 0'], 400);
            return;
        }
        
        // Obtener la sesión actual
        $stmt = $db->prepare("SELECT * FROM time_sessions WHERE id = ? AND is_active = 0");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if (!$session) {
            sendJsonResponse(['error' => 'Sesión no encontrada o aún está activa'], 404);
            return;
        }
        
        $oldDuration = $session['duration_seconds'];
        $difference = $newDuration - $oldDuration;
        
        // Actualizar la duración de la sesión
        $stmt = $db->prepare("
            UPDATE time_sessions 
            SET duration_seconds = ?
            WHERE id = ?
        ");
        $stmt->execute([$newDuration, $sessionId]);
        
        // Actualizar el tiempo total del proyecto
        $stmt = $db->prepare("
            UPDATE projects 
            SET total_time_seconds = total_time_seconds + ?
            WHERE id = ?
        ");
        $stmt->execute([$difference, $session['project_id']]);
        
        // Actualizar el tiempo total de la fase si existe
        if ($session['phase_id']) {
            $stmt = $db->prepare("
                UPDATE project_phases 
                SET actual_time_seconds = actual_time_seconds + ?
                WHERE id = ?
            ");
            $stmt->execute([$difference, $session['phase_id']]);
        }
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Sesión actualizada correctamente',
            'old_duration' => $oldDuration,
            'new_duration' => $newDuration
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function deleteTimerSession($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['session_id'])) {
            sendJsonResponse(['error' => 'session_id es requerido'], 400);
            return;
        }
        
        $sessionId = $data['session_id'];
        
        // Obtener la sesión
        $stmt = $db->prepare("SELECT * FROM time_sessions WHERE id = ? AND is_active = 0");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if (!$session) {
            sendJsonResponse(['error' => 'Sesión no encontrada o aún está activa'], 404);
            return;
        }
        
        // Restar el tiempo del proyecto
        $stmt = $db->prepare("
            UPDATE projects 
            SET total_time_seconds = GREATEST(0, total_time_seconds - ?)
            WHERE id = ?
        ");
        $stmt->execute([$session['duration_seconds'], $session['project_id']]);
        
        // Restar el tiempo de la fase si existe
        if ($session['phase_id']) {
            $stmt = $db->prepare("
                UPDATE project_phases 
                SET actual_time_seconds = GREATEST(0, actual_time_seconds - ?)
                WHERE id = ?
            ");
            $stmt->execute([$session['duration_seconds'], $session['phase_id']]);
        }
        
        // Eliminar la sesión
        $stmt = $db->prepare("DELETE FROM time_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Sesión eliminada correctamente'
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}
