<?php
require_once 'config.php';
requireLogin();

$pdo = db();
$admin = currentAdmin();

// ── Handle POST actions ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_president') {
        $orgId = (int)$_POST['organization_id'];
        $newPresidentId = (int)$_POST['new_president_id'];
        $reason = trim($_POST['reason'] ?? 'changed');

        // Verify org and new president exist
        $org = $pdo->prepare('SELECT president_id FROM organizations WHERE id = ?');
        $org->execute([$orgId]);
        $orgData = $org->fetch();
        if (!$orgData) {
            flash('error', 'Organization not found.');
            header('Location: organization_presidents.php');
            exit;
        }

        $member = $pdo->prepare('SELECT id FROM organization_members WHERE organization_id = ? AND user_id = ? AND is_active = 1');
        $member->execute([$orgId, $newPresidentId]);
        if (!$member->fetch()) {
            flash('error', 'New president must be an active member of the organization.');
            header('Location: organization_presidents.php');
            exit;
        }

        // End current president history
        if ($orgData['president_id']) {
            $pdo->prepare('
                UPDATE organization_president_history 
                SET ended_at = CURDATE() 
                WHERE organization_id = ? AND ended_at IS NULL
            ')->execute([$orgId]);
        }

        // Update org president
        $pdo->prepare('UPDATE organizations SET president_id = ?, president_since = CURDATE() WHERE id = ?')
            ->execute([$newPresidentId, $orgId]);

        // Log in history
        $pdo->prepare('
            INSERT INTO organization_president_history (organization_id, president_id, started_at, reason, changed_by)
            VALUES (?, ?, CURDATE(), ?, ?)
        ')->execute([$orgId, $newPresidentId, $reason, $admin['id']]);

        // Update member role
        $pdo->prepare('UPDATE organization_members SET role = ? WHERE organization_id = ? AND user_id = ?')
            ->execute(['President', $orgId, $newPresidentId]);
        
        // Set old president to member (if still active)
        if ($orgData['president_id']) {
            $pdo->prepare('UPDATE organization_members SET role = ? WHERE organization_id = ? AND user_id = ?')
                ->execute(['Member', $orgId, $orgData['president_id']]);
        }

        flash('success', 'Organization president changed successfully.');
        header('Location: organization_presidents.php');
        exit;
    }
}

