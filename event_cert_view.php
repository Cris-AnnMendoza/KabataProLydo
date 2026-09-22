<?php
/**
 * Event Participation Certificate
 * Landscape A4 - Professional Design
 */
require_once __DIR__ . '/shared/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$pdo    = db();
$userId = (int)$_SESSION['user_id'];

$certId  = (int)($_GET['cert_id'] ?? 0);
$eventId = (int)($_GET['event']   ?? 0);

if ($certId) {
    $certStmt = $pdo->prepare('SELECT * FROM event_certificates WHERE id = ? AND user_id = ? LIMIT 1');
    $certStmt->execute([$certId, $userId]);
    $cert = $certStmt->fetch();
} elseif ($eventId) {
    $certStmt = $pdo->prepare('SELECT * FROM event_certificates WHERE event_id = ? AND user_id = ? LIMIT 1');
    $certStmt->execute([$eventId, $userId]);
    $cert = $certStmt->fetch();
} else {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;color:#c62828"><h2>Invalid Request</h2></div>');
}

if (!$cert) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;color:#c62828">
        <h2>Certificate Not Found</h2>
        <p>You have not checked out of this event yet, or the certificate does not exist.</p>
        <a href="/shared/youth/events.php" style="color:#1565c0">← Back to Events</a>
    </div>');
}

$evStmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
$evStmt->execute([$cert['event_id']]);
$event = $evStmt->fetch();

if (!$event) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;color:#c62828"><h2>Event Not Found</h2></div>');
}

$uStmt = $pdo->prepare('SELECT * FROM youth_users WHERE id = ? LIMIT 1');
$uStmt->execute([$userId]);
$user = $uStmt->fetch();

$ciStmt = $pdo->prepare('SELECT checked_in_at, checked_out_at FROM event_checkins WHERE event_id = ? AND user_id = ? LIMIT 1');
$ciStmt->execute([$cert['event_id'], $userId]);
$checkin = $ciStmt->fetch();

$fullName   = strtoupper(trim($user['first_name'] . ' ' . $user['last_name']));
$issueDate  = date('F j, Y', strtotime($cert['generated_at']));
$eventDate  = date('F j, Y', strtotime($event['event_date']));
$certNo     = $cert['cert_number'];

