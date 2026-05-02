<?php
namespace Otus\Events;

require_once "IBLOCK_DEAL_ID.php";

class CrmDealEventHandler
{
    /**
     * Обработчик после обновления сделки
     */
    public static function OnAfterCrmDealUpdate($arFields = [])
    {
        if (\Otus\Events\IblockEventHandler::$isInternalUpdate) {
            return;
        }

        $iBlockElementId = self::findIblockElementByDealId($arFields["ID"]);

        $updateFields = self::prepareIblockFields($arFields);
        
        self::log("updateFields = ", $updateFields);
        
        self::updateIblockElement($iBlockElementId, $updateFields);

        self::log('OnAfterCrmDealUpdate', 
            $arFields
        );
    }

    /**
     * Подготовка полей инфоблока из данных сделки
     */
    private static function prepareIblockFields($dealFields)
    {
        $updateFields = [];
        
        // 1. Название заявки (убираем префикс "Сделка по " если он был)
        if (!empty($dealFields['TITLE'])) {
            $title = $dealFields['TITLE'];
            // Убираем префикс, если он есть
            $title = str_replace('Сделка: ', '', $title);
            $updateFields['NAME'] = $title;
        }
        
        // 2. Сумма (UF_SUM)
        if (isset($dealFields['OPPORTUNITY'])) {
            $updateFields['PROPERTY_VALUES']['UF_SUM'] = (float)$dealFields['OPPORTUNITY'];
        }
        
        // 3. Ответственный (UF_RESPONSIBLE)
        if (isset($dealFields['ASSIGNED_BY_ID'])) {
            $updateFields['PROPERTY_VALUES']['UF_RESPONSIBLE'] = (int)$dealFields['ASSIGNED_BY_ID'];
        }
        
        // 4. Компания (UF_CUSTOMER) - формат CO_ID
        if (isset($dealFields['COMPANY_ID']) && $dealFields['COMPANY_ID'] > 0) {
            $updateFields['PROPERTY_VALUES']['UF_CUSTOMER'] = "CO_" . $dealFields['COMPANY_ID'];
        }
        
        return $updateFields;
    }

    /**
     * Поиск элемента инфоблока по ID сделки в свойстве UF_DEAL
     */
    private static function findIblockElementByDealId($dealId)
    {
        $elementId = 0;
        
        $res = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => IBLOCK_DEAL_ID,
                'PROPERTY_UF_DEAL' => $dealId,
                'ACTIVE' => 'Y',
            ],
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME']
        );
        
        if ($item = $res->Fetch()) {
            $elementId = $item['ID'];
        }
        
        return $elementId;
    }

    /**
     * Обновление элемента инфоблока
     */
    public static $isInternalUpdate = false;

    private static function updateIblockElement($elementId, $fields)
    {
        self::$isInternalUpdate = true;
        $el = new \CIBlockElement();
        
        // Подготавливаем основные поля
        $arFields = [];
        if (isset($fields['NAME'])) {
            $arFields['NAME'] = $fields['NAME'];
        }
        
        // Обновляем основные поля
        if (!empty($arFields)) {
            $result = $el->Update($elementId, $arFields);
            if (!$result) {
                throw new \Exception("Ошибка обновления основных полей: " . $el->LAST_ERROR);
            }
        }
        
        // Обновляем свойства
        if (isset($fields['PROPERTY_VALUES']) && !empty($fields['PROPERTY_VALUES'])) {
            \CIBlockElement::SetPropertyValuesEx(
                $elementId,
                IBLOCK_DEAL_ID,
                $fields['PROPERTY_VALUES']
            );
        }
        self::$isInternalUpdate = false;
        return true;
    }

    /**
     * Логирование
     */
    private static function log(string $event, Array $data): void
    {
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/crm_deal_events.log';
        $logEntry = sprintf(
            "[%s] %s: %s\n",
            date('Y-m-d H:i:s'),
            $event,
            print_r($data, true)
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

}