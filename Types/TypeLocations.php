<?php

namespace Vnet\Types;

use Vnet\Constants\PostTypes;
use Vnet\Constants\Taxonomies;

class TypeLocations extends PostType
{

    protected $slug = PostTypes::LOCATIONS;

    function setup()
    {
        $this->menuColorOrange();
        $this->menuAwesomeIco('f124');
        $this->groupTaxonomies(TaxCities::getInstance()->getSlug());

        $this->register([
            'label' => 'Локации',
            'publicly_queryable' => false,
            'has_archive' => false,
            'exclude_from_search' => true,
            'taxonomies' => [
                Taxonomies::LOC_CITIES,
                Taxonomies::LOC_PLACES,
                Taxonomies::LOC_COUNTRIES
            ]
        ]);
    }
}
