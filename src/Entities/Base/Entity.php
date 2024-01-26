<?php

namespace Vnetby\Wptheme\Entities\Base;

use Vnetby\Schemaorg\Types\Type;
use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Entities\Admin\Admin;
use Vnetby\Wptheme\Entities\Admin\AdminPostType;
use Vnetby\Wptheme\Traits\CacheClass;

abstract class Entity
{
    const CLASS_ADMIN = '';
    const KEY = '';

    use CacheClass;

    /**
     * @var Admin
     */
    private static $adminInstances = [];

    protected $wpItem;

    protected function __construct($wpItem)
    {
        $this->wpItem = $wpItem;
    }

    /**
     * - Инициализирует сущность в wp
     * @param Admin $admin
     */
    static function setup($admin)
    {
        $admin->register();
    }

    /**
     * - Выполняет фильтрацию по элементам
     * @return DbResult<static>
     */
    abstract static function filter(array $filter = [], int $page = 1, int $perPage = -1);

    /**
     * @return Admin
     */
    static function getAdmin()
    {
        $class = get_called_class();
        if (!isset(self::$adminInstances[$class])) {
            $adminClass = static::CLASS_ADMIN;
            self::$adminInstances[$class] = new $adminClass($class);
        }
        return self::$adminInstances[$class];
    }

    static function isEditPage(): bool
    {
        if (static::isTaxonomy()) {
            if (empty($_GET['post_type']) || empty($_GET['taxonomy'])) {
                return false;
            }
            return is_admin() && $_GET['taxonomy'] === static::getKey();
        }
        if (static::isPostType()) {
            return is_admin() && !empty($_GET['post_type']) && $_GET['post_type'] === static::getKey();
        }
        return false;
    }

    /**
     * - Получает текущий элемент
     * - Работает только на странице детального просмотра
     * @return ?static
     */
    static function getCurrent()
    {
        if (static::isPostType() && static::isSingle()) {
            if ($post = get_post()) {
                return static::getByWpItem($post);
            }
            return null;
        }
        if (static::isTaxonomy() && static::isSingle()) {
            $obj = get_queried_object();
            if ($obj && $obj instanceof \WP_Term) {
                return static::getByWpItem($obj);
            }
            return null;
        }
        return null;
    }

    /**
     * - Получает элемент по ID
     * @param integer $id
     * @return ?static
     */
    static function getById(int $id)
    {
        return static::fetchCache(function () use ($id) {
            if (static::isPostType()) {
                $post = get_post($id);
                return $post && !is_wp_error($post) ? static::getByWpItem($post) : null;
            }
            if (static::isTaxonomy()) {
                $term = get_term_by('id', $id, static::getKey());
                return $term && !is_wp_error($term) ? static::getByWpItem($term) : null;
            }
            return null;
        });
    }

    /**
     * - Получает по объекту элемента wordpress
     * @param \WP_Post|\WP_Term $wpItem
     * @return static
     */
    static function getByWpItem($wpItem)
    {
        return static::fetchCache(function () use ($wpItem) {
            return new static($wpItem);
        });
    }

    static function getArchiveSeoTitle(): string
    {
        if (static::isPostType()) {
            return Container::getSeo()->getArchiveTitle(static::getKey());
        }
        return '';
    }

    static function getArchiveSeoDesc(): string
    {
        if (static::isPostType()) {
            return Container::getSeo()->getArchiveDesc(static::getKey());
        }
        return '';
    }

    static function getArchiveSeoImageId(): int
    {
        if (static::isPostType()) {
            return Container::getSeo()->getArchiveImageId(static::getKey());
        }
        return 0;
    }

    static function getArchiveSeoImage(): string
    {
        if (static::isPostType()) {
            return Container::getSeo()->getArchiveImage(static::getKey());
        }
        return '';
    }

    /**
     * - Получает хлебные крошки архива
     * @return array<array{
     *      url: string,
     *      label: string
     * }>
     */
    static function getArchiveSeoBreadcrumbs(): array
    {
        if (static::isPostType()) {
            return Container::getSeo()->getArchiveBreadcrumbs(static::getKey());
        }
        return [];
    }

    /**
     * @return Type[]|Type|null
     */
    static function getCurrentArchiveSchemaType()
    {
        if (!is_archive()) {
            return null;
        }
        return Container::getSeo()->getCurrentArchiveSchemaType(static::getKey());
    }

    protected static function getWpDb(): \wpdb
    {
        return $GLOBALS['wpdb'];
    }

    /**
     * - Проверяет является ли данная сущность, сущностью типа поста
     * @return boolean
     */
    static function isPostType(): bool
    {
        $class = get_called_class();
        return $class instanceof EntityPostType || is_subclass_of($class, EntityPostType::class);
    }

    /**
     * - Проверяет является ли данныя сущность, сущностью таксономии
     * @return boolean
     */
    static function isTaxonomy(): bool
    {
        $class = get_called_class();
        return $class instanceof EntityTaxonomy || is_subclass_of($class, EntityTaxonomy::class);
    }

    static function urlArchive(): string
    {
        if (static::isPostType()) {
            $url = get_post_type_archive_link(static::getKey());
            return $url ? $url : '';
        }
        if (static::isTaxonomy()) {
            $postType = static::getMainPostType();
            if (!$postType) {
                return '';
            }
            if ($entityClass = Container::getLoader()->getEntityClass($postType)) {
                return $entityClass::urlArchive();
            }
        }
        return '';
    }

