<?php

namespace Vnet\Types;

use Vnet\Helpers\Acf;
use Vnet\Helpers\ArrayHelper;
use Vnet\Helpers\DB;
use Vnet\Helpers\HelperFn;

class PostType extends Core
{

    /**
     * @var null|\WP_Post_Type
     */
    protected $postType = null;


    /**
     * @see https://wp-kama.ru/function/register_post_type
     * @param array $params 
     */
    protected function register(array $params)
    {
        add_action('init', function () use ($params) {
            $postType = register_post_type($this->getSlug(), $this->filterParams($params));
            if (!is_wp_error($postType)) {
                $this->postType = $postType;
            }
        });
    }


    private function filterParams(array $params): array
    {
        $defLabels = [
            'name' => 'Элементы',
            'singular_name' => 'Элемент',
            'add_new' => 'Добавить элемент',
            'add_new_item' => 'Добавление элемента',
            'edit_item' => 'Редактирование элемента',
            'new_item' => 'Новый элемент',
            'view_item' => 'Смотреть элемент',
            'search_items' => 'Искать элемент',
            'not_found' => 'Не найдено',
            'not_found_in_trash' => 'Не найдено в корзине',
            'parent_item_colon' => 'Родительский элемент:',
            'menu_name' => 'Элементы'
        ];

        $labels = $params['labels'] ?? [];

        if (empty($params['label'])) {
            $params['label'] = 'Элементы';
        }

        if (empty($labels['name'])) {
            $labels['name'] = $params['label'];
        }

        if (empty($labels['menu_name'])) {
            $labels['menu_name'] = $params['label'];
        }

        $labels = array_merge($defLabels, $labels);

        $params['labels'] = $labels;

        $params['public'] = $params['public'] ?? true;
        $params['has_archive'] = $params['has_archive'] ?? true;
        $params['query_var'] = $params['query_var'] ?? false;
        $params['supports'] = $params['supports'] ?? ['title', 'thumbnail', 'excerpt', 'editor'];

        return $params;
    }


    /**
     * - Добавляет роут для страниц данного типа поста
     * - Для роута отдельной страницы должен присутствовать обязательный параметр name
     * с регулярным выражением: ([^/]+)
     * @param string $regex регулярное вырожение которому должна соответствовать ссылка
     * @param string[] $queryVars мыссив урл параметров. Можно будет получить через get_query_var()
     * порядок должен соответстовать порядку группам в регулярном выражении
     * @param string $after 
     * @return void 
     */
    protected function addRoute(string $regex, array $queryVars, $after = 'top')
    {
        add_action('init', function () use ($regex, $queryVars, $after) {
            $query = '';

            foreach ($queryVars as $i => $var) {
                $query .= '&' . $var . '=$matches[' . ($i + 1) . ']';
            }

            $url = 'index.php?post_type=' . $this->getSlug() . $query;

            add_rewrite_rule($regex, $url, $after);
        });

        add_filter('query_vars', function ($vars) use ($queryVars) {
            $vars = array_merge($vars, $queryVars);
            return $vars;
        });
    }


