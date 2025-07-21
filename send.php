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

// Get background image
$bgImagePath = __DIR__ . '/assets/img/template5.png';
if (!file_exists($bgImagePath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Background template image not found.']);
    exit;
}
$bgImageBase64 = base64_encode(file_get_contents($bgImagePath));
$bgImageSrc = 'data:image/jpeg;base64,' . $bgImageBase64;

// === HTML template ===
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        @page {
            size: 55mm 85mm;
            margin: 0;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: 55mm;
            height: 85mm;
        }
        .card {
            width: 100%;
            height: 100%;
            background: url(' . $bgImageSrc . ') no-repeat center center;
            background-size: 100% 100%; /* Stretch to fill */
            font-family: Arial, sans-serif;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .profile {
            text-align: center;
            margin-bottom: 2mm;
        }
        .profile img {
            width: 22mm;
            height: 22mm;
            border-radius: 50%;
            border: 1mm solid #ea2867;
            margin-top:3rem;
        }
        .name {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 1mm;
        }
        .details {
            text-align: center;
            color: #000000ff;
            font-size: 9px;
            margin-bottom: 1mm;
        }
        .info {
            font-size: 8px;
            margin: 0 3mm;
            color: #000000ff;
        }
        .name { font-size: 9px; }
        .details { font-size: 8px; }
        .info { font-size: 7px; }
        .info table {
            width: 100%;
        }
        .info td {
            padding: 0.5mm 0;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="profile"><img src="' . $image64 . '" /></div>
        <div class="name">' . $name . '</div>
        <div class="details">STD. ' . $standard . ' ' . strtoupper($division) . '</div>
        <div class="info">
            <table>
                <tr><td><strong>Parent</strong></td><td style="color: #000000ff;">: ' . $parentsName . '</td></tr>
                <tr><td><strong>Address</strong></td><td style="color: #000000ff;">: ' . $address . '</td></tr>
                <tr><td><strong>Phone</strong></td><td style="color: #000000ff;">: ' . $phone . '</td></tr>
                <tr><td><strong>Admisson No</strong></td><td style="color: #000000ff;">: ' . $admissionNo . '</td></tr>
                <tr><td><strong>Blood Group</strong></td><td style="color: #000000ff;">: ' . $bloodGroup . '</td></tr>
                <tr><td><strong>DOB</strong></td><td style="color: #000000ff;">: ' . $dob . '</td></tr>
            </table>
        </div>
    </div>
</body>
</html>';

// === Generate PDF ===
$pdfPath = '';
try {
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper([0, 0, 55 * 2.83465, 85 * 2.83465], 'portrait');
    $dompdf->render();
    $pdfData = $dompdf->output();
    
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
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Change to DEBUG_SERVER for troubleshooting
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'idcardpro9@gmail.com'; // Replace with your email
        $mail->Password = 'rptoxgufsmmqpqlh'; // Replace with your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('no-reply@example.com', 'ID Card Generator');
        $mail->addAddress('rhmnramees730@gmail.com'); // Primary recipient
        // $mail->addCC('another@example.com'); // Optional CC
        
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