<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\UserTable;

$module_id = 'light.calendar.responsible';

// Подключаем модуль и файлы локализации
Loader::includeModule($module_id);
Loc::loadMessages(__FILE__);

// Права доступа к настройкам (только администраторам)
if ($APPLICATION->GetGroupRight($module_id) < 'W') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$responsibleCalendarId = (int)Option::get($module_id, 'responsible_calendar_id', 0);
$responsibleUserId = (int)Option::get($module_id, 'responsible_user_id', 0);

// Сохраняем настройки при отправке формы
if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid()) {
    // Сохраняем ID ответственного сотрудника
    $responsibleUserId = (int)$_POST['responsible_user_id'];
    Option::set($module_id, 'responsible_user_id', $responsibleUserId);

    $responsibleCalendarId = (int)$_POST['responsible_calendar_id'];
    Option::set($module_id, 'responsible_calendar_id', $responsibleCalendarId);

    // Очищаем кеш, чтобы новые значения точно применились
    $cache = \Bitrix\Main\Data\Cache::createInstance();
    $cache->cleanDir('options');
    // Показываем сообщение об успешном сохранении
    LocalRedirect($APPLICATION->GetCurPageParam('success=Y', ['success']));
}

// Список активных пользователей
$users = UserTable::getList([
    'select' => ['ID', 'LOGIN', 'NAME', 'LAST_NAME'],
    'filter' => [
        'ACTIVE' => 'Y',
        '!=UF_DEPARTMENT' => false,
    ],
    'order' => ['LAST_NAME' => 'ASC', 'NAME' => 'ASC']
])->fetchAll();

?>

<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($module_id) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    <table class="edit-table" width="100%">
        <tr class="heading">
            <td colspan="2"><?= Loc::getMessage('VENDOR_CALENDAR_RESPONSIBLE_SETTINGS_TAB') ?></td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('VENDOR_CALENDAR_RESPONSIBLE_RESPONSIBLE_USER') ?>:</td>
            <td>
                <select name="responsible_user_id">
                    <option value="0"><?= Loc::getMessage('VENDOR_CALENDAR_RESPONSIBLE_SELECT_USER') ?></option>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $name = trim($user['LAST_NAME'] . ' ' . $user['NAME']);
                        if (empty($name)) $name = $user['LOGIN'];
                        ?>
                        <option value="<?= $user['ID'] ?>" <?= $user['ID'] == $responsibleUserId ? 'selected' : '' ?>>
                            <?= htmlspecialcharsbx($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><?= Loc::getMessage('VENDOR_CALENDAR_RESPONSIBLE_CALENDAR_ID') ?>:</td>
            <td>
                <input type="text" name="responsible_calendar_id" value="<?= htmlspecialcharsbx($responsibleCalendarId) ?>">
            </td>
        </tr>
        <?php if ($_GET['success'] == 'Y'): ?>
            <tr><td colspan="2" class="adm-info-message"><?= Loc::getMessage('VENDOR_CALENDAR_RESPONSIBLE_SAVED') ?></td></tr>
        <?php endif; ?>
    </table>
    <input type="submit" name="save" value="<?= Loc::getMessage('VENDOR_CALENDAR_RESPONSIBLE_SAVE_BTN') ?>">
</form>