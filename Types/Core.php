<?php

namespace Vnet\Types;

use Vnet\Traits\Instance;

class Core
{

    use Instance;

    protected $slug = '';

    private $arInstances = [];


    protected function __construct()
    {
        if ($this->isIframe()) {
            $this->theAdminStyle("
                #adminmenumain,
                #wpadminbar,
                #screen-meta,
                #screen-meta-links,
                #wpfooter,
                .wp-heading-inline,
                .wp-heading-inline+.page-title-action,
                .wp-header-end {
                    display: none!important;
                }
                html.wp-toolbar {
                    padding: 0px!important;
                }
                #wpcontent, #wpfooter {
                    margin-left: 0px!important;
                    padding-left: 0px!important;
                }
                .wrap {
                    margin: 0px!important;
                }
            ");
        }
    }

    protected function isIframe(): bool
    {
        return isset($_SERVER['HTTP_SEC_FETCH_DEST']) && $_SERVER['HTTP_SEC_FETCH_DEST'] == 'iframe';
    }

    protected function theAdminStyle(string $css)
    {
        add_action('admin_head', function () use ($css) {
            $this->theStyle($css);
        });
    }


    protected function theAdminScript(string $js, $onLoad = false)
    {
        add_action('admin_footer', function () use ($js, $onLoad) {
            if ($onLoad) {
                $this->theScriptOnLoad($js);
            } else {
                $this->theScript($js);
            }
        });
    }


    protected function theStyle(string $css)
    {
        echo '<style>' . $css . '</style>';
    }


    protected function theScript(string $js)
    {
        echo '<script>' . $js . '</script>';
    }


    protected function theScriptOnLoad(string $js)
    {
        echo '<script> window.addEventListener("DOMContentLoaded", function () { ' . $js . ' }); </script>';
    }


    /**
     * - Добавляет класс к пункту меню
     * @param string[] $class 
     */
    protected function addMenuClass(...$class)
    {
        add_action('admin_init', function () use ($class) {
            global $menu;

            if (!$menu) {
                return;
            }

            foreach ($menu as &$item) {
                if (!isset($item[5])) {
                    continue;
                }

                if ($item[5] !== 'menu-posts-' . $this->slug) {
                    continue;
                }

                $item[4] = implode(' ', array_merge(explode(' ', $item[4]), $class));

                break;
            }
        });
    }


    function setup()
    {
    }


    function getSlug(): string
    {
        return $this->slug;
    }
}
