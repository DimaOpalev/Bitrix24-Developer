<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Company\AccessRequest\AccessRequestTable;
use Company\AccessRequest\AccessRequestHistoryTable;

class CBPHistoryStatusActivity extends BaseActivity
{
    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'Title' => '',
            'RequestId' => null,
            'UserDecisionMaker' => null,
            'Comment' => null,
            'Status' => null,
            'RefTask' => null,

            // выход
            'ResultText' => null,
            'ResultHTML' => null,
            'ErrorText' => null,
            'RefDepartment' => null,
            'DepartmenManager' => null,
        ];

        $this->SetPropertiesTypes([
            'RequestId' => ['Type' => FieldType::INT],
            'Status' => ['Type' => FieldType::INT],
            'RefTask' => ['Type' => FieldType::INT],
            'UserDecisionMaker' => ['Type' => FieldType::USER],
            'Comment' => ['Type' => FieldType::STRING],
            'RefDepartment' => ['Type' => FieldType::INT],
            'DepartmenManager' => ['Type' => FieldType::USER],
            'ResultText' => ['Type' => FieldType::STRING],
            'ResultHTML' => ['Type' => FieldType::STRING],
            'ErrorText' => ['Type' => FieldType::STRING],
        ]);
    }

    protected static function getFileName(): string
    {
        return __FILE__;
    }

    /**
     * UI активности
     */
    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        $statusOptions = [];

        if (Loader::includeModule('company.accessrequest'))
        {
            foreach (AccessRequestTable::getStatusList() as $code => $name)
            {
                $statusOptions[$code] = $name;
            }
        }

        return [
            'RequestId' => [
                'Name' => 'Связь с листом допуска',
                'FieldName' => 'RequestId',
                'Type' => FieldType::INT,
                'Required' => true,
                'Description' => 'Связь с листом допуска',
            ],
            'UserDecisionMaker' => [
                'Name' => 'Лицо, принимающее решение',
                'FieldName' => 'UserDecisionMaker',
                'Type' => FieldType::USER,
                'Required' => true,
                'Description' => 'Лицо (идентификатор пользователя), принимающее решение',
            ],
            'Comment' => [
                'Name' => 'Комментарий',
                'FieldName' => 'Comment',
                'Type' => FieldType::STRING,
                'Required' => true,
                'Description' => 'Комментарий',
            ],
            'Status' => [
                'Name' => 'Статус заявления',
                'FieldName' => 'Status',
                'Type' => FieldType::SELECT,
                'Options' => $statusOptions,
                'Required' => true,
                'Description' => 'Статус заявления из выбранного списка',
            ],
            'RefTask' => [
                'Name' => 'Ссылка на задачу',
                'FieldName' => 'RefTask',
                'Type' => FieldType::INT,
                'Required' => false,
                'Description' => 'Ссылка на задачу',
            ],
        ];
    }

    /**
     * Основная логика
     */
    protected function internalExecute(): ErrorCollection
    {
        $errors = parent::internalExecute();

        try {
            if (!Loader::includeModule('company.accessrequest')) {
                throw new \Exception('Модуль company.accessrequest не подключён');
            }

            // Парсим значения
            $requestId = (int)$this->parseValue($this->RequestId, FieldType::INT);
            $status = (int)$this->parseValue($this->Status, FieldType::INT);
            $comment = $this->parseValue($this->Comment, FieldType::STRING);
            $refTask = (int)$this->parseValue($this->RefTask, FieldType::INT);

            $this->log("requestId={$requestId}, status={$status}");

            if ($requestId <= 0) {
                throw new \Exception('Некорректный ID заявки');
            }

            $request = AccessRequestTable::getById($requestId)->fetch();

            if (!$request) {
                throw new \Exception("Заявка #{$requestId} не найдена");
            }

            $oldStatus = (int)$request['STATUS'];

            $rawValue = $this->UserDecisionMaker; 
            $arUserIds = \CBPHelper::ExtractUsers($rawValue, $this->GetDocumentId());
            $userId = (!empty($arUserIds)) ? (int)$arUserIds[0] : 0;


            $result = AccessRequestHistoryTable::Add([
                'USER_DECISION_MAKER' => $userId,
                'REF_REQUEST' => $requestId,
                'STATUS' => $status,
                'CREATED_DATE' => new DateTime(),
                'COMMENT' => $comment,
            ]);

            if (!$result->isSuccess()) {
                throw new \Exception(
                    implode(', ', $result->getErrorMessages())
                );
            }

            $departmentId = (int)$request['REF_DEPARTMENT'];
            $this->preparedProperties['RefDepartment'] = $departmentId;

            $managerId = 0;
            if (\Bitrix\Main\Loader::includeModule('intranet')) {
                $arManagers = \CIntranetUtils::GetDepartmentManager([$departmentId], false, true);
                foreach ($arManagers as $key => $value) {
                    $managerId = $value["ID"];
                    break;
                }
            }

            if( $managerId > 0 ) {
                $this->preparedProperties['DepartmenManager'] = 'user_' . $managerId;
            } else {
                throw new \Exception("Руководитель у заданного подразделения не найден.");
            }

            $result = AccessRequestTable::update($requestId, [
                'STATUS' => $status,
                'UPDATED_DATE' => new DateTime(),
                'REF_TASK' => $refTask,
            ]);

            if (!$result->isSuccess()) {
                throw new \Exception(
                    implode(', ', $result->getErrorMessages())
                );
            }

            $resultText = AccessRequestTable::generateText($requestId);
            // $resultHTML = "";
            $resultHTML = AccessRequestTable::generateHTML($requestId);
            $this->preparedProperties['ResultText'] = $resultText;
            $this->preparedProperties['ResultHTML'] = $resultHTML;

            $path = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/bp_list_dopusk.log';
            file_put_contents($path, print_r([
                "requestId" => $requestId,
                "status" => $status,
                "comment" => $comment,
                "UserDecisionMaker" => $userId,
                "departmentId" => $departmentId,
                "managerId" => $managerId,
                "ResultText" => $resultText,
                "ResultHTML" => $resultHTML,
            ], true), FILE_APPEND);

        }
        catch (\Throwable $e) {

            $this->preparedProperties['ErrorText'] = $e->getMessage();

            $errors->setError(new Error($e->getMessage()));

            $this->writeToTrackingService(
                'Ошибка: ' . $e->getMessage(),
                \CBPTrackingType::Error,
                0
            );


            $path = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/bp_list_dopusk_error.log';
            file_put_contents($path, $e->getMessage(), FILE_APPEND);
        }

        return $errors;
    }

}