<?php

namespace Vnetby\Wptheme\Traits\Config;

use Vnetby\Wptheme\Cache\Cache;
use Vnetby\Wptheme\Cache\Providers\CacheFile;
use Vnetby\Wptheme\Cache\Providers\CacheProvider;


trait ConfigCache
{
    protected Cache $cache;
    protected CacheProvider $cacheProvider;


    function registerCache()
    {
        if (!isset($this->cacheProvider)) {
            $this->cacheProvider = new CacheFile;
        }
        $this->cache = new Cache($this->cacheProvider);
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
        return $this->cache->fetch($key, $fn, $ttl);
    }
}
