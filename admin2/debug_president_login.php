<?php
require_once 'config.php';
require_once '../shared/config.php';

$pdo = db();

// Check if president exists
$check = $pdo->prepare('SELECT * FROM organization_presidents WHERE email = ?');
$check->execute(['crisann.mendoza@gmail.com']);
$pres = $check->fetch();

if ($pres) {
    echo "✓ President found<br>";
    echo "ID: " . $pres['id'] . "<br>";
    echo "Email: " . $pres['email'] . "<br>";
    echo "Org ID: " . $pres['organization_id'] . "<br>";
} else {
    echo "✗ President NOT found";
}
?>
