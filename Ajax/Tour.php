<?php

namespace Vnet\Ajax;

use Vnet\Contact\Telegram;
use Vnet\Entities\PostTour;
use Vnet\Entities\PostTrip;
use Vnet\Entities\TermCity;
use Vnet\Helpers\Date;
use Vnet\Theme\About;
use Vnet\Theme\B24;
use Vnet\Theme\FormBooking;
use Vnet\Theme\Price;
use Vnet\Theme\Template;

class Tour extends Core
{


    /**
     * - Фильтрует посты
     */
    function filter()
    {
        $citiesIds = $_REQUEST['city'] ?? [];
        $catsIds = $_REQUEST['category'] ?? [];
        $tours = PostTour::filter($citiesIds, $catsIds);
        Template::theTemplate('tours-filter-content', ['cities' => $citiesIds, 'tours' => $tours, 'categories' => $catsIds]);
        exit;
    }


    /**
     * - Получает tab на странице тура
     */
    function getTab()
    {
        $tab = $_REQUEST['tab'];
        $tourId = $_REQUEST['tourId'];
        $tripId = $_REQUEST['tripId'];

        $tour = PostTour::getById((int)$tourId);
        $trip = $tripId ? PostTrip::getById((int)$tripId) : null;

        Template::theTemplate('tour-tabs/layout', [
            'active' => false,
            'key' => $tab,
            'tour' => $tour,
            'trip' => $trip
        ]);

        exit;
    }


    /**
     * - Бронирование тура
     * @return void 
     */
    function booking()
    {
        $tripId = (int)$_POST['trip'];
        $cityId = (int)$_POST['city_' . $tripId];

        $name = $_POST['name'];
        $phone = $_POST['phone'];

        $adults = !empty($_POST['adults']) ? (int)$_POST['adults'] : 0;
        $children = !empty($_POST['children']) ? (int)$_POST['children'] : 0;

        if (!B24::createBooking($name, $phone, $tripId, $cityId, $adults, $children, !empty($_REQUEST['messenger']['value']) ? $_REQUEST['messenger'] : [])) {
            $this->theError('Произошла ошибка при создании заявки, наши специалисты уже работают над ее устранением. Вы также можете оставить заявку по номеру телефона: ' . About::getMainPhone()['label'] . ' (viber, telegram, whatsapp)');
        }

        $this->theSuccess([
            'redirect' => '/thank-you/?data=' . base64_encode(
                serialize([
                    'trip' => $tripId,
                    'city' => $cityId,
                    'adults' => $adults,
                    'children' => $children
                ])
            )
        ]);

        // $tour = PostTour::getById($tourId);
        // $trip = PostTrip::getById($tripId);
        // $city = TermCity::getById($cityId);

        // if (!$tour || !$trip || !$city) {
        //     $this->theError();
        // }

        // $price = FormBooking::calcPrice($tripId, $cityId, $adults, $children);
        // $messenger = !empty($_POST['messenger']['value']) ? $_POST['messenger']['value'] . ' (' . $_POST['messenger']['type'] . ')' : '';

        // $msg = [
        //     'Тур' => $tour->getTitle(),
        //     'Выезд' => Date::format('d.m.Y', $trip->getStartDate('d.m.Y')),
        //     'Город' => $city->getName(),
        //     'Пассажиры' => ['Взрослых: ' . $adults, 'Детей: ' . $children],
        //     'Имя' => $name,
        //     'Телефон' => '+48' . $phone,
        //     'Мессенджер' => $messenger,
        //     'Стоимость' => number_format($price, '2', ',', ' ') . ' ' . Price::getMainCurrency()
        // ];

        // $msg = implode(PHP_EOL, $msg);

        // Telegram::sendInfoAdmin('Новое бронирование по телефону', $msg, []);

        // $this->theSuccess([
        //     'redirect' => '/thank-you/?data=' . base64_encode(
        //         serialize([
        //             'trip' => $trip->getId(),
        //             'city' => $city->getId(),
        //             'adults' => $adults,
        //             'children' => $children
        //         ])
        //     )
        // ]);
    }


    /**
     * - Получает таблицу с рассчетом стоимости
     * @return void 
     */
    function getSubtotal()
    {
        $tourId = (int)$_REQUEST['tour_id'];
        $tripId = !empty($_REQUEST['trip']) ? (int)$_REQUEST['trip'] : 0;
        $adults = !empty($_REQUEST['adults']) ? (int)$_REQUEST['adults'] : 0;
        $children = !empty($_REQUEST['children']) ? (int)$_REQUEST['children'] : 0;

        $cityId = !empty($_REQUEST['city_' . $tripId]) ? (int)$_REQUEST['city_' . $tripId] : 0;

        Template::theTemplate('booking-subtotal', [
            'tour' => PostTour::getById($tourId),
            'tripId' => $tripId,
            'cityId' => $cityId,
            'adults' => $adults,
            'children' => $children,
            'wrapClass' => ''
        ]);

        exit;
    }
}
