<?php

namespace Vnetby\Wptheme\Cache\Providers;

abstract class CacheProvider
{
    abstract function get(string $key);

    abstract function has(string $key, int $ttl = 0): bool;

    abstract function set(string $key, $value);

    abstract function delete(string $key);

    abstract function flush();
}
