<?php
namespace Company\AccessRequest\Activity;

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Company\AccessRequest\AccessRequestTable;

Loc::loadMessages(__FILE__);

class UpdateStatusActivity extends BaseActivity
{
    protected static $requiredModules = ['company.accessrequest'];
    
    public function __construct(mixed $name)
    {
        parent::__construct($name);
        
        $this->arProperties = [
            'Title' => '',
            'RequestId' => null,
            'NewStatus' => null,
            'OldStatus' => null,
        ];
        
        $this->SetPropertiesTypes([
            'OldStatus' => [
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
        
        $oldStatus = (int)$request['STATUS'];
        $newStatus = (int)$this->NewStatus;
        
        if ($oldStatus === $newStatus) {
            $this->OldStatus = $oldStatus;
            $this->WriteToTrackingService("Статус заявки #{$requestId} уже имеет значение: " . $this->getStatusName($newStatus));
            return $errors;
        }
        
        $result = AccessRequestTable::update($requestId, [
            'STATUS' => $newStatus,
            'UPDATED_DATE' => new DateTime(),
        ]);
        
        if (!$result->isSuccess()) {
            $errors->add([new Error("Ошибка обновления статуса заявки #{$requestId}: " . implode(', ', $result->getErrorMessages()))]);
            return $errors;
        }
        
        $this->OldStatus = $oldStatus;
        $this->WriteToTrackingService(
            "Статус заявки #{$requestId} изменён: " .
            $this->getStatusName($oldStatus) . " → " . $this->getStatusName($newStatus)
        );
        
        return $errors;
    }
    
    protected function getStatusName($status)
    {
        $list = AccessRequestTable::getStatusList();
        return $list[$status] ?? Loc::getMessage('UPDATE_STATUS_UNKNOWN');
    }
    
    /**
     * Описывает параметры для диалога настроек
     */
    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        $statusOptions = [];
        $statusList = AccessRequestTable::getStatusList();
        foreach ($statusList as $code => $name) {
            $statusOptions[$code] = $name;
        }
        
        return [
            'RequestId' => [
                'Name' => Loc::getMessage('UPDATE_STATUS_REQUEST_ID_NAME'),
                'Description' => Loc::getMessage('UPDATE_STATUS_REQUEST_ID_DESC'),
                'Type' => FieldType::INT,
                'Required' => true,
            ],
            'NewStatus' => [
                'Name' => Loc::getMessage('UPDATE_STATUS_NEW_STATUS_NAME'),
                'Description' => Loc::getMessage('UPDATE_STATUS_NEW_STATUS_DESC'),
                'Type' => FieldType::SELECT,
                'Options' => $statusOptions,
                'Required' => true,
            ],
            'OldStatus' => [
                'Name' => Loc::getMessage('UPDATE_STATUS_OLD_STATUS_NAME'),
                'Type' => FieldType::INT,
            ],
        ];
    }
}