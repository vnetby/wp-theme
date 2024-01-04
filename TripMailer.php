<?php

/**
 * - Запускает рассылку по крону
 */

namespace Vnet;

use Error;
use MailerSend\Helpers\Builder\EmailParams;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\MailerSend;
use Vnet\Contact\Email;
use Vnet\Entities\PostTour;
use Vnet\Entities\PostTrip;
use Vnet\Helpers\Path;
use Vnet\Theme\Subscribe;
use Vnet\Theme\Template;
use Vnet\Types\TypeTrips;
use wpdb;


class TripMailer
{

    private static function createTable()
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        maybe_create_table($wpdb->prefix . 'trip_mailer', "CREATE TABLE `{$wpdb->prefix}trip_mailer` (
            `mailer_id` VARCHAR(50) NOT NULL,
            `subscribe_id` BIGINT UNSIGNED NOT NULL,
            `trip_id` BIGINT UNSIGNED NOT NULL,
            `create_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `send_date` DATETIME NULL,
            `status` TINYINT UNSIGNED NOT NULL DEFAULT 0
            )
        ");
    }


    static function startCron(int $tripId)
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;

        self::createTable();

        $date = date('Y-m-d H:i:s');
        $mailerId = md5(uniqid() . $tripId . $date);

        if (!MAILER_TEST) {
            $subscribs = Subscribe::getTourSubscribs();
        } else {
            $subscribs = [Subscribe::get(MAILER_TEST_EMAIL)];
        }

        $hasSubscribs = [];

        foreach ($subscribs as $subscribeData) {
            $subscribeData = (array)$subscribeData;

            $email = $subscribeData['name'];
            $termId = $subscribeData['term_id'];

            if (in_array($email, $hasSubscribs)) {
                continue;
            }

            $hasSubscribs[] = $email;

            // раскоментировать если прошла валидация хотя бы один раз 
            // $exist = get_term_meta($termId, 'exist_email', true);
            // if ($exist != 1) {
            //     continue;
            // }

            $wpdb->insert($wpdb->prefix . 'trip_mailer', [
                'mailer_id' => $mailerId,
                'subscribe_id' => $subscribeData['term_id'],
                'trip_id' => (int)$tripId,
                'create_date' => $date
            ]);
        }

        add_post_meta($tripId, 'mailer_start_date', date('Y-m-d H:i:s'));

        $cronFile = Path::join(THEME_PATH, 'cron/trip-mailer.php');

        exec("nohup php -f {$cronFile} {$mailerId} > /dev/null &");
    }


    static function sendMailerDepartMultiple(PostTrip $trip)
    {
        // if ($trip->getMailerId()) {
        //     throw new Error('Рассылка по данному выезду уже запущена');
        // }

        $subject = self::getMailerDepartSubject($trip);
        $from = Loader::getInstance()->getEmailFrom();
        $fromName = Loader::getInstance()->getEmailFromName();

        $arEmails = self::getSubscribeEmails();

        $arEmails = array_chunk($arEmails, 499);

        if (count($arEmails) > 10) {
            throw new Error('Кол-во email адресов превышает лимиты сервиса mailersend');
        }

        $mailersend = new MailerSend(['api_key' => MAILSEND_TOKEN]);

        foreach ($arEmails as $recipEmails) {
            $bulkEmailParams = [];
            foreach ($recipEmails as $email) {
                $recipients = [
                    new Recipient($email, null)
                ];

                $body = self::getMailerDepartBody($trip, $email);

                $bulkEmailParams[] = (new EmailParams())
                    ->setFrom($from)
                    ->setFromName($fromName)
                    ->setRecipients($recipients)
                    ->setSubject($subject)
                    ->setHtml($body);
            }
            $mailersend->bulkEmail->send($bulkEmailParams);
            sleep(2);
        }

        // $res = $mailersend->bulkEmail->send($bulkEmailParams);

        // if (empty($res['body']['bulk_email_id'])) {
        //     throw new Error(!empty($res['body']['message']) ? $res['body']['message'] : 'Неопознанная ошибка от сервиса mailersend');
        // }

        // $bulkEmailId = $res['body']['bulk_email_id'];

        // $trip->setMailerId($bulkEmailId);
        $trip->updateEmailLastDate();
    }


    static function getBulkStatus(PostTrip $trip): ?array
    {
        $mailersend = new MailerSend(['api_key' => MAILSEND_TOKEN]);
        if (!$trip->getMailerId()) {
            return null;
        }
        $res = $mailersend->bulkEmail->getStatus($trip->getMailerId());
        return $res['body']['data'];
    }


    /**
     * - Получает массив электронных адресов
     */
    private static function getSubscribeEmails(): array
    {
        if (MAILER_TEST) {
            return [
                'vadzim.kananovich.by@gmail.com',
                'vadzim.kananovich.1995@gmail.com',
                'info@holly-travel.pl',
                'naladallas@mail.ru',
                'nala_dallas@gmail.com',
                'noreplay@holly-travel.pl',
                'elena@holly-travel.pl',
                'vadzim.kananovich.by@gmail.com',
                'vadzim.kananovich.1995@gmail.com',
                'info@holly-travel.pl',
                'naladallas@mail.ru',
                'nala_dallas@gmail.com',
                'noreplay@holly-travel.pl',
                'elena@holly-travel.pl',
                'elena@holly-travel.pl',
                'vadzim.kananovich.by@gmail.com',
                'vadzim.kananovich.1995@gmail.com',
                'info@holly-travel.pl',
                'naladallas@mail.ru',
                'nala_dallas@gmail.com',
                'noreplay@holly-travel.pl',
                'elena@holly-travel.pl',
            ];
        }

        $subscribs = array_unique(array_column(Subscribe::getTourSubscribs(), 'name'));

        return $subscribs;
    }


    static function execMailer(string $mailerId)
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;

        $tripId = 0;

        while ($emails = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}trip_mailer` WHERE `mailer_id` = '{$mailerId}' AND `status` = 0 LIMIT 1", ARRAY_A)) {
            $emailData = $emails[0];
            if (!$tripId) {
                $tripId = (int)$emailData['trip_id'];
            }
            $subscrube = Subscribe::getById($emailData['subscribe_id']);


            if ($email = $subscrube->name) {
                self::sendMailerDepart($email, $tripId);
                // Email::mailerDepart($email, $tripId);
            }

            $wpdb->update(
                $wpdb->prefix . 'trip_mailer',
                ['status' => 1],
                [
                    'mailer_id' => $emailData['mailer_id'],
                    'trip_id' => $tripId,
                    'subscribe_id' => $emailData['subscribe_id'],
                    'status' => 0
                ]
            );
            usleep(500);
        }

