<?php
require_once 'config.php';
requireLogin();

$pdo = db();
$admin = currentAdmin();

// ── Handle POST actions ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $orgId = (int)$_POST['organization_id'];
        $pdo->prepare('UPDATE organizations SET accreditation_status = ? WHERE id = ?')->execute(['active', $orgId]);
        $pdo->prepare('UPDATE accreditation_submissions SET status = ?, reviewed_by = ?, review_date = NOW() WHERE organization_id = ?')
            ->execute(['approved', $admin['id'], $orgId]);
        flash('success', 'Organization accredited successfully.');
        header('Location: accreditation_review.php');
        exit;
    }

    if ($action === 'reject') {
        $orgId = (int)$_POST['organization_id'];
        $comments = trim($_POST['review_comments'] ?? '');
        $pdo->prepare('UPDATE organizations SET accreditation_status = ? WHERE id = ?')->execute(['rejected', $orgId]);
        $pdo->prepare('UPDATE accreditation_submissions SET status = ?, reviewed_by = ?, review_date = NOW(), review_comments = ? WHERE organization_id = ?')
            ->execute(['rejected', $admin['id'], $comments, $orgId]);
        flash('success', 'Organization accreditation rejected.');
        header('Location: accreditation_review.php');
        exit;
    }

    if ($action === 'request_revision') {
        $orgId = (int)$_POST['organization_id'];
        $comments = trim($_POST['review_comments'] ?? '');
        $pdo->prepare('UPDATE organizations SET accreditation_status = ? WHERE id = ?')->execute(['needs_revision', $orgId]);
        $pdo->prepare('UPDATE accreditation_submissions SET status = ?, reviewed_by = ?, review_date = NOW(), review_comments = ? WHERE organization_id = ?')
            ->execute(['needs_revision', $admin['id'], $comments, $orgId]);
        flash('success', 'Revision request sent to organization.');
        header('Location: accreditation_review.php');
        exit;
    }
}

