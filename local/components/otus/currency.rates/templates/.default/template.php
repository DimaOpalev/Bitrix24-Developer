<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

$this->setFrameMode(true);

// Проверяем, загружен ли Bootstrap CSS
$isBootstrapLoaded = false;

function isBootstrapLoaded($APPLICATION)
{
    // Проверяем CSS
    $headStrings = $APPLICATION->GetHeadStrings();
    foreach ($headStrings as $string) {
        if (preg_match('/bootstrap[^>]*\.css/i', $string)) {
            return true;
        }
    }

    // Проверяем JS
    $arHeadScripts = $APPLICATION->GetHeadScripts();
    foreach ($arHeadScripts as $script) {
        if (strpos($script, 'bootstrap') !== false) {
            return true;
        }
    }

    return false;
}

// // Проверяем и подключаем Bootstrap при необходимости
// if (!isBootstrapLoaded($APPLICATION)) {
//     // Версия 5.3.0
//     $APPLICATION->AddHeadString('
//         <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
//         <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
//     ');

//     // Добавляем мета-тег для адаптивности
//     $APPLICATION->AddHeadString('<meta name="viewport" content="width=device-width, initial-scale=1">');
// }


$currentUrl = $APPLICATION->GetCurPageParam('', ['currency']);

?>

<div class="currency-rates">
    <h1>Курсы валют</h1>

    <form class="mt-2" method="GET" action="<?= $currentUrl ?>">
        <label for="currency_select">
            Выберите валюту:
        </label>
        <div class="row">
            <div class="col-auto">
                <select name="currency" id="currency_select" class="form-select">
                    <option disabled selected value="">
                        Выберите валюту
                    </option>
                    <?php foreach ($arResult['CURRENCIES_LIST'] as $code => $name): ?>
                        <option value="<?= htmlspecialcharsbx($code) ?>"
                            <?= ($code == $arResult['CURRENT_CURRENCY']) ? 'selected' : '' ?>>
                            <?= htmlspecialcharsbx($name) ?> (<?= htmlspecialcharsbx($code) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">
                    Показать информацию
                </button>
            </div>
        </div>
    </form>
    <?php if (!empty($arResult['CURRENT_CURRENCY'])): ?>
        <div class="currency-info mt-4">
            <h2>
                Информация о валюте
            </h2>
            <?php $APPLICATION->IncludeComponent(
                "bitrix:main.ui.grid",
                "",
                Array(
                    "ACTION_PANEL" => [],
                    "AJAX_MODE" => "N",
                    "AJAX_OPTION_HISTORY" => "N",
                    "AJAX_OPTION_JUMP" => "N",
                    "AJAX_OPTION_STYLE" => "N",
                    "ALLOW_COLUMNS_RESIZE" => false,
                    "ALLOW_COLUMNS_SORT" => false,
                    "ALLOW_PIN_HEADER" => false,
                    "ALLOW_SORT" => false,
                    "COLUMNS" => $arResult['GRID_COLUMNS'],
                    "DEFAULT_PAGE_SIZE" => 10,
                    "ENABLE_NEXT_PAGE" => false,
                    "GRID_ID" => "currency_grid_".$arResult['CURRENT_CURRENCY'],
                    "NAV_OBJECT" => null,
                    "PAGE_SIZES" => [],
                    "ROWS" => [$arResult['GRID_DATA']],
                    "SHOW_ACTION_PANEL" => false,
                    "SHOW_CHECK_ALL_CHECKBOXES" => false,
                    "SHOW_PAGESIZE" => false,
                    "SHOW_ROW_CHECKBOXES" => false,
                    "SHOW_SELECTED_COUNTER" => false,
                    "SHOW_TOTAL_COUNTER" => false,
                    "TOTAL_ROWS_COUNT" => 1
                )
            );?>
        </div>
    <?php else: ?>
        <div class="currency-error" style="color: red; padding: 20px; border: 1px solid red;">
            Информация о валюте не найдена.
        </div>
    <?php endif; ?>
</div>
