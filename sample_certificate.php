<?php
/**
 * Sample Certificate Usage
 * Shows how to customize and display the certificate template
 */

// Define certificate configuration
$certConfig = [
    'title'           => 'CERTIFICATE OF COMPLETION',
    'subtitle'        => 'THIS CERTIFICATE IS AWARDED TO',
    'recipient'       => 'Olivia Wilson',
    'achievement'     => 'FOR SUCCESSFULLY COMPLETING THE COURSE OF "PROFESSIONAL DEVELOPMENT"',
    'date'            => date('m.d.Y'),
    'date_label'      => 'DATE',
    'certified_by'    => 'Richard Wilson',
    'certified_label' => 'CERTIFIED BY',
    'logo_url'        => '/lydo logo.png',
    'organization'    => 'LOCAL YOUTH DEVELOPMENT OFFICE',
    'show_qr'         => true,
    'qr_text'         => 'https://example.com/verify?cert=12345',
];

// Include the template (which uses $certConfig)
include 'certificate_template.php';
?>
