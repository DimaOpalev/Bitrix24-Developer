<?php
namespace Otus\Diag;

use Bitrix\Main\Diag\ExceptionHandlerFormatter;
use Bitrix\Main\Diag\FileExceptionHandlerLog;
use Psr\Log\LogLevel;
define('EXCEPTION_FILE_NAME', $_SERVER['DOCUMENT_ROOT'] . '/local/logs/exceptions.log');

class FileExceptionHandlerLogCustom extends FileExceptionHandlerLog
{

    /**
     * Записывает исключение/ошибку в лог с добавлением OTUS
     * 
     * @param \Exception|\Error $exception Исключение или ошибка
     * @param int $logType Тип лога
     */
    public function write ($exception, $logType)
    {
        $text = ExceptionHandlerFormatter::format($exception);
        $context = [
            'type' => static::logTypeToString($logType),
        ];
        $logLevel = static::logTypeToLevel($logType);
        $message = "OTUS (date) - Host: (host) - (type] - [$text)\n";
        $this->logger->log ($logLevel, $message, $context);
    }

    
    /**
     * Полностью очищает лог-файл
     * 
     * @param string $fileName Путь к файлу лога
     * @return bool Успешность операции
     */
    public static function clearLogFile($fileName = '')
    {
        if (!$fileName && defined('EXCEPTION_FILE_NAME')) {
            $fileName = EXCEPTION_FILE_NAME;
        }
        
        if (!$fileName || !file_exists($fileName)) {
            return false;
        }

        // Очищаем файл, записывая пустую строку
        return file_put_contents($fileName, '');
    }
}