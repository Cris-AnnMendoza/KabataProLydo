<?php
require_once 'config.php';
requireLogin();

$pdo = db();
$admin = currentAdmin();

// Get all staff activities
$activities = $pdo->query('
    SELECT * FROM staff_activity_log
    ORDER BY created_at DESC
    LIMIT 200
')->fetchAll();

// Group by staff name
$staffStats = [];
foreach ($activities as $activity) {
    if (!isset($staffStats[$activity['staff_name']])) {
        $staffStats[$activity['staff_name']] = [
            'count' => 0,
            'last_action' => $activity['created_at'],
            'actions' => []
        ];
    }
    $staffStats[$activity['staff_name']]['count']++;
    $staffStats[$activity['staff_name']]['last_action'] = $activity['created_at'];
}

// Sort by most recent
uasort($staffStats, function($a, $b) {
    return strtotime($b['last_action']) - strtotime($a['last_action']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Staff Activity Log – LYDO Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="admin.css"/>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-wrap">
<?php include 'topbar.php'; ?>
<main class="content">

<div class="page-header">
  <div><h2>Staff Activity Log</h2><p>View activities and updates made by staff members using the shared admin account.</p></div>
</div>

<!-- Staff Summary -->
<div class="card" style="margin-bottom:20px">
  <h3 style="margin:0 0 15px 0;font-size:16px">Staff Activity Summary</h3>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px">
    <?php foreach ($staffStats as $staffName => $stats): ?>
      <div style="background:#f8fafc;padding:15px;border-radius:8px;border-left:4px solid #007bff">
        <div style="font-weight:600;color:#2d3748;margin-bottom:5px"><?=htmlspecialchars($staffName)?></div>
        <div style="font-size:24px;font-weight:700;color:#007bff"><?=$stats['count']?></div>
        <div style="font-size:12px;color:#718096;margin-top:5px">
          Last: <?=date('M d, H:i', strtotime($stats['last_action']))?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Activity Log Table -->
<div class="card">
  <h3 style="margin:0 0 15px 0;font-size:16px">Recent Activities</h3>
  <div class="table-wrap">
    <table class="tbl" style="font-size:13px">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>Staff Name</th>
          <th>Action</th>
          <th>Entity Type</th>
          <th>Entity ID</th>
          <th>Details</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($activities)): ?>
          <tr><td colspan="7" style="text-align:center;padding:20px;color:#a0aec0">No activities yet</td></tr>
        <?php else: foreach ($activities as $i => $activity): ?>
          <tr style="<?=($i % 2 === 0 ? 'background:#fafbfc' : '')?>">
            <td><span style="font-size:11px;color:#718096"><?=date('M d, H:i:s', strtotime($activity['created_at']))?></span></td>
            <td><strong><?=htmlspecialchars($activity['staff_name'])?></strong></td>
            <td>
              <span style="background:#e7f3ff;color:#004085;padding:4px 8px;border-radius:4px;font-size:11px">
                <?=htmlspecialchars($activity['action'])?>
              </span>
            </td>
            <td><?=htmlspecialchars($activity['entity_type'] ?? '—')?></td>
            <td><?=$activity['entity_id'] ?? '—'?></td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?=htmlspecialchars($activity['details'] ?? '')?>">
              <?=htmlspecialchars(substr($activity['details'] ?? '', 0, 50))?>
            </td>
            <td><span style="font-size:11px;color:#a0aec0"><?=htmlspecialchars($activity['ip_address'] ?? '—')?></span></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

</main>
</div>

</body>
</html>
