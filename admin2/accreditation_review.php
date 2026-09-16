<?php
require_once 'config.php';
requireLogin();

$pdo = db();
$admin = currentAdmin();

// ── Handle POST actions ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $appId = (int)($_POST['app_id'] ?? 0);

    if (!$appId) {
        flash('error', 'Invalid application ID.');
        header('Location: accreditation_review.php');
        exit;
    }

    // Verify application exists
    $verifyStmt = $pdo->prepare('SELECT id, organization_id FROM accreditation_applications WHERE id = ? LIMIT 1');
    $verifyStmt->execute([$appId]);
    $app = $verifyStmt->fetch();
    if (!$app) {
        flash('error', 'Application not found.');
        header('Location: accreditation_review.php');
        exit;
    }

    if ($action === 'approve') {
        $certNo    = 'LYDO-' . date('Y') . '-' . str_pad($appId, 4, '0', STR_PAD_LEFT);
        $validUntil= date('Y-m-d', strtotime('+1 year'));
        $pdo->prepare('UPDATE accreditation_applications SET status="approved", certificate_no=?, valid_until=?, reviewed_by=?, reviewed_at=CURRENT_TIMESTAMP WHERE id=?')
            ->execute([$certNo, $validUntil, $admin['id'], $appId]);
        
        // Update organization status
        $pdo->prepare('UPDATE organizations SET accreditation_status="active" WHERE id=?')
            ->execute([$app['organization_id']]);
        
        flash('success', "Application approved. Certificate: $certNo");
        header('Location: accreditation_review.php');
        exit;
    }

    if ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? '');
        if (!$reason) {
            flash('error', 'Rejection reason is required.');
            header('Location: accreditation_review.php');
            exit;
        }
        $pdo->prepare('UPDATE accreditation_applications SET status="rejected", rejection_reason=?, reviewed_by=?, reviewed_at=CURRENT_TIMESTAMP WHERE id=?')
            ->execute([$reason, $admin['id'], $appId]);
        
        // Update organization status
        $pdo->prepare('UPDATE organizations SET accreditation_status="rejected" WHERE id=?')
            ->execute([$app['organization_id']]);
        
        flash('success', 'Application rejected.');
        header('Location: accreditation_review.php');
        exit;
    }

    if ($action === 'request_revision') {
        $reason = trim($_POST['revision_reason'] ?? '');
        if (!$reason) {
            flash('error', 'Revision reason is required.');
            header('Location: accreditation_review.php');
            exit;
        }
        $pdo->prepare('UPDATE accreditation_applications SET status="needs_revision", reviewed_by=?, reviewed_at=CURRENT_TIMESTAMP WHERE id=?')
            ->execute([$admin['id'], $appId]);
        
        flash('success', 'Revision request sent to organization.');
        header('Location: accreditation_review.php');
        exit;
    }
}

