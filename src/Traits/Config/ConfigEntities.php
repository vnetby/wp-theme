<?php

namespace Vnetby\Wptheme\Traits\Config;

use Error;
use Vnetby\Wptheme\Entities\Entity;
use Vnetby\Wptheme\Entities\EntityCategory;
use Vnetby\Wptheme\Entities\EntityPage;
use Vnetby\Wptheme\Entities\EntityPost;
use Vnetby\Wptheme\Entities\EntityTag;
use Vnetby\Wptheme\Entities\EntityTaxonomy;
use Vnetby\Wptheme\Entities\PostType;
use Vnetby\Wptheme\Entities\Taxonomy;
use Vnetby\Wptheme\Models\Model;
use Vnetby\Wptheme\Models\Post;
use Vnetby\Wptheme\Models\Term;

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
     * - Регестрирует сущность
     * - Порядок регистрации не имеет значение, автоматически определится это таксономия или тип поста
     * @param string $key Уникальный ключ сущности
     *   в случае с таксономией - это таксономия
     *   в случае с типом поста - это тип поста
     * @param class-string<Entity> $entityClass класс сущности
     * 
     * @return static
     */
    function registerEntity(string $key, string $entityClass)
    {
        $postTypes = get_post_types();
        $taxes = get_taxonomies();

        if (isset($taxes[$key]) || isset($this->entitiesTax[$key])) {
            throw new Error("Taxonomy {$key} exists");
        }

        if (isset($postTypes[$key]) || isset($this->entitiesPosts[$key])) {
            throw new Error("Post type {$key} exists");
        }

        if ($entityClass === EntityTaxonomy::class || is_subclass_of($entityClass, EntityTaxonomy::class)) {
            $this->entitiesTax[$key] = $entityClass;
        } else {
            $this->entitiesPosts[$key] = $entityClass;
        }
        return $this;
    }


    /**
     * - Получает текущую сущность
     * @return Entity|null
     */
    function getCurrentEntity(): ?Entity
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
        if ($term = get_queried_object()) {
            return $term->taxonomy;
        }
        return '';
    }


    /**
     * - Получает текущий элемент сущности
     * @return Post|Term|null
     */
    function getCurrentEntityElement()
    {
        if ($entity = $this->getCurrentEntity()) {
            return $entity->getCurrentElement();
        }
        return null;
    }


    function getEntity(string $key): ?Entity
    {
        return $this->entities[$key] ?? null;
    }


    /**
     * - Получает все зарегестрированные сущности
     * @return Entity[]
     */
    function getEntities(): array
    {
        return $this->entities;
    }


    protected function setupEntities()
    {
        foreach ($this->entitiesTax as $key => $className) {
            $this->entities[$key] = new $className($key);
        }
        foreach ($this->entitiesPosts as $key => $className) {
            $this->entities[$key] = new $className($key);
        }
        foreach ($this->entities as $key => $entity) {
            $entity->setup();
        }
    }
}
