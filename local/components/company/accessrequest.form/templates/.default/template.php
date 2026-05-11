<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Page\Asset;

Extension::load(['ui.buttons', 'ui.alerts']);
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

if (!empty($arResult['ERRORS'])) {
    foreach ($arResult['ERRORS'] as $error) {
        echo '<div class="ui-alert ui-alert-danger">' . $error . '</div>';
    }
}

?>
<a class="ui-btn ui-btn-sm" href="<?= $arResult['BACK_URL'] ?>"><?= Loc::getMessage('BACK_BUTTON') ?></a>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-6">
            <form method="post" id="access-request-form">
                <?= bitrix_sessid_post() ?>
                <input type="hidden" name="ID" value="<?= $arResult['REQUEST']['ID'] ?? 0 ?>">
                <h4 class="text-center">Лист допуска на сотрудника</h3>
                <table class="table">
                    <tr>
                        <td colspan="3"><label for="FIO"><?= Loc::getMessage('FIELD_EMPLOYEE') ?>:</label></td>
                    </tr>
                    <tr <?=isset($arResult["ERRORS"]["FIO"]) ? "class='table-danger'":""?>>
                        <td colspan="3">
                            <input type="text" name="FIO" id="FIO" value="<?= htmlspecialcharsbx($arResult["POST_DATA"]['FIO']) ?>" 
                                <?= $arResult['READONLY'] ? 'readonly' : '' ?> class="form-control ">
                        </td>
                    </tr>
                    <tr <?=isset($arResult["ERRORS"]["insertCompanyStructure"]) ? "class='table-danger'":""?>>
                        <td colspan="3"><b><?= Loc::getMessage('FIELD_DEPARTMENT') ?>:</b>
                            <div id="insertCompanyStructure"></div>
                            <!-- Скрытое поле для хранения ID отдела -->
                            <input type="hidden" name="REF_DEPARTMENT" id="REF_DEPARTMENT" value="<?= (int)$arResult["POST_DATA"]['REF_DEPARTMENT'] ?>">

                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <b><?= Loc::getMessage('FIELD_STATUS') ?>:</b> <?= $arResult['STATUS_LIST'][$arResult['CURRENT_STATUS']] ?>
                        </td>
                    </tr>
                    <tr class="text-center">
                        <th><?= Loc::getMessage('TYPE_ACCESS') ?></th>
                        <th><?= Loc::getMessage('ACCESS_ALLOW') ?></th>
                        <th><?= Loc::getMessage('ACCESS_DENY') ?></th>
                    </tr>
                    
                    <?php
                    // Вывод секций и элементов доступа
                    $currentSection = 0;
                    foreach ($arResult['ACCESS_ELEMENTS'] as $element) {
                        $sectionId = (int)$element['IBLOCK_SECTION_ID'];
                        if ($currentSection !== $sectionId) {
                            $sectionName = $arResult['ACCESS_SECTIONS'][$sectionId]['NAME'];
                            ?>
                                <tr><th colspan="3" class="text-center"><?=$sectionName?></th></tr>
                            <?php
                            $currentSection = $sectionId;
                        }
                        $checkedAllow = '';
                        $checkedDeny = 'checked="checked"';
                        $otherValue = '';
                        
                        if(isset($arResult['POST_DATA']['user_value'][$element['ID']])) {
                            if ($arResult['POST_DATA']['user_value'][$element['ID']] == 'all') {
                                $checkedAllow = 'checked="checked"';
                                $checkedDeny = '';
                            }
                        }
                        
                        if (isset($arResult['POST_DATA']['user_other'][$element['ID']])) {
                            $otherValue = htmlspecialcharsbx($arResult['POST_DATA']['user_other'][$element['ID']]);
                        }
                    
                        $isOther = ($element['CODE'] == 'other');
                        $row_class_error = "";
                        if(isset($arResult["ERRORS"]["row_".$element['ID']])) {
                            $row_class_error = "class='table-danger'";
                        }
                        ?>
                        <tr id="row_<?= $element['ID'] ?>" <?=$row_class_error?>>
                            <td>
                                <?= $element['NAME'] ?>
                                <?php if ($isOther): ?>
                                    <textarea name="user_other[<?= $element['ID'] ?>]" class="other form-control" rows="2" <?= $arResult['READONLY'] ? 'readonly' : '' ?>><?= $otherValue ?></textarea>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <input type="radio" name="user_value[<?= $element['ID'] ?>]" value="all" <?= $checkedAllow ?> <?= $arResult['READONLY'] ? 'disabled' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="radio" name="user_value[<?= $element['ID'] ?>]" value="cancel" <?= $checkedDeny ?> <?= $arResult['READONLY'] ? 'disabled' : '' ?>>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                
                <?php if (!$arResult['READONLY']): ?>
                    <button class="ui-btn ui-btn-primary" name="matchingButton"><?= Loc::getMessage('SAVE_BUTTON') ?></button>
                    <button class="ui-btn ui-btn-success" name="SendMatching"><?= Loc::getMessage('SEND_BUTTON') ?></button>
                <?php endif; ?>
                <a class="ui-btn ui-btn-sm" href="<?= $arResult['BACK_URL'] ?>"><?= Loc::getMessage('BACK_BUTTON') ?></a>
            </form>
        </div>
        <?php
        if($arParams['ID']) {
        ?>
            <div class="col-lg-6">
                <h4 class="text-center">История согласования</h4>
                <?php
                    $APPLICATION->IncludeComponent(
                        'bitrix:main.ui.grid',
                        '',
                        [
                            'GRID_ID' => "AccessRequestHistrory",
                            'COLUMNS' => $arResult['COLUMNS'],
                            'ROWS' => $arResult['ROWS'],
                            'NAV_OBJECT' => $arResult['NAV_OBJECT'],
                            'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
                            'SHOW_PAGESIZE' => true,
                            'AJAX_MODE' => 'Y',
                            'AJAX_OPTION_JUMP' => 'N',
                            'AJAX_OPTION_HISTORY' => 'N',
                        ]
                    );
                ?>
            </div>
        <?php
        }
        ?>
    </div>
</div>
<script>
    BX.ready(function() {
        // Загружаем модуль лениво
        BX.Runtime.loadExtension('ui.entity-selector').then(exports => {
            const { TagSelector, Dialog } = exports;
            // Получаем ID отдела из PHP
            const departmentId = <?= (int)$arResult["POST_DATA"]['REF_DEPARTMENT'] ?>;
            console.log("departmentId", departmentId);
            // Создаём виджет
            const tagSelector = new TagSelector({
                multiple: false,
                // Если есть предзаполненный отдел, добавляем его
                dialogOptions: {
                    context: 'COMPANY_ACCESS_REQUEST',
                    entities: [{
                        id: 'department',
                        options: {
                            selectMode: 'departmentsOnly' // выбор только отделов[reference:2]
                        }
                    }],
                    dynamicLoad: true,
                    dynamicSearch: true,
                    // Передаём массив предзаполненных элементов
                    preselectedItems: departmentId ? [['department', departmentId]] : [],
                    events: {
                        'Item:onSelect': (event) => {
                            const item = event.getData().item;
                            console.log('Выбрано:', event);
                            $('#REF_DEPARTMENT').val(item.getId());
                            // Обновить отображение в кастомном теге
                        },
                        'Item:onDeselect': (event) => {
                            $('#REF_DEPARTMENT').val('');
                        }
                    }
                    
                },

            });

            // Рендерим виджет в нужный контейнер
            tagSelector.renderTo(document.getElementById('insertCompanyStructure'));
        }).catch(error => {
            console.error('Ошибка загрузки ui.entity-selector:', error);
        });
    });
</script>