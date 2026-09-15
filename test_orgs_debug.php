<?php
/**
 * Debug script to query organization data
 * Checks: 1) Total organizations count
 *         2) Organizations with accreditation_status='active'
 *         3) All unique accreditation_status values
 */

// Suppress the HTML error message from config.php and capture output
ob_start();

try {
    require_once __DIR__ . '/shared/config.php';
    $pdo = db();
    ob_end_clean(); // Clear any buffered output if connection was successful
    
    echo "=== ORGANIZATION ACCREDITATION DEBUG ===\n\n";
    
    // 1. Total organizations count
    echo "1. TOTAL ORGANIZATIONS COUNT:\n";
    echo "-----------------------------------\n";
    $totalStmt = $pdo->prepare('SELECT COUNT(*) as total FROM organizations');
    $totalStmt->execute();
    $totalResult = $totalStmt->fetch();
    $totalCount = $totalResult['total'];
    echo "Total Organizations: " . $totalCount . "\n\n";
    
    // 2. Organizations with accreditation_status='active'
    echo "2. ORGANIZATIONS WITH accreditation_status='active':\n";
    echo "-----------------------------------\n";
    $activeStmt = $pdo->prepare('SELECT COUNT(*) as total FROM organizations WHERE accreditation_status = ?');
    $activeStmt->execute(['active']);
    $activeResult = $activeStmt->fetch();
    $activeCount = $activeResult['total'];
    echo "Active Organizations: " . $activeCount . "\n\n";
    
    // 3. All unique accreditation_status values and their counts
    echo "3. ALL UNIQUE ACCREDITATION_STATUS VALUES:\n";
    echo "-----------------------------------\n";
    $statusStmt = $pdo->prepare('
        SELECT accreditation_status, COUNT(*) as count 
        FROM organizations 
        GROUP BY accreditation_status 
        ORDER BY accreditation_status ASC
    ');
    $statusStmt->execute();
    $statusResults = $statusStmt->fetchAll();
    
    if (empty($statusResults)) {
        echo "No organizations found with accreditation_status values.\n";
    } else {
        foreach ($statusResults as $row) {
            $status = $row['accreditation_status'] ?? 'NULL';
            $count = $row['count'];
            echo "  - '{$status}': {$count} organization(s)\n";
        }
    }
    
    echo "\n4. SAMPLE ORGANIZATIONS BY STATUS:\n";
    echo "-----------------------------------\n";
    
    if (!empty($statusResults)) {
        foreach ($statusResults as $row) {
            $status = $row['accreditation_status'];
            echo "\nStatus: '{$status}'\n";
            
            $sampleStmt = $pdo->prepare('
                SELECT id, name, barangay, accreditation_status 
                FROM organizations 
                WHERE accreditation_status = ? 
                LIMIT 3
            ');
            $sampleStmt->execute([$status]);
            $samples = $sampleStmt->fetchAll();
            
            foreach ($samples as $sample) {
                echo "  - ID: {$sample['id']}, Name: {$sample['name']}, Barangay: {$sample['barangay']}\n";
            }
        }
    }
    
    echo "\n=== END DEBUG ===\n";
    
} catch (PDOException $e) {
    ob_end_clean();
    echo "Database Connection Error:\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "This script requires a running MySQL database.\n";
    echo "Make sure you have:\n";
    echo "  1. XAMPP MySQL running locally, OR\n";
    echo "  2. Railway environment variables set for production\n";
    echo "\nTo run this script:\n";
    echo "  - Via web: http://localhost/path-to-project/test_orgs_debug.php\n";
    echo "  - Via CLI: Start XAMPP MySQL, then run this script\n";
    exit(1);
} catch (Throwable $e) {
    ob_end_clean();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
