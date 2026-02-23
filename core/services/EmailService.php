<?php
namespace core\services;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use think\facade\Log;
/**
 * Class EmailService
 * @package core\services
 */
class EmailService {
    
    public function create($email, $code)
    {
        $mail = new PHPMailer(true);
        try {
            // 配置SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.share-email.com';  // SMTP服务器
            $mail->SMTPAuth = true;
            $mail->Username = 'verify@alamoana.center'; // 登录SMTP的邮箱
            $mail->Password = 'Aa362400..';    // 邮箱密码或授权码
            $mail->SMTPSecure = 'ssl'; // 加密协议，常用 tls 或 ssl
            $mail->Port = 465;         // 端口号，tls一般是587，ssl一般是465
            // 发件人信息
            $mail->setFrom('verify@alamoana.center', 'Ala Moana Center');
            // 收件人
            $mail->addAddress($email);
            // 邮件内容
            $mail->isHTML(true);
            $mail->Subject = 'Alamall Verify';
            $mail->Body = "Your code is: <h2>{$code}</h2> Use it to verify your email in Alamall App. If you didn't request this, simply ignore this message.";
            $mail->send();
            return true;
        } catch (Exception $e) {
            Log::error('邮件发送失败：'.$mail->ErrorInfo);
            return false;
        }
    }
}