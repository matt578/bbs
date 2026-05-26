<?php
declare(strict_types=1);

require_once __DIR__ . '/config_auth.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail(string $toEmail, string $toName, string $code): bool
{
    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');

        $mail->isHTML(true);
        $mail->Subject = 'Your verification code';
        $mail->Body = "
            <div style='font-family:Arial,sans-serif;font-size:14px;line-height:1.6;'>
                <h2>Bohol Bicycle Inventory</h2>
                <p>Hello {$safeName},</p>
                <p>Your verification code is:</p>
                <div style='font-size:28px;font-weight:bold;letter-spacing:4px;margin:16px 0;'>{$code}</div>
                <p>This code will expire in " . VERIFICATION_CODE_EXPIRY_MINUTES . " minutes.</p>
                <p>If you did not request this, you can ignore this email.</p>
            </div>
        ";

        $mail->AltBody = "Your verification code is: {$code}. This code expires in " . VERIFICATION_CODE_EXPIRY_MINUTES . " minutes.";

        return $mail->send();
    } catch (Exception $e) {
        error_log('Mail error: ' . $e->getMessage());
        return false;
    }
}