<?php
/**
 * Event Participation Certificate
 * Professional Landscape with QR Inside Certificate
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
.toolbar{display:flex;gap:12px;margin-bottom:16px;width:100%;max-width:1200px;align-items:center}
.btn{padding:12px 24px;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;transition:.2s}
.btn-back{background:#0d3b6e;color:#fff}
.btn-back:hover{background:#1565c0}
.btn-print{background:#2e7d32;color:#fff;margin-left:auto}
.btn-print:hover{background:#388e3c}
.toolbar-title{flex:1;color:#0d3b6e;font-weight:700;font-size:.95rem}
.certificate{width:100%;max-width:1200px;aspect-ratio:297/210;background:#fff;position:relative;box-shadow:0 8px 32px rgba(0,0,0,.12);display:flex;align-items:center;justify-content:center;overflow:hidden}
.cert-frame{position:absolute;inset:20px;border:3px solid #0d3b6e;pointer-events:none;z-index:1}
.cert-frame::before{content:'';position:absolute;inset:6px;border:1px solid #c8a84b;pointer-events:none}
.cert-corner{position:absolute;width:28px;height:28px;border:2px solid #0d3b6e;z-index:3}
.cert-corner-tl{top:16px;left:16px;border-right:none;border-bottom:none}
.cert-corner-tr{top:16px;right:16px;border-left:none;border-bottom:none}
.cert-corner-bl{bottom:16px;left:16px;border-right:none;border-top:none}
.cert-corner-br{bottom:16px;right:16px;border-left:none;border-top:none}
.cert-wrapper{width:calc(100% - 80px);height:calc(100% - 80px);position:relative;z-index:2;display:flex;flex-direction:column;justify-content:space-between;padding:0}
.cert-header{padding:24px 32px 10px;display:flex;align-items:center;gap:14px}
.logo{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#0d3b6e,#1565c0);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0}
.logo img{width:85%;height:85%;object-fit:contain}
.org-info{flex:1}
.org-name{font-size:1.2rem;font-weight:900;color:#0d3b6e;font-family:'Playfair Display',serif;line-height:1.1}
.org-sub{font-size:.95rem;color:#0d3b6e;font-weight:700;margin-top:2px}
.org-addr{font-size:.85rem;color:#666;margin-top:2px}
.seal-right{width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#2e7d32,#43a047);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.6rem;flex-shrink:0}
.divider{height:1px;background:linear-gradient(90deg,transparent,#0d3b6e 20%,#c8a84b 50%,#0d3b6e 80%,transparent);margin:8px 32px 0}
.cert-body{padding:0 32px;flex:1;display:flex;flex-direction:column;justify-content:center;text-align:center;position:relative}
.cert-type{font-size:1rem;font-weight:900;letter-spacing:.25em;text-transform:uppercase;color:#c8a84b;margin-bottom:8px}
.cert-title{font-family:'Playfair Display',serif;font-size:2.8rem;font-weight:900;color:#0d3b6e;margin-bottom:10px}
.subtitle{font-size:1.05rem;color:#666;font-style:italic;margin-bottom:12px}
.recipient-name{font-family:'Playfair Display',serif;font-size:2.5rem;font-weight:900;color:#0d3b6e;border-bottom:4px solid #c8a84b;padding-bottom:10px;display:block;width:100%;letter-spacing:.02em;margin:10px 0}
.location{font-size:1rem;color:#555;margin-top:8px}
.content-text{font-size:1.08rem;color:#333;line-height:1.8;margin:16px 0}
.event-highlight{font-weight:900;color:#0d3b6e;font-size:1.2rem;text-transform:uppercase}
.footer-container{display:grid;grid-template-columns:auto 1fr auto;align-items:flex-end;gap:20px;padding:20px 32px 16px;position:relative;width:100%}
.footer-sigs{display:flex;gap:140px;justify-content:center;grid-column:2;justify-self:center}
.sig-block{text-align:center;flex-direction:column;display:flex;align-items:center}
.sig-line{height:2px;background:#0d3b6e;margin-bottom:5px;width:150px;margin-left:auto;margin-right:auto}
.sig-name{font-size:.92rem;font-weight:900;color:#0d3b6e;letter-spacing:.02em;text-transform:uppercase;margin-top:4px}
.sig-title{font-size:.85rem;color:#555;line-height:1.5;font-weight:600}
.qr-area{width:160px;display:flex;flex-direction:column;align-items:center;gap:10px;grid-column:3;grid-row:1;justify-self:end}
.qr-box{width:145px;height:145px;display:flex;align-items:center;justify-content:center;border:1px solid #c8a84b;border-radius:4px;background:#fafafa}
#certQR{display:block !important;width:140px !important;height:140px !important;margin:0 !important;padding:0 !important}
#certQR canvas{width:140px !important;height:140px !important;display:block !important;border:none;background:#fff}
#certQR img{display:none !important}
.qr-text{font-size:.88rem;font-weight:700;color:#0d3b6e;text-align:center;text-transform:uppercase;letter-spacing:.05em}
.cert-num-small{font-size:.75rem;color:#666;font-family:'Courier New',monospace;text-align:center;margin-top:5px;word-break:break-all}

@media print{
  body{background:#fff;padding:0;margin:0}
  .toolbar{display:none}
  .certificate{box-shadow:none;width:297mm;height:210mm;max-width:none;margin:0;page-break-after:avoid;aspect-ratio:auto}
  @page{size:A4 landscape;margin:0}
}
@media(max-width:1024px){
  .certificate{max-width:100%;aspect-ratio:auto;min-height:600px}
  .cert-wrapper{padding:20px}
  .cert-header{padding:16px 20px 8px;gap:10px}
  .logo{width:40px;height:40px}
  .seal-right{width:40px;height:40px;font-size:1rem}
  .org-name{font-size:.9rem}
  .cert-title{font-size:1.6rem}
  .recipient-name{font-size:1.5rem}
  .footer-container{gap:60px}
  .footer-sigs{gap:60px}
  .sig-line{width:100px}
  .qr-box{width:110px;height:110px}
  #certQR{width:105px !important;height:105px !important}
  #certQR canvas{width:105px !important;height:105px !important}
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

  <div class="cert-wrapper">
    
    <!-- Header -->
    <div class="cert-header">
      <div class="logo"><img src="/lydo logo.png" alt="LYDO"/></div>
      <div class="org-info">
        <div class="org-name">Local Youth Development Office</div>
        <div class="org-sub">Municipal Government of Sta. Cruz, Laguna</div>
        <div class="org-addr">Sta. Cruz, Laguna 4009 · Philippines</div>
      </div>
      <div class="seal-right"><i class="fas fa-award"></i></div>
    </div>

    <div class="divider"></div>

    <!-- Certificate Body -->
    <div class="cert-body">
      <div class="cert-type">Certificate of Participation</div>
      <div class="cert-title">This is to Certify That</div>
      <div class="subtitle">This certificate is proudly presented to</div>

      <div class="recipient-name"><?= htmlspecialchars($fullName) ?></div>
      <?php if ($user['barangay']): ?>
      <div class="location">of <?= htmlspecialchars($user['barangay']) ?>, Sta. Cruz, Laguna</div>
      <?php endif; ?>

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

    <!-- Footer with Signatures and QR Code -->
    <div class="footer-container">
      <!-- Signatures in Center -->
      <div class="footer-sigs">
        <div class="sig-block">
          <div style="height:12px"></div>
          <div class="sig-line"></div>
          <div class="sig-name">LYDO Coordinator</div>
          <div class="sig-title">Youth Coordinator<br/>LYDO Office</div>
        </div>
        <div class="sig-block">
          <div style="height:12px"></div>
          <div class="sig-line"></div>
          <div class="sig-name">Municipal Mayor</div>
          <div class="sig-title">Municipal Government<br/>Sta. Cruz, Laguna</div>
        </div>
      </div>

      <!-- QR Code on Right -->
      <div class="qr-area">
        <div class="qr-box">
          <div id="certQR"></div>
        </div>
        <div class="qr-text">Scan<br/>Verify</div>
        <div class="cert-num-small"><?= htmlspecialchars(substr($certNo, -6)) ?></div>
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
