<?php

/**
 * - Класс для работы с общими настройками сео
 */

namespace Vnetby\Wptheme\Seo;

use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Front\Template;

class SeoOptions
{

    const OPTION_KEY = 'vnet_seo_options';

    function __construct()
    {
        add_action('admin_menu', function () {
            add_options_page(__('Настройки СЕО', 'vnet'), __('СЕО', 'vnet'), 'manage_options', 'vnet-seo-options', function () {
                Template::theFile(Container::getLoader()->libPath('templates/seo-options.php'));
            });
        });
    }


    /**
     * - Сохраняет общие настройки
     */
    function saveOptionsFromRequest()
    {
        $postTypes = Container::getSeo()->getPostTypesWithArchives();
        foreach ($postTypes as $postType) {
            $data['post_type_' . $postType->name] = [
                'title' => $_REQUEST['vnet-seo-post-' . $postType->name . '-title'],
                'desc' => $_REQUEST['vnet-seo-post-' . $postType->name . '-desc'],
                'image' => (int)($_REQUEST['vnet-seo-post-' . $postType->name . '-image'] ?? 0)
            ];
        }
        $data['__common__'] = [
            'image' => (int)($_REQUEST['vnet-seo-common-image'] ?? 0)
        ];
        // на странице настроек, на уровне wp экранируются ковычки в полях
        foreach ($data as &$item) {
            foreach ($item as &$val) {
                if (is_string($val)) {
                    $val = stripslashes($val);
                }
            }
        }
        update_option(static::OPTION_KEY, $data);
    }


    function getOptions(): array
    {
        $data = get_option('vnet_seo_options');
        if (!$data) {
            return [];
        }
        if (is_array($data)) {
            return $data;
        }
        $res = @unserialize($data);
        return $res ? $res : [];
    }

    /**
     * @return array{
     *      title: string,
     *      desc: string,
     *      image: int
     * }
     */
    function getPostTypeOptions(string $postType): array
    {
        return $this->getOptions()['post_type_' . $postType];
    }

    /**
     * @return array{
     *      image: int
     * }
     */
    function getCommonOptions(): array
    {
        return $this->getOptions()['__common__'] ?? [];
    }


    function getArchiveTitle(string $postType): string
    {
        return $this->getPostTypeOptions($postType)['title'] ?? '';
    }

    function getArchiveDesc(string $postType): string
    {
        return $this->getPostTypeOptions($postType)['desc'] ?? '';
    }

    function getArchiveImageId(string $postType): string
    {
        return (int)($this->getPostTypeOptions($postType)['image'] ?? 0);
    }

    function getCommonImageId(): int
    {
        return (int)($this->getCommonOptions()['image'] ?? 0);
    }
}
