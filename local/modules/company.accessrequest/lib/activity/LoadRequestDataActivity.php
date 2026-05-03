<?php
namespace Company\AccessRequest\Activity;

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Company\AccessRequest\AccessRequestTable;

Loc::loadMessages(__FILE__);

class LoadRequestDataActivity extends BaseActivity
{
    protected static $requiredModules = ['company.accessrequest'];
    
    public function __construct(mixed $name)
    {
        parent::__construct($name);
        
        $this->arProperties = [
            'Title' => '',
            'RequestId' => null,
            'RequestData' => null,
            'EmployeeName' => null,
            'DepartmentId' => null,
            'RequestedAccess' => null,
            'RequestedAccessRaw' => null,
            'Status' => null,
            'StatusText' => null,
            'StatusCode' => null,
        ];
        
        $this->SetPropertiesTypes([
            'RequestData' => [
                'Type' => FieldType::STRING,
                'Multiple' => true,
            ],
            'EmployeeName' => [
                'Type' => FieldType::STRING,
            ],
            'DepartmentId' => [
                'Type' => FieldType::INT,
            ],
            'RequestedAccess' => [
                'Type' => FieldType::TEXT,
            ],
            'RequestedAccessRaw' => [
                'Type' => FieldType::STRING,
            ],
            'Status' => [
                'Type' => FieldType::SELECT,
                'Options' => $this->getStatusOptions(),
            ],
            'StatusText' => [
                'Type' => FieldType::STRING,
            ],
            'StatusCode' => [
                'Type' => FieldType::INT,
            ],
        ]);
    }
    
    /**
     * Возвращает путь к файлу активности
     */
    protected static function getFileName(): string
    {
        return __FILE__;
    }

    
    protected function internalExecute(): ErrorCollection
    {
        $errors = new ErrorCollection();
        
        $requestId = (int)$this->RequestId;
        if ($requestId <= 0) {
            $errors->add([new Error('Не передан ID заявки')]);
            return $errors;
        }
        
        $request = AccessRequestTable::getById($requestId)->fetch();
        if (!$request) {
            $errors->add([new Error("Заявка #{$requestId} не найдена")]);
            return $errors;
        }
        
        $this->RequestData = $request;
        $this->EmployeeName = $request['EMPLOYEE_NAME'];
        $this->DepartmentId = $request['REF_DEPARTMENT'];
        $this->RequestedAccessRaw = $request['REQUESTED_ACCESS'];
        $this->RequestedAccess = $this->formatRequestedAccess($request['REQUESTED_ACCESS']);
        
        $statusCode = (int)$request['STATUS'];
        $statusText = AccessRequestTable::getStatusName($statusCode);
        
        $this->StatusCode = $statusCode;
        $this->StatusText = $statusText;
        $this->Status = $statusCode;
        
        $this->WriteToTrackingService("Загружены данные заявки #{$requestId}: статус: {$statusText} ({$statusCode})");
        
        return $errors;
    }

    protected function formatRequestedAccess(string $jsonData)
    {
        $data = json_decode($jsonData, true);
        if (!$data || !is_array($data)) {
            return Loc::getMessage('LOAD_REQUEST_DATA_NO_ACCESS_DATA');
        }
        
        $result = [];
        
        // Формируем итоговый текст
        $result[] = "<b>Сотрудник:</b> " . htmlspecialcharsbx($data['FIO'] ?? '');
        $result[] = "";
        $result[] = "<b>Запрашиваемые доступы:</b>";
        
        if (!empty($data['user_value'])) {
            foreach ($data['user_value'] as $elementId => $value) {
                if ($value === 'all') {
                    $comment = "";
                    if(isset( $data['user_value'][$elementId] )) {
                        $comment = " - ". $data['user_value'][$elementId];
                    }
                    $result[] = $value.$comment;
                }
            }
        }
        
        if (!empty($data['COMMENT'])) {
            $result[] = "";
            $result[] = "<b>Комментарий:</b> " . htmlspecialcharsbx($data['COMMENT']);
        }
        
        return implode("\n", $result);
    }
    
    protected function getDepartmentName(int $departmentId)
    {
        if (!Loader::includeModule('iblock')) {
            return null;
        }
        
        $rsSection = \CIBlockSection::GetList(
            [],
            ['ID' => $departmentId, 'ACTIVE' => 'Y'],
            false,
            ['NAME']
        );
        
        if ($section = $rsSection->Fetch()) {
            return $section['NAME'];
        }
        
        return null;
    }
    
    protected function getStatusOptions()
    {
        return AccessRequestTable::getStatusList();
    }
    
    /**
     * Описывает параметры для диалога настроек
     */
    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        return [
            'RequestId' => [
                'Name' => Loc::getMessage('LOAD_REQUEST_DATA_REQUEST_ID_NAME'),
                'Description' => Loc::getMessage('LOAD_REQUEST_DATA_REQUEST_ID_DESC'),
                'Type' => FieldType::INT,
                'Required' => true,
            ],
            'RequestData' => [
                'Name' => Loc::getMessage('LOAD_REQUEST_DATA_REQUEST_DATA_NAME'),
                'Description' => Loc::getMessage('LOAD_REQUEST_DATA_REQUEST_DATA_DESC'),
                'Type' => FieldType::STRING,
                'Multiple' => true,
            ],
            'EmployeeName' => [
                'Name' => Loc::getMessage('LOAD_REQUEST_DATA_EMPLOYEE_NAME_NAME'),
                'Type' => FieldType::STRING,
            ],
            'DepartmentId' => [
                'Name' => Loc::getMessage('LOAD_REQUEST_DATA_DEPARTMENT_ID_NAME'),
                'Type' => FieldType::INT,
            ],
            'RequestedAccess' => [
                'Name' => Loc::getMessage('LOAD_REQUEST_DATA_REQUESTED_ACCESS_NAME'),
                'Description' => Loc::getMessage('LOAD_REQUEST_DATA_REQUESTED_ACCESS_DESC'),
                'Type' => FieldType::TEXT,
            ],
            'RequestedAccessRaw' => [
                'Name' => Loc::getMessage('LOAD_REQUEST_DATA_REQUESTED_ACCESS_RAW_NAME'),
                'Type' => FieldType::STRING,
            ],
            'Status' => [
                'Name' => Loc::getMessage('LOAD_REQUEST_DATA_STATUS_NAME'),
                'Description' => Loc::getMessage('LOAD_REQUEST_DATA_STATUS_DESC'),
                'Type' => FieldType::SELECT,
                'Options' => self::getStatusOptionsStatic(),
            ],
            'StatusText' => [
                'Name' => Loc::getMessage('LOAD_REQUEST_DATA_STATUS_TEXT_NAME'),
                'Type' => FieldType::STRING,
            ],
            'StatusCode' => [
                'Name' => Loc::getMessage('LOAD_REQUEST_DATA_STATUS_CODE_NAME'),
                'Type' => FieldType::INT,
            ],
        ];
    }
    
    protected static function getStatusOptionsStatic()
    {
        return AccessRequestTable::getStatusList();
    }
}