// Get all organizations with president info
$orgs = $pdo->query('
    SELECT o.*,
           CONCAT(u.first_name, " ", u.last_name) as president_name,
           u.email as president_email,
           u.age as president_age,
           u.educational_level,
           u.graduation_year,
           (SELECT COUNT(*) FROM organization_members WHERE organization_id = o.id AND is_active = 1) as member_count,
           (SELECT COUNT(*) FROM organization_president_history WHERE organization_id = o.id) as president_changes,
           COALESCE(o.accreditation_status, "pending") as accred_status
    FROM organizations o
    LEFT JOIN youth_users u ON o.president_id = u.id
    ORDER BY o.name
')->fetchAll();

// Separate upcoming graduations (current year or next)
$currentYear = date('Y');
$upcomingGraduations = array_filter($orgs, fn($o) => 
    $o['graduation_year'] && $o['graduation_year'] <= intval($currentYear) + 1 && $o['president_id']
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Organization Presidents – LYDO Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="admin.css"/>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-wrap">
<?php include 'topbar.php'; ?>
<main class="content">

<?php if ($msg=flash('success')): ?><div class="flash success"><i class="fas fa-check-circle"></i><?=htmlspecialchars($msg)?></div><?php endif; ?>
<?php if ($msg=flash('error')):   ?><div class="flash error"><i class="fas fa-exclamation-circle"></i><?=htmlspecialchars($msg)?></div><?php endif; ?>

<div class="page-header">
  <div>
    <h2>Organization Presidents</h2>
    <p>Manage organization leadership and president transitions.</p>
  </div>
</div>

<?php if (!empty($upcomingGraduations)): ?>
<div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:15px;margin-bottom:20px">
  <p style="margin:0;color:#856404"><i class="fas fa-exclamation-triangle"></i> <strong><?=count($upcomingGraduations)?> president(s)</strong> graduating this year or next. Consider planning transitions.</p>
</div>
<?php endif; ?>

<!-- Summary Stats -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
  <div class="stat-card blue"><div class="stat-icon"><i class="fas fa-sitemap"></i></div><div><span class="stat-val"><?=count($orgs)?></span><span class="stat-lbl">Total Organizations</span></div></div>
  <div class="stat-card green"><div class="stat-icon"><i class="fas fa-crown"></i></div><div><span class="stat-val"><?=count(array_filter($orgs, fn($o) => $o['president_id']))?></span><span class="stat-lbl">With President</span></div></div>
  <div class="stat-card orange"><div class="stat-icon"><i class="fas fa-clock"></i></div><div><span class="stat-val"><?=count($upcomingGraduations)?></span><span class="stat-lbl">Upcoming Changes</span></div></div>
  <div class="stat-card" style="border:1px solid #e2e8f0"><div class="stat-icon" style="background:#f1f5f9;color:#475569"><i class="fas fa-history"></i></div><div><span class="stat-val"><?=array_sum(array_map(fn($o) => $o['president_changes'], $orgs))?></span><span class="stat-lbl">Total Changes</span></div></div>
</div>

<div class="card">
  <div class="table-wrap">
    <table class="tbl">
      <thead>
        <tr>
          <th>#</th>
          <th>Organization</th>
          <th>Current President</th>
          <th>Since</th>
          <th>Grad Year</th>
          <th>Accreditation</th>
          <th>Members</th>
          <th>Changes</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orgs as $i => $org): ?>
          <tr <?php echo $org['graduation_year'] && $org['graduation_year'] <= intval($currentYear) + 1 ? 'style="background:#fffacd"' : ''; ?>>
            <td><?=$i+1?></td>
            <td><strong><?=htmlspecialchars($org['name'])?></strong></td>
            <td>
              <?php if ($org['president_id']): ?>
                <div><strong><?=htmlspecialchars($org['president_name'])?></strong></div>
                <div style="font-size:12px;color:#718096"><?=htmlspecialchars($org['president_email'])?></div>
              <?php else: ?>
                <span style="color:#a0aec0"><i class="fas fa-minus"></i> No president</span>
              <?php endif; ?>
            </td>
            <td>
              <?php echo $org['president_since'] ? date('M d, Y', strtotime($org['president_since'])) : '—'; ?>
            </td>
            <td>
              <?php if ($org['graduation_year']): ?>
                <span style="background:<?=($org['graduation_year'] <= intval($currentYear) + 1 ? '#fff3cd' : '#e7f3ff');?>;padding:4px 8px;border-radius:4px;font-size:12px">
                  <?=$org['graduation_year']?>
                  <?php if ($org['graduation_year'] <= intval($currentYear)) echo '<span style="color:#dc3545">⚠ This year</span>'; ?>
                </span>
              <?php else: ?>
                <span style="color:#a0aec0">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php
                $status = $org['accred_status'] ?? 'pending';
                $colors = [
                  'accredited' => ['#d4edda', '#155724'],
                  'pending' => ['#fff3cd', '#856404'],
                  'rejected' => ['#f8d7da', '#721c24'],
                  'review' => ['#cfe2ff', '#084298']
                ];
                $c = $colors[$status] ?? $colors['pending'];
              ?>
              <span style="background:<?=$c[0]?>;color:<?=$c[1]?>;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:600">
                <?=ucfirst($status)?>
              </span>
            </td>
            <td><?=$org['member_count']?></td>
            <td><?=$org['president_changes']?></td>
            <td>
              <button class="btn-secondary" onclick="changePresident(<?=$org['id']?>, '<?=htmlspecialchars($org['name'])?>')">
                <i class="fas fa-sync-alt"></i> Change
              </button>
              <button class="btn-secondary" onclick="viewHistory(<?=$org['id']?>)">
                <i class="fas fa-history"></i> History
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</main>
</div>

<!-- Change President Modal -->
<div id="changePresidentModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="width:90%;max-width:500px;padding:25px;max-height:80vh;overflow-y:auto">
    <h3 style="margin:0 0 15px 0">Change Organization President</h3>
    <form method="POST">
      <input type="hidden" name="action" value="change_president">
      <input type="hidden" name="organization_id" id="presidentOrgId">
      
      <div style="margin-bottom:15px">
        <label style="display:block;font-weight:600;margin-bottom:5px;font-size:14px">Organization</label>
        <input type="text" id="presidentOrgName" readonly style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:6px;background:#f8f9fa;font-size:14px">
      </div>

      <div style="margin-bottom:15px">
        <label style="display:block;font-weight:600;margin-bottom:5px;font-size:14px">New President</label>
        <select name="new_president_id" id="presidentSelect" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:6px;font-size:14px" required>
          <option value="">Select member...</option>
        </select>
      </div>

      <div style="margin-bottom:15px">
        <label style="display:block;font-weight:600;margin-bottom:5px;font-size:14px">Reason</label>
        <select name="reason" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:6px;font-size:14px">
          <option value="changed">Changed</option>
          <option value="graduated">Graduated</option>
          <option value="resigned">Resigned</option>
          <option value="transferred">Transferred</option>
          <option value="promoted">Promoted</option>
          <option value="other">Other</option>
        </select>
      </div>

      <div style="display:flex;gap:10px">
        <button type="button" class="btn-secondary" onclick="closeModal('changePresidentModal')" style="flex:1">Cancel</button>
        <button type="submit" class="btn-success" style="flex:1">Change President</button>
      </div>
    </form>
  </div>
</div>

<!-- History Modal -->
<div id="historyModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="width:90%;max-width:600px;padding:25px;max-height:80vh;overflow-y:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px">
      <h3 style="margin:0">President History</h3>
      <button onclick="closeModal('historyModal')" style="background:none;border:none;font-size:20px;cursor:pointer">×</button>
    </div>
    <div id="historyContent" style="max-height:500px;overflow-y:auto"></div>
  </div>
</div>

<script>
function changePresident(orgId, orgName) {
    document.getElementById('changePresidentModal').style.display = 'flex';
    document.getElementById('presidentOrgId').value = orgId;
    document.getElementById('presidentOrgName').value = orgName;
    
    // Load members for this org
    fetch('/admin2/get_org_members.php?org_id=' + orgId)
        .then(r => r.json())
        .then(data => {
            const select = document.getElementById('presidentSelect');
            select.innerHTML = '<option value="">Select member...</option>';
            data.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = m.first_name + ' ' + m.last_name + ' (' + m.role + ')';
                select.appendChild(opt);
            });
        });
}

