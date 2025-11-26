<?php
/**
 * Script para crear nuevos usuarios admin
 * BroDev Lab
 */

require_once 'config/config.php';

echo "====================================\n";
echo "CREAR NUEVOS USUARIOS ADMIN\n";
echo "BroDev Lab\n";
echo "====================================\n\n";

try {
    $db = getDBConnection();
    
    // Generar hashes de contraseña
    $gabrielPassword = 'Gabriel2024!';
    $lautaroPassword = 'Lautaro2024!';
    
    $gabrielHash = password_hash($gabrielPassword, PASSWORD_DEFAULT);
    $lautaroHash = password_hash($lautaroPassword, PASSWORD_DEFAULT);
    
    echo "Hashes generados:\n";
    echo "Gabriel: $gabrielHash\n";
    echo "Lautaro: $lautaroHash\n\n";
    
    // Eliminar usuario admin existente
    echo "1. Eliminando usuario 'admin' existente...\n";
    $stmt = $db->prepare("DELETE FROM admin_users WHERE username = 'admin'");
    $stmt->execute();
    echo "   ✓ Usuario 'admin' eliminado\n\n";
    
    // Crear usuario Gabriel Bustos
    echo "2. Creando usuario Gabriel Bustos...\n";
    $stmt = $db->prepare("
        INSERT INTO admin_users (username, email, password_hash, full_name, role, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'gabriel',
        'gabriel@brodevlab.com',
        $gabrielHash,
        'Gabriel Bustos',
        'super_admin',
        'active'
    ]);
    echo "   ✓ Usuario Gabriel creado exitosamente\n";
    echo "   - Username: gabriel\n";
    echo "   - Email: gabriel@brodevlab.com\n";
    echo "   - Password: $gabrielPassword\n";
    echo "   - Role: super_admin\n\n";
    
    // Crear usuario Lautaro Magliano
    echo "3. Creando usuario Lautaro Magliano...\n";
    $stmt = $db->prepare("
        INSERT INTO admin_users (username, email, password_hash, full_name, role, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'lautaro',
        'lautaro@brodevlab.com',
        $lautaroHash,
        'Lautaro Magliano',
        'super_admin',
        'active'
    ]);
    echo "   ✓ Usuario Lautaro creado exitosamente\n";
    echo "   - Username: lautaro\n";
    echo "   - Email: lautaro@brodevlab.com\n";
    echo "   - Password: $lautaroPassword\n";
    echo "   - Role: super_admin\n\n";
    
    // Verificar usuarios creados
    echo "4. Verificando usuarios en la base de datos...\n";
    $stmt = $db->query("SELECT id, username, email, full_name, role, status, created_at FROM admin_users ORDER BY id");
    $users = $stmt->fetchAll();
    
    echo "\nUsuarios actuales en la base de datos:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-5s %-15s %-30s %-20s %-15s %-10s\n", "ID", "Username", "Email", "Full Name", "Role", "Status");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($users as $user) {
        printf(
            "%-5d %-15s %-30s %-20s %-15s %-10s\n",
            $user['id'],
            $user['username'],
            $user['email'],
            $user['full_name'],
            $user['role'],
            $user['status']
        );
    }
    
    echo str_repeat("-", 80) . "\n";
    echo "\n✅ OPERACIÓN COMPLETADA EXITOSAMENTE\n\n";
    
    echo "====================================\n";
    echo "CREDENCIALES DE ACCESO:\n";
    echo "====================================\n\n";
    
    echo "Usuario 1 - Gabriel Bustos:\n";
    echo "  URL: " . SITE_URL . "/admin/login.php\n";
    echo "  Username: gabriel\n";
    echo "  Email: gabriel@brodevlab.com\n";
    echo "  Password: $gabrielPassword\n";
    echo "  Role: super_admin\n\n";
    
    echo "Usuario 2 - Lautaro Magliano:\n";
    echo "  URL: " . SITE_URL . "/admin/login.php\n";
    echo "  Username: lautaro\n";
    echo "  Email: lautaro@brodevlab.com\n";
    echo "  Password: $lautaroPassword\n";
    echo "  Role: super_admin\n\n";
    
    echo "====================================\n";
    echo "⚠️  IMPORTANTE: Guarda estas credenciales en un lugar seguro\n";
    echo "====================================\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
