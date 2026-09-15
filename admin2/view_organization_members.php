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

$members = $pdo->prepare('
    SELECT u.id, u.first_name, u.middle_name, u.last_name, u.email, u.contact_number, u.barangay, u.status,
           u.graduation_year, u.educational_level,
           om.role, om.joined_at, om.is_active,
           (o.president_id = u.id) as is_president
    FROM organization_members om
    JOIN youth_users u ON om.user_id = u.id
    JOIN organizations o ON om.organization_id = o.id
    WHERE om.organization_id = ?
    ORDER BY om.role DESC, om.joined_at ASC
');
$members->execute([$orgId]);
$memberList = $members->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Members - <?=htmlspecialchars($orgData['name'])?></title>
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
            max-width: 1000px;
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
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #e2e8f0;
        }
        .stat {
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #007bff;
        }
        .stat-label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .content {
            padding: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #f8f9fa;
            border-bottom: 2px solid #e2e8f0;
        }
        th {
            padding: 12px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        tbody tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #718096;
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
                    <?=htmlspecialchars($orgData['category'])?>
                </div>
            </div>
            <button onclick="window.close()" style="background: rgba(255,255,255,0.2); border: 1px solid white; color: white; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-value"><?=count($memberList)?></div>
                <div class="stat-label">Total Members</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?=count(array_filter($memberList, fn($m) => $m['is_active']))?></div>
                <div class="stat-label">Active Members</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?=count(array_filter($memberList, fn($m) => $m['status'] === 'approved'))?></div>
                <div class="stat-label">Approved</div>
            </div>
        </div>

        <div class="content">
            <?php if (empty($memberList)): ?>
                <div class="empty">
                    <div style="font-size: 40px; margin-bottom: 10px; opacity: 0.5;">
                        <i class="fas fa-users"></i>
                    </div>
                    <p>No members found</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Barangay</th>
                            <th>Role</th>
                            <th>Grad Year</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($memberList as $member): ?>
                            <tr style="<?php echo $member['is_president'] ? 'background:#fff8f0;border-left:4px solid #ff9800' : ''; ?>">
                                <td>
                                    <strong><?=htmlspecialchars($member['first_name'] . ' ' . $member['last_name'])?></strong>
                                    <?php if ($member['is_president']): ?>
                                        <span class="badge" style="background:#ff9800;color:white;font-size:10px;margin-left:5px">👑 President</span>
                                    <?php endif; ?>
                                </td>
                                <td><?=htmlspecialchars($member['email'])?></td>
                                <td><?=htmlspecialchars($member['contact_number'])?></td>
                                <td><?=htmlspecialchars($member['barangay'])?></td>
                                <td><?=htmlspecialchars($member['role'])?></td>
                                <td><?php echo $member['graduation_year'] ? $member['graduation_year'] : '—'; ?></td>
                                <td>
                                    <span class="badge <?php echo $member['status'] === 'approved' ? 'badge-active' : ($member['status'] === 'pending' ? 'badge-pending' : 'badge-inactive'); ?>">
                                        <?=ucfirst($member['status'])?>
                                    </span>
                                </td>
                                <td><?=date('M d, Y', strtotime($member['joined_at']))?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
