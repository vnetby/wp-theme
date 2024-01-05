<?php

namespace Vnetby\Wptheme\Traits\Config;

use Vnetby\Wptheme\Entities\Entity;
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
     * @var array<string,class-string<Entity>>
     */
    protected array $entitiesClasses = [
        'post' => PostType::class,
        'page' => PostType::class,
        'category' => Taxonomy::class,
        'post_tag' => Taxonomy::class
    ];


    /**
     * - Регестрирует сущность
     *
     * @param string $key Уникальный ключ сущности
     *   в случае с таксономией - это таксономия
     *   в случае с типом поста - это тип поста
     * @param class-string<Entity> $entityClass класс сущности
     * 
     * @return static
     */
    function registerEntity(string $key, string $entityClass)
    {
        $this->entitiesClasses[$key] = $entityClass;
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


    protected function setupEntities()
    {
        foreach ($this->entitiesClasses as $key => $className) {
            $this->entities[$key] = new $className($key);
        }
        foreach ($this->entities as $key => $entity) {
            $entity->setup();
        }
    }
}
