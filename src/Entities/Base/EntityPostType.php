<?php

namespace Vnetby\Wptheme\Entities\Base;

use Vnetby\Helpers\HelperDate;
use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Entities\Admin\AdminPostType;
use WP_Query;

abstract class EntityPostType extends Entity
{

    const CLASS_ADMIN = AdminPostType::class;

    /**
     * @param AdminPostType $admin
     */
    static function setup($admin)
    {
        parent::setup($admin);
    }

    /**
     * @return AdminPostType
     */
    static function getAdmin()
    {
        return parent::getAdmin();
    }

    /**
     * - Получает посты со статусом "published"
     * @return DbResult<static>
     */
    static function getPublished(int $page = 1, int $perPage = -1, ?array $queryArgs = null)
    {
        $args = [
            'post_status' => 'publish'
        ];

        if ($queryArgs) {
            $args = array_merge($args, $queryArgs);
        }

        return static::filter($args, $page, $perPage);
    }

    static function filter(array $filter = [], int $page = 1, int $perPage = -1)
    {
        return static::fetchCache(function () use ($filter, $page, $perPage) {
            $filter['post_type'] = static::getKey();
            $filter['paged'] = $page;
            $filter['posts_per_page'] = $perPage;

            $query = new WP_Query($filter);

            $res = [];

            foreach ($query->posts as $post) {
                $res[] = static::getByWpItem($post);
            }

            return new DbResult($res, $page, $perPage, $query->found_posts);
        });
    }

    static function urlArchive(): string
    {
        $url = get_post_type_archive_link(static::getKey());
        return $url ? $url : '';
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
