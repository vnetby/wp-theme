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
        add_action('add_meta_boxes', function ($query, \WP_Post $post) {
            $postType = get_post_type_object($post->post_type);

            // тип поста не имеет публичной части
            // значит сео нет смысла выводить
            if (!$postType->publicly_queryable) {
                return;
            }

            add_meta_box('vnet_seo_metabox', __('SEO', 'vnet'), function (\WP_Post $post, $meta) {
                Template::theFile(Container::getLoader()->libPath('templates/seo-metabox.php'), [
                    'title' => self::getPostTitle($post->ID),
                    'desc' => self::getPostDesc($post->ID),
                    'image' => self::getPostImageId($post->ID),
                    'name_title' => 'vnet-seo-title',
                    'name_desc' => 'vnet-seo-desc',
                    'name_image' => 'vnet-seo-image'
                ]);
            });
        }, 10, 2);

        add_action('admin_menu', function () {
            add_options_page(__('Настройки СЕО', 'vnet'), __('СЕО', 'vnet'), 'manage_options', 'vnet-seo-options', function () {
                Template::theFile(Container::getLoader()->libPath('templates/seo-options.php'));
            });
        });

        add_action('save_post', function ($postId) {
            $title = $_REQUEST['vnet-seo-title'] ?? '';
            $desc = $_REQUEST['vnet-seo-desc'] ?? '';
            $image = $_REQUEST['vnet-seo-image'] ?? '';
            update_post_meta($postId, 'vnet_seo', serialize(['title' =>  $title, 'desc' => $desc, 'image' => $image]));
        });
    }


    static function saveOptionsFromRequest()
    {
        $postTypes = self::getPostTypesWithArchives();
        $data = [
            'archiveCommonTitle' => $_REQUEST['vnet-seo-common-archive-title'] ?? '',
            'archiveCommonDesc' => $_REQUEST['vnet-seo-common-archive-desc'] ?? '',
            'commonImage' => (int)($_REQUEST['vnet-seo-common-image'] ?? 0)
        ];
        foreach ($postTypes as $postType) {
            $data['post_type_' . $postType->name] = [
                'title' => $_REQUEST['vnet-seo-post-' . $postType->name . '-title'],
                'desc' => $_REQUEST['vnet-seo-post-' . $postType->name . '-desc'],
                'image' => (int)($_REQUEST['vnet-seo-post-' . $postType->name . '-image'] ?? 0)
            ];
        }
        update_option('vnet_seo_options', serialize($data));
    }


    static function getOptions(): array
    {
        $data = get_option('vnet_seo_options');
        return $data ? unserialize($data) : [];
    }

    static function getPostTypeOptions(string $postType): array
    {
        return self::getOptions()['post_type_' . $postType] ?? [];
    }

    static function getOptionArchiveTitle(string $postType): string
    {
        return self::getPostTypeOptions($postType)['title'] ?? '';
    }

    static function getOptionArchiveDesc(string $postType): string
    {
        return self::getPostTypeOptions($postType)['desc'] ?? '';
    }

    static function getOptionArchiveImageId(string $postType): int
    {
        return (int)(self::getPostTypeOptions($postType)['image'] ?? 0);
    }

    static function getOptionCommonArchiveTitle(): string
    {
        return self::getOptions()['archiveCommonTitle'] ?? '';
    }

    static function getOptionCommonArchiveDesc(): string
    {
        return self::getOptions()['archiveCommonDesc'] ?? '';
    }

    static function getOptionCommonImageId(): int
    {
        return (int)(self::getOptions()['commonImage'] ?? 0);
    }


    /**
     * - Получает типы постов с архивами
     *
     * @return \WP_Post_Type[]
     */
    static function getPostTypesWithArchives(): array
    {
        $types = get_post_types();
        $res = [];
        foreach ($types as $type) {
            $postType = get_post_type_object($type);
            if (!$postType) {
                continue;
            }
            if ($postType->has_archive && $postType->publicly_queryable) {
                $res[] = $postType;
            }
        }
        return $res;
    }


    static function getArchiveTitle(string $postType): string
    {
        if ($title = self::getOptionArchiveTitle($postType)) {
            return $title;
        }
        return self::getOptionCommonArchiveTitle();
    }

    static function getArchiveDesc(string $postType): string
    {
        if ($desc = self::getOptionArchiveDesc($postType)) {
            return $desc;
        }
        return self::getOptionCommonArchiveDesc();
    }

    static function getArchiveImageId(string $postType): int
    {
        if ($img = self::getArchiveImageId($postType)) {
            return $img;
        }
        return self::getOptionCommonImageId();
    }

    static function getArchiveImage(string $postType): string
    {
        if ($img = self::getArchiveImageId($postType)) {
            return wp_get_attachment_image_url($img, 'full');
        }
        return '';
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
        return self::getOptionCommonImageId();
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
