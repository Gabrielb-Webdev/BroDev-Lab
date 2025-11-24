<?php
/**
 * API de Autenticación
 * Maneja login, logout y verificación de sesiones
 * Endpoint: /api/auth.php
 */

require_once '../config/config.php';

// Iniciar sesión PHP
session_start();

setCorsHeaders();

try {
    $db = getDBConnection();
} catch (PDOException $e) {
    sendJsonResponse(['error' => 'Error de conexión a base de datos. Verifica que la instalación esté completa.'], 500);
    exit;
}
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'POST':
        switch ($action) {
            case 'login':
                handleLogin($db);
                break;
            case 'logout':
                handleLogout($db);
                break;
            case 'register-admin':
                handleRegisterAdmin($db);
                break;
            default:
                sendJsonResponse(['error' => 'Acción no válida'], 400);
        }
        break;
        
    case 'GET':
        switch ($action) {
            case 'verify':
                verifySession($db);
                break;
            case 'current-user':
                getCurrentUser($db);
                break;
            default:
                sendJsonResponse(['error' => 'Acción no válida'], 400);
        }
        break;
        
    default:
        sendJsonResponse(['error' => 'Método no permitido'], 405);
}

// ============================================
// LOGIN DE ADMINISTRADORES
// ============================================
function handleLogin($db) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $userType = $data['user_type'] ?? 'admin'; // 'admin' o 'client'
    
    if (empty($username) || empty($password)) {
        sendJsonResponse(['error' => 'Usuario y contraseña son requeridos'], 400);
        return;
    }
    
    if ($userType === 'admin') {
        loginAdmin($db, $username, $password);
    } else {
        loginClient($db, $username, $password);
    }
}

function loginAdmin($db, $username, $password) {
    try {
        // Verificar que la tabla existe
        $tableCheck = $db->query("SHOW TABLES LIKE 'admin_users'");
        if ($tableCheck->rowCount() === 0) {
            sendJsonResponse(['error' => 'La base de datos no está configurada. Por favor ejecuta el instalador en /setup.php'], 500);
            return;
        }
        
        // Buscar usuario admin por username o email
        $stmt = $db->prepare("
            SELECT id, username, email, password_hash, full_name, role, status 
            FROM admin_users 
            WHERE (username = ? OR email = ?) AND status = 'active'
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendJsonResponse(['error' => 'Credenciales inválidas'], 401);
            return;
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            sendJsonResponse(['error' => 'Credenciales inválidas'], 401);
            return;
        }
        
        // Crear sesión
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + ADMIN_SESSION_LIFETIME);
        
        $stmt = $db->prepare("
            INSERT INTO user_sessions (user_id, user_type, session_token, ip_address, user_agent, expires_at)
            VALUES (?, 'admin', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            $sessionToken,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $expiresAt
        ]);
        
        // Actualizar último login
        $stmt = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Guardar en sesión PHP
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = 'admin';
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['session_token'] = $sessionToken;
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'user_type' => 'admin',
                'session_token' => $sessionToken,
                'expires_at' => $expiresAt
            ]
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => 'Error en el servidor: ' . $e->getMessage()], 500);
    }
}

function loginClient($db, $accessCode, $password = null) {
    try {
        // Buscar cliente por código de acceso
        $stmt = $db->prepare("
            SELECT id, name, email, phone, company, access_code, status 
            FROM clients 
            WHERE access_code = ? AND status = 'active'
        ");
        $stmt->execute([$accessCode]);
        $client = $stmt->fetch();
        
        if (!$client) {
            sendJsonResponse(['error' => 'Código de acceso inválido'], 401);
            return;
        }
        
        // Si el cliente tiene contraseña configurada, verificarla
        if (!empty($client['password_hash']) && !empty($password)) {
            if (!password_verify($password, $client['password_hash'])) {
                sendJsonResponse(['error' => 'Contraseña incorrecta'], 401);
                return;
            }
        }
        
        // Crear sesión
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        $stmt = $db->prepare("
            INSERT INTO user_sessions (user_id, user_type, session_token, ip_address, user_agent, expires_at)
            VALUES (?, 'client', ?, ?, ?, ?)
        ");
        $stmt->execute([
            $client['id'],
            $sessionToken,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $expiresAt
        ]);
        
        // Actualizar último login
        $stmt = $db->prepare("UPDATE clients SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$client['id']]);
        
        // Guardar en sesión PHP
        $_SESSION['user_id'] = $client['id'];
        $_SESSION['user_type'] = 'client';
        $_SESSION['client_name'] = $client['name'];
        $_SESSION['access_code'] = $client['access_code'];
        $_SESSION['session_token'] = $sessionToken;
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'user_id' => $client['id'],
                'name' => $client['name'],
                'email' => $client['email'],
                'company' => $client['company'],
                'user_type' => 'client',
                'session_token' => $sessionToken,
                'expires_at' => $expiresAt
            ]
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => 'Error en el servidor: ' . $e->getMessage()], 500);
    }
}