// Get all pending organizations with submissions
$pendingOrgs = $pdo->query('
    SELECT o.*, 
           COALESCE(s.status, "not_submitted") as submission_status,
           s.submission_date,
           s.review_comments,
           (SELECT COUNT(*) FROM organization_accreditation_files WHERE organization_id = o.id) as file_count,
           (SELECT COUNT(*) FROM organization_members WHERE organization_id = o.id AND is_active = 1) as member_count
    FROM organizations o
    LEFT JOIN accreditation_submissions s ON o.id = s.organization_id
    WHERE o.accreditation_status IN ("pending", "needs_revision")
    ORDER BY s.submission_date DESC, o.created_at DESC
')->fetchAll();

$approvedOrgs = $pdo->query('
    SELECT o.*, 
           s.submission_date,
           (SELECT COUNT(*) FROM organization_accreditation_files WHERE organization_id = o.id) as file_count,
           (SELECT COUNT(*) FROM organization_members WHERE organization_id = o.id AND is_active = 1) as member_count
    FROM organizations o
    LEFT JOIN accreditation_submissions s ON o.id = s.organization_id
    WHERE o.accreditation_status = "active"
    ORDER BY o.updated_at DESC
')->fetchAll();

$rejectedOrgs = $pdo->query('
    SELECT o.*, 
           s.submission_date,
           s.review_comments,
           (SELECT COUNT(*) FROM organization_accreditation_files WHERE organization_id = o.id) as file_count,
           (SELECT COUNT(*) FROM organization_members WHERE organization_id = o.id AND is_active = 1) as member_count
    FROM organizations o
    LEFT JOIN accreditation_submissions s ON o.id = s.organization_id
    WHERE o.accreditation_status = "rejected"
    ORDER BY s.submission_date DESC
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
    <i class="fas fa-hourglass-half"></i> Pending (<?=count($pendingOrgs)?>)
  </button>
  <button class="tab-btn" onclick="switchTab('approved')">
    <i class="fas fa-check-circle"></i> Approved (<?=count($approvedOrgs)?>)
  </button>
  <button class="tab-btn" onclick="switchTab('rejected')">
    <i class="fas fa-times-circle"></i> Rejected (<?=count($rejectedOrgs)?>)
  </button>
</div>

<!-- Pending Tab -->
<div id="pending-tab" class="tab-content" style="display:block">
  <?php if (empty($pendingOrgs)): ?>
    <div style="text-align:center;padding:40px;color:#718096">
      <i class="fas fa-inbox" style="font-size:40px;margin-bottom:10px;display:block;opacity:0.5"></i>
      <p>No pending accreditations</p>
    </div>
  <?php else: ?>
    <?php foreach ($pendingOrgs as $org): ?>
      <div class="card" style="margin-bottom:15px">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:15px">
          <div>
            <h3 style="margin:0;font-size:18px"><?=htmlspecialchars($org['name'])?></h3>
            <p style="margin:5px 0 0 0;color:#718096;font-size:13px">
              <i class="fas fa-map-marker-alt"></i> <?=htmlspecialchars($org['barangay'])?> • 
              <?=htmlspecialchars($org['category'])?> • 
              <?=$org['member_count']?> member<?=$org['member_count']!==1?'s':''?>
            </p>
          </div>
          <span class="badge" style="background:#ffc107;color:#000">
            <?=ucfirst($org['submission_status'])?>
          </span>
        </div>

        <?php if ($org['submission_status'] !== 'not_submitted'): ?>
          <div style="background:#f8f9fa;padding:10px;border-radius:6px;margin-bottom:15px;font-size:13px">
            <p style="margin:0"><strong>Submitted:</strong> <?=date('M d, Y H:i', strtotime($org['submission_date']))?></p>
            <p style="margin:5px 0 0 0"><strong>Files:</strong> <?=$org['file_count']?> document<?=$org['file_count']!==1?'s':''?> uploaded</p>
          </div>
        <?php else: ?>
          <div style="background:#fff3cd;padding:10px;border-radius:6px;margin-bottom:15px;font-size:13px;color:#856404">
            <i class="fas fa-exclamation-triangle"></i> No documents submitted yet
          </div>
        <?php endif; ?>

        <div style="display:flex;gap:10px">
          <button class="btn-secondary" onclick="viewDocuments(<?=$org['id']?>)">
            <i class="fas fa-file-pdf"></i> View Documents
          </button>
          <button class="btn-secondary" onclick="viewMembers(<?=$org['id']?>)">
            <i class="fas fa-users"></i> View Members
          </button>
          <button class="btn-success" onclick="approveOrg(<?=$org['id']?>)">
            <i class="fas fa-check"></i> Approve
          </button>
          <button class="btn-warning" onclick="requestRevision(<?=$org['id']?>)">
            <i class="fas fa-edit"></i> Request Revision
          </button>
          <button class="btn-danger" onclick="rejectOrg(<?=$org['id']?>)">
            <i class="fas fa-times"></i> Reject
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Approved Tab -->
<div id="approved-tab" class="tab-content" style="display:none">
  <?php if (empty($approvedOrgs)): ?>
    <div style="text-align:center;padding:40px;color:#718096">
      <i class="fas fa-check-circle" style="font-size:40px;margin-bottom:10px;display:block;opacity:0.5"></i>
      <p>No approved organizations yet</p>
    </div>
  <?php else: ?>
    <?php foreach ($approvedOrgs as $org): ?>
      <div class="card" style="margin-bottom:15px;border-left:4px solid #28a745">
        <div style="display:flex;justify-content:space-between;align-items:start">
          <div>
            <h3 style="margin:0;font-size:18px"><?=htmlspecialchars($org['name'])?></h3>
            <p style="margin:5px 0 0 0;color:#718096;font-size:13px">
              <i class="fas fa-map-marker-alt"></i> <?=htmlspecialchars($org['barangay'])?> • 
              <?=htmlspecialchars($org['category'])?> • 
              <?=$org['member_count']?> member<?=$org['member_count']!==1?'s':''?>
            </p>
          </div>
          <span class="badge" style="background:#28a745;color:#fff">Accredited</span>
        </div>
        <div style="display:flex;gap:10px;margin-top:15px">
          <button class="btn-secondary" onclick="viewDocuments(<?=$org['id']?>)">
            <i class="fas fa-file-pdf"></i> View Documents
          </button>
          <button class="btn-secondary" onclick="viewMembers(<?=$org['id']?>)">
            <i class="fas fa-users"></i> View Members
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Rejected Tab -->
<div id="rejected-tab" class="tab-content" style="display:none">
  <?php if (empty($rejectedOrgs)): ?>
    <div style="text-align:center;padding:40px;color:#718096">
      <i class="fas fa-times-circle" style="font-size:40px;margin-bottom:10px;display:block;opacity:0.5"></i>
      <p>No rejected organizations</p>
    </div>
  <?php else: ?>
    <?php foreach ($rejectedOrgs as $org): ?>
      <div class="card" style="margin-bottom:15px;border-left:4px solid #dc3545">
        <div style="display:flex;justify-content:space-between;align-items:start">
          <div>
            <h3 style="margin:0;font-size:18px"><?=htmlspecialchars($org['name'])?></h3>
            <p style="margin:5px 0 0 0;color:#718096;font-size:13px">
              <i class="fas fa-map-marker-alt"></i> <?=htmlspecialchars($org['barangay'])?> • 
              <?=htmlspecialchars($org['category'])?>
            </p>
            <?php if ($org['review_comments']): ?>
              <p style="margin:10px 0 0 0;padding:10px;background:#ffe5e5;border-radius:4px;font-size:13px;color:#721c24">
                <strong>Reason:</strong> <?=htmlspecialchars($org['review_comments'])?>
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

</body>
</html>
