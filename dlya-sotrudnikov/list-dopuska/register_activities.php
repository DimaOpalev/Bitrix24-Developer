<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Bitrix\Bizproc\Activity\ActivityRegistry;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
if (!Loader::includeModule('bizproc')) {
    die('Модуль bizproc не подключен');
}

if (!Loader::includeModule('company.accessrequest')) {
    die('Модуль company.accessrequest не найден');
}

if (!Loader::includeModule('bizproc')) {
    die('Модуль bizproc не найден');
}

// Регистрируем активности
$activities = [
    [
        'CLASS_NAME' => \Company\AccessRequest\Activity\LoadRequestDataActivity::class,
        'CLASS' => 'LoadRequestDataActivity',
        'NAME' => Loc::getMessage('LOAD_REQUEST_DATA_ACTIVITY_NAME'),
        'DESCRIPTION' => Loc::getMessage('LOAD_REQUEST_DATA_ACTIVITY_DESC'),
    ],
    [
        'CLASS_NAME' => \Company\AccessRequest\Activity\WriteHistoryActivity::class,
        'CLASS' => 'WriteHistoryActivity',
        'NAME' => Loc::getMessage('WRITE_HISTORY_ACTIVITY_NAME'),
        'DESCRIPTION' => Loc::getMessage('WRITE_HISTORY_ACTIVITY_DESC'),
    ],
    [
        'CLASS_NAME' => \Company\AccessRequest\Activity\UpdateStatusActivity::class,
        'CLASS' => 'UpdateStatusActivity',
        'NAME' => Loc::getMessage('UPDATE_STATUS_ACTIVITY_NAME'),
        'DESCRIPTION' => Loc::getMessage('UPDATE_STATUS_ACTIVITY_DESC'),
    ],
];

$registered = [];
foreach ($activities as $activity) {
    // Проверяем, зарегистрирована ли уже активность
    $dbResult = \CBPActivity::GetList([], ['CLASS' => $activity['CLASS']]);
    if (!$dbResult->Fetch()) {
        \CBPTaskService::RegisterActivity(
            $activity['CLASS'],
            $activity['NAME'],
            $activity['DESCRIPTION'],
            $activity['CLASS_NAME']
        );
        $registered[] = $activity['CLASS'];
    }
}

if (!empty($registered)) {
    echo "Зарегистрированы активности: " . implode(', ', $registered);
} else {
    echo "Активности уже зарегистрированы";
}

echo "<br><a href='/bitrix/admin/iblock_bizproc_activity_settings.php?lang=ru'>Проверить список активностей</a>";