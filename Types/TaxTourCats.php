<?php

namespace Vnet\Types;

use Vnet\Constants\PostTypes;
use Vnet\Constants\Taxonomies;
use Vnet\Entities\TermTourCat;

class TaxTourCats extends Taxonomy
{

    protected $slug = Taxonomies::TOUR_CATS;


    function setup()
    {
        // $this->hideSlug();
        $this->hideDescription();

        $this->onAfterUpdate(function ($termId) {
            TermTourCat::getById($termId)->autoAssign();
        });

        $this->register([
            'label' => 'Категории',
            'publicly_queryable' => true,
            'show_admin_column' => true
        ], [PostTypes::TOURS]);
    }
}
