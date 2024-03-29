<?php

namespace Vnetby\Wptheme\Entities\Base;

use Vnetby\Wptheme\Entities\Admin\AdminTaxonomy;


abstract class EntityTaxonomy extends Entity
{

    const CLASS_ADMIN = AdminTaxonomy::class;


    /**
     * @param AdminTaxonomy $admin
     */
    static function setup($admin)
    {
        parent::setup($admin);
    }

    /**
     * @return AdminTaxonomy
     */
    static function getAdmin()
    {
        return parent::getAdmin();
    }

    static function filter(array $filter = [], int $page = 1, int $perPage = -1)
    {
        return static::fetchCache(function () use ($filter, $page, $perPage) {
            $filter['taxonomy'] = static::getKey();
            if ($perPage > 0) {
                $filter['number'] = $perPage;
                $filter['offset'] = $perPage * $page - $perPage;
            } else {
                if (isset($filter['number'])) {
                    unset($filter['number']);
                }
                if (isset($filter['offset'])) {
                    unset($filter['offset']);
                }
            }
            $query = new \WP_Term_Query($filter);
            $res = [];
            if ($query->terms) {
                foreach ($query->terms as $term) {
                    $res[] = static::getByWpItem($term);
                }
            }
            return new DbResult($res, $page, $perPage, -1);
        });
    }


    /**
     * - Получает элемент по slug
     * @param string $slug 
     * @return null|static
     */
    static function getBySlug(string $slug)
    {
        return self::fetchCache(function () use ($slug) {
            $term = get_term_by('slug', esc_sql($slug), static::getKey());
            return $term ? static::getByWpItem($term) : null;
        });
    }
}
