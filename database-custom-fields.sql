-- ================================================
-- SISTEMA DE CAMPOS CUSTOMIZABLES (CUSTOM FIELDS)
-- ================================================

-- Tabla para definir tipos de campos disponibles
CREATE TABLE IF NOT EXISTS field_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    type_label VARCHAR(100) NOT NULL,
    icon VARCHAR(10),
    validation_rules TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar tipos de campos predefinidos
INSERT INTO field_types (type_name, type_label, icon, validation_rules) VALUES
('text', 'Texto', '📝', '{"min_length": 0, "max_length": 500}'),
('number', 'Número', '🔢', '{"min": null, "max": null, "decimals": 2}'),
('select', 'Dropdown', '📋', '{"options": [], "multiple": false}'),
('multiselect', 'Selección Múltiple', '☑️', '{"options": [], "max_selections": null}'),
('date', 'Fecha', '📅', '{"format": "Y-m-d", "min_date": null, "max_date": null}'),
('datetime', 'Fecha y Hora', '🕐', '{"format": "Y-m-d H:i:s", "timezone": "America/Argentina/Buenos_Aires"}'),
('email', 'Email', '📧', '{"pattern": "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$"}'),
('phone', 'Teléfono', '📞', '{"pattern": "^[+]?[(]?[0-9]{1,4}[)]?[-\\s\\.]?[(]?[0-9]{1,4}[)]?[-\\s\\.]?[0-9]{1,9}$"}'),
('url', 'URL', '🔗', '{"pattern": "^https?://[^\\s/$.?#].[^\\s]*$"}'),
('currency', 'Moneda', '💰', '{"currency": "ARS", "decimals": 2, "symbol": "$"}'),
('percentage', 'Porcentaje', '📊', '{"min": 0, "max": 100, "decimals": 1}'),
('checkbox', 'Checkbox', '✅', '{"default": false}'),
('textarea', 'Texto Largo', '📄', '{"min_length": 0, "max_length": 5000, "rows": 4}'),
('color', 'Color', '🎨', '{"format": "hex"}'),
('file', 'Archivo', '📎', '{"max_size_mb": 10, "allowed_types": ["pdf", "doc", "docx", "xls", "xlsx"]}'),
('image', 'Imagen', '🖼️', '{"max_size_mb": 5, "allowed_types": ["jpg", "jpeg", "png", "gif", "webp"]}'),
('rating', 'Calificación', '⭐', '{"min": 1, "max": 5, "step": 1}'),
('priority', 'Prioridad', '🔥', '{"options": ["Baja", "Media", "Alta", "Urgente"]}'),
('user', 'Usuario', '👤', '{"multiple": false}'),
('tags', 'Etiquetas', '🏷️', '{"max_tags": 10, "allow_new": true}'),
('relation', 'Relación', '🔗', '{"target_table": null, "display_field": null}')
ON DUPLICATE KEY UPDATE type_label = VALUES(type_label);

-- Tabla para definir entidades que pueden tener custom fields
CREATE TABLE IF NOT EXISTS custom_field_entities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_name VARCHAR(50) NOT NULL UNIQUE,
    entity_label VARCHAR(100) NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    icon VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar entidades que soportan custom fields
INSERT INTO custom_field_entities (entity_name, entity_label, table_name, icon) VALUES
('project', 'Proyectos', 'projects', '📁'),
('client', 'Clientes', 'clients', '👥'),
('phase', 'Fases', 'project_phases', '📌'),
('task', 'Tareas', 'tasks', '✅')
ON DUPLICATE KEY UPDATE entity_label = VALUES(entity_label);

