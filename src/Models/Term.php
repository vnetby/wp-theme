<?php

namespace Vnetby\Wptheme\Models;

class Term extends Model
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
        if (array_key_exists($id, static::$cacheTerms)) {
            return static::$cacheTerms[$id];
        }

        static::$cacheTerms[$id] = null;

        $wpdb = static::getWpDb();
        $table = $wpdb->term_taxonomy;

        $data = $wpdb->get_results("SELECT `taxonomy` FROM `${table}` WHERE `term_id` = {$id} LIMIT 1", ARRAY_A);

        if ($data && !is_wp_error($data)) {
            $tax = $data[0]['taxonomy'];

            $res = get_term($id, $tax);

            static::$cacheTerms[$id] = $res ? static::getByWpItem($res) : null;
        }

        return static::$cacheTerms[$id];
    }
}
