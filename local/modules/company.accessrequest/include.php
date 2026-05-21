<?php

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;

define('COMPANY_ACCESSREQUEST_MODULE_ID', 'company.accessrequest');

Loader::registerAutoLoadClasses('company.accessrequest', [
    'Company\AccessRequest\AccessRequestTable' => 'lib/AccessRequestTable.php',
    'Company\AccessRequest\AccessRequestHistoryTable' => 'lib/AccessRequestHistoryTable.php',
    // 'Company\AccessRequest\EventHandlers' => 'lib/EventHandlers.php',

    'Company\AccessRequest\Activity\LoadRequestDataActivity' => 'lib/activity/LoadRequestDataActivity.php',
    'Company\AccessRequest\Activity\UpdateStatusActivity' => 'lib/activity/UpdateStatusActivity.php',
    'Company\AccessRequest\Activity\WriteHistoryActivity' => 'lib/activity/WriteHistoryActivity.php',
]);

// Регистрация обработчиков событий
$eventManager = EventManager::getInstance();

// Событие для добавления вкладки в карточку смарт-процесса
// $eventManager->addEventHandler(
//     'crm',
//     'onEntityDetailsTabsInitialized',
//     [\Company\AccessRequest\EventHandlers::class, 'onEntityDetailsTabsInitialized']
// ); 

// Регистрация активити для бизнес-процессов
EventManager::getInstance()->addEventHandler(
    'main',
    'OnIncludeServiceBizproc',
    function() {
        $activities = include __DIR__ . '/install/activities.php';
        return $activities;
    }
);