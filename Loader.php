<?php

namespace Vnet;

use Vnet\Helpers\ArrayHelper;
use Vnet\Helpers\Path;
use Vnet\Traits\Instance;

class Loader
{
    use Instance;

    private $timeZone = 'UTC';

    /**
     * - Локализация сайта
     */
    private $locale = 'ru_RU';

    /**
     * - Используется для генерации миниатюр
     * 'image size key' => [width, height, crop]
     */
    private $imageSizes = [];

    /**
     * - Массив строк с сообщениями
     * @var array
     */
    private $messages = [];

    /**
     * - Массив переменных для фронта
     * @var array
     */
    private $frontVars = [];

    /**
     * @var string
     */
    private $textDomain = 'vnet';

    /**
     * - Массив областей меню
     * @var array
     */
    private $menus = [];

    /**
     * - Массив стилей для фронта
     * @var array
     */
    private $frontStyles = [];

    /**
     * - Массив скриптов для фронта
     * @var array
     */
    private $frontScripts = [];

    /**
     * - Массив стилей для админки
     * @var array
     */
    private $adminStyles = [];

    /**
     * - Массив скриптов для админки
     * @var array
     */
    private $adminScripts = [];

    /**
     * - Массив стилей для текстового редактора
     * @var array
     */
    private $mceCss = [];

    /**
     * - Аватарка для root пользователя
     * @var string
     */
    private $rootAvatar = '';

    private $pathSvg = 'img/svg';

    /**
     * - Почта администатора
     * @var string
     */
    private $emailAdmin = '';

    /**
     * - Имя отправителя для системных сообщений
     * @var string
     */
    private $emailFromName = '';

    /**
     * - Почта от которой будут отправляться письма
     * @var string
     */
    private $emailFrom = '';

    private bool $isLocaleSetted = false;


    protected function __construct()
    {
        // устанавливаем основные константы
        define('THEME_URI', get_stylesheet_directory_uri() . '/');
        define('THEME_PATH', get_stylesheet_directory() . '/');
        define('AJAX_URL', admin_url("admin-ajax.php"));

        // устанавливаем версию
        if (file_exists(THEME_PATH . 'version')) {
            define('FRONT_VERSION', trim(file_get_contents(THEME_PATH . 'version')));
        } else {
            define('FRONT_VERSION', '1.0');
        }
    }


