<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Crm\EntityRequisite;
use CCrmOwnerType;

class CBPGetDadataOrgByInnActivity extends BaseActivity
{
    /**
     * Конструктор
     */
    public function __construct($name)
    {
        parent::__construct($name);

        // Свойства действия (параметры)
        $this->arProperties = [
            'Title' => '',
            'Inn' => '',
            'ApiKey' => '',          // API ключ из поля активити
            'SecretKey' => '',        // Секретный ключ из поля активити

            // возвращаемое значение
            'Text' => null,

            // Выходные параметры
            'CompanyId' => null,
            'OrgName' => null,
            'Address' => null,
            'Kpp' => null,
            'Ogrn' => null,
            'Director' => null,
            'Okved' => null,
            'Status' => null,
            'ErrorText' => null,
        ];

        // Типы свойств
        $this->SetPropertiesTypes([
            'CompanyId' => ['Type' => FieldType::INT],
            'OrgName' => ['Type' => FieldType::STRING],
            'Address' => ['Type' => FieldType::STRING],
            'Kpp' => ['Type' => FieldType::STRING],
            'Ogrn' => ['Type' => FieldType::STRING],
            'Director' => ['Type' => FieldType::STRING],
            'Okved' => ['Type' => FieldType::STRING],
            'Status' => ['Type' => FieldType::STRING],
            'ErrorText' => ['Type' => FieldType::STRING],
            'Text' => ['Type' => FieldType::STRING],
        ]);
    }

    /**
     * Возвращает путь к файлу активности
     */
    protected static function getFileName(): string
    {
        return __FILE__;
    }

