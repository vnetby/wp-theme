<?php

namespace Vnet\Ajax;

use Vnet\Contact\Telegram;
use Vnet\Entities\PostTour;
use Vnet\Theme\B24;
use Vnet\Theme\Subscribe;

class Forms extends Core
{


    /**
     * - Подписка на рассылку на странице тура и в подвале
     */
    function subscribe()
    {
        $this->validate([
            'captcha' => true,
            'validate' => [
                'email' => 'required|email'
            ]
        ]);

        $tourId = !empty($_REQUEST['tour_id']) ? esc_sql($_REQUEST['tour_id']) : null;

        $msg = 'Ваша подписка успешно оформлена. Мы будем держать Вас в курсе новых туров и выездов.';
        $adminMsg = 'Подписка на рассылку: ' . $_REQUEST['email'];

        if ($tourId) {
            $tour = PostTour::getById((int)$tourId);
            $msg = "Ваша подписка по туру \"{$tour->getTitle()}\" успешно оформлена. Мы будем держать Вас в курсе новых выездов.";
            $adminMsg .= PHP_EOL . 'тур: ' . $tour->getTitle();
        }

        if (Subscribe::add(esc_sql($_REQUEST['email']), $tourId)) {
            B24::createNotif($adminMsg);
            $this->theSuccess([
                'msg' => $msg,
                'clearInputs' => true
            ]);
        }

        $this->theError([
            'msg' => 'serverError',
            'clearInputs' => false
        ]);
    }


    /**
     * - Заказть обратный звонок
     */
    function backRequest()
    {
        $this->validate([
            'protected' => false,
            'captcha' => true,
            'referer' => true,
            'validate' => [
                'phone' => 'required'
            ]
        ]);

        $resCreate = B24::createLead([
            'FIELDS' => [
                'TITLE' => 'Обратный звонок: ' . $_REQUEST['phone'],
                'PHONE' => [
                    [
                        'VALUE' => '+48' . $_REQUEST['phone'],
                        'TYPE' => 'WORK'
                    ]
                ]
            ]
        ]);

        if (!$resCreate) {
            $this->theError();
        }

        // if (!Telegram::sendBackRequest($_POST)) {
        //     $this->theError();
        // }

        $this->theSuccess([
            'msg' => 'Ваша заявка успешно отправлена. Наш менеджер Вам перезвонит.',
            'clearInputs' => true
        ]);
    }

    /**
     * - Форма на странице контактов
     * @return void 
     */
    function contactForm()
    {
        $this->validate([
            'protected' => false,
            'captcha' => true,
            'validate' => [
                'name' => 'required|min:3|max:50',
                'phone' => 'required|max:30',
                'email' => 'email|max:90',
                'comment' => 'required|max:500'
            ]
        ]);

        $resCreate = B24::createLead([
            'FIELDS' => [
                'TITLE' => 'Сообщение с сайта: ' . $_REQUEST['name'],
                'NAME' => $_REQUEST['name'],
                'PHONE' => [
                    [
                        'VALUE' => '+48' . $_REQUEST['phone'],
                        'TYPE' => 'WORK'
                    ]
                ],
                'EMAIL' => [
                    [
                        'VALUE' => $_REQUEST['email'],
                        'TYPE' => 'WORK'
                    ]
                ],
                'COMMENTS' => $_REQUEST['comment']
            ]
        ]);

        if (!$resCreate) {
            $this->theError();
        }

        // if (!Telegram::sendContactForm($_POST)) {
        //     $this->theError();
        // }

        $this->theSuccess([
            'msg' => 'Ваше сообщение успешно отправлено. В бижайшее время с Вами свяжется наш менеджер.',
            'clearInputs' => true
        ]);
    }
}
