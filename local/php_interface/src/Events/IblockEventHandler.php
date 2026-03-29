<?php
namespace Otus\Events;

use Bitrix\Main\Loader;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\DealSettings;

class IblockEventHandler
{
    const IBLOCK_DEAL_ID = 21;

    /**
     * Обработчик перед добавления элемента в инфоблок
     */
    public static function OnAfterIBlockElementAdd(&$arFields)
    {
        // Проверяем, что это наш инфоблок
        if ($arFields['IBLOCK_ID'] != self::IBLOCK_DEAL_ID) {
            return;
        }

        self::log('onAfterIBlockElementAdd - Перед созданием записи в инфоблок', [
            $arFields
        ]);

        try {
            // Проверяем, что модуль CRM установлен
            if (!Loader::includeModule('crm')) {
                throw new \Exception('Модуль CRM не установлен');
            }

            // Получаем все свойства элемента
            $properties = self::getElementProperties($arFields['ID']);
            $title = "Сделка: ". $arFields["NAME"];
            $sum = self::getSum($properties);
            $comment = implode(PHP_EOL, $properties["UF_TYPE_WORK"]);
            $responsibleId = $properties["UF_RESPONSIBLE"][0];
            $companyId = self::getCompanyId($properties);
            $elementId = $arFields['ID'];

            $dealId = self::createDeal($title, $sum, $companyId, $comment, $responsibleId, $elementId);

            if ($dealId) {
                //Обновляем поле UF_DEAL в инфоблоке
                self::updateDealField($arFields['ID'], $dealId);
                
                self::log('OnAfterIBlockElementAdd - Сделка успешно создана', [
                    'ELEMENT_ID' => $arFields['ID'],
                    'DEAL_ID' => $dealId,
                ]);
            }

        } catch (\Exception $e) {
            self::log('onAfterIBlockElementAdd - ОШИБКА', [
                'ERROR' => $e->getMessage(),
                'ELEMENT_ID' => $arFields['ID'] ?? null,
            ]);
        }
    }

    /**
     * Обработчик перед изменением элемента в инфоблок
     */
    public static function OnAfterIBlockElementUpdate(&$arFields)
    {
        // Проверяем, что это наш инфоблок
        if ($arFields['IBLOCK_ID'] != self::IBLOCK_DEAL_ID) {
            return;
        }


        // Получаем все свойства элемента
        $properties = self::getElementProperties($arFields['ID']);
        $title = "Сделка по ". $arFields["NAME"];
        $sum = self::getSum($properties);
        $comment = implode(PHP_EOL, $properties["UF_TYPE_WORK"]);
        $responsibleId = $properties["UF_RESPONSIBLE"][0];
        $companyId = self::getCompanyId($properties);
        $elementId = $arFields['ID'];
        $dealId = $properties["UF_DEAL"][0];
        
        //Проверка, есть ли сделка?
        if(empty($dealId)) {
            $dealId = self::createDeal($title, $sum, $companyId, $comment, $responsibleId, $elementId);
            if($dealId) {
                self::updateDealField($elementId, $dealId);
            }
        } else {
            self::updateDeal($dealId, $title, $sum, $companyId, $comment, $responsibleId);
        }

        self::log('OnAfterIBlockElementUpdate - Перед изменением записи в инфоблок', [
            $arFields,
            "properties" => $properties,
        ]);

        try {
            // Проверяем, что модуль CRM установлен
            if (!Loader::includeModule('crm')) {
                throw new \Exception('Модуль CRM не установлен');
            }

            // Получаем все свойства элемента

        } catch (\Exception $e) {
            self::log('OnAfterIBlockElementUpdate - ОШИБКА', [
                'ERROR' => $e->getMessage(),
                'ELEMENT_ID' => $arFields['ID'] ?? null,
            ]);
        }
    }

    /**
     * Обработчик перед удалением элемента
     */
    public static function onElementBeforeDelete($id)
    {
        $element = \CIBlockElement::GetByID($id)->Fetch();
        
        if ($element && $element['IBLOCK_ID'] == self::IBLOCK_DEAL_ID) {
            self::log('onElementBeforeDelete', [
                'ID' => $id,
                'NAME' => $element['NAME'],
            ]);
        }
    }

    /**
     * Получение свойств элемента
     */
    private static function getElementProperties($elementId)
    {
        $properties = [];
        $dbProps = \CIBlockElement::GetProperty(
            self::IBLOCK_DEAL_ID,
            $elementId,
            [],
            []
        );
        
        while ($prop = $dbProps->Fetch()) {
            $code = $prop['CODE'];
            if (!isset($properties[$code])) {
                $properties[$code] = [];
            }
            $properties[$code][] = $prop['VALUE'];
        }
        
        return $properties;
    }