    /**
     * Описывает параметры для диалога настроек
     */
    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        return [
            'Inn' => [
                'Name' => Loc::getMessage('GETDADATA_ACTIVITY_INN'),
                'FieldName' => 'Inn',
                'Type' => FieldType::STRING,
                'Required' => true,
                'Description' => Loc::getMessage('GETDADATA_ACTIVITY_INN_HINT'),
            ],
            'ApiKey' => [
                'Name' => Loc::getMessage('GETDADATA_ACTIVITY_API_KEY'),
                'FieldName' => 'ApiKey',
                'Type' => FieldType::STRING,
                'Required' => true,
                'Description' => Loc::getMessage('GETDADATA_ACTIVITY_API_KEY_HINT'),
            ],
            'SecretKey' => [
                'Name' => Loc::getMessage('GETDADATA_ACTIVITY_SECRET_KEY'),
                'FieldName' => 'SecretKey',
                'Type' => FieldType::STRING,
                'Required' => false,
                'Description' => Loc::getMessage('GETDADATA_ACTIVITY_SECRET_KEY_HINT'),
            ],
        ];
    }

    /**
     * Основная логика выполнения
     */
    protected function internalExecute(): ErrorCollection
    {
        $errors = parent::internalExecute();
        try {
            $rootActivity = $this->GetRootActivity(); // Получаем объект бизнес-процесса
            $documentId = $rootActivity->GetDocumentId();
            $documentService = $this->workflow->GetService("DocumentService"); // Доступ к сервису управления документами
            $arDocumentFields = $documentService->GetDocument($documentId); // Массив со значениями всех полей документа


            $this->log("documentType = ".print_r($documentId, true).", arDocumentFields = ".print_r($arDocumentFields, true));

            // Получаем значения параметров

            $inn = $this->Inn;
            $apiKey = $this->ApiKey;
            $secretKey = $this->SecretKey;

            // Валидация
            if (empty($inn)) {
                throw new \Exception(Loc::getMessage('GETDADATA_ACTIVITY_ERROR_EMPTY_INN'));
            }

            // Очищаем ИНН
            $inn = preg_replace('/[^0-9]/', '', $inn);

            // Проверяем длину ИНН
            if (strlen($inn) !== 10 && strlen($inn) !== 12) {
                throw new \Exception(Loc::getMessage('GETDADATA_ACTIVITY_ERROR_INN_FORMAT'));
            }

            $result = $this->requestDadata($inn, $apiKey, $secretKey);

            // Генерируем результат
            $resultText = print_r($result, true);

            // Сохраняем возвращаемое значение
            $this->preparedProperties['Text'] = $resultText;

            // Записываем в журнал БП (для отладки)
            $this->log($resultText);

            // 4. Обрабатываем результат
            if ($result && isset($result['suggestions'][0])) {
                $data = $result['suggestions'][0]['data'];
                $value = $result['suggestions'][0]['value'];

                $companyId = $this->findCompanyIdByInn($inn);
                if(!$companyId) {
                    $companyId = $this->createCompanyWithInn($value, $inn);
                }

                $this->log("companyId = {$companyId}");
                // Сохраняем возвращаемые значения
                $this->preparedProperties['CompanyId'] = $companyId;

                $this->preparedProperties['OrgName'] = $value;
                $this->preparedProperties['Address'] = $data['address']['value'] ?? '';
                $this->preparedProperties['Kpp'] = $data['kpp'] ?? '';
                $this->preparedProperties['Ogrn'] = $data['ogrn'] ?? '';
                $this->preparedProperties['Inn'] = $data['inn'] ?? $inn;
                $this->preparedProperties['Director'] = $data['management']['name'] ?? '';
                $this->preparedProperties['Okved'] = $data['okved'] ?? '';
                $this->preparedProperties['Status'] = 'success';

                $documentService->UpdateDocument($documentId, [
                    'PROPERTY_UF_CUSTOMER' => "CO_{$companyId}",
                ]);
                
            } else {
                throw new \Exception(Loc::getMessage('GETDADATA_ACTIVITY_ERROR_NOT_FOUND', ['#INN#' => $inn]));
            }

        } catch (\Exception $e) {
            // Сохраняем ошибку
            $this->preparedProperties['Status'] = 'error';
            $this->preparedProperties['ErrorText'] = $e->getMessage();
            
            // Добавляем ошибку в коллекцию
            $errors->setError(new Error($e->getMessage()));
            
            // Логируем ошибку
            $this->writeToTrackingService(
                Loc::getMessage('GETDADATA_ACTIVITY_ERROR', ['#ERROR#' => $e->getMessage()]),
                CBPTrackingType::Error,
                0
            );
        }
        

        return $errors;
    }

    /**
     * Поиск организации в CRM по ИНН
     */
    private function findCompanyIdByInn(string $inn): ?int
    {
        // Очищаем ИНН от лишних пробелов на всякий случай
        $inn = trim($inn);
        
        if (empty($inn) || !Loader::includeModule('crm')) {
            return null;
        }

        $requisite = new EntityRequisite();
        
        $res = $requisite->getList([
            'filter' => [
                '=RQ_INN' => $inn,
                '=ENTITY_TYPE_ID' => CCrmOwnerType::Company // ID = 4
            ],
            'select' => ['ENTITY_ID'],
            'limit' => 1 // Нам достаточно первой найденной компании
        ]);

        if ($item = $res->fetch()) {
            return (int)$item['ENTITY_ID'];
        }

        return null;
    }

    /**
     * Создание компании
     * 
     */
    private function createCompanyWithInn(string $title, string $inn)
    {
        if (!Loader::includeModule('crm')) return false;

        // 1. Создаем саму компанию
        $company = new \CCrmCompany();
        $companyFields = [
            'TITLE' => $title,
            'OPENED' => 'Y', // Доступна всем
            'CURRENCY_ID' => 'RUB',
        ];

        $companyId = $company->Add($companyFields);

        if (!$companyId) {
            return false; // Ошибка создания компании
        }

        // 2. Добавляем реквизиты (ИНН)
        $requisite = new EntityRequisite();
        $requisiteFields = [
            'ENTITY_TYPE_ID' => \CCrmOwnerType::Company, // ID = 4
            'ENTITY_ID'      => $companyId,              // Привязка к нашей компании
            'PRESET_ID'      => 1,                       // ID шаблона (обычно 1 — Организация, 2 — ИП)
            'NAME'           => 'Реквизиты ' . $title,
            'SORT'           => 100,
            'ACTIVE'         => 'Y',
            'RQ_INN'         => trim($inn),              // ИНН
        ];

        $requisiteResult = $requisite->add($requisiteFields);

        if ($requisiteResult->isSuccess()) {
            return (int)$companyId;
        }

        return (int)$companyId; // Возвращаем ID, даже если реквизиты не создались
    }

    /**
     * Запрос к DaData API
     */
    private function requestDadata(string $inn, string $apiKey, string $secretKey = ''): array
    {
        $url = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party';
        $data = json_encode(['query' => $inn]);
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Token ' . $apiKey,
        ];
        
        if (!empty($secretKey)) {
            $headers[] = 'X-Secret: ' . $secretKey;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('cURL Error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new \Exception('HTTP Error ' . $httpCode . ': ' . $response);
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }
        
        return $result;
    }

}