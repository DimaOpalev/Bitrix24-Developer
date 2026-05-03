<?php
namespace Company\AccessRequest\Activity;

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Company\AccessRequest\AccessRequestTable;
use Company\AccessRequest\AccessRequestHistoryTable;

Loc::loadMessages(__FILE__);

class WriteHistoryActivity extends BaseActivity
{
    protected static $requiredModules = ['company.accessrequest'];
    
    public function __construct($name)
    {
        parent::__construct($name);
        
        $this->arProperties = [
            'Title' => '',
            'RequestId' => null,
            'DecisionMakerId' => null,
            'DecisionStatus' => null,
            'Comment' => null,
            'HistoryId' => null,
        ];
        
        $this->SetPropertiesTypes([
            'HistoryId' => [
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
        
        $decisionMakerId = (int)$this->DecisionMakerId;
        if ($decisionMakerId <= 0) {
            $decisionMakerId = $GLOBALS['USER']->GetID();
        }
        
        $status = (int)$this->DecisionStatus;
        
        $result = AccessRequestHistoryTable::add([
            'REF_REQUEST' => $requestId,
            'USER_DECISION_MAKER' => $decisionMakerId,
            'STATUS' => $status,
            'COMMENT' => trim((string)$this->Comment),
        ]);
        
        if (!$result->isSuccess()) {
            $errors->add([new Error('Ошибка записи в историю: ' . implode(', ', $result->getErrorMessages()))]);
            return $errors;
        }
        
        $this->HistoryId = $result->getId();
        
        if ($status === AccessRequestTable::STATUS_APPROVED || 
            $status === AccessRequestTable::STATUS_REJECTED ||
            $status === AccessRequestTable::STATUS_CANCELLED) {
            AccessRequestTable::update($requestId, [
                'STATUS' => $status,
                'UPDATED_DATE' => new DateTime(),
            ]);
        }
        
        $this->WriteToTrackingService("Запись в историю добавлена для заявки #{$requestId}. Статус: {$status}");
        
        return $errors;
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
                'Name' => Loc::getMessage('WRITE_HISTORY_REQUEST_ID_NAME'),
                'Description' => Loc::getMessage('WRITE_HISTORY_REQUEST_ID_DESC'),
                'Type' => FieldType::INT,
                'Required' => true,
            ],
            'DecisionMakerId' => [
                'Name' => Loc::getMessage('WRITE_HISTORY_DECISION_MAKER_ID_NAME'),
                'Description' => Loc::getMessage('WRITE_HISTORY_DECISION_MAKER_ID_DESC'),
                'Type' => FieldType::USER,
                'Required' => true,
            ],
            'DecisionStatus' => [
                'Name' => Loc::getMessage('WRITE_HISTORY_DECISION_STATUS_NAME'),
                'Description' => Loc::getMessage('WRITE_HISTORY_DECISION_STATUS_DESC'),
                'Type' => FieldType::SELECT,
                'Options' => $statusOptions,
                'Required' => true,
            ],
            'Comment' => [
                'Name' => Loc::getMessage('WRITE_HISTORY_COMMENT_NAME'),
                'Type' => FieldType::TEXT,
                'Required' => false,
            ],
            'HistoryId' => [
                'Name' => Loc::getMessage('WRITE_HISTORY_HISTORY_ID_NAME'),
                'Type' => FieldType::INT,
            ],
        ];
    }
}