<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Localization\Loc;

class CBPHelloWorldActivity extends BaseActivity
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
            'Target' => 'мир',        // обращение (по умолчанию)
            'Message' => '',           // текст сообщения

            // возвращаемое значение
            'Text' => null,
        ];

        // Типы свойств
        $this->SetPropertiesTypes([
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
     * ✅ ПРАВИЛЬНАЯ СИГНАТУРА С PropertiesDialog
     */
    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        return [
            'Target' => [
                'Name' => Loc::getMessage('HELLOWORLD_ACTIVITY_TARGET'),
                'FieldName' => 'Target',
                'Type' => FieldType::STRING,
                'Required' => true,
                'Default' => 'мир',
            ],
            'Message' => [
                'Name' => Loc::getMessage('HELLOWORLD_ACTIVITY_MESSAGE'),
                'FieldName' => 'Message',
                'Type' => FieldType::TEXT,
                'Required' => true,
            ],
        ];
    }

    /**
     * Основная логика выполнения
     */
    protected function internalExecute(): ErrorCollection
    {
        $errors = parent::internalExecute();

        // Получаем значения параметров
        $target = $this->Target ?? 'мир';
        $message = $this->Message ?? '';

        // Генерируем результат
        $resultText = "Привет, {$target}! {$message}";

        // Сохраняем возвращаемое значение
        $this->preparedProperties['Text'] = $resultText;

        // Записываем в журнал БП (для отладки)
        $this->log($resultText);

        return $errors;
    }

}