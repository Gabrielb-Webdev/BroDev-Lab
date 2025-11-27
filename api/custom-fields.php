<?php
/**
 * API para gestión de Custom Fields (Campos Customizables)
 * Sistema dinámico tipo Notion/Airtable para agregar/eliminar columnas
 */

require_once '../config/config.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($method) {
        case 'GET':
            handleGet($pdo, $action);
            break;
        case 'POST':
            handlePost($pdo, $action);
            break;
        case 'PUT':
            handlePut($pdo, $action);
            break;
        case 'DELETE':
            handleDelete($pdo, $action);
            break;
        default:
            throw new Exception('Método no soportado');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// ==================== GET Handlers ====================

function handleGet($pdo, $action) {
    switch ($action) {
        case 'field-types':
            getFieldTypes($pdo);
            break;
        case 'fields':
            getCustomFields($pdo);
            break;
        case 'field':
            getCustomField($pdo);
            break;
        case 'values':
            getFieldValues($pdo);
            break;
        case 'views':
            getCustomViews($pdo);
            break;
        case 'sync':
            getSyncUpdates($pdo);
            break;
        default:
            throw new Exception('Acción no válida');
    }
}

function getFieldTypes($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM field_types ORDER BY type_label");
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($types as &$type) {
            if ($type['validation_rules']) {
                $type['validation_rules'] = json_decode($type['validation_rules'], true);
            }
        }
        
        echo json_encode(['success' => true, 'data' => $types]);
    } catch (PDOException $e) {
        // Tabla no existe todavía
        echo json_encode(['success' => true, 'data' => [], 'warning' => 'Custom fields tables not installed yet']);
    }
}

function getCustomFields($pdo) {
    $entityType = $_GET['entity_type'] ?? null;
    
    if (!$entityType) {
        throw new Exception('entity_type es requerido');
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                cf.*,
                ft.type_label,
                ft.icon as type_icon
            FROM custom_fields cf
            LEFT JOIN field_types ft ON cf.field_type = ft.type_name
            WHERE cf.entity_type = ? AND cf.is_visible = 1
            ORDER BY cf.display_order
        ");
        $stmt->execute([$entityType]);
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($fields as &$field) {
            if ($field['field_options']) {
                $field['field_options'] = json_decode($field['field_options'], true);
            }
            if ($field['validation_rules']) {
                $field['validation_rules'] = json_decode($field['validation_rules'], true);
            }
        }
        
        echo json_encode(['success' => true, 'data' => $fields]);
    } catch (PDOException $e) {
        // Tabla no existe todavía
        echo json_encode(['success' => true, 'data' => [], 'warning' => 'Custom fields tables not installed yet']);
    }
}

