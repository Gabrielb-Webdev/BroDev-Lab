-- ============================================
-- SISTEMA COMPLETO DE TAREAS - BroDev Lab
-- Incluye tablas base + datos de ejemplo
-- Versión completa con datos coherentes
-- ============================================

-- ============================================
-- PASO 1: TABLAS BASE (sin foreign keys)
-- ============================================

-- Tabla de Clientes (necesaria para projects)
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(50),
    company VARCHAR(255),
    access_code VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Administradores (necesaria para tasks)
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'developer') DEFAULT 'developer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PASO 2: TABLA DE PROYECTOS (depende de clients)
-- ============================================

CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    project_name VARCHAR(255) NOT NULL,
    project_type VARCHAR(100),
    description TEXT,
    budget DECIMAL(10, 2),
    hourly_rate DECIMAL(10, 2) DEFAULT 50.00,
    status ENUM('quote', 'pending_approval', 'approved', 'in_progress', 'review', 'testing', 'client_review', 'completed', 'on_hold', 'cancelled') DEFAULT 'quote',
    start_date DATE,
    estimated_end_date DATE,
    actual_end_date DATE NULL,
    total_time_seconds INT DEFAULT 0,
    progress_percentage DECIMAL(5, 2) DEFAULT 0.00,
    assigned_to VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PASO 3: SISTEMA DE TAREAS (depende de projects y admins)
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
    INDEX idx_action (action_type),
    INDEX idx_created (created_at)
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
    INDEX idx_parent (parent_task_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ETIQUETAS
CREATE TABLE IF NOT EXISTS task_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#3b82f6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RELACION TAREAS-ETIQUETAS
CREATE TABLE IF NOT EXISTS task_tag_relations (
    task_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (task_id, tag_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES task_tags(id) ON DELETE CASCADE,
    INDEX idx_task (task_id),
    INDEX idx_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PASO 4: DATOS DE EJEMPLO
-- ============================================

-- Insertar cliente de ejemplo (solo si no existe)
INSERT IGNORE INTO clients (id, name, email, phone, company, access_code, status) 
VALUES (1, 'Cliente Demo', 'demo@cliente.com', '+1234567890', 'Demo Corp', 'DEMO2024', 'active');

-- Insertar admin de ejemplo (solo si no existe)
-- Password: admin123 (cambiar en producción)
INSERT IGNORE INTO admins (id, username, password_hash, full_name, email, role) 
VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gabriel Bustos', 'admin@brodevlab.com', 'super_admin');

-- Insertar proyecto de ejemplo (solo si no existe)
INSERT IGNORE INTO projects (id, client_id, project_name, project_type, description, status, start_date, estimated_end_date, assigned_to) 
VALUES (1, 1, 'Sistema de Gestión de Tareas', 'Web Development', 'Desarrollo de un sistema completo de gestión de tareas estilo ClickUp', 'in_progress', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Gabriel Bustos');

-- Insertar etiquetas por defecto
INSERT IGNORE INTO task_tags (id, name, color) VALUES
(1, 'Bug', '#ef4444'),
(2, 'Feature', '#3b82f6'),
(3, 'Enhancement', '#8b5cf6'),
(4, 'Documentation', '#06b6d4'),
(5, 'Urgent', '#dc2626'),
(6, 'Backend', '#10b981'),
(7, 'Frontend', '#f59e0b'),
(8, 'Database', '#ec4899');

-- Insertar tareas de ejemplo
INSERT IGNORE INTO tasks (id, title, description, status, priority, project_id, assignee_id, due_date, created_at) VALUES
(1, 'Diseñar landing page', 'Crear mockups en Figma para la página principal del proyecto', 'in_progress', 'high', 1, 1, DATE_ADD(CURDATE(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),
(2, 'Implementar autenticación JWT', 'Sistema de login con tokens JWT y refresh tokens', 'todo', 'urgent', 1, 1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(3, 'Escribir documentación técnica', 'README.md completo con guía de instalación y uso', 'review', 'normal', 1, 1, DATE_ADD(CURDATE(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 4 DAY)),
(4, 'Optimizar queries de base de datos', 'Agregar índices y mejorar performance de consultas', 'done', 'low', 1, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
(5, 'Crear API REST para usuarios', 'Endpoints CRUD completos para gestión de usuarios', 'todo', 'high', 1, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
(6, 'Implementar sistema de notificaciones', 'Push notifications y email alerts', 'in_progress', 'normal', 1, 1, DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(7, 'Testing de integración', 'Pruebas end-to-end con Jest y Cypress', 'todo', 'normal', 1, 1, DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(8, 'Deploy a producción', 'Configurar servidor y dominio', 'todo', 'high', 1, 1, DATE_ADD(CURDATE(), INTERVAL 15 DAY), NOW());

-- Insertar algunas relaciones de etiquetas
INSERT IGNORE INTO task_tag_relations (task_id, tag_id) VALUES
(1, 7), -- Diseñar landing page -> Frontend
(2, 6), -- Autenticación -> Backend
(2, 5), -- Autenticación -> Urgent
(3, 4), -- Documentación -> Documentation
(4, 8), -- Optimizar queries -> Database
(5, 6), -- API REST -> Backend
(5, 2), -- API REST -> Feature
(6, 6), -- Notificaciones -> Backend
(7, 2), -- Testing -> Feature
(8, 5); -- Deploy -> Urgent
