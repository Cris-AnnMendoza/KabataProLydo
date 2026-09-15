<?php
require_once 'config.php';
requireLogin();

$pdo = db();
$admin = currentAdmin();

// Only super admins can run this
if ($admin['role'] !== 'super_admin') {
    die('<h2>Access Denied</h2>');
}

$sql = file_get_contents(__DIR__ . '/../database/missing_tables_mysql.sql');

try {
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s) && !str_starts_with(trim($s), '--')
    );

    foreach ($statements as $stmt) {
        $pdo->exec($stmt . ';');
    }

    echo '<div style="padding:20px;background:#e8f5e9;color:#2e7d32;border-radius:8px;margin:20px">';
    echo '<h2>✓ All tables created successfully</h2>';
    echo '<p>Created/verified ' . count($statements) . ' tables</p>';
    echo '<p><a href="accreditation.php" style="color:#2e7d32;font-weight:bold">Go to Accreditation →</a></p>';
    echo '</div>';

} catch (Exception $e) {
    echo '<div style="padding:20px;background:#ffebee;color:#c62828;border-radius:8px;margin:20px">';
    echo '<h2>✗ Error creating tables</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?>
