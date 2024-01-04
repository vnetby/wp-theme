<?php

namespace Vnet\Helpers;


class ArrayHelper
{

    static function get(array $arr, $key, $def = null)
    {
        if (!is_array($arr)) {
            return $def;
        }

        if (!is_array($key)) {
            if (!isset($arr[$key])) {
                return $def;
            }
            return $arr[$key];
        }

        $prev_arr = $arr;

        foreach ($key as $_key) {
            if (!isset($prev_arr[$_key])) {
                return $def;
            }
            $prev_arr = $prev_arr[$_key];
        }

        return $prev_arr;
    }


    static function getPost($key, $def = false)
    {
        return self::get($_POST, $key, $def);
    }


    static function getGet($key, $def = false)
    {
        return self::get($_GET, $key, $def);
    }


    static function getRequest($key, $def = false)
    {
        return self::get($_REQUEST, $key, $def);
    }


    static function getServer($key, $def = false)
    {
        return self::get($_SERVER, $key, $def);
    }


    /**
     * - Вставляет в массив значение на позици после $afterKey
     */
    static function insert(array $arr, string $afterKey, array $mergeArray)
    {
        $index = array_search($afterKey, array_keys($arr));
        $before = array_slice($arr, 0, $index + 1, true);
        $before = array_merge($before, $mergeArray);
        return array_merge($before, array_slice($arr, $index, null, true));
    }
}