    static function labelArchive(): string
    {
        if (static::isPostType()) {
            return static::getAdmin()->getLabel();
        }
        if (static::isTaxonomy()) {
            $postType = static::getMainPostType();
            if (!$postType) {
                return '';
            }
            if ($entity = Container::getLoader()->getEntityClass($postType)) {
                return $entity::getAdmin()->getLabel();
            }
            return '';
        }
        return '';
    }

    /**
     * - Если это таксономия - вернет первый привязанный тип поста
     */
    static function getMainPostType(): string
    {
        if (!static::isTaxonomy()) {
            return '';
        }
        return static::getAdmin()->getObjectType()[0] ?? '';
    }

    /**
     * - Получает уникальный ключ сущности
     * @return string
     */
    static function getKey(): string
    {
        return static::KEY;
    }

    /**
     * - Явлаяется ли страница архивом текущего типа поста или таксономии
     * - Архивом может быть только тип поста
     * @return boolean
     */
    static function isArchive(): bool
    {
        if (static::isPostType()) {
            return is_post_type_archive(static::getKey());
        }
        return false;
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
        return $this->setField(static::isPostType() ? $postField : $termField, $value);
    }


    function getField(string $key, $def = null)
    {
        return $this->wpItem->$key ?? $def;
    }


    private function getRightField(string $postField, string $termField, $def = null)
    {
        return $this->getField(static::isPostType() ? $postField : $termField, $def);
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
        return static::isPostType() ? get_permalink($this->wpItem) : get_term_link($this->wpItem, $this->wpItem->taxonomy);
    }


    function getEditUrl(): string
    {
        return static::isPostType() ? get_edit_post_link($this->wpItem) : get_edit_term_link($this->wpItem);
    }


    function getContent(): string
    {
        return $this->getRightField('post_content', 'description', '');
    }


    function getMeta(string $key, bool $single = true)
    {
        if (static::isPostType()) {
            return get_post_meta($this->getId(), $key, $single);
        }
        if (static::isTaxonomy()) {
            return get_term_meta($this->getId(), $key, $single);
        }
        return null;
    }


    function getAcfField(string $selector, $def = null)
    {
        if (!function_exists('get_field')) {
            return $def;
        }
        $res = get_field($selector, $this->wpItem, true);
        return $res !== null ? $res : $def;
    }


    /**
     * @return \WP_Post|\WP_Term
     */
    function getWpItem()
    {
        return $this->wpItem;
    }


    function getOrder(): int
    {
        if (static::isTaxonomy()) {
            $val = get_term_meta($this->getId(), 'tax_position', true);
            return $val ? (int)$val : 0;
        }
        if (static::isPostType()) {
            return (int)$this->wpItem->menu_order;
        }
        return 0;
    }


    function updateMeta(string $key, $value, $prevValue = '')
    {
        if (static::isPostType()) {
            return update_post_meta($this->getId(), $key, $value, $prevValue);
        }
        if (static::isTaxonomy()) {
            return update_term_meta($this->getId(), $key, $value, $prevValue);
        }
        return false;
    }


    function deleteMeta(string $key, $value = '')
    {
        if (static::isPostType()) {
            return delete_post_meta($this->getId(), $key, $value);
        }
        if (static::isTaxonomy()) {
            return delete_term_meta($this->getId(), $key, $value);
        }
        return false;
    }

    /**
     * - Является ли текущая страница детальной страницей элемента
     * @return boolean
     */
    static function isSingle(): bool
    {
        if (static::isPostType()) {
            return is_singular(static::getKey());
        }
        if (static::isTaxonomy()) {
            return is_tax(static::getKey());
        }
        return false;
    }

    /**
     * - Получает СЕО заголовок текущего элемента
     */
    function getSeoTitle(): string
    {
        if (static::isPostType()) {
            return Container::getSeo()->getPostTitle($this->getId());
        }
        if (static::isTaxonomy()) {
            return Container::getSeo()->getTermTitle($this->getId());
        }
        return '';
    }

    /**
     * - Получает СЕО описание данного элемента
     */
    function getSeoDesc(): string
    {
        if (static::isPostType()) {
            return Container::getSeo()->getPostDesc($this->getId());
        }
        if (static::isTaxonomy()) {
            return Container::getSeo()->getTermDesc($this->getId());
        }
        return '';
    }

    /**
     * - Получает ID сео картинки данного элемента
     */
    function getSeoImageId(): int
    {
        if (static::isPostType()) {
            return Container::getSeo()->getPostImageId($this->getId());
        }
        if (static::isTaxonomy()) {
            return Container::getSeo()->getTermImageId($this->getId());
        }
        return 0;
    }

    /**
     * - Получает картинку для сео данного элемента
     */
    function getSeoImage(): string
    {
        if (static::isPostType()) {
            return Container::getSeo()->getPostImage($this->getId());
        }
        if (static::isTaxonomy()) {
            return Container::getSeo()->getTermImage($this->getId());
        }
        return '';
    }

    /**
     * - Получает тип schema.org данного элемента
     * @return Type[]|Type|null
     */
    function getSeoSchemaType()
    {
        if (static::isPostType()) {
            return Container::getSeo()->getPostSchemaType($this->getId());
        }
        return null;
    }

    /**
     * - Получает навигационную цепочку элемента
     * @return array<array{
     *      url: string,
     *      label: string
     * }>
     */
    function getSeoBreadcrumbs(): array
    {
        if (static::isPostType()) {
            return Container::getSeo()->getPostBreadcrumbs($this->getId());
        }
        if (static::isTaxonomy()) {
            return Container::getSeo()->getTermBreadcrumbs($this->getId());
        }
        return [];
    }

    function getCanonicalUrl(): string
    {
        return '';
    }
}
