<?php
require_once 'config.php';
require_once '../shared/config.php';

$pdo = db();

try {
    // Delete if exists
    $pdo->prepare('DELETE FROM organization_presidents WHERE email = ?')->execute(['crisann.mendoza@gmail.com']);
    
    // Delete org if exists
    $pdo->prepare('DELETE FROM organizations WHERE name = ?')->execute(['Cris-Ann Test Organization']);
    
    // Create org
    $pdo->prepare('INSERT INTO organizations (name, is_active, created_at) VALUES (?, 1, NOW())')
        ->execute(['Cris-Ann Test Organization']);
    $orgId = $pdo->lastInsertId();
    
    // Create president
    $pwd = password_hash('password123', PASSWORD_DEFAULT);
    $pdo->prepare('
        INSERT INTO organization_presidents (organization_id, full_name, email, password, is_active, created_at) 
        VALUES (?, ?, ?, ?, 1, NOW())
    ')->execute([$orgId, 'Cris-Ann Mendoza', 'crisann.mendoza@gmail.com', $pwd]);
    
    echo "✓ Account created!<br>";
    echo "Email: crisann.mendoza@gmail.com<br>";
    echo "Password: password123<br>";
    echo "Org: Cris-Ann Test Organization";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
