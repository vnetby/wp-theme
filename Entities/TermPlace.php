<?php

namespace Vnet\Entities;

use Vnet\Constants\Taxonomies;
use Vnet\Helpers\Acf;

class TermPlace extends Term
{

    protected static $tax = Taxonomies::LOC_PLACES;

    /**
     * @var TermCity|false
     */
    private $city = null;


    function getCityId(): int
    {
        $cityId = $this->getField('rel_city');
        if (!$cityId) {
            return 0;
        }
        return (int)$cityId;
    }


    function getCity(): ?TermCity
    {
        if ($this->city !== null) {
            return $this->city ? $this->city : null;
        }

        $cityId = $this->getCityId();

        if (!$cityId) {
            $this->city = false;
            return null;
        }

        $city = TermCity::getById($cityId);

        if (!$city) {
            $this->city = false;
            return null;
        }

        $this->city = $city;
        return $this->city;
    }


    function getAddress(): string
    {
        if ($address = $this->getField('place_info_address')) {
            return $address;
        }
        return '';
    }


    function getMapUrl(): string
    {
        if ($map = $this->getField('place_info_mapurl')) {
            return $map;
        }
        return '';
    }


    function getImageUrl($size = 'thumbnail'): string
    {
        if ($imgId = $this->getField('place_info_image')) {
            return wp_get_attachment_image_url($imgId, $size);
        }
        return '';
    }
}
