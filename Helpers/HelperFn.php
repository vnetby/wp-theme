<?php

namespace Vnet\Helpers;

use ReflectionFunction;

class HelperFn
{

    /**
     * - Вызывает функцию с передачей параметров
     */
    static function execCallback(callable $callback, ...$args)
    {
        $ref = new ReflectionFunction($callback);
        $callbackArgs = [];
        $params = $ref->getParameters();
        foreach ($params as $i => $param) {
            if (array_key_exists($i, $args)) {
                $callbackArgs[$i] = $args[$i];
            }
        }
        return call_user_func($callback, ...$callbackArgs);
    }
}
