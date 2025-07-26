<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// === Receive and validate input ===
$json = file_get_contents("php://input");
$data = json_decode($json, true);

// Check if JSON decoding was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data received.']);
    exit;
}

// Validate required fields
$requiredFields = ['name', 'parentsName', 'address', 'phone', 'standard', 'division', 'bloodGroup', 'admissionNo', 'dob', 'image'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

// Extract data
$name = htmlspecialchars($data['name']);
$parentsName = htmlspecialchars($data['parentsName']);
$address = htmlspecialchars($data['address']);
$phone = htmlspecialchars($data['phone']);
$standard = htmlspecialchars($data['standard']);
$division = htmlspecialchars($data['division']);
$bloodGroup = htmlspecialchars($data['bloodGroup']);
$admissionNo = htmlspecialchars($data['admissionNo']);
$dob = htmlspecialchars($data['dob']);
$image64 = $data['image'];

// Validate image data
if (strpos($image64, 'data:image') !== 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid image format. Please provide a valid image.']);
    exit;
}

// Get background image - using relative path instead of base64
$bgImagePath = 'assets/img/template2.png';
if (!file_exists(__DIR__ . '/' . $bgImagePath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Background template image not found.']);
    exit;
}

$schoolLogoPath = 'assets/img/school-logo1.png';

// === HTML template ===
// ... [keep all your initial code until the HTML template section] ...

// === HTML template with absolute positioning ===
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <style>
        @page {
            size: 55mm 85mm;
            margin: 0;
            padding: 0;
        }
        body {
            margin: 0;
            padding: 0;
            width: 55mm;
            height: 85mm;
            position: relative;
            font-family: Arial, sans-serif;
        }
        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        .header-container{
            width:100%;
          position:absolute;
          top:0px;
          left:0px;
          z-index: 2;
        }
        .school-logo {
            width: 13mm;
            height: auto;
            display: block;
            margin: 2mm auto 2mm 2mm;
        }
        .school-address {
            text-align: center;
            font-size: 7pt;
            margin: 0;
            padding: 0;
            font-weight:bold;
            color:white;
            position:absolute;
            top:0.5rem;
            left:5rem;
            width:10rem;
        }

        /* Profile image container */
        .profile-container {
            position: absolute;
            top: 12mm;  /* Adjusted to bring content up */
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
            text-align: center;
        }
        .profile-img {
            width: 20mm;
            height: 20mm;
            border-radius: 50%;
            border: 1mm solid #ea2867;
            object-fit: cover;
        }
        /* Name section */
        .name {
            position: absolute;
            top: 35mm;  /* Positioned below profile image */
            left: 0;
            width: 100%;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
            z-index: 2;
            font-family: "Montserrat", Arial, sans-serif;
        }
        /* Standard/Division */
        .details {
            position: absolute;
            top: 39mm;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            z-index: 2;
            font-family: "Montserrat", Arial, sans-serif;
        }
        /* Information table */
        .info-table {
            position: absolute;
            top: 45mm;  /* Positioned below details */
            left: 5mm;
            width: 45mm;
            font-size: 6pt;
            z-index: 2;
            font-family: "Montserrat", Arial, sans-serif;
        }
        .info-table td {
            padding: 0.3mm 0;
            vertical-align: top;
            font-weight: bold;
        }
        .info-table td:first-child {
            font-weight: 300;
            width: 38%;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <img class="background" src="' . $bgImagePath . '" />
    
    <div class="header-container">
        <div>
            <img class="school-logo" src="' . $schoolLogoPath . '" />
        </div>
        <div>
            <p class="school-address">
                <span style="font-size: 10pt;">G M U P S CHERKALA <br/></span>
                    P O &nbsp;THEKKIL FERRY  <br/>
                    671542 KASARAGOD 
            </p>
        </div>
    </div>
    <div class="profile-container">
        <img class="profile-img" src="' . $image64 . '" />
    </div>
    
    <div class="name">' . $name . '</div>
    <div class="details">STD. ' . $standard . ' ' . strtoupper($division) . '</div>
    
    <table class="info-table">
        <tr><td>Parent</td><td>: ' . $parentsName . '</td></tr>
        <tr><td>Address</td><td>: ' . $address . '</td></tr>
        <tr><td>Phone</td><td>: ' . $phone . '</td></tr>
        <tr><td>Admission No</td><td>: ' . $admissionNo . '</td></tr>
        <tr><td>Blood Group</td><td>: ' . $bloodGroup . '</td></tr>
        <tr><td>DOB</td><td>: ' . $dob . '</td></tr>
    </table>
</body>
</html>';

// echo json_encode(['success' => true, 'message' => $html]);
// === Generate PDF ===
$pdfPath = '';
try {
    $dompdf = new Dompdf([
        'chroot' => __DIR__, // Important for local file access
        'isRemoteEnabled' => true // Enable loading of remote images
    ]);
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper([0, 0, 55 * 2.83465, 85 * 2.83465], 'portrait');
    
    // Improve rendering quality
    $dompdf->set_option('isPhpEnabled', true);
    $dompdf->set_option('isHtml5ParserEnabled', true);
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->set_option('defaultFont', 'Arial');
    
    $dompdf->render();
    $pdfData = $dompdf->output();
    
    // Save PDF temporarily
    $uploadDir = __DIR__ . "/uploads";
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    $pdfPath = "$uploadDir/idcard_" . time() . ".pdf";
    if (!file_put_contents($pdfPath, $pdfData)) {
        throw new Exception('Failed to save PDF file');
    }

    // === Send email ===
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'idcardpro9@gmail.com';
        $mail->Password = 'rptoxgufsmmqpqlh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('no-reply@example.com', 'ID Card Generator');
        $mail->addAddress('makeomaaz13@gmail.com');//
        $ccEmails = ['rhmnramees730@gmail.com'];
        foreach ($ccEmails as $cc) {
            $mail->addCC($cc);
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Student ID Card for $name";
        $mail->Body = "
            <h2>Student ID Card Generated</h2>
            <p><strong>Student:</strong> $name</p>
            <p><strong>Standard:</strong> $standard $division</p>
            <p><strong>Admission No:</strong> $admissionNo</p>
            <p>Please find the ID card attached.</p>
        ";
        $mail->AltBody = "Student: $name\nStandard: $standard $division\nAdmission No: $admissionNo\n\nPlease find the ID card attached.";
        
        // Attachment
        $mail->addAttachment($pdfPath, 'IDCard.pdf');
        
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'ID card generated and sent successfully!']);
    } catch (Exception $e) {
        throw new Exception("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    // Clean up - delete the PDF file if it exists
    if ($pdfPath && file_exists($pdfPath)) {
        @unlink($pdfPath);
    }
}
