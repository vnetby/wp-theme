<?php

namespace Vnetby\Wptheme;

use Vnetby\Helpers\HelperArr;

class Router
{

    static function the404()
    {
        global $wp_query;

        $wp_query->set_404();

        status_header(404);

        get_template_part(404);

        exit;
    }

    static function redirect301(string $location)
    {
        wp_redirect($location, 301);
        exit;
    }

    static function redirect302(string $location)
    {
        wp_redirect($location, 302);
        exit;
    }

    /**
     * - Получает текущий путь запроса
     * - Без закрывающего /
     * @return string 
     */
    static function getCurrentPath(): string
    {
        $path = preg_replace("/\?.*$/", '', $_SERVER['REQUEST_URI']);
        $path = preg_replace("/\/$/", '', $path);
        return $path;
    }

    static function getCurrentUrl(): string
    {
        $url = HelperArr::getServer('REQUEST_URI', '');
        return site_url($url);
    }
}
