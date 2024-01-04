<?php

/**
 * - Значения данных констант могут отличаться на разных серверах
 */


// define('CAPTCHA_KEY', '6Ldu7pogAAAAAG7SM4EMhHebpO1jLwwZ9d7PniAX');
// define('CAPTCHA_SECRET', '6Ldu7pogAAAAALknd0-M68KeDJDz4xfgrRxHo2oy');

// удалить константу на проде!
define('ADMIN_EMAIL', 'info@holly-travel.pl');

define('REDIS_HOST', '');
define('REDIS_PORT', '');
define('REDIS_PASS', 'ptHkV9cbn6EKYR8s');
define('REDIS_PREFIX', 'tour');

define('TG_TOKEN', '5597078689:AAH0PPLnqMQ9Ukrn_4KTWMP_CSZH6wIPRY0');
// define('TG_CHANNEL', '@hollytravelsite');
define('TG_CHANNEL', '5314874564');

// ключ метрики yandex
// используется для отправки целей (ym(89420018,'reachGoal','header_social'))
define('YANDEX_METRIKA_KEY', '89420018');
define('GA_ID', 'G-3NWHC4JFV1');

// slug городов для вывода страниц с фильтром по данным городам
define('CITIES_SLUGS', [
    'wroclaw',
    'warsaw',
    'katowice',
    'lodz',
    'poznan',
    'legnica'
]);

// подключения к crm
define('B24_URL', 'https://crm.holly-travel.pl/');
define('B24_USER', 'root');
define('B24_PASS', 'h!,jSS@uGW;yK!]~2MM]');
// ID пользователей которым отправлять уведомления
define('B24_NOTIF_USERS', [1, 6]);
// ID основного менеджера, будет назначаться как ответственный по лидам
define('B24_MAIN_MANAGER_ID', 6);
// Пользовательские поля CRM
define('B24_FIELD_LEAD_COUNT_TOURISTS', 'UF_CRM_1664631767');
define('B24_FIELD_LEAD_CHILDREN', 'UF_CRM_1679909202');
define('B24_FIELD_LEAD_TRIP', 'UF_CRM_1680027812');
define('B24_FIELD_LEAD_CITY', 'UF_CRM_1680027410');
define('B24_FIELD_LEAD_PREPAYMENT', 'UF_CRM_1664810816');
define('B24_FIELD_LEAD_TAX', 'UF_CRM_1664811006');
define('B24_FIELD_LEAD_HOTEL', 'UF_CRM_1665235506');

// Ключ api для проверки e-mail
define('ABSTRACT_API_KEY', 'd01ead65490b4d57a36fb06d8049a90f');
// Тестирование рассылки
define('MAILER_TEST', true);
define('MAILER_TEST_EMAIL', 'info@holly-travel.pl');

// SMT Данные
define('MAIL_SMTP', true);
define('MAIL_SMTP_HOST', 'smtp.ionos.de');
define('MAIL_SMTP_PORT', 465);
define('MAIL_SMTP_USER', 'noreplay@holly-travel.pl');
define('MAIL_SMTP_PASS', 'Arrogaminca_1995#');

// https://app.mailersend.com/
define('MAILSEND_TOKEN', 'mlsn.defa5e936aa98bf0f38a0c7a49281ea4733e36a22483938a0a3c6c98923730f6');

define('SITE_LOCALE', 'pl_PL');
// define('SITE_LOCALE', 'ru_RU');