<?php

/**
 * - Табы на странице тура
 */

namespace Vnet\Theme;

use Vnet\Constants\QueryVars;
use Vnet\Entities\PostTour;
use Vnet\Entities\PostTrip;
use Vnet\Helpers\Path;

class TourTabs
{

    private static $keyPayment = 'payment';

    private static $keyProgram = 'program';


    static function getTabs(): array
    {
        $tourTabs = [
            'description' => [
                'label' => __('Описание', 'vnet'),
                'ico' => 'about'
            ],
            'program' => [
                'label' => __('Программа', 'vnet'),
                'ico' => 'schedule'
            ],
            'payment' => [
                'label' => __('Оплата', 'vnet'),
                'ico' => 'cash'
            ],
            'locations' => [
                'label' => __('Места сбора', 'vnet'),
                'ico' => 'loc'
            ],
            // 'gallery' => [
            //     'label' => 'Галерея',
            //     // 'ico' => 'gallery'
            // ],
            'reviews' => [
                'label' => __('Отзывы', 'vnet'),
                'ico' => 'comment'
            ]
        ];
        return $tourTabs;
    }


    static function getActiveKey(): string
    {
        $activeTab = get_query_var(QueryVars::TOUR_TAB);

        if (!$activeTab) {
            $activeTab = self::getDefTabKey();
        }

        return $activeTab;
    }


    static function getDefTabKey(): string
    {
        return array_keys(self::getTabs())[0];
    }


    static function getTourTabPaymentKey(): string
    {
        return self::$keyPayment;
    }


    static function getTourTabProgramKey(): string
    {
        return self::$keyProgram;
    }


    /**
     * - Формирует ссылку на страницу таба
     * @param string $tabKey 
     * @param PostTour $tour 
     * @param PostTrip|null $trip 
     * @return string 
     */
    static function buildUrl(string $tabKey, \Vnet\Entities\PostTour $tour, \Vnet\Entities\PostTrip $trip = null): string
    {
        $url = $trip ? $trip->getPermalink() : $tour->getPermalink();

        if ($tabKey === self::getDefTabKey()) {
            return $url;
        }

        return Path::join($url, $tabKey) . '/';
    }
}
