<?php

namespace Vnetby\Wptheme\Traits;

trait CacheObject
{
    private $__obCache = [];

    protected function hasObCache(string $key): bool
    {
        return array_key_exists($key, $this->__obCache);
    }

    protected function getObCache(string $key)
    {
        return $this->__obCache[$key];
    }

    protected function setObCache(string $key, $value)
    {
        $this->__obCache[$key] = $value;
    }


    protected function fetchObCache(string $key, callable $fn)
    {
        if (!$this->hasObCache($key)) {
            $this->setObCache($key, $fn());
        }
        return $this->getObCache($key);
    }
}
