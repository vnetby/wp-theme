<?php

namespace Vnetby\Wptheme\Models;

class ModelTaxonomy extends Model
{

    static function getCurrent()
    {
        $obj = get_queried_object();
        if (!$obj) {
            return null;
        }
        return static::getByWpItem($obj);
    }


    static function getById(int $id)
    {
        return self::fetchCache('getById:' . $id, function () use ($id) {
            $wpdb = static::getWpDb();
            $table = $wpdb->term_taxonomy;

            $data = $wpdb->get_results("SELECT `taxonomy` FROM `${table}` WHERE `term_id` = {$id} LIMIT 1", ARRAY_A);

            if ($data && !is_wp_error($data)) {
                $tax = $data[0]['taxonomy'];

                $res = get_term($id, $tax);

                return $res ? static::getByWpItem($res) : null;
            }

            return null;
        });
    }


    static function isSingular(): bool
    {
        return is_tax(static::getKey());
    }


    /**
     * - Получает элемент по slug
     * @param string $slug 
     * @return null|static
     */
    static function getBySlug(string $slug): ?self
    {
        return static::fetchCache('getBySlug:' . $slug, function () use ($slug) {
            $term = get_term_by('slug', esc_sql($slug), static::getKey());
            if (!$term) {
                return null;
            }
            return static::getByWpItem($term);
        });
    }
}
