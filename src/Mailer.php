<?php

namespace Vnetby\Wptheme;

use PHPMailer\PHPMailer\PHPMailer;
use Vnetby\Wptheme\Front\Template;

class Mailer
{

    /**
     * - Отправляет письмо администртору
     * @param array $args
     * @return void
     */
    public static function sendAdmin(array $args)
    {
        $body = self::getEmailBody('admin', $args);
        $to = Container::getLoader()->getEmailAdmin();
        return self::send($to, $body);
    }


    /**
     * - Подключает шаблон
     * @param string $template
     * @param array $args
     * @return string
     */
    protected static function getEmailBody(string $template, array $args = []): string
    {
        $str = Container::getClassTemplate()::getEmailTemplate('head');
        $str .= Container::getClassTemplate()::getEmailTemplate($template, $args);
        $str .= Container::getClassTemplate()::getEmailTemplate('footer');
        return $str;
    }


    /**
     * - Отправляет письмо
     * - Работает с PHPMailer
     * @see https://github.com/PHPMailer/PHPMailer
     * @return bool 
     */
    protected static function send(string $to, string $body, string $subject = '', array $headers = [], array $attachs = [])
    {
        $loader = Container::getLoader();

        $mail = new PHPMailer(false);

        $mail->setFrom($loader->getEmailFrom(), $loader->getEmailFromName());
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Subject = $subject;
        $mail->Body = $body;

        foreach ($headers as $header => $headerValue) {
            $mail->addCustomHeader($header, $headerValue);
        }

        foreach ($attachs as $filePath) {
            if (!preg_match("/^\//", $filePath)) {
                $filePath = "/{$filePath}";
            }
            $filePath = $_SERVER['DOCUMENT_ROOT'] . $filePath;
            $mail->addAttachment($filePath);
        }

        if ($loader->smtp()) {
            $mail->isSMTP();
            $mail->Host = $loader->getSmtpHost();
            $mail->Port = $loader->getSmtpPort();
            $mail->SMTPSecure = $loader->smtpSsl() ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            if ($loader->getSmtpUser()) {
                $mail->SMTPAuth = true;
                $mail->Username = $loader->getSmtpUser();
                $mail->Password = $loader->getSmtpPass();
            }
        } else {
            $mail->isMail();
        }

        return $mail->send();
    }
}
