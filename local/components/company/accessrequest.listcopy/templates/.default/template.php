<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\UI\Toolbar\Facade\Toolbar;

// Загружаем языковой файл для шаблона
Loc::loadMessages(__FILE__);
Extension::load(['ui.buttons', 'ui.forms', 'ui.alerts', 'ui.grid']);

// Добавляем кнопки в тулбар
if (isset($arResult['TOOLBAR']['BUTTONS'])) {
    foreach ($arResult['TOOLBAR']['BUTTONS'] as $buttonParams) {
        $button = new \Bitrix\UI\Buttons\Button($buttonParams);
        Toolbar::addButton($button);
    }
}

// Добавляем фильтр в тулбар
if (isset($arResult['TOOLBAR']['FILTER'])) {
    Toolbar::addFilter($arResult['TOOLBAR']['FILTER']);
}

// Подключаем грид, уже без отдельной кнопки
$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '',
    [
        'GRID_ID' => $arResult['GRID_ID'],
        'COLUMNS' => $arResult['COLUMNS'],
        'ROWS' => $arResult['ROWS'],
        'NAV_OBJECT' => $arResult['NAV_OBJECT'],
        'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
        'SHOW_PAGESIZE' => true,
        'SHOW_NAVIGATION_PANEL' => true,
        'NAV_PARAM_NAME' => 'page',
        'PAGE_SIZES' => [                  // Доступные варианты (опционально)
            ['NAME' => '5', 'VALUE' => '5'],
            ['NAME' => '10', 'VALUE' => '10'],
            ['NAME' => '20', 'VALUE' => '20'],
            ['NAME' => '50', 'VALUE' => '50'],
            ['NAME' => '100', 'VALUE' => '100'],
        ],
        'DEFAULT_PAGE_SIZE' => 50,
        'SHOW_ROW_CHECKBOXES' => false,
        'SHOW_CHECK_ALL_CHECKBOXES' => false,
        'SHOW_SELECTED_COUNTER' => false,
        'SHOW_ACTION_PANEL' => false,
        'FILTER' => $arResult['FILTERS'],
        'FILTER_ID' => $arResult['FILTER_ID'],
        'SHOW_NAVIGATION_PANEL' => true,
        'ENABLE_COLLAPSIBLE_ROWS' => false,
        'SORT' => $arResult['SORT'],
        'SORT_VARS' => $arResult['SORT_VARS'],
    ]
);
?>