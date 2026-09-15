
<?php
/**
 * Event Participation Certificate
 * Includes: Time In, Time Out, Verification QR Code
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

// Load event
$evStmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
$evStmt->execute([$cert['event_id']]);
$event = $evStmt->fetch();

if (!$event) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;color:#c62828"><h2>Event Not Found</h2></div>');
}

// Load user
$uStmt = $pdo->prepare('SELECT * FROM youth_users WHERE id = ? LIMIT 1');
$uStmt->execute([$userId]);
$user = $uStmt->fetch();

// Load check-in record (time in / time out)
$ciStmt = $pdo->prepare('SELECT checked_in_at, checked_out_at FROM event_checkins WHERE event_id = ? AND user_id = ? LIMIT 1');
$ciStmt->execute([$cert['event_id'], $userId]);
$checkin = $ciStmt->fetch();

$fullName   = strtoupper(trim($user['first_name'] . ' ' . $user['last_name']));
$issueDate  = date('F j, Y', strtotime($cert['generated_at']));
$eventDate  = date('F j, Y', strtotime($event['event_date']));
$certNo     = $cert['cert_number'];
$timeIn     = $checkin && $checkin['checked_in_at']  ? date('g:i A', strtotime($checkin['checked_in_at']))  : '—';
$timeOut    = $checkin && $checkin['checked_out_at'] ? date('g:i A', strtotime($checkin['checked_out_at'])) : '—';
$timeInFull = $checkin && $checkin['checked_in_at']  ? date('F j, Y g:i A', strtotime($checkin['checked_in_at']))  : '—';
$timeOutFull= $checkin && $checkin['checked_out_at'] ? date('F j, Y g:i A', strtotime($checkin['checked_out_at'])) : '—';

// Generate a verification hash (HMAC so it can't be faked)
$verifySecret = 'LYDO_VERIFY_2026_' . DB_NAME;
$verifyHash   = substr(hash_hmac('sha256', $certNo . $userId . $cert['event_id'], $verifySecret), 0, 16);
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
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Playfair Display',serif;background:#f5f1e8;display:flex;flex-direction:column;align-items:center;min-height:100vh;padding:20px}
.print-bar{background:#0d3b6e;color:#fff;padding:12px 24px;border-radius:8px;margin-bottom:20px;display:flex;align-items:center;gap:14px;width:100%;max-width:1100px;flex-wrap:wrap}
.print-bar span{flex:1;font-size:.9rem;font-weight:500}
.btn-p{padding:10px 22px;background:#fff;color:#0d3b6e;border:none;border-radius:6px;font-family:'Inter',sans-serif;font-size:.88rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:7px;transition:.2s;text-decoration:none}
.btn-p:hover{background:#e8ecf0}
.btn-back{padding:10px 22px;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);border-radius:6px;font-family:'Inter',sans-serif;font-size:.88rem;font-weight:600;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:7px}
.cert-wrap{width:1100px;height:850px;max-width:100%;background:#fef8f3;position:relative;overflow:hidden;box-shadow:0 10px 50px rgba(0,0,0,.15)}
.cert-border{position:absolute;inset:30px;border:2px solid #0d3b6e;pointer-events:none;z-index:2}
.cert-border::before{content:'';position:absolute;inset:-4px;border:1px dashed #c8a84b;pointer-events:none;opacity:.6}
.cert-bg{position:absolute;inset:0;background:radial-gradient(ellipse at 10% 10%,rgba(13,59,110,.02) 0%,transparent 40%),radial-gradient(ellipse at 90% 90%,rgba(200,168,75,.02) 0%,transparent 40%)}
.cert-content{position:relative;z-index:3;padding:48px 64px;text-align:center;height:100%;display:flex;flex-direction:column;justify-content:space-between;font-family:'Inter',sans-serif}
.cert-header{display:flex;align-items:center;justify-content:center;gap:20px;margin-bottom:20px}
.cert-seal{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.8rem;box-shadow:0 6px 20px rgba(13,59,110,.25);flex-shrink:0;overflow:hidden}
.cert-org-main{font-size:1.1rem;font-weight:900;color:#0d3b6e;letter-spacing:.05em;font-family:'Playfair Display',serif}
.cert-org-sub{font-size:.76rem;color:#0d3b6e;font-weight:600;margin-top:3px;letter-spacing:.02em}
.cert-org-address{font-size:.65rem;color:#666;margin-top:2px}
.divider{height:3px;width:180px;background:linear-gradient(90deg,transparent,#c8a84b,transparent);margin:16px auto;border-radius:2px}
.cert-type{font-size:.85rem;font-weight:700;letter-spacing:.3em;text-transform:uppercase;color:#c8a84b;margin-bottom:8px;font-family:'Inter',sans-serif}
.cert-title{font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:900;color:#0d3b6e;line-height:1.3;margin-bottom:12px}
.cert-presented{font-size:.8rem;color:#666;margin:12px 0 6px;font-style:italic;font-family:'Inter',sans-serif}
.cert-name{font-family:'Playfair Display',serif;font-size:2rem;font-weight:800;color:#0d3b6e;border-bottom:3px solid #c8a84b;display:inline-block;padding-bottom:8px;margin:8px 0;min-width:380px;letter-spacing:.03em}
.cert-barangay{font-size:.78rem;color:#666;margin-top:4px;font-family:'Inter',sans-serif}
.cert-body{font-size:.88rem;color:#333;line-height:1.8;margin:14px 0;max-width:720px;margin-left:auto;margin-right:auto;font-family:'Inter',sans-serif}
.cert-event{font-weight:800;color:#0d3b6e;font-size:.95rem}

/* Footer */
.cert-footer{display:grid;grid-template-columns:1fr auto 1fr;gap:20px;margin-top:16px;align-items:flex-end}
.cert-sig{text-align:center;font-family:'Inter',sans-serif}
.cert-sig-line{height:2px;background:#0d3b6e;margin-bottom:6px;width:140px;margin-left:auto;margin-right:auto}
.cert-sig-name{font-size:.75rem;font-weight:700;color:#0d3b6e}
.cert-sig-title{font-size:.65rem;color:#666;line-height:1.4}
.cert-info-box{background:#fef8f3;border:2px solid #0d3b6e;border-radius:6px;padding:12px 16px;text-align:center;font-family:'Inter',sans-serif}
.cert-info-label{font-size:.68rem;color:#0d3b6e;text-transform:uppercase;letter-spacing:.08em;font-weight:800}
.cert-info-val{font-size:.82rem;font-weight:700;color:#0d3b6e;margin-top:4px;font-family:'Courier New',monospace;letter-spacing:.05em}

/* Verification QR */
.verify-box{display:flex;flex-direction:column;align-items:center;gap:8px}
.verify-box #certQR canvas,
.verify-box #certQR img{border-radius:4px;border:3px solid #0d3b6e;padding:6px;background:#fff}
.verify-label{font-size:.65rem;color:#0d3b6e;text-align:center;max-width:110px;line-height:1.5;font-weight:700;font-family:'Inter',sans-serif;text-transform:uppercase;letter-spacing:.05em}

.corner{position:absolute;width:28px;height:28px;z-index:4}
.corner-tl{top:28px;left:28px;border-top:3px solid #0d3b6e;border-left:3px solid #0d3b6e}
.corner-tr{top:28px;right:28px;border-top:3px solid #0d3b6e;border-right:3px solid #0d3b6e}
.corner-bl{bottom:28px;left:28px;border-bottom:3px solid #0d3b6e;border-left:3px solid #0d3b6e}
.corner-br{bottom:28px;right:28px;border-bottom:3px solid #0d3b6e;border-right:3px solid #0d3b6e}

@media(max-width:700px){
  .cert-content{padding:36px 24px}
  .cert-title{font-size:1.8rem}
  .cert-name{font-size:1.6rem;min-width:unset;max-width:100%}
  .cert-body{font-size:.82rem}
  .cert-footer{grid-template-columns:1fr;gap:12px}
  .cert-header{flex-direction:column;gap:12px}
  .verify-box{gap:8px}
  .verify-box #certQR canvas,
  .verify-box #certQR img{width:130px!important;height:130px!important;border-width:2px}
  .cert-sig-line{width:100px}
  .cert-name{min-width:auto}
  .divider{width:120px}
}
@media print{
  html,body{width:279mm;height:216mm;margin:0;padding:0;background:#fff}
  body{padding:0;display:block}
  .print-bar{display:none!important}
  .cert-wrap{box-shadow:none;width:279mm;height:216mm;max-width:none;page-break-after:avoid;page-break-inside:avoid;background:#fef8f3}
  .cert-content{page-break-inside:avoid}
  @page{size:279mm 216mm landscape;margin:0}
}
</style>
</head>
<body>

<div class="print-bar">
  <a href="/shared/youth/events.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back</a>
  <span>Certificate — <?= htmlspecialchars($event['title']) ?></span>
  <button class="btn-p" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
  <button class="btn-p" onclick="window.print()"><i class="fas fa-download"></i> Save as PDF</button>
</div>

<!-- Certificate -->
<div class="cert-wrap" id="certificate">
  <div class="cert-bg"></div>
  <div class="cert-border"></div>
  <div class="corner corner-tl"></div>
  <div class="corner corner-tr"></div>
  <div class="corner corner-bl"></div>
  <div class="corner corner-br"></div>

  <div class="cert-content">

    <!-- Header -->
    <div class="cert-header">
      <div class="cert-seal" style="background:linear-gradient(135deg,#0d3b6e,#1565c0)">
        <img src="/lydo logo.png" alt="LYDO" style="width:80%;height:80%;object-fit:contain"/>
      </div>
      <div style="flex:1">
        <div class="cert-org-main">Local Youth Development Office</div>
        <div class="cert-org-sub">Municipal Government of Sta. Cruz, Laguna</div>
        <div class="cert-org-address">Sta. Cruz, Laguna 4009 · Philippines</div>
      </div>
      <div class="cert-seal" style="background:linear-gradient(135deg,#2e7d32,#43a047)"><i class="fas fa-award"></i></div>
    </div>

    <div class="divider"></div>

    <div class="cert-type">Certificate of Participation</div>
    <div class="cert-title">This is to Certify That</div>

    <div class="cert-presented">This certificate is proudly presented to</div>
    <div class="cert-name"><?= htmlspecialchars($fullName) ?></div>
    <?php if ($user['barangay']): ?>
    <div class="cert-barangay">of <?= htmlspecialchars($user['barangay']) ?>, Sta. Cruz, Laguna</div>
    <?php endif; ?>

    <div class="cert-body">
      has successfully attended and participated in the
      <br><strong>
      <span class="cert-event"><?= htmlspecialchars($event['title']) ?></span></strong>
      <br>
      held on <strong><?= $eventDate ?></strong>
      <?php if ($event['location']): ?>
        at <strong><?= htmlspecialchars($event['location']) ?></strong>
      <?php endif; ?>
      <br><br>
      organized by the Local Youth Development Office of Sta. Cruz, Laguna.
    </div>

    <div class="divider"></div>

    <!-- Footer -->
    <div class="cert-footer">
      <div class="cert-sig">
        <div style="height:28px"></div>
        <div class="cert-sig-line"></div>
        <div class="cert-sig-name">LYDO Coordinator</div>
        <div class="cert-sig-title">Youth Coordinator<br>Local Youth Development Office</div>
      </div>

      <!-- Center: cert info + verification QR -->
      <div style="display:flex;flex-direction:column;align-items:center;gap:12px">
        <div class="cert-info-box">
          <div class="cert-info-label">Certificate No.</div>
          <div class="cert-info-val"><?= htmlspecialchars($certNo) ?></div>
          <div style="margin-top:10px">
            <div class="cert-info-label">Date Issued</div>
            <div class="cert-info-val" style="font-family:'Inter',sans-serif;letter-spacing:0"><?= $issueDate ?></div>
          </div>
        </div>
        <!-- Verification QR -->
        <div class="verify-box">
          <div id="certQR"></div>
          <div class="verify-label">Scan to verify</div>
        </div>
      </div>

      <div class="cert-sig">
        <div style="height:28px"></div>
        <div class="cert-sig-line"></div>
        <div class="cert-sig-name">Municipal Mayor</div>
        <div class="cert-sig-title">Municipal Government<br>Sta. Cruz, Laguna</div>
      </div>
    </div>

  </div>
</div>

<script>
// Generate verification QR code
new QRCode(document.getElementById('certQR'), {
  text: <?= json_encode($verifyUrl) ?>,
  width: 140,
  height: 140,
  colorDark: '#0d3b6e',
  colorLight: '#ffffff',
  correctLevel: QRCode.CorrectLevel.H
});
</script>
</body>
</html>