// Get all pending applications
$pendingApps = $pdo->query('
    SELECT a.*, o.name as org_name, o.barangay, o.category,
           (SELECT COUNT(*) FROM organization_members WHERE organization_id = a.organization_id AND is_active = 1) as member_count,
           (SELECT COUNT(*) FROM accreditation_documents WHERE application_id = a.id) as doc_count
    FROM accreditation_applications a
    LEFT JOIN organizations o ON o.id = a.organization_id
    WHERE a.status IN ("submitted", "under_review")
    ORDER BY a.created_at ASC
')->fetchAll();

$approvedApps = $pdo->query('
    SELECT a.*, o.name as org_name, o.barangay, o.category,
           (SELECT COUNT(*) FROM accreditation_documents WHERE application_id = a.id) as doc_count
    FROM accreditation_applications a
    LEFT JOIN organizations o ON o.id = a.organization_id
    WHERE a.status = "approved"
    ORDER BY a.reviewed_at DESC
')->fetchAll();

$rejectedApps = $pdo->query('
    SELECT a.*, o.name as org_name, o.barangay, o.category
    FROM accreditation_applications a
    LEFT JOIN organizations o ON o.id = a.organization_id
    WHERE a.status = "rejected"
    ORDER BY a.reviewed_at DESC
')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Accreditation Review – LYDO Admin</title>
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
  <div><h2>Organization Accreditation Review</h2><p>Manage pending organization accreditations and review submitted documents.</p></div>
</div>

<!-- Tabs -->
<div style="display:flex;gap:10px;margin-bottom:20px;border-bottom:1px solid #e2e8f0">
  <button class="tab-btn active" onclick="switchTab('pending')">
    <i class="fas fa-hourglass-half"></i> Pending (<?=count($pendingApps)?>)
  </button>
  <button class="tab-btn" onclick="switchTab('approved')">
    <i class="fas fa-check-circle"></i> Approved (<?=count($approvedApps)?>)
  </button>
  <button class="tab-btn" onclick="switchTab('rejected')">
    <i class="fas fa-times-circle"></i> Rejected (<?=count($rejectedApps)?>)
  </button>
</div>

<!-- Pending Tab -->
<div id="pending-tab" class="tab-content" style="display:block">
  <?php if (empty($pendingApps)): ?>
    <div style="text-align:center;padding:40px;color:#718096">
      <i class="fas fa-inbox" style="font-size:40px;margin-bottom:10px;display:block;opacity:0.5"></i>
      <p>No pending accreditations</p>
    </div>
  <?php else: ?>
    <?php foreach ($pendingApps as $app): ?>
      <div class="card" style="margin-bottom:15px">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:15px">
          <div>
            <h3 style="margin:0;font-size:18px"><?=htmlspecialchars($app['org_name'])?></h3>
            <p style="margin:5px 0 0 0;color:#718096;font-size:13px">
              <i class="fas fa-map-marker-alt"></i> <?=htmlspecialchars($app['barangay'])?> • 
              <?=htmlspecialchars($app['category'])?> • 
              <?=$app['member_count']?> member<?=$app['member_count']!==1?'s':''?>
            </p>
          </div>
          <span class="badge" style="background:#ffc107;color:#000">
            <?=ucwords(str_replace('_', ' ', $app['status']))?>
          </span>
        </div>

        <div style="background:#f8f9fa;padding:10px;border-radius:6px;margin-bottom:15px;font-size:13px">
          <p style="margin:0"><strong>Submitted:</strong> <?=date('M d, Y H:i', strtotime($app['created_at']))?></p>
          <p style="margin:5px 0 0 0"><strong>Documents:</strong> <?=$app['doc_count']?> file<?=$app['doc_count']!==1?'s':''?> uploaded</p>
        </div>

        <div style="display:flex;gap:10px">
          <button class="btn-secondary" onclick="viewDocuments(<?=$app['id']?>)">
            <i class="fas fa-file-pdf"></i> View Documents
          </button>
          <button class="btn-success" onclick="showApproveModal(<?=$app['id']?>)">
            <i class="fas fa-check"></i> Approve
          </button>
          <button class="btn-warning" onclick="showRevisionModal(<?=$app['id']?>)">
            <i class="fas fa-edit"></i> Request Revision
          </button>
          <button class="btn-danger" onclick="showRejectModal(<?=$app['id']?>)">
            <i class="fas fa-times"></i> Reject
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Approved Tab -->
<div id="approved-tab" class="tab-content" style="display:none">
  <?php if (empty($approvedApps)): ?>
    <div style="text-align:center;padding:40px;color:#718096">
      <i class="fas fa-check-circle" style="font-size:40px;margin-bottom:10px;display:block;opacity:0.5"></i>
      <p>No approved applications yet</p>
    </div>
  <?php else: ?>
    <?php foreach ($approvedApps as $app): ?>
      <div class="card" style="margin-bottom:15px;border-left:4px solid #28a745">
        <div style="display:flex;justify-content:space-between;align-items:start">
          <div>
            <h3 style="margin:0;font-size:18px"><?=htmlspecialchars($app['org_name'])?></h3>
            <p style="margin:5px 0 0 0;color:#718096;font-size:13px">
              <i class="fas fa-map-marker-alt"></i> <?=htmlspecialchars($app['barangay'])?> • 
              <?=htmlspecialchars($app['category'])?>
            </p>
            <?php if ($app['certificate_no']): ?>
              <p style="margin:10px 0 0 0;padding:10px;background:#d4edda;border-radius:4px;font-size:13px;color:#155724">
                <strong>Certificate:</strong> <?=htmlspecialchars($app['certificate_no'])?> • 
                <strong>Valid Until:</strong> <?=date('F j, Y', strtotime($app['valid_until']))?>
              </p>
            <?php endif; ?>
          </div>
          <span class="badge" style="background:#28a745;color:#fff">Approved</span>
        </div>
        <div style="display:flex;gap:10px;margin-top:15px">
          <button class="btn-secondary" onclick="viewDocuments(<?=$app['id']?>)">
            <i class="fas fa-file-pdf"></i> View Documents
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Rejected Tab -->
<div id="rejected-tab" class="tab-content" style="display:none">
  <?php if (empty($rejectedApps)): ?>
    <div style="text-align:center;padding:40px;color:#718096">
      <i class="fas fa-times-circle" style="font-size:40px;margin-bottom:10px;display:block;opacity:0.5"></i>
      <p>No rejected applications</p>
    </div>
  <?php else: ?>
    <?php foreach ($rejectedApps as $app): ?>
      <div class="card" style="margin-bottom:15px;border-left:4px solid #dc3545">
        <div style="display:flex;justify-content:space-between;align-items:start">
          <div>
            <h3 style="margin:0;font-size:18px"><?=htmlspecialchars($app['org_name'])?></h3>
            <p style="margin:5px 0 0 0;color:#718096;font-size:13px">
              <i class="fas fa-map-marker-alt"></i> <?=htmlspecialchars($app['barangay'])?> • 
              <?=htmlspecialchars($app['category'])?>
            </p>
            <?php if ($app['rejection_reason']): ?>
              <p style="margin:10px 0 0 0;padding:10px;background:#ffe5e5;border-radius:4px;font-size:13px;color:#721c24">
                <strong>Reason:</strong> <?=htmlspecialchars($app['rejection_reason'])?>
              </p>
            <?php endif; ?>
          </div>
          <span class="badge" style="background:#dc3545;color:#fff">Rejected</span>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</main>
</div>

<!-- Modals -->
<div id="approveModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="width:90%;max-width:400px;padding:25px">
    <h3 style="margin:0 0 15px 0">Approve Organization?</h3>
    <p style="margin:0 0 20px 0;color:#718096;font-size:14px">
      This organization will be marked as accredited and can now participate in LYDO programs.
    </p>
    <form method="POST" style="display:flex;gap:10px">
      <input type="hidden" name="action" value="approve">
      <input type="hidden" name="organization_id" id="approveOrgId">
      <button type="button" class="btn-secondary" onclick="closeModal('approveModal')" style="flex:1">Cancel</button>
      <button type="submit" class="btn-success" style="flex:1">Approve</button>
    </form>
  </div>
</div>

<div id="rejectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="width:90%;max-width:500px;padding:25px">
    <h3 style="margin:0 0 15px 0">Reject Organization?</h3>
    <form method="POST">
      <input type="hidden" name="action" value="reject">
      <input type="hidden" name="organization_id" id="rejectOrgId">
      <label style="display:block;margin-bottom:10px">
        <strong style="font-size:14px">Reason for Rejection</strong>
        <textarea name="review_comments" style="width:100%;height:100px;padding:10px;border:1px solid #e2e8f0;border-radius:6px;font-family:inherit;margin-top:5px" required></textarea>
      </label>
      <div style="display:flex;gap:10px">
        <button type="button" class="btn-secondary" onclick="closeModal('rejectModal')" style="flex:1">Cancel</button>
        <button type="submit" class="btn-danger" style="flex:1">Reject</button>
      </div>
    </form>
  </div>
</div>

<div id="revisionModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="width:90%;max-width:500px;padding:25px">
    <h3 style="margin:0 0 15px 0">Request Revision</h3>
    <form method="POST">
      <input type="hidden" name="action" value="request_revision">
      <input type="hidden" name="organization_id" id="revisionOrgId">
      <label style="display:block;margin-bottom:10px">
        <strong style="font-size:14px">Comments for Organization</strong>
        <textarea name="review_comments" style="width:100%;height:100px;padding:10px;border:1px solid #e2e8f0;border-radius:6px;font-family:inherit;margin-top:5px" required placeholder="Describe what needs to be revised..."></textarea>
      </label>
      <div style="display:flex;gap:10px">
        <button type="button" class="btn-secondary" onclick="closeModal('revisionModal')" style="flex:1">Cancel</button>
        <button type="submit" class="btn-warning" style="flex:1">Send</button>
      </div>
    </form>
  </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById(tab + '-tab').style.display = 'block';
    event.target.classList.add('active');
}

function openModal(modalId, orgId) {
    document.getElementById(modalId).style.display = 'flex';
    if (orgId) {
        document.getElementById(modalId.replace('Modal', 'OrgId')).value = orgId;
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function approveOrg(orgId) { openModal('approveModal', orgId); }
function rejectOrg(orgId) { openModal('rejectModal', orgId); }
function requestRevision(orgId) { openModal('revisionModal', orgId); }

function viewDocuments(orgId) {
    window.open('/admin2/view_accreditation_files.php?org_id=' + orgId, '_blank');
}

function viewMembers(orgId) {
    window.open('/admin2/view_organization_members.php?org_id=' + orgId, '_blank');
}

window.onclick = function(e) {
    if (e.target.id.includes('Modal') && e.target.id.endsWith('Modal')) {
        e.target.style.display = 'none';
    }
}
</script>

<style>
.tab-btn {
    background: transparent;
    border: none;
    padding: 12px 16px;
    cursor: pointer;
    font-size: 14px;
    color: #718096;
    border-bottom: 2px solid transparent;
    transition: all 0.3s;
}
.tab-btn.active {
    color: #007bff;
    border-bottom-color: #007bff;
}
.tab-btn:hover {
    color: #2d3748;
}
.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
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
.btn-warning {
    background: #ff9800;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
}
.btn-warning:hover {
    background: #e68900;
}
.btn-danger {
    background: #dc3545;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
}
.btn-danger:hover {
    background: #c82333;
}
</style>

<!-- APPROVE MODAL -->
<div id="approveModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;padding:30px;border-radius:12px;max-width:500px;width:90%">
    <h3 style="margin:0 0 15px 0">Approve Application?</h3>
    <p style="color:#666;margin:0 0 20px 0">This will mark the organization as accredited and generate a certificate number.</p>
    <form method="POST" style="display:flex;gap:10px;justify-content:flex-end">
      <input type="hidden" name="action" value="approve"/>
      <input type="hidden" name="app_id" id="modalAppId" value=""/>
      <button type="button" onclick="document.getElementById('approveModal').style.display='none'" style="padding:10px 20px;background:#e2e8f0;border:none;border-radius:6px;cursor:pointer">Cancel</button>
      <button type="submit" style="padding:10px 20px;background:#28a745;color:#fff;border:none;border-radius:6px;cursor:pointer">Approve</button>
    </form>
  </div>
</div>

<!-- REVISION MODAL -->
<div id="revisionModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;padding:30px;border-radius:12px;max-width:500px;width:90%">
    <h3 style="margin:0 0 15px 0">Request Revision</h3>
    <form method="POST">
      <input type="hidden" name="action" value="request_revision"/>
      <input type="hidden" name="app_id" id="revisionAppId" value=""/>
      <div style="margin-bottom:15px">
        <label style="display:block;margin-bottom:5px;font-weight:600">Reason for Revision:</label>
        <textarea name="revision_reason" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:6px;font-family:inherit;min-height:100px" required placeholder="Explain what needs to be revised..."></textarea>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('revisionModal').style.display='none'" style="padding:10px 20px;background:#e2e8f0;border:none;border-radius:6px;cursor:pointer">Cancel</button>
        <button type="submit" style="padding:10px 20px;background:#ff9800;color:#fff;border:none;border-radius:6px;cursor:pointer">Send Revision Request</button>
      </div>
    </form>
  </div>
</div>

<!-- REJECT MODAL -->
<div id="rejectModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;padding:30px;border-radius:12px;max-width:500px;width:90%">
    <h3 style="margin:0 0 15px 0">Reject Application</h3>
    <form method="POST">
      <input type="hidden" name="action" value="reject"/>
      <input type="hidden" name="app_id" id="rejectAppId" value=""/>
      <div style="margin-bottom:15px">
        <label style="display:block;margin-bottom:5px;font-weight:600">Reason for Rejection:</label>
        <textarea name="rejection_reason" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:6px;font-family:inherit;min-height:100px" required placeholder="Explain why the application is being rejected..."></textarea>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" onclick="document.getElementById('rejectModal').style.display='none'" style="padding:10px 20px;background:#e2e8f0;border:none;border-radius:6px;cursor:pointer">Cancel</button>
        <button type="submit" style="padding:10px 20px;background:#dc3545;color:#fff;border:none;border-radius:6px;cursor:pointer">Reject</button>
      </div>
    </form>
  </div>
</div>

<script>
function switchTab(tabName) {
  document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById(tabName + '-tab').style.display = 'block';
  event.target.classList.add('active');
}

function showApproveModal(appId) {
  document.getElementById('modalAppId').value = appId;
  document.getElementById('approveModal').style.display = 'flex';
}

function showRevisionModal(appId) {
  document.getElementById('revisionAppId').value = appId;
  document.getElementById('revisionModal').style.display = 'flex';
}

function showRejectModal(appId) {
  document.getElementById('rejectAppId').value = appId;
  document.getElementById('rejectModal').style.display = 'flex';
}

function viewDocuments(appId) {
  window.location.href = 'admin2/accreditation.php?view=' + appId;
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
  ['approveModal', 'revisionModal', 'rejectModal'].forEach(modalId => {
    const modal = document.getElementById(modalId);
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  });
});
</script>

</body>
</html>
