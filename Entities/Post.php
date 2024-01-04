<?php

namespace Vnet\Entities;

use Vnet\Cache;
use Vnet\Helpers\Acf;
use Vnet\Helpers\Date;
use Vnetby\Schemaorg\Types\Thing\CreativeWork\WebPage\WebPage;
use Vnetby\Schemaorg\Types\Type;
use WP_Post;
use WP_Query;

class Post
{

    protected static $postType = '';

    /**
     * @var static[]
     */
    private static $instances = [];

    /**
     * @var \WP_Post
     */
    protected $post = null;


    protected function __construct(\WP_Post $post)
    {
        $this->post = $post;
    }


    /**
     * - Получает все посты
     * @return static[]
     */
    static function getAll(int $page = 1, int $perPage = -1)
    {
        $args = [
            'post_type' => static::$postType,
            'paged' => $page,
            'posts_per_page' => $perPage
        ];

        return Cache::fetch(serialize($args), function () use ($args) {
            $query = new WP_Query($args);

            $res = [];

            foreach ($query->posts as $post) {
                $res[] = new static($post);
            }

            return $res;
        });
    }


    /**
     * @return static[] 
     */
    static function getPublished(?array $queryArgs = null): array
    {
        $args = [
            'post_type' => static::$postType,
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ];

        if ($queryArgs) {
            $args = array_merge($args, $queryArgs);
        }

        return Cache::fetch(serialize($args), function () use ($args) {
            $query = new WP_Query($args);

            $res = [];

            foreach ($query->posts as $post) {
                $res[] = new static($post);
            }

            return $res;
        });
    }


    /**
     * @param int $id 
     * @return static|null
     */
    static function getById(int $id): ?self
    {
        if (isset(self::$instances[$id])) {
            return self::$instances[$id];
        }

        $post = get_post($id);

        if (!$post || is_wp_error($post)) {
            self::$instances[$id] = null;
        } else {
            self::$instances[$id] = new static($post);
        }

        return self::$instances[$id];
    }


    /**
     * - Получает текущий пост
     * @return null|static
     */
    static function getCurrent(): ?self
    {
        global $post;

        if (!$post) {
            return null;
        }

        if ($post->post_type !== static::$postType) {
            return null;
        }

        if (!isset(self::$instances[$post->ID])) {
            self::$instances[$post->ID] = new static($post);
        }

        return self::$instances[$post->ID];
    }


    /**
     * @param WP_Post $wpPost 
     * @return static 
     */
    static function getByPost(\WP_Post $wpPost): self
    {
        return new static($wpPost);
    }


    function getPost(): \WP_Post
    {
        return $this->post;
    }


    function getTitle(): string
    {
        return get_the_title($this->post);
    }


    function getId(): int
    {
        return $this->post->ID;
    }


    function getMeta(string $key, $single = false)
    {
        return get_post_meta($this->getId(), $key, $single);
    }

    function updateMeta(string $key, $value, $prevValue = '')
    {
        return update_post_meta($this->getId(), $key, $value, $prevValue);
    }

    function deleteMeta(string $key, $value = '')
    {
        return delete_post_meta($this->getId(), $key, $value);
    }


    function getField($selector)
    {
        return get_field($selector, $this->getId(), true);
    }


    function getPermalink(): string
    {
        return get_permalink($this->getId());
    }


    function getAdminUrl(): string
    {
        return '/wp-admin/post.php?post=' . $this->getId() . '&action=edit';
    }


    function getThumbnailUrl($size = 'thumbnail', $icon = false): string
    {
        if ($img = get_post_thumbnail_id($this->getPost())) {
            $src = wp_get_attachment_image_url($img, $size, $icon);
            return $src ? $src : '';
        }
        return '';
    }


    function getPostDate(?string $format = null): string
    {
        $date = $this->post->post_date;

        if (!$format) {
            return $date;
        }

        return Date::format($format, $date);
    }


    function getExcerpt(): string
    {
        return get_the_excerpt($this->getPost());
    }


    function getContent(): string
    {
        return apply_filters('the_content', $this->post->post_content);
    }


    protected function fetchCache(string $key, callable $fetchFuntion)
    {
        return Cache::fetch($this->getCacheKey($key), $fetchFuntion);
    }


    protected function setCache(string $key, $value)
    {
        Cache::set($this->getCacheKey($key), $value);
    }


    protected function getCache(string $key, $def = null)
    {
        return Cache::get($this->getCacheKey($key), $def);
    }


    private function getCacheKey(string $key): string
    {
        return 'posts:' . $this->getId() . ':' . $key;
    }


    static function urlArchive(): string
    {
        $url = get_post_type_archive_link(static::$postType);
        return $url ? $url : '';
    }


    /**
     * - Является ли текущая страница архивом данного типа поста
     * @return bool 
     */
    static function isArchive(): bool
    {
        return is_post_type_archive(static::$postType);
    }

    /**
     * - Является ли текущая страница отдельной страницой записи типа поста
     * @return bool
     */
    static function isSingular(): bool
    {
        return is_singular(static::$postType);
    }


    function isPublish(): bool
    {
        return $this->getPost()->post_status === 'publish';
    }


    function getSeoTitle(): string
    {
        $data = Acf::getField('page_seo');
        if (!empty($data['title'])) {
            return $data['title'];
        }
        return $this->getTitle();
    }

    function getSeoDesc(): string
    {
        $info = Acf::getField('page_seo');
        if (!empty($info['desc'])) {
            return $info['desc'];
        }
        return $this->getExcerpt();
    }

    function getSeoImage(): string
    {
        if ($img = $this->getThumbnailUrl('medium')) {
            return $img;
        }
        return '';
    }

    function getSeoCanonical(): string
    {
        return $this->getPermalink();
    }

    function getSeoJsonLd(): Type
    {
        $page = new WebPage;
        $page->setName($this->getSeoTitle());
        $page->setUrl($this->getPermalink());
        $page->setDescription($this->getSeoDesc());
        if ($img = $this->getSeoImage()) {
            $page->setImage($img);
        }
        return $page;
    }
}
