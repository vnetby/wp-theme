<?php

namespace Vnet\Types;

use Vnet\Constants\PostTypes;
use Vnet\Constants\Taxonomies;

class TaxPlaces extends Taxonomy
{

    protected $slug = Taxonomies::LOC_PLACES;

    function setup()
    {
        $this->hideDescription();
        $this->hideSlug();
        $this->hideYoast();

        $this->register([
            'label' => 'Места сбора',
            'publicly_queryable' => false
        ], [PostTypes::LOCATIONS]);
    }
}
