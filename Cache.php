<?php

namespace Vnet;

class Cache
{

    private static $cache = [];

    private static $prefix = '';


    /**
     * @param string $key 
     * @param mixed $def 
     * @return mixed 
     */
    static function get(string $key, $def = null)
    {
        if (self::has($key)) {
            return self::$cache[self::getCacheKey($key)];
        }
        return $def;
    }


    /**
     * @param string $key 
     * @param mixed $value любое значение
     * @return void 
     */
    static function set(string $key, $value)
    {
        self::$cache[self::getCacheKey($key)] = $value;
    }


    /**
     * - Проверяет есть зи значение в кэше
     * @param string $key 
     * @return bool 
     */
    static function has(string $key): bool
    {
        return array_key_exists(self::getCacheKey($key), self::$cache);
    }


    /**
     * - Получает значение из кэша
     * - Если его нет - вызовет функцию $fetchFunction для получения значения
     * @param string $key 
     * @param callable $fetchFunction должна вернуть значение
     * @return mixed 
     */
    static function fetch(string $key, callable $fetchFunction)
    {
        if (self::has($key)) {
            return self::get($key);
        }

        $cache = call_user_func($fetchFunction);

        self::set($key, $cache);

        return $cache;
    }


    private static function getCacheKey($key): string
    {
        $prefix = self::$prefix ? self::$prefix : $_SERVER['HTTP_HOST'] ?? '';
        return $prefix ? $prefix . ':' . $key : $key;
    }
}
