<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Grid\Options;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Container;
use Company\AccessRequest\AccessRequestTable;

Loc::loadMessages(__FILE__);

class AccessRequestListComponent extends CBitrixComponent
{
    protected $gridId = 'access_request_grid';
    protected $filterId = 'access_request_filter';
    protected $smartProcessEntityTypeId = 1042;

    public function onPrepareComponentParams($arParams)
    {
        return $arParams;
    }

    public function executeComponent()
    {
        Loader::includeModule('company.accessrequest');
        Loader::includeModule('crm');

        // 1. Инициализация грида и фильтра
        $gridOptions = new Options($this->gridId);
        $navParams = $gridOptions->GetNavParams();
        $sort = $gridOptions->GetSorting(['sort' => ['ID' => 'DESC']]);

        $filterOption = new FilterOptions($this->filterId);
        $filterData = $filterOption->getFilter($this->getFilters());

        // 2. Разделяем фильтры на две группы
        $smartProcessFilter = $this->prepareSmartProcessFilter($filterData);
        $accessRequestFilter = $this->prepareAccessRequestFilter($filterData);

        // 3. Получаем ID из access_request по фильтру (если есть фильтрация по полям access_request)
        $filteredRequestIds = $this->getFilteredRequestIds($accessRequestFilter);

        // 4. Если есть фильтр по access_request и он не дал результатов — выходим
        if ($filteredRequestIds !== null && empty($filteredRequestIds)) {
            $this->arResult['ROWS'] = [];
            $this->arResult['TOTAL_ROWS_COUNT'] = 0;
            $this->arResult['NAV_OBJECT'] = null;
            $this->includeComponentTemplate();
            return;
        }

        // 5. Добавляем ID в фильтр смарт-процесса
        if (!empty($filteredRequestIds)) {
            $smartProcessFilter['=UF_CRM_4_REQUEST_ID'] = $filteredRequestIds;
        }

        // 6. Получаем данные из смарт-процесса с учётом прав и пагинации
        $factory = Container::getInstance()->getFactory($this->smartProcessEntityTypeId);
        if (!$factory) {
            ShowError('Смарт-процесс не найден');
            return;
        }

        $smartItemsResult = $factory->getItemsFilteredByPermissions([
            'select' => ['ID', 'UF_CRM_4_REQUEST_ID', 'STAGE_ID', 'ASSIGNED_BY_ID', 'CREATED_TIME'],
            'filter' => $smartProcessFilter,
            'order' => ['ID' => 'DESC'],
            'limit' => $navParams['nPageSize'],
            'offset' => ($navParams['iNumPage']) * $navParams['nPageSize'],
            'count_total' => true,
        ], $this->getUserId());

        // В зависимости от версии, результат может быть массивом или объектом
        if (is_array($smartItemsResult)) {
            // Старая версия — возвращает массив
            $smartItems = $smartItemsResult;
            $totalCount = count($smartItems); // или нужно делать отдельный запрос для подсчёта
        } else {
            // Новая версия — возвращает объект с методами
            $smartItems = [];
            foreach ($smartItemsResult as $item) {
                $smartItems[] = $item;
            }
            $totalCount = $smartItemsResult->getCount();
        }

        $smartItems = [];
        foreach ($smartItemsResult as $item) {
            $smartItems[] = $item;
            $requestIds[] = $item->get('UF_CRM_4_REQUEST_ID');
        }

        // 7. Если нет элементов — выводим пустой грид
        if (empty($smartItems)) {
            $this->arResult['ROWS'] = [];
            $this->arResult['TOTAL_ROWS_COUNT'] = $totalCount;
            $this->setupGridAndIncludeTemplate($gridOptions, $navParams, $sort, $totalCount);
            return;
        }

        // 8. Получаем данные из access_request по ID из смарт-процесса
        $requestsData = [];
        if (!empty($requestIds)) {
            $list = AccessRequestTable::getList([
                'select' => ['*'],
                'filter' => [
                    'LOGIC' => 'OR',
                    [
                        '=ID' => $requestIds,
                    ],
                    [
                        '=STATUS' => 0,
                        '=REF_CREATE_USER' => $this->getUserId(),
                    ],
                ],
            ]);
            while ($row = $list->fetch()) {
                $requestsData[$row['ID']] = $row;
            }
        }

        // 9. Формируем строки для грида
        $rows = [];
        foreach ($smartItems as $item) {
            $requestId = $item->get('UF_CRM_4_REQUEST_ID');
            $request = $requestsData[$requestId] ?? null;
            
            if (!$request) {
                continue;
            }

            $rows[] = [
                'data' => $request,
                'columns' => [
                    'ID' => $item->getId(),
                    'EMPLOYEE_NAME' => $request['EMPLOYEE_NAME'],
                    'REQUEST_STATUS' => AccessRequestTable::getStatusName($request['STATUS']),
                    'CREATED_DATE' => $request['CREATED_DATE'] ? $request['CREATED_DATE']->toString() : '',
                    'SMART_STAGE' => $this->getStageName($item->getStageId()),
                    'SMART_ASSIGNED' => $this->getUserName($item->get('ASSIGNED_BY_ID')),
                    'SMART_CREATED' => $item->get('CREATED_TIME') ? $item->get('CREATED_TIME')->toString() : '',
                ],
                'actions' => $this->getRowActions($request),
            ];
        }

        // 10. Пагинация для грида
        $cdbResult = new \CDBResult();
        $cdbResult->InitFromArray($rows);
        $cdbResult->NavNum = $navParams['iNumPage'];
        $cdbResult->NavPageSize = $navParams['nPageSize'];
        $cdbResult->NavRecordCount = $totalCount;
        $cdbResult->NavPageCount = ceil($totalCount / $navParams['nPageSize']);

        // 11. Передаём в шаблон
        $this->arResult['GRID_ID'] = $this->gridId;
        $this->arResult['FILTER_ID'] = $this->filterId;
        $this->arResult['COLUMNS'] = $this->getColumns();
        $this->arResult['FILTERS'] = $this->getFilters();
        $this->arResult['ROWS'] = $rows;
        $this->arResult['NAV_OBJECT'] = $cdbResult;
        $this->arResult['TOTAL_ROWS_COUNT'] = $totalCount;
        $this->arResult['SORT'] = $sort['sort'];
        $this->arResult['SORT_VARS'] = $sort['vars'];

        $this->includeComponentTemplate();
    }

