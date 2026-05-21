<?php
use Bitrix\Main\Page\Asset as Asset;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\EventManager;
use \Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;


if(file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once(__DIR__ . '/../../vendor/autoload.php');
}

//require_once $_SERVER['DOCUMENT_ROOT'] . '/local/lib/otuslogger.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/App/Debug/Log.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/src/Otus/Diag/FileExceptionHanlderLogCustom.php';
// require_once  $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/extensions.php';

// Bitrix\Main\Loader::registerAutoLoadClasses(null, [
//     'Otus\\Events\\IblockEventHandler' => '/local/php_interface/src/Events/IblockEventHandler.php',
// ]);

$eventManager = EventManager::getInstance();

// ===== СОБЫТИЯ ИНФОБЛОКОВ =====
$eventManager->addEventHandler(
    "iblock", 
    "OnAfterIBlockElementAdd", 
    [
        'Otus\Events\IblockEventHandler', 'OnAfterIBlockElementAdd'
    ]
);

$eventManager->addEventHandler(
    "iblock", 
    "OnAfterIBlockElementUpdate", 
    [
        'Otus\Events\IblockEventHandler', 'OnAfterIBlockElementUpdate'
    ]
);

$eventManager->addEventHandler(
    "iblock", 
    "OnBeforeIBlockElementDelete", 
    [
        'Otus\Events\IblockEventHandler', 'onElementBeforeDelete'
    ]
);

// ===== НОВОЕ СОБЫТИЕ CRM (обновление сделки) =====
$eventManager->addEventHandler(
    "crm", 
    "OnAfterCrmDealUpdate", 
    ['Otus\Events\CrmDealEventHandler', 'OnAfterCrmDealUpdate']
);

// // Удаление сделки (перед удалением)
// $eventManager->addEventHandler(
//     "crm", 
//     "OnBeforeCrmDealDelete", 
//     ['Otus\Event\CrmDealEventHandler', 'onBeforeDealDelete']
// );


/*
$eventManager->AddEventHandler('main', 'OnEpilog', function() {
    //Bitrix\Main\Page\Asset::getInstance()->addJs('/otus/students_dz/homework8/src/js/homework8.js');
    Bitrix\Main\Page\Asset::getInstance()->addJs('/otus/students_dz/homework8/src/js/timeman.js');
});
*/

// Используем OnPageStart — здесь уже доступны данные пользователя
$eventManager->addEventHandler("main", "OnBeforeProlog", function() {

    $CMain = new CMain();

    if (stristr($CMain->GetCurUri(), '/bizproc/processes/18/element/0/0/')) {
        CJSCore::Init(array("jquery3"));
        $asset = Asset::getInstance();

        $user = CurrentUser::get();

        var_dump(
            $user->getId(), //вернет false(или 0) если не авторизован
            $user->isAdmin(),
            $user->getLogin(),
            $user->getEmail(),
            $user->getUserGroups(),
            $user->getFormattedName(),
            $user->getFullName(),
            $user->getLastName(),
            $user->getSecondName()
        );

        $userId = $user->getId();

        $asset->addString('<script>
            window.myApp = window.myApp || {}; 
            window.myApp.params = ' . Bitrix\Main\Web\Json::encode(
            [
                'userID' => $userId, 
            ]
        ) . ';</script>');

        $asset->addJs("/local/js/otpusk-otgul.js");
    }
});

AddEventHandler('main', 'OnEpilog', function() {
    global $APPLICATION;
    
    // Подключаем компонент на всех страницах
    $APPLICATION->IncludeComponent(
        'otus:timeman.integration',
        '',
        [],
        null,
        ['HIDE_ICONS' => 'Y']
    );
});


// Подписка на событие для добавления вкладки в карточку смарт-процесса
$eventManager->addEventHandler('crm', 'onEntityDetailsTabsInitialized', function ($event) {
    $params = $event->getParameters();
    $entityTypeId = (int)$params['entityTypeID'];
    
    // Проверяем, что это нужный смарт-процесс (1042)
    if ($entityTypeId !== 1042) {
        return;
    }
    
    $entityId = (int)$params['entityID'];
    $requestId = null;
    
    // Получаем значение UF_CRM_4_REQUEST_ID
    if ($entityId > 0 && Loader::includeModule('company.accessrequest')) {
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
        if ($factory) {
            $item = $factory->getItem($entityId);
            if ($item) {
                $requestId = $item->get('UF_CRM_4_REQUEST_ID');
            }
        }
    }
    
    // Генерируем содержимое вкладки
    global $APPLICATION;
    ob_start();
    $APPLICATION->IncludeComponent(
        'company:accessrequest.history',
        '.default',
        [
            'REQUEST_ID' => $requestId,
        ]
    );
    $tabContent = ob_get_clean();
    
    // Добавляем новую вкладку
    $tabs = $params['tabs'];
    $tabs[] = [
        'id' => 'access_request_history_tab',
        'name' => 'История согласований',
        'active' => false,
        'enabled' => true,
        'html' => $tabContent,
    ];
    
    // Возвращаем обновлённый массив вкладок
    return new \Bitrix\Main\EventResult(
        \Bitrix\Main\EventResult::SUCCESS,
        ['tabs' => $tabs]
    );
});


?>