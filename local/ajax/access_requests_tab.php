<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
use \Bitrix\Main\Loader;
use Bitrix\Crm\Service\Container;

/**
 * Возвращает ID заявок из access_request, связанных с контактом
 */
function getAccessRequestIdsByContact(int $contactId): array
{
    
    if (!Loader::includeModule('crm')) {
        return [];
    }
    
    $smartProcessEntityTypeId = 1042;
    $factory = Container::getInstance()->getFactory($smartProcessEntityTypeId);
    if (!$factory) {
        return [];
    }
    
    $itemsResult = $factory->getItems([
        'select' => ['UF_CRM_4_REQUEST_ID'],
        'filter' => ['=CONTACT_ID' => $contactId],
    ]);
    
    $requestIds = [];
    foreach ($itemsResult as $item) {
        $reqId = $item->get('UF_CRM_4_REQUEST_ID');
        if ($reqId) {
            $requestIds[] = (int)$reqId;
        }
    }
    
    return $requestIds;
}

global $APPLICATION;

$APPLICATION->RestartBuffer();
$APPLICATION->ShowAjaxHead();

\Bitrix\Main\UI\Extension::load([
    'ui.vue3',
    'ui.notification',
    'main.core',
]);


    // Временная отладка
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/debug_tab.log', 
        date('Y-m-d H:i:s') . ' - ContactAjax called: ' . print_r($_REQUEST, true) . "\n", 
        FILE_APPEND
    );

$contactId = (int)($_REQUEST['PARAMS']['params']['CONTACT_ID'] ?? 0);

if (!$contactId) {
    echo 'Контакт не найден';
    return;
}

$requestIds = getAccessRequestIdsByContact($contactId);
// $requestIds = [];

if (empty($requestIds)) {
    echo '<div style="padding:20px;">Нет заявок</div>';
    return;
}

global $APPLICATION;

$APPLICATION->IncludeComponent(
    'company:accessrequest.list',
    '.default',
    [
        'FORCE_FILTER' => ['=ID' => $requestIds],
        'HIDE_FILTER' => 'Y',
        'HIDE_TOOLBAR' => 'Y',
    ]
);
