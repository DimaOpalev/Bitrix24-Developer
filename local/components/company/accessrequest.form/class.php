<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Crm\ContactTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Company\AccessRequest\AccessRequestTable;
use Company\AccessRequest\AccessRequestHistoryTable;
use Bitrix\Main\Grid\Options;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\Context;
use Bitrix\Main\SystemException;

class AccessRequestFormComponent extends CBitrixComponent
{
    protected $gridId = 'access_request_form_grid';
    protected $filterId = 'access_request_form_filter';

    public function onPrepareComponentParams($arParams)
    {
        $arParams['ID'] = (int)($arParams['ID'] ?? $_GET['ID'] ?? 0);
        $arParams['ACTION'] = $arParams['ACTION'] ?? $_GET['ACTION'] ?? 'new';
        $arParams['BACK_URL'] = $arParams['BACK_URL'] ?? '../';
        return $arParams;
    }

    /**
     * Точка входа в компонент
     * 
     */
    public function executeComponent()
    {
        Loader::includeModule('company.accessrequest');
        Loader::includeModule('iblock'); // для получения справочника доступов

        CJSCore::Init(array('jquery3'));

        global $USER;

        // Загружаем справочник доступов (инфоблок list_dopuska)
        $this->arResult = AccessRequestTable::loadAccessDirectory();

        $this->arResult['USER_ID'] = $USER->GetID();
        $this->arResult['IS_ADMIN'] = $USER->IsAdmin();

        $this->arResult['ID'] = $this->arParams['ID'];

        // Если редактируем существующую заявку
        if ($this->arParams['ID'] > 0) {
            $is_load_request = $this->loadRequestData($this->arParams['ID']);
            if(!$is_load_request) {
                return;
            }
            $this->arResult['READONLY'] = $this->isReadOnly();
        } else {
            $this->arResult['READONLY'] = false;
            $this->arResult['REQUEST'] = [];
        }

        // Подготовка данных для шаблона
        $this->arResult['STATUS_LIST'] = AccessRequestTable::getStatusList();
        $this->arResult['ACCESS_RULES'] = [
            'all'    => Loc::getMessage('ACCESS_ALLOW'),
            'cancel' => Loc::getMessage('ACCESS_DENY'),
        ];
        $this->arResult['CURRENT_STATUS'] = $this->arResult['REQUEST']['STATUS'] ?? AccessRequestTable::STATUS_NEW;

        $this->arResult['BACK_URL'] = $this->arParams['BACK_URL'];

        // Обработка POST (сохранение или отправка на согласование)
        if (check_bitrix_sessid()) {
            $this->handlePost();
        }

        /**
         * Подготовка грида gridAccessRequestHistrory
         */
        $this->gridAccessRequestHistrory();

        $this->includeComponentTemplate();
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
            "=REF_REQUEST" => $this->arParams['ID']
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

    /**
     * Загрузка данных для запроса
     */
    protected function loadRequestData($id)
    {
        global $USER;
        $accessCheck = AccessRequestTable::checkAccessRightElementByID($id);

        $request = AccessRequestTable::getById($id)->fetch();

        if($request['REF_CREATE_USER'] != $USER->GetID() && $request['STATUS'] === AccessRequestTable::STATUS_NEW) {
            ShowError('Недостаточно прав для просмотра элемента');
            return false;
            // LocalRedirect($this->arParams['BACK_URL']);
        }

        if (!$accessCheck['ACCESS'] && $request['STATUS'] != AccessRequestTable::STATUS_NEW) {
            ShowError('Недостаточно прав для просмотра элемента');
            return false;
            // LocalRedirect($this->arParams['BACK_URL']);
        }

        if (!$request) {
            ShowError('Заявка не найдена');
            return false;
            // LocalRedirect($this->arParams['BACK_URL']);
        }

        // Декодируем список доступов
        $request['REQUESTED_ACCESS_DECODED'] = json_decode($request['REQUESTED_ACCESS'], true) ?: [];

        // Устанавливаем значения в $_POST-подобный массив для шаблона
        $this->arResult['REQUEST'] = $request;
        $this->arResult['POST_DATA'] = $request['REQUESTED_ACCESS_DECODED'];
        return true;
    }

    protected function isReadOnly()
    {
        // Если заявка уже согласована или отклонена — только чтение
        $status = $this->arResult['REQUEST']['STATUS'];
        return in_array($status, [
            AccessRequestTable::STATUS_REVIEW,
            AccessRequestTable::STATUS_APPROVED,
            AccessRequestTable::STATUS_REJECTED,
            AccessRequestTable::STATUS_CANCELLED,
            AccessRequestTable::STATUS_COMPLETED,
        ]);
    }

    protected function handlePost()
    {
        global $USER;
        
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();

        if (!$request->isPost()) {
            return;
        }

        $post["POST_DATA"] = $request->getPostList()->toArray();

        // Валидация
        $errors = [];
        if (empty($post["POST_DATA"]['FIO'])) $errors['FIO'] = Loc::getMessage('ERROR_FIO');
        if (empty($post["POST_DATA"]['REF_DEPARTMENT'])) $errors['insertCompanyStructure'] = Loc::getMessage('ERROR_DEPARTMENT');

        $everythingCancelled = true;

        // Проверка комментариев для выбранных "other" доступов
        if (!empty($post["POST_DATA"]['user_value'])) {
            foreach ($post["POST_DATA"]['user_value'] as $elementId => $value) {
                if($post["POST_DATA"]['user_value'][$elementId] === "all") {
                    $everythingCancelled = false;
                }
                if ($value === 'all' && empty($post["POST_DATA"]['user_other'][$elementId]) && $this->isOtherElement($elementId)) {
                    $errors['row_' . $elementId] = Loc::getMessage('ERROR_COMMENT_REQUIRED');
                }
            }
        }

        if ($everythingCancelled) $errors['everythingCancelled'] = Loc::getMessage('ERROR_EVERYTHING_CANCELED');


        if (!empty($errors)) {
            $this->arResult['ERRORS'] = $errors;
            $this->arResult = array_merge($this->arResult, $post);
            return;
        }

        unset($post["POST_DATA"]["sessid"]);
        unset($post["POST_DATA"]["ID"]);
        // Сохраняем заявку
        $fields = [
            'REF_CREATE_USER' => $USER->GetID(),
            'REF_DEPARTMENT' => (int)$post["POST_DATA"]['REF_DEPARTMENT'],
            'EMPLOYEE_NAME' => $post["POST_DATA"]['FIO'],
            'REQUESTED_ACCESS' => json_encode($post["POST_DATA"], JSON_UNESCAPED_UNICODE),
            'STATUS' => AccessRequestTable::STATUS_NEW,
            'UPDATED_DATE' => new DateTime(),
        ];

        if ($this->arParams['ID'] > 0) {
            $result = AccessRequestTable::update($this->arParams['ID'], $fields);
            $requestId = $this->arParams['ID'];
        } else {
            $fields['CREATED_DATE'] = new DateTime();
            $result = AccessRequestTable::add($fields);
            $requestId = $result->getId();
        }

        if (!$result->isSuccess()) {
            $this->arResult['ERRORS']['db'] = implode(', ', $result->getErrorMessages());
            return;
        }

        // Если нажата кнопка "Отправить на согласование" — запускаем бизнес-процесс или создаём задачу
        if (isset($post['POST_DATA']['SendMatching'])) {
            AddMessage2Log(
                print_r([
                    "requestId" => $requestId, 
                    "post" => $post
                ], true), 'accessrequest'
            );
            $this->startApprovalProcess($requestId, $post);
        }

        LocalRedirect($this->arResult['BACK_URL']);
    }

    protected function isOtherElement(int $elementId): bool
    {
        $el = $this->arResult['ACCESS_ELEMENTS'][$elementId] ?? null;
        return $el && $el['CODE'] === 'other';
    }

    /**
     * Запуск процесса утверждения
     */
    protected function startApprovalProcess(int $requestId, array $post)
    {
        global $USER;

        $moduleId = COMPANY_ACCESSREQUEST_MODULE_ID;
        $smartProcessEntityTypeId = (int)\Bitrix\Main\Config\Option::get($moduleId, 'entity_type_id', 0);
        if ($smartProcessEntityTypeId <= 0) {
            $this->arResult['ERRORS']['smart_process'] = Loc::getMessage('SMART_PROCESS_ID_NOT_SPECIFIED');
            //'Не указан ID смарт-процесса. Обратитесь к администратору.';
            return;
        }

        // Получаем имя поля для хранения ID заявки
        $requestIdFieldName = \Bitrix\Main\Config\Option::get($moduleId, 'request_id_field_name', '');
        if (empty($requestIdFieldName)) {
            $this->arResult['ERRORS']['field_name'] = Loc::getMessage('ERROR_FIELD_NOT_SET'). " request_id_field_name";
            //'Поле не задано';
            return;
        }

        // Подключаем модуль CRM
        if (!\Bitrix\Main\Loader::includeModule('crm')) {
            $this->arResult['ERRORS']['crm'] = Loc::getMessage('ERROR_CRM_MODULE_NOT_CONNECTED');
            //'Модуль CRM не доступен';
            return;
        }

        $contactId = $this->findAndCreatedContact($post);

        // Получаем фабрику для смарт-процесса
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($smartProcessEntityTypeId);
        if (!$factory) {
            $this->arResult['ERRORS']['smart_process'] = Loc::getMessage('ERROR_SMART_PROCESS_NOT_FOUND');
            //'Смарт-процесс не найден';
            return;
        }

        // Создаём элемент в смарт-процессе
        $item = $factory->createItem();
        $item->setTitle(Loc::getMessage('TITLE_EMPLOYEE_ACCESS') . ': ' .  $post["POST_DATA"]['FIO'] ?? "");

        $observerIds = [$USER->GetID()];
        $item->setObservers($observerIds);

        // Устанавливаем связь с заявкой
        $item->set($requestIdFieldName, $requestId);

        if ($contactId) {
            $item->set('CONTACT_ID', $contactId);  // Добавляем контакт в смарт-процесс
        }

        $operation = $factory->getAddOperation($item);
        $result = $operation->launch();

        if (!$result->isSuccess()) {
            $errors = implode(', ', $result->getErrorMessages());
            $this->arResult['ERRORS']['smart_process'] = Loc::getMessage('ERROR_CREATING_SMART_PROCESS').': ' . $errors;
            return;
        }

        $smartProcessId = (int)$item->getId();
        // Обновляем запись в таблице access_request, связывая с элементом смарт-процесса
        $updateResult = AccessRequestTable::update($requestId, [
            'REF_SMART_PROCESS_ID' => $smartProcessId,
            'STATUS' => AccessRequestTable::STATUS_REVIEW, // 10 - На рассмотрении
        ]);

        $this->addTimelineComment($smartProcessEntityTypeId, $smartProcessId, AccessRequestTable::generateHTML($requestId));

        return $smartProcessId;

    }

    /**
     * Создание/поиск контакта
     */
    private function findAndCreatedContact(array $post): ?int {
        // --- Создание/поиск контакта ---
        $fio = trim($post["POST_DATA"]['FIO'] ?? '');
        $phone = trim($post["POST_DATA"]['employeePhoneNumber'] ?? '');
        $contactId = null;

        $nameParts = array_pad(explode(' ', $fio, 3), 3, '');

        if (!empty($fio)) {
            // Поиск по телефону
            if (!empty($phone)) {
                $existing = \CCrmContact::GetListEx(
                    [],
                    ['PHONE' => $phone],
                    false,
                    false,
                    ['ID']
                )->Fetch();
                if ($existing) $contactId = (int)$existing['ID'];
            }
            // Поиск по ФИО (если не найден)
            if (!$contactId) {
                $searchFilter = ['LOGIC' => 'AND'];

                foreach ($nameParts as $part) {

                    $part = trim($part);

                    if (!$part) {
                        continue;
                    }

                    $searchFilter[] = [
                        '%SEARCH_CONTENT' => mb_strtoupper($part)
                    ];
                }

                if(!empty($searchFilter))  {
					$existing = ContactTable::getList([
						'select' => ['ID'],
						'filter' => $searchFilter,
						'limit' => 1
					])->fetch();
                }

                if ($existing) $contactId = (int)$existing['ID'];
            }
            // Создание нового контакта
            if (!$contactId) {
                // $nameParts = array_pad(explode(' ', $fio, 3), 3, '');
                $contactFields = [
                    'NAME' => $nameParts[1],
                    'LAST_NAME' => $nameParts[0],
                    'SECOND_NAME' => $nameParts[2],
                    'PHONE' => $phone ? [['VALUE' => $phone, 'VALUE_TYPE' => 'WORK']] : [],
                ];
                $contact = new \CCrmContact(false);
                $contactId = $contact->Add($contactFields);
                if (!$contactId) {
                    $this->arResult['ERRORS']['contact'] = 'Не удалось создать контакт';
                }
            }
        }
        return $contactId;
    }

    protected function addTimelineComment($smartProcessEntityTypeId, $smartProcessItemId, $commentText)
    {
        if (!Loader::includeModule('crm')) {
            return;
        }

        $commentData = [
            'TEXT' => $commentText, // Текст комментария
            'AUTHOR_ID' => AccessRequestTable::getUserId(), // ID текущего пользователя
            'BINDINGS' => [
                [
                    'ENTITY_TYPE_ID' => $smartProcessEntityTypeId, // Прямое указание типа для смарт-процессов
                    'ENTITY_ID' => $smartProcessItemId,
                ]
            ],
            // 'FILES' => [] // При необходимости добавьте файлы
        ];

        $entryId = \Bitrix\Crm\Timeline\CommentEntry::create($commentData);
    }
}
