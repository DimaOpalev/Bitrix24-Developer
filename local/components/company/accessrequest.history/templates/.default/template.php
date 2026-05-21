<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
Extension::load(['ui.design-tokens', 'ui.fonts.opensans', 'ui.info-helper']);
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

if (empty($arResult['HISTORY'])): ?>
    <div class="ui-alert ui-alert-info">
        <span class="ui-alert-message"><?= GetMessage('NO_HISTORY_RECORDS') ?></span>
    </div>
<?php else: ?>
    <div class="access-request-history" style="padding: 20px;">
        <table class="table">
            <thead>
                <tr>
                    <th><?= GetMessage('HISTORY_DATE') ?></th>
                    <th><?= GetMessage('HISTORY_USER') ?></th>
                    <th><?= GetMessage('HISTORY_STATUS') ?></th>
                    <th><?= GetMessage('HISTORY_COMMENT') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($arResult['HISTORY'] as $item): ?>
                    <tr>
                        <td><?= $item['CREATED_DATE'] ? $item['CREATED_DATE']->toString() : '' ?></td>
                        <td><?= htmlspecialcharsbx($item['USER_NAME']) ?></td>
                        <td><?= $arResult['STATUS_LIST'][$item['STATUS']] ?? $item['STATUS'] ?></td>
                        <td><?= htmlspecialcharsbx($item['COMMENT']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
        <?php
            /*
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
            )
            */
        ?>
<?php endif;