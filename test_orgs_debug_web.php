<?php
/**
 * Web Version - Debug script to query organization data
 * Accessible at: http://localhost/path-to-project/test_orgs_debug_web.php
 * 
 * Checks: 1) Total organizations count
 *         2) Organizations with accreditation_status='active'
 *         3) All unique accreditation_status values
 */

require_once __DIR__ . '/shared/config.php';

// Get the output as HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Accreditation Debug</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
            margin-top: 0;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #ecf0f1;
            border-left: 4px solid #3498db;
            border-radius: 4px;
        }
        .section h2 {
            color: #2c3e50;
            margin-top: 0;
        }
        .stat-box {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 15px 25px;
            border-radius: 4px;
            margin: 10px 10px 10px 0;
            font-size: 18px;
            font-weight: bold;
        }
        .stat-box.secondary {
            background: #27ae60;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #bdc3c7;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #c62828;
        }
        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #2e7d32;
        }
        .code {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.5;
        }
        .info-box {
            background: #e3f2fd;
            color: #1565c0;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #1565c0;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Organization Accreditation Debug Report</h1>
        
        <?php
        try {
            $pdo = db();
            ?>
            <div class="success">✅ Database connection successful</div>
            
            <!-- SECTION 1: Total Count -->
            <div class="section">
                <h2>1️⃣  Total Organizations Count</h2>
                <?php
                $totalStmt = $pdo->prepare('SELECT COUNT(*) as total FROM organizations');
                $totalStmt->execute();
                $totalResult = $totalStmt->fetch();
                $totalCount = $totalResult['total'];
                ?>
                <div class="stat-box"><?php echo $totalCount; ?> organizations</div>
                <div class="info-box">
                    <strong>Query:</strong> <code>SELECT COUNT(*) FROM organizations</code>
                </div>
            </div>
            
            <!-- SECTION 2: Active Organizations -->
            <div class="section">
                <h2>2️⃣  Organizations with accreditation_status='active'</h2>
                <?php
                $activeStmt = $pdo->prepare('SELECT COUNT(*) as total FROM organizations WHERE accreditation_status = ?');
                $activeStmt->execute(['active']);
                $activeResult = $activeStmt->fetch();
                $activeCount = $activeResult['total'];
                ?>
                <div class="stat-box secondary"><?php echo $activeCount; ?> active</div>
                <div class="info-box">
                    <strong>Query:</strong> <code>SELECT COUNT(*) FROM organizations WHERE accreditation_status = 'active'</code>
                </div>
                <p><strong>Percentage:</strong> <?php echo $totalCount > 0 ? round(($activeCount / $totalCount) * 100, 2) : 0; ?>% of total organizations</p>
            </div>
            
            <!-- SECTION 3: All Unique Status Values -->
            <div class="section">
                <h2>3️⃣  All Unique accreditation_status Values</h2>
                <?php
                $statusStmt = $pdo->prepare('
                    SELECT accreditation_status, COUNT(*) as count 
                    FROM organizations 
                    GROUP BY accreditation_status 
                    ORDER BY count DESC, accreditation_status ASC
                ');
                $statusStmt->execute();
                $statusResults = $statusStmt->fetchAll();
                ?>
                
                <?php if (empty($statusResults)): ?>
                    <div class="error">❌ No organizations found in database</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Accreditation Status</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statusResults as $row): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($row['accreditation_status'] ?? 'NULL'); ?></code></td>
                                    <td><?php echo $row['count']; ?></td>
                                    <td><?php echo round(($row['count'] / $totalCount) * 100, 2); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="code" style="margin-top: 15px;">
SELECT accreditation_status, COUNT(*) as count 
FROM organizations 
GROUP BY accreditation_status 
ORDER BY count DESC
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- SECTION 4: Sample Data -->
            <div class="section">
                <h2>4️⃣  Sample Organizations by Status</h2>
                
                <?php if (empty($statusResults)): ?>
                    <div class="error">❌ No organizations found in database</div>
                <?php else: ?>
                    <?php foreach ($statusResults as $statusRow): ?>
                        <h3 style="color: #34495e; margin-top: 20px;">
                            Status: <code><?php echo htmlspecialchars($statusRow['accreditation_status'] ?? 'NULL'); ?></code> 
                            (<?php echo $statusRow['count']; ?> total)
                        </h3>
                        
                        <?php
                        $sampleStmt = $pdo->prepare('
                            SELECT id, name, barangay, category, accreditation_status 
                            FROM organizations 
                            WHERE accreditation_status = ? 
                            ORDER BY id ASC
                            LIMIT 5
                        ');
                        $sampleStmt->execute([$statusRow['accreditation_status']]);
                        $samples = $sampleStmt->fetchAll();
                        ?>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Organization Name</th>
                                    <th>Barangay</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($samples as $sample): ?>
                                    <tr>
                                        <td><?php echo $sample['id']; ?></td>
                                        <td><?php echo htmlspecialchars($sample['name']); ?></td>
                                        <td><?php echo htmlspecialchars($sample['barangay']); ?></td>
                                        <td><?php echo htmlspecialchars($sample['category'] ?? 'N/A'); ?></td>
                                        <td><code><?php echo htmlspecialchars($sample['accreditation_status']); ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Summary -->
            <div class="section">
                <h2>📊 Summary</h2>
                <ul>
                    <li><strong>Total Organizations:</strong> <?php echo $totalCount; ?></li>
                    <li><strong>Active (accredited):</strong> <?php echo $activeCount; ?> (<?php echo $totalCount > 0 ? round(($activeCount / $totalCount) * 100, 2) : 0; ?>%)</li>
                    <li><strong>Unique Status Values:</strong> <?php echo count($statusResults); ?></li>
                    <li><strong>Database Connection:</strong> ✅ Successful</li>
                    <li><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
                </ul>
            </div>
            
            <?php
        } catch (PDOException $e) {
            ?>
            <div class="error">
                <strong>❌ Database Connection Failed</strong><br>
                Error: <?php echo htmlspecialchars($e->getMessage()); ?><br>
                <br>
                <strong>To fix:</strong>
                <ul>
                    <li>Make sure MySQL is running in XAMPP Control Panel</li>
                    <li>Check that the database credentials in <code>shared/config.php</code> are correct</li>
                    <li>Verify the database <code><?php echo htmlspecialchars(DB_NAME); ?></code> exists</li>
                </ul>
            </div>
            <?php
        } catch (Throwable $e) {
            ?>
            <div class="error">
                <strong>❌ Error Occurred</strong><br>
                Error: <?php echo htmlspecialchars($e->getMessage()); ?>
            </div>
            <?php
        }
        ?>
    </div>
</body>
</html>