function getCustomField($pdo) {
    $fieldId = $_GET['id'] ?? null;
    
    if (!$fieldId) {
        throw new Exception('id es requerido');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM custom_fields WHERE id = ?");
    $stmt->execute([$fieldId]);
    $field = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$field) {
        throw new Exception('Campo no encontrado');
    }
    
    if ($field['field_options']) {
        $field['field_options'] = json_decode($field['field_options'], true);
    }
    if ($field['validation_rules']) {
        $field['validation_rules'] = json_decode($field['validation_rules'], true);
    }
    
    // Obtener opciones si es select/multiselect
    if (in_array($field['field_type'], ['select', 'multiselect'])) {
        $stmt = $pdo->prepare("
            SELECT * FROM custom_field_options 
            WHERE field_id = ? AND is_active = 1
            ORDER BY display_order
        ");
        $stmt->execute([$fieldId]);
        $field['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
function getFieldValues($pdo) {
    $entityType = $_GET['entity_type'] ?? null;
    $entityIds = $_GET['entity_ids'] ?? null;

    if (!$entityType) {
        throw new Exception('entity_type es requerido');
    }

    try {
        // Obtener campos para esta entidad
        $stmt = $pdo->prepare("SELECT id, field_name FROM custom_fields WHERE entity_type = ? AND is_visible = 1");
        $stmt->execute([$entityType]);
        $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);    // Obtener campos para esta entidad
    $stmt = $pdo->prepare("SELECT id, field_name FROM custom_fields WHERE entity_type = ? AND is_visible = 1");
    $stmt->execute([$entityType]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fieldIds = array_column($fields, 'id');
    $fieldMap = array_column($fields, 'field_name', 'id');
    
    if (empty($fieldIds)) {
        echo json_encode(['success' => true, 'data' => []]);
        return;
    }
    
    // Construir query para valores
    $query = "SELECT field_id, entity_id, field_value FROM custom_field_values WHERE field_id IN (" . implode(',', array_fill(0, count($fieldIds), '?')) . ")";
    $params = $fieldIds;
    
    if ($entityIds) {
        $ids = explode(',', $entityIds);
        $query .= " AND entity_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        $params = array_merge($params, $ids);
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $values = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar por entity_id
    $organized = [];
    foreach ($values as $value) {
        $entityId = $value['entity_id'];
        $fieldName = $fieldMap[$value['field_id']];
        
        if (!isset($organized[$entityId])) {
            $organized[$entityId] = ['entity_id' => $entityId];
        }
        
        $organized[$entityId][$fieldName] = $value['field_value'];
    }
    
    echo json_encode(['success' => true, 'data' => array_values($organized)]);
    echo json_encode(['success' => true, 'data' => array_values($organized)]);
    } catch (PDOException $e) {
function getCustomViews($pdo) {
    $entityType = $_GET['entity_type'] ?? null;

    if (!$entityType) {
        throw new Exception('entity_type es requerido');
    }

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM custom_views
            WHERE entity_type = ? AND is_public = 1
            ORDER BY is_default DESC, view_name
        ");
        $stmt->execute([$entityType]);
        $views = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($views as &$view) {
            if ($view['visible_fields']) {
                $view['visible_fields'] = json_decode($view['visible_fields'], true);
            }
            if ($view['filters']) {
                $view['filters'] = json_decode($view['filters'], true);
            }
        }

        echo json_encode(['success' => true, 'data' => $views]);
    } catch (PDOException $e) {
        // Tabla no existe todavía
        echo json_encode(['success' => true, 'data' => [], 'warning' => 'Custom views tables not installed yet']);
    }
}    
    echo json_encode(['success' => true, 'data' => $views]);
}

function getSyncUpdates($pdo) {
    $lastSync = $_GET['last_sync'] ?? null;
    $entityType = $_GET['entity_type'] ?? null;
    
    if (!$lastSync) {
        throw new Exception('last_sync es requerido');
    }
    
    $query = "SELECT * FROM sync_log WHERE changed_at > ?";
    $params = [$lastSync];
    
    if ($entityType) {
        $query .= " AND entity_type = ?";
        $params[] = $entityType;
    }
    
    $query .= " ORDER BY changed_at ASC LIMIT 100";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($updates as &$update) {
        if ($update['changed_fields']) {
            $update['changed_fields'] = json_decode($update['changed_fields'], true);
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $updates,
        'server_time' => date('Y-m-d H:i:s')
    ]);
}

// ==================== POST Handlers ====================

function handlePost($pdo, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create-field':
            createCustomField($pdo, $data);
            break;
        case 'update-value':
            updateFieldValue($pdo, $data);
            break;
        case 'create-view':
            createCustomView($pdo, $data);
            break;
        case 'reorder-fields':
            reorderFields($pdo, $data);
            break;
        default:
            throw new Exception('Acción no válida');
    }
}

function createCustomField($pdo, $data) {
    $required = ['entity_type', 'field_name', 'field_label', 'field_type'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("$field es requerido");
        }
    }
    
    // Obtener el siguiente display_order
    $stmt = $pdo->prepare("SELECT MAX(display_order) as max_order FROM custom_fields WHERE entity_type = ?");
    $stmt->execute([$data['entity_type']]);
    $maxOrder = $stmt->fetch(PDO::FETCH_ASSOC)['max_order'] ?? 0;
    
    $stmt = $pdo->prepare("
        INSERT INTO custom_fields 
        (entity_type, field_name, field_label, field_type, field_options, validation_rules, 
         default_value, is_required, is_visible, is_system, display_order, column_width, help_text)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['entity_type'],
        $data['field_name'],
        $data['field_label'],
        $data['field_type'],
        isset($data['field_options']) ? json_encode($data['field_options']) : null,
        isset($data['validation_rules']) ? json_encode($data['validation_rules']) : null,
        $data['default_value'] ?? null,
        $data['is_required'] ?? false,
        $data['is_visible'] ?? true,
        $data['is_system'] ?? false,
        $maxOrder + 1,
        $data['column_width'] ?? 'auto',
        $data['help_text'] ?? null
    ]);
    
    $fieldId = $pdo->lastInsertId();
    
    // Log para sync
    logSync($pdo, $data['entity_type'], $fieldId, 'field_created', ['field_name' => $data['field_name']]);
    
    echo json_encode(['success' => true, 'field_id' => $fieldId]);
}

function updateFieldValue($pdo, $data) {
    $required = ['field_id', 'entity_id', 'value'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            throw new Exception("$field es requerido");
        }
    }
    
    // Obtener valor anterior para auditoría
    $stmt = $pdo->prepare("SELECT field_value FROM custom_field_values WHERE field_id = ? AND entity_id = ?");
    $stmt->execute([$data['field_id'], $data['entity_id']]);
    $oldValue = $stmt->fetch(PDO::FETCH_ASSOC)['field_value'] ?? null;
    
    // Upsert valor
    $stmt = $pdo->prepare("
        INSERT INTO custom_field_values (field_id, entity_id, field_value)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE field_value = VALUES(field_value), updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$data['field_id'], $data['entity_id'], $data['value']]);
    
    // Guardar en historial
    if ($oldValue !== $data['value']) {
        $stmt = $pdo->prepare("
            INSERT INTO field_value_history (field_id, entity_id, old_value, new_value, changed_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['field_id'],
            $data['entity_id'],
            $oldValue,
            $data['value'],
            $data['changed_by'] ?? null
        ]);
        
        // Obtener entity_type para sync log
        $stmt = $pdo->prepare("SELECT entity_type, field_name FROM custom_fields WHERE id = ?");
        $stmt->execute([$data['field_id']]);
        $field = $stmt->fetch(PDO::FETCH_ASSOC);
        
        logSync($pdo, $field['entity_type'], $data['entity_id'], 'value_updated', [
            'field_name' => $field['field_name'],
            'old_value' => $oldValue,
            'new_value' => $data['value']
        ]);
    }
    
    echo json_encode(['success' => true]);
}

