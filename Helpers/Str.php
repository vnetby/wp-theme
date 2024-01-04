<?php

namespace Vnet\Helpers;


class Str
{

    /**
     * @var NCLNameCaseRu
     */
    private static $declination = null;
    private static $hasDeclinationLib = false;

    /**
     * - Склонение числительных
     * @param int $numeber число
     * @param array $titles массив строк из 3-х элементов:
     *   ['Сидит %d котик', 'Сидят %d котика', 'Сидит %d котиков']
     */
    static function declenNum(int $number, array $titles): string
    {
        $cases = [2, 0, 1, 1, 1, 2];
        $format = $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
        return sprintf($format, $number);
    }


    /**
     * - Обрезает строку до кол-ва символов
     * @param string $str 
     * @param int $softCount сколько слов оставить
     * @param int $hardCount на какое максимальное кол-во слов делать проверку
     * @return string 
     */
    static function cut(string $str, int $softCount, int $hardCount, string $moreStr = ' ...'): string
    {
        $str = trim(strip_tags($str));

        preg_match_all("/([^\s]{3,})/miu", $str, $matches);

        if (count($matches[0]) <= $hardCount) {
            return $str;
        }

        preg_match_all("/([^\s]+)/miu", $str, $matches);

        $newStr = '';
        $countWords = 0;

        foreach ($matches[0] as $word) {
            if ($countWords > $softCount) {
                $newStr .= $moreStr;
                break;
            }
            // if (mb_strlen($word) < 3) {
            //     continue;
            // }
            if (!$newStr) {
                $newStr = $word;
            } else {
                $newStr .= ' ' . $word;
            }
            $countWords++;
        }

        return $newStr;
    }


    static function getClassName(string $fullClassName): string
    {
        preg_match("/\\\([^\\\]+)$/", $fullClassName, $mathes);
        return $mathes[1] ?? '';
    }


    /**
     * - Склорнение по падежам
     * @see https://dwweb.ru/page/php/function/011_padeji_v_php.html
     * 
     * @param string $str строка для склонени
     * @param int $dec индекс падежа (0 = именительный; 1 = родительный ...)
     * 
     * @return string
     */
    static function declination($str, $dec = 0)
    {
        self::setupDeclination();

        if (!self::$declination) {
            return $str;
        }

        if (mb_strtolower($str) == 'познань' && $dec = 1) {
            return 'Познани';
        }

        if (mb_strtolower($str) == 'лодзь' && $dec = 1) {
            return 'Лодзи';
        }

        if (mb_strtolower($str) == 'легница' && $dec = 1) {
            return 'Легницы';
        }

        $vars = self::$declination->q($str);

        return isset($vars[$dec]) ? $vars[$dec] : $str;
    }


    private static function setupDeclination()
    {
        if (!self::$hasDeclinationLib) {
            self::$hasDeclinationLib = true;
            if (file_exists(__DIR__ . '/declination/NCLNameCaseRu.php')) {
                require_once __DIR__ . '/declination/NCLNameCaseRu.php';
                self::$declination = new NCLNameCaseRu;
            }
        }
    }
}
