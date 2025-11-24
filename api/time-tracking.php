<?php
/**
 * API para Time Tracking
 * Endpoint: /api/time-tracking.php
 */

require_once '../config/config.php';
setCorsHeaders();

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Obtener sesiones de tiempo
        if (isset($_GET['project_id'])) {
            getProjectTimeSessions($db, $_GET['project_id']);
        } elseif (isset($_GET['phase_id'])) {
            getPhaseTimeSessions($db, $_GET['phase_id']);
        } elseif (isset($_GET['active'])) {
            getActiveSessions($db);
        }
        break;
        
    case 'POST':
        // Iniciar nueva sesión de tiempo
        $action = $_GET['action'] ?? 'start';
        if ($action === 'start') {
            startTimeSession($db);
        } elseif ($action === 'stop') {
            stopTimeSession($db);
        }
        break;
        
    default:
        sendJsonResponse(['error' => 'Método no permitido'], 405);
}

function startTimeSession($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['project_id']) || !isset($data['phase_id'])) {
            sendJsonResponse(['error' => 'Datos incompletos'], 400);
        }
        
        // Verificar si hay sesiones activas para este proyecto
        $checkStmt = $db->prepare("
            SELECT id FROM time_sessions 
            WHERE project_id = ? AND is_active = 1
        ");
        $checkStmt->execute([$data['project_id']]);
        
        if ($checkStmt->fetch()) {
            sendJsonResponse(['error' => 'Ya hay una sesión activa para este proyecto'], 400);
        }
        
        // Crear nueva sesión
        $stmt = $db->prepare("
            INSERT INTO time_sessions 
            (project_id, phase_id, session_description, start_time, is_active) 
            VALUES (?, ?, ?, NOW(), 1)
        ");
        
        $stmt->execute([
            $data['project_id'],
            $data['phase_id'],
            $data['description'] ?? 'Trabajando en el proyecto'
        ]);
        
        $sessionId = $db->lastInsertId();
        
        // Actualizar estado de la fase a "in_progress"
        $phaseStmt = $db->prepare("
            UPDATE project_phases 
            SET status = 'in_progress', 
                start_date = COALESCE(start_date, NOW())
            WHERE id = ?
        ");
        $phaseStmt->execute([$data['phase_id']]);
        
        // Actualizar estado del proyecto
        $projectStmt = $db->prepare("
            UPDATE projects 
            SET status = 'in_progress'
            WHERE id = ? AND status = 'pending'
        ");
        $projectStmt->execute([$data['project_id']]);
        
        // Obtener información de la fase
        $phaseInfoStmt = $db->prepare("
            SELECT phase_name FROM project_phases WHERE id = ?
        ");
        $phaseInfoStmt->execute([$data['phase_id']]);
        $phaseInfo = $phaseInfoStmt->fetch();
        
        // Crear actividad
        $activityStmt = $db->prepare("
            INSERT INTO project_activities 
            (project_id, phase_id, activity_type, title, description, created_by) 
            VALUES (?, ?, 'phase_started', ?, ?, ?)
        ");
        $activityStmt->execute([
            $data['project_id'],
            $data['phase_id'],
            'Fase iniciada: ' . $phaseInfo['phase_name'],
            'Se ha comenzado a trabajar en esta fase',
            'Gabriel Dev'
        ]);
        
        // Crear notificación para el cliente
        $clientStmt = $db->prepare("
            SELECT client_id FROM projects WHERE id = ?
        ");
        $clientStmt->execute([$data['project_id']]);
        $client = $clientStmt->fetch();
        
        $notifStmt = $db->prepare("
            INSERT INTO notifications 
            (client_id, project_id, notification_type, title, message) 
            VALUES (?, ?, 'phase_started', ?, ?)
        ");
        $notifStmt->execute([
            $client['client_id'],
            $data['project_id'],
            '🚀 Nueva fase iniciada',
            'Hemos comenzado a trabajar en: ' . $phaseInfo['phase_name']
        ]);
        
        sendJsonResponse([
            'success' => true, 
            'session_id' => $sessionId,
            'message' => 'Sesión de tiempo iniciada'
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function stopTimeSession($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['session_id'])) {
            sendJsonResponse(['error' => 'ID de sesión requerido'], 400);
        }
        
        // Obtener información de la sesión
        $sessionStmt = $db->prepare("
            SELECT * FROM time_sessions WHERE id = ? AND is_active = 1
        ");
        $sessionStmt->execute([$data['session_id']]);
        $session = $sessionStmt->fetch();
        
        if (!$session) {
            sendJsonResponse(['error' => 'Sesión no encontrada o ya finalizada'], 404);
        }
        
        // Calcular duración
        $startTime = new DateTime($session['start_time']);
        $endTime = new DateTime();
        $duration = $endTime->getTimestamp() - $startTime->getTimestamp();
        
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
            $data['session_id']
        ]);
        
        // Actualizar tiempo total de la fase
        $phaseStmt = $db->prepare("
            UPDATE project_phases 
            SET actual_time_seconds = actual_time_seconds + ?
            WHERE id = ?
        ");
        $phaseStmt->execute([$duration, $session['phase_id']]);
        
        // Actualizar tiempo total del proyecto
        $projectStmt = $db->prepare("
            UPDATE projects 
            SET total_time_seconds = total_time_seconds + ?
            WHERE id = ?
        ");
        $projectStmt->execute([$duration, $session['project_id']]);
        
        // Verificar si se debe completar la fase
        if (isset($data['complete_phase']) && $data['complete_phase']) {
            completePhase($db, $session['phase_id'], $session['project_id']);
        }
        
        sendJsonResponse([
            'success' => true,
            'duration' => $duration,
            'formatted_duration' => formatTime($duration),
            'message' => 'Sesión de tiempo finalizada'
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function completePhase($db, $phaseId, $projectId) {
    // Actualizar estado de la fase
    $stmt = $db->prepare("
        UPDATE project_phases 
        SET status = 'completed', end_date = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$phaseId]);
    
    // Obtener información de la fase
    $phaseInfoStmt = $db->prepare("
        SELECT phase_name, actual_time_seconds FROM project_phases WHERE id = ?
    ");
    $phaseInfoStmt->execute([$phaseId]);
    $phaseInfo = $phaseInfoStmt->fetch();
    
    // Crear actividad
    $activityStmt = $db->prepare("
        INSERT INTO project_activities 
        (project_id, phase_id, activity_type, title, description, created_by) 
        VALUES (?, ?, 'phase_completed', ?, ?, ?)
    ");
    $activityStmt->execute([
        $projectId,
        $phaseId,
        '✅ Fase completada: ' . $phaseInfo['phase_name'],
        'Tiempo total: ' . formatTime($phaseInfo['actual_time_seconds']),
        'Gabriel Dev'
    ]);
    
    // Calcular progreso del proyecto
    updateProjectProgress($db, $projectId);
    
    // Crear notificación
    $clientStmt = $db->prepare("SELECT client_id FROM projects WHERE id = ?");
    $clientStmt->execute([$projectId]);
    $client = $clientStmt->fetch();
    
    $notifStmt = $db->prepare("
        INSERT INTO notifications 
        (client_id, project_id, notification_type, title, message) 
        VALUES (?, ?, 'phase_completed', ?, ?)
    ");
    $notifStmt->execute([
        $client['client_id'],
        $projectId,
        '✅ Fase completada',
        $phaseInfo['phase_name'] . ' ha sido completada exitosamente. Tiempo invertido: ' . formatTime($phaseInfo['actual_time_seconds'])
    ]);
}

function updateProjectProgress($db, $projectId) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_phases,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_phases
        FROM project_phases
        WHERE project_id = ?
    ");
    $stmt->execute([$projectId]);
    $result = $stmt->fetch();
    
    $progress = ($result['completed_phases'] / $result['total_phases']) * 100;
    
    $updateStmt = $db->prepare("
        UPDATE projects 
        SET progress_percentage = ?
        WHERE id = ?
    ");
    $updateStmt->execute([$progress, $projectId]);
}

function getProjectTimeSessions($db, $projectId) {
    try {
        $stmt = $db->prepare("
            SELECT 
                ts.*,
                pp.phase_name
            FROM time_sessions ts
            LEFT JOIN project_phases pp ON ts.phase_id = pp.id
            WHERE ts.project_id = ?
            ORDER BY ts.start_time DESC
        ");
        $stmt->execute([$projectId]);
        $sessions = $stmt->fetchAll();
        
        sendJsonResponse(['success' => true, 'data' => $sessions]);
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function getActiveSessions($db) {
    try {
        $stmt = $db->query("
            SELECT 
                ts.*,
                pp.phase_name,
                p.project_name
            FROM time_sessions ts
            LEFT JOIN project_phases pp ON ts.phase_id = pp.id
            LEFT JOIN projects p ON ts.project_id = p.id
            WHERE ts.is_active = 1
            ORDER BY ts.start_time DESC
        ");
        $sessions = $stmt->fetchAll();
        
        // Calcular tiempo transcurrido para sesiones activas
        foreach ($sessions as &$session) {
            $startTime = new DateTime($session['start_time']);
            $now = new DateTime();
            $session['elapsed_seconds'] = $now->getTimestamp() - $startTime->getTimestamp();
            $session['elapsed_formatted'] = formatTime($session['elapsed_seconds']);
        }
        
        sendJsonResponse(['success' => true, 'data' => $sessions]);
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}
?>
