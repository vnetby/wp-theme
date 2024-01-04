<?php

namespace Vnet\Entities;

use Vnet\Constants\Cache;
use Vnet\Constants\Taxonomies;

class TermCity extends Term
{
    protected static $tax = Taxonomies::LOC_CITIES;


    function getArchiveContent(): ?array
    {
        if ($data = $this->getField('archive_tour_content')) {
            return $data;
        }
        return null;
    }


    /**
     * - Получает места сбора привязанные к данному городу
     * @return TermPlace[]
     */
    function getPlaces(): array
    {
        $ids = $this->getPlacesIds();
        $res = [];

        foreach ($ids as $termId) {
            $res[] = TermPlace::getById($termId);
        }

        return $res;
    }


    function getPlacesIds(): array
    {
        return $this->fetchCache(Cache::TERM_CITY_PLACES, function () {
            global $wpdb;

            $tableMeta = $wpdb->termmeta;
            $tableTax = $wpdb->term_taxonomy;

            $id = $this->getId();
            $tax = Taxonomies::LOC_PLACES;

            $placesIds = $wpdb->get_results("SELECT `{$tableTax}`.`term_id` FROM `{$tableTax}`
            INNER JOIN `{$tableMeta}` ON `{$tableTax}`.`term_id` = `{$tableMeta}`.`term_id`
            WHERE (`{$tableTax}`.`taxonomy` = '{$tax}') AND (`{$tableMeta}`.`meta_key` = 'rel_city' AND `{$tableMeta}`.`meta_value` = {$id})", ARRAY_A);

            $res = [];

            foreach ($placesIds as $val) {
                $res[] = (int)$val['term_id'];
            }

            return $res;
        });
    }


    function getImageSrc($size = 'thumbnail'): string
    {
        if ($img = $this->getField('city_img')) {
            return wp_get_attachment_image_url($img, $size);
        }
        return '';
    }


    function getCrmCityId(): int
    {
        return (int)$this->getField('city_crm_id');
    }

    /**
     * - Это город выезда
     */
    function isDepart(): bool
    {
        return !!$this->getField('city_is_depart');
    }
}