    /**
     * - Заголовок будет генерироваться автоматически
     * @param callable $getTitle функция для получения заголовка
     * @return void 
     */
    protected function autoTitle(callable $getTitle)
    {
        $this->theAdminStyle("
            body.post-type-{$this->slug} #titlediv #titlewrap {
                display: none!important;
            }
        ");

        $this->theAdminScript("
            let title = document.createElement('h1');
            title.innerHTML = jQuery('body.post-type-{$this->slug} #titlediv #titlewrap input[type=\"text\"]').val();
            jQuery('body.post-type-{$this->slug} #titlediv #titlewrap').before(title);
        ");

        add_filter('wp_insert_post_data', function ($data) use ($getTitle) {
            if ($data['post_type'] !== $this->slug) {
                return $data;
            }
            $data['post_title'] = call_user_func($getTitle, $data);
            return $data;
        }, 99);
    }


    protected function addImgColumn(string $position = 'title', string $label = 'Картинка', ?callable $callback = null)
    {
        if ($callback === null) {
            $callback = function ($postId) {
                if ($img = get_the_post_thumbnail_url($postId)) {
                    echo '<img src="' . $img . '" style="width: 120px;">';
                }
            };
        }
        $this->addColumn('image', $label, $callback, $position);
    }


    /**
     * - Добавляет колонку на странице списка терминов
     * @param string $colKey уникальный ключ новой колонки
     * @param string $colLabel Название колонки
     * @param callable $getValCallback функция для получения значения ячейки
     * @param string $position либо ключ колонки после которой вставить текущую, либо first|last
     */
    protected function addColumn(string $colKey, string $colLabel, callable $getValCallback, string $position = 'last')
    {
        add_filter("manage_{$this->slug}_posts_columns", function (array $columns) use ($colKey, $position, $colLabel) {
            if ($position === 'last') {
                $position = array_keys($columns)[count(array_keys($columns)) - 1];
            } else if ($position === 'first') {
                $position = 'cb';
            }
            $columns = ArrayHelper::insert($columns, $position, [$colKey => $colLabel]);
            return $columns;
        });
        add_action("manage_{$this->slug}_posts_custom_column", function ($column, $postId) use ($colKey, $getValCallback) {
            if ($column === $colKey) {
                echo call_user_func($getValCallback, (int)$postId);
            }
        }, 10, 3);
    }


    /**
     * - Удаляет колонку
     * @param string $colKey 
     * @return void 
     */
    protected function removeColumn(string $colKey)
    {
        add_filter("manage_{$this->slug}_posts_columns", function (array $columns) use ($colKey) {
            if (isset($columns[$colKey])) {
                unset($columns[$colKey]);
            }
            return $columns;
        });
    }


    protected function sortableColumn(string $colKey, callable $sortFunction)
    {
        add_filter("manage_edit-{$this->slug}_sortable_columns", function ($columns) use ($colKey) {
            $columns[$colKey] = $colKey;
            return $columns;
        });

        if (is_admin()) {
            add_action('pre_get_posts', function (\WP_Query $query) use ($colKey, $sortFunction) {
                if (!$query->is_main_query() || $query->query['post_type'] !== $this->getSlug() || $query->get('orderby') !== $colKey) {
                    return;
                }
                call_user_func($sortFunction, $query);
            });
        }
    }


    /**
     * - Устанавливает сортировку по умолчанию
     * @param callable $sortFunction 
     * @return void 
     */
    protected function defaultSort(callable $sortFunction)
    {
        if (is_admin()) {
            $this->setQuerySort($sortFunction);
        }
    }


    protected function defaultFrontSort(callable $sortFunction)
    {
        if (!is_admin()) {
            $this->setQuerySort($sortFunction);
        }
    }


    private function setQuerySort(callable $sortFunction)
    {
        add_action('pre_get_posts', function (\WP_Query $query) use ($sortFunction) {
            if (!$query->is_main_query() || empty($query->query['post_type']) || $query->query['post_type'] !== $this->getSlug() || !!$query->get('orderby')) {
                return;
            }
            call_user_func($sortFunction, $query);
        });
    }


    /**
     * - Добавляет фильтр на странице списка в админке
     * @param callable $drawCallback функция которая отрисовывает элемент
     * @param callable $filterCallback функция которая изменяет WP_Query (добавляет параметры фильтра)
     * @return void 
     */
    protected function addAdminFilter(callable $drawCallback, callable $filterCallback)
    {
        add_action('restrict_manage_posts', function () use ($drawCallback) {
            global $pagenow;
            if (!is_admin() || $pagenow !== 'edit.php' || $_GET['post_type'] !== $this->slug) {
                return;
            }
            call_user_func($drawCallback);
        });

        add_filter('parse_query', function (\WP_Query $query) use ($filterCallback) {
            global $pagenow;
            if (!is_admin() || $pagenow !== 'edit.php' || $_GET['post_type'] !== $this->slug || !$query->is_main_query()) {
                return;
            }
            call_user_func($filterCallback, $query);
        });
    }


    protected function removeAdminFilter(string $filterId)
    {
        $this->theAdminStyle("
            .tablenav #{$filterId} {
                display: none!important;
            }
        ");
    }


    protected function menuColorOrange()
    {
        $this->menuColor('orange');
    }


    protected function menuColorGreen()
    {
        $this->menuColor('green');
    }


    /**
     * - Устанавливает цвет пункту меню
     * @param string $color orange|green
     */
    protected function menuColor(string $color)
    {
        $this->addMenuClass('theme-menu-item', "c-{$color}");
    }


    protected function menuAwesomeIco(string $ico)
    {
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');
        });

