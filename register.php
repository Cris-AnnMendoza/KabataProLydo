<?php
require_once __DIR__ . '/shared/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$pdo = db();

// ── Required fields ───────────────────────────────────────
$required = ['first_name','last_name','gender','birthdate','age',
             'civil_status','contact_number','email','password','barangay'];

foreach ($required as $f) {
    if (empty($_POST[$f])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => ucfirst(str_replace('_',' ',$f)) . ' is required.']);
        exit;
    }
}

$email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit;
}

$password = $_POST['password'];
if (strlen($password) < 8) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
    exit;
}

// Enhanced password strength validation
$hasLowercase = preg_match('/[a-z]/', $password);
$hasUppercase = preg_match('/[A-Z]/', $password);
$hasNumber = preg_match('/[0-9]/', $password);
$hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);

$strengthScore = 0;
if ($hasLowercase) $strengthScore++;
if ($hasUppercase) $strengthScore++;
if ($hasNumber) $strengthScore++;
if ($hasSpecial) $strengthScore++;

if ($strengthScore < 2) {
    http_response_code(422);
    echo json_encode([
        'success' => false, 
        'message' => 'Password is too weak. Please include a mix of uppercase letters, lowercase letters, numbers, and special characters.'
    ]);
    exit;
}

if ($password !== ($_POST['confirm_password'] ?? '')) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

// ── Check duplicate email ─────────────────────────────────
$check = $pdo->prepare('SELECT id FROM youth_users WHERE email = ? LIMIT 1');
$check->execute([$email]);
if ($check->fetch()) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'This email is already registered. Please login.']);
    exit;
}

// ── Hash password ─────────────────────────────────────────
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// ── Get registration role ─────────────────────────────────
$registerAs = $_POST['register_as'] ?? 'youth_member';
$organizationId = null;
$youthOrganizationId = null;

if ($registerAs === 'organization_president') {
    // Organization President Registration
    if (empty($_POST['organization_id'])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please select your organization.']);
        exit;
    }
    
    $organizationId = (int)$_POST['organization_id'];
    
    // Verify organization exists and is accredited
    $checkOrg = $pdo->prepare('SELECT id, accreditation_status FROM organizations WHERE id = ? LIMIT 1');
    $checkOrg->execute([$organizationId]);
    $orgData = $checkOrg->fetch();
    
    if (!$orgData) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Selected organization not found.']);
        exit;
    }
    
    if ($orgData['accreditation_status'] !== 'active') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'You can only register as president of an accredited organization.']);
        exit;
    }
    
    // Note: Check for existing president removed - allow switching presidents
} else {
    // Youth Member - MUST have an organization
    // Option 1: Select from existing accredited organizations
    if (!empty($_POST['organization_id'])) {
        $youthOrganizationId = (int)$_POST['organization_id'];
        
        // Verify organization exists
        $checkOrg = $pdo->prepare('SELECT id FROM organizations WHERE id = ? LIMIT 1');
        $checkOrg->execute([$youthOrganizationId]);
        if (!$checkOrg->fetch()) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Selected organization not found.']);
            exit;
        }
    }
    // Option 2: Create new organization (pending accreditation)
    elseif (!empty($_POST['new_organization_name'])) {
        $newOrgName = trim($_POST['new_organization_name']);
        if (!$newOrgName) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Organization name cannot be empty.']);
            exit;
        }
        
        $newOrgCategory = trim($_POST['new_organization_category'] ?? 'Other');
        $newOrgBarangay = trim($_POST['barangay']);
        
        // Verify barangay is provided
        if (!$newOrgBarangay) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Barangay is required to create an organization.']);
            exit;
        }
        
        // Insert new organization in pending status
        $insertOrg = $pdo->prepare('INSERT INTO organizations (name, category, barangay, accreditation_status, created_by) VALUES (?, ?, ?, ?, ?)');
        $insertOrg->execute([$newOrgName, $newOrgCategory, $newOrgBarangay, 'pending', 0]);
        $youthOrganizationId = $pdo->lastInsertId();
    }
    else {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Please select or create an organization.']);
        exit;
    }
}

// ── Classification / programs ─────────────────────────────
$classification = isset($_POST['youth_classification'])
    ? json_encode((array)$_POST['youth_classification'])
    : null;

$programs = isset($_POST['programs_interested'])
    ? json_encode((array)$_POST['programs_interested'])
    : null;

// ── Insert ────────────────────────────────────────────────
$sql = 'INSERT INTO youth_users (
    first_name, middle_name, last_name, suffix, gender, birthdate, age,
    civil_status, contact_number, email, password,
    house_number, street, barangay, municipality, province, zip_code,
    status, youth_classification, educational_status, school_name, course_or_grade,
    employment_status, organization_name, organization_type, organization_role,
    years_membership, skills, interests, programs_interested, volunteer_availability
) VALUES (
    :first_name,:middle_name,:last_name,:suffix,:gender,:birthdate,:age,
    :civil_status,:contact_number,:email,:password,
    :house_number,:street,:barangay,:municipality,:province,:zip_code,
    :status,:youth_classification,:educational_status,:school_name,:course_or_grade,
    :employment_status,:organization_name,:organization_type,:organization_role,
    :years_membership,:skills,:interests,:programs_interested,:volunteer_availability
)';

