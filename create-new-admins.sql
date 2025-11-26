-- ============================================
-- Crear Nuevos Usuarios Admin
-- BroDev Lab
-- ============================================

-- Eliminar el usuario admin existente
DELETE FROM admin_users WHERE username = 'admin';

-- Crear usuario: Gabriel Bustos
-- Username: gabriel / Password: Gabriel2024!
INSERT INTO admin_users (username, email, password_hash, full_name, role, status) 
VALUES (
    'gabriel',
    'gabriel@brodevlab.com',
    '$2y$10$YzB5WqXJvHK4vN8jXF.7Z.TFDqN1Z/mK9eF1jZxFQqLH2WwZxnBGi', -- Password: Gabriel2024!
    'Gabriel Bustos',
    'super_admin',
    'active'
);

-- Crear usuario: Lautaro Magliano
-- Username: lautaro / Password: Lautaro2024!
INSERT INTO admin_users (username, email, password_hash, full_name, role, status) 
VALUES (
    'lautaro',
    'lautaro@brodevlab.com',
    '$2y$10$0F8xH5K4vB9rT2jWqL6zE.8Nf5jZxK3bQ4pD2Y/eH7vG8xA1cT9Nm', -- Password: Lautaro2024!
    'Lautaro Magliano',
    'super_admin',
    'active'
);

-- ============================================
-- CREDENCIALES DE ACCESO:
-- ============================================
-- Usuario 1:
--   Username: gabriel
--   Email: gabriel@brodevlab.com
--   Password: Gabriel2024!
--   Role: super_admin
--
-- Usuario 2:
--   Username: lautaro
--   Email: lautaro@brodevlab.com
--   Password: Lautaro2024!
--   Role: super_admin
-- ============================================
