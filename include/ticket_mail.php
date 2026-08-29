<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendTicketReplyMail($email, $name, $ticketNumber, $message){

    $mail = new PHPMailer(true);

    try {

        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;

        /* 🔴 لازم App Password */
        $mail->Username = 'ahmedhider359@gmail.com';
       $mail->Password = 'sosw hmdg ruti popb';

        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('yourgmail@gmail.com', 'Support System');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
        $mail->Subject = "Reply to Ticket #$ticketNumber";

        $mail->Body = "
        <div style='font-family:Arial'>
            <h3>تم الرد على تذكرتك</h3>
            <p><b>رقم التذكرة:</b> $ticketNumber</p>
            <p><b>الرد:</b><br>$message</p>
        </div>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log($mail->ErrorInfo);
        return false;
    }
}