<?php

namespace Vnet\Helpers;

use Vnet\Loader;

class Html
{

    static function urlPhone(string $label): string
    {
        return 'tel:+' . preg_replace("/[^\d]+/", '', $label);
    }


    static function ulrEmail(string $email): string
    {
        return 'mailto:' . $email;
    }


    static function getSvg($name, $img = false)
    {
        $pathSvg = Loader::getInstance()->getPathSvg();

        $file = Path::join($pathSvg, $name . '.svg');

        if (!file_exists($file)) {
            return '';
        }

        if ($img) {
            $root = ArrayHelper::getServer('DOCUMENT_ROOT');
            $src = str_replace($root, '', $file);
            if (!preg_match("/^\//", $src)) $src = "/$src";
            return "<img src='$src' class='svg-img' alt='svg image'>";
        }

        $file = file_get_contents($file);
        $file = str_replace('<svg', '<svg data-name="' . $name . '"', $file);

        return $file;
    }
}
