-- ============================================
-- ACTUALIZACIÓN DE ESTADOS DE PROYECTOS
-- Ejecutar este script en PHPMyAdmin
-- ============================================

-- Modificar la columna status para incluir todos los estados nuevos
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

-- Migrar estados antiguos a los nuevos
UPDATE projects SET status = 'quote' WHERE status = 'pending';
UPDATE projects SET status = 'completed' WHERE status = 'completed';
UPDATE projects SET status = 'in_progress' WHERE status = 'in_progress';
UPDATE projects SET status = 'on_hold' WHERE status = 'on_hold';
UPDATE projects SET status = 'cancelled' WHERE status = 'cancelled';
