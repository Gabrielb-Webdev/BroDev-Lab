<?php
/**
 * Script para aplicar actualizaciones a la base de datos
 * Ejecutar una sola vez: http://tu-sitio.com/run-update.php
 */

require_once 'config/config.php';

try {
    $db = getDBConnection();
    
    echo "<h2>🔄 Actualizando Base de Datos...</h2>";
    echo "<pre>";
    
    // Mejorar estados de proyectos
    echo "✓ Actualizando estados de proyectos...\n";
    $db->exec("ALTER TABLE projects MODIFY COLUMN status ENUM(
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
    ) DEFAULT 'quote'");
    
    // Mejorar estados de fases
    echo "✓ Actualizando estados de fases...\n";
    $db->exec("ALTER TABLE project_phases MODIFY COLUMN status ENUM(
        'not_started', 
        'in_progress', 
        'paused', 
        'completed', 
        'blocked'
    ) DEFAULT 'not_started'");
    
    // Agregar índice para timer activo
    echo "✓ Agregando índice para sesiones activas...\n";
    try {
        $db->exec("ALTER TABLE time_sessions ADD INDEX idx_active_session (is_active, project_id, phase_id)");
    } catch (PDOException $e) {
        if ($e->getCode() != '42000') { // Ignorar si ya existe
            throw $e;
        }
        echo "  (Índice ya existe)\n";
    }
    
    // Crear tabla de milestones si no existe
    echo "✓ Creando tabla de milestones...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS project_milestones (
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
    )");
    
    echo "\n<strong>✅ ¡Actualización completada exitosamente!</strong>\n";
    echo "\n<p>Ahora puedes eliminar este archivo por seguridad.</p>";
    echo "</pre>";
    
    echo "<a href='admin/index.php' style='display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #7C3AED, #EC4899); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; margin-top: 20px;'>🚀 Ir al Panel Admin</a>";
    
} catch (PDOException $e) {
    echo "<h2>❌ Error en la actualización</h2>";
    echo "<pre>";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "</pre>";
    echo "<p>Por favor, revisa la configuración de la base de datos.</p>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Actualización de Base de Datos</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #0A0118;
            color: #fff;
        }
        pre {
            background: rgba(124, 58, 237, 0.1);
            padding: 20px;
            border-radius: 8px;
            border: 2px solid rgba(124, 58, 237, 0.3);
        }
    </style>
</head>
<body>
</body>
</html>
