<?php

namespace Vnetby\Wptheme;

use Vnetby\Wptheme\Front\Template;

class Seo
{

    /**
     * - Добавляет мета бокс с сео данными на каждом элементе типа поста
     */
    static function addPostsSeo()
    {
        add_action('add_meta_boxes', function () {
            add_meta_box('vnet_seo_metabox', __('SEO', 'vnet'), function ($post, $meta) {
                Template::theFile(Container::getLoader()->libPath('templates/seo-metabox.php'), ['post' => $post]);
            });
        });

        add_action('save_post', function ($postId) {
            $title = $_REQUEST['vnet-seo-title'] ?? '';
            $desc = $_REQUEST['vnet-seo-desc'] ?? '';
            $image = $_REQUEST['vnet-seo-image'] ?? '';
            update_post_meta($postId, 'vnet_seo', serialize(['title' =>  $title, 'desc' => $desc, 'image' => $image]));
        });
    }


    static function getPostTitle(int $postId): string
    {
        $data = self::getPostSeoMeta($postId);
        if (!empty($data['title'])) {
            return $data['title'];
        }
        return get_the_title($postId);
    }

    static function getPostDesc(int $postId): string
    {
        $data = self::getPostSeoMeta($postId);
        if (!empty($data['desc'])) {
            return $data['desc'];
        }
        return get_the_excerpt($postId);
    }

    static function getPostImageId(int $postId): int
    {
        $data = self::getPostSeoMeta($postId);
        if (!empty($data['image'])) {
            return (int)$data['image'];
        }
        if ($img = get_post_thumbnail_id($postId)) {
            return $img;
        }
        return 0;
    }

    static function getPostImage(int $postId): string
    {
        $imgId = self::getPostImageId($postId);
        if (!$imgId) {
            return '';
        }
        return wp_get_attachment_image_url($imgId, 'full');
    }


    static function getPostSeoMeta(int $postId): array
    {
        $data = get_post_meta($postId, 'vnet_seo', true);
        return $data ? unserialize($data) : [];
    }
}
