<?php
namespace Otus\Events;

class IblockEventHandler 
{
    const IBLOCK_DEAL_ID = 21;

    /**
     * Обработчик перед добавлением элемента
     */
    public static function onElementBeforeAdd(&$arFields)
    {
        // Проверяем, что это нужный инфоблок
        if ($arFields['IBLOCK_ID'] != self::IBLOCK_DEAL_ID) {
            return;
        }
        
        // Логируем
        self::log('onElementBeforeAdd', [
            'IBLOCK_ID' => $arFields['IBLOCK_ID'],
            'NAME' => $arFields['NAME'],
        ]);
        
        // Можно изменять поля
        // $arFields['PROPERTY_VALUES']['UF_SOURCE'] = 'auto';
    }

    /**
     * Обработчик после обновления элемента
     */
    public static function onElementAfterUpdate(&$arFields)
    {
        if ($arFields['IBLOCK_ID'] != self::IBLOCK_DEAL_ID) {
            return;
        }
        
        self::log('onElementAfterUpdate', [
            'ID' => $arFields['ID'],
            'NAME' => $arFields['NAME'],
        ]);
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
     * Логирование
     */
    private static function log($event, $data)
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