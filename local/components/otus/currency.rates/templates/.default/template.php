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
    <div class="currency-selector row">
        <form method="post" action="<?= $currentUrl ?>">
            <div class="col-md-4">
                <div>
                    <label for="currency_select">
                        Выберите валюту:
                    </label>
                </div>
                <select name="selected_currency" id="currency_select" class="form-select">
                    <?php foreach ($arResult['CURRENCIES_LIST'] as $code => $name): ?>
                        <option value="<?= htmlspecialcharsbx($code) ?>" 
                            <?= ($code == $arResult['CURRENT_CURRENCY']['CODE']) ? 'selected' : '' ?>>
                            <?= htmlspecialcharsbx($name) ?> (<?= htmlspecialcharsbx($code) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                Показать информацию
            </button>
        </form>
    </div>
</div>
    <?php if (!empty($arResult['CURRENT_CURRENCY'])): ?>
        <div class="currency-info">
            <h2>
                Информация о валюте: 
            </h2>
            
            <table>
                <tr>
                    <td>
                        Код валюты:
                    </td>
                    <td>
                        <strong><?= htmlspecialcharsbx($arResult['CURRENT_CURRENCY']['CODE']) ?></strong>
                    </td>
                </tr>
                <tr>
                    <td>
                        Полное название:
                    </td>
                    <td>
                        <?= htmlspecialcharsbx($arResult['CURRENT_CURRENCY']["INFO"]['FULL_NAME']) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        Курс валюты:
                    </td>
                    <td>
                        <?= htmlspecialcharsbx($arResult['CURRENT_CURRENCY']["INFO"]['AMOUNT']) ?>
                    </td>
                </tr>

            </table>
        </div>
    <?php else: ?>
        <div class="currency-error" style="color: red; padding: 20px; border: 1px solid red;">
            Информация о валюте не найдена.
        </div>
    <?php endif; ?>
</div>