<?php

namespace Vnet\Types;

use Vnet\Constants\PostTypes;
use Vnet\Constants\Taxonomies;

class TaxCountries extends Taxonomy
{

    protected $slug = Taxonomies::LOC_COUNTRIES;

    function setup()
    {
        $this->hideYoast();
        $this->hideSlug();
        $this->hideDescription();
        $this->hidePosts();

        $this->register([
            'label' => 'Страны',
            'publicly_queryable' => false
        ], [PostTypes::LOCATIONS]);
    }
}
