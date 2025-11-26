<?php
/**
 * Middleware de Autenticación y Autorización
 * BroDev Lab
 */

// Configurar cookies de sesión antes de iniciar (igual que en auth.php)
if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 86400, // 24 horas
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', $isSecure ? 1 : 0);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_lifetime', 86400);
    }
    
    session_start();
}

/**
 * Verificar que el usuario esté autenticado
 */
function requireAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autenticado. Por favor inicia sesión.']);
        exit;
    }
    
    return true;
}

/**
 * Verificar que el usuario sea administrador
 */
function requireAdmin() {
    requireAuth();
    
    if ($_SESSION['user_type'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado. Se requieren permisos de administrador.']);
        exit;
    }
    
    // Verificar que el rol sea admin o super_admin
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado. Se requiere rol de administrador.']);
        exit;
    }
    
    return true;
}

/**
 * Verificar que el usuario sea super admin
 */
function requireSuperAdmin() {
    requireAdmin();
    
    if ($_SESSION['role'] !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado. Se requieren permisos de super administrador.']);
        exit;
    }
    
    return true;
}

/**
 * Verificar que el usuario sea cliente
 */
function requireClient() {
    requireAuth();
    
    if ($_SESSION['user_type'] !== 'client') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado. Solo para clientes.']);
        exit;
    }
    
    return true;
}

/**
 * Verificar que el usuario tenga acceso a un proyecto específico
 */
function canAccessProject($db, $projectId) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return false;
    }
    
    // Los admins pueden acceder a todos los proyectos
    if ($_SESSION['user_type'] === 'admin') {
        return true;
    }
    
    // Los clientes solo pueden acceder a sus propios proyectos
    if ($_SESSION['user_type'] === 'client') {
        try {
            $stmt = $db->prepare("SELECT id FROM projects WHERE id = ? AND client_id = ?");
            $stmt->execute([$projectId, $_SESSION['user_id']]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    return false;
}

/**
 * Verificar que el usuario tenga acceso a un cliente específico
 */
function canAccessClient($db, $clientId) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return false;
    }
    
    // Los admins pueden acceder a todos los clientes
    if ($_SESSION['user_type'] === 'admin') {
        return true;
    }
    
    // Los clientes solo pueden acceder a su propia información
    if ($_SESSION['user_type'] === 'client') {
        return $_SESSION['user_id'] == $clientId;
    }
    
    return false;
}

/**
 * Obtener el ID del usuario actual
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Obtener el tipo de usuario actual
 */
function getCurrentUserType() {
    return $_SESSION['user_type'] ?? null;
}

/**
 * Obtener el rol del usuario actual (solo para admins)
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Verificar si el usuario actual es admin
 */
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Verificar si el usuario actual es cliente
 */
function isClient() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'client';
}

/**
 * Filtrar proyectos según permisos del usuario
 */
function filterProjectsByPermissions($db, $projects) {
    if (!isset($_SESSION['user_type'])) {
        return [];
    }
    
    // Admins ven todos los proyectos
    if ($_SESSION['user_type'] === 'admin') {
        return $projects;
    }
    
    // Clientes solo ven sus proyectos
    if ($_SESSION['user_type'] === 'client') {
        return array_filter($projects, function($project) {
            return $project['client_id'] == $_SESSION['user_id'];
        });
    }
    
    return [];
}

/**
 * Registrar actividad del usuario
 */
function logUserActivity($db, $action, $details = null) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        return;
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO user_activity_log (user_id, user_type, action, details, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $_SESSION['user_type'],
            $action,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

/**
 * Validar token de sesión
 */
function validateSessionToken($db) {
    if (!isset($_SESSION['session_token'])) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("
            SELECT user_id, user_type 
            FROM user_sessions 
            WHERE session_token = ? AND expires_at > NOW()
        ");
        $stmt->execute([$_SESSION['session_token']]);
        $session = $stmt->fetch();
        
        if (!$session) {
            // Sesión expirada o inválida
            session_unset();
            session_destroy();
            return false;
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error validating session: " . $e->getMessage());
        return false;
    }
}

/**
 * Limpiar sesiones expiradas (ejecutar periódicamente)
 */
function cleanExpiredSessions($db) {
    try {
        $stmt = $db->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Error cleaning sessions: " . $e->getMessage());
        return 0;
    }
}
?>
