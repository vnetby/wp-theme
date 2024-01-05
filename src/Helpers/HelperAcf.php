<?php

namespace Vnetby\Wptheme\Helpers;

use Vnetby\Helpers\HelperArr;

class HelperAcf
{

    /**
     * - Кэш полей в пост запросе
     * @var array
     */
    private static $postFields = null;


    /**
     * - Получает поле acf из post запроса
     * @param string|string[] $fieldName ключ либо массив вложенных ключей
     * @param mixed $def
     * @return mixed
     */
    static function getPostFieldValue($fieldName, $def = null)
    {
        return HelperArr::get(self::getPostFields(), $fieldName, $def);
    }


    /**
     * - Получает acf поля из пост запроса
     * - Заменяет ключи на их соответствующие name
     * (по умолчанию в пост запросе приходят ключи полей)
     * @return array 
     */
    static function getPostFields(): array
    {
        if (self::$postFields === null) {
            self::$postFields = self::fetchPostFields();
        }
        return self::$postFields;
    }


    private static function fetchPostFields(?array $fields = null): array
    {
        $res = [];

        if (empty($_POST['acf'])) {
            return $res;
        }

        $fields = $fields ?? $_POST['acf'];

        foreach ($fields as $fieldKey => $fieldVal) {

            $fieldName = $fieldKey;

            if (preg_match("/^field_/", $fieldKey)) {
                $field = acf_get_field($fieldKey);

                if (!$field) {
                    continue;
                }

                $fieldName = $field['name'];
            }

            if (is_array($fieldVal)) {
                $res[$fieldName] = self::fetchPostFields($fieldVal);
            } else {
                $res[$fieldName] = $fieldVal;
            }
        }

        return $res;
    }


    static function formatAcfValue($values)
    {
        if (!is_array($values)) {
            return $values;
        }

        $res = [];

        foreach ($values as $fieldKey => $fieldVal) {
            $fieldName = $fieldKey;

            if (preg_match("/^field_/", $fieldKey)) {
                $field = acf_get_field($fieldKey);

                if (!$field) {
                    continue;
                }

                $fieldName = $field['name'];
            }

            if (is_array($fieldVal)) {
                $res[$fieldName] = self::formatAcfValue($fieldVal);
            } else {
                $res[$fieldName] = $fieldVal;
            }
        }

        return $res;
    }


    static function getOption(string $selector, $formatValue = true)
    {
        return self::getField($selector, 'option', $formatValue);
    }


    static function getField(string $selector, $postId = false, $formatValue = true)
    {
        if (!function_exists('get_field')) {
            return null;
        }
        return get_field($selector, $postId, $formatValue);
    }


    static function disableUpdate()
    {
        if (class_exists('acf_pro_updates')) {
            add_action('init', function () {
                $filters = $GLOBALS['wp_filter'];
                $callbacks = $filters['init']->callbacks;
                foreach ($callbacks as $i => $data) {
                    foreach ($data as $key => $params) {
                        if (!isset($params['function'])) {
                            continue;
                        }
                        if (!is_array($params['function'])) {
                            continue;
                        }
                        if (!isset($params['function'][0])) {
                            continue;
                        }
                        if ($params['function'][0] instanceof \acf_pro_updates) {
                            unset($GLOBALS['wp_filter']['init']->callbacks[$i][$key]);
                        }
                    }
                }
            });
        }
    }
}
