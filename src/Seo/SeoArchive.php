<?php

/**
 * - Класс для работы с сео архивов
 */

namespace Vnetby\Wptheme\Seo;

use Vnetby\Schemaorg\Types\Thing\Intangible\ItemList\ItemList;
use Vnetby\Schemaorg\Types\Thing\Intangible\ListItem\ListItem;
use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Entities\Base\EntityPostType;
use Vnetby\Wptheme\Entities\Base\EntityTaxonomy;
use Vnetby\Wptheme\Entities\EntityPost;

class SeoArchive
{

    function __construct()
    {
    }

    /**
     * - Получает заголовок архива
     */
    function getArchiveTitle(string $postType): string
    {
        if ($title = Container::getSeo()->options->getArchiveTitle($postType)) {
            return $title;
        }
        return get_bloginfo('title');
    }

    /**
     * - Получает описание архива
     */
    function getArchiveDesc(string $postType): string
    {
        if ($desc = Container::getSeo()->options->getArchiveDesc($postType)) {
            return $desc;
        }
        return get_bloginfo('description');
    }

    /**
     * - Получает ID картинки для архива
     * @return integer
     */
    function getArchiveImageId(string $postType): int
    {
        if ($imgId = Container::getSeo()->options->getArchiveImageId($postType)) {
            return $imgId;
        }
        return Container::getSeo()->options->getCommonImageId();
    }


    function getArchiveImage(string $postType): string
    {
        if ($img = $this->getArchiveImageId($postType)) {
            return wp_get_attachment_image_url($img, 'full');
        }
        return '';
    }

    /**
     * - Формирует тип schma.org текущей страницы архива
     * - Работает на странице термина и архива
     * @return Type[]|Type|null
     */
    function getCurrentArchiveSchemaType()
    {
        if (!is_archive()) {
            return null;
        }

        $postType = get_post_type();
        $entityClass = Container::getLoader()->getEntityClass($postType);

        /** @var EntityTaxonomy|null */
        $term = is_tax() ? Container::getLoader()->getCurrentEntityElement() : null;

        if (!$entityClass) {
            return null;
        }

        $list = new ItemList;

        $title = $term ? $term->getSeoTitle() : $entityClass::getArchiveSeoTitle();
        $desc = $term ? $term->getSeoDesc() : $entityClass::getArchiveSeoDesc();
        $img = $term ? $term->getSeoImage() : $entityClass::getArchiveSeoImage();

        if ($title) {
            $list->setName(Container::getSeo()->filterTitle($title));
        }

        if ($desc) {
            $list->setDescription(Container::getSeo()->filterDesc($desc));
        }

        if ($img) {
            $list->setImage($img);
        }

        $arrItems = [];

        $itemsClass = $term ? Container::getLoader()->getEntityClass($term->getMainPostType()) : $entityClass;

        if ($itemsClass) {
            foreach ($GLOBALS['wp_query']->posts as $post) {
                /** @var EntityPostType */
                $entityItem = $itemsClass::getByWpItem($post);
                if ($item = $entityItem->getSeoSchemaType()) {
                    $listItem = new ListItem;
                    $listItem->setItem($item);
                    $arrItems[] = $listItem;
                }
            }
        }

        $list->setItemListElement($arrItems);
        $list->setNumberOfItems($GLOBALS['wp_query']->found_posts);

        return $list;
    }


    /**
     * - Получает хлебные крошки
     * @return array<array{
     *      url: string,
     *      label: string
     * }>
     */
    function getArchiveBreadcrumbs(string $postType): array
    {
        $entity = Container::getLoader()->getEntityClass($postType);

        if (!$entity) {
            return [];
        }

        $res = [];

        if ($home = Container::getSeo()->getHomeBreadcrumb()) {
            $res[] = $home;
        }

        if ($entity::getAdmin()->getHasArchive() && Container::getSeo()->showLastBreadcrumb()) {
            $res[] = [
                'url' => $entity::urlArchive(),
                'label' => $entity::labelArchive()
            ];
        }

        return $res;
    }
}
