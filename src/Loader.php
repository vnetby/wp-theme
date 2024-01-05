<?php

namespace Vnetby\Wptheme;

use Vnetby\Helpers\HelperPath;
use Vnetby\Wptheme\Traits\Config\ConfigAjax;
use Vnetby\Wptheme\Traits\Config\ConfigEntities;
use Vnetby\Wptheme\Traits\Config\ConfigLocale;
use Vnetby\Wptheme\Traits\Config\ConfigMailer;
use Vnetby\Wptheme\Traits\Config\ConfigPathes;
use Vnetby\Wptheme\Traits\Config\ConfigTheme;
use Vnetby\Wptheme\Traits\Singletone;

class Loader
{
    use Singletone;

    use ConfigLocale;
    use ConfigEntities;
    use ConfigPathes;
    use ConfigAjax;
    use ConfigMailer;
    use ConfigTheme;

    /**
     * - Устанавливаем значения по умолчанию
     */
    protected function __construct()
    {
        // запоминаем вызванный класс как загрузчик темы
        Container::setClassLoader(get_called_class());

        $this->setLocale(get_locale());
        $this->setTimeZone(wp_timezone_string());
        $this->setDateFormat(get_bloginfo('date_format'));
        $this->setTimeFormat(get_bloginfo('time_fromat'));
        $this->setAjaxUrl($this->libUri('src/ajax.php'));
        $this->setEmailAdmin(get_bloginfo('admin_email'));
        $this->setEmailFrom($this->getEmailAdmin());
        $this->setEmailFromName('noreplay');

        $this->setMessages([
            'required' => __('Заполните поле', 'vnet'),
            'email' => __('Некорректный формат почты', 'vnet'),
            'phone' => __('Некорректный формат телефона', 'vnet'),
            'checkFields' => __('Проверьте введенные Вами данные', 'vnet'),
            'compare' => __('Значения не совпадают', 'vnet'),
            'emailExists' => __('Пользователь с таким e-mail существует', 'vnet'),
            'minLength' => __('Минимальное кол-во символов:', 'vnet') . ' $1',
            'maxLength' => __('Максимальное кол-во символов:', 'vnet') . ' $1',
            'wrongPass' => __('Не верный пароль', 'vnet'),
            'maxSize' => __('Максимальный размер файла:', 'vnet') . ' $1 MB',
            'isImage' => __('Можно загружать только изображения', 'vnet'),
            'fileExtensions' => __('Допустимые разрешения файлов:', 'vnet') . ' $1',
            'typeFloat' => __('Не корректный формат', 'vnet'),
            'onlyEnCharset' => __('Можно использовать буквы латинского алфавита и цифры', 'vnet'),
            'mask' => __('Некорректный формат', 'vnet'),
            'latName' => __('Некорректный формат', 'vnet'),
            'latNameRuSymbols' => __('Введите латиницей как в паспорте', 'vnet'),
            'acceptTerms' => __('Примите условия', 'vnet'),
            'serverError' => __('Произошла серверная ошибка. Пожалуйста, обратитесь в службу поддержки.', 'vnet'),
            'commentModeration' => __('Ваш отзыв успешно отправлен на модерацию', 'vnet'),
            'emailExists' => __('Пользователь с таким email существует', 'vnet'),
            'userEmailNotExists' => __('Пользователь с таким e-mail не существует', 'vnet'),
            'loginExists' => __('Пользователь с таким логином существует', 'vnet'),
            'successProfileUpdate' => __('Данные успешно обновлены', 'vnet'),
            'wrongPassword' => __('Не верный пароль', 'vnet'),
            'successRegister' => __('Регистрация прошла успешно. Проверьте Вашу почту для активации аккаунта.', 'vnet'),
            'successRecoveryEmail' => __('Инструкции по восстановлению пароля были отправлены на Ваш email.', 'vnet'),
            'successSendAdmin' => __('Ваше сообщение успешно отправлено', 'vnet'),
            'onlyEnCharsetLogin' => __('Логин может состоять из букв латинского алфавита и цифр.', 'vnet'),
            'incorrectFields' => __('Не все поля заполнены корректно', 'vnet'),
            'successSubmit' => __('Ваша заявка успешно отправлена', 'vnet')
        ]);
    }


    /**
     * - Метод загрузки темы
     * - Должен вызываться самым последним, после установки всех настроек
     * @return self
     */
    function setup(): self
    {
        // для wordpress важная настрайка
        date_default_timezone_set('UTC');

        $this->registerLocale();
        $this->addDefaultFrontVars();
        $this->registerMenus();
        $this->addSupports();
        $this->registerThumbnails();
        $this->renderBackDates();
        $this->renderMceScripts();
        $this->renderFrontScripts();
        $this->renderAdminScripts();
        $this->renderLoginScripts();
        $this->setAdminAvatar();
        $this->removeEmoji();
        $this->removeWpScripts();
        $this->removeHeadTags();
        $this->addFilters();
        $this->setupEntities();

        return $this;
    }


