<?php

namespace Vnetby\Wptheme\Cache;

use Vnetby\Wptheme\Cache\Providers\CacheProvider;
use Vnetby\Wptheme\Traits\CacheObject;

class Cache
{
    private CacheProvider $provider;

    use CacheObject;

    function __construct(CacheProvider $provider)
    {
        $this->provider = $provider;
    }


    function fetch(string $key, callable $fn, int $ttl = 0)
    {
        if ($this->hasObCache($key)) {
            return $this->getObCache($key);
        }

        if ($this->provider->has($key, $ttl)) {
            $cache = $this->provider->get($key);
        } else {
            $cache = $fn();
        }

        $this->setObCache($key, $cache);

        if ($ttl > 0) {
            $this->provider->set($key, $cache);
        }

        return $cache;
    }


    function flush()
    {
        $this->provider->flush();
    }
}