$pdo->prepare($sql)->execute([
    ':first_name'           => trim($_POST['first_name']),
    ':middle_name'          => trim($_POST['middle_name'] ?? ''),
    ':last_name'            => trim($_POST['last_name']),
    ':suffix'               => trim($_POST['suffix'] ?? ''),
    ':gender'               => $_POST['gender'],
    ':birthdate'            => $_POST['birthdate'],
    ':age'                  => (int)$_POST['age'],
    ':civil_status'         => $_POST['civil_status'],
    ':contact_number'       => trim($_POST['contact_number']),
    ':email'                => $email,
    ':password'             => $hash,
    ':house_number'         => trim($_POST['house_number'] ?? ''),
    ':street'               => trim($_POST['street'] ?? ''),
    ':barangay'             => $_POST['barangay'],
    ':municipality'         => $_POST['municipality'] ?? 'Sta. Cruz',
    ':province'             => $_POST['province']     ?? 'Laguna',
    ':zip_code'             => $_POST['zip_code']     ?? '4009',
    ':status'               => 'pending',
    ':youth_classification' => $classification,
    ':educational_status'   => $_POST['educational_status']   ?? null,
    ':school_name'          => $_POST['school_name']          ?? null,
    ':course_or_grade'      => $_POST['course_or_grade']      ?? null,
    ':employment_status'    => $_POST['employment_status']    ?? null,
    ':organization_name'    => $_POST['organization_name']    ?? null,
    ':organization_type'    => $_POST['organization_type']    ?? null,
    ':organization_role'    => $_POST['organization_role']    ?? null,
    ':years_membership'     => (int)($_POST['years_membership'] ?? 0),
    ':skills'               => $_POST['skills']               ?? null,
    ':interests'            => $_POST['interests']            ?? null,
    ':programs_interested'  => $programs,
    ':volunteer_availability' => $_POST['volunteer_availability'] ?? null,
]);

$userId = $pdo->lastInsertId();

// ── Associate youth with organization ──────────────────────
if ($youthOrganizationId) {
    // Check if organization already has a president
    $checkPres = $pdo->prepare('SELECT president_id FROM organizations WHERE id = ? LIMIT 1');
    $checkPres->execute([$youthOrganizationId]);
    $orgCheck = $checkPres->fetch();
    $isFirstMember = !$orgCheck['president_id'];
    
    $memberRole = $isFirstMember ? 'President' : 'Member';
    $memberSql = 'INSERT INTO organization_members (organization_id, user_id, role, joined_at, is_active) 
                  VALUES (?, ?, ?, NOW(), 1) 
                  ON DUPLICATE KEY UPDATE is_active = 1';
    $pdo->prepare($memberSql)->execute([$youthOrganizationId, $userId, $memberRole]);
    
    // If first member, set as president
    if ($isFirstMember) {
        $updatePres = $pdo->prepare('UPDATE organizations SET president_id = ?, president_since = CURDATE() WHERE id = ?');
        $updatePres->execute([$userId, $youthOrganizationId]);
        
        // Log in history
        $historyLog = $pdo->prepare('
            INSERT INTO organization_president_history (organization_id, president_id, started_at, reason)
            VALUES (?, ?, CURDATE(), "initial_founding")
        ');
        $historyLog->execute([$youthOrganizationId, $userId]);
    }
}

// ── Create Organization President Record ──────────────────
if ($registerAs === 'organization_president' && $organizationId) {
    $fullName = trim($_POST['first_name']) . ' ' . 
                trim($_POST['middle_name'] ?? '') . ' ' . 
                trim($_POST['last_name']);
    $fullName = preg_replace('/\s+/', ' ', $fullName); // Remove extra spaces
    
    $presidentSql = 'INSERT INTO organization_presidents (
        organization_id, user_id, email, password, full_name, 
        contact_number, is_active, created_at
    ) VALUES (
        :organization_id, :user_id, :email, :password, :full_name,
        :contact_number, 0, NOW()
    )';
    
    $pdo->prepare($presidentSql)->execute([
        ':organization_id' => $organizationId,
        ':user_id'         => $userId,
        ':email'           => $email,
        ':password'        => $hash,
        ':full_name'       => $fullName,
        ':contact_number'  => trim($_POST['contact_number'])
    ]);
    
    // Update organization with president_id
    $updateOrg = $pdo->prepare('UPDATE organizations SET president_id = ? WHERE id = ?');
    $updateOrg->execute([$userId, $organizationId]);
    
    $message = 'Organization President registration submitted! Your account is pending approval. You will be notified once approved.';
} else if ($youthOrganizationId) {
    // For youth members with pending org, provide next steps
    $org = $pdo->prepare('SELECT accreditation_status FROM organizations WHERE id = ?');
    $org->execute([$youthOrganizationId]);
    $orgData = $org->fetch();
    
    if ($orgData['accreditation_status'] === 'pending') {
        $message = 'Registration submitted! Your account is pending approval. Your organization is also pending accreditation. Next Steps: 1) You will receive an email with instructions to submit your organization\'s required documents (Constitution, Officers List, Financial Report, etc.) to the LYDO Office. 2) Please gather these documents from your organization officers and upload them through your dashboard. 3) The LYDO Admin will review and approve your organization. You will be notified of the status.';
    } else {
        $message = 'Registration submitted! Your account is pending approval by the LYDO office. You will be notified once approved.';
    }
} else {
    $message = 'Registration submitted! Your account is pending approval by the LYDO office. You will be notified once approved.';
}

echo json_encode([
    'success' => true,
    'message' => $message,
]);
