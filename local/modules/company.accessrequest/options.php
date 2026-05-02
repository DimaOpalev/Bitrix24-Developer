<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Service\Container;

Loader::includeModule('crm');

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('company.accessrequest')) {
    return;
}

$module_id = 'company.accessrequest';
$request = \Bitrix\Main\Context::getCurrent()->getRequest();

// Определяем массив опций
$arAllOptions = [
    ['entity_type_id', Loc::getMessage('SMART_PROCESS_ID'), '', ['text', 50]],
    ['request_id_field_name', Loc::getMessage('REQUEST_ID_FIELD_NAME'), '', ['text', 50]],
    ['department_head_role', Loc::getMessage('DEPARTMENT_HEAD_ROLE'), '', ['text', 50]],
];

if ($request->isPost() && check_bitrix_sessid()) {
    foreach ($arAllOptions as $option) {
        $name = $option[0];
        $val = $request->getPost($name);
        Option::set($module_id, $name, $val);
    }
    
    // Нажата кнопка создания связи
    if ($request->getPost('create_binding') === 'Y') {
        // 1. Подключаем модуль CRM
        if (!Loader::includeModule('crm')) {
            \CAdminMessage::ShowMessage([
                'MESSAGE' => Loc::getMessage('ERROR_CRM_MODULE_NOT_CONNECTED'),
                'TYPE' => 'ERROR'
            ]);
            return;
        }
    }
}

$tabControl = new CAdminTabControl('tabControl', [
    ['DIV' => 'edit1', 'TAB' => Loc::getMessage('MAIN_TAB_SET'), 'ICON' => '', 'TITLE' => Loc::getMessage('MAIN_TAB_TITLE_SET')],
]);

$tabControl->Begin();
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= $module_id ?>&lang=<?= LANGUAGE_ID ?>">
<?= bitrix_sessid_post() ?>
<? $tabControl->BeginNextTab(); ?>
<tr>
    <td width="40%"><?= Loc::getMessage('SMART_PROCESS_ID') ?></td>
    <td width="60%">
        <input type="text" name="entity_type_id"
               value="<?= htmlspecialcharsbx(Option::get($module_id, 'entity_type_id', '')) ?>">
    </td>
</tr>
<tr>
    <td width="40%"><?= Loc::getMessage('REQUEST_ID_FIELD_NAME') ?></td>
    <td width="60%">
        <input type="text" name="request_id_field_name"
               value="<?= htmlspecialcharsbx(Option::get($module_id, 'request_id_field_name', '')) ?>">
    </td>
</tr>
<? $tabControl->Buttons(); ?>
<input type="submit" name="apply" value="<?= Loc::getMessage('MAIN_SAVE') ?>">
<? $tabControl->End(); ?>
</form>
<?