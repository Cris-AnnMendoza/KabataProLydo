<?php
/**
 * Ensure all required tables exist on Railway
 * Include this at the top of admin pages that use custom tables
 */

function ensureTables() {
    try {
        $pdo = db();
        $requiredTables = ['accreditation_applications', 'assistance_requests', 'volunteer_programs', 'scholarship_programs', 'contact_messages'];
        
        foreach ($requiredTables as $table) {
            try {
                $pdo->query("SELECT 1 FROM $table LIMIT 1");
            } catch (Exception $e) {
                // Table doesn't exist, create all missing tables
                try {
                    $sql = file_get_contents(__DIR__ . '/../database/missing_tables_mysql.sql');
                    $statements = array_filter(
                        array_map('trim', explode(';', $sql)),
                        fn($s) => !empty($s) && !str_starts_with(trim($s), '--')
                    );
                    
                    foreach ($statements as $stmt) {
                        // Only create accreditation, assistance, volunteer, scholarship, contact tables
                        if (strpos($stmt, 'CREATE TABLE') !== false) {
                            try {
                                $pdo->exec($stmt . ';');
                            } catch (Exception $tableError) {
                                error_log("Table creation error: " . $tableError->getMessage());
                            }
                        }
                    }
                    break; // Exit loop after trying to create all tables
                } catch (Exception $setupError) {
                    error_log("Failed to create tables: " . $setupError->getMessage());
                    return false;
                }
            }
        }
        return true;
    } catch (Exception $e) {
        error_log("ensureTables exception: " . $e->getMessage());
        return false;
    }
}

// Auto-call on include
try {
    ensureTables();
} catch (Exception $e) {
    error_log("Error in ensureTables: " . $e->getMessage());
}
