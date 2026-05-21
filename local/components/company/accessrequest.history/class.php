<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Company\AccessRequest\AccessRequestHistoryTable;
use Company\AccessRequest\AccessRequestTable;
use Bitrix\Main\Grid\Options;
use Bitrix\Main\Localization\Loc;

class AccessRequestHistoryComponent extends CBitrixComponent
{
    protected $gridId = 'AccessRequestHistrory';

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

        $this->gridAccessRequestHistrory();

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

    protected function getColumns()
    {
        Loc::loadMessages(__FILE__);
        return [
            ['id' => 'ID', 'name' => "ID", 'sort' => 'ID', 'default' => true],
            ['id' => 'USER_DECISION_MAKER', 'name' => "Ответственное лицо", 'sort' => 'USER_DECISION_MAKER', 'default' => true],
            ['id' => 'STATUS', 'name' => "Статус", 'sort' => 'STATUS', 'default' => true],
            ['id' => 'CREATED_DATE', 'name' => "Дата создания", 'sort' => 'CREATED_DATE', 'default' => true],
            ['id' => 'COMMENT', 'name' => "Комментарий", 'sort' => 'COMMENT', 'default' => false],
        ];
    }

    protected function gridAccessRequestHistrory() {
        $gridOptions = new Options($this->gridId);
        $navParams = $gridOptions->GetNavParams();
        $sort = $gridOptions->GetSorting(['sort' => ['ID' => 'ASC']]);

        $filter = [
            "=REF_REQUEST" => $this->arParams['REQUEST_ID']
        ];

        // Получаем общее количество записей
        $totalCount = AccessRequestHistoryTable::getList([
            'select' => ['CNT' => new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')],
            'filter' => $filter,
        ])->fetch()['CNT'];

        // Получаем данные для текущей страницы
        $list = AccessRequestHistoryTable::getList([
            'select' => ['*'],
            'filter' => $filter,
            'order' => $sort['sort'],
            'limit' => $navParams['nPageSize'],
            'offset' => ($navParams['iNumPage']) * $navParams['nPageSize'],
        ]);

        $rows = [];
        while ($row = $list->fetch()) {
            $rows[] = [
                'data' => $row,
                'columns' => [
                    'ID' => $row['ID'],
                    'USER_DECISION_MAKER' => AccessRequestTable::getUserName($row['USER_DECISION_MAKER']),
                    'STATUS' => AccessRequestTable::getStatusName($row['STATUS']),
                    'CREATED_DATE' => $row['CREATED_DATE'] ? $row['CREATED_DATE']->toString() : '',
                    'COMMENT' => $row['COMMENT'],
                ],
            ];
        }

        // Создаём объект CDBResult для пагинации
        $cdbResult = new \CDBResult();
        $cdbResult->InitFromArray($rows);
        $cdbResult->NavNum = $navParams['iNumPage'];
        $cdbResult->NavPageSize = $navParams['nPageSize'];
        $cdbResult->NavRecordCount = $totalCount;
        $cdbResult->NavPageCount = ceil($totalCount / $navParams['nPageSize']);
        $cdbResult->NavStart($navParams['nPageSize'], $navParams['iNumPage']);

        $this->arResult['GRID_ID'] = $this->gridId;
        $this->arResult['COLUMNS'] = $this->getColumns();
        $this->arResult['ROWS'] = $rows;
        $this->arResult['NAV_OBJECT'] = $cdbResult;
        $this->arResult['SORT'] = $sort['sort'];
        $this->arResult['SORT_VARS'] = $sort['vars'];
        $this->arResult['TOTAL_ROWS_COUNT'] = $totalCount;
    }
}