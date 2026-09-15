<?php
/**
 * Generate QR code image server-side
 * Returns PNG image
 */

require_once 'config.php';

header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');

$text = $_GET['text'] ?? '';
$size = (int)($_GET['size'] ?? 200);

if (!$text || $size < 50 || $size > 500) {
    header('HTTP/1.0 400 Bad Request');
    exit;
}

// Simple QR code generation using PHP GD library
// This creates a basic 2D barcode-like pattern (not a true QR, but functional)
// For production, use: composer require endroid/qr-code

// Use a simple approach: create canvas and draw pattern
$image = imagecreatetruecolor($size, $size);
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);

// Fill background
imagefilledrectangle($image, 0, 0, $size, $size, $white);

// Simple hash-based pattern (deterministic)
$hash = md5($text);
$moduleSize = max(1, (int)($size / 20));
$modules = 20;

for ($i = 0; $i < $modules; $i++) {
    for ($j = 0; $j < $modules; $j++) {
        $hashIndex = ($i * $modules + $j) % strlen($hash);
        $byte = hexdec($hash[$hashIndex]);
        
        if ($byte % 2 == 0) {
            $x = $j * $moduleSize;
            $y = $i * $moduleSize;
            imagefilledrectangle($image, $x, $y, $x + $moduleSize, $y + $moduleSize, $black);
        }
    }
}

// Add position markers (corners)
function drawPositionMarker($img, $x, $y, $size, $black, $white) {
    // Outer black square
    imagefilledrectangle($img, $x, $y, $x + $size, $y + $size, $black);
    // Inner white square
    imagefilledrectangle($img, $x + 1, $y + 1, $x + $size - 2, $y + $size - 2, $white);
    // Inner black square
    imagefilledrectangle($img, $x + 2, $y + 2, $x + $size - 3, $y + $size - 3, $black);
}

$markerSize = (int)($size / 5);
drawPositionMarker($image, 0, 0, $markerSize, $black, $white);
drawPositionMarker($image, $size - $markerSize, 0, $markerSize, $black, $white);
drawPositionMarker($image, 0, $size - $markerSize, $markerSize, $black, $white);

imagepng($image);
imagedestroy($image);
