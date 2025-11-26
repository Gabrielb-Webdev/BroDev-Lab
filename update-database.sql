-- ============================================
-- ACTUALIZACIÓN DE BASE DE DATOS
-- Sistema completo de tracking de proyectos
-- ============================================

-- Mejorar estados de proyectos
ALTER TABLE projects 
MODIFY COLUMN status ENUM(
    'quote', 
    'pending_approval', 
    'approved', 
    'in_progress', 
    'review', 
    'testing', 
    'client_review', 
    'completed', 
    'on_hold', 
    'cancelled'
) DEFAULT 'quote';

-- Mejorar estados de fases
ALTER TABLE project_phases 
MODIFY COLUMN status ENUM(
    'not_started', 
    'in_progress', 
    'paused', 
    'completed', 
    'blocked'
) DEFAULT 'not_started';

-- Agregar índice para timer activo
ALTER TABLE time_sessions 
ADD INDEX idx_active_session (is_active, project_id, phase_id);

-- Tabla para milestone/hitos del proyecto
CREATE TABLE IF NOT EXISTS project_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    milestone_name VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE,
    status ENUM('pending', 'completed', 'overdue') DEFAULT 'pending',
    completed_at DATETIME NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Vista para estadísticas de proyecto
CREATE OR REPLACE VIEW project_stats AS
SELECT 
    p.id,
    p.project_name,
    p.client_id,
    p.status,
    p.progress_percentage,
    COUNT(DISTINCT pp.id) as total_phases,
    SUM(CASE WHEN pp.status = 'completed' THEN 1 ELSE 0 END) as completed_phases,
    SUM(CASE WHEN pp.status = 'in_progress' THEN 1 ELSE 0 END) as active_phases,
    COALESCE(SUM(ts.duration_seconds), 0) as total_time_seconds,
    COALESCE(SUM(ts.duration_seconds) / 3600, 0) as total_hours,
    COALESCE(SUM(ts.duration_seconds) / 3600 * p.hourly_rate, 0) as total_cost
FROM projects p
LEFT JOIN project_phases pp ON p.id = pp.project_id
LEFT JOIN time_sessions ts ON p.id = ts.project_id
GROUP BY p.id;
