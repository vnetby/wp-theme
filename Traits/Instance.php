<?php

/**
 * - Полуение экземпляра класса через статический метод getInstance()
 */

namespace Vnet\Traits;


trait Instance
{

    /**
     * @var static[]
     */
    private static $instance = [];


    protected function __construct()
    {
    }


    /**
     * @return static 
     */
    final static function getInstance()
    {
        $class = get_called_class();
        if (!isset(self::$instance[$class])) {
            self::$instance[$class] = new static();
        }
        return self::$instance[$class];
    }
}
