<?php

namespace Company\AccessRequest;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Container;

class EventHandlers
{
    /**
     * Добавляет вкладку "История согласований" в карточку смарт-процесса
     * 
     * @param \Bitrix\Main\Event $event
     * @return \Bitrix\Main\EventResult
     */
    public static function onEntityDetailsTabsInitialized($event)
    {
        $params = $event->getParameters();
        $entityTypeId = (int)($params['entityTypeID'] ?? 0);
        
        // Проверяем, что это нужный смарт-процесс (1042)
        if ($entityTypeId !== 1042) {
            return new \Bitrix\Main\EventResult(
                \Bitrix\Main\EventResult::SUCCESS,
                ['tabs' => $params['tabs']]
            );
        }
        
        $entityId = (int)($params['entityID'] ?? 0);
        $requestId = null;
        
        // Получаем значение UF_CRM_4_REQUEST_ID
        if ($entityId > 0 && Loader::includeModule('crm')) {
            $factory = Container::getInstance()->getFactory($entityTypeId);
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
            'name' => Loc::getMessage('ACCESS_REQUEST_HISTORY_TAB_NAME') ?: 'История согласований',
            'active' => false,
            'enabled' => true,
            'html' => $tabContent,
        ];
        
        return new \Bitrix\Main\EventResult(
            \Bitrix\Main\EventResult::SUCCESS,
            ['tabs' => $tabs]
        );
    }
}