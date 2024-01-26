<?php

/**
 * - Добавляет возможность кэшировать методы в классе
 */

namespace Vnetby\Wptheme\Traits;

use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Entities\Base\Entity;

trait CacheClass
{
    protected static int $ttlCache = 0;

    /**
     * - Получает данные из кэша если они есть, в противном случае вызывает переданную функцию
     * - Уникальный ключ кэша формируется автоматичкски из следующих частей:
     *      - класс в котором вызван метод (static::class)
     *      - название метода, в котром вызван данный метод
     *      - $keySuffix, или хэш md5 серилизированных аргументов метода, в котором вызван данный метод
     *      - ID элемента, если данный метод был вызван в контесте объекта сущности
     * - В качестве разделителя частей ключа кэша используется знак :
     * - Объектное кэширование отработает вне зависимости от параметра $ttl
     * - Если в качестве аргументов метода, в котором вызван данный метод,
     *      переданы параметры которые нельзя серилизовать, например SimpleXMLElement,
     *      либо переданные аргументы являются объектами/массивами большой вложенности,
     *      необходимо вручную передать $keySuffix
     * @param callable $fn - функция которая отработает в случае если кэша нет
     * @param string $keySuffix - доподнительная часть ключа кэша
     *      если не передать, в качестве такого ключа, будут использованы
     *      аргументы метода, в контекстве которого был вызван данный метод
     * @param integer|null $ttl время жизни кэша, по умолчанию static::$ttlCache
     */
    protected static function fetchCache(callable $fn, $keySuffix = '', ?int $ttl = null)
    {
        $debug = debug_backtrace();

        $callFrom = $debug[1];

        if (!$keySuffix) {
            $args = $callFrom['args'] ?? [];
            if ($args) {
                $keySuffix = md5(serialize($args));
            }
        }

        $class = get_called_class();

        $fullKey = $class . ':' . $callFrom['function'];

        if ($keySuffix) {
            $fullKey .= ':' . $keySuffix;
        }

        // для объектов сущностей добавляем ID элемента
        if ($callFrom['type'] == '->' && isset($callFrom['object']) && $callFrom['object'] instanceof Entity) {
            $fullKey .= ':' . $callFrom['object']->getId();
        }

        if ($ttl === null) {
            $ttl = self::$ttlCache;
        }

        return Container::getLoader()->fetchCache($fullKey, $fn, $ttl);
    }
}
