<?php

/**
 * - Класс для работы с сео терминов
 */

namespace Vnetby\Wptheme\Seo;

use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Front\Template;

class SeoTerm
{

    const META_KEY = 'vnet_seo';

    function __construct()
    {
        add_action('init', function () {
            $this->registerMetaboxes();
        });
    }


    function registerMetaboxes()
    {
        $taxes = Container::getSeo()->getPublicTaxonomies();

        foreach ($taxes as $tax) {
            add_action("{$tax->name}_add_form_fields", function () {
                echo '<div style="padding: 20px; background-color: #fff; border-radius: 10px;">';
                echo '<h3 style="margin-top: 0px">' . __('СЕО', 'vnet') . '</h3>';
                echo '<p>';
                echo __('Приоритетность заголовка: СЕО заголовок; название элемента.', 'vnet');
                echo '<br>';
                echo __('Приоритетность описания: СЕО описание; описание элемента.', 'vnet');
                echo '<br>';
                echo __('Приоритетность картинки: СЕО картинка; картинка по умолчанию.', 'vnet');
                echo '</p>';
                Template::theFile(Container::getLoader()->libPath('templates/seo-metabox.php'), [
                    'title' => '',
                    'desc' => '',
                    'image' => '',
                    'name_title' => 'vnet-seo-term-title',
                    'name_desc' => 'vnet-seo-term-desc',
                    'name_image' => 'vnet-seo-term-image'
                ]);
                echo '</div>';
                echo '<br>';
            });
            add_action("{$tax->name}_edit_form", function (\WP_Term $term) {
                echo '<div style="padding: 20px; background-color: #fff; border-radius: 15px;">';
                echo '<h3 style="margin-top: 0px">' . __('СЕО', 'vnet') . '</h3>';
                echo '<p>';
                echo __('Приоритетность заголовка: СЕО заголовок; название элемента.', 'vnet');
                echo '<br>';
                echo __('Приоритетность описания: СЕО описание; описание элемента.', 'vnet');
                echo '<br>';
                echo __('Приоритетность картинки: СЕО картинка; картинка по умолчанию.', 'vnet');
                echo '</p>';
                Template::theFile(Container::getLoader()->libPath('templates/seo-metabox.php'), [
                    'title' => $this->getTermMetaTitle($term->term_id),
                    'desc' => $this->getTermMetaDesc($term->term_id),
                    'image' => $this->getTermMetaImageId($term->term_id),
                    'name_title' => 'vnet-seo-term-title',
                    'name_desc' => 'vnet-seo-term-desc',
                    'name_image' => 'vnet-seo-term-image'
                ]);
                echo '</div>';
                echo '<br>';
            });
            add_action("saved_{$tax->name}", function ($termId) {
                $title = $_REQUEST['vnet-seo-term-title'] ?? '';
                $desc = $_REQUEST['vnet-seo-term-desc'] ?? '';
                $img = (int)($_REQUEST['vnet-seo-term-image'] ?? 0);
                update_term_meta($termId, static::META_KEY, ['title' => $title, 'desc' => $desc, 'image' => $img]);
            });
        }
    }


    /**
     * - Получает сео настройки термина
     * @param integer $termId
     * @return array{
     *      title: ?string,
     *      desc: ?string,
     *      image: ?int
     * }
     */
    function getTermSeoMeta(int $termId): array
    {
        $data = get_term_meta($termId, static::META_KEY, true);
        if (!$data || is_array($data)) {
            return $data;
        }
        $res = @unserialize($data);
        return $res ? $res : [];
    }


    function getTermTitle(int $termId): string
    {
        if ($title = $this->getTermMetaTitle($termId)) {
            return $title;
        }
        $term = Container::getLoader()->getTermById($termId);
        if ($term) {
            return $term->getTitle();
        }
        return '';
    }

    function getTermDesc(int $termId): string
    {
        if ($desc = $this->getTermMetaDesc($termId)) {
            return $desc;
        }
        $term = Container::getLoader()->getTermById($termId);
        if ($term) {
            return $term->getContent();
        }
        return '';
    }

    function getTermImageId(int $termId): int
    {
        return $this->getTermMetaImageId($termId);
    }

    function getTermImage(int $termId): string
    {
        if ($img = $this->getTermMetaImageId($termId)) {
            return wp_get_attachment_image_url($img, 'full');
        }
        return '';
    }

    function getTermMetaTitle(int $termId): string
    {
        return $this->getTermSeoMeta($termId)['title'] ?? '';
    }

    function getTermMetaDesc(int $termId): string
    {
        return $this->getTermSeoMeta($termId)['desc'] ?? '';
    }

    function getTermMetaImageId(int $termId): int
    {
        return (int)($this->getTermSeoMeta($termId)['image'] ?? 0);
    }


    /**
     * - Получает хлебные крошки термина
     * @return array<array{
     *      url: string,
     *      label: string
     * }>
     */
    function getTermBreadcrumbs(int $termId): array
    {
        $item = Container::getLoader()->getTermById($termId);

        if (!$item) {
            return [];
        }

        $res = [];

        if ($home = Container::getSeo()->getHomeBreadcrumb()) {
            $res[] = $home;
        }

        if ($postType = $item::getMainPostType()) {
            if ($entity = Container::getLoader()->getEntityClass($postType)) {
                if ($entity::getAdmin()->getHasArchive() && $postType !== 'page') {
                    $res[] = [
                        'url' => $entity::urlArchive(),
                        'label' => $entity::labelArchive()
                    ];
                }
            }
        }

        if (Container::getSeo()->showLastBreadcrumb()) {
            $res[] = [
                'url' => $item->getUrl(),
                'label' => $item->getTitle()
            ];
        }

        return $res;
    }
}
