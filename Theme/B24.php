<?php

namespace Vnet\Theme;

use Vnet\Entities\PostTrip;
use Vnet\Entities\TermCity;
use Vnet\Helpers\Path;

class B24
{

    private static $messTypes = [
        'viber' => 'VIBER',
        'tg' => 'TELEGRAM',
        'whatsapp' => 'OTHER'
    ];


    /**
     * - Создает лид с новым бронированием
     * @return bool 
     */
    static function createBooking(string $name, string $phone, int $tripId, int $cityId, $adults = 1, $children = 0, $messenger = []): bool
    {
        $trip = PostTrip::getById($tripId);
        $city = TermCity::getById($cityId);

        if (!$trip || !$city) {
            return false;
        }

        $totalPass = $adults + $children;
        $nights = $trip->getTourNights();

        // общая стоимость заявки
        $price = FormBooking::calcPrice($tripId, $cityId, $adults, $children);

        // предоплата
        $prepayment = $trip->getPrepayment() * $totalPass;
        $prepaymentCur = $trip->getPrepaymentCurrency();

        // стоимость отеля
        $hotel = $trip->getHotelPrice() * $nights * $totalPass;
        $hotelCur = $trip->getHotelPriceCurrency();

        // туристическая услуга
        $tax = $trip->getTax() * $totalPass;
        $taxCur = $trip->getTaxCurrency();

        $data = [
            'FIELDS' => [
                'TITLE' => 'Бронь с сайта: ' . $name,
                'PHONE' => [
                    [
                        'VALUE' => '+48' . $phone,
                        'TYPE' => 'WORK'
                    ]
                ],
                'NAME' => $name,
                'OPPORTUNITY' => $price,
                'CURRENCY_ID' => Price::getMainCurrency(true),
                'SOURCE_ID' => 'STORE',
                B24_FIELD_LEAD_COUNT_TOURISTS => $totalPass,
                B24_FIELD_LEAD_CHILDREN => $children,
                B24_FIELD_LEAD_TRIP => [$trip->getCrmTripId()],
                B24_FIELD_LEAD_CITY => [$city->getCrmCityId()],
                B24_FIELD_LEAD_PREPAYMENT => $prepayment . '|' . $prepaymentCur,
                B24_FIELD_LEAD_TAX => $tax . '|' . $taxCur,
                B24_FIELD_LEAD_HOTEL => $hotel . '|' . $hotelCur,
            ]
        ];

        $utm = Utm::getInstance()->toUpperArray();

        $data['FIELDS'] = array_merge($data['FIELDS'], $utm);

        if ($messenger) {
            $type = self::$messTypes[$messenger['type']] ?? 'OTHER';
            $val = $messenger['value'] ?? '';
            if ($type === 'OTHER') {
                $val = $messenger['type'] . ': ' . $val;
            }
            $data['FIELDS']['IM'] = [
                [
                    'VALUE' => $val,
                    'VALUE_TYPE' => $type
                ]
            ];
        }

        $res = self::createLead($data);

        return $res;
    }


    static function createNotif(string $message)
    {
        $data = [
            'MESSAGE' => $message
        ];

        foreach (B24_NOTIF_USERS as $userId) {
            $data['USER_ID'] = $userId;
            self::post('/rest/1/mpiv1ci86ptcn6r0/im.notify.personal.add.json', $data);
        }
    }

    /**
     * - Создает новый лид
     * @param array $params
     * @return void 
     */
    static function createLead(array $params): bool
    {
        if (!isset($params['FIELDS']['ASSIGNED_BY_ID'])) {
            $params['FIELDS']['ASSIGNED_BY_ID'] = B24_MAIN_MANAGER_ID;
        }
        if (!isset($params['FIELDS']['CREATED_BY_ID'])) {
            $params['FIELDS']['CREATED_BY_ID'] = B24_MAIN_MANAGER_ID;
        }
        $res = self::post('/rest/1/2u1j0qfuwyvit9h7/crm.lead.add.json', $params);
        return !empty($res['body']['result']);
    }


    private static function post(string $path, array $getParams = [], $postParams = []): array
    {
        return self::sendRequest('POST', $path, $getParams, $postParams);
    }

    private static function get(string $path, array $getParams = []): array
    {
        return self::sendRequest('GET', $path, $getParams);
    }


    /**
     * - Отправляет запрос
     * @return array 
     */
    private static function sendRequest(string $method, string $path, array $getParams = [], $postParams = [], $headers = []): array
    {
        $method = strtoupper($method);
        $url = Path::join(B24_URL, $path);

        if ($getParams) {
            $url .= '?' . http_build_query($getParams);
        }

        $headers = array_merge($headers, [
            "Content-type: application/json"
        ]);

        $ch = curl_init($url);

        $chParams = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT =>  30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HTTPAUTH => CURLAUTH_ANY,
            CURLOPT_USERPWD => B24_USER . ':' . B24_PASS
        ];

        if ($method !== 'GET') {
            if ($method === 'POST') {
                $chParams[CURLOPT_POST] = true;
            } else {
                $chParams[CURLOPT_CUSTOMREQUEST] = $method;
            }
        }

        if ($postParams) {
            $chParams[CURLOPT_POSTFIELDS] = $postParams;
        }

        curl_setopt_array($ch, $chParams);

        $resBody = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);

        $res = [
            'info' => $info,
            'error' => $error,
            'body' => $resBody ? @json_decode($resBody, true) : null
        ];

        return $res;
    }
}
