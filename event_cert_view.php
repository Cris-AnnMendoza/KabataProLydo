<?php
/**
 * Event Participation Certificate
 * Landscape A4 - Professional Design with Integrated QR
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
*{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%}
body{font-family:'Inter',sans-serif;background:#f0f0f0;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.toolbar{display:flex;gap:12px;margin-bottom:16px;width:100%;max-width:1000px;align-items:center}
.btn{padding:12px 24px;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;transition:.2s}
.btn-back{background:#0d3b6e;color:#fff}
.btn-back:hover{background:#1565c0}
.btn-print{background:#2e7d32;color:#fff;margin-left:auto}
.btn-print:hover{background:#388e3c}
.toolbar-title{flex:1;color:#0d3b6e;font-weight:700;font-size:.95rem}
.certificate{width:100%;max-width:1000px;aspect-ratio:297/210;background:#fff;position:relative;box-shadow:0 8px 32px rgba(0,0,0,.12);display:grid;grid-template-columns:1fr 120px;overflow:hidden}
.cert-content{padding:35px 40px;display:flex;flex-direction:column;justify-content:space-between;position:relative;z-index:2}
.cert-frame{position:absolute;inset:20px;border:3px solid #0d3b6e;pointer-events:none;z-index:1}
.cert-frame::before{content:'';position:absolute;inset:6px;border:1px solid #c8a84b;pointer-events:none}
.cert-corner{position:absolute;width:28px;height:28px;border:2px solid #0d3b6e;z-index:3}
.cert-corner-tl{top:16px;left:16px;border-right:none;border-bottom:none}
.cert-corner-tr{top:16px;right:16px;border-left:none;border-bottom:none}
.cert-corner-bl{bottom:16px;left:16px;border-right:none;border-top:none}
.cert-corner-br{bottom:16px;right:16px;border-left:none;border-top:none}
.header{display:flex;align-items:center;gap:16px;margin-bottom:10px}
.logo{width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,#0d3b6e,#1565c0);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0}
.logo img{width:85%;height:85%;object-fit:contain}
.org-info{flex:1}
.org-name{font-size:.95rem;font-weight:900;color:#0d3b6e;font-family:'Playfair Display',serif;line-height:1.1}
.org-sub{font-size:.7rem;color:#0d3b6e;font-weight:700;margin-top:1px}
.org-addr{font-size:.6rem;color:#666;margin-top:1px}
.seal-right{width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,#2e7d32,#43a047);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem;flex-shrink:0}
.divider{height:1px;background:linear-gradient(90deg,transparent,#0d3b6e 20%,#c8a84b 50%,#0d3b6e 80%,transparent);margin:10px 0}
.cert-type{font-size:.75rem;font-weight:900;letter-spacing:.25em;text-transform:uppercase;color:#c8a84b;text-align:center;margin-bottom:6px}
.cert-title{font-family:'Playfair Display',serif;font-size:2rem;font-weight:900;color:#0d3b6e;text-align:center;margin-bottom:6px}
.subtitle{font-size:.8rem;color:#666;text-align:center;font-style:italic;margin-bottom:8px}
.recipient-name{font-family:'Playfair Display',serif;font-size:1.75rem;font-weight:900;color:#0d3b6e;text-align:center;border-bottom:3px solid #c8a84b;padding-bottom:6px;display:inline-block;width:100%;letter-spacing:.02em}
.location{font-size:.75rem;color:#555;text-align:center;margin-top:4px}
.content-text{font-size:.82rem;color:#333;line-height:1.65;text-align:center;margin:12px 0}
.event-highlight{font-weight:900;color:#0d3b6e;font-size:.9rem;text-transform:uppercase}
.footer-sigs{display:grid;grid-template-columns:1fr 1fr;gap:40px;margin-top:12px}
.sig-block{text-align:center}
.sig-line{height:2px;background:#0d3b6e;margin-bottom:3px;width:120px;margin-left:auto;margin-right:auto}
.sig-name{font-size:.68rem;font-weight:900;color:#0d3b6e;letter-spacing:.02em;text-transform:uppercase;margin-top:1px}
.sig-title{font-size:.6rem;color:#555;line-height:1.3}
.qr-section{background:linear-gradient(135deg,#f8f6f3 0%,#faf8f5 100%);border-left:2px solid #c8a84b;padding:16px 12px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;position:relative;z-index:2}
.qr-label{font-size:.58rem;font-weight:900;color:#0d3b6e;text-transform:uppercase;letter-spacing:.08em;text-align:center;line-height:1.3}
.qr-box{width:100px;height:100px;display:flex;align-items:center;justify-content:center}
#certQR{display:block !important;width:100px !important;height:100px !important;margin:0 !important;padding:0 !important}
#certQR canvas{width:100px !important;height:100px !important;display:block !important;border:1px solid #0d3b6e;background:#fff}
#certQR img{display:none !important}
.cert-num{font-size:.62rem;color:#0d3b6e;text-align:center;border-top:1px solid #c8a84b;padding-top:8px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.cert-num-value{font-family:'Courier New',monospace;font-size:.65rem;margin-top:2px;word-break:break-all}
.cert-date{font-size:.55rem;color:#666;text-align:center;margin-top:4px}

@media print{
  body{background:#fff;padding:0;margin:0}
  .toolbar{display:none}
  .certificate{box-shadow:none;width:297mm;height:210mm;max-width:none;margin:0;page-break-after:avoid}
  @page{size:A4 landscape;margin:0}
}
@media(max-width:768px){
  .certificate{max-width:100%;aspect-ratio:auto}
  .cert-content{padding:20px 25px}
  .header{gap:10px}
  .logo{width:40px;height:40px}
  .seal-right{width:40px;height:40px;font-size:1rem}
  .org-name{font-size:.85rem}
  .cert-title{font-size:1.5rem}
  .recipient-name{font-size:1.3rem}
  .qr-box{width:80px;height:80px}
  #certQR{width:80px !important;height:80px !important}
  #certQR canvas{width:80px !important;height:80px !important}
}
</style>
</head>
<body>

<div class="toolbar">
  <a href="/shared/youth/events.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  <span class="toolbar-title">Certificate of Participation – <?= htmlspecialchars($event['title']) ?></span>
  <button class="btn btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
</div>

<div class="certificate">
  <div class="cert-frame"></div>
  <div class="cert-corner cert-corner-tl"></div>
  <div class="cert-corner cert-corner-tr"></div>
  <div class="cert-corner cert-corner-bl"></div>
  <div class="cert-corner cert-corner-br"></div>

  <!-- Main Certificate Content -->
  <div class="cert-content">
    
    <!-- Header -->
    <div>
      <div class="header">
        <div class="logo"><img src="/lydo logo.png" alt="LYDO"/></div>
        <div class="org-info">
          <div class="org-name">Local Youth Development Office</div>
          <div class="org-sub">Municipal Government of Sta. Cruz, Laguna</div>
          <div class="org-addr">Sta. Cruz, Laguna 4009 · Philippines</div>
        </div>
        <div class="seal-right"><i class="fas fa-award"></i></div>
      </div>

      <div class="divider"></div>

      <!-- Certificate Text -->
      <div class="cert-type">Certificate of Participation</div>
      <div class="cert-title">This is to Certify That</div>
      <div class="subtitle">This certificate is proudly presented to</div>

      <div style="text-align:center;margin:8px 0">
        <div class="recipient-name"><?= htmlspecialchars($fullName) ?></div>
        <?php if ($user['barangay']): ?>
        <div class="location">of <?= htmlspecialchars($user['barangay']) ?>, Sta. Cruz, Laguna</div>
        <?php endif; ?>
      </div>

      <div class="content-text">
        has successfully attended and participated in the<br/>
        <span class="event-highlight"><?= htmlspecialchars($event['title']) ?></span><br/>
        held on <strong><?= $eventDate ?></strong>
        <?php if ($event['location']): ?>
          at <strong><?= htmlspecialchars($event['location']) ?></strong>
        <?php endif; ?><br/><br/>
        organized by the Local Youth Development Office of Sta. Cruz, Laguna.
      </div>
    </div>

    <!-- Footer Signatures -->
    <div class="footer-sigs">
      <div class="sig-block">
        <div style="height:16px"></div>
        <div class="sig-line"></div>
        <div class="sig-name">LYDO Coordinator</div>
        <div class="sig-title">Youth Coordinator<br/>Local Youth Development Office</div>
      </div>
      <div class="sig-block">
        <div style="height:16px"></div>
        <div class="sig-line"></div>
        <div class="sig-name">Municipal Mayor</div>
        <div class="sig-title">Municipal Government<br/>Sta. Cruz, Laguna</div>
      </div>
    </div>

  </div>

  <!-- QR Code Section (Integrated) -->
  <div class="qr-section">
    <div class="qr-label">Verify<br/>Certificate</div>
    <div class="qr-box">
      <div id="certQR"></div>
    </div>
    <div class="cert-num">
      <div><?= htmlspecialchars($certNo) ?></div>
      <div class="cert-num-value"><?= htmlspecialchars(substr($certNo, 0, 8)) ?></div>
      <div class="cert-date"><?= date('M j, Y', strtotime($issueDate)) ?></div>
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
      width: 100,
      height: 100,
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
