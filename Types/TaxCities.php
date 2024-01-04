<?php

namespace Vnet\Types;

use Vnet\Constants\PostTypes;
use Vnet\Constants\Taxonomies;

class TaxCities extends Taxonomy
{

    protected $slug = Taxonomies::LOC_CITIES;


    function setup()
    {
        $this->addColumn('image', 'Картинка', function (int $termId) {
            if ($img = get_field('city_img', 'term_' . $termId)) {
                return '<img src="' . wp_get_attachment_image_url($img, 'thumbnail') . '" style="width: 150px;">';
            }
            return '';
        }, 'name');

        $this->addColumn('is_depart', 'Выезд', function (int $termId) {
            if (get_field('city_is_depart', 'term_' . $termId)) {
                return 'Да';
            }
            return '';
        }, 'image');

        $this->hideDescription();
        // $this->hideSlug();
        $this->hideYoast();
        $this->hidePosts();

        $this->register([
            'label' => 'Города',
            'publicly_queryable' => true
        ], [PostTypes::LOCATIONS]);
    }
}
