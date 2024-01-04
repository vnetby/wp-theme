<?php

namespace Vnet\Entities;

use Vnet\Ajax;
use Vnet\Constants\PostTypes;
use WP_Query;

class PostBlog extends Post
{

    protected static $postType = PostTypes::BLOG;


    /**
     * @return self[] 
     */
    static function getFrontPosts(): array
    {
        $query = new WP_Query([
            'post_type' => 'blog',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'order' => 'DESC',
            'orderby' => 'date'
        ]);

        $res = [];

        foreach ($query->posts as $post) {
            $res[] = new self($post);
        }

        return $res;
    }
}
