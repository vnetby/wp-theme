<?php

/**
 * - Входная точка для ajax запросов
 */

require $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

if (empty($_REQUEST['_class']) || empty($_REQUEST['_method'])) {
    http_response_code(404);
    exit;
}

define('VNET_DOING_AJAX', true);

$className = base64_decode($_REQUEST['_class']);
$method = base64_decode($_REQUEST['_method']);

if (!class_exists($className) || !method_exists($className, $method)) {
    http_response_code(404);
    exit;
}

$ajax = $className::getInstance();

$ajax->$method();

exit;
