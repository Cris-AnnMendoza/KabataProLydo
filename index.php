<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
if (file_exists(__DIR__ . '/index.html')) {
    readfile(__DIR__ . '/index.html');
    exit;
}
echo 'Site loading...';
