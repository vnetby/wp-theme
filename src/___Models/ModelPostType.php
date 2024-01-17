<?php

namespace Vnetby\Wptheme\Models;

use Vnetby\Helpers\HelperDate;
use Vnetby\Wptheme\Container;


class ModelPostType extends Model
{

    /**
     * - массив привязанных терминов таксономий
     *
     * @var array<string,Term[]>
     */
    protected array $terms = [];


    function getExcerpt(): string
    {
        return $this->wpItem->post_excerpt;
    }


    function getImage($size = 'thumbnail', $icon = false): string
    {
        if ($img = get_post_thumbnail_id($this->getWpItem())) {
            $src = wp_get_attachment_image_url($img, $size, $icon);
            return $src ? $src : '';
        }
        return '';
    }


    function getPostDate(?string $format = null): string
    {
        if (!$format) {
            $format = Container::getLoader()->getDateTimeFormat();
        }

        $date = $this->wpItem->post_date;

        return HelperDate::format($format, $date);
    }


    function isPublish(): bool
    {
        return $this->getField('post_status') === 'publish';
    }


    function isFrontPage(): bool
    {
        $id = (int)get_option('page_on_front', 0);
        if (!$id) {
            return false;
        }
        return $id === $this->getId();
    }
}