// ============================================
// LOGOUT
// ============================================
function handleLogout($db) {
    try {
        $sessionToken = $_SESSION['session_token'] ?? null;
        
        if ($sessionToken) {
            // Eliminar sesión de la base de datos
            $stmt = $db->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$sessionToken]);
        }
        
        // Destruir sesión PHP
        session_unset();
        session_destroy();
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => 'Error al cerrar sesión'], 500);
    }
}

// ============================================
// VERIFICAR SESIÓN
// ============================================
function verifySession($db) {
    try {
        $sessionToken = $_SESSION['session_token'] ?? null;
        
        if (!$sessionToken) {
            sendJsonResponse(['authenticated' => false], 401);
            return;
        }
        
        // Verificar que la sesión existe y no ha expirado
        $stmt = $db->prepare("
            SELECT user_id, user_type, expires_at 
            FROM user_sessions 
            WHERE session_token = ? AND expires_at > NOW()
        ");
        $stmt->execute([$sessionToken]);
        $session = $stmt->fetch();
        
        if (!$session) {
            session_unset();
            session_destroy();
            sendJsonResponse(['authenticated' => false], 401);
            return;
        }
        
        sendJsonResponse([
            'authenticated' => true,
            'user_type' => $session['user_type'],
            'user_id' => $session['user_id']
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => 'Error al verificar sesión'], 500);
    }
}

// ============================================
// OBTENER USUARIO ACTUAL
// ============================================
function getCurrentUser($db) {
    try {
        $sessionToken = $_SESSION['session_token'] ?? null;
        $userType = $_SESSION['user_type'] ?? null;
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$sessionToken || !$userType || !$userId) {
            sendJsonResponse(['error' => 'No autenticado'], 401);
            return;
        }
        
        if ($userType === 'admin') {
            $stmt = $db->prepare("
                SELECT id, username, email, full_name, role 
                FROM admin_users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                sendJsonResponse([
                    'success' => true,
                    'data' => [
                        'user_id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'full_name' => $user['full_name'],
                        'role' => $user['role'],
                        'user_type' => 'admin'
                    ]
                ]);
            }
        } else {
            $stmt = $db->prepare("
                SELECT id, name, email, company, access_code 
                FROM clients 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                sendJsonResponse([
                    'success' => true,
                    'data' => [
                        'user_id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'company' => $user['company'],
                        'access_code' => $user['access_code'],
                        'user_type' => 'client'
                    ]
                ]);
            }
        }
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => 'Error al obtener usuario'], 500);
    }
}

// ============================================
// REGISTRAR NUEVO ADMIN (Solo super_admin)
// ============================================
function handleRegisterAdmin($db) {
    // Verificar que el usuario actual es super_admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
        sendJsonResponse(['error' => 'No tienes permisos para esta acción'], 403);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = $data['username'] ?? '';
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $fullName = $data['full_name'] ?? '';
    $role = $data['role'] ?? 'admin';
    
    if (empty($username) || empty($email) || empty($password) || empty($fullName)) {
        sendJsonResponse(['error' => 'Todos los campos son requeridos'], 400);
        return;
    }
    
    // Validar rol
    $validRoles = ['super_admin', 'admin', 'editor'];
    if (!in_array($role, $validRoles)) {
        $role = 'admin';
    }
    
    try {
        // Verificar que no exista el username o email
        $stmt = $db->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            sendJsonResponse(['error' => 'El usuario o email ya existe'], 400);
            return;
        }
        
        // Hash de la contraseña
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertar nuevo admin
        $stmt = $db->prepare("
            INSERT INTO admin_users (username, email, password_hash, full_name, role, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $username,
            $email,
            $passwordHash,
            $fullName,
            $role,
            $_SESSION['user_id']
        ]);
        
        sendJsonResponse([
            'success' => true,
            'message' => 'Usuario administrador creado exitosamente',
            'data' => [
                'id' => $db->lastInsertId(),
                'username' => $username,
                'email' => $email,
                'full_name' => $fullName,
                'role' => $role
            ]
        ]);
        
    } catch (PDOException $e) {
        sendJsonResponse(['error' => 'Error al crear usuario: ' . $e->getMessage()], 500);
    }
}

// ============================================
// FUNCIÓN AUXILIAR
// ============================================
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
