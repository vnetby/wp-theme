<?php

namespace Vnetby\Wptheme\Traits\Config;

use Vnetby\Wptheme\Cache\Cache;
use Vnetby\Wptheme\Cache\Providers\CacheFile;
use Vnetby\Wptheme\Cache\Providers\CacheProvider;


trait ConfigCache
{
    protected Cache $cache;
    protected CacheProvider $cacheProvider;
    private bool $__cacheEnabled = true;


    function registerCache()
    {
        if (!isset($this->cacheProvider)) {
            $this->cacheProvider = new CacheFile;
        }
        $this->cache = new Cache($this->cacheProvider);
    }


    /**
     * @return static
     */
    function enableCache()
    {
        $this->__cacheEnabled = true;
        return $this;
    }


    /**
     * @return static
     */
    function disableCache()
    {
        $this->__cacheEnabled = false;
        return $this;
    }


    /**
     * @param CacheProvider $provider
     * @return static
     */
    function setCacheProvider(CacheProvider $provider)
    {
        $this->cacheProvider = $provider;
    }

    function getCache(): Cache
    {
        return $this->cache;
    }

    function fetchCache(string $key, callable $fn, int $ttl = 0)
    {
        return $this->__cacheEnabled ? $this->cache->fetch($key, $fn, $ttl) : $fn();
    }
}
