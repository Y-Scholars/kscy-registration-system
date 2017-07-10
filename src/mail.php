<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 07. 10
 */

require_once('./mail.account.php');
require_once('./vendor/phpmailer/phpmailer/PHPMailerAutoload.php');

class Mailer {

    public function send($email, $title, $content) {
    
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->CharSet    = "utf-8";
        $mail->Encoding   = "base64";
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
        
        if (!$mail->send())
            return false;
        
        return true;
    }
}

$mail = new Mailer;
?>