function createCustomView($pdo, $data) {
    $required = ['entity_type', 'view_name', 'visible_fields'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("$field es requerido");
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO custom_views 
        (entity_type, view_name, view_type, visible_fields, filters, sort_by, sort_order, is_default, is_public, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $data['entity_type'],
        $data['view_name'],
        $data['view_type'] ?? 'table',
        json_encode($data['visible_fields']),
        isset($data['filters']) ? json_encode($data['filters']) : null,
        $data['sort_by'] ?? null,
        $data['sort_order'] ?? 'ASC',
        $data['is_default'] ?? false,
        $data['is_public'] ?? true,
        $data['created_by'] ?? null
    ]);
    
    $viewId = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'view_id' => $viewId]);
}

function reorderFields($pdo, $data) {
    if (empty($data['field_orders']) || !is_array($data['field_orders'])) {
        throw new Exception('field_orders es requerido y debe ser un array');
    }
    
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("UPDATE custom_fields SET display_order = ? WHERE id = ?");
        
        foreach ($data['field_orders'] as $fieldId => $order) {
            $stmt->execute([$order, $fieldId]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// ==================== PUT Handlers ====================

function handlePut($pdo, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update-field':
            updateCustomField($pdo, $data);
            break;
        case 'toggle-visibility':
            toggleFieldVisibility($pdo, $data);
            break;
        default:
            throw new Exception('Acción no válida');
    }
}

function updateCustomField($pdo, $data) {
    if (empty($data['id'])) {
        throw new Exception('id es requerido');
    }
    
    $updates = [];
    $params = [];
    
    $allowedFields = [
        'field_label', 'field_options', 'validation_rules', 'default_value',
        'is_required', 'is_visible', 'column_width', 'help_text'
    ];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            if (in_array($field, ['field_options', 'validation_rules'])) {
                $params[] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
            } else {
                $params[] = $data[$field];
            }
        }
    }
    
    if (empty($updates)) {
        throw new Exception('No hay campos para actualizar');
    }
    
    $params[] = $data['id'];
    
    $sql = "UPDATE custom_fields SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode(['success' => true]);
}

function toggleFieldVisibility($pdo, $data) {
    if (empty($data['id'])) {
        throw new Exception('id es requerido');
    }
    
    $stmt = $pdo->prepare("UPDATE custom_fields SET is_visible = NOT is_visible WHERE id = ?");
    $stmt->execute([$data['id']]);
    
    echo json_encode(['success' => true]);
}

// ==================== DELETE Handlers ====================

function handleDelete($pdo, $action) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'delete-field':
            deleteCustomField($pdo, $data);
            break;
        default:
            throw new Exception('Acción no válida');
    }
}

function deleteCustomField($pdo, $data) {
    if (empty($data['id'])) {
        throw new Exception('id es requerido');
    }
    
    // Verificar que no sea un campo del sistema
    $stmt = $pdo->prepare("SELECT is_system, entity_type FROM custom_fields WHERE id = ?");
    $stmt->execute([$data['id']]);
    $field = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$field) {
        throw new Exception('Campo no encontrado');
    }
    
    if ($field['is_system']) {
        throw new Exception('No se pueden eliminar campos del sistema');
    }
    
    $stmt = $pdo->prepare("DELETE FROM custom_fields WHERE id = ?");
    $stmt->execute([$data['id']]);
    
    logSync($pdo, $field['entity_type'], $data['id'], 'field_deleted', []);
    
    echo json_encode(['success' => true]);
}

// ==================== Helper Functions ====================

function logSync($pdo, $entityType, $entityId, $action, $changedFields) {
    $stmt = $pdo->prepare("
        INSERT INTO sync_log (entity_type, entity_id, action, changed_fields)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $entityType,
        $entityId,
        $action,
        json_encode($changedFields)
    ]);
}
