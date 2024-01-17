<?php

namespace Vnetby\Wptheme\Entities\Admin;

use Error;
use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Entities\Base\Entity;

abstract class Admin
{

    /**
     * - Класс сущности к которой относится объект
     * @var class-string<Entity>
     */
    private string $classEntity;

    /**
     * - Объект зарегестрированной сущности в wordpress
     */
    protected $wpItem;

    /**
     * - Параметры для передачи в функцию wordpress регистрации сущности
     * @param array<string,mixed>
     */
    protected array $params = [];


    function __construct(string $classEntity)
    {
        $this->classEntity = $classEntity;
    }


    /**
     * - Регистрирует сущность в wordpress
     * - Вызывается в сущности
     * - Устанавливает $this->wpItem
     * @return static
     */
    abstract protected function register(array $params = []);


    function __call($name, $arguments)
    {
        if (preg_match("/^set/", $name)) {
            $param = $this->methodToParam(preg_replace("/^set/", '', $name));
            $this->params[$param] = $arguments[0];
            return $this;
        }
        if (preg_match("/^get/", $name)) {
            $param = $this->methodToParam(preg_replace("/^get/", '', $name));
            if (!isset($this->wpItem)) {
                throw new Error("Cannot get parameter {$param} before initialization");
            }
            if (!property_exists($this->wpItem, $param)) {
                throw new Error("Trying to get undefined wordpress property {$param}");
            }
            return $this->wpItem->{$param};
        }
        throw new Error("Undefined method {$name}");
    }

    /**
     * - Конвертирует метод set/get в ключ массива $this->params
     * @param string $name
     * @return string
     */
    protected function methodToParam(string $name): string
    {
        $res = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        return $res;
    }

    protected function getKey(): string
    {
        return $this->classEntity::KEY;
    }

    protected function theAdminStyle(string $css)
    {
        add_action('admin_head', function () use ($css) {
            $this->theStyle($css);
        });
        return $this;
    }

    protected function theAdminScript(string $js, $onLoad = false)
    {
        add_action('admin_footer', function () use ($js, $onLoad) {
            if ($onLoad) {
                $this->theScriptOnLoad($js);
            } else {
                $this->theScript($js);
            }
        });
        return $this;
    }

    protected function theStyle(string $css)
    {
        echo '<style>' . $css . '</style>';
        return $this;
    }

    protected function theScript(string $js)
    {
        echo '<script>' . $js . '</script>';
        return $this;
    }

    protected function theScriptOnLoad(string $js)
    {
        echo '<script> window.addEventListener("DOMContentLoaded", function () { ' . $js . ' }); </script>';
        return $this;
    }

    /**
     * - Добавляет класс к пункту меню
     * @param string[] $class 
     */
    protected function addMenuClass(...$class)
    {
        add_action('admin_init', function () use ($class) {
            global $menu;

            if (!$menu) {
                return;
            }

            foreach ($menu as &$item) {
                if (!isset($item[5])) {
                    continue;
                }

                if ($item[5] !== 'menu-posts-' . $this->getKey()) {
                    continue;
                }

                $item[4] = implode(' ', array_merge(explode(' ', $item[4]), $class));

                break;
            }
        });
        return $this;
    }

    protected function fetchCache(callable $fn, string $key = '', int $ttl = 0)
    {
        $callFrom = debug_backtrace()[1]['function'] ?? '';
        $fullKey = get_called_class() . ':' . $callFrom;
        if ($key) {
            $fullKey .= ':' . $key;
        }
        return Container::getLoader()->fetchCache($fullKey, $fn, $ttl);
    }
}
