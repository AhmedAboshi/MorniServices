<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
header('Content-Type: text/html; charset=utf-8');
require __DIR__ . '/../vendor/autoload.php';

function sendOTP($email, $otp){

    $mail = new PHPMailer(true);
 $mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';
    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        // 🔴 غيرها بإيميلك
       $mail->Username = 'ahmedhider359@gmail.com';
       $mail->Password = 'sosw hmdg ruti popb';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('yourgmail@gmail.com', 'Fleet management system');
        $mail->addAddress($email);
     
$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';

        $mail->isHTML(true);
        $mail->Subject = "Your OTP Code";

        $mail->Body = "
        <div style='font-size:18px'>
            <h3>كود التحقق الخاص بك</h3>
            <p><b style='font-size:22px'>$otp</b></p>
            <p>الكود صالح لمدة 3 دقائق فقط.</p>
        </div>";

        $mail->send();

        return true;

    } catch (Exception $e) {
        return false;
    }
}
function sendMail($email, $subject, $message){

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        $mail->Username = 'ahmedhider359@gmail.com';
        $mail->Password = 'sosw hmdg ruti popb';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('ahmedhider359@gmail.com', 'Fleet management system');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        return $mail->send();

    } catch (Exception $e) {
        return false;
    }
}
?>