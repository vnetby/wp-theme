<?php

namespace Vnetby\Wptheme\Models;

use Vnetby\Helpers\HelperDate;
use Vnetby\Wptheme\Container;
use WP_Query;

class ModelPostType extends Model
{

    /**
     * - массив привязанных терминов таксономий
     *
     * @var array<string,Term[]>
     */
    protected array $terms = [];


    static function getCurrent()
    {
        global $post;
        if (!$post || (!is_singular() && !is_front_page())) {
            return null;
        }
        return static::getByWpItem($post);
    }


    static function getById(int $id)
    {
        return self::fetchCache('getById:' . $id, function () use ($id) {
            $post = get_post($id);
            return $post && !is_wp_error($post) ? static::getByWpItem($post) : null;
        });
    }


    static function getPublished(int $page = 1, int $perPage = -1, ?array $queryArgs = null): DbResult
    {
        $args = [
            'post_status' => 'publish'
        ];

        if ($queryArgs) {
            $args = array_merge($args, $queryArgs);
        }

        return static::filter($args, $page, $perPage);
    }


    static function filter(array $queryArgs = [], int $page = 1, int $perPage = -1): DbResult
    {
        $queryArgs['post_type'] = static::getKey();
        $queryArgs['paged'] = $page;
        $queryArgs['posts_per_page'] = $perPage;

        return static::fetchCache(serialize($queryArgs), function () use ($queryArgs, $page, $perPage) {
            $query = new WP_Query($queryArgs);

            $res = [];

            foreach ($query->posts as $post) {
                $res[] = new static($post);
            }

            return new DbResult($res, $page, $perPage, $query->found_posts);
        });
    }


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
}