        $this->addMenuClass('theme-menu-item', 'awesome-icon');

        $this->theAdminStyle("
            .theme-menu-item.awesome-icon#menu-posts-{$this->slug} .wp-menu-image {
                font-family: \"Font Awesome 5 Free\";
            }
            .theme-menu-item.awesome-icon#menu-posts-{$this->slug} .wp-menu-image:before {
                content: \"\\{$ico}\";
                font-family: inherit;
                font-weight: bold;
            }
        ");
    }


    /**
     * - Скрывает создание и список
     * - Перенаправляет на страницу таксономии
     * @param string $tax
     */
    protected function groupTaxonomies(string $tax)
    {
        $url = '/wp-admin/edit-tags.php?taxonomy=' . $tax . '&post_type=' . $this->slug;

        // скрываем пункты меню
        $this->theAdminStyle("
            #menu-posts-{$this->slug} .wp-submenu .wp-first-item,
            #menu-posts-{$this->slug} .wp-submenu .wp-first-item + li {
                display: none!important;
            }
        ");

        // меняем основную ссылку
        $this->theAdminScript("
            jQuery('#menu-posts-{$this->slug} a').first().attr('href', '{$url}');
        ");

        // выполняем редирект если пользователь попал на скрытые страницы

        $file = basename($_SERVER['SCRIPT_NAME']);

        if (empty($_GET['post_type']) || !in_array($file, ['edit.php', 'post-new.php'])) {
            return;
        }

        if ($_GET['post_type'] !== $this->slug) {
            return;
        }

        wp_redirect($url);

        exit;
    }


    /**
     * - Устанавливает кол-во постов на странице архива на фронте
     * @param int $perPage
     */
    function perPage(int $perPage)
    {
        add_filter('pre_get_posts', function (\WP_Query $query) use ($perPage) {
            if (!$query->is_post_type_archive($this->getSlug()) || is_admin() || !$query->is_main_query()) {
                return $query;
            }
            $query->set('posts_per_page', $perPage);
        });
    }


    protected function filterExcerpt(callable $fn, int $priority = 10)
    {
        add_filter('get_the_excerpt', function (string $exerpt, \WP_Post $post) use ($fn) {
            if ($post->post_type !== $this->getSlug()) {
                return $exerpt;
            }
            return call_user_func($fn, $exerpt, $post);
        }, $priority, 2);
    }


    protected function filterTitle(callable $fn, int $priority = 10)
    {
        if (is_admin()) {
            return;
        }
        add_filter('the_title', function (string $title, int $postId) use ($fn) {
            if (get_post_type($postId) !== $this->getSlug()) {
                return $title;
            }
            return call_user_func($fn, $title, $postId);
        }, $priority, 2);
    }


    protected function filterPermalink(callable $fn, int $priority = 10)
    {
        if (is_admin()) {
            return;
        }
        add_filter('post_type_link', function (string $permalink, \WP_Post $post, bool $leavename) use ($fn) {
            if ($post->post_type !== $this->getSlug()) {
                return $permalink;
            }
            return call_user_func($fn, $permalink, $post, $leavename);
        }, $priority, 3);
    }


    /**
     * - Добавляет метабокс
     * @param string $id 
     * @param string $title 
     * @param callable $drawCallback 
     * @param callable $updateCallback 
     * @param string $context 
     * @param string $priority 
     */
    protected function addMetabox(string $id, string $title, callable $drawCallback, callable $updateCallback = null, string $context = 'advanced', string $priority = 'default')
    {
        add_action('add_meta_boxes', function () use ($id, $title, $drawCallback, $context, $priority) {
            add_meta_box($id, $title, $drawCallback, $this->getSlug(), $context, $priority);
        });

        if ($updateCallback) {
            add_action('save_post_' . $this->getSlug(), function (int $postId, \WP_Post $post, bool $update) use ($updateCallback) {
                call_user_func($updateCallback, $postId, $post, $update);
            }, 10, 3);
        }
    }


    /**
     * - Хук после обновления элемента
     * @see https://wp-kama.com/hook/post_updated
     * @param callable $callback 
     * @return void 
     */
    protected function onUpdated(callable $callback)
    {
        add_action('post_updated', function (int $postId, \WP_Post $postAfter, \WP_Post $postBefore) use ($callback) {
            if ($postAfter->post_type === $this->getSlug()) {
                call_user_func($callback, $postId, $postAfter, $postBefore);
            }
        }, 10, 3);
    }


    /**
     * - Срабатывает всякий раз, когда запись (пост, страница) создается или обновляется
     * @see https://wp-kama.ru/hook/save_post
     * @param callable $callback 
     * @return void 
     */
    protected function onSave(callable $callback)
    {
        $slug = $this->getSlug();
        add_action("save_post_{$slug}", $callback, -1, 3);
    }


    /**
     * - Срабатывает после обновления полей ACF
     * @see https://www.advancedcustomfields.com/resources/acf-save_post/
     * @param callable $callback 
     * @return void 
     */
    protected function onAcfSave(callable $callback)
    {
        add_action('acf/save_post', function ($postId) use ($callback) {
            if (!is_numeric($postId)) {
                return;
            }
            if (get_post_type($postId) !== $this->getSlug()) {
                return;
            }
            call_user_func($callback, $postId);
        }, 10, 1);
    }


    /**
     * - Добаляет доп классы строке элемента в таблице списка
     * @param callable $callback должен вернуть массив строк
     * @return void 
     */
    protected function rowClass(callable $callback)
    {
        add_filter('post_class', function (array $classes, array $class, int $postId) use ($callback) {
            if (!is_admin()) {
                return $classes;
            }
            $screen = get_current_screen();
            if ($screen->post_type !== $this->getSlug() || $screen->base !== 'edit') {
                return $classes;
            }
            return array_merge($classes, call_user_func($callback, $postId));
        }, 10, 3);
    }


    /**
     * - Должен быть вызван вначале!
     * - Устанавливает значение колонки в таблице posts из значения ACF поля
     * @param string $acfName название ACF поля
     * @param string $postField название колонки из таблицы posts
     * @return void 
     */
    protected function acfToPostField(string $acfName, string $postField)
    {
        $postField = strtolower($postField);

        $allowFields = [
            'post_title',
            'post_author',
            'post_content',
            'post_excerpt'
        ];

        if (!in_array($postField, $allowFields)) {
            return;
        }

        add_filter("acf/load_value/name={$acfName}", function ($value, int $postId) use ($postField) {
            if (get_post_type($postId) !== $this->getSlug()) {
                return $value;
            }
            $post = get_post($postId);
            if (!$post || !property_exists($post, $postField)) {
                return $value;
            }
            return $post->$postField ?: $value;
        }, 10, 2);

        add_filter("acf/update_value/name={$acfName}", function ($value, int $postId) use ($acfName, $postField) {
            if (get_post_type($postId) !== $this->getSlug()) {
                return $value;
            }
            if (is_string($value) || is_numeric($value)) {
                $GLOBALS['acf_top_post_field_' . $postField . '_' . $acfName . '_' . $postId] = $value;
                return null;
            }
            return $value;
        }, 10, 2);

        $this->onAcfSave(function (int $postId) use ($acfName, $postField) {
            if (get_post_type($postId) !== $this->getSlug()) {
                return;
            }
            $value = $GLOBALS['acf_top_post_field_' . $postField . '_' . $acfName . '_' . $postId] ?? '';
            wp_update_post(['ID' => $postId, $postField => $value]);
            if (isset($GLOBALS['acf_top_post_field_' . $postField . '_' . $acfName . '_' . $postId])) {
                unset($GLOBALS['acf_top_post_field_' . $postField . '_' . $acfName . '_' . $postId]);
            }
        });
    }


    /**
     * - Должен быть вызван самым первым!
     * - Сохраняет и получает значения всех переданных ACF полей в колонке post_content_filtered
     *   таблицы wp_posts, тем самым уменьшая кол-во записей в таблице postmeta.
     * - Данный метод можно применять только для ACF полей которые никак не участвуют в выборке постов из БД
     *   например сео описание или сео заголовок.
     * @param string $acfNames имя или ключи полей
     * @return void 
     */
    protected function acfToPostContentFiltered(string ...$acfNames)
    {
        $this->onAcfSave(function (int $postId) use ($acfNames) {
            $acfValues = [];
            $globalKey = 'prevent_acf_load_value_filter_' . $postId;
            $GLOBALS[$globalKey] = true;

            foreach ($acfNames as $acfName) {
                $field = acf_get_field($acfName);

                if (!$field) {
                    continue;
                }

                $acfName = $field['name'];

                $acfValues[$field['key']] = get_field($field['key'], $postId, false);

                delete_field($field['key'], $postId);
            }

            wp_update_post([
                'ID' => $postId,
                'post_content_filtered' => serialize($acfValues)
            ]);

            unset($GLOBALS[$globalKey]);
        });

        foreach ($acfNames as $acfName) {
            $hook = preg_match("/^acf_/", $acfName) ? 'key=' . $acfName : 'name=' . $acfName;

            add_filter("acf/load_value/{$hook}", function ($value, int $postId, array $field) use ($acfName) {
                $globalKey = 'prevent_acf_load_value_filter_' . $postId;

                if (get_post_type($postId) !== $this->getSlug() || !empty($GLOBALS[$globalKey])) {
                    return $value;
                }

                $acfKey = $field['key'];
                $filter = false;

                // если не переадны данные поля - значит запрос с фронта
                if (!$acfKey) {
                    $filter = true;
                    $acfKey = acf_get_field($acfName)['key'];
                }

                $post = get_post($postId);

                $content = $post->post_content_filtered ? unserialize($post->post_content_filtered) : [];

                $resVal = isset($content[$acfKey]) ? $content[$acfKey] : $value;

                if ($filter) {
                    return Acf::formatAcfValue($resVal);
                }

                return $resVal;
            }, 10, 3);
        }
    }


    protected function filterArchiveMainQuery(callable $callback)
    {
        add_action('pre_get_posts', function (\WP_Query $query) use ($callback) {
            if (!$query->is_main_query() || !$query->is_post_type_archive($this->getSlug())) {
                return;
            }
            HelperFn::execCallback($callback, $query);
        });
    }


    // /**
    //  * - Сохраняет метаданные в другую таблицу БД
    //  * - Название БД сосотит из названия типа поста
    //  * @param array $metaKeys 
    //  * @return static 
    //  */
    // protected function metaToTable(string $metaKey, string $sqlType = "STRING", $default = null, $index = false): self
    // {
    //     global $wpdb;

    //     $table = $wpdb->prefix . 'posts_' . $this->getSlug() . '_meta';

    //     if (is_admin() && !DB::tableExists($table)) {
    //         DB::createTable(
    //             $table,
    //             "`post_id` BIGINT UNSIGNED NOT NULL"
    //         )::addColIndex(
    //             $table,
    //             'post_id'
    //         );
    //     }

    //     if (is_admin() && !DB::columnExists($table, $metaKey)) {
    //         if ($default === null) {
    //             $sqlType .= " NULL";
    //         } else {
    //             $sqlType .= " NOT NULL DEFAULT {$default}";
    //         }
    //         DB::addColumn($table, $metaKey, $sqlType);
    //         if ($index) {
    //             DB::addColIndex($table, $metaKey);
    //         }
    //     }

    //     DB::isUqualColType($table, $metaKey, $sqlType);

    //     // if (!DB::isUqualColType($table, $metaKey, $sqlType)) {
    //     //     file_put_contents(__DIR__ . '/_DEBUG_', print_r('NOT EQUAL', true) . PHP_EOL, FILE_APPEND);
    //     // } else {
    //     //     file_put_contents(__DIR__ . '/_DEBUG_', print_r('EQUAL', true) . PHP_EOL, FILE_APPEND);
    //     // }

    //     // сохраняем данные
    //     add_filter('update_post_metadata', function ($check, int $postId, string $updateMetaKey, $metaValue, $prevVlue) use ($table, $metaKey) {
    //         if ($updateMetaKey !== $metaKey || get_post_type($postId) !== $this->getSlug()) {
    //             return $check;
    //         }

    //         if (is_array($metaValue) || is_object($metaValue)) {
    //             $metaValue = serialize($metaValue);
    //         }

    //         if ($prevVlue) {
    //             DB::update($table, [$metaKey => $metaValue], [$metaKey => $prevVlue, 'post_id' => $postId]);
    //             return true;
    //         }

    //         if (DB::getResults("SELECT `{$metaKey}` FROM `{$table}` WHERE `{$metaKey}` = '{$metaValue}' AND `post_id` = {$postId}")) {
    //             return true;
    //         }

    //         if (DB::getResults("SELECT `{$metaKey}` FROM `{$table}` WHERE `post_id` = {$postId} LIMIT 1")) {
    //             DB::update($table, [$metaKey => $metaValue], ['post_id' => $postId]);
    //             return true;
    //         }

    //         DB::insert($table, ['post_id' => $postId, $metaKey => $metaValue]);

    //         return true;
    //     }, 10, 5);

    //     // удаляем данные
    //     add_filter('delete_post_metadata', function ($delete, int $postId, string $deleteMetaKey, $metaValue, bool $deleteAll) use ($table, $metaKey) {
    //         if ($deleteMetaKey !== $metaKey || get_post_type($postId) !== $this->getSlug()) {
    //             return $delete;
    //         }

    //         if (is_array($metaValue) || is_object($metaValue)) {
    //             $metaValue = serialize($metaValue);
    //         }

    //         if ($deleteAll) {
    //             if ($metaValue) {
    //                 DB::update($table, [$metaKey => null], [$metaKey = $metaValue]);
    //             } else {
    //                 DB::query("UPDATE `{$table}` SET `{$metaKey}` = NULL");
    //             }
    //             return true;
    //         }

    //         if ($metaValue) {
    //             DB::update($table, [$metaKey => null], ['post_id' => $postId, $metaKey => $metaValue]);
    //         } else {
    //             DB::update($table, [$metaKey => null], ['post_id' => $postId]);
    //         }

    //         return true;
    //     }, 10, 5);

    //     // получаем данные
    //     add_filter('get_post_metadata', function ($value, int $postId, string $getMetakey, bool $single) use ($table, $metaKey) {
    //         if ($getMetakey !== $metaKey) {
    //             return $value;
    //         }

    //         if (get_post_type($postId) !== $this->getSlug()) {
    //             return $value;
    //         }

    //         $query = "SELECT `{$metaKey}` FROM `{$table}` WHERE `post_id` = {$postId}";

    //         if ($single) {
    //             $query .= " LIMIT 1";
    //         }

    //         $res = DB::getResults($query);

    //         if (is_wp_error($res)) {
    //             return null;
    //         }

    //         if ($single) {
    //             return $res[0][$metaKey] ?? null;
    //         }

    //         return array_column($res, $metaKey);
    //     }, 10, 4);

    //     return $this;
    // }
}
