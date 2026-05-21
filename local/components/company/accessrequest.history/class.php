<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Company\AccessRequest\AccessRequestHistoryTable;

class AccessRequestHistoryComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        Loader::includeModule('company.accessrequest');
        
        $requestId = (int)$this->arParams['REQUEST_ID'];
        if ($requestId <= 0) {
            $this->arResult['HISTORY'] = [];
            $this->includeComponentTemplate();
            return;
        }
        
        $history = AccessRequestHistoryTable::getList([
            'select' => ['*'],
            'filter' => ['=REF_REQUEST' => $requestId],
            'order' => ['ID' => 'DESC'],
        ])->fetchAll();
        
        // Добавляем имена пользователей
        foreach ($history as &$item) {
            $item['USER_NAME'] = $this->getUserName($item['USER_DECISION_MAKER']);
        }
        
        $this->arResult['HISTORY'] = $history;
        $this->arResult['REQUEST_ID'] = $requestId;
        $this->arResult['STATUS_LIST'] = \Company\AccessRequest\AccessRequestTable::getStatusList();
        
        $this->includeComponentTemplate();
    }
    /**
     * Возвращает имя пользователя по ID
     * 
     * @param int $userId ID пользователя
     * @return string
     */
    protected function getUserName(int $userId): string
    {
        if ($userId <= 0) return '';
        
        $user = \Bitrix\Main\UserTable::getList([
            'select' => ['NAME', 'LAST_NAME', 'LOGIN'],
            'filter' => ['=ID' => $userId],
        ])->fetch();
        
        if ($user) {
            $name = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
            return $name ?: $user['LOGIN'];
        }
        
        return '';
    }
}