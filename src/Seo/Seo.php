<?php

namespace Vnetby\Wptheme\Seo;

use Vnetby\Schemaorg\Jsonld;
use Vnetby\Schemaorg\Types\Thing\Intangible\ItemList\BreadcrumbList;
use Vnetby\Schemaorg\Types\Thing\Intangible\ListItem\ListItem;
use Vnetby\Schemaorg\Types\Thing\Organization\Organization;
use Vnetby\Schemaorg\Types\Thing\Thing;
use Vnetby\Schemaorg\Types\Type;
use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Router;
use Vnetby\Wptheme\Traits\CacheClass;
use Vnetby\Wptheme\Traits\Singletone;


class Seo
{
    const LIMIT_TITLE = 60;
    const LIMIT_DESC = 300;
    const LIMIT_IMG_WIDTH = 1200;
    const LIMIT_IMG_HEIGHT = 630;

    use CacheClass, Singletone;

    public SeoOptions $options;
    public SeoTerm $term;
    public SeoPost $post;
    public SeoArchive $archive;


    protected function __construct()
    {
        $options = Container::getClassSeoOptions();
        $term = Container::getClassSeoTerm();
        $post = Container::getClassSeoPost();
        $archive = Container::getClassSeoArchive();

        $this->options = new $options;
        $this->term = new $term;
        $this->post = new $post;
        $this->archive = new $archive;

        add_filter('pre_get_document_title', function (string $title) {
            if ($pageTitle = $this->getCurrentTitle()) {
                return $this->filterTitle($pageTitle);
            }
            return $title;
        });

        add_action('wp_head', function () {
            if ($pageDesc = $this->getCurrentDesc()) {
                $pageDesc = $this->filterDesc($pageDesc);
                echo '<meta name="description" content="' . $pageDesc . '">';
            }
            $this->renderOgMeta();
            $this->renderJsonLd();
        });

        add_filter('get_canonical_url', function ($url) {
            if ($item = Container::getLoader()->getCurrentEntityElement()) {
                if ($canonical = $item->getCanonicalUrl()) {
                    return $canonical;
                }
            }
            return $url;
        });
    }


    function getTermTitle(int $termId): string
    {
        return $this->term->getTermTitle($termId);
    }

    function getTermDesc(int $termId): string
    {
        return $this->term->getTermDesc($termId);
    }

    function getTermImageId(int $termId): int
    {
        return $this->term->getTermImageId($termId);
    }

    function getTermImage(int $termId): string
    {
        return $this->term->getTermImage($termId);
    }

    /**
     * @return array<array{
     *      url: string,
     *      label: string
     * }>
     */
    function getTermBreadcrumbs(int $termId): array
    {
        return $this->term->getTermBreadcrumbs($termId);
    }

    function getPostTitle(int $postId): string
    {
        return $this->post->getPostTitle($postId);
    }

    function getPostDesc(int $postId): string
    {
        return $this->post->getPostDesc($postId);
    }

    function getPostImageId(int $postId): int
    {
        return $this->post->getPostImageId($postId);
    }

    function getPostImage(int $postId): string
    {
        return $this->post->getPostImage($postId);
    }

    /**
     * @return array<array{
     *      url: string,
     *      label: string
     * }>
     */
    function getPostBreadcrumbs(int $postId): array
    {
        return $this->post->getPostBreadcrumbs($postId);
    }

    /**
     * @return Type[]|Type|null
     */
    function getPostSchemaType(int $postId)
    {
        return $this->post->getPostSchemaType($postId);
    }


    function getArchiveTitle(string $postType): string
    {
        return $this->archive->getArchiveTitle($postType);
    }

    function getArchiveDesc(string $postType): string
    {
        return $this->archive->getArchiveDesc($postType);
    }

    function getArchiveImageId(string $postType): int
    {
        return $this->archive->getArchiveImageId($postType);
    }

    function getArchiveImage(string $postType): string
    {
        return $this->archive->getArchiveImage($postType);
    }

    /**
     * @return Type[]|Type|null
     */
    function getCurrentArchiveSchemaType()
    {
        return $this->archive->getCurrentArchiveSchemaType();
    }

