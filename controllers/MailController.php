<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailController {

    /*
        tls -> 587
        ssl -> 465
    */
    public function sendEmail($email, $subjet, $message) {

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail::CHARSET_UTF8;
            
            $mail->SMTPAuth   = true; // Enable SMTP authentication
            $mail->Host       = ""; // Set the SMTP server to send through
            $mail->Username   = ""; // SMTP username
            $mail->Password   = "";

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            //Recipients
            $mail->setFrom(EMAIL_ADMIN, 'Admin');
            $mail->addAddress($email);


            // Content
            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = $subjet;
            $mail->Body    = $message;
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            if ($mail->send()) {
                return array(
                    "message" => "correo enviado"
                );
            }

        } catch (Exception $e) {
            return array(
                "error" => "Error: {$mail->ErrorInfo}"
            );
        }
    }
}