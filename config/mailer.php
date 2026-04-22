<?php
/**
 * Centralized SMTP Mailer using PHPMailer
 * SMTP: smtp.hostinger.com:465 (SSL)
 * From: support@connectwith.in
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email via Hostinger SMTP.
 *
 * @param string $to         Recipient email
 * @param string $subject    Email subject
 * @param string $body       HTML (or plain-text) body
 * @param string $toName     Recipient name (optional)
 * @param bool   $isHtml     Whether body is HTML (default true)
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendMail($to, $subject, $body, $toName = '', $isHtml = true) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug  = 0;
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST')       ?: 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_FROM_EMAIL') ?: 'support@connectwith.in';
        $mail->Password   = getenv('SMTP_PASSWORD')   ?: '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int)(getenv('SMTP_PORT') ?: 465);

        // From / To
        $fromEmail = getenv('SMTP_FROM_EMAIL') ?: 'support@connectwith.in';
        $fromName  = getenv('SMTP_FROM_NAME')  ?: 'ConnectWith9';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to, $toName);
        $mail->addReplyTo($fromEmail, $fromName);

        // Content
        $mail->isHTML($isHtml);
        $mail->CharSet  = 'UTF-8';
        $mail->Subject  = $subject;
        $mail->Body     = $body;
        if ($isHtml) {
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $body));
        }

        $mail->send();
        return ['success' => true, 'error' => null];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Wrap plain text in a simple branded HTML template.
 */
function mailHtmlTemplate($title, $bodyContent) {
    return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
  .wrapper { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; }
  .header { background: linear-gradient(135deg, #0B1C3D, #1E3A8A); color: white; padding: 24px 30px; }
  .header h2 { margin: 0; font-size: 20px; }
  .content { padding: 30px; color: #333; font-size: 15px; line-height: 1.7; }
  .footer { background: #f8f8f8; padding: 16px 30px; font-size: 12px; color: #999; text-align: center; border-top: 1px solid #eee; }
  a { color: #FF6A00; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header"><h2>' . $title . '</h2></div>
  <div class="content">' . $bodyContent . '</div>
  <div class="footer">ConnectWith9 &bull; support@connectwith.in &bull; <a href="https://connectwith9.com">connectwith9.com</a></div>
</div>
</body>
</html>';
}