        delete_post_meta($tripId, 'mailer_start_date');

        $wpdb->query("DELETE FROM `{$wpdb->prefix}trip_mailer` WHERE `mailer_id` = '{$mailerId}'");
    }


    static function sendMailerDepart(string $email, int $tripId)
    {
        $trip = PostTrip::getById($tripId);

        if (!$trip) {
            return;
        }

        $subject = self::getMailerDepartSubject($trip);
        $body = self::getMailerDepartBody($trip, $email);

        $mailersend = new MailerSend(['api_key' => MAILSEND_TOKEN]);

        $recipients = [
            new Recipient($email, null),
        ];

        $emailParams = (new EmailParams())
            ->setFrom(Loader::getInstance()->getEmailFrom())
            ->setFromName(Loader::getInstance()->getEmailFromName())
            ->setRecipients($recipients)
            ->setSubject($subject)
            ->setHtml($body);

        try {
            $mailersend->email->send($emailParams);
        } catch (\Exception $e) {
        }
    }


    private static function getMailerDepartBody(PostTrip $trip, string $email)
    {
        $url = Subscribe::getUnsubscribeUrl($email);
        ob_start();
        Template::theTemplate('email/mailer-head');
        Template::theTemplate('email/mailer-depart', ['trip' => $trip]);
        Template::theTemplate('email/mailer-footer', ['unsubscribeUrl' => $url]);
        return ob_get_clean();
    }


    private static function getMailerDepartSubject(PostTrip $trip): string
    {
        $tour = $trip->getTour();
        $subject = 'Новый выезд ' . $tour->getTitle() . ' ' . $trip->getFullDate();
        return $subject;
    }



    /**
     * - Возврощает количество отправленных рассылок
     * @param string $trip_id 
     * 
     * @return array
     */
    static function getCountMailerSend($trip_id)
    {
        /**
         * @var wpdb $wpdb
         */
        global $wpdb;

        $allCount = 0;
        $sendCount = 0;

        if (!self::tableExists($wpdb->prefix . 'trip_mailer')) {
            return ['allCount' =>  $allCount, 'sendCount' => $sendCount];
        }


        $allCount = $wpdb->get_var("SELECT  COUNT(*) FROM `{$wpdb->prefix}trip_mailer` WHERE `trip_id` = '{$trip_id}'");
        $sendCount = $wpdb->get_var("SELECT  COUNT(*) FROM `{$wpdb->prefix}trip_mailer` WHERE `trip_id` = '{$trip_id}' AND `status` = 1");

        return ['allCount' =>  $allCount, 'sendCount' => $sendCount];
    }


    /**
     * - Проверяет существование таблицы
     * @param string $tableName название таблицы для проверки
     * 
     * @return bool
     */
    private static function tableExists($tableName)
    {
        global $wpdb;

        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($tableName));
        $res = $wpdb->get_var($query);

        return $res == $tableName;
    }
}
