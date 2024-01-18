<?php

namespace Vnetby\Wptheme;

use Vnetby\Helpers\HelperStr;
use Vnetby\Schemaorg\Jsonld;
use Vnetby\Schemaorg\Types\Thing\CreativeWork\WebPage\WebPage;
use Vnetby\Schemaorg\Types\Type;
use Vnetby\Wptheme\Front\Template;


class Seo
{
    const LIMIT_TITLE = 60;
    const LIMIT_DESC = 300;
    const LIMIT_IMG_WIDTH = 1200;
    const LIMIT_IMG_HEIGHT = 630;


    static function setup()
    {
        add_filter('pre_get_document_title', function (string $title) {
            if ($pageTitle = static::getCurrentTitle()) {
                return static::filterTitle($pageTitle);
            }
            return $title;
        });

        add_action('wp_head', function () {
            if ($pageDesc = static::getCurrentDesc()) {
                $pageDesc = static::filterDesc($pageDesc);
                echo '<meta name="description" content="' . $pageDesc . '">';
            }
            static::renderOgMeta();
            static::generateJsonLd();
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


    protected static function filterTitle(string $title): string
    {
        return static::filterSeoString($title, static::LIMIT_TITLE);
    }


    protected static function filterDesc(string $desc): string
    {
        return static::filterSeoString($desc, static::LIMIT_DESC);
    }


    protected static function filterSeoString(string $str, $limit = -1): string
    {
        $str = strip_tags($str);
        $str = preg_replace("/\n/", ' ', $str);
        $str = preg_replace("/[\s]{2,}/", ' ', $str);
        if ($limit > -1 && mb_strlen($str) > $limit) {
            $str = mb_substr($str, 0, $limit);
        }
        return htmlspecialchars($str);
    }


    protected static function renderOgMeta()
    {
        $metaTags = [
            [
                'property' => 'og:title',
                'content' => static::filterTitle(static::getCurrentTitle())
            ],
            [
                'property' => 'og:description',
                'content' => static::filterDesc(static::getCurrentDesc())
            ],
            [
                'property' => 'og:image',
                'content' => static::getCurrentImage()
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
    protected static function generateJsonLd()
    {
        if ($type = static::getCurrentSchemaType()) {
            Jsonld::create($type)->render();
        }
    }


    /**
     * - Добавляет необходимые настройки для сео
     */
    static function setupSeoSettings()
    {
        // настройки сео поста

        add_action('add_meta_boxes', function ($query, \WP_Post $post) {
            if (!static::isPublicPostType($post->post_type)) {
                return;
            }

            add_meta_box('vnet_seo_metabox', __('SEO', 'vnet'), function (\WP_Post $post, $meta) {
                echo '<p>';
                echo __('Приоритетность заголовка: СЕО заголовок; название поста.', 'vnet');
                echo '<br>';
                echo __('Приоритетность описания: СЕО описание; краткое описание поста.', 'vnet');
                echo '<br>';
                echo __('Приоритетность картинки: СЕО картинка; картинка поста; картинка архива; картинка по умолчанию.', 'vnet');
                echo '</p>';
                Template::theFile(Container::getLoader()->libPath('templates/seo-metabox.php'), [
                    'title' => static::getPostMetaTitle($post->ID),
                    'desc' => static::getPostMetaDesc($post->ID),
                    'image' => static::getPostMetaImageId($post->ID),
                    'name_title' => 'vnet-seo-title',
                    'name_desc' => 'vnet-seo-desc',
                    'name_image' => 'vnet-seo-image'
                ]);
            });
        }, 10, 2);

        add_action('save_post', function ($postId) {
            $post = get_post($postId);

            if (!static::isPublicPostType($post->post_type)) {
                return;
            }

            $title = $_REQUEST['vnet-seo-title'] ?? '';
            $desc = $_REQUEST['vnet-seo-desc'] ?? '';
            $image = $_REQUEST['vnet-seo-image'] ?? '';
            update_post_meta($postId, 'vnet_seo', ['title' =>  $title, 'desc' => $desc, 'image' => $image]);
        });


        // настройки сео архивов и общие

        add_action('admin_menu', function () {
            add_options_page(__('Настройки СЕО', 'vnet'), __('СЕО', 'vnet'), 'manage_options', 'vnet-seo-options', function () {
                Template::theFile(Container::getLoader()->libPath('templates/seo-options.php'));
            });
        });

        // настройки сео терминов

        $taxes = static::getPublicTaxonomies();

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
                    'title' => static::getTermMetaTitle($term->term_id),
                    'desc' => static::getTermMetaDesc($term->term_id),
                    'image' => static::getTermMetaImageId($term->term_id),
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
                update_term_meta($termId, 'vnet_seo', serialize(['title' => $title, 'desc' => $desc, 'image' => $img]));
            });
        }
    }


    static function getCurrentSchemaType(): ?Type
    {
        if (is_404()) {
            return null;
        }

        if ($model = Container::getLoader()->getCurrentEntityElement()) {
            if ($type = $model->getSeoSchemaType()) {
                return $type;
            }
            return null;
        }

        if (is_archive()) {
            if ($type = static::getArchiveSchemaType($GLOBALS['wp_query']->query['post_type'])) {
                return $type;
            }
            return null;
        }

        return null;
    }


    /**
     * - Получает типы постов с архивами
     *
     * @return \WP_Post_Type[]
     */
    static function getPostTypesWithArchives(): array
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
     * - Получает типы постов с публичными элементами
     *
     * @return \WP_Post_Type[]
     */
    static function getPublicPostTypes(): array
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

    static function isPublicPostType(string $postType): bool
    {
        $postTypes = static::getPublicPostTypes();
        foreach ($postTypes as $postType) {
            if ($postType->name === $postType->name) {
                return true;
            }
        }
        return false;
    }

    /**
     * - Получает таксономии которые имеют бубличные страницы терминов
     * @return \WP_Taxonomy[]
     */
    static function getPublicTaxonomies(): array
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

    /**
     * - Сохраняет общие настройки
     */
    static function saveOptionsFromRequest()
    {
        $postTypes = static::getPostTypesWithArchives();
        $data = [
            'archiveCommonTitle' => $_REQUEST['vnet-seo-common-archive-title'] ?? '',
            'archiveCommonDesc' => $_REQUEST['vnet-seo-common-archive-desc'] ?? '',
            'commonImage' => (int)($_REQUEST['vnet-seo-common-image'] ?? 0)
        ];
        foreach ($postTypes as $postType) {
            $data['post_type_' . $postType->name] = [
                'title' => $_REQUEST['vnet-seo-post-' . $postType->name . '-title'],
                'desc' => $_REQUEST['vnet-seo-post-' . $postType->name . '-desc'],
                'image' => (int)($_REQUEST['vnet-seo-post-' . $postType->name . '-image'] ?? 0)
            ];
        }
        update_option('vnet_seo_options', serialize($data));
    }

    /**
     * - Получает настройки сео поста
     * @param integer $postId
     * @return array
     */
    static function getPostSeoMeta(int $postId): array
    {
        $data = get_post_meta($postId, 'vnet_seo', true);
        if (!$data) {
            return [];
        }
        // обратная совместимость
        // раньше сохранялось строкой
        if (!is_array($data)) {
            $data = @unserialize($data);
        }
        return $data ? $data : [];
    }

    /**
     * - Получает сео настройки термина
     * @param integer $termId
     * @return array
     */
    static function getTermSeoMeta(int $termId): array
    {
        $data = get_term_meta($termId, 'vnet_seo', true);
        return $data ? unserialize($data) : [];
    }

    /**
     * - Получает заголовок текущей страницы
     * 
     * @return string
     */
    static function getCurrentTitle(): string
    {
        if (is_404()) {
            return Container::getLoader()->getNotFoundTitle();
        }
        if ($entity = Container::getLoader()->getCurrentEntityClass()) {
            if ($item = $entity::getCurrent()) {
                return $item->getSeoTitle();
            }
            if (is_archive()) {
                return $entity::getArchiveSeoTitle();
            }
        }
        return get_bloginfo('title');
    }

    /**
     * - Получает описание текущей страницы
     * @return string
     */
    static function getCurrentDesc(): string
    {
        if (is_404()) {
            return '';
        }
        if ($entity = Container::getLoader()->getCurrentEntityClass()) {
            if ($item = $entity::getCurrent()) {
                return $item->getSeoDesc();
            }
            if (is_archive()) {
                return $entity::getArchiveSeoDesc();
            }
        }
        return get_bloginfo('description');
    }

    /**
     * - Получает картинку текущей страницы
     * @return string
     */
    static function getCurrentImage(): string
    {
        if ($entity = Container::getLoader()->getCurrentEntityClass()) {
            if ($item = $entity::getCurrent()) {
                return $item->getSeoImage();
            }
            if (is_archive()) {
                return $entity::getArchiveSeoImage();
            }
        }
        return '';
    }

    ///////////////////////////////////////////////////////////////
    //                          ТЕРМИНЫ
    ///////////////////////////////////////////////////////////////

    static function getTermTitle(int $termId): string
    {
        if ($title = static::getTermMetaTitle($termId)) {
            return $title;
        }
        $term = Container::getLoader()->getTermById($termId);
        if ($term) {
            return $term->getTitle();
        }
        return '';
    }

    static function getTermDesc(int $termId): string
    {
        if ($desc = static::getTermMetaDesc($termId)) {
            return $desc;
        }
        $term = Container::getLoader()->getTermById($termId);
        if ($term) {
            return $term->getContent();
        }
        return '';
    }

    static function getTermImageId(int $termId): int
    {
        return static::getTermMetaImageId($termId);
    }

    static function getTermImage(int $termId): string
    {
        if ($img = static::getTermMetaImageId($termId)) {
            return wp_get_attachment_image_url($img, 'full');
        }
        return '';
    }

    static function getTermMetaTitle(int $termId): string
    {
        return static::getTermSeoMeta($termId)['title'] ?? '';
    }

    static function getTermMetaDesc(int $termId): string
    {
        return static::getTermSeoMeta($termId)['desc'] ?? '';
    }

    static function getTermMetaImageId(int $termId): int
    {
        return (int)(static::getTermSeoMeta($termId)['image'] ?? 0);
    }

    static function getTermSchemaType(int $termId): WebPage
    {
        return static::getDefaultSchemaType(
            static::getTermTitle($termId),
            static::getTermDesc($termId),
            static::getTermImage($termId)
        );
    }

    ///////////////////////////////////////////////////////////////
    //                      ТИПЫ ПОСТОВ
    ///////////////////////////////////////////////////////////////

    static function getPostTitle(int $postId): string
    {
        if ($title = static::getPostMetaTitle($postId)) {
            return $title;
        }
        return get_the_title($postId);
    }

    static function getPostDesc(int $postId): string
    {
        if ($desc = static::getPostMetaDesc($postId)) {
            return $desc;
        }
        return get_the_excerpt($postId);
    }

    static function getPostImageId(int $postId): int
    {
        if ($img = static::getPostMetaImageId($postId)) {
            return $img;
        }
        if ($img = get_post_thumbnail_id($postId)) {
            return $img;
        }
        return static::getOptionCommonImageId();
    }

    static function getPostImage(int $postId): string
    {
        $imgId = static::getPostImageId($postId);
        if (!$imgId) {
            return '';
        }
        return wp_get_attachment_image_url($imgId, 'full');
    }

    static function getPostMetaTitle(int $postId): string
    {
        return static::getPostSeoMeta($postId)['title'] ?? '';
    }

    static function getPostMetaDesc(int $postId): string
    {
        return static::getPostSeoMeta($postId)['desc'] ?? '';
    }

    static function getPostMetaImageId(int $postId): int
    {
        return (int)(static::getPostSeoMeta($postId)['image'] ?? 0);
    }

    static function getPostSchemaType(int $postId): WebPage
    {
        return static::getDefaultSchemaType(
            static::getPostTitle($postId),
            static::getPostDesc($postId),
            static::getPostImage($postId)
        );
    }

    ///////////////////////////////////////////////////////////////
    //                    Общие настройки
    ///////////////////////////////////////////////////////////////

    static function getOptions(): array
    {
        $data = get_option('vnet_seo_options');
        return $data ? unserialize($data) : [];
    }

    static function getPostTypeOptions(string $postType): array
    {
        return static::getOptions()['post_type_' . $postType] ?? [];
    }

    static function getOptionArchiveTitle(string $postType): string
    {
        return static::getPostTypeOptions($postType)['title'] ?? '';
    }

    static function getOptionArchiveDesc(string $postType): string
    {
        return static::getPostTypeOptions($postType)['desc'] ?? '';
    }

    static function getOptionArchiveImageId(string $postType): int
    {
        return (int)(static::getPostTypeOptions($postType)['image'] ?? 0);
    }

    static function getOptionCommonArchiveTitle(): string
    {
        return static::getOptions()['archiveCommonTitle'] ?? '';
    }

    static function getOptionCommonArchiveDesc(): string
    {
        return static::getOptions()['archiveCommonDesc'] ?? '';
    }

    static function getOptionCommonImageId(): int
    {
        return (int)(static::getOptions()['commonImage'] ?? 0);
    }

    static function getArchiveTitle(string $postType): string
    {
        if ($title = static::getOptionArchiveTitle($postType)) {
            return $title;
        }
        return static::getOptionCommonArchiveTitle();
    }

    static function getArchiveDesc(string $postType): string
    {
        if ($desc = static::getOptionArchiveDesc($postType)) {
            return $desc;
        }
        return static::getOptionCommonArchiveDesc();
    }

    static function getArchiveImageId(string $postType): int
    {
        if ($img = static::getOptionArchiveImageId($postType)) {
            return $img;
        }
        return static::getOptionCommonImageId();
    }

    static function getArchiveImage(string $postType): string
    {
        if ($img = static::getArchiveImageId($postType)) {
            return wp_get_attachment_image_url($img, 'full');
        }
        return '';
    }

    static function getArchiveSchemaType(string $postType): Type
    {
        return static::getDefaultSchemaType(
            static::getArchiveTitle($postType),
            static::getArchiveDesc($postType),
            static::getArchiveImage($postType)
        );
    }


    static function getDefaultSchemaType(string $title = '', string $desc = '', string $image = ''): Type
    {
        return static::getWebPageSchemaType($title, $desc, $image);
    }


    static function getWebPageSchemaType(string $title = '', string $desc = '', string $image = ''): WebPage
    {
        $page = new WebPage;

        if (!$title) {
            $title = self::getCurrentTitle();
        }

        if (!$desc) {
            $desc = self::getCurrentDesc();
        }

        if (!$image) {
            $image = self::getCurrentImage();
        }

        if ($title) {
            $page->setName($title);
        }

        if ($desc) {
            $page->setDescription($desc);
        }

        if ($image) {
            $page->setImage($image);
        }

        return $page;
    }
}
