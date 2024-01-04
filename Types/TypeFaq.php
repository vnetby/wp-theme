<?php

namespace Vnet\Types;

use Vnet\Constants\PostTypes;

class TypeFaq extends PostType
{

    protected $slug = PostTypes::FAQ;


    function setup()
    {
        $this->menuColorGreen();

        $this->register([
            'label' => 'FAQ',
            'menu_icon' => 'dashicons-testimonial',
            'publicly_queryable' => false,
            'exclude_from_search' => true
        ]);
    }
}
