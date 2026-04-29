<?php

namespace Local\App\Debug;

use Bitrix\Main\Diag\Debug;
define('DEBUG_FILE_NAME', $_SERVER["DOCUMENT_ROOT"].'/local/logs/log_custom.log');

class Log extends Debug
{
    
    /**
     * Полностью очищает лог-файл
     * 
     * @param string $fileName Путь к файлу лога
     * @return bool Успешность операции
     */
    public static function clearLogFile($fileName = '')
    {
        if (!$fileName && defined('DEBUG_FILE_NAME')) {
            $fileName = DEBUG_FILE_NAME;
        }
        
        if (!$fileName || !file_exists($fileName)) {
            return false;
        }
        
        // Очищаем файл, записывая пустую строку
        return file_put_contents($fileName, '') !== false;
    }

    /**
     * Альтернативная версия с добавлением OTUS в каждую строку лога
     * 
     * @param string $filePath Полный путь к файлу
     * @return bool
     */
    public static function writeToLog($filePath = '')
    {
        if (empty($filePath)) {
            // Используем стандартный путь для логов Битрикс
            $filePath = DEBUG_FILE_NAME;
        }
        
        $log = "\n------------------------\n";
        $log .= date("d.m.Y G:i:s") . "\n";
        $log .= "------------------------\n";
        
        return file_put_contents($filePath, $log, FILE_APPEND);
    }
}