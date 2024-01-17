<?php

namespace Vnetby\Wptheme\Traits\Config;

use Error;
use Vnetby\Wptheme\Entities\Base\Entity;
use Vnetby\Wptheme\Entities\EntityCategory;
use Vnetby\Wptheme\Entities\EntityPage;
use Vnetby\Wptheme\Entities\EntityPost;
use Vnetby\Wptheme\Entities\EntityTag;

trait ConfigEntities
{

    /**
     * - Массив зарегестрированных сущностей
     * @var array<string,Entity>
     */
    protected array $entities = [];

    /**
     * - Классы зарегестрированных сущностей
     * @var array<string,class-string<Taxonomy>>
     */
    protected array $entitiesTax = [
        'category' => EntityCategory::class,
        'post_tag' => EntityTag::class
    ];

    /**
     * - Классы зарегестрированных сущностей
     * @var array<string,class-string<PostType>>
     */
    protected array $entitiesPosts = [
        'post' => EntityPost::class,
        'page' => EntityPage::class
    ];

    /**
     * - Классы не установленных типов сущностей
     * @var array<string,class-string<PostType>>
     */
    protected array $entitiesUndefied = [];


    /**
     * - Регестрирует сущность
     * - Порядок регистрации не имеет значение, автоматически определится это таксономия или тип поста
     * @param class-string<Entity> $entityClass класс сущности
     * @throws Error
     * 
     * @return static
     */
    function registerEntity(string $entityClass)
    {
        if (!$entityClass::KEY) {
            throw new Error("Missing KEY constant in {$entityClass}");
        }

        if (!$entityClass::CLASS_ADMIN) {
            throw new Error("Missing CLASS_ADMIN constant in {$entityClass}");
        }

        $key = $entityClass::KEY;

        $postTypes = get_post_types();
        $taxes = get_taxonomies();

        if (isset($taxes[$key]) && !isset($this->entitiesTax[$key])) {
            throw new Error("Taxonomy {$key} exists");
        }

        if (isset($postTypes[$key]) && !isset($this->entitiesPosts[$key])) {
            throw new Error("Post type {$key} exists");
        }

        if ($entityClass::isTaxonomy()) {
            $this->entitiesTax[$key] = $entityClass;
        } else if ($entityClass::isPostType()) {
            $this->entitiesPosts[$key] = $entityClass;
        } else {
            $this->entitiesUndefied[$key] = $entityClass;
        }

        return $this;
    }


    /**
     * - Получает текущую сущность
     * @return ?class-string<Entity>
     */
    function getCurrentEntityClass(): ?string
    {
        if ($key = $this->getCurrentEntityKey()) {
            return $this->entities[$key] ?? null;
        }
        return null;
    }


    /**
     * - Получает уникальный ключ текущей сущности
     * - Если это страница термина - вернет таксономию
     * - Если это страница поста - вернет тип поста
     *
     * @return string
     */
    function getCurrentEntityKey(): string
    {
        if (is_singular() || is_front_page()) {
            return get_post_type();
        }
        if (is_tax()) {
            if ($term = get_queried_object()) {
                return $term->taxonomy;
            }
            return '';
        }
        if (is_archive()) {
            return $GLOBALS['wp_query']->query['post_type'];
        }
        return '';
    }


    /**
     * - Получает текущий элемент сущности
     * @return ?Entity
     */
    function getCurrentEntityElement()
    {
        if ($class = $this->getCurrentEntityClass()) {
            return $class::getCurrent();
        }
        return null;
    }


    /**
     * @return ?class-string<Entity>
     */
    function getEntityClass(string $key): ?string
    {
        return $this->entities[$key] ?? null;
    }


    /**
     * - Получает все зарегестрированные сущности
     * @return class-string<Entity>[]
     */
    function getEntities(): array
    {
        return $this->entities;
    }


    protected function setupEntities()
    {
        foreach ($this->entitiesTax as $key => $className) {
            $this->entities[$key] = $className;
            $className::setup($className::getAdmin());
        }
        foreach ($this->entitiesPosts as $key => $className) {
            $this->entities[$key] = $className;
            $className::setup($className::getAdmin());
        }
        foreach ($this->entitiesUndefied as $key => $className) {
            $this->entities[$key] = $className;
            $className::setup($className::getAdmin());
        }
    }
}
