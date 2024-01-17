<?php

namespace Vnetby\Wptheme\Entities;

class EntityPage extends Base\EntityPostType
{
    const KEY = 'page';

    /**
     * - Получает страницу котороя используется на фронте
     * @return ?static
     */
    static function getFrontPage()
    {
        return static::fetchCache(function () {
            $id = (int)get_option('page_on_front', 0);
            return $id ? static::getById($id) : null;
        });
    }
}
