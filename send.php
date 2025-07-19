<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
$requiredFields = ['name', 'parentsName', 'address', 'phone', 'standard' , 'division', 'bloodGroup', 'admissionNo', 'dob', 'image'];
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

// === HTML template ===
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .card { width: 250px; height: 360px; border: 1px solid #ccc; padding: 0; }
        .header { background-color: #d72638; color: white; text-align: center; padding: 10px 5px; }
        .profile { text-align: center; margin: 10px 0; }
        .profile img { width: 80px; height: 80px; border-radius: 50%; border: 2px solid #d72638; }
        .name { text-align: center; margin-top: 5px; font-weight: bold; font-size: 15px; }
        .details { text-align: center; color: #d72638; font-size: 11px; margin: 2px 0 10px 0; }
        .info { font-size: 10px; margin: 0 10px; }
        .info table { width: 100%; }
        .info td { padding: 2px 0; }
        .barcode { text-align: center; margin-top: 15px; }
        .barcode img { width: 130px; height: 30px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header"><h3 style="margin: 0;">Esoft Factory</h3></div>
        <div class="profile"><img src="' . $image64 . '" /></div>
        <div class="name">' . $name . '</div>
        <div class="details">STD. ' . $standard . ' ' . strtoupper($division) . '</div>
        <div class="info">
            <table>
            <tr><td><strong>Parent:</strong></td><td style="color: #d72638;">' . $parentsName . '</td></tr>
            <tr><td><strong>Address:</strong></td><td style="color: #d72638;">' . $address . '</td></tr>
            <tr><td><strong>Phone:</strong></td><td style="color: #d72638;">' . $phone . '</td></tr>
            <tr><td><strong>Admisson No:</strong></td><td style="color: #d72638;">' .  $admissionNo . '</td></tr>
            <tr><td><strong>Blood Group:</strong></td><td style="color: #d72638;">' . $bloodGroup . '</td></tr>
            <tr><td><strong>DOB:</strong></td><td style="color: #d72638;">' . $dob . '</td></tr>
            </table>
        </div>
        <div class="barcode"><img src="https://dummyimage.com/130x30/000/fff&text=||||||||||||" /></div>
    </div>
</body>
</html>
';

// === Generate PDF ===
try {
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper(array(0, 0, 250, 360));
    $dompdf->render();
    
    $pdfData = $dompdf->output();
    $uploadDir = __DIR__ . "/uploads";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $pdfPath = "$uploadDir/idcard_" . time() . ".pdf";
    file_put_contents($pdfPath, $pdfData);

    // === Send email ===
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'idcardpro9@gmail.com';
    $mail->Password = 'rptoxgufsmmqpqlh';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('no-reply@example.com', 'ID Card Generator');
    $mail->addAddress('rhmnramees730@gmail.com'); // Change to appropriate recipient
    $mail->Subject = "Student ID Card for $name";
    $mail->Body = "Student: $name\nStandard: $standard\nAdmission No: $admissionNo\n\nPlease find the ID card attached.";
    $mail->addAttachment($pdfPath, 'IDCard.pdf');

    if ($mail->send()) {
        echo json_encode(['success' => true, 'message' => 'ID card generated and sent successfully!']);
    } else {
        throw new Exception('Email could not be sent.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    if (file_exists($pdfPath)) {
        @unlink($pdfPath);
    }
}