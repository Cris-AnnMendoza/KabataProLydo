<?php
require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$userId = $_SESSION['youth_id'] ?? null;
if (!$userId) {
    header('Location: login.php');
    exit;
}

$pdo = db();

// Get user's organization
$userOrg = $pdo->prepare('
    SELECT o.*, 
           (SELECT COUNT(*) FROM organization_members WHERE organization_id = o.id AND is_active = 1) as member_count
    FROM organization_members om
    JOIN organizations o ON om.organization_id = o.id
    WHERE om.user_id = ? AND om.is_active = 1
    LIMIT 1
');
$userOrg->execute([$userId]);
$org = $userOrg->fetch();

if (!$org) {
    echo '<div class="alert alert-warning">No organization found.</div>';
    exit;
}

// Get accreditation submission status
$submission = $pdo->prepare('
    SELECT * FROM accreditation_submissions 
    WHERE organization_id = ? 
    ORDER BY submission_date DESC 
    LIMIT 1
');
$submission->execute([$org['id']]);
$subData = $submission->fetch();

// Get uploaded files
$files = $pdo->prepare('
    SELECT file_type, original_filename, uploaded_at, file_size
    FROM organization_accreditation_files 
    WHERE organization_id = ? 
    ORDER BY file_type, uploaded_at DESC
');
$files->execute([$org['id']]);
$fileList = $files->fetchAll();

// Group files by type
$filesByType = [];
foreach ($fileList as $file) {
    if (!isset($filesByType[$file['file_type']])) {
        $filesByType[$file['file_type']] = [];
    }
    $filesByType[$file['file_type']][] = $file;
}

// Required documents for accreditation
$requiredDocs = [
    'constitution_bylaws' => 'Constitution & Bylaws',
    'officers_directory' => 'Officers Directory',
    'members_list' => 'Members List',
    'financial_report' => 'Financial Report',
    'organizational_chart' => 'Organizational Chart',
    'mission_vision' => 'Mission & Vision Statement'
];

$statusColors = [
    'pending' => '#FFA500',
    'approved' => '#28a745',
    'rejected' => '#dc3545',
    'needs_revision' => '#ff9800',
    'active' => '#28a745'
];

$statusLabels = [
    'pending' => 'Pending Review',
    'approved' => 'Approved',
    'rejected' => 'Rejected',
    'needs_revision' => 'Needs Revision',
    'active' => 'Accredited'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Accreditation Status</title>
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
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            color: #1a202c;
            font-size: 28px;
        }
        .header p {
            margin: 0;
            color: #718096;
            font-size: 14px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
            color: white;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .info-card strong {
            display: block;
            color: #718096;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-card span {
            display: block;
            color: #2d3748;
            font-size: 16px;
            font-weight: 500;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            font-size: 18px;
            color: #2d3748;
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .documents-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .document-item {
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 10px;
            background: #fafbfc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .document-item.required {
            border-left: 4px solid #dc3545;
        }
        .document-item.uploaded {
            border-left: 4px solid #28a745;
            background: #f0fdf4;
        }
        .doc-name {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 4px;
        }
        .doc-status {
            font-size: 12px;
            color: #718096;
        }
        .doc-status.required {
            color: #dc3545;
        }
        .doc-status.uploaded {
            color: #28a745;
        }
        .doc-date {
            font-size: 12px;
            color: #a0aec0;
        }
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline-item {
            display: flex;
            margin-bottom: 20px;
            position: relative;
            padding-left: 50px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #e2e8f0;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #cbd5e0;
        }
        .timeline-item.completed::before {
            background: #28a745;
            box-shadow: 0 0 0 2px #28a745;
        }
        .timeline-item.active::before {
            background: #007bff;
            box-shadow: 0 0 0 2px #007bff;
        }
        .timeline-content h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #2d3748;
            font-weight: 600;
        }
        .timeline-content p {
            margin: 0;
            font-size: 13px;
            color: #718096;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        .alert-info {
            background: #e7f3ff;
            border-color: #007bff;
            color: #004085;
        }
        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($org['name']); ?></h1>
            <p><?php echo htmlspecialchars($org['category'] ?? 'Organization'); ?> • <?php echo htmlspecialchars($org['barangay']); ?></p>
            <span class="status-badge" style="background-color: <?php echo $statusColors[$org['accreditation_status']] ?? '#6c757d'; ?>">
                <?php echo $statusLabels[$org['accreditation_status']] ?? 'Unknown'; ?>
            </span>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <strong>Organization President</strong>
                <span>
                    <?php 
                    if ($org['president_id']) {
                        $pres = $pdo->prepare('SELECT first_name, last_name FROM youth_users WHERE id = ?');
                        $pres->execute([$org['president_id']]);
                        $presData = $pres->fetch();
                        echo $presData ? htmlspecialchars($presData['first_name'] . ' ' . $presData['last_name']) : 'Unknown';
                    } else {
                        echo 'Not assigned';
                    }
                    ?>
                </span>
            </div>
            <div class="info-card">
                <strong>Organization Members</strong>
                <span><?php echo $org['member_count']; ?></span>
            </div>
            <div class="info-card">
                <strong>Accreditation Status</strong>
                <span><?php echo $statusLabels[$org['accreditation_status']] ?? 'Unknown'; ?></span>
            </div>
            <div class="info-card">
                <strong>President Since</strong>
                <span><?php echo $org['president_since'] ? date('M d, Y', strtotime($org['president_since'])) : '—'; ?></span>
            </div>
        </div>

        <?php if ($org['accreditation_status'] === 'pending'): ?>
            <div class="alert alert-info">
                <strong>📋 Next Steps:</strong><br>
                Your organization is pending accreditation. Please upload the required documents below to complete the process. 
                The LYDO Office will review your submission and notify you of the status.
            </div>
        <?php elseif ($org['accreditation_status'] === 'needs_revision'): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Needs Revision:</strong><br>
                <?php echo htmlspecialchars($subData['review_comments'] ?? 'Please review the uploaded documents and make necessary corrections.'); ?>
                Please upload the revised documents.
            </div>
        <?php elseif ($org['accreditation_status'] === 'rejected'): ?>
            <div class="alert alert-warning">
                <strong>❌ Rejected:</strong><br>
                <?php echo htmlspecialchars($subData['review_comments'] ?? 'Your accreditation application was not approved.'); ?>
                Please contact the LYDO Office for more information.
            </div>
        <?php elseif ($org['accreditation_status'] === 'active'): ?>
            <div class="alert alert-success">
                <strong>✓ Accredited:</strong><br>
                Your organization is officially accredited. You can now participate in LYDO programs and events.
            </div>
        <?php endif; ?>

        <?php if ($org['accreditation_status'] === 'pending' || $org['accreditation_status'] === 'needs_revision'): ?>
            <div class="section">
                <h2>📄 Required Documents</h2>
                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="submit_files">
                    <ul class="documents-list">
                        <?php foreach ($requiredDocs as $docKey => $docName): ?>
                            <?php $hasFile = isset($filesByType[$docKey]); ?>
                            <li class="document-item <?php echo $hasFile ? 'uploaded' : 'required'; ?>">
                                <div>
                                    <div class="doc-name"><?php echo htmlspecialchars($docName); ?></div>
                                    <div class="doc-status <?php echo $hasFile ? 'uploaded' : 'required'; ?>">
                                        <?php echo $hasFile ? '✓ Uploaded' : '× Required'; ?>
                                        <?php if ($hasFile && isset($filesByType[$docKey][0])): ?>
                                            <div class="doc-date"><?php echo date('M d, Y', strtotime($filesByType[$docKey][0]['uploaded_at'])); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <input type="file" name="<?php echo $docKey; ?>" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" style="max-width: 200px;">
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="submit" class="btn btn-primary" style="margin-top: 15px;">
                        📤 Upload Documents
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="section">
                <h2>📄 Uploaded Documents</h2>
                <?php if (!empty($fileList)): ?>
                    <ul class="documents-list">
                        <?php foreach ($requiredDocs as $docKey => $docName): ?>
                            <?php if (isset($filesByType[$docKey])): ?>
                                <?php foreach ($filesByType[$docKey] as $file): ?>
                                    <li class="document-item uploaded">
                                        <div>
                                            <div class="doc-name"><?php echo htmlspecialchars($docName); ?></div>
                                            <div class="doc-status uploaded">
                                                <?php echo htmlspecialchars($file['original_filename']); ?> 
                                                <div class="doc-date"><?php echo date('M d, Y', strtotime($file['uploaded_at'])); ?> • <?php echo number_format($file['file_size'] / 1024, 1); ?>KB</div>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: #718096; font-size: 14px;">No documents uploaded yet.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <h2>📅 Accreditation Timeline</h2>
            <div class="timeline">
                <div class="timeline-item <?php echo $org['accreditation_status'] !== 'pending' ? 'completed' : 'active'; ?>">
                    <div class="timeline-content">
                        <h3>1. Register Your Organization</h3>
                        <p>Create your organization account and join as a member</p>
                    </div>
                </div>
                <div class="timeline-item <?php echo $subData ? 'completed' : ''; ?>">
                    <div class="timeline-content">
                        <h3>2. Submit Required Documents</h3>
                        <p>Upload constitution, officers list, members list, and other required documents</p>
                    </div>
                </div>
                <div class="timeline-item <?php echo $org['accreditation_status'] === 'active' ? 'completed' : ''; ?>">
                    <div class="timeline-content">
                        <h3>3. LYDO Review & Approval</h3>
                        <p>LYDO Admin reviews your submission. You will be notified of the result within 5-7 business days</p>
                    </div>
                </div>
                <div class="timeline-item <?php echo $org['accreditation_status'] === 'active' ? 'completed' : ''; ?>">
                    <div class="timeline-content">
                        <h3>4. Official Accreditation</h3>
                        <p>Your organization is now officially accredited and can participate in LYDO programs</p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($org['accreditation_status'] === 'active'): ?>
            <div class="alert alert-success">
                <strong>Questions?</strong> Contact the LYDO Office for any inquiries about your organization or programs.
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('uploadForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const response = await fetch('/shared/youth/accreditation_upload.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + (data.errors?.[0] || data.message));
                }
            } catch (err) {
                alert('Upload failed: ' + err.message);
            }
        });
    </script>
</body>
</html>
