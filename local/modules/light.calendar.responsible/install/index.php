<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\EventManager;
use Light\Calendar\Responsible\EventHandlers;

Loc::loadMessages(__FILE__);

class light_calendar_responsible extends CModule
{
    public $MODULE_ID = 'light.calendar.responsible';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        if (is_array($arModuleVersion) && $arModuleVersion['VERSION']) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('VENDOR_CALENDAR_RESPONSIBLE_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('VENDOR_CALENDAR_RESPONSIBLE_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('VENDOR_CALENDAR_RESPONSIBLE_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('VENDOR_CALENDAR_RESPONSIBLE_PARTNER_URI');
    }

    public function DoInstall()
    {
        // Регистрируем модуль в системе
        RegisterModule($this->MODULE_ID);
        // Регистрируем обработчики событий
        $this->InstallEvents();
        // Устанавливаем настройки по умолчанию
        $this->InstallOptions();
    }

    public function DoUninstall()
    {
        // Удаляем обработчики событий
        $this->UnInstallEvents();
        // Удаляем настройки модуля
        $this->UnInstallOptions();
        // Удаляем модуль из системы
        UnRegisterModule($this->MODULE_ID);
    }

    public function InstallEvents()
    {
        $eventManager = EventManager::getInstance();
        // Регистрируем обработчик на событие календаря
        $eventManager->registerEventHandler(
            'calendar',                 // Имя модуля
            'OnAfterCalendarEventEdit', // Имя события
            $this->MODULE_ID,           // ID нашего модуля
            EventHandlers::class,       // Класс-обработчик
            'onAfterCalendarEventEditHandler' // Метод для вызова
        );
    }

    public function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            'calendar',
            'OnAfterCalendarEventEdit',
            $this->MODULE_ID,
            EventHandlers::class,
            'onAfterCalendarEventEditHandler'
        );
    }

    public function InstallOptions()
    {
        // Устанавливаем значение по умолчанию для ответственного
        \Bitrix\Main\Config\Option::set($this->MODULE_ID, 'responsible_user_id', 1);
        \Bitrix\Main\Config\Option::set($this->MODULE_ID, 'responsible_calendar_id', 0);
    }

    public function UnInstallOptions()
    {
        // Удаляем все настройки модуля
        \Bitrix\Main\Config\Option::delete($this->MODULE_ID);
    }
}