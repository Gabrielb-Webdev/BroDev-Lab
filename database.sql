-- ============================================
-- BASE DE DATOS PARA PORTAL DE CLIENTES
-- BroDev Lab - Client Management System
-- ============================================

-- Tabla de Clientes
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(50),
    company VARCHAR(255),
    access_code VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Tabla de Proyectos
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    project_name VARCHAR(255) NOT NULL,
    project_type VARCHAR(100),
    description TEXT,
    budget DECIMAL(10, 2),
    hourly_rate DECIMAL(10, 2) DEFAULT 50.00,
    status ENUM('pending', 'in_progress', 'completed', 'on_hold', 'cancelled') DEFAULT 'pending',
    start_date DATE,
    estimated_end_date DATE,
    actual_end_date DATE NULL,
    total_time_seconds INT DEFAULT 0,
    progress_percentage DECIMAL(5, 2) DEFAULT 0.00,
    assigned_to VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- Tabla de Fases del Proyecto
CREATE TABLE IF NOT EXISTS project_phases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    phase_number INT NOT NULL,
    phase_name VARCHAR(255) NOT NULL,
    description TEXT,
    estimated_hours DECIMAL(6, 2),
    actual_time_seconds INT DEFAULT 0,
    status ENUM('pending', 'in_progress', 'completed', 'paused') DEFAULT 'pending',
    start_date DATETIME NULL,
    end_date DATETIME NULL,
    assigned_to VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Tabla de Sesiones de Tiempo (Time Tracking)
CREATE TABLE IF NOT EXISTS time_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    phase_id INT,
    session_description VARCHAR(500),
    start_time DATETIME NOT NULL,
    end_time DATETIME NULL,
    duration_seconds INT DEFAULT 0,
    is_active BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (phase_id) REFERENCES project_phases(id) ON DELETE SET NULL
);

-- Tabla de Actividades/Updates del Proyecto
CREATE TABLE IF NOT EXISTS project_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    phase_id INT NULL,
    activity_type ENUM('phase_started', 'phase_completed', 'milestone', 'comment', 'file_uploaded', 'status_change') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_url VARCHAR(500) NULL,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (phase_id) REFERENCES project_phases(id) ON DELETE SET NULL
);

-- Tabla de Documentos/Archivos
CREATE TABLE IF NOT EXISTS project_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    phase_id INT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_by VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (phase_id) REFERENCES project_phases(id) ON DELETE SET NULL
);

-- Tabla de Mensajes/Comunicación
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    sender_type ENUM('client', 'admin') NOT NULL,
    sender_name VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Tabla de Notificaciones
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    project_id INT,
    notification_type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

-- Tabla de Administradores
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin', 'developer') DEFAULT 'developer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Insertar admin por defecto (password: admin123 - CAMBIAR DESPUÉS)
INSERT INTO admins (username, password_hash, full_name, email, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gabriel Dev', 'admin@brodevlab.com', 'super_admin');

-- Índices para mejorar performance
CREATE INDEX idx_client_email ON clients(email);
CREATE INDEX idx_project_status ON projects(status);
CREATE INDEX idx_project_client ON projects(client_id);
CREATE INDEX idx_phase_project ON project_phases(project_id);
CREATE INDEX idx_time_project ON time_sessions(project_id);
CREATE INDEX idx_time_active ON time_sessions(is_active);
CREATE INDEX idx_activities_project ON project_activities(project_id);
CREATE INDEX idx_messages_project ON messages(project_id);
CREATE INDEX idx_notifications_client ON notifications(client_id);

-- Vista para resumen de proyectos
CREATE VIEW project_summary AS
SELECT 
    p.id,
    p.project_name,
    c.name as client_name,
    c.email as client_email,
    p.status,
    p.progress_percentage,
    p.total_time_seconds,
    p.hourly_rate,
    (p.total_time_seconds / 3600.0) * p.hourly_rate as total_cost,
    COUNT(DISTINCT pp.id) as total_phases,
    SUM(CASE WHEN pp.status = 'completed' THEN 1 ELSE 0 END) as completed_phases,
    p.start_date,
    p.estimated_end_date
FROM projects p
JOIN clients c ON p.client_id = c.id
LEFT JOIN project_phases pp ON p.id = pp.project_id
GROUP BY p.id;
