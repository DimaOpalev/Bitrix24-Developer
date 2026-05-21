<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Grid\Options;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Container;
use Company\AccessRequest\AccessRequestTable;
use Bitrix\Main\UI\PageNavigation;

Loc::loadMessages(__FILE__);

class AccessRequestListComponent extends CBitrixComponent
{
    protected $toolbarId = 'access_request_toolbar';
    protected $gridId = 'access_request_grid';
    protected $filterId = 'access_request_grid';

    protected $smartProcessEntityTypeId = null;

    public function onPrepareComponentParams($arParams)
    {
        Loader::includeModule('company.accessrequest');
        $moduleId = COMPANY_ACCESSREQUEST_MODULE_ID;
        $arParams['ADD_BUTTON_URL'] = trim($arParams['ADD_BUTTON_URL'] ?? '/item/');
        $arParams['ITEM_URL'] = trim($arParams['ITEM_URL'] ?? '/item/?ID=#ID#');
        $this->smartProcessEntityTypeId = (int)\Bitrix\Main\Config\Option::get($moduleId, 'entity_type_id', 0);

        return $arParams;
    }

    protected function setRows($request, $item): array 
    {
        // Формируем ссылку на задачу
        $taskLink = '';
        if (!empty($request['REF_TASK'])) {
            $taskLink = '<a href="/company/personal/user/0/tasks/task/view/' . $request['REF_TASK'] . '/">Задача #' . $request['REF_TASK'] . '</a>';
        }
        return [
            'data' => $request,
            'columns' => [
                'REQUEST_ID' => $request['ID'],
                'EMPLOYEE_NAME' => $request['EMPLOYEE_NAME'],
                'REQUEST_STATUS' => '<span class="badge '.AccessRequestTable::BADGE_STATUS[$request['STATUS']].'">'.AccessRequestTable::getStatusName($request['STATUS']).'</span>',
                'CREATED_DATE' => $request['CREATED_DATE'] ? $request['CREATED_DATE']->toString() : '',
                'SMART_STAGE' => $item ? $this->getStageName($item->getStageId()) : null,
                'SMART_ASSIGNED' => $item ? $this->getUserName($item->get('ASSIGNED_BY_ID')) : null,
                'SMART_CREATED' => $item ? ($item->get('CREATED_TIME') ? $item->get('CREATED_TIME')->toString() : '') : null,
                'REF_TASK' => $taskLink,
            ],
            'actions' => $this->getRowActions($request),
        ];
    }

    protected function getFilterFields(): array
    {
        return [
            [
                'id' => 'EMPLOYEE_NAME',
                'name' => GetMessage('FILTER_EMPLOYEE_NAME'),
                'type' => 'string',
            ],
            [
                'id' => 'STATUS',
                'name' => GetMessage('FILTER_STATUS'),
                'type' => 'list',
                'items' => AccessRequestTable::getStatusList(),
            ],
        ];
    }

