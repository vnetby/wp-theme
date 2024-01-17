<?php

namespace Vnetby\Wptheme\Models;

use Error;
use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Entities\Base\DbResult;
use Vnetby\Wptheme\Entities\Base\Entity;
use Vnetby\Wptheme\Traits\ModelSeo;


abstract class Model
{
    use ModelSeo;

    /**
     * - Объект элемента wordpress
     * @var \WP_Post|\WP_Term
     */
    protected $wpItem;

    protected array $meta = [];


    /**
     * @param \WP_Post|\WP_Term $wpItem
     */
    function __construct($wpItem)
    {
        $this->wpItem = $wpItem;
    }


    /**
     * - Получает элемент по ID
     * @param integer $id
     * @return ?static
     */
    static function getById(int $id)
    {
        return static::getModelEntity()->getElementById($id);
    }


    /**
     * - Получает объект элемента по объекту WP
     * @param \WP_Post|\WP_Term $wpItem
     * @return static
     */
    static function getByWpItem($wpItem)
    {
        return static::getModelEntity()->getElementByWpItem($wpItem);
    }


    /**
     * - Получает текущий элемент
     * @return ?static
     */
    static function getCurrent()
    {
        return static::getModelEntity()->getCurrentElement();
    }


    /**
     * - Является ли текущая страница страницей архива
     */
    static function isArchive(): bool
    {
        return static::getModelEntity()->isPageArchive();
    }


    /**
     * - Является ли текущая страница отдельной страницей
     */
    static function isSingle(): bool
    {
        return static::getModelEntity()->isPageSingle();
    }


    /**
     * - Фильтрует элементы
     * @param array $filter
     * @param integer $page
     * @param integer $perPage
     * @return DbResult<static>
     */
    static function filter(array $filter = [], int $page = 1, int $perPage = -1)
    {
        return static::getModelEntity()->filter($filter, $page, $perPage);
    }


    /**
     * - Проверяет наследовательность вызванного класса
     * @throws \Error
     * @param integer $deep
     * @return void
     */
    protected static function validateExtend($deep = 1)
    {
        if (!static::isExtended()) {
            $fnName = debug_backtrace()[$deep]['function'] ?? '';
            throw new Error("You can use {$fnName} method only from parent class");
        }
    }


    /**
     * - Получает сущность к которой привязана модель
     * @throws \Error
     * 
     * @return Entity
     */
    static function getModelEntity()
    {
        static::validateExtend(2);
        foreach (Container::getLoader()->getEntities() as $entity) {
            if ($entity->getModelClass() === get_called_class()) {
                return $entity;
            }
        }
        $className = get_called_class();
        throw new Error("Cannot get entity of {$className}");
    }


    /**
     * - Проверяет является ли вызванный класс любым отличным от ModelPostType или ModelTaxonomy
     * @return boolean
     */
    protected static function isExtended(): bool
    {
        $className = get_called_class();
        if ($className === ModelPostType::class || $className === ModelTaxonomy::class) {
            return false;
        }
        return true;
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


    function getKey(): string
    {
        return $this->getRightField('post_type', 'taxonomy', '');
    }


    function getEntity(): ?Entity
    {
        return Container::getLoader()->getEntity($this->getKey());
    }


    protected function fetchCache(callable $fn, string $key = '', int $ttl = 0)
    {
        $callFrom = debug_backtrace()[1]['function'] ?? '';
        $fullKey = get_called_class() . ':' . $callFrom;
        if ($key) {
            $fullKey .= ':' . $key;
        }
        $fullKey .= ':' . $this->getId();
        return Container::getLoader()->fetchCache($fullKey, $fn, $ttl);
    }
}
