<?php

namespace Vnetby\Wptheme\Seo;

use Vnetby\Helpers\HelperDate;
use Vnetby\Schemaorg\Types\Thing\CreativeWork\Article\Article;
use Vnetby\Schemaorg\Types\Thing\CreativeWork\WebPage\WebPage;
use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Entities\Base\EntityPostType;
use Vnetby\Wptheme\Front\Template;

class SeoPost
{

    const SCHEMA_TYPE_ARTICLE = 'article';
    const SCHEMA_TYPE_WEB_PAGE = 'webpage';

    function __construct()
    {
        add_action('init', function () {
            $this->addMetaboxes();
        });
    }

    protected function addMetaboxes()
    {
        add_action('add_meta_boxes', function ($query, \WP_Post $post) {
            if (!Container::getSeo()->isPublicPostType($post->post_type)) {
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
                    'title' => $this->getPostMetaTitle($post->ID),
                    'desc' => $this->getPostMetaDesc($post->ID),
                    'image' => $this->getPostMetaImageId($post->ID),
                    'name_title' => 'vnet-seo-title',
                    'name_desc' => 'vnet-seo-desc',
                    'name_image' => 'vnet-seo-image'
                ]);
            });
        }, 10, 2);

        add_action('save_post', function ($postId) {
            $post = get_post($postId);

            if (!Container::getSeo()->isPublicPostType($post->post_type)) {
                return;
            }

            $title = $_REQUEST['vnet-seo-title'] ?? '';
            $desc = $_REQUEST['vnet-seo-desc'] ?? '';
            $image = $_REQUEST['vnet-seo-image'] ?? '';
            update_post_meta($postId, 'vnet_seo', ['title' =>  $title, 'desc' => $desc, 'image' => $image]);
        });
    }



    /**
     * - Получает настройки сео поста
     * @param integer $postId
     * @return array
     */
    function getPostSeoMeta(int $postId): array
    {
        $data = get_post_meta($postId, 'vnet_seo', true);
        if (!$data) {
            return [];
        }
        if (!is_array($data)) {
            $data = @unserialize($data);
        }
        return $data ? $data : [];
    }


    function getPostTitle(int $postId): string
    {
        if ($title = $this->getPostMetaTitle($postId)) {
            return $title;
        }
        if ($item = Container::getLoader()->getPostById($postId)) {
            if ($title = $item->getTitle()) {
                return $title;
            }
        }
        return get_the_title($postId);
    }

    function getPostDesc(int $postId): string
    {
        if ($desc = $this->getPostMetaDesc($postId)) {
            return $desc;
        }
        if ($item = Container::getLoader()->getPostById($postId)) {
            if ($desc = $item->getExcerpt()) {
                return $desc;
            }
        }
        return get_the_excerpt($postId);
    }

    function getPostImageId(int $postId): int
    {
        if ($img = $this->getPostMetaImageId($postId)) {
            return $img;
        }
        if ($item = Container::getLoader()->getPostById($postId)) {
            if ($img = $item->getImageId()) {
                return $img;
            }
        }
        if ($img = get_post_thumbnail_id($postId)) {
            return $img;
        }
        return Container::getSeo()->options->getCommonImageId();
    }

    function getPostImage(int $postId): string
    {
        $imgId = $this->getPostImageId($postId);
        if (!$imgId) {
            return '';
        }
        return wp_get_attachment_image_url($imgId, 'full');
    }

    function getPostMetaTitle(int $postId): string
    {
        return $this->getPostSeoMeta($postId)['title'] ?? '';
    }

    function getPostMetaDesc(int $postId): string
    {
        return $this->getPostSeoMeta($postId)['desc'] ?? '';
    }

    function getPostMetaImageId(int $postId): int
    {
        return (int)($this->getPostSeoMeta($postId)['image'] ?? 0);
    }

    /**
     * @return Type[]|Type|null
     */
    function getPostSchemaType(int $postId)
    {
        $item = Container::getLoader()->getPostById($postId);

        if (!$item) {
            return null;
        }

        if ($item->getKey() === 'page') {
            return $this->getSchemaTypePage($item);
        }

        return $this->getSchemaTypeArticle($item);
    }


    protected function getSchemaTypeArticle(EntityPostType $item): Article
    {
        return $this->createPageArticleSchemaType($item, self::SCHEMA_TYPE_ARTICLE);
    }


    protected function getSchemaTypePage(EntityPostType $item): WebPage
    {
        return $this->createPageArticleSchemaType($item, self::SCHEMA_TYPE_WEB_PAGE);
    }

    /**
     * - Данный метод создает тип Article или WebPage
     * - Так как используемые методы в типе Article и WebPage одинаковы
     *
     * @param EntityPostType $item
     * @param string<self::SCHEMA_TYPE_*> $type 
     * @return Article|WebPage
     */
    protected function createPageArticleSchemaType(EntityPostType $item, string $type)
    {
        $page = $type === self::SCHEMA_TYPE_ARTICLE ? new Article : new WebPage;

        if ($url = $item->getUrl()) {
            $page->setUrl($url);
        }

        if ($title = $item->getSeoTitle()) {
            $page->setName(Container::getSeo()->filterTitle($title));
        }

        if ($desc = $item->getSeoDesc()) {
            $page->setDescription(Container::getSeo()->filterDesc($desc));
        }

        if ($org = Container::getSeo()->getSchemaOrganization()) {
            $page->setPublisher($org);
        }

        if ($date = $item->getPublishDate('Y-m-d H:i:s')) {
            $page->setDateCreated($date);
        }

        if ($date = $item->getModifiedDate('Y-m-d H:i:s')) {
            $page->setDateModified($date);
        }

        if ($img = $item->getSeoImage()) {
            $page->setImage($img);
        }

        return $page;
    }

    /**
     * @return array<array{
     *      url: string,
     *      label: string
     * }>
     */
    function getPostBreadcrumbs(int $postId): array
    {
        $item = Container::getLoader()->getPostById($postId);

        if (!$item) {
            return [];
        }

        $res = [];

        if ($home = Container::getSeo()->getHomeBreadcrumb()) {
            $res[] = $home;
        }

        if ($item->isFrontPage()) {
            return $res;
        }

        $admin = $item::getAdmin();

        if ($admin->getHasArchive() && $item::getKey() !== 'page') {
            $res[] = [
                'url' => $item::urlArchive(),
                'label' => $item::labelArchive()
            ];
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