-- Tabla de definición de custom fields
CREATE TABLE IF NOT EXISTS custom_fields (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_type VARCHAR(50) NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_label VARCHAR(200) NOT NULL,
    field_type VARCHAR(50) NOT NULL,
    field_options TEXT,
    validation_rules TEXT,
    default_value TEXT,
    is_required BOOLEAN DEFAULT FALSE,
    is_visible BOOLEAN DEFAULT TRUE,
    is_system BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    column_width VARCHAR(20) DEFAULT 'auto',
    help_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_field (entity_type, field_name),
    FOREIGN KEY (entity_type) REFERENCES custom_field_entities(entity_name) ON DELETE CASCADE,
    FOREIGN KEY (field_type) REFERENCES field_types(type_name) ON DELETE RESTRICT,
    INDEX idx_entity_type (entity_type),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar campos predeterminados para PROYECTOS
INSERT INTO custom_fields (entity_type, field_name, field_label, field_type, field_options, is_required, is_system, display_order, column_width) VALUES
('project', 'project_name', 'Nombre del Proyecto', 'text', '{"max_length": 255}', TRUE, TRUE, 1, '250px'),
('project', 'client_id', 'Cliente', 'relation', '{"target_table": "clients", "display_field": "client_name"}', TRUE, TRUE, 2, '200px'),
('project', 'status', 'Estado', 'select', '{"options": ["quote", "pending_approval", "approved", "in_progress", "review", "testing", "client_review", "completed", "on_hold", "cancelled"]}', TRUE, TRUE, 3, '150px'),
('project', 'project_type', 'Tipo', 'select', '{"options": ["web", "mobile", "desktop", "api", "design", "consulting", "maintenance", "other"]}', FALSE, TRUE, 4, '120px'),
('project', 'estimated_hours', 'Horas Estimadas', 'number', '{"min": 0, "decimals": 1}', FALSE, TRUE, 5, '130px'),
('project', 'hourly_rate', 'Tarifa por Hora', 'currency', '{"currency": "ARS", "decimals": 2}', FALSE, TRUE, 6, '130px'),
('project', 'total_budget', 'Presupuesto Total', 'currency', '{"currency": "ARS", "decimals": 2}', FALSE, TRUE, 7, '150px'),
('project', 'start_date', 'Fecha de Inicio', 'date', '{}', FALSE, TRUE, 8, '130px'),
('project', 'estimated_end_date', 'Fecha Estimada Fin', 'date', '{}', FALSE, TRUE, 9, '150px'),
('project', 'priority', 'Prioridad', 'priority', '{}', FALSE, TRUE, 10, '120px'),
('project', 'progress', 'Progreso', 'percentage', '{}', FALSE, TRUE, 11, '100px'),
('project', 'description', 'Descripción', 'textarea', '{"rows": 3}', FALSE, TRUE, 12, '300px')
ON DUPLICATE KEY UPDATE field_label = VALUES(field_label);

-- Insertar campos predeterminados para CLIENTES
INSERT INTO custom_fields (entity_type, field_name, field_label, field_type, field_options, is_required, is_system, display_order, column_width) VALUES
('client', 'client_name', 'Nombre', 'text', '{"max_length": 255}', TRUE, TRUE, 1, '200px'),
('client', 'email', 'Email', 'email', '{}', TRUE, TRUE, 2, '200px'),
('client', 'phone', 'Teléfono', 'phone', '{}', FALSE, TRUE, 3, '150px'),
('client', 'company', 'Empresa', 'text', '{"max_length": 255}', FALSE, TRUE, 4, '200px'),
('client', 'website', 'Sitio Web', 'url', '{}', FALSE, FALSE, 5, '200px'),
('client', 'country', 'País', 'text', '{}', FALSE, FALSE, 6, '120px'),
('client', 'industry', 'Industria', 'select', '{"options": ["Tecnología", "Retail", "Servicios", "Manufactura", "Salud", "Educación", "Entretenimiento", "Otro"]}', FALSE, FALSE, 7, '150px'),
('client', 'client_type', 'Tipo de Cliente', 'select', '{"options": ["Individual", "Startup", "PyME", "Empresa", "Agencia", "Gobierno"]}', FALSE, FALSE, 8, '150px'),
('client', 'status', 'Estado', 'select', '{"options": ["lead", "active", "inactive", "vip"]}', FALSE, TRUE, 9, '120px'),
('client', 'notes', 'Notas', 'textarea', '{"rows": 3}', FALSE, FALSE, 10, '300px')
ON DUPLICATE KEY UPDATE field_label = VALUES(field_label);

-- Tabla de valores de custom fields
CREATE TABLE IF NOT EXISTS custom_field_values (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    field_id INT NOT NULL,
    entity_id INT NOT NULL,
    field_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES custom_fields(id) ON DELETE CASCADE,
    UNIQUE KEY unique_field_value (field_id, entity_id),
    INDEX idx_entity (entity_id),
    INDEX idx_field (field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para opciones de campos select/multiselect customizables
CREATE TABLE IF NOT EXISTS custom_field_options (
    id INT PRIMARY KEY AUTO_INCREMENT,
    field_id INT NOT NULL,
    option_value VARCHAR(255) NOT NULL,
    option_label VARCHAR(255) NOT NULL,
    option_color VARCHAR(7),
    option_icon VARCHAR(10),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES custom_fields(id) ON DELETE CASCADE,
    INDEX idx_field_id (field_id),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para historial de cambios (auditoría)
CREATE TABLE IF NOT EXISTS field_value_history (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    field_id INT NOT NULL,
    entity_id INT NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES custom_fields(id) ON DELETE CASCADE,
    INDEX idx_entity (entity_id),
    INDEX idx_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para vistas/layouts personalizados
CREATE TABLE IF NOT EXISTS custom_views (
    id INT PRIMARY KEY AUTO_INCREMENT,
    entity_type VARCHAR(50) NOT NULL,
    view_name VARCHAR(100) NOT NULL,
    view_type VARCHAR(30) DEFAULT 'table',
    visible_fields TEXT,
    filters TEXT,
    sort_by VARCHAR(100),
    sort_order VARCHAR(4) DEFAULT 'ASC',
    is_default BOOLEAN DEFAULT FALSE,
    is_public BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entity_type) REFERENCES custom_field_entities(entity_name) ON DELETE CASCADE,
    INDEX idx_entity_type (entity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar vista predeterminada para proyectos
INSERT INTO custom_views (entity_type, view_name, view_type, visible_fields, is_default, is_public) VALUES
('project', 'Vista Predeterminada', 'table', '["project_name", "client_id", "status", "project_type", "start_date", "estimated_end_date", "priority", "progress"]', TRUE, TRUE)
ON DUPLICATE KEY UPDATE view_name = VALUES(view_name);

-- Tabla para sincronización en tiempo real
CREATE TABLE IF NOT EXISTS sync_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    action VARCHAR(20) NOT NULL,
    changed_fields TEXT,
    changed_by INT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_changed_at (changed_at),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para notificaciones en tiempo real
CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    notification_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    entity_type VARCHAR(50),
    entity_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