    /**
     * - Поулчает хлебные крошки архива
     * @param string $postType
     * @return array<array{
     *      url: string,
     *      label: string
     * }>
     */
    function getArchiveBreadcrumbs(string $postType): array
    {
        return $this->archive->getArchiveBreadcrumbs($postType);
    }


    /**
     * - Получает хлебную крошку главной страницы
     * - Используется при построении хлебных крошек на всех страницах
     * @return null|array{
     *      url: string,
     *      label: string
     * } если вернуть null - главная страница не будет выводится в хлебных крошках
     */
    function getHomeBreadcrumb(): ?array
    {
        return [
            'url' => get_home_url(),
            'label' => __('Главная', 'vnet')
        ];
    }

    /**
     * - Выводить последнюю хлебную крошку
     * @return boolean
     */
    function showLastBreadcrumb(): bool
    {
        return true;
    }


    /**
     * - Получает таксономии которые имеют бубличные страницы терминов
     * @return \WP_Taxonomy[]
     */
    function getPublicTaxonomies(): array
    {
        $taxes = get_taxonomies();
        $res = [];

        foreach ($taxes as $taxKey) {
            $tax = get_taxonomy($taxKey);
            if (!$tax) {
                continue;
            }
            if (!$tax->publicly_queryable) {
                continue;
            }
            $res[] = $tax;
        }

        return $res;
    }


    function isPublicPostType(string $postType): bool
    {
        $postTypes = $this->getPublicPostTypes();
        foreach ($postTypes as $postType) {
            if ($postType->name === $postType->name) {
                return true;
            }
        }
        return false;
    }


    /**
     * - Получает типы постов с публичными элементами
     *
     * @return \WP_Post_Type[]
     */
    function getPublicPostTypes(): array
    {
        $types = get_post_types();
        $res = [];
        foreach ($types as $type) {
            $postType = get_post_type_object($type);
            if (!$postType) {
                continue;
            }
            // почему-то для страниц не установлено publicly_queryable
            if ($postType->publicly_queryable || $postType->name === 'page') {
                $res[] = $postType;
            }
        }
        return $res;
    }


    /**
     * - Получает типы постов с архивами
     *
     * @return \WP_Post_Type[]
     */
    function getPostTypesWithArchives(): array
    {
        $types = get_post_types();
        $res = [];
        foreach ($types as $type) {
            $postType = get_post_type_object($type);
            if (!$postType) {
                continue;
            }
            if ($postType->has_archive && $postType->publicly_queryable) {
                $res[] = $postType;
            }
        }
        return $res;
    }


    /**
     * - Получает массив хлебных крошек текущей страницы
     * @return array<array{
     *      url: string,
     *      text: string
     * }>
     */
    function getCurrentBreadcrumbs(): array
    {
        if (is_tax()) {
            $obj = get_queried_object();
            if ($obj) {
                return $this->term->getTermBreadcrumbs($obj->term_id);
            }
            return [];
        }

        if (is_archive()) {
            return $this->archive->getArchiveBreadcrumbs(get_post_type());
        }

        if (is_singular()) {
            return $this->post->getPostBreadcrumbs(get_the_ID());
        }

        return [];
    }


    /**
     * - Фильтрует заголовок перед выводом
     */
    function filterTitle(string $title): string
    {
        return $this->filterSeoString($title, static::LIMIT_TITLE);
    }


    /**
     * - Фильтрует описание перед выводом
     */
    function filterDesc(string $desc): string
    {
        return $this->filterSeoString($desc, static::LIMIT_DESC);
    }


    /**
     * - Фильтрует строку перед выводом
     */
    function filterSeoString(string $str, $limit = -1): string
    {
        $str = strip_tags($str);
        $str = preg_replace("/\n/", ' ', $str);
        $str = preg_replace("/[\s]{2,}/", ' ', $str);
        if ($limit > -1 && mb_strlen($str) > $limit) {
            $str = mb_substr($str, 0, $limit - 3);
            $str = preg_replace("/\s[^\s]*$/", '', $str);
            $str .= '...';
        }
        return htmlspecialchars($str);
    }


