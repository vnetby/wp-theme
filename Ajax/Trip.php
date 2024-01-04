<?php

namespace Vnet\Ajax;

use Vnet\Contact\Email;
use Vnet\Entities\PostTrip;
use Vnet\Theme\Subscribe;
use Vnet\TripMailer;

class Trip extends Core
{


    /**
     * - Отправляет рассылку по выезду
     * @return void 
     */
    function sendMailer()
    {
        $this->validate([
            'protected' => true,
            'validate' => [
                'trip_id' => 'required|numeric'
            ]
        ]);

        $trip = PostTrip::getById((int)$_REQUEST['trip_id']);

        // $subscribs = Subscribe::getTourSubscribs();
        // $emails = array_column($subscribs, 'name');
        // $emails = array_filter(array_unique($emails));

        // Email::mailerDepart($subscribs, $trip);
        // TripMailer::startCron($trip->getId());
        try {
            TripMailer::sendMailerDepartMultiple($trip);
        } catch (\Throwable $e) {
            $this->theError($e->getMessage());
        }

        // $trip->updateEmailLastDate();

        $this->theSuccess();
    }

    /**
     * - Получает статус рассылки
     * @return void 
     */
    function getCurrentMailerStatus()
    {
        $this->validate([
            'protected' => true,
            'validate' => [
                'trip_id' => 'required|numeric'
            ]
        ]);

        $trip = PostTrip::getById((int)$_REQUEST['trip_id']);

        $status = TripMailer::getBulkStatus($trip);

        if ($status['state'] == 'completed') {
            echo 'Рассылка завершена';
            exit;
        }

        echo '';

        exit;

        // $data = TripMailer::getCountMailerSend($tripId);

        // if (!get_post_meta($tripId, 'mailer_start_date')) {
        //     $this->theSuccess([
        //         'data' =>
        //         '<p> Все рассылки отправлены! </p>'
        //     ]);
        // }

        // $this->theSuccess([
        //     'data' =>
        //     '<p>Количество отправленных рассылок:<br> <b>' . $data['sendCount'] . '</b> из <b>' . $data['allCount'] . '</b></p>'
        // ]);
    }


    /**
     * - Отправляет тестовое письмо по рассылке
     * @return void 
     */
    function sendTestMailer()
    {
        $this->validate([
            'protected' => true,
            'validate' => [
                'test-email' => 'required|email',
                'trip_id' => 'required|numeric'
            ]
        ]);

        TripMailer::sendMailerDepart($_REQUEST['test-email'], (int)$_REQUEST['trip_id']);
        // Email::mailerDepart($_REQUEST['test-email'], (int)$_REQUEST['trip_id']);

        $this->theSuccess();
    }
}
