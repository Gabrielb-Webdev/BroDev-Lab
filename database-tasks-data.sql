-- ============================================
-- DATOS DE EJEMPLO PARA SISTEMA DE TAREAS
-- Ejecutar DESPUÉS de database-tasks-simple.sql
-- ============================================

INSERT INTO tasks (title, description, status, priority, project_id, assignee_id, due_date, created_at) 
SELECT * FROM (SELECT 
    'Diseñar landing page' as title,
    'Crear mockups en Figma para la página principal del proyecto' as description,
    'in_progress' as status,
    'high' as priority,
    1 as project_id,
    1 as assignee_id,
    DATE_ADD(CURDATE(), INTERVAL 4 DAY) as due_date,
    DATE_SUB(NOW(), INTERVAL 6 DAY) as created_at
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tasks WHERE title = 'Diseñar landing page');

INSERT INTO tasks (title, description, status, priority, project_id, assignee_id, due_date, created_at) 
SELECT * FROM (SELECT 
    'Implementar autenticación JWT' as title,
    'Sistema de login con tokens JWT y refresh tokens' as description,
    'todo' as status,
    'urgent' as priority,
    1 as project_id,
    1 as assignee_id,
    DATE_ADD(CURDATE(), INTERVAL 2 DAY) as due_date,
    DATE_SUB(NOW(), INTERVAL 5 DAY) as created_at
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tasks WHERE title = 'Implementar autenticación JWT');

INSERT INTO tasks (title, description, status, priority, project_id, assignee_id, due_date, created_at) 
SELECT * FROM (SELECT 
    'Escribir documentación técnica' as title,
    'README.md completo con guía de instalación y uso' as description,
    'review' as status,
    'normal' as priority,
    1 as project_id,
    1 as assignee_id,
    DATE_ADD(CURDATE(), INTERVAL 9 DAY) as due_date,
    DATE_SUB(NOW(), INTERVAL 4 DAY) as created_at
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tasks WHERE title = 'Escribir documentación técnica');

INSERT INTO tasks (title, description, status, priority, project_id, assignee_id, due_date, created_at) 
SELECT * FROM (SELECT 
    'Optimizar queries de base de datos' as title,
    'Agregar índices y mejorar performance de consultas' as description,
    'done' as status,
    'low' as priority,
    1 as project_id,
    1 as assignee_id,
    DATE_SUB(CURDATE(), INTERVAL 1 DAY) as due_date,
    DATE_SUB(NOW(), INTERVAL 8 DAY) as created_at
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tasks WHERE title = 'Optimizar queries de base de datos');

INSERT INTO tasks (title, description, status, priority, project_id, assignee_id, due_date, created_at) 
SELECT * FROM (SELECT 
    'Crear API REST para usuarios' as title,
    'Endpoints CRUD completos para gestión de usuarios' as description,
    'todo' as status,
    'high' as priority,
    1 as project_id,
    1 as assignee_id,
    DATE_ADD(CURDATE(), INTERVAL 5 DAY) as due_date,
    DATE_SUB(NOW(), INTERVAL 3 DAY) as created_at
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tasks WHERE title = 'Crear API REST para usuarios');

INSERT INTO tasks (title, description, status, priority, project_id, assignee_id, due_date, created_at) 
SELECT * FROM (SELECT 
    'Implementar sistema de notificaciones' as title,
    'Push notifications y email alerts' as description,
    'in_progress' as status,
    'normal' as priority,
    1 as project_id,
    1 as assignee_id,
    DATE_ADD(CURDATE(), INTERVAL 7 DAY) as due_date,
    DATE_SUB(NOW(), INTERVAL 2 DAY) as created_at
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tasks WHERE title = 'Implementar sistema de notificaciones');

INSERT INTO tasks (title, description, status, priority, project_id, assignee_id, due_date, created_at) 
SELECT * FROM (SELECT 
    'Testing de integración' as title,
    'Pruebas end-to-end con Jest y Cypress' as description,
    'todo' as status,
    'normal' as priority,
    1 as project_id,
    1 as assignee_id,
    DATE_ADD(CURDATE(), INTERVAL 10 DAY) as due_date,
    DATE_SUB(NOW(), INTERVAL 1 DAY) as created_at
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tasks WHERE title = 'Testing de integración');

INSERT INTO tasks (title, description, status, priority, project_id, assignee_id, due_date, created_at) 
SELECT * FROM (SELECT 
    'Deploy a producción' as title,
    'Configurar servidor y dominio' as description,
    'todo' as status,
    'urgent' as priority,
    1 as project_id,
    1 as assignee_id,
    DATE_ADD(CURDATE(), INTERVAL 3 DAY) as due_date,
    NOW() as created_at
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM tasks WHERE title = 'Deploy a producción');
