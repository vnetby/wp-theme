<?php

namespace Vnetby\Wptheme\Models;


abstract class Model
{

    /**
     * @var array<int,Post>
     */
    protected static array $cachePosts = [];

    /**
     * @var array<int,Term>
     */
    protected static array $cacheTerms = [];

    /**
     * - Объект элемента wordpress
     * @var \WP_Post|\WP_Term
     */
    protected $wpItem;

    protected array $meta = [];

    /**
     * - Получает текущий элемент
     *
     * @return ?static
     */
    abstract static function getCurrent();

    /**
     * - Получает элемент по ID
     *
     * @param integer $id
     * @return ?static
     */
    abstract static function getById(int $id);


    /**
     * @param \WP_Post|\WP_Term $wpItem
     */
    function __construct($wpItem)
    {
        $this->wpItem = $wpItem;
    }


    protected static function getWpDb(): \wpdb
    {
        return $GLOBALS['wpdb'];
    }


    /**
     * - Получает по объекту элемента wordpress
     * @param \WP_Post|\WP_Term $wpItem
     * @return void
     */
    static function getByWpItem($wpItem)
    {
        if ($wpItem instanceof \WP_Post) {
            if (!isset(static::$cachePosts[$wpItem->ID])) {
                static::$cachePosts[$wpItem->ID] = new static($wpItem);
            }
            return static::$cachePosts[$wpItem->ID];
        }
        if (!isset(static::$cacheTerms[$wpItem->term_id])) {
            static::$cacheTerms[$wpItem->term_id] = new static($wpItem);
        }
        return static::$cacheTerms[$wpItem->term_id];
    }


    /**
     * - Устанавливает значение поля
     * @param string $key ключ из WP_Post
     * @param mixed $value
     * 
     * @return static
     */
    function setField(string $key, $value)
    {
        $this->wpItem->$key = $value;
        return $this;
    }

    /**
     * - Устанавливает значение в зависимости от того является ли элемент постом или термином
     *
     * @param string $postField
     * @param string $termField
     * @param mixed $value
     * 
     * @return static
     */
    private function setRightField(string $postField, string $termField, $value)
    {
        return $this->setField($this->isPost() ? $postField : $termField, $value);
    }


    function getField(string $key, $def = null)
    {
        return $this->wpItem->$key ?? $def;
    }

    private function getRightField(string $postField, string $termField, $def = null)
    {
        return $this->getField($this->isPost() ? $postField : $termField, $def);
    }

    function getId(): int
    {
        return (int)$this->getRightField('ID', 'term_id', 0);
    }

    function getTitle(): string
    {
        return $this->getRightField('post_title', 'name', '');
    }

    function getSlug(): string
    {
        return $this->getRightField('post_name', 'slug', '');
    }

    function getParentId(): int
    {
        return (int)$this->getRightField('post_parent', 'parent', 0);
    }

    function getUrl(): string
    {
        return $this->isPost() ? get_permalink($this->wpItem) : get_term_link($this->wpItem, $this->wpItem->taxonomy);
    }

    function getEditUrl(): string
    {
        return $this->isPost() ? get_edit_post_link($this->wpItem) : get_edit_term_link($this->wpItem);
    }

    function getContent(): string
    {
        return $this->getRightField('post_content', 'description', '');
    }

    function getMeta(string $key, bool $single = true)
    {
        return $this->isPost() ? get_post_meta($this->getId(), $key, $single) : get_term_meta($this->getId(), $key, $single);
    }

    function getAcfField(string $selector, $def = null)
    {
        if (!function_exists('get_field')) {
            return $def;
        }
        return get_field($selector, $this->wpItem, true);
    }

    /**
     * @return \WP_Post|\WP_Term
     */
    function getWpItem()
    {
        return $this->wpItem;
    }

    function isPost(): bool
    {
        return $this instanceof Post;
    }

    function isTerm(): bool
    {
        return $this instanceof Term;
    }
}
