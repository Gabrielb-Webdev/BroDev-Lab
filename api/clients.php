<?php
/**
 * API para gestión de clientes
 * Endpoint: /api/clients.php
 */

require_once '../config/config.php';
require_once '../config/auth-middleware.php';

setCorsHeaders();

$db = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        requireAuth();
        
        if (isset($_GET['access_code'])) {
            getClientByAccessCode($db, $_GET['access_code']);
        } elseif (isset($_GET['id'])) {
            getClientById($db, $_GET['id']);
        } else {
            requireAdmin();
            getAllClients($db);
        }
        break;
        
    case 'POST':
        requireAdmin();
        createClient($db);
        break;
        
    case 'PUT':
        requireAdmin();
        updateClient($db);
        break;
        
    case 'DELETE':
        requireAdmin();
        deleteClient($db);
        break;
        
    default:
        sendJsonResponse(['error' => 'Método no permitido'], 405);
}

function createClient($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['name']) || !isset($data['email'])) {
            sendJsonResponse(['error' => 'Nombre y email son requeridos'], 400);
        }
        
        // Generar código de acceso único
        $accessCode = generateAccessCode();
        
        $stmt = $db->prepare("
            INSERT INTO clients 
            (name, email, phone, company, access_code) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['company'] ?? null,
            $accessCode
        ]);
        
        $clientId = $db->lastInsertId();
        
        sendJsonResponse([
            'success' => true,
            'client_id' => $clientId,
            'access_code' => $accessCode,
            'message' => 'Cliente creado exitosamente'
        ]);
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            sendJsonResponse(['error' => 'El email ya está registrado'], 400);
        }
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function getAllClients($db) {
    try {
        $stmt = $db->query("
            SELECT 
                c.*,
                COUNT(DISTINCT p.id) as total_projects,
                SUM(CASE WHEN p.status = 'in_progress' THEN 1 ELSE 0 END) as active_projects
            FROM clients c
            LEFT JOIN projects p ON c.id = p.client_id
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ");
        $clients = $stmt->fetchAll();
        sendJsonResponse(['success' => true, 'data' => $clients]);
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function getClientById($db, $id) {
    try {
        $stmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        $client = $stmt->fetch();
        
        if ($client) {
            sendJsonResponse(['success' => true, 'data' => $client]);
        } else {
            sendJsonResponse(['error' => 'Cliente no encontrado'], 404);
        }
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function getClientByAccessCode($db, $accessCode) {
    try {
        $stmt = $db->prepare("SELECT * FROM clients WHERE access_code = ?");
        $stmt->execute([$accessCode]);
        $client = $stmt->fetch();
        
        if ($client) {
            // Actualizar último login
            $updateStmt = $db->prepare("UPDATE clients SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$client['id']]);
            
            sendJsonResponse(['success' => true, 'data' => $client]);
        } else {
            sendJsonResponse(['error' => 'Código de acceso inválido'], 404);
        }
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}

function updateClient($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id'])) {
            sendJsonResponse(['error' => 'ID de cliente requerido'], 400);
        }
        
        $fields = [];
        $values = [];
        $allowedFields = ['name', 'email', 'phone', 'company', 'status'];
        
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
        $sql = "UPDATE clients SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        
        sendJsonResponse(['success' => true, 'message' => 'Cliente actualizado']);
    } catch (PDOException $e) {
        sendJsonResponse(['error' => $e->getMessage()], 500);
    }
}
?>
