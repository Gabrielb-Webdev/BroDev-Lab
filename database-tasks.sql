-- ============================================
-- TABLA TASKS - Sistema de Tareas para Board View
-- ============================================

CREATE TABLE IF NOT EXISTS tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('todo', 'in_progress', 'review', 'done') DEFAULT 'todo',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    project_id INT,
    assignee_id INT,
    due_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assignee_id) REFERENCES admins(id) ON DELETE SET NULL,
    
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_project (project_id),
    INDEX idx_assignee (assignee_id),
    INDEX idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS DE EJEMPLO
-- ============================================

-- Insertar tareas de ejemplo si no existen
INSERT INTO tasks (title, description, status, priority, project_id, assignee_id, due_date, created_at) VALUES
('Diseñar landing page', 'Crear mockups en Figma para la página principal del proyecto', 'in_progress', 'high', 1, 1, DATE_ADD(CURDATE(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
('Implementar autenticación JWT', 'Sistema de login con tokens JWT y refresh tokens', 'todo', 'urgent', 1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
('Escribir documentación técnica', 'README.md completo con guía de instalación y uso', 'review', 'normal', 1, 1, DATE_ADD(CURDATE(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
('Optimizar queries de base de datos', 'Agregar índices y mejorar performance de consultas', 'done', 'low', 1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
('Crear API REST para usuarios', 'Endpoints CRUD completos para gestión de usuarios', 'todo', 'high', 1, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
('Implementar sistema de notificaciones', 'Push notifications y email alerts', 'in_progress', 'normal', 1, 1, DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Testing de integración', 'Pruebas end-to-end con Jest y Cypress', 'todo', 'normal', 1, 1, DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Deploy a producción', 'Configurar servidor y dominio', 'todo', 'urgent', 1, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), NOW())
ON DUPLICATE KEY UPDATE id=id;

-- ============================================
-- ÍNDICES ADICIONALES PARA PERFORMANCE
-- ============================================

-- Índice compuesto para búsquedas frecuentes
CREATE INDEX IF NOT EXISTS idx_status_priority ON tasks(status, priority);
CREATE INDEX IF NOT EXISTS idx_project_status ON tasks(project_id, status);
CREATE INDEX IF NOT EXISTS idx_assignee_status ON tasks(assignee_id, status);

-- ============================================
-- COMENTARIOS EN TAREAS (para futura implementación)
-- ============================================

CREATE TABLE IF NOT EXISTS task_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    mentions JSON, -- Array de user_ids mencionados [@usuario]
    attachments JSON, -- Array de URLs de archivos adjuntos
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES admins(id) ON DELETE CASCADE,
    
    INDEX idx_task (task_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ACTIVIDAD DE TAREAS (audit trail)
-- ============================================

CREATE TABLE IF NOT EXISTS task_activity (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    user_id INT,
    action_type ENUM('created', 'updated', 'deleted', 'status_changed', 'assigned', 'commented') NOT NULL,
    old_value TEXT,
    new_value TEXT,
    metadata JSON, -- Información adicional del cambio
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES admins(id) ON DELETE SET NULL,
    
    INDEX idx_task (task_id),
    INDEX idx_user (user_id),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SUBTAREAS (hierarchía de tareas)
-- ============================================

CREATE TABLE IF NOT EXISTS subtasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_task_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('todo', 'in_progress', 'done') DEFAULT 'todo',
    assignee_id INT,
    position INT DEFAULT 0, -- Para ordenamiento
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (parent_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (assignee_id) REFERENCES admins(id) ON DELETE SET NULL,
    
    INDEX idx_parent (parent_task_id),
    INDEX idx_position (parent_task_id, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ETIQUETAS/TAGS PARA TAREAS
-- ============================================

CREATE TABLE IF NOT EXISTS task_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#3b82f6', -- Hex color
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS task_tag_relations (
    task_id INT NOT NULL,
    tag_id INT NOT NULL,
    
    PRIMARY KEY (task_id, tag_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES task_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VISTAS PARA REPORTES
-- ============================================

CREATE OR REPLACE VIEW v_tasks_summary AS
SELECT 
    t.id,
    t.title,
    t.status,
    t.priority,
    t.due_date,
    p.name as project_name,
    p.color as project_color,
    u.username as assignee_name,
    u.email as assignee_email,
    (SELECT COUNT(*) FROM subtasks WHERE parent_task_id = t.id) as subtask_count,
    (SELECT COUNT(*) FROM subtasks WHERE parent_task_id = t.id AND status = 'done') as completed_subtasks,
    (SELECT COUNT(*) FROM task_comments WHERE task_id = t.id) as comment_count,
    DATEDIFF(t.due_date, CURDATE()) as days_until_due,
    CASE 
        WHEN t.due_date < CURDATE() AND t.status != 'done' THEN 'overdue'
        WHEN t.due_date = CURDATE() AND t.status != 'done' THEN 'due_today'
        WHEN DATEDIFF(t.due_date, CURDATE()) <= 3 AND t.status != 'done' THEN 'due_soon'
        ELSE 'on_track'
    END as urgency_status,
    t.created_at,
    t.updated_at
FROM tasks t
LEFT JOIN projects p ON t.project_id = p.id
LEFT JOIN admins u ON t.assignee_id = u.id;

-- ============================================
-- TRIGGERS PARA AUDIT TRAIL
-- ============================================

DELIMITER //

CREATE TRIGGER IF NOT EXISTS task_after_insert
AFTER INSERT ON tasks
FOR EACH ROW
BEGIN
    INSERT INTO task_activity (task_id, user_id, action_type, new_value, metadata)
    VALUES (NEW.id, NEW.assignee_id, 'created', NEW.title, JSON_OBJECT('status', NEW.status, 'priority', NEW.priority));
END//

CREATE TRIGGER IF NOT EXISTS task_after_update
AFTER UPDATE ON tasks
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO task_activity (task_id, user_id, action_type, old_value, new_value, metadata)
        VALUES (NEW.id, NEW.assignee_id, 'status_changed', OLD.status, NEW.status, JSON_OBJECT('title', NEW.title));
    END IF;
    
    IF OLD.assignee_id != NEW.assignee_id THEN
        INSERT INTO task_activity (task_id, user_id, action_type, old_value, new_value, metadata)
        VALUES (NEW.id, NEW.assignee_id, 'assigned', OLD.assignee_id, NEW.assignee_id, JSON_OBJECT('title', NEW.title));
    END IF;
END//

DELIMITER ;

-- ============================================
-- FIN DEL SCRIPT
-- ============================================

-- Mostrar resumen
SELECT 'Tabla tasks creada exitosamente' as mensaje;
SELECT COUNT(*) as total_tareas FROM tasks;
SELECT status, COUNT(*) as cantidad FROM tasks GROUP BY status;
