<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();

$userId = $_SESSION['youth_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$pdo = db();
$action = $_POST['action'] ?? '';

// ── Get user's organization ──────────────────────────────
$userOrg = $pdo->prepare('
    SELECT o.id, o.name, o.accreditation_status 
    FROM organization_members om
    JOIN organizations o ON om.organization_id = o.id
    WHERE om.user_id = ? AND om.is_active = 1
    LIMIT 1
');
$userOrg->execute([$userId]);
$org = $userOrg->fetch();

if (!$org) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Organization not found.']);
    exit;
}

if ($action === 'submit_files') {
    // Get required file types from POST
    $fileTypes = $_POST['file_types'] ?? [];
    if (!is_array($fileTypes) || empty($fileTypes)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'No files specified.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/accreditation/' . $org['id'];
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uploadedFiles = 0;
    $errors = [];

    foreach ($fileTypes as $fileType) {
        if (!isset($_FILES[$fileType]) || $_FILES[$fileType]['error'] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($_FILES[$fileType]['error'] !== UPLOAD_ERR_OK) {
            $errors[] = ucfirst(str_replace('_', ' ', $fileType)) . ' upload failed.';
            continue;
        }

        $file = $_FILES[$fileType];
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            $errors[] = ucfirst(str_replace('_', ' ', $fileType)) . ' must be smaller than 5MB.';
            continue;
        }

        // Allowed file types
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = ucfirst(str_replace('_', ' ', $fileType)) . ' has invalid file type.';
            continue;
        }

        $timestamp = time();
        $random = bin2hex(random_bytes(4));
        $newFilename = $fileType . '_' . $timestamp . '_' . $random . '.' . $ext;
        $filePath = $uploadDir . '/' . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Store file record in database
            $fileRecord = $pdo->prepare('
                INSERT INTO organization_accreditation_files (organization_id, file_type, original_filename, file_path, file_size)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE file_path = ?, file_size = ?, uploaded_at = NOW()
            ');
            $fileRecord->execute([
                $org['id'],
                $fileType,
                $file['name'],
                'accreditation/' . $org['id'] . '/' . $newFilename,
                $file['size'],
                'accreditation/' . $org['id'] . '/' . $newFilename,
                $file['size']
            ]);
            $uploadedFiles++;
        } else {
            $errors[] = 'Failed to save ' . ucfirst(str_replace('_', ' ', $fileType));
        }
    }

    if ($uploadedFiles > 0) {
        // Create or update accreditation submission
        $submission = $pdo->prepare('
            INSERT INTO accreditation_submissions (organization_id, submitted_by, status, submission_date)
            VALUES (?, ?, "pending", NOW())
            ON DUPLICATE KEY UPDATE status = "pending", submission_date = NOW()
        ');
        $submission->execute([$org['id'], $userId]);

        echo json_encode([
            'success' => true,
            'message' => "Uploaded $uploadedFiles file(s) successfully.",
            'errors' => $errors
        ]);
    } else {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'No files uploaded.', 'errors' => $errors]);
    }
    exit;
}

if ($action === 'get_status') {
    $submission = $pdo->prepare('
        SELECT * FROM accreditation_submissions 
        WHERE organization_id = ? 
        ORDER BY submission_date DESC 
        LIMIT 1
    ');
    $submission->execute([$org['id']]);
    $subData = $submission->fetch();

    $files = $pdo->prepare('
        SELECT file_type, original_filename, uploaded_at 
        FROM organization_accreditation_files 
        WHERE organization_id = ? 
        ORDER BY uploaded_at DESC
    ');
    $files->execute([$org['id']]);
    $fileList = $files->fetchAll();

    echo json_encode([
        'success' => true,
        'organization' => $org,
        'submission' => $subData,
        'files' => $fileList
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);
