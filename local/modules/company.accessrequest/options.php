<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

if (!Loader::includeModule('company.accessrequest')) {
    return;
}

$module_id = 'company.accessrequest';
$request = \Bitrix\Main\Context::getCurrent()->getRequest();

// Определяем массив опций
$arAllOptions = [
    ['department_head_role', Loc::getMessage('DEPARTMENT_HEAD_ROLE'), '', ['text', 50]],
];

if ($request->isPost() && check_bitrix_sessid()) {
    foreach ($arAllOptions as $option) {
        $name = $option[0];
        $val = $request->getPost($name);
        Option::set($module_id, $name, $val);
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
    <td width="40%"><?= Loc::getMessage('DEPARTMENT_HEAD_ROLE') ?></td>
    <td width="60%">
        <input type="text" name="department_head_role" value="<?= htmlspecialcharsbx(Option::get($module_id, 'department_head_role', '')) ?>">
    </td>
</tr>
<? $tabControl->Buttons(); ?>
<input type="submit" name="apply" value="<?= Loc::getMessage('MAIN_SAVE') ?>">
<? $tabControl->End(); ?>
</form>
<?