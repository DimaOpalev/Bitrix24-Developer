<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI\Extension;
use Bitrix\Main\Localization\Loc;

// Загружаем языковой файл для шаблона
Loc::loadMessages(__FILE__);

Extension::load(['ui.buttons', 'ui.forms', 'ui.alerts', 'ui.grid']);

if (!empty($arResult['ADD_BUTTON_URL'])): ?>
<div style="margin-bottom: 20px;">
    <a href="<?= htmlspecialcharsbx($arResult['ADD_BUTTON_URL']) ?>" class="ui-btn ui-btn-primary">
        <?= htmlspecialcharsbx($arResult['ADD_REQUEST_BUTTON_TEXT'] ?: Loc::getMessage('ADD_REQUEST_BUTTON')) ?>
    </a>
</div>
<?php endif;

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
        'SHOW_CHECK_ALL_CHECKBOXES' => false,
        'SHOW_SELECTED_COUNTER' => false,
        'SHOW_ACTION_PANEL' => true,
        'ACTION_PANEL' => [],
        'FILTER' => $arResult['FILTERS'],
        'FILTER_ID' => $arResult['FILTER_ID'],
        'SHOW_NAVIGATION_PANEL' => true,
        'ENABLE_COLLAPSIBLE_ROWS' => false,
        'SORT' => $arResult['SORT'],
        'SORT_VARS' => $arResult['SORT_VARS'],
    ]
);