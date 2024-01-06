<?php

namespace Vnetby\Wptheme\Models;

use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Entities\Entity;

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
     * - Получает сущность элемента
     * @return Entity
     */
    static function getEntity(): ?Entity
    {
        foreach (Container::getLoader()->getEntities() as $entity) {
            if ($entity->getModelClass() === get_called_class()) {
                return $entity;
            }
        }
        return null;
    }


    /**
     * - Получает уникальный ключ сущности
     * @return string
     */
    static function getKey(): string
    {
        return static::getEntity()->getKey();
    }


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
     * @return static
     */
    static function getByWpItem($wpItem)
    {
        return self::fetchCache('getByWpItem:' . ($wpItem instanceof \WP_Post ? $wpItem->ID : $wpItem->term_id), function () use ($wpItem) {
            return new static($wpItem);
        });
    }


    static function fetchCache(string $key, callable $fn, int $ttl = 0)
    {
        $key = md5(get_called_class() . ':' . $key);
        return Container::getLoader()->fetchCache($key, $fn, $ttl);
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
        return $this instanceof ModelPostType;
    }

    function isTerm(): bool
    {
        return $this instanceof ModelTaxonomy;
    }

    function getOrder(): int
    {
        if ($this->isTerm()) {
            $val = get_term_meta($this->getId(), 'tax_position', true);
            return $val ? (int)$val : 0;
        }
        return (int)$this->wpItem->menu_order;
    }

    function updateMeta(string $key, $value, $prevValue = '')
    {
        if ($this->isPost()) {
            return update_post_meta($this->getId(), $key, $value, $prevValue);
        }
        return update_term_meta($this->getId(), $key, $value, $prevValue);
    }

    function deleteMeta(string $key, $value = '')
    {
        if ($this->isPost()) {
            return delete_post_meta($this->getId(), $key, $value);
        }
        return delete_term_meta($this->getId(), $key, $value);
    }

    function getchCacheItem(string $key, callable $fn, int $ttl = 0)
    {
        return static::fetchCache($key . ':' . $this->getId(), $fn, $ttl);
    }
}
