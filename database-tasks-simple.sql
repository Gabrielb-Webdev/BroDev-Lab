-- ============================================
-- SISTEMA DE TAREAS - BroDev Lab
-- Versión simplificada sin triggers (compatible con Hostinger)
-- ============================================

-- TABLA PRINCIPAL DE TAREAS
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
    INDEX idx_due_date (due_date),
    INDEX idx_status_priority (status, priority),
    INDEX idx_project_status (project_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- COMENTARIOS EN TAREAS
CREATE TABLE IF NOT EXISTS task_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    mentions JSON,
    attachments JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES admins(id) ON DELETE CASCADE,
    INDEX idx_task (task_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ACTIVIDAD DE TAREAS (audit trail)
CREATE TABLE IF NOT EXISTS task_activity (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    user_id INT,
    action_type ENUM('created', 'updated', 'deleted', 'status_changed', 'assigned', 'commented') NOT NULL,
    old_value TEXT,
    new_value TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_task (task_id),
    INDEX idx_action (action_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SUBTAREAS
CREATE TABLE IF NOT EXISTS subtasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_task_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('todo', 'in_progress', 'done') DEFAULT 'todo',
    assignee_id INT,
    position INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (parent_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (assignee_id) REFERENCES admins(id) ON DELETE SET NULL,
    INDEX idx_parent (parent_task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ETIQUETAS
CREATE TABLE IF NOT EXISTS task_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#3b82f6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RELACION TAREAS-ETIQUETAS
CREATE TABLE IF NOT EXISTS task_tag_relations (
    task_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (task_id, tag_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES task_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