    public function executeComponent()
    {
        Loader::includeModule('company.accessrequest');
        Loader::includeModule('crm');
        Loader::includeModule('ui');

        // 1. Инициализация грида и фильтра
        // Вместо ручного чтения из $_GET
        $gridOptions = new Options($this->gridId);
        $navParams = $gridOptions->GetNavParams();

        $currentPage = max(1, (int)($navParams['iNumPage'] ?? 1));
        $pageSize = max(1, (int)($navParams['nPageSize'] ?? 20));

        $nav = new PageNavigation($this->gridId);

        $nav->allowAllRecords(true)
            ->setPageSize($pageSize)
            ->initFromUri();

        $currentPage = $nav->getCurrentPage();

        // Получаем сортировку от пользователя
        $userSort = $gridOptions->GetSorting(['sort' => ['ID' => 'DESC']]);
        $sort = $userSort['sort'];

        // Сортировка для смарт-процесса (разрешенные поля)
        $allowedSmartSortFields = ['ID', 'CREATED_TIME', 'STAGE_ID', 'ASSIGNED_BY_ID'];
        $smartOrder = [];
        foreach ($sort as $field => $direction) {
            if (in_array($field, $allowedSmartSortFields)) {
                $smartOrder[$field] = $direction;
            }
        }
        if (empty($smartOrder)) {
            $smartOrder = ['ID' => 'DESC'];
        }

        // Сортировка для access_request (разрешенные поля)
        $allowedAccessSortFields = ['ID', 'CREATED_DATE', 'STATUS', 'EMPLOYEE_NAME'];
        $accessOrder = [];
        foreach ($sort as $field => $direction) {
            if (in_array($field, $allowedAccessSortFields)) {
                $accessOrder[$field] = $direction;
            }
        }
        $accessOrderFinal = !empty($accessOrder) ? $accessOrder : ['ID' => 'DESC'];

        // Получаем данные фильтра
        $filterOption = new FilterOptions($this->filterId);
        $filterData = $filterOption->getFilter($this->getFilters());

        $this->arResult['HIDE_FILTER'] = $this->arParams['HIDE_FILTER'] === 'Y';
        $this->arResult['HIDE_TOOLBAR'] = $this->arParams['HIDE_TOOLBAR'] === 'Y';

        if (!$this->arResult['HIDE_TOOLBAR']) {
            // Подготавливаем данные для тулбара
            $this->arResult['TOOLBAR'] = [
                'BUTTONS' => [
                    [
                        'text' => Loc::getMessage('ADD_REQUEST_BUTTON'),
                        'link' => $this->arParams['ADD_BUTTON_URL'],
                        'color' => \Bitrix\UI\Buttons\Color::PRIMARY,
                    ],
                ],
                'FILTER' => [
                    'GRID_ID' => $this->gridId,
                    'FILTER_ID' => $this->filterId,
                    'FILTER' => $this->getFilters(),
                    'ENABLE_LIVE_SEARCH' => true,
                ],
            ];
        }

        // Подготавливаем фильтры
        $smartProcessFilter = $this->prepareSmartProcessFilter($filterData);

        // Получаем ID из смарт-процесса (для связанных заявок)
        $factory = Container::getInstance()->getFactory($this->smartProcessEntityTypeId);
        if (!$factory) {
            ShowError('Смарт-процесс не найден');
            return;
        }

        $smartItemsResult = $factory->getItemsFilteredByPermissions([
            'select' => ['ID', 'TITLE', 'UF_CRM_4_REQUEST_ID', 'STAGE_ID', 'ASSIGNED_BY_ID', 'CREATED_TIME'],
            'filter' => $smartProcessFilter,
            'order' => $smartOrder,
        ], $this->getUserId());

        $smartRequestIds = [];
        $smartItemsById = [];

        if (is_array($smartItemsResult)) {
            foreach ($smartItemsResult as $item) {
                $requestId = $item['UF_CRM_4_REQUEST_ID'];
                if ($requestId) {
                    $smartRequestIds[] = $requestId;
                    $smartItemsById[$requestId] = $item;
                }
            }
        } else {
            foreach ($smartItemsResult as $item) {
                $requestId = $item->get('UF_CRM_4_REQUEST_ID');
                if ($requestId) {
                    $smartRequestIds[] = $requestId;
                    $smartItemsById[$requestId] = $item;
                }
            }
        }

        // Формируем фильтр для access_request (включая новые заявки со статусом 0)
        $accessFilter = [
            'LOGIC' => 'AND',
            [
                'LOGIC' => 'OR',
                [
                    '=ID' => !empty($smartRequestIds) ? $smartRequestIds : 0,
                ],
                [
                    '=STATUS' => 0,
                    '=REF_CREATE_USER' => $this->getUserId(),
                ],
            ],
        ];

        if (!empty($smartProcessFilter["%TITLE"])) {
            $accessFilter[] = [
                '%REQUESTED_ACCESS' => $smartProcessFilter["%TITLE"]
            ];
        }

        // Если передан принудительный фильтр (например, из вкладки контакта)
        if (!empty($this->arParams['FORCE_FILTER']) && is_array($this->arParams['FORCE_FILTER'])) {
            $forceFilter = $this->arParams['FORCE_FILTER'];
            // Применяем его к основному фильтру
            if (empty($accessFilter)) {
                $accessFilter = $forceFilter;
            } else {
                $accessFilter = [
                    'LOGIC' => 'AND',
                    $accessFilter,
                    $forceFilter,
                ];
            }
        }

        // Получаем общее количество
        $totalCount = AccessRequestTable::getList([
            'select' => ['CNT' => new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')],
            'filter' => $accessFilter,
        ])->fetch()['CNT'];
        $nav->setRecordCount($totalCount);

        \Bitrix\Main\Application::getConnection()->startTracker();

        // Получаем данные с учётом пагинации
        $list = AccessRequestTable::getList([
            'select' => ['*'],
            'filter' => $accessFilter,
            'order' => $accessOrderFinal,
            'limit' => $nav->getLimit(),
            'offset' => $nav->getOffset(),
        ]);

        // Формируем строки для грида
        $rows = [];
        while ($row = $list->fetch()) {
            $smartItem = $smartItemsById[$row['ID']] ?? null;
            $rows[] = $this->setRows($row, $smartItem);
        }

        $this->arResult['NAV_OBJECT'] = $nav;
        // Передаём в шаблон
        $this->arResult['GRID_ID'] = $this->gridId;
        $this->arResult['FILTER_ID'] = $this->filterId;
        $this->arResult['COLUMNS'] = $this->getColumns();
        $this->arResult['FILTERS'] = $this->getFilters();
        $this->arResult['ROWS'] = $rows;


        $this->arResult['TOTAL_ROWS_COUNT'] = $totalCount;
        $this->arResult['SORT'] = $sort;
        $this->arResult['SORT_VARS'] = $userSort['vars'];

        $this->arResult['ADD_BUTTON_URL'] = $this->arParams['ADD_BUTTON_URL'];
        $this->arResult['ADD_REQUEST_BUTTON_TEXT'] = Loc::getMessage('ADD_REQUEST_BUTTON');
        $this->arResult['ITEM_URL'] = $this->arParams['ITEM_URL'];

        $this->includeComponentTemplate();
    }

    /**
     * Возвращает список колонок грида
     */
    protected function getColumns()
    {
        return [
            ['id' => 'REQUEST_ID', 'name' => 'ID заявки', 'sort' => 'ID', 'default' => true],
            ['id' => 'EMPLOYEE_NAME', 'name' => Loc::getMessage('COLUMN_EMPLOYEE_NAME'), 'sort' => 'EMPLOYEE_NAME', 'default' => true],
            ['id' => 'REQUEST_STATUS', 'name' => 'Статус заявки', 'sort' => 'STATUS', 'default' => true],
            ['id' => 'SMART_STAGE', 'name' => 'Стадия СП', 'sort' => 'STAGE_ID', 'default' => true],
            ['id' => 'CREATED_DATE', 'name' => Loc::getMessage('COLUMN_CREATED_DATE'), 'sort' => 'CREATED_DATE', 'default' => true],
            ['id' => 'SMART_ASSIGNED', 'name' => 'Ответственный в СП', 'sort' => 'ASSIGNED_BY_ID', 'default' => false],
            ['id' => 'SMART_CREATED', 'name' => 'Дата создания в СП', 'sort' => 'CREATED_TIME', 'default' => false],
            ['id' => 'REF_TASK', 'name' => Loc::getMessage('COLUMN_TASK'), 'default' => true],

        ];
    }

    /**
     * Возвращает список полей фильтра
     */
    protected function getFilters()
    {
        $stageList = $this->getStageList();
        
        return [
            ['id' => 'ID', 'name' => 'ID заявки', 'type' => 'number'],
            ['id' => 'EMPLOYEE_NAME', 'name' => Loc::getMessage('FILTER_EMPLOYEE_NAME'), 'type' => 'string'],
            [
                'id' => 'SMART_STAGE',
                'name' => 'Стадия СП',
                'type' => 'list',
                'items' => $stageList,
            ],
        ];
    }

    /**
     * Подготавливает фильтр для access_request
     */
    protected function prepareAccessRequestFilter($filterData)
    {
        $filter = [];
        
        if (!empty($filterData['EMPLOYEE_NAME'])) {
            $filter['%EMPLOYEE_NAME'] = $filterData['EMPLOYEE_NAME'];
        }
        
        // ID теперь обрабатывается в смарт-процессе
        // не нужно добавлять его в фильтр access_request

        return $filter;
    }

    /**
     * Подготавливает фильтр для смарт-процесса
     */
    protected function prepareSmartProcessFilter($filterData)
    {
        $filter = [];

        // Фильтр по стадии смарт-процесса
        if (!empty($filterData['SMART_STAGE'])) {
            $filter['=STAGE_ID'] = $filterData['SMART_STAGE'];
        }

        // Фильтр по ID заявки (из access_request)
        if (!empty($filterData['ID'])) {
            $filter['=UF_CRM_4_REQUEST_ID'] = (int)$filterData['ID'];
        }

        // Глобальный поиск
        if (!empty($filterData['FIND'])) {
            $searchString = $filterData['FIND'];
            $filter['%TITLE'] = $searchString;
        }

        return $filter;
    }

    /**
     * Возвращает ID заявок из access_request по фильтру
     * Возвращает null, если фильтра нет
     */
    protected function getFilteredRequestIds(array $filter, array $order = [])
    {
        if (empty($filter)) {
            return null;
        }

        $params = [
            'select' => ['ID'],
            'filter' => $filter,
        ];

        if (!empty($order)) {
            $params['order'] = $order;
        }

        $result = AccessRequestTable::getList($params);

        $ids = [];
        while ($row = $result->fetch()) {
            $ids[] = $row['ID'];
        }

        return $ids;
    }

    /**
     * Возвращает список стадий для фильтра
     */
    protected function getStageList()
    {
        $stages = [];
        
        $factory = Container::getInstance()->getFactory($this->smartProcessEntityTypeId);
        if (!$factory) {
            return $stages;
        }
        
        $stageCollection = $factory->getStages();
        
        /** @var \Bitrix\Crm\EO_Status $stage */
        foreach ($stageCollection as $stage) {
            $stages[$stage->getStatusId()] = $stage->getName();
        }

        return $stages;
    }

    /**
     * Возвращает название стадии для отображения
     */
    protected function getStageName($stageId)
    {
        static $stages = null;
        if ($stages === null) {
            $stages = $this->getStageList();
        }
        return $stages[$stageId] ?? $stageId;
    }

    /**
     * Возвращает имя пользователя по ID
     */
    protected function getUserName(int $userId): string
    {
        if (!$userId) return '';
        
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

    /**
     * Возвращает ID текущего пользователя
     */
    protected function getUserId()
    {
        global $USER;
        return $USER->GetID();
    }

    /**
     * Действия для строки грида
     */
    protected function getRowActions($row)
    {
        $url = str_replace('#ID#', $row['ID'], $this->arParams['ITEM_URL']);
        return [
            [
                'text' => Loc::getMessage('ACTION_OPEN'),
                'onclick' => "window.location.href='" . $url . "'",
                'default' => true,
            ],
        ];
    }

    /**
     * Настройка грида и шаблона при пустом результате
     */
    protected function setupGridAndIncludeTemplate($gridOptions, $navParams, $userSort, $totalCount)
    {
        // TOOLBAR уже должен быть установлен в executeComponent
        // но на случай, если метод вызван до установки, продублируем
        if (!isset($this->arResult['TOOLBAR'])) {
            $this->arResult['TOOLBAR'] = [
                'BUTTONS' => [
                    [
                        'text' => Loc::getMessage('ADD_REQUEST_BUTTON'),
                        'link' => $this->arParams['ADD_BUTTON_URL'],
                        'color' => \Bitrix\UI\Buttons\Color::PRIMARY,
                    ],
                ],
                'FILTER' => [
                    'GRID_ID' => $this->gridId,
                    'FILTER_ID' => $this->filterId,
                    'FILTER' => $this->getFilters(),
                    'ENABLE_LIVE_SEARCH' => true,
                ],
            ];
        }
        
        $this->arResult['GRID_ID'] = $this->gridId;
        $this->arResult['FILTER_ID'] = $this->filterId;
        $this->arResult['COLUMNS'] = $this->getColumns();
        $this->arResult['FILTERS'] = $this->getFilters();
        $this->arResult['ROWS'] = [];
        $this->arResult['SORT'] = $userSort['sort'];
        $this->arResult['SORT_VARS'] = $userSort['vars'];
        $this->arResult['TOTAL_ROWS_COUNT'] = $totalCount;
        
        // Создаём пустой CDBResult для пагинации
        $cdbResult = new \CDBResult();
        $cdbResult->InitFromArray([]);
        $cdbResult->NavNum = $navParams['iNumPage'];
        $cdbResult->NavPageSize = $navParams['nPageSize'];
        $cdbResult->NavRecordCount = $totalCount;
        $cdbResult->NavPageCount = ceil($totalCount / $navParams['nPageSize']);
        $cdbResult->NavStart($navParams['nPageSize'], $navParams['iNumPage']);
        $this->arResult['NAV_OBJECT'] = $cdbResult;
        
        $this->includeComponentTemplate();
    }
}