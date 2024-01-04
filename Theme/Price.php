<?php

namespace Vnet\Theme;

class Price
{

    /**
     * - Основная валюта
     */
    private static $mainCurrency = 'zł';
    private static $mainCurrencyIso = 'PLN';

    private static $symbols = [
        'EUR' => '€',
        'USD' => '$',
        'PLN' => 'zł'
    ];


    /**
     * - Фильтрует цену с базы данных
     * @param string|int $dbPrice
     */
    static function fromDb($dbPrice)
    {
        $price = (int)$dbPrice;
        if (!$price) {
            return 0;
        }
        return $price / 100;
    }


    /**
     * - Формирует целое число для значение в БД
     * @param string|float|int $value
     * @return int
     */
    static function toDb($value)
    {
        $price = (int)((float)$value * 100);
        return $price;
    }


    /**
     * - Округляет цену
     * @param int|float $price
     * @return int|float
     */
    static function round($price)
    {
        $price = (int)($price * 100);
        return $price / 100;
    }


    /**
     * - Формирует цену для вывода пользователю
     * @param int|float $price
     * @param string $currency [optional]
     */
    static function formatPrice(float $price, $currency = null)
    {
        if ($currency !== false) {
            $currency = self::getCurrencySymbol($currency);
        }

        $strPrice = self::formatPriceValue($price);

        $res = '<span class="price-wrap">';
        $res .= '<span class="price">' . $strPrice . '</span>';
        if ($currency !== false) {
            $res .= ' <span class="currency">' . $currency . '</span>';
        }
        $res .= '</span>';

        return $res;
    }


    static function formatPriceValue(float $price): string
    {
        $price = abs($price);
        $strPrice = number_format($price, 2, ',', ' ');
        $strPrice = preg_replace("/,[0]{2}$/", '', $strPrice);
        return $strPrice;
    }


    static function getCurrencySymbol($iso = null)
    {
        if (!$iso) {
            return self::$mainCurrency;
        }
        if (isset(self::$symbols[$iso])) {
            return self::$symbols[$iso];
        }
        return $iso;
    }


    static function getMainCurrency($iso = false)
    {
        if ($iso) {
            return self::$mainCurrencyIso;
        }
        return self::$mainCurrency;
    }
}
