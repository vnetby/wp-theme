<?php

namespace Vnet\Theme;

use Vnet\Helpers\Acf;

class ArchiveOptions
{
    const KEY_TOURS = 'archive_tours';
    const KEY_BLOG = 'archive_blog';

    static function setup()
    {
        if (!function_exists('acf_add_options_page')) {
            return;
        }

        acf_add_options_page([
            'page_title' => 'Архивы',
            'menu_title' => 'Архивы',
            'menu_slug' => 'archive-options'
        ]);
    }


    static function getTours(): array
    {
        return self::getField(self::KEY_TOURS);
    }


    static function getBlog(): array
    {
        return self::getField(self::KEY_BLOG);
    }


    private static function getField($key): array
    {
        $res = Acf::getField($key, 'option');
        return $res ? $res : [];
    }
}
