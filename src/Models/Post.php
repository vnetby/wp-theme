<?php

namespace Vnetby\Wptheme\Models;

use Vnetby\Wptheme\Container;
use WP_Query;

class Post extends Model
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
        if (array_key_exists($id, static::$cachePosts)) {
            return static::$cachePosts[$id];
        }

        $post = get_post($id);

        static::$cachePosts[$id] = $post && !is_wp_error($post) ? static::getByWpItem($post) : null;

        return static::$cachePosts[$id];
    }


    static function getPublished(int $page = 1, int $perPage = -1, ?array $queryArgs = null): DbResult
    {
        $args = [
            'post_type' => static::getKey(),
            'posts_per_page' => $perPage,
            'paged' => $page,
            'post_status' => 'publish'
        ];

        if ($queryArgs) {
            $args = array_merge($args, $queryArgs);
        }

        $cacheKey = md5('get_posts:' . serialize($args));

        return Container::getLoader()->fetchCache($cacheKey, function () use ($args, $page, $perPage) {
            $query = new WP_Query($args);

            $res = [];

            foreach ($query->posts as $post) {
                $res[] = new static($post);
            }

            return new DbResult($res, $page, $perPage, $query->found_posts);
        }, 3600);
    }


    function getExcerpt(): string
    {
        return $this->wpItem->post_excerpt;
    }
}
