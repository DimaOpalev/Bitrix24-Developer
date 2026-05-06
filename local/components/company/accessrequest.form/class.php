<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Company\AccessRequest\AccessRequestTable;
use Company\AccessRequest\AccessRequestHistoryTable;
use Bitrix\Main\Context;

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

        // Если редактируем существующую заявку
        if ($this->arParams['ID'] > 0) {
            $this->loadRequestData($this->arParams['ID']);
            $this->arResult['READONLY'] = $this->isReadOnly();
        } else {
            $this->arResult['READONLY'] = false;
            $this->arResult['REQUEST'] = [];
        }

        // Обработка POST (сохранение или отправка на согласование)
        if (check_bitrix_sessid()) {
            $this->handlePost();
        }

        // Подготовка данных для шаблона
        $this->arResult['STATUS_LIST'] = AccessRequestTable::getStatusList();
        $this->arResult['ACCESS_RULES'] = [
            'all'    => Loc::getMessage('ACCESS_ALLOW'),
            'cancel' => Loc::getMessage('ACCESS_DENY'),
        ];
        $this->arResult['CURRENT_STATUS'] = $this->arResult['REQUEST']['STATUS'] ?? AccessRequestTable::STATUS_NEW;

        $this->arResult['BACK_URL'] = $this->arParams['BACK_URL'];

        $this->includeComponentTemplate();
    }

    /*
    protected function loadAccessDirectory()
    {
        // Получаем структуру из инфоблока "list_dopuska"
        $result = [];
        $sections = [];
        $elements = [];

        $rsSections = CIBlockSection::GetList(
            ['SORT' => 'ASC'],
            ['IBLOCK_CODE' => 'list_dopuska', 'ACTIVE' => 'Y'],
            false,
            ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'DEPTH_LEVEL']
        );

        while ($section = $rsSections->Fetch()) {
            $sections[$section['ID']] = $section;
        }

        $rsElements = CIBlockElement::GetList(
            ['IBLOCK_SECTION_ID' => 'ASC', 'SORT' => 'ASC'],
            ['IBLOCK_CODE' => 'list_dopuska', 'ACTIVE' => 'Y', 'SECTION_ID' => array_keys($sections)],
            false,
            false,
            ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'CODE']
        );

        while ($element = $rsElements->Fetch()) {
            $elements[$element['ID']] = $element;
        }

        $this->arResult['ACCESS_SECTIONS'] = $sections;
        $this->arResult['ACCESS_ELEMENTS'] = $elements;

    }
    */

    protected function loadRequestData($id)
    {
        $request = AccessRequestTable::getById($id)->fetch();
        if (!$request) {
            LocalRedirect($this->arParams['BACK_URL']);
        }

        // Декодируем список доступов
        $request['REQUESTED_ACCESS_DECODED'] = json_decode($request['REQUESTED_ACCESS'], true) ?: [];

        // Устанавливаем значения в $_POST-подобный массив для шаблона
        $this->arResult['REQUEST'] = $request;
        $this->arResult['POST_DATA'] = $request['REQUESTED_ACCESS_DECODED'];
    }

    protected function isReadOnly()
    {
        // Если заявка уже согласована или отклонена — только чтение
        $status = $this->arResult['REQUEST']['STATUS'];
        return in_array($status, [
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

        // Устанавливаем связь с заявкой
        $item->set($requestIdFieldName, $requestId);

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

        // Добавляем запись в историю
        AccessRequestHistoryTable::add([
            'REF_REQUEST' => $requestId,
            'USER_DECISION_MAKER' => $GLOBALS['USER']->GetID(),
            'STATUS' => AccessRequestTable::STATUS_REVIEW,
            'COMMENT' => Loc::getMessage('REQUEST_SENT'),
        ]);
        
        return $smartProcessId;
        // Здесь логика запуска бизнес-процесса или создания задачи
        // Например, создаём запись в истории
        /*
        AccessRequestHistoryTable::add([
            'REF_REQUEST' => $requestId,
            'USER_DECISION_MAKER' => $GLOBALS['USER']->GetID(),
            'STATUS' => AccessRequestTable::STATUS_REVIEW,
            'COMMENT' => 'Отправлено на согласование: ' . ($post['COMMENT'] ?? ''),
        ]);

        // Обновляем статус заявки
        AccessRequestTable::update($requestId, ['STATUS' => AccessRequestTable::STATUS_REVIEW]);
        */
        // TODO: здесь можно запустить реальный бизнес-процесс или поставить задачу руководителю
        // Например, через CTaskItem::add, или через запуск БП методом CBPDocument::StartWorkflow
    }
}