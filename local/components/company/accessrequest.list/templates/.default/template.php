<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI\Extension;

Extension::load(['ui.buttons', 'ui.forms', 'ui.alerts', 'ui.grid']);

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
        'SHOW_ROW_CHECKBOXES' => false,
        'SHOW_SELECTED_COUNTER' => false,
        'ACTION_PANEL' => [],
        'FILTER' => $arResult['FILTERS'],
        'FILTER_ID' => $arResult['FILTER_ID'],
        'SHOW_NAVIGATION_PANEL' => true,
        'ENABLE_COLLAPSIBLE_ROWS' => false,
        'SORT' => $arResult['SORT'],
        'SORT_VARS' => $arResult['SORT_VARS'],
    ]
);