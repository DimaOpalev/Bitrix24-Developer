<?php

use Bitrix\Main\Loader;

// Подключаем основной класс с обработчиками
Loader::registerAutoLoadClasses('light.calendar.responsible', [
    'Light\Calendar\Responsible\EventHandlers' => 'lib/EventHandlers.php',
]);