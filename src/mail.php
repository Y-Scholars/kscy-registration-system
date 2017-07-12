<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 07. 10
 */

require_once('./mail.account.php');
require_once('./vendor/phpmailer/phpmailer/PHPMailerAutoload.php');

date_default_timezone_set('Asia/Seoul');

class Mailer {

    public function send($email, $title, $content) {
    
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->ContentType= "text/html";
        $mail->CharSet    = "utf-8";
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = MAILER_USERNAME;
        $mail->Password   = MAILER_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->setFrom(MAILER_USERNAME, MAILER_FROM);
        $mail->AddReplyTo(MAILER_USERNAME, MAILER_FROM);
        $mail->addAddress($email);
        
        $mail->isHTML(true);
        
        $mail->Subject = $title;
        $mail->Body    = $content;
        
        $success = $mail->send();

        return $success;
    }
}

$mail = new Mailer;
?>