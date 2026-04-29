<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = [
    'GROUPS' => [
        'BASE' => [
            'NAME' => 'Основные настройки',
        ],
    ],
    'PARAMETERS' => [
        'USER_ID' => [
            'PARENT' => 'BASE',
            'NAME' => 'ID пользователя',
            'TYPE' => 'STRING',
            'DEFAULT' => '',
        ],
        'CACHE_TIME' => ['DEFAULT' => 3600],
        'AJAX_MODE' => [], // стандартная опция
    ],
];