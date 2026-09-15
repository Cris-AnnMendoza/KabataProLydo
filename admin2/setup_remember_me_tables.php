<?php
/**
 * Setup script for Remember Me token tables
 * Run this once after deployment to enable persistent mobile login
 * 
 * Access: /admin2/setup_remember_me_tables.php?key=your_admin_key
 */

require_once __DIR__ . '/../shared/config.php';

// Simple security check
$adminKey = $_GET['key'] ?? '';
$expectedKey = 'lydo_setup_' . substr(md5(DB_NAME), 0, 8);

if ($adminKey !== $expectedKey) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;padding:40px;color:#c62828;background:#ffebee;border-radius:10px;max-width:600px;margin:40px auto">
        <h2>Access Denied</h2>
        <p>Invalid setup key.</p>
    </div>');
}

$pdo = db();
$setupSql = file_get_contents(__DIR__ . '/../database/add_remember_me_tokens.sql');

try {
    // Split by delimiter and execute
    $statements = array_filter(array_map('trim', preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $setupSql)));
    
    foreach ($statements as $stmt) {
        if (!empty($stmt) && !str_starts_with(trim($stmt), '--')) {
            $pdo->exec($stmt);
        }
    }
    
    echo '<div style="font-family:sans-serif;padding:40px;color:#2e7d32;background:#e8f5e9;border-radius:10px;max-width:600px;margin:40px auto">
        <h2>✓ Setup Complete</h2>
        <p>Remember me token tables created successfully.</p>
        <p style="font-size:0.9rem;color:#558b2f;margin-top:12px">Remember Me feature is now enabled for persistent mobile login.</p>
    </div>';
    
} catch (Exception $e) {
    echo '<div style="font-family:sans-serif;padding:40px;color:#c62828;background:#ffebee;border-radius:10px;max-width:600px;margin:40px auto">
        <h2>Setup Failed</h2>
        <p>'.htmlspecialchars($e->getMessage()).'</p>
    </div>';
}
