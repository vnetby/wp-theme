<?php

/**
 * - Класс для работы с UTM метками
 */

namespace Vnet\Theme;

use Vnet\Traits\Instance;

class Utm
{
    use Instance;

    const SESSION_KEY = 'UTM_DATA';

    private $utmSource = '';
    private $utmMedium = '';
    private $utmCampaign = '';
    private $utmContent = '';
    private $utmTerm = '';

    private function __construct()
    {
        if ($this->getRequest()) {
            $this->fillFromRequest();
        } else {
            $this->fillFromSession();
        }
        $this->saveSession();
    }


    private function getSession(): array
    {
        if (!empty($_COOKIE[self::SESSION_KEY])) {
            $res = $this->unserialize($_COOKIE[self::SESSION_KEY]);
            return !empty($res['utm_source']) ? $res : [];
        }
        return [];
    }


    private function saveSession()
    {
        setcookie(self::SESSION_KEY, $this->serialize(), time() + (10 * 365 * 24 * 60 * 60), '/');
    }


    private function fillFromSession()
    {
        $this->fillObject($this->getSession());
    }


    private function fillFromRequest()
    {
        $data = $_GET;
        if (!isset($data['utm_source']) && !empty($_SERVER['HTTP_REFERER']) && $this->isCurrentDomain($_SERVER['HTTP_REFERER'])) {
            $urlData = parse_url($_SERVER['HTTP_REFERER']);
            parse_str($urlData['query'], $res);
            $data = $res;
        }
        $this->fillObject($data);
    }


    private function getRequest(): array
    {
        $data = $_GET;
        if (!isset($data['utm_source']) && !empty($_SERVER['HTTP_REFERER']) && $this->isCurrentDomain($_SERVER['HTTP_REFERER'])) {
            $urlData = parse_url($_SERVER['HTTP_REFERER']);
            $res = [];
            if (!empty($urlData['query'])) {
                parse_str($urlData['query'], $res);
            }
            $data = $res;
        }
        return !empty($data['utm_source']) ? $data : [];
    }


    private function isCurrentDomain(string $url): bool
    {
        if (!preg_match("%^https?://[^/]+%", $url)) {
            return true;
        }
        preg_match("%^https?://([^/]+)%", $url, $urlMatches);
        if (empty($urlMatches[1])) {
            return false;
        }
        return $urlMatches[1] === $_SERVER['HTTP_HOST'];
    }


    private function getKeys(): array
    {
        $keys = [
            'utm_source' => 'utmSource',
            'utm_medium' => 'utmMedium',
            'utm_campaign' => 'utmCampaign',
            'utm_content' => 'utmContent',
            'utm_term' => 'utmTerm'
        ];
        return $keys;
    }


    private function fillObject(array $data)
    {
        $keys = $this->getKeys();

        foreach ($keys as $key => $objKey) {
            if (!$keys[$key]) {
                continue;
            }
            $this->{$objKey} = $data[$key] ?? '';
        }
    }


    private function serialize(): string
    {
        return base64_encode(serialize($this->toArray()));
    }


    private function unserialize(string $data): array
    {
        return unserialize(base64_decode($data));
    }


    function toArray(): array
    {
        $keys = $this->getKeys();
        $res = [];
        foreach ($keys as $key => $objKey) {
            $res[$key] = $this->{$objKey};
        }
        return $res;
    }


    function toUpperArray(): array
    {
        $data = $this->toArray();
        $res = [];
        foreach ($data as $key => $val) {
            $res[strtoupper($key)] = $val;
        }
        return $res;
    }


    /**
     * - Добавляет utm метки к урл
     */
    function url(string $url): string
    {
        $str = '?';
        if (preg_match("/\?/", $url)) {
            $str = '&';
        }
        $data = $this->toArray();
        $url .= $str . http_build_query($data);
        return $url;
    }
}