    /**
     * Создание сделки в CRM
     */
    private static function createDeal($title, $sum, $companyId, $comment="", $responsibleId, $elementId)
    {
        $deal = new \CCrmDeal(false);
        
        // Базовые поля сделки
        $dealFields = [
            'TITLE' => $title,
            'OPPORTUNITY' => $sum,
            'CURRENCY_ID' => 'RUB',
            'ASSIGNED_BY_ID' => $responsibleId,
            'BEGINDATE' => date('Y-m-d'),
            'CLOSEDATE' => date('Y-m-d', strtotime('+1 month')),
            'OPENED' => 'Y',
            'STAGE_ID' => 'NEW',
            'CATEGORY_ID' => 0,
            'SOURCE_ID' => 'WEB',
            'SOURCE_DESCRIPTION' => 'Из инфоблока заявок',
        ];
        
        // Добавляем компанию, если указана
        if ($companyId > 0) {
            $dealFields['COMPANY_ID'] = $companyId;
        }
        
        // Добавляем комментарий, если указан
        $dealFields['COMMENTS'] = "Заявка №{$elementId}\n" . $comment;
        
        self::log('createDeal - Параметры сделки', $dealFields);
        
        $dealId = $deal->Add($dealFields);
        
        if (!$dealId) {
            $error = $deal->LAST_ERROR ?: 'Неизвестная ошибка';
            throw new \Exception("Ошибка при создании сделки: {$error}");
        }
        
        return $dealId;
    }

    /**
     * Обновление поля UF_DEAL в инфоблоке
     */
    private static function updateDealField($elementId, $dealId)
    {
        \CIBlockElement::SetPropertyValuesEx(
            $elementId,
            self::IBLOCK_DEAL_ID,
            ['UF_DEAL' => $dealId]
        );
        
        self::log('updateDealField - Поле UF_DEAL обновлено', [
            'ELEMENT_ID' => $elementId,
            'DEAL_ID' => $dealId,
        ]);
    }

    /**
     * Обновление существующей сделки без генерации событий
     */
    private static function updateDeal($dealId, $title, $sum, $companyId, $comment, $responsibleId)
    {
        $dealFields = [
            'TITLE' => $title,
            'OPPORTUNITY' => $sum,
            'ASSIGNED_BY_ID' => $responsibleId,
            'COMMENTS' => $comment,
        ];
        
        if ($companyId > 0) {
            $dealFields['COMPANY_ID'] = $companyId;
        }
        
        //отключаем системные события
        $options = [
            'ENABLE_SYSTEM_EVENTS' => false,  // События OnBefore/OnAfterCrmDealUpdate НЕ сработают
            'REGISTER_SONET_EVENT' => false,   // Дополнительно отключаем события SO/NO
            'DISABLE_USER_FIELD_CHECK' => true, // Отключаем проверку пользовательских полей
        ];
        
        $deal = new \CCrmDeal(false);  // false — отключаем проверку прав
        $result = $deal->Update($dealId, $dealFields, true, true, $options);
        
        if (!$result) {
            $error = $deal->LAST_ERROR ?: 'Неизвестная ошибка';
            throw new \Exception("Ошибка при обновлении сделки: {$error}");
        }
        
        self::log('updateDeal - Сделка обновлена (события отключены)', [
            'DEAL_ID' => $dealId,
            'fields' => $dealFields,
        ]);
        
        return $result;
    }

    /**
     * Получение суммы из свойства UF_SUM
     */
    private static function getSum($properties)
    {
        if (isset($properties['UF_SUM']) && !empty($properties['UF_SUM'])) {
            $value = $properties['UF_SUM'][0];
            // Очищаем от лишних символов и преобразуем в число
            $value = preg_replace('/[^0-9.,]/', '', $value);
            $value = str_replace(',', '.', $value);
            return (float)$value;
        }
        return 0;
    }

    /**
     * Получение ID компании из свойства UF_CUSTOMER
     * Значение может быть в формате CO_11 или просто 11
     */
    private static function getCompanyId($properties)
    {
        if (isset($properties['UF_CUSTOMER']) && !empty($properties['UF_CUSTOMER'])) {
            $value = $properties['UF_CUSTOMER'][0];
            // Если приходит в формате CO_11
            if (is_string($value) && strpos($value, 'CO_') === 0) {
                return (int)str_replace('CO_', '', $value);
            }
            return (int)$value;
        }
        return 0;
    }

    /**
     * Логирование
     */
    private static function log(string $event, array $data): void
    {
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/iblock_events.log';
        $logEntry = sprintf(
            "[%s] %s: %s\n",
            date('Y-m-d H:i:s'),
            $event,
            print_r($data, true)
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

}