    protected function renderOgMeta()
    {
        $metaTags = [
            [
                'property' => 'og:title',
                'content' => $this->filterTitle($this->getCurrentTitle())
            ],
            [
                'property' => 'og:description',
                'content' => $this->filterDesc($this->getCurrentDesc())
            ],
            [
                'property' => 'og:image',
                'content' => $this->getCurrentImage()
            ],
            [
                'property' => 'og:locale',
                'content' => get_locale()
            ],
            [
                'property' => 'og:type',
                'content' => 'website'
            ],
            [
                'property' => 'og:url',
                'content' => Router::getCurrentUrl()
            ],
            [
                'property' => 'og:site_name',
                'content' => htmlspecialchars(get_bloginfo('title'))
            ]
        ];

        foreach ($metaTags as $metData) {
            $str = '<meta property="' . $metData['property'] . '" content="' . $metData['content'] . '">' . "\r\n";
            echo $str;
        }
    }


    /**
     * - Генерирует JSON-lD разметку
     */
    protected function renderJsonLd()
    {
        if ($mainType = $this->getCurrentSchemaType()) {
            if (!is_array($mainType)) {
                $mainType = [$mainType];
            }
            foreach ($mainType as $typeItem) {
                Jsonld::create($typeItem)->render();
            }
        }

        if ($org = $this->getSchemaOrganization()) {
            Jsonld::create($org)->render();
        }

        if ($breads = $this->getCurrentSchemaBreacrumbs()) {
            Jsonld::create($breads)->render();
        }
    }


    /**
     * - Получает тип текущей страницы
     * @return Type[]|Type|null
     */
    function getCurrentSchemaType()
    {
        if (is_404()) {
            return null;
        }

        if ($entity = Container::getLoader()->getCurrentEntityClass()) {
            if (is_archive()) {
                return $entity::getCurrentArchiveSchemaType();
            }
            if ($item = $entity::getCurrent()) {
                return $item->getSeoSchemaType();
            }
        }

        return null;
    }


    /**
     * - Получает заголовок текущей страницы
     * 
     * @return string
     */
    function getCurrentTitle(): string
    {
        if (is_404()) {
            return Container::getLoader()->getNotFoundTitle();
        }
        if ($entity = Container::getLoader()->getCurrentEntityClass()) {
            if (is_archive() && !is_tax()) {
                return $entity::getArchiveSeoTitle();
            }
            if ($item = $entity::getCurrent()) {
                return $item->getSeoTitle();
            }
        }
        return get_bloginfo('title');
    }


    /**
     * - Получает описание текущей страницы
     * @return string
     */
    function getCurrentDesc(): string
    {
        if (is_404()) {
            return '';
        }
        if ($entity = Container::getLoader()->getCurrentEntityClass()) {
            if (is_archive() && !is_tax()) {
                return $entity::getArchiveSeoDesc();
            }
            if ($item = $entity::getCurrent()) {
                return $item->getSeoDesc();
            }
        }
        return get_bloginfo('description');
    }


    /**
     * - Получает картинку текущей страницы
     * @return string
     */
    function getCurrentImage(): string
    {
        if ($entity = Container::getLoader()->getCurrentEntityClass()) {
            if (is_archive() && !is_tax()) {
                return $entity::getArchiveSeoImage();
            }
            if ($item = $entity::getCurrent()) {
                return $item->getSeoImage();
            }
        }
        return '';
    }


    /**
     * @return ?BreadcrumbList
     */
    function getCurrentSchemaBreacrumbs()
    {
        if ($items = $this->getCurrentBreadcrumbs()) {
            return $this->createSchemaBreadcrumb($items);
        }
        return null;
    }

    /**
     * - Формирует тип BreadcrumbList по переданному массиву
     *
     * @param array<array{
     *      url: string,
     *      label: string
     * }> $items
     * @return BreadcrumbList
     */
    function createSchemaBreadcrumb(array $items)
    {
        $bread = new BreadcrumbList;

        $schemaItems = [];

        $i = 1;
        foreach ($items as $link) {
            $item = new ListItem;
            $item->setName($link['label']);
            $item->setUrl($link['url']);
            $item->setIdentifier($link['url']);
            $item->setPosition($i);
            $item->setItem((new Thing)->setName($link['label'])->setUrl($link['url'])->setIdentifier($link['url']));
            $schemaItems[] = $item;
            $i++;
        }

        $bread->setItemListElement($schemaItems);

        return $bread;
    }


    /**
     * @return ?Organization
     */
    function getSchemaOrganization()
    {
        return null;
    }
}
