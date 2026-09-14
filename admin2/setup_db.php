<?php
require_once __DIR__ . '/../shared/config.php';

try {
    $pdo = db();
    
    // Create admin_users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(191) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'staff_encoder',
            is_active TINYINT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert admin user
    $pdo->exec("
        INSERT IGNORE INTO admin_users (full_name, email, password, role) 
        VALUES ('Admin', 'admin@lydo.gov.ph', '\$2y\$12\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin')
    ");
    
    echo "✅ Database setup complete!<br>";
    echo "Email: admin@lydo.gov.ph<br>";
    echo "Password: Admin@1234<br>";
    echo "<a href='/login.php'>Go to Login</a>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
