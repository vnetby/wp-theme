<?php

namespace Vnetby\Wptheme\Models;

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


    function getExcerpt(): string
    {
        return $this->wpItem->post_excerpt;
    }
}
