<?php
/**
 * Professional Certificate Template
 * Matches the clean, elegant style with:
 * - Centered, minimal design
 * - Logo at top
 * - Large serif title
 * - Recipient name with underline
 * - Achievement text
 * - Date and signature area with QR code
 */

// Configuration (customize as needed)
$config = [
    'title'           => 'CERTIFICATE OF COMPLETION',
    'subtitle'        => 'THIS CERTIFICATE IS AWARDED TO',
    'recipient'       => 'John Smith',
    'achievement'     => 'FOR SUCCESSFULLY COMPLETING THE COURSE OF "PROFESSIONAL DEVELOPMENT"',
    'date'            => date('m.d.Y'),
    'date_label'      => 'DATE',
    'certified_by'    => 'Director Name',
    'certified_label' => 'CERTIFIED BY',
    'logo_url'        => '/lydo logo.png',
    'organization'    => 'LOCAL YOUTH DEVELOPMENT OFFICE',
    'show_qr'         => true,
    'qr_text'         => 'https://example.com/verify',
];

// Merge with passed config
if (isset($certConfig)) {
    $config = array_merge($config, $certConfig);
}

extract($config);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= htmlspecialchars($title) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<?php if ($show_qr): ?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<?php endif; ?>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{
  font-family:'Lora',serif;
  background:#e8e6e1;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  min-height:100vh;
  padding:20px
}
.print-bar{
  background:#2c3e50;
  color:#fff;
  padding:14px 28px;
  border-radius:8px;
  margin-bottom:24px;
  display:flex;
  align-items:center;
  gap:16px;
  width:100%;
  max-width:1000px;
  flex-wrap:wrap;
  box-shadow:0 4px 12px rgba(0,0,0,0.15)
}
.print-bar span{flex:1;font-size:0.95rem;font-weight:500}
.btn-action{
  padding:10px 24px;
  background:#fff;
  color:#2c3e50;
  border:none;
  border-radius:6px;
  font-family:'Lora',serif;
  font-size:0.9rem;
  font-weight:600;
  cursor:pointer;
  display:flex;
  align-items:center;
  gap:8px;
  transition:all 0.2s;
  text-decoration:none
}
.btn-action:hover{background:#f0f0f0}
.btn-back{
  padding:10px 24px;
  background:rgba(255,255,255,0.2);
  color:#fff;
  border:1px solid rgba(255,255,255,0.3);
  border-radius:6px;
  font-family:'Lora',serif;
  font-size:0.9rem;
  cursor:pointer;
  text-decoration:none;
  display:flex;
  align-items:center;
  gap:8px
}
.btn-back:hover{background:rgba(255,255,255,0.3)}

/* Certificate */
.certificate{
  width:1000px;
  height:auto;
  min-height:770px;
  max-width:100%;
  background:#fafaf8;
  padding:60px 80px;
  text-align:center;
  border:3px solid #2c3e50;
  box-shadow:0 20px 60px rgba(0,0,0,0.2);
  position:relative;
  page-break-inside:avoid
}

.certificate::before,
.certificate::after{
  content:'';
  position:absolute;
  background:#2c3e50
}
.certificate::before{
  top:20px;
  left:20px;
  right:20px;
  height:1px
}
.certificate::after{
  bottom:20px;
  left:20px;
  right:20px;
  height:1px
}

.cert-decorative{
  position:absolute;
  width:30px;
  height:30px;
  border:2px solid #2c3e50
}
.cert-deco-tl{top:20px;left:20px;border-right:none;border-bottom:none}
.cert-deco-tr{top:20px;right:20px;border-left:none;border-bottom:none}
.cert-deco-bl{bottom:20px;left:20px;border-right:none;border-top:none}
.cert-deco-br{bottom:20px;right:20px;border-left:none;border-top:none}

.cert-header{margin-bottom:40px;position:relative;z-index:1}
.cert-logo{
  width:70px;
  height:70px;
  margin:0 auto 16px;
  display:flex;
  align-items:center;
  justify-content:center;
  border:3px solid #2c3e50;
  border-radius:50%;
  background:#fff
}
.cert-logo img{
  width:90%;
  height:90%;
  object-fit:contain
}

.cert-org{
  font-size:0.95rem;
  font-weight:600;
  letter-spacing:0.05em;
  color:#2c3e50;
  text-transform:uppercase;
  margin-bottom:8px
}

.cert-type{
  font-size:0.8rem;
  letter-spacing:0.15em;
  text-transform:uppercase;
  color:#8b8680;
  margin-bottom:32px;
  font-weight:600
}

.cert-title{
  font-family:'Playfair Display',serif;
  font-size:3.5rem;
  font-weight:900;
  color:#2c3e50;
  letter-spacing:-0.02em;
  margin-bottom:8px;
  line-height:1.1
}

.cert-subtitle{
  font-size:0.9rem;
  color:#333;
  margin-bottom:16px;
  font-weight:500
}

.cert-recipient{
  font-family:'Playfair Display',serif;
  font-size:2.5rem;
  font-weight:900;
  color:#2c3e50;
  margin:24px 0;
  padding-bottom:16px;
  border-bottom:3px solid #2c3e50;
  display:inline-block;
  min-width:400px;
  letter-spacing:0.02em
}

.cert-achievement{
  font-size:0.95rem;
  color:#333;
  line-height:1.8;
  margin:32px 0;
  max-width:700px;
  margin-left:auto;
  margin-right:auto;
  letter-spacing:0.01em
}

.cert-footer{
  display:grid;
  grid-template-columns:1fr auto 1fr;
  gap:40px;
  margin-top:48px;
  align-items:flex-end;
  justify-items:center;
  position:relative;
  z-index:1
}

.cert-sig{
  text-align:center;
  width:100%;
  max-width:200px
}

.cert-sig-line{
  height:2px;
  background:#2c3e50;
  margin-bottom:10px;
  margin-top:20px
}

.cert-sig-name{
  font-size:0.75rem;
  font-weight:700;
  color:#2c3e50;
  letter-spacing:0.05em;
  text-transform:uppercase;
  margin-top:6px
}

.cert-sig-title{
  font-size:0.7rem;
  color:#555;
  margin-top:4px;
  line-height:1.4
}

.cert-center{
  display:flex;
  flex-direction:column;
  align-items:center;
  gap:20px;
  justify-content:flex-end
}

.cert-date-box{
  background:#fafaf8;
  border:2px solid #2c3e50;
  border-radius:4px;
  padding:12px 20px;
  text-align:center
}

.cert-date-label{
  font-size:0.65rem;
  color:#2c3e50;
  text-transform:uppercase;
  letter-spacing:0.1em;
  font-weight:700;
  margin-bottom:4px
}

.cert-date-val{
  font-size:0.9rem;
  font-weight:700;
  color:#2c3e50;
  letter-spacing:0.05em
}

.qr-container{
  text-align:center
}

#certQR,
#certQR canvas,
#certQR img{
  max-width:120px;
  max-height:120px;
  border:2px solid #2c3e50;
  border-radius:4px;
  padding:6px;
  background:#fff;
  display:inline-block!important
}

