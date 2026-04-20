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
            $mail->CharSet = PHPMailer::CHARSET_UTF8;

            $mail->SMTPAuth   = true;
            $mail->Host       = env('SMTP_HOST', 'smtp.gmail.com');
            $mail->Username   = env('SMTP_USER', '');
            $mail->Password   = env('SMTP_PASS', '');

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) env('SMTP_PORT', 587);

            // Recipients
            $mail->setFrom(env('SMTP_FROM', EMAIL_ADMIN), 'Admin');
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
            return array("error" => "No se pudo enviar el correo.");

        } catch (Exception $e) {
            error_log("Mail error: " . $e->getMessage());
            return array(
                "error" => "Error enviando correo."
            );
        }
    }
}