    function setup(): self
    {
        // прокидываем переменные на фронт
        $this
            ->addFrontVar('messages', $this->messages)
            ->addFrontVar('themeUri', THEME_URI)
            ->addFrontVar('ajaxUrl', AJAX_URL)
            ->addFrontVar('captchaKey', defined('CAPTCHA_KEY') ? constant('CAPTCHA_KEY') : null);

        // убираем сообщения об ошибке deprecated
        add_filter('deprecated_hook_trigger_error', '__return_false');

        if (!$this->isLocaleSetted) {
            // устанавливаем переводы
            load_textdomain($this->textDomain, THEME_PATH . 'languages/' . get_locale() . '.mo');
            add_action('after_setup_theme', function () {
                load_theme_textdomain($this->textDomain, THEME_PATH . 'languages');
            });

            // устанавливаем локализацию сайта
            if (!is_admin()) {
                switch_to_locale($this->getLocale());
                add_filter('locale', function ($locale) {
                    return $this->getLocale();
                });
                add_filter('language_attributes', function ($value) {
                    $locale = $this->getLocale();
                    $attr = str_replace("_", "-", $locale);
                    return 'lang="' . $attr . '"';
                });
            }
        }

        // регестрируем меню
        add_action('after_stup_theme', function () {
            foreach ($this->menus as $key => $desc) {
                register_nav_menu($key, $desc);
            }
        });

        // добавляем theme support
        add_action('after_setup_theme', function () {
            add_theme_support('automatic-feed-links');
            add_theme_support('menus');
            add_theme_support('title-tag');
            add_theme_support('post-thumbnails');
            add_theme_support('html5', [
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption'
            ]);
            add_theme_support('customize-selective-refresh-widgets');
            add_theme_support('woocommerce');
            add_theme_support('yoast-seo-breadcrumbs');
        });

        // регистрируем миниатюры
        add_action('after_setup_theme', function () {
            foreach ($this->imageSizes as $key => &$val) {
                add_image_size($key, $val[0], $val[1], isset($val[2]) ? $val[2] : false);
            }
        });

        // добавляем краткое описание к старницам
        add_post_type_support('page', 'excerpt');

        // подключаем стили в текстовом редакторе
        foreach ($this->mceCss as $src) {
            add_filter('mce_css', function ($url) use ($src) {
                if (!empty($url)) {
                    $url .= ',';
                }
                $url .= $src;
                return $url;
            });
        }

        // выводим глобальную переменную на фронт и в админке
        foreach (['wp_head', 'admin_head'] as $hook) {
            add_action($hook, function () {
                echo '<script>';
                echo 'window.backDates = JSON.parse(\'' . json_encode($this->frontVars, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '\')';
                echo '</script>';
            });
        }

        // подключаем скрипты и стили на фронте
        add_action('wp_enqueue_scripts', function () {
            foreach ($this->frontStyles as $key => $params) {
                $path = Path::urlToPath($params['src']);
                if (file_exists($path)) {
                    wp_enqueue_style($key, $params['src'], $params['deps'], fileatime($path), $params['media'] ?? 'all');
                }
            }
            foreach ($this->frontScripts as $key => $params) {
                $path = Path::urlToPath($params['src']);
                if (file_exists($path)) {
                    wp_enqueue_script($key, $params['src'], $params['deps'], fileatime($path), $params['inFooter']);
                }
            }
            wp_deregister_script('jquery');
            wp_register_script('jquery', THEME_URI . 'assets/jquery3/jquery3.min.js');
            wp_enqueue_script('jquery');
        });

        // подключаем скрипты и стили в админке
        add_action('admin_enqueue_scripts', function () {
            foreach ($this->adminStyles as $key => $params) {
                $path = Path::urlToPath($params['src']);
                if (file_exists($path)) {
                    wp_enqueue_style($key, $params['src'], $params['deps'], fileatime($path), $params['media'] ?? 'all');
                }
            }
            foreach ($this->adminScripts as $key => $params) {
                $path = Path::urlToPath($params['src']);
                if (file_exists($path)) {
                    wp_enqueue_script($key, $params['src'], $params['deps'], fileatime($path), $params['inFooter']);
                }
            }
        });

        // подключаем стили и скрипты на странице авторизации
        add_action('login_head', function () {
            foreach ($this->adminStyles as $key => $params) {
                $path = Path::urlToPath($params['src']);
                if (file_exists($path)) {
                    echo '<link rel="stylesheet" href="' . $params['src'] . '?v=' . fileatime($path) . '">';
                }
            }
        });
        add_action('login_footer', function () {
            foreach ($this->adminScripts as $key => $params) {
                $path = Path::urlToPath($params['src']);
                if (file_exists($path)) {
                    echo '<script src="' . $params['src'] . '?v=' . fileatime($path) . '"></script>';
                }
            }
        });

        // устанавливаем аватарку супер пользователю
        if ($this->rootAvatar && file_exists(Path::join(ABSPATH, $this->rootAvatar))) {
            add_filter('get_avatar_url', function ($url, $id_or_email, $args) {
                if (gettype($id_or_email) === 'string' || gettype($id_or_email) === 'integer') {
                    if ($id_or_email == 1) {
                        return THEME_URI . 'img/root-avatar.jpeg';
                    }
                }

                if (gettype($id_or_email) === 'object') {
                    if ($id_or_email->user_id == 1) {
                        return THEME_URI . 'img/root-avatar.jpeg';
                    }
                }
                return $url;
            }, 10, 3);
        }

        // отключаем emoji они никому не нужны
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        add_filter('tiny_mce_plugins', function ($plugins) {
            if (is_array($plugins)) {
                return array_diff($plugins, array('wpemoji'));
            } else {
                return [];
            }
        });

        // отключаем скрипты и стили от wp на фронте
        add_action('wp_enqueue_scripts', function () {
            wp_dequeue_style('global-styles');
            wp_dequeue_style('classic-theme-styles');
            wp_dequeue_style('wp-block-library');
        }, 100);

        // remove all tags from header
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'index_rel_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'start_post_rel_link', 10, 0);
        remove_action('wp_head', 'parent_post_rel_link', 10, 0);
        remove_action('wp_head', 'adjacent_posts_rel_link', 10, 0);
        remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('template_redirect', 'rest_output_link_header', 11);

        // language
        add_filter('multilingualpress.hreflang_type', '__return_false');

        return $this;
    }


    function setPathSvg(string $themeRelativePath): self
    {
        $this->pathSvg = $themeRelativePath;
        return $this;
    }


    function setTimeZone(string $zone = 'UTC'): self
    {
        date_default_timezone_set($zone);
        $this->timeZone = $zone;
        return $this;
    }


    function addImageSize(string $key, int $width, int $height, bool $crop = true): self
    {
        $this->imageSizes[$key] = [$width, $height, $crop];
        return $this;
    }


    function setMessages(array $messages): self
    {
        $this->messages = $messages;
        return $this;
    }


    function addFrontVar(string $key, $value): self
    {
        $this->frontVars[$key] = $value;
        return $this;
    }


    function addMenu(string $key, string $desc): self
    {
        $this->menus[$key] = $desc;
        return $this;
    }


    function addAdminScript(string $handle, string $src, $inFooter = true, $deps = []): self
    {
        $this->adminScripts[$handle] = [
            'src' => $src,
            'inFooter' => $inFooter,
            'deps' => $deps
        ];
        return $this;
    }


    function addAdminStyle(string $handle, string $src, $deps = [], $media = 'all'): self
    {
        $this->adminStyles[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'media' => $media
        ];
        return $this;
    }


    function addFrontScript(string $handle, string $src, $inFooter = true, $deps = []): self
    {
        $this->frontScripts[$handle] = [
            'src' => $src,
            'inFooter' => $inFooter,
            'deps' => $deps
        ];
        return $this;
    }


    function addFrontStyle(string $handle, string $src, $deps = [], $media = 'all'): self
    {
        $this->frontStyles[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'media' => $media
        ];
        return $this;
    }


    function addMceCss(string $src): self
    {
        $this->mceCss[] = $src;
        return $this;
    }


    function setRootAvatar(string $src): self
    {
        $this->rootAvatar = $src;
        return $this;
    }


    function getPathSvg($abs = true): string
    {
        return !$abs ? $this->pathSvg : Path::join(THEME_PATH, $this->pathSvg);
    }


    function getTimezone(): string
    {
        return $this->timeZone;
    }


    /**
     * - Получает текстовое сообщение из массива self::$messages
     * - вернет $key если не найдено
     * @param string $key
     * @param array $replace [optional] массив значений для замены в строке сообщения
     * @return string|string[]
     */
    function getMessage($key, $replace = [])
    {
        $msg = ArrayHelper::get($this->messages, $key, $key);

        if (!$replace || is_array($msg)) {
            return $msg;
        }

        foreach ($replace as $i => $str) {
            $n = $i + 1;
            $msg = str_replace("\$$n", $str, $msg);
        }

        return $msg;
    }


    function getEmailAdmin(): string
    {
        return $this->emailAdmin;
    }

    function setEmailAdmin(string $email): self
    {
        $this->emailAdmin = $email;
        return $this;
    }


    function getEmailFromName(): string
    {
        return $this->emailFromName;
    }

    function setEmailFromName(string $fromName): self
    {
        $this->emailFromName = $fromName;
        return $this;
    }


    function getEmailFrom(): string
    {
        return $this->emailFrom;
    }

    function setEmailFrom(string $emailFrom): self
    {
        $this->emailFrom = $emailFrom;
        return $this;
    }

    function setLocale(string $locale): self
    {
        $this->locale = $locale;
        $this->isLocaleSetted = true;

        if (!is_admin()) {
            switch_to_locale($this->getLocale());
            add_filter('locale', function ($locale) {
                return $this->getLocale();
            });
            add_filter('language_attributes', function ($value) {
                $locale = $this->getLocale();
                $attr = str_replace("_", "-", $locale);
                return 'lang="' . $attr . '"';
            });
        }

        load_textdomain($this->textDomain, THEME_PATH . 'languages/' . get_locale() . '.mo');

        add_action('after_setup_theme', function () {
            load_theme_textdomain($this->textDomain, THEME_PATH . 'languages');
        });


        return $this;
    }

    function getLocale(): string
    {
        return $this->locale;
    }
}