    /**
     * - Регистрирует локаль, устанавливает необходимые хуки для корректной работы
     * @return static
     */
    protected function registerLocale()
    {
        // устанавливаем переводы
        load_textdomain($this->textDomain, $this->themePath('languages', $this->getLocale() . '.mo'));
        add_action('after_setup_theme', function () {
            load_theme_textdomain($this->textDomain, $this->themePath('languages'));
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
        return $this;
    }

    /**
     * - Прокидывает переменные по умолчанию на фронт
     * @return static
     */
    protected function addDefaultFrontVars()
    {
        // прокидываем переменные на фронт
        $this
            ->addFrontVar('messages', $this->messages)
            ->addFrontVar('captchaKey', $this->getCaptchaKey());
        return $this;
    }

    /**
     * - Добавляет дополнительные фильтры
     * @return static
     */
    protected function addFilters()
    {
        add_filter('multilingualpress.hreflang_type', '__return_false');
        add_filter('deprecated_hook_trigger_error', '__return_false');
    }

    /**
     * - Регистрирует области меню
     * @return static
     */
    protected function registerMenus()
    {
        // регестрируем меню
        add_action('after_stup_theme', function () {
            foreach ($this->menus as $key => $desc) {
                register_nav_menu($key, $desc);
            }
        });
        return $this;
    }

    /**
     * - Добавляет дополнительные поддержки
     * @return static
     */
    protected function addSupports()
    {
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

        // добавляем краткое описание к старницам
        add_post_type_support('page', 'excerpt');

        return $this;
    }

    /**
     * - Регистрирует миниатюры
     * @return static
     */
    protected function registerThumbnails()
    {
        // регистрируем миниатюры
        add_action('after_setup_theme', function () {
            foreach ($this->imageSizes as $key => &$val) {
                add_image_size($key, $val[0], $val[1], isset($val[2]) ? $val[2] : false);
            }
        });
        return $this;
    }

    /**
     * - Выводит глобальную переменную backDates на фронте
     * @return static
     */
    protected function renderBackDates()
    {
        // выводим глобальную переменную на фронт и в админке
        foreach (['wp_head', 'admin_head'] as $hook) {
            add_action($hook, function () {
                echo '<script>';
                echo 'window.backDates = JSON.parse(\'' . json_encode($this->frontVars, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '\')';
                echo '</script>';
            });
        }
    }

    /**
     * - Выводит стили в текстовом редакторе в админке
     * @return static
     */
    protected function renderMceScripts()
    {
        foreach ($this->mceCss as $src) {
            add_filter('mce_css', function ($url) use ($src) {
                if (!empty($url)) {
                    $url .= ',';
                }
                $url .= $src;
                return $url;
            });
        }
        return $this;
    }

    /**
     * - Выводит скрипты и стили на фронте
     * @return static
     */
    protected function renderFrontScripts()
    {
        add_action('wp_enqueue_scripts', function () {
            foreach ($this->frontStyles as $key => $params) {
                $path = HelperPath::urlToPath($params['src']);
                if (file_exists($path)) {
                    wp_enqueue_style($key, $params['src'], $params['deps'], filemtime($path), $params['media'] ?? 'all');
                }
            }
            foreach ($this->frontScripts as $key => $params) {
                $path = HelperPath::urlToPath($params['src']);
                if (file_exists($path)) {
                    wp_enqueue_script($key, $params['src'], $params['deps'], filemtime($path), $params['inFooter']);
                }
            }
            wp_deregister_script('jquery');
            wp_register_script('jquery', $this->libUri('assets/jquery3/jquery3.min.js'));
            wp_enqueue_script('jquery');
        });
        return $this;
    }

    /**
     * - Выводит скрипты и стили в административной части
     * @return static
     */
    protected function renderAdminScripts()
    {
        // подключаем скрипты и стили в админке
        add_action('admin_enqueue_scripts', function () {
            foreach ($this->adminStyles as $key => $params) {
                $path = HelperPath::urlToPath($params['src']);
                if (file_exists($path)) {
                    wp_enqueue_style($key, $params['src'], $params['deps'], filemtime($path), $params['media'] ?? 'all');
                }
            }
            foreach ($this->adminScripts as $key => $params) {
                $path = HelperPath::urlToPath($params['src']);
                if (file_exists($path)) {
                    wp_enqueue_script($key, $params['src'], $params['deps'], filemtime($path), $params['inFooter']);
                }
            }
        });
        return $this;
    }

    /**
     * - Выводит скрипты и стили на странице авторизации
     * @return static
     */
    protected function renderLoginScripts()
    {
        // подключаем стили и скрипты на странице авторизации
        add_action('login_head', function () {
            foreach ($this->adminStyles as $key => $params) {
                $path = HelperPath::urlToPath($params['src']);
                if (file_exists($path)) {
                    echo '<link rel="stylesheet" href="' . $params['src'] . '?v=' . filemtime($path) . '">';
                }
            }
        });
        add_action('login_footer', function () {
            foreach ($this->adminScripts as $key => $params) {
                $path = HelperPath::urlToPath($params['src']);
                if (file_exists($path)) {
                    echo '<script src="' . $params['src'] . '?v=' . filemtime($path) . '"></script>';
                }
            }
        });
        return $this;
    }

    /**
     * - Устанавливает аватарку супер пользователю
     * @return static
     */
    protected function setAdminAvatar()
    {
        if (!$this->rootAvatar || !file_exists($this->themePath($this->rootAvatar))) {
            return $this;
        }

        add_filter('get_avatar_url', function ($url, $id_or_email, $args) {
            $avatarUri = $this->themeUri($this->rootAvatar);

            if (gettype($id_or_email) === 'string' || gettype($id_or_email) === 'integer') {
                if ($id_or_email == 1) {
                    return $avatarUri;
                }
            }

            if (gettype($id_or_email) === 'object') {
                if ($id_or_email->user_id == 1) {
                    return $avatarUri;
                }
            }

            return $url;
        }, 10, 3);

        return $this;
    }

    /**
     * @return static
     */
    protected function removeWpScripts()
    {
        add_action('wp_enqueue_scripts', function () {
            wp_dequeue_style('global-styles');
            wp_dequeue_style('classic-theme-styles');
            wp_dequeue_style('wp-block-library');
        }, 100);
        return $this;
    }

    /**
     * @return static
     */
    protected function removeEmoji()
    {
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
        return $this;
    }

    /**
     * @return static
     */
    protected function removeHeadTags()
    {
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
        return $this;
    }
}
