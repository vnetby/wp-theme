<?php

namespace Vnet\Types;

use Vnet\Constants\PostTypes;

class TypeBlog extends PostType
{

    protected $slug = PostTypes::BLOG;


    function setup()
    {
        $this->menuColorOrange();
        $this->menuAwesomeIco('f1ea');

        $this->register([
            'label' => 'Блог'
        ]);
    }
}
