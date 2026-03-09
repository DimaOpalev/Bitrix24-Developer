<?php
use Bitrix\Main\Page\Asset as Asset;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\EventManager;

if(file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once(__DIR__ . '/../../vendor/autoload.php');
}

//require_once $_SERVER['DOCUMENT_ROOT'] . '/local/lib/otuslogger.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/App/Debug/Log.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/src/Otus/Diag/FileExceptionHanlderLogCustom.php';
// require_once  $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/extensions.php';

$eventManager = EventManager::getInstance();

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

?>