<?php

/**
 * - Входная точка для ajax запросов
 * - В htaccess файле необходимо настроить перезапись в данный файл
 *   RewriteRule ^ajax/([^/]+)/([^/]+)/?$ /wp-content/themes/vnet-tour/ajax.php?_class=$1&_method=$2 [L,QSA]
 */

require $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

if (empty($_REQUEST['_class']) || empty($_REQUEST['_method'])) {
    http_response_code(404);
    exit;
}

define('IS_THEME_AJAX', true);

$className = ucfirst($_REQUEST['_class']);
$method = $_REQUEST['_method'];

$fullClass = '\\Vnet\\Ajax\\' . $className;

$ajax = $fullClass::getInstance();

if (!method_exists($ajax, $method)) {
    http_response_code(404);
    exit;
}

$ajax->$method();

exit;
