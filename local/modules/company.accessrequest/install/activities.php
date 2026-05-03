<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$activities = [
    [
        'CLASS_NAME' => \Company\AccessRequest\Activity\LoadRequestDataActivity::class,
        'CLASS' => 'LoadRequestDataActivity',
        'NAME' => Loc::getMessage('LOAD_REQUEST_DATA_ACTIVITY_NAME'),
        'DESCRIPTION' => Loc::getMessage('LOAD_REQUEST_DATA_ACTIVITY_DESC'),
    ],
    [
        'CLASS_NAME' => \Company\AccessRequest\Activity\UpdateStatusActivity::class,
        'CLASS' => 'UpdateStatusActivity',
        'NAME' => Loc::getMessage('UPDATE_ACTIVITY_NAME'),
        'DESCRIPTION' => Loc::getMessage('UPDATE_ACTIVITY_DESC'),
    ],
    [
        'CLASS_NAME' => \Company\AccessRequest\Activity\WriteHistoryActivity::class,
        'CLASS' => 'WriteHistoryActivity',
        'NAME' => Loc::getMessage('WRITE_HISTORY_ACTIVITY_NAME'),
        'DESCRIPTION' => Loc::getMessage('WRITE_HISTORY_ACTIVITY_DESC'),
    ],
];

return $activities;