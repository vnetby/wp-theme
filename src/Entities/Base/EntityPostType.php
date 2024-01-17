<?php

namespace Vnetby\Wptheme\Entities\Base;

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
        $filter['post_type'] = static::getKey();
        $filter['paged'] = $page;
        $filter['posts_per_page'] = $perPage;

        return static::fetchCache(function () use ($filter, $page, $perPage) {
            $query = new WP_Query($filter);

            $res = [];

            foreach ($query->posts as $post) {
                $res[] = static::getByWpItem($post);
            }

            return new DbResult($res, $page, $perPage, $query->found_posts);
        }, serialize($filter));
    }


    static function urlArchive(): string
    {
        $url = get_post_type_archive_link(static::getKey());
        return $url ? $url : '';
    }
}
