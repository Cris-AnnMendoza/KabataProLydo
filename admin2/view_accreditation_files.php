<?php
require_once 'config.php';
requireLogin();

$orgId = (int)($_GET['org_id'] ?? 0);
if (!$orgId) {
    http_response_code(404);
    echo 'Organization not found.';
    exit;
}

$pdo = db();

$org = $pdo->prepare('SELECT * FROM organizations WHERE id = ? LIMIT 1');
$org->execute([$orgId]);
$orgData = $org->fetch();

if (!$orgData) {
    http_response_code(404);
    echo 'Organization not found.';
    exit;
}

$files = $pdo->prepare('
    SELECT file_type, original_filename, file_path, file_size, uploaded_at
    FROM organization_accreditation_files
    WHERE organization_id = ?
    ORDER BY file_type, uploaded_at DESC
');
$files->execute([$orgId]);
$fileList = $files->fetchAll();

// Group by type
$filesByType = [];
foreach ($fileList as $file) {
    if (!isset($filesByType[$file['file_type']])) {
        $filesByType[$file['file_type']] = [];
    }
    $filesByType[$file['file_type']][] = $file;
}

$fileTypeLabels = [
    'constitution_bylaws' => 'Constitution & Bylaws',
    'officers_directory' => 'Officers Directory',
    'members_list' => 'Members List',
    'financial_report' => 'Financial Report',
    'organizational_chart' => 'Organizational Chart',
    'mission_vision' => 'Mission & Vision'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accreditation Files - <?=htmlspecialchars($orgData['name'])?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .header-info {
            margin-top: 10px;
            font-size: 14px;
            opacity: 0.95;
        }
        .content {
            padding: 30px;
        }
        .file-section {
            margin-bottom: 30px;
        }
        .file-section h3 {
            font-size: 16px;
            color: #2d3748;
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 10px;
            border-left: 4px solid #007bff;
        }
        .file-info {
            flex: 1;
        }
        .file-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }
        .file-meta {
            font-size: 12px;
            color: #718096;
        }
        .file-actions {
            display: flex;
            gap: 10px;
        }
        .btn-view {
            background: #007bff;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-view:hover {
            background: #0056b3;
        }
        .btn-download {
            background: #28a745;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-download:hover {
            background: #218838;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        .empty-icon {
            font-size: 40px;
            margin-bottom: 10px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><?=htmlspecialchars($orgData['name'])?></h1>
                <div class="header-info">
                    <i class="fas fa-map-marker-alt"></i> <?=htmlspecialchars($orgData['barangay'])?> • 
                    <?=htmlspecialchars($orgData['category'])?> •
                    <strong>Status:</strong> <?=ucfirst(str_replace('_', ' ', $orgData['accreditation_status']))?>
                </div>
            </div>
            <button onclick="window.close()" style="background: rgba(255,255,255,0.2); border: 1px solid white; color: white; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="content">
            <?php if (empty($fileList)): ?>
                <div class="empty">
                    <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
                    <p>No documents uploaded yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($fileTypeLabels as $fileKey => $label): ?>
                    <?php if (isset($filesByType[$fileKey])): ?>
                        <div class="file-section">
                            <h3><?=$label?></h3>
                            <?php foreach ($filesByType[$fileKey] as $file): ?>
                                <div class="file-item">
                                    <div class="file-info">
                                        <div class="file-name">
                                            <i class="fas fa-file"></i> <?=htmlspecialchars($file['original_filename'])?>
                                        </div>
                                        <div class="file-meta">
                                            Uploaded <?=date('M d, Y H:i', strtotime($file['uploaded_at']))?> • 
                                            <?=number_format($file['file_size'] / 1024, 1)?>KB
                                        </div>
                                    </div>
                                    <div class="file-actions">
                                        <a href="<?=htmlspecialchars('/shared/uploads/' . $file['file_path'])?>" target="_blank" class="btn-view">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="<?=htmlspecialchars('/shared/uploads/' . $file['file_path'])?>" download class="btn-download">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