    /**
     * Возвращает список колонок грида
     */
    protected function getColumns()
    {
        return [
            ['id' => 'ID', 'name' => 'ID СП', 'sort' => 'ID', 'default' => true],
            ['id' => 'EMPLOYEE_NAME', 'name' => Loc::getMessage('COLUMN_EMPLOYEE_NAME'), 'default' => true],
            ['id' => 'REQUEST_STATUS', 'name' => 'Статус заявки', 'default' => true],
            ['id' => 'SMART_STAGE', 'name' => 'Стадия СП', 'default' => true],
            ['id' => 'CREATED_DATE', 'name' => Loc::getMessage('COLUMN_CREATED_DATE'), 'sort' => 'CREATED_DATE', 'default' => true],
            ['id' => 'SMART_ASSIGNED', 'name' => 'Ответственный в СП', 'default' => false],
            ['id' => 'SMART_CREATED', 'name' => 'Дата создания в СП', 'default' => false],
        ];
    }

    /**
     * Возвращает список полей фильтра
     */
    protected function getFilters()
    {
        return [
            ['id' => 'ID', 'name' => 'ID заявки', 'type' => 'number'],
            ['id' => 'EMPLOYEE_NAME', 'name' => Loc::getMessage('FILTER_EMPLOYEE_NAME'), 'type' => 'string'],
            [
                'id' => 'SMART_STAGE',
                'name' => 'Стадия СП',
                'type' => 'list',
                'items' => $this->getStageList(),
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
        if (!empty($filterData['ID'])) {
            $filter['=ID'] = $filterData['ID'];
        }
        return $filter;
    }

    /**
     * Подготавливает фильтр для смарт-процесса
     */
    protected function prepareSmartProcessFilter($filterData)
    {
        $filter = [];
        if (!empty($filterData['SMART_STAGE'])) {
            $filter['=STAGE_ID'] = $filterData['SMART_STAGE'];
        }
        return $filter;
    }

    /**
     * Возвращает ID заявок из access_request по фильтру
     * Возвращает null, если фильтра нет
     */
    protected function getFilteredRequestIds(array $filter)
    {
        if (empty($filter)) {
            return null;
        }

        $result = AccessRequestTable::getList([
            'select' => ['ID'],
            'filter' => $filter,
        ]);
        
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
        
        // getStageList() уже возвращает коллекцию объектов EO_Status
        $stageCollection = $factory->getStages();
        // var_dump($stageCollection);
        // die();
        
        //->getStageList();
        
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
        return [
            [
                'text' => Loc::getMessage('ACTION_OPEN'),
                'onclick' => "window.location.href='/dlya-sotrudnikov/list-dopuska/item/?ID=" . $row['ID'] . "'",
                'default' => true,
            ],
        ];
    }

    /**
     * Настройка грида и шаблона при пустом результате
     */
    protected function setupGridAndIncludeTemplate($gridOptions, $navParams, $sort, $totalCount)
    {
        $this->arResult['GRID_ID'] = $this->gridId;
        $this->arResult['FILTER_ID'] = $this->filterId;
        $this->arResult['COLUMNS'] = $this->getColumns();
        $this->arResult['FILTERS'] = $this->getFilters();
        $this->arResult['SORT'] = $sort['sort'];
        $this->arResult['SORT_VARS'] = $sort['vars'];
        $this->arResult['TOTAL_ROWS_COUNT'] = $totalCount;
        
        // Создаём пустой CDBResult для пагинации
        $cdbResult = new \CDBResult();
        $cdbResult->InitFromArray([]);
        $cdbResult->NavNum = $navParams['iNumPage'];
        $cdbResult->NavPageSize = $navParams['nPageSize'];
        $cdbResult->NavRecordCount = $totalCount;
        $cdbResult->NavPageCount = ceil($totalCount / $navParams['nPageSize']);
        $this->arResult['NAV_OBJECT'] = $cdbResult;
        
        $this->includeComponentTemplate();
    }
}