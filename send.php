<?php
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// === Receive input ===
$data = json_decode(file_get_contents("php://input"), true);
$name     = htmlspecialchars($data['name'] ?? '');
$email    = htmlspecialchars($data['email'] ?? '');
$phone    = htmlspecialchars($data['phone'] ?? '');
$image64  = $data['image'] ?? '';

if (!$email || !$image64 || $image64 === 'data:,') {
    http_response_code(400);
    echo "Missing email or image.";
    exit;
}

// === Clean HTML template ===
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .card {
            width: 250px;
            height: 360px;
            border: 1px solid #ccc;
            padding: 0;
            box-sizing: border-box;
        }
        .header {
            background-color: #d72638;
            color: white;
            text-align: center;
            padding: 10px 5px;
        }
        .profile {
            text-align: center;
            margin: 10px 0;
        }
        .profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid #d72638;
        }
        .name {
            text-align: center;
            margin-top: 5px;
            font-weight: bold;
            font-size: 15px;
        }
        .role {
            text-align: center;
            color: #d72638;
            font-size: 11px;
            margin: 2px 0 10px 0;
        }
        .info {
            font-size: 10px;
            margin: 0 10px;
        }
        .info table {
            width: 100%;
        }
        .info td {
            padding: 2px 0;
        }
        .barcode {
            text-align: center;
            margin-top: 15px;
        }
        .barcode img {
            width: 130px;
            height: 30px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h3 style="margin: 0;">Esoft Factory</h3>
        </div>
        <div class="profile">
            <img src="' . $image64 . '" />
        </div>
        <div class="name">' . $name . '</div>
        <div class="role">Graphic Designer</div>
        <div class="info">
            <table>
                <tr>
                    <td><strong>ID No:</strong></td>
                    <td style="color: #d72638;">A458341290</td>
                </tr>
                <tr>
                    <td><strong>Blood:</strong></td>
                    <td style="color: #d72638;">O+</td>
                </tr>
                
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td style="color: #d72638;">' . $phone . '</td>
                </tr>
            </table>
        </div>
        <div class="barcode">
            <img src="https://dummyimage.com/130x30/000/fff&text=||||||||||||" />
        </div>
    </div>
</body>
</html>
';

// === Generate PDF ===
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper(array(0, 0, 250, 360)); // Custom size in points
$dompdf->render();

$pdfData = $dompdf->output();
$uploadDir = __DIR__ . "/uploads";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
$pdfPath = "$uploadDir/idcard_" . time() . ".pdf";
file_put_contents($pdfPath, $pdfData);

// === Send email ===
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'idcardpro9@gmail.com';
    $mail->Password = 'rptoxgufsmmqpqlh';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('no-reply@example.com', 'ID Card Generator');
    $mail->addAddress('rhmnramees730@gmail.com');
    $mail->Subject = "Your ID Card";
    $mail->Body = "Hi {$name},\n\nPlease find your ID card attached.";
    $mail->addAttachment($pdfPath, 'IDCard.pdf');

    $mail->send();
    echo "Email sent successfully!";
} catch (Exception $e) {
    echo "Mailer Error: " . $mail->ErrorInfo;
} finally {
    @unlink($pdfPath);
}