@media(max-width:768px){
  .certificate{
    padding:40px 30px;
    min-height:auto;
    border-width:2px;
    width:100%
  }
  .cert-title{font-size:2.4rem}
  .cert-recipient{
    font-size:1.8rem;
    min-width:auto;
    max-width:100%
  }
  .cert-achievement{font-size:0.85rem}
  .cert-footer{
    grid-template-columns:1fr;
    gap:20px;
    margin-top:32px
  }
  .cert-sig-line{margin-top:12px}
  .cert-logo{width:60px;height:60px}
  .cert-org{font-size:0.85rem}
}

@media print{
  html,body{
    width:297mm;
    height:210mm;
    margin:0;
    padding:0;
    background:#fff
  }
  body{padding:0;display:block}
  .print-bar{display:none!important}
  .certificate{
    box-shadow:none;
    width:297mm;
    height:210mm;
    max-width:none;
    page-break-after:avoid;
    page-break-inside:avoid;
    border-color:#2c3e50;
    margin:0
  }
  @page{size:297mm 210mm landscape;margin:0}
}
</style>
</head>
<body>

<div class="print-bar">
  <span>Certificate — <?= htmlspecialchars($title) ?></span>
  <button class="btn-action" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
  <button class="btn-action" onclick="downloadPDF()"><i class="fas fa-download"></i> Download PDF</button>
</div>

<div class="certificate" id="certificate">
  <div class="cert-deco-tl cert-decorative"></div>
  <div class="cert-deco-tr cert-decorative"></div>
  <div class="cert-deco-bl cert-decorative"></div>
  <div class="cert-deco-br cert-decorative"></div>

  <div class="cert-header">
    <div class="cert-logo">
      <img src="<?= htmlspecialchars($logo_url) ?>" alt="Logo"/>
    </div>
    <div class="cert-org"><?= htmlspecialchars($organization) ?></div>
  </div>

  <div class="cert-type">Certificate</div>
  <div class="cert-title"><?= htmlspecialchars($title) ?></div>
  
  <div class="cert-subtitle"><?= htmlspecialchars($subtitle) ?></div>
  <div class="cert-recipient"><?= htmlspecialchars($recipient) ?></div>

  <div class="cert-achievement">
    <?= htmlspecialchars($achievement) ?>
  </div>

  <div class="cert-footer">
    <div class="cert-sig">
      <div class="cert-sig-line"></div>
      <div class="cert-sig-name">Authorized Signatory</div>
      <div class="cert-sig-title">Organization Lead</div>
    </div>

    <div class="cert-center">
      <div class="cert-date-box">
        <div class="cert-date-label"><?= htmlspecialchars($date_label) ?></div>
        <div class="cert-date-val"><?= htmlspecialchars($date) ?></div>
      </div>
      <?php if ($show_qr): ?>
      <div class="qr-container">
        <div id="certQR"></div>
      </div>
      <?php endif; ?>
    </div>

    <div class="cert-sig">
      <div class="cert-sig-line"></div>
      <div class="cert-sig-name"><?= htmlspecialchars($certified_by) ?></div>
      <div class="cert-sig-title"><?= htmlspecialchars($certified_label) ?></div>
    </div>
  </div>
</div>

<?php if ($show_qr): ?>
<script>
new QRCode(document.getElementById('certQR'), {
  text: <?= json_encode($qr_text) ?>,
  width: 120,
  height: 120,
  colorDark: '#2c3e50',
  colorLight: '#ffffff',
  correctLevel: QRCode.CorrectLevel.H
});
</script>
<?php endif; ?>

<script>
function downloadPDF(){
  const element = document.getElementById('certificate');
  const opt = {
    margin:0,
    filename:'certificate.pdf',
    image:{type:'jpeg',quality:0.98},
    html2canvas:{scale:2},
    jsPDF:{format:'a4',orientation:'landscape'}
  };
  html2pdf().set(opt).save();
}
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

</body>
</html>
