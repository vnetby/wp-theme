<?php

namespace Vnet\Helpers;

use Vnet\Loader;
use Vnet\Theme\Translator;

class Date
{

    static function format(string $format, string $date): string
    {
        return self::utcFunc(function () use ($format, $date) {
            // ACF хранит в таком формате даты
            if (preg_match("/[\d]{4}[\d]{2}[\d]{2}/", $date)) {
                $date = preg_replace("/([\d]{4})([\d]{2})([\d]{2})/", "$1-$2-$3", $date);
            }
            $time = is_numeric($date) ? $date : strtotime($date);
            $newDate = date($format, $time);
            return $newDate;
        });
    }


    static function getRuMoth(int $monthNum): string
    {
        $monthes = ['Янв', 'Февр', 'Марта', 'Апр', 'Мая', 'Июня', 'Июля', 'Авг', 'Сент', 'Окт', 'Ноя', 'Дек'];
        return $monthes[$monthNum - 1];
    }


    static function ruShortDate(string $date): string
    {
        return self::utcFunc(function () use ($date) {
            $time = strtotime($date);
            $day = date('d', $time);
            $year = date('Y', $time);
            $thisYear = date('Y', time());
            $month = self::getRuMoth(date('n', $time));
            if ($thisYear !== $year) {
                return "{$day} {$month} {$year}";
            }
            return "{$day} {$month}";
        });
    }


    static function ruShortDateRange(string $start, string $end): string
    {
        return self::utcFunc(function () use ($start, $end) {
            $endStr = self::ruShortDate($end);
            if (date('m', strtotime($start)) !== date('m', strtotime($end))) {
                $startDay = self::ruShortDate($start);
            } else {
                $startDay = date('d', strtotime($start));
            }
            return $startDay . ' - ' . $endStr;
        });
    }


    static function shortDateRange(string $start, string $end): string
    {
        return self::utcFunc(function () use ($start, $end) {
            $arrStart = [self::format('Y', $start), self::format('m', $start), self::format('d', $start)];
            $arrEnd = [self::format('Y', $end), self::format('m', $end), self::format('d', $end)];

            if (($arrStart[0] !== $arrEnd[0]) && ($arrStart[1] !== $arrEnd[1]) && ($arrStart[2] !== $arrEnd[2])) {
                return implode('.', array_reverse($arrStart)) . ' - ' . implode('.', array_reverse($arrEnd));
            }

            if (($arrStart[1] !== $arrEnd[1]) && ($arrStart[2] !== $arrEnd[2])) {
                array_shift($arrStart);
                return implode('.', array_reverse($arrStart)) . '-' . implode('.', array_reverse($arrEnd));
            }

            if ($arrStart[2] !== $arrEnd[2]) {
                return $arrStart[2] . '-' . implode('.', array_reverse($arrEnd));
            }

            return implode('.', array_reverse($arrStart));
        });
    }


    /**
     * - Добавляет дни к дате
     * @param string $date 
     * @param int $days 
     * @param string $format возвращаемый формат даты
     * @return string 
     */
    static function addDays(string $date, int $days, string $format = 'Y-m-d'): string
    {
        return self::utcFunc(function () use ($date, $days, $format) {
            return date($format, strtotime($date . ' + ' . $days . ' days'));
        });
    }


    static function getStringDay(string $date): string
    {
        $daysRu = [
            'Воскресенье',
            'Понедельник',
            'Вторник',
            'Среда',
            'Четверг',
            'Пятница',
            'Суббота'
        ];

        $daysPl = [
            'Niedziela',
            'Poniedziałek',
            'Wtorek',
            'Środa',
            'Czwartek',
            'Piątek',
            'Sobota'
        ];

        $days = Translator::isPl() ? $daysPl : $daysRu;

        return self::utcFunc(function () use ($date, $days) {
            $numDay = date('w', strtotime($date));
            return $days[$numDay];
        });
    }


    static function toTime(string $date): int
    {
        return self::utcFunc(function () use ($date) {
            return strtotime($date);
        });
    }


    /**
     * - Выполнит футнкцию во временной зоне UTC
     * - Вернет результат выполнения перданной функции
     * @param callable $fn 
     * @return mixed 
     */
    private static function utcFunc(callable $fn)
    {
        self::setUtc();
        $val = call_user_func($fn);
        self::restoreTimezone();
        return $val;
    }

    private static function setUtc()
    {
        date_default_timezone_set('UTC');
    }

    private static function restoreTimezone()
    {
        date_default_timezone_set(Loader::getInstance()->getTimezone());
    }
}
