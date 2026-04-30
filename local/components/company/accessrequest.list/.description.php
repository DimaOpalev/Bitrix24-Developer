<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('COMPONENT_NAME'),
    'DESCRIPTION' => Loc::getMessage('COMPONENT_DESCRIPTION'),
    'ICON' => '',
    'SORT' => 10,
    'CACHE_PATH' => 'Y',
    'PATH' => [
        'ID' => 'company',
        'NAME' => Loc::getMessage('COMPONENT_PATH_ID'),
    ],
];