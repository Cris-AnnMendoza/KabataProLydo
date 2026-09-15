<?php
require_once 'config.php';
require_once '../shared/config.php';

$pdo = db();

// Check if Cris-Ann already exists
$checkPresident = $pdo->prepare('SELECT id FROM organization_presidents WHERE email = ? LIMIT 1');
$checkPresident->execute(['crisann@lydo.local']);
if ($checkPresident->fetch()) {
    echo "Cris-Ann Mendoza already exists as a president.<br>";
    exit;
}

// Check if organization exists, if not create one
$checkOrg = $pdo->prepare('SELECT id FROM organizations WHERE name = ? LIMIT 1');
$checkOrg->execute(['Cris-Ann Test Organization']);
$orgResult = $checkOrg->fetch();

if (!$orgResult) {
    // Create organization
    $pdo->prepare('
        INSERT INTO organizations (name, is_active, created_at)
        VALUES (?, 1, NOW())
    ')->execute(['Cris-Ann Test Organization']);
    $orgId = $pdo->lastInsertId();
    echo "Created organization: Cris-Ann Test Organization (ID: $orgId)<br>";
} else {
    $orgId = $orgResult['id'];
    echo "Using existing organization ID: $orgId<br>";
}

// Create president user
$password = password_hash('password123', PASSWORD_DEFAULT);
$pdo->prepare('
    INSERT INTO organization_presidents (
        organization_id,
        full_name,
        email,
        password,
        is_active,
        created_at
    ) VALUES (?, ?, ?, ?, 1, NOW())
')->execute([
    $orgId,
    'Cris-Ann Mendoza',
    'crisann@lydo.local',
    $password
]);

$presidentId = $pdo->lastInsertId();
echo "✓ Created president user (ID: $presidentId)<br>";
echo "<br>";
echo "Login credentials:<br>";
echo "Email: crisann@lydo.local<br>";
echo "Password: password123<br>";
echo "<br>";
echo "Organization: Cris-Ann Test Organization (ID: $orgId)<br>";
echo "<br>";
echo '<a href="index.php">Back to Admin</a>';
?>
