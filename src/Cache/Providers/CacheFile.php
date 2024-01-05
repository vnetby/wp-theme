<?php

namespace Vnetby\Wptheme\Cache\Providers;

use SimpleXMLElement;
use Vnetby\Helpers\HelperPath;
use Vnetby\Wptheme\Container;


class CacheFile extends CacheProvider
{
    const TYPE_XML = 'xml';
    const TYPE_SERIALIZE = 'serialize';
    const DIR_CACHE = 'cache-file';


    function get(string $key)
    {
        $cacheFile = $this->getCacheFile($key);
        $content = unserialize(file_get_contents($cacheFile));

        if ($content['type'] === static::TYPE_XML) {
            $data = simplexml_load_string($content['data']);
        } else {
            $data = $content['data'];
        }

        return $data;
    }


    function has(string $key, int $ttl = 0): bool
    {
        if ($ttl === 0) {
            return false;
        }

        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return false;
        }

        return time() - filemtime($file) < $ttl;
    }


    function set(string $key, $value)
    {
        $file = $this->getCacheFile($key);
        $dir = dirname($file);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        if ($value instanceof SimpleXMLElement) {
            $data = [
                'type' => self::TYPE_XML,
                'data' => $value->asXML()
            ];
        } else {
            $data = [
                'type' => self::TYPE_SERIALIZE,
                'data' => $value
            ];
        }

        $data = serialize($data);

        file_put_contents($file, $data);
    }


    function delete(string $key)
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }


    function flush()
    {
        $dir = HelperPath::join(Container::getLoader()->getPathCache(true), static::DIR_CACHE);
        @exec("rm -rf {$dir}");
    }


    protected function getCacheFile(string $key): string
    {
        if (!$this->isMd5($key)) {
            $key = md5($key);
        }

        $dir1 = substr($key, 0, 2);
        $dir2 = substr($key, 2, 3);
        $fileName = substr($key, 5);

        return HelperPath::join(Container::getLoader()->getPathCache(true), static::DIR_CACHE, $dir1, $dir2, $fileName);
    }


    private function isMd5(string $str): bool
    {
        return preg_match('/^[a-f0-9]{32}$/', $str);
    }
}
