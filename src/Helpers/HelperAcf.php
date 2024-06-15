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


    static function formatAcfValue($values, bool $removeSlashes = false)
    {
        if (!is_array($values)) {
            return $removeSlashes && is_string($values) ? stripslashes($values) : $values;
        }

        $res = [];
        $isRepeater = false;

        foreach ($values as $fieldKey => $fieldVal) {
            $fieldName = $fieldKey;

            if (preg_match("/^field_/", $fieldKey)) {
                $field = acf_get_field($fieldKey);

                if (!$field) {
                    continue;
                }

                $fieldName = $field['name'];
            } else if (preg_match("/^row-[\d]+/", $fieldName)) {
                $fieldName = (int)preg_replace("/^row-/", '', $fieldName);
                $isRepeater = true;
            }

            if (is_array($fieldVal)) {
                $res[$fieldName] = self::formatAcfValue($fieldVal, $removeSlashes);
            } else {
                $res[$fieldName] = $removeSlashes && is_string($fieldVal) ? stripslashes($fieldVal) : $fieldVal;
            }
        }

        if ($isRepeater) {
            $res = array_values($res);
        }

        return $res;
    }


    /**
     * - Меняет ключи полей на кличи в формате field_
     */
    static function toAdminValue(string $fieldName, $values)
    {
        if (!is_array($values)) {
            return $values;
        }

        $field = acf_get_field($fieldName);

        if (!$field) {
            return $values;
        }

        $res = self::createFieldValue($field, $values);

        return $res;
    }


    private static function createFieldValue(array $field, $values)
    {
        if (!is_array($values) || empty($field['sub_fields'])) {
            return $values;
        }

        $isRepeater = $field['type'] === 'repeater';
        $res = [];

        foreach ($field['sub_fields'] as $subField) {
            $val = array_key_exists('default_value', $subField) ? $subField['default_value'] : null;
            if ($isRepeater) {
                foreach ($values as $i => $fieldVal) {
                    if (array_key_exists($subField['name'], $fieldVal)) {
                        $val = $fieldVal[$subField['name']];
                    } else if (array_key_exists($subField['key'], $fieldVal)) {
                        $val = $fieldVal[$subField['key']];
                    }
                    $res[$i][$subField['key']] = is_array($val) ? static::createFieldValue($subField, $val) : $val;
                }
            } else {
                if (array_key_exists($subField['name'], $values)) {
                    $val = $values[$subField['name']];
                } else if (array_key_exists($subField['key'], $values)) {
                    $val = $values[$subField['key']];
                }
                $res[$subField['key']] = is_array($val) ? static::createFieldValue($subField, $val) : $val;
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
