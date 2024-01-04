<?php

/**
 * - форма бронирования. Рассчитывает стоимость тура
 */

namespace Vnet\Theme;

use Vnet\Entities\PostTrip;

class FormBooking
{

    private static $childDiscount = 10;
    private static $childDiscountLong = 5;

    private static $childDiscountAge = 16;

    // процент скидки на 1-го взрослого пассажира при групповом бронировании
    private static $groupDiscountPercent = 30;
    private static $groupDiscountPercentLong = 15;

    // минимальное кол-во туристов брони для скидки группового бронирования
    private static $groupDiscountMin = 5;
    // минимальное кол-во взрослых в брони для групповой скидки
    private static $groupDiscountMinAdults = 3;


    static function getChildDiscountAge(): int
    {
        return self::$childDiscountAge;
    }


    static function getChildDiscount(\Vnet\Entities\PostTour $tour = null): int
    {
        if (!$tour) {
            return self::$childDiscount;
        }
        return $tour->getTourNights() < 3 ? self::$childDiscount : self::$childDiscountLong;
    }


    static function getGroupDiscount(\Vnet\Entities\PostTour $tour = null): int
    {
        if (!$tour) {
            return self::$groupDiscountPercent;
        }
        return $tour->getTourNights() < 3 ? self::$groupDiscountPercent : self::$groupDiscountPercentLong;
    }


    /**
     * - Рассчитвает стоимость тура
     * @param int $tripId 
     * @param int $cityId 
     * @param int $adults 
     * @param int $children 
     * @return float 
     */
    static function calcPrice(int $tripId, int $cityId = 0, int $adults = 0, int $children = 0): float
    {
        $trip = PostTrip::getById($tripId);
        $tour = $trip->getTour();

        // стоимость на 1-го взрослого
        $adultPrice = $trip->getPrice();

        if ($cityId) {
            $adultPrice += $trip->getCityAddPrice($cityId);
        }

        // стоимость на 1-го ребенка
        $childPrice = $adultPrice - (self::getChildDiscount($tour) * $adultPrice / 100);

        // скидка основному пассажиру при групповом бронировании
        $groupDiscount = self::getGroupDiscount($tour) * $adultPrice / 100;

        // общая стоимость без групповой скидки
        $total = ($adults * $adultPrice) + ($children * $childPrice);

        // применяем групповую скидку
        if (self::canApplyGroupDiscount($adults, $children)) {
            $total -= $groupDiscount;
        }

        return round($total, 2);
    }


    static function canApplyGroupDiscount(int $adults = 0, int $children = 0): bool
    {
        if (($adults + $children) < self::$groupDiscountMin) {
            return false;
        }
        if ($adults < self::$groupDiscountMinAdults) {
            return false;
        }
        return true;
    }
}