$verifySecret = 'LYDO_VERIFY_2026_' . DB_NAME;
$verifyHash   = substr(hash_hmac('sha256', "{$certNo}{$userId}{$cert['event_id']}", $verifySecret), 0, 16);
$verifyUrl    = 'http://' . $_SERVER['HTTP_HOST'] . '/verify_cert.php?cert=' . urlencode($certNo) . '&h=' . $verifyHash;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Certificate – <?= htmlspecialchars($event['title']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%}
body{font-family:'Inter',sans-serif;background:#e5e5e5;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:10px}
.screen-bar{display:flex;gap:12px;margin-bottom:12px;width:100%;max-width:1300px}
.btn-back{padding:10px 20px;background:#0d3b6e;color:#fff;border:none;border-radius:6px;font-size:.9rem;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:8px}
.btn-back:hover{background:#1565c0}
.btn-print{padding:10px 20px;background:#2e7d32;color:#fff;border:none;border-radius:6px;font-size:.9rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px}
.btn-print:hover{background:#388e3c}
.cert-container{width:100%;max-width:1300px;aspect-ratio:297/210;background:#fff;position:relative;box-shadow:0 10px 40px rgba(0,0,0,.15);display:flex}
.cert-main{flex:1;padding:40px 50px;display:flex;flex-direction:column;justify-content:space-between;position:relative;z-index:2;text-align:center}
.cert-border-outer{position:absolute;inset:20px;border:3px solid #0d3b6e;pointer-events:none;z-index:1}
.cert-border-inner{position:absolute;inset:25px;border:1px solid #c8a84b;pointer-events:none;z-index:1}
.cert-header{display:flex;align-items:center;justify-content:center;gap:20px;margin-bottom:15px}
.cert-logo{width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#0d3b6e,#1565c0);display:flex;align-items:center;justify-content:center;color:#fff;overflow:hidden;flex-shrink:0}
.cert-logo img{width:90%;height:90%;object-fit:contain}
.cert-org{flex:1}
.cert-org-main{font-size:1.1rem;font-weight:900;color:#0d3b6e;font-family:'Playfair Display',serif;letter-spacing:.02em}
.cert-org-sub{font-size:.75rem;color:#0d3b6e;font-weight:700;margin-top:2px}
.cert-org-addr{font-size:.65rem;color:#555;margin-top:1px}
.cert-seal-right{width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#2e7d32,#43a047);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.6rem;flex-shrink:0}
.cert-divider{height:2px;width:250px;background:linear-gradient(90deg,transparent,#0d3b6e,#c8a84b,#0d3b6e,transparent);margin:12px auto;border-radius:1px}
.cert-type{font-size:.8rem;font-weight:900;letter-spacing:.3em;text-transform:uppercase;color:#c8a84b;margin-bottom:8px;font-family:'Inter',sans-serif}
.cert-title{font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:900;color:#0d3b6e;margin-bottom:8px;letter-spacing:.01em}
.cert-subtitle{font-size:.85rem;color:#666;margin-bottom:12px;font-style:italic}
.cert-name{font-family:'Playfair Display',serif;font-size:2rem;font-weight:900;color:#0d3b6e;border-bottom:3px solid #c8a84b;display:inline-block;padding-bottom:8px;margin:6px 0;letter-spacing:.03em;text-transform:uppercase}
.cert-location{font-size:.78rem;color:#555;margin-top:4px;margin-bottom:10px}
.cert-body{font-size:.85rem;color:#333;line-height:1.7;margin:8px 0}
.cert-event-name{font-weight:900;color:#0d3b6e;font-size:.95rem;text-transform:uppercase}
.cert-footer-sigs{display:flex;justify-content:space-between;margin-top:15px;gap:20px}
.cert-sig{flex:1;text-align:center}
.cert-sig-line{height:2px;background:#0d3b6e;margin-bottom:4px;width:140px;margin-left:auto;margin-right:auto}
.cert-sig-name{font-size:.7rem;font-weight:900;color:#0d3b6e;letter-spacing:.02em;text-transform:uppercase;margin-top:2px}
.cert-sig-title{font-size:.62rem;color:#555;line-height:1.4;font-weight:600}
.cert-qr-section{width:180px;padding:20px 15px;background:#f9f7f3;border-left:3px double #0d3b6e;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;position:relative;z-index:2}
.qr-label-top{font-size:.62rem;font-weight:900;color:#0d3b6e;text-transform:uppercase;letter-spacing:.08em;text-align:center;margin-bottom:10px}
.qr-container{display:flex;align-items:center;justify-content:center;width:140px;height:140px;margin-bottom:8px}
#certQR{display:block !important;width:140px !important;height:140px !important;margin:0 !important;padding:0 !important;overflow:hidden}
#certQR canvas{width:140px !important;height:140px !important;display:block !important;border:2px solid #0d3b6e;border-radius:4px;background:#fff}
#certQR img{display:none !important}
.qr-label-bottom{font-size:.6rem;color:#0d3b6e;text-align:center;font-weight:700;text-transform:uppercase;letter-spacing:.05em;line-height:1.4}
.cert-info-mini{font-size:.65rem;color:#0d3b6e;text-align:center;margin-top:8px;border-top:1px solid #c8a84b;padding-top:8px}
.cert-info-mini-label{font-weight:900;letter-spacing:.06em;text-transform:uppercase}
.cert-info-mini-value{font-family:'Courier New',monospace;font-weight:700;margin-top:2px;font-size:.7rem}

@media print{
  body{background:#fff;padding:0;margin:0}
  .screen-bar{display:none}
  .cert-container{box-shadow:none;width:297mm;height:210mm;max-width:none;margin:0;page-break-after:avoid}
  @page{size:A4 landscape;margin:0}
}
</style>
</head>
<body>

<div class="screen-bar">
  <a href="/shared/youth/events.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  <span style="flex:1;color:#0d3b6e;font-weight:600">Certificate of Participation – <?= htmlspecialchars($event['title']) ?></span>
  <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Certificate</button>
</div>

<div class="cert-container">
  <div class="cert-border-outer"></div>
  <div class="cert-border-inner"></div>

  <div class="cert-main">
    <!-- Header -->
    <div>
      <div class="cert-header">
        <div class="cert-logo">
          <img src="/lydo logo.png" alt="LYDO"/>
        </div>
        <div class="cert-org">
          <div class="cert-org-main">Local Youth Development Office</div>
          <div class="cert-org-sub">Municipal Government of Sta. Cruz, Laguna</div>
          <div class="cert-org-addr">Sta. Cruz, Laguna 4009 · Philippines</div>
        </div>
        <div class="cert-seal-right"><i class="fas fa-award"></i></div>
      </div>

      <div class="cert-divider"></div>

      <!-- Certificate Content -->
      <div class="cert-type">Certificate of Participation</div>
      <div class="cert-title">This is to Certify That</div>
      <div class="cert-subtitle">This certificate is proudly presented to</div>

      <div class="cert-name"><?= htmlspecialchars($fullName) ?></div>
      <?php if ($user['barangay']): ?>
      <div class="cert-location">of <?= htmlspecialchars($user['barangay']) ?>, Sta. Cruz, Laguna</div>
      <?php endif; ?>

      <div class="cert-body">
        has successfully attended and participated in the
        <span class="cert-event-name"><?= htmlspecialchars($event['title']) ?></span><br/>
        held on <strong><?= $eventDate ?></strong>
        <?php if ($event['location']): ?>
          at <strong><?= htmlspecialchars($event['location']) ?></strong>
        <?php endif; ?><br/><br/>
        organized by the Local Youth Development Office of Sta. Cruz, Laguna.
      </div>
    </div>

    <!-- Footer with Signatures -->
    <div class="cert-footer-sigs">
      <div class="cert-sig">
        <div style="height:20px"></div>
        <div class="cert-sig-line"></div>
        <div class="cert-sig-name">LYDO Coordinator</div>
        <div class="cert-sig-title">Youth Coordinator<br/>Local Youth Development Office</div>
      </div>

      <div class="cert-sig">
        <div style="height:20px"></div>
        <div class="cert-sig-line"></div>
        <div class="cert-sig-name">Municipal Mayor</div>
        <div class="cert-sig-title">Municipal Government<br/>Sta. Cruz, Laguna</div>
      </div>
    </div>
  </div>

  <!-- QR Code Section on Right -->
  <div class="cert-qr-section">
    <div class="qr-label-top">Verify<br/>Certificate</div>
    
    <div class="qr-container">
      <div id="certQR"></div>
    </div>

    <div class="qr-label-bottom">SCAN TO VERIFY AUTHENTICITY</div>

    <div class="cert-info-mini">
      <div class="cert-info-mini-label">Cert. No.</div>
      <div class="cert-info-mini-value"><?= htmlspecialchars($certNo) ?></div>
      <div style="margin-top:6px;padding-top:6px;border-top:1px solid #c8a84b">
        <div class="cert-info-mini-label" style="font-size:.6rem">Issued</div>
        <div class="cert-info-mini-value"><?= date('M j, Y', strtotime($issueDate)) ?></div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var qrContainer = document.getElementById('certQR');
  if (qrContainer) {
    qrContainer.innerHTML = '';
    new QRCode(qrContainer, {
      text: <?= json_encode($verifyUrl) ?>,
      width: 140,
      height: 140,
      colorDark: '#0d3b6e',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H,
      useSVG: false
    });
  }
});
</script>
</body>
</html>