function viewHistory(orgId) {
    document.getElementById('historyModal').style.display = 'flex';
    fetch('/admin2/get_president_history.php?org_id=' + orgId)
        .then(r => r.json())
        .then(data => {
            let html = '';
            if (data.length === 0) {
                html = '<p style="color:#718096;text-align:center;padding:20px">No history yet</p>';
            } else {
                data.forEach((h, i) => {
                    const duration = h.ended_at ? 
                        Math.floor((new Date(h.ended_at) - new Date(h.started_at)) / (1000*60*60*24)) + ' days' :
                        'Current';
                    html += `<div style="padding:15px;border-bottom:1px solid #e2e8f0;${i === 0 ? 'border-left:4px solid #28a745' : ''}">
                        <div style="font-weight:600">${h.president_name}</div>
                        <div style="font-size:12px;color:#718096">
                            ${h.started_at} to ${h.ended_at || 'Present'} (${duration})
                        </div>
                        <div style="font-size:12px;color:#a0aec0;margin-top:5px">
                            Reason: ${h.reason || 'unknown'}
                        </div>
                    </div>`;
                });
            }
            document.getElementById('historyContent').innerHTML = html;
        });
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

window.onclick = function(e) {
    if (e.target.id.endsWith('Modal')) {
        e.target.style.display = 'none';
    }
}
</script>

<style>
.btn-success {
    background: #28a745;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
}
.btn-success:hover {
    background: #218838;
}
.stat-card.orange {
    border: 1px solid #ffe4c4;
    background: #fff8f0;
}
.stat-card.orange .stat-val {
    color: #ff9800;
}
</style>

</body>
</html>
