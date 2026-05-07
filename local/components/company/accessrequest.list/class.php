<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Grid\Options;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Company\AccessRequest\AccessRequestTable;

class AccessRequestListComponent extends CBitrixComponent
{
    protected $gridId = 'access_request_grid';
    protected $filterId = 'access_request_filter';

    public function onPrepareComponentParams($arParams)
    {
        $arParams['ADD_BUTTON_URL'] = trim($arParams['ADD_BUTTON_URL'] ?? '/item/');
        $arParams['ITEM_URL'] = trim($arParams['ITEM_URL'] ?? '/item/?ID=#ID#');
        return $arParams;
    }

    public function executeComponent()
    {
        Loader::includeModule('company.accessrequest');

        $gridOptions = new Options($this->gridId);
        $navParams = $gridOptions->GetNavParams();
        $sort = $gridOptions->GetSorting(['sort' => ['ID' => 'DESC']]);

        $filterOption = new FilterOptions($this->filterId);
        $filterData = $filterOption->getFilter($this->getFilters());
        $filter = $this->prepareFilter($filterData);
        $filter = $this->applyAccessFilter($filter);

        // Получаем общее количество записей
        $totalCount = AccessRequestTable::getList([
            'select' => ['CNT' => new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')],
            'filter' => $filter,
        ])->fetch()['CNT'];

        // Получаем данные для текущей страницы
        $list = AccessRequestTable::getList([
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
                    'EMPLOYEE_NAME' => $row['EMPLOYEE_NAME'],
                    'STATUS' => '<span class="badge '.AccessRequestTable::BADGE_STATUS[$row['STATUS']].'">'.AccessRequestTable::getStatusName($row['STATUS']).'</span>',
                    'CREATED_DATE' => $row['CREATED_DATE'] ? $row['CREATED_DATE']->toString() : '',
                    'REF_CREATE_USER' => $this->getUserName($row['REF_CREATE_USER']),
                    'REF_TASK' => $row['REF_TASK'] ? '<a href="/company/personal/user/' . $row['REF_TASK'] . '/tasks/task/view/' . $row['REF_TASK'] . '/">Задача #' . $row['REF_TASK'] . '</a>' : '',
                ],
                'actions' => $this->getRowActions($row),
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
        $this->arResult['FILTER_ID'] = $this->filterId;
        $this->arResult['COLUMNS'] = $this->getColumns();
        $this->arResult['FILTERS'] = $this->getFilters();
        $this->arResult['ROWS'] = $rows;
        $this->arResult['NAV_OBJECT'] = $cdbResult;
        $this->arResult['SORT'] = $sort['sort'];
        $this->arResult['SORT_VARS'] = $sort['vars'];
        $this->arResult['TOTAL_ROWS_COUNT'] = $totalCount;

        $this->arResult['ADD_BUTTON_URL'] = $this->arParams['ADD_BUTTON_URL'];
        $this->arResult['ADD_REQUEST_BUTTON_TEXT'] = Loc::getMessage('ADD_REQUEST_BUTTON');
        $this->arResult['ITEM_URL'] = $this->arParams['ITEM_URL'];

        $this->includeComponentTemplate();
    }

    protected function getColumns()
    {
        Loc::loadMessages(__FILE__);
        return [
            ['id' => 'ID', 'name' => Loc::getMessage('COLUMN_ID'), 'sort' => 'ID', 'default' => true],
            ['id' => 'EMPLOYEE_NAME', 'name' => Loc::getMessage('COLUMN_EMPLOYEE_NAME'), 'sort' => 'EMPLOYEE_NAME', 'default' => true],
            ['id' => 'STATUS', 'name' => Loc::getMessage('COLUMN_STATUS'), 'sort' => 'STATUS', 'default' => true],
            ['id' => 'CREATED_DATE', 'name' => Loc::getMessage('COLUMN_CREATED_DATE'), 'sort' => 'CREATED_DATE', 'default' => true],
            ['id' => 'REF_CREATE_USER', 'name' => Loc::getMessage('COLUMN_CREATOR'), 'sort' => 'REF_CREATE_USER', 'default' => false],
            ['id' => 'REF_TASK', 'name' => Loc::getMessage('COLUMN_TASK'), 'default' => false],
        ];
    }

    protected function getFilters()
    {
        Loc::loadMessages(__FILE__);
        return [
            ['id' => 'ID', 'name' => Loc::getMessage('FILTER_ID'), 'type' => 'number'],
            ['id' => 'EMPLOYEE_NAME', 'name' => Loc::getMessage('FILTER_EMPLOYEE_NAME'), 'type' => 'string'],
            [
                'id' => 'STATUS',
                'name' => Loc::getMessage('FILTER_STATUS'),
                'type' => 'list',
                'items' => AccessRequestTable::getStatusList(),
            ],
        ];
    }

    protected function prepareFilter($filterData)
    {
        $filter = [];
        if (!empty($filterData['ID'])) {
            $filter['=ID'] = $filterData['ID'];
        }
        if (!empty($filterData['EMPLOYEE_NAME'])) {
            $filter['%EMPLOYEE_NAME'] = $filterData['EMPLOYEE_NAME'];
        }
        if (!empty($filterData['STATUS'])) {
            $filter['=STATUS'] = $filterData['STATUS'];
        }
        return $filter;
    }

    protected function applyAccessFilter($filter)
    {
        global $USER;
        if ($USER->IsAdmin()) {
            return $filter;
        }
        if ($USER->CanDoOperation('company.accessrequest_view_all_requests')) {
            return $filter;
        }
        if ($USER->CanDoOperation('company.accessrequest_view_department_requests')) {
            $userDepartments = \Bitrix\Main\UserTable::getList([
                'select' => ['UF_DEPARTMENT'],
                'filter' => ['=ID' => $USER->GetID()],
            ])->fetch();
            if ($userDepartments && !empty($userDepartments['UF_DEPARTMENT'])) {
                $filter['=REF_DEPARTMENT'] = $userDepartments['UF_DEPARTMENT'];
            }
            return $filter;
        }
        $filter['=REF_CREATE_USER'] = $USER->GetID();
        return $filter;
    }

    protected function getRowActions($row)
    {
        $itemUrl = str_replace('#ID#', $row['ID'], $this->arParams['ITEM_URL']);

        return [
            [
                'text' => Loc::getMessage('ACTION_OPEN'),
                'onclick' => "window.location.href='" . CUtil::JSEscape($itemUrl) . "'",
                'default' => true,
            ],
        ];
    }

    protected function getUserName($userId)
    {
        $user = \Bitrix\Main\UserTable::getList([
            'select' => ['NAME', 'LAST_NAME', 'LOGIN'],
            'filter' => ['=ID' => $userId],
        ])->fetch();
        if ($user) {
            $name = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
            return $name ?: $user['LOGIN'];
        }
        return (string)$userId;
    }
}