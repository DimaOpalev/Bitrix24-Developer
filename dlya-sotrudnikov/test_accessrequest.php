<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Company\AccessRequest\AccessRequestTable;
use Bitrix\Main\Type\DateTime;

// Проверяем, авторизован ли пользователь (лучше администратор)
global $USER;
if (!$USER->IsAdmin()) {
    die('Требуются права администратора');
}

// Подключаем модуль
if (!Loader::includeModule('company.accessrequest')) {
    die('Модуль company.accessrequest не найден');
}

echo '<h1>Тестирование модуля AccessRequest</h1>';

// 1. CREATE — добавление тестовой записи
$addResult = AccessRequestTable::add([
    'REF_CREATE_USER' => $USER->GetID(),
    'REF_DEPARTMENT' => 1,
    'EMPLOYEE_NAME' => 'Тестовый Сотрудник',
    'REQUESTED_ACCESS' => json_encode(['rdp', '1c']),
    'STATUS' => AccessRequestTable::STATUS_NEW,
    'CREATED_DATE' => new DateTime(),
]);

if ($addResult->isSuccess()) {
    $newId = $addResult->getId();
    echo "<p>✅ Заявка создана, ID = $newId</p>";
} else {
    die('<p>❌ Ошибка при создании: ' . implode(', ', $addResult->getErrorMessages()) . '</p>');
}

// 2. READ — получение списка заявок
$list = AccessRequestTable::getList([
    'select' => ['*'],
    'limit' => 10,
    'order' => ['ID' => 'DESC'],
]);

echo '<h2>Последние 10 заявок</h2>';
echo '<table border="1" cellpadding="5"><tr><th>ID</th><th>ФИО сотрудника</th><th>Статус</th><th>Дата создания</th></tr>';
while ($row = $list->fetch()) {
    echo "<tr>
            <td>{$row['ID']}</td>
            <td>{$row['EMPLOYEE_NAME']}</td>
            <td>" . AccessRequestTable::getStatusName($row['STATUS']) . "</td>
            <td>{$row['CREATED_DATE']}</td>
          </tr>";
}
echo '</table>';

// 3. UPDATE — обновление статуса только что созданной записи
$updateResult = AccessRequestTable::update($newId, [
    'STATUS' => AccessRequestTable::STATUS_REVIEW,
    'UPDATED_DATE' => new DateTime(),
]);

if ($updateResult->isSuccess()) {
    echo "<p>✅ Статус заявки #$newId изменён на 'На рассмотрении'</p>";
} else {
    echo "<p>❌ Ошибка обновления: " . implode(', ', $updateResult->getErrorMessages()) . "</p>";
}

// 4. DELETE — удаление тестовой записи (раскомментируйте, если нужно)
/*
$deleteResult = AccessRequestTable::delete($newId);
if ($deleteResult->isSuccess()) {
    echo "<p>✅ Заявка #$newId удалена</p>";
} else {
    echo "<p>❌ Ошибка удаления: " . implode(', ', $deleteResult->getErrorMessages()) . "</p>";
}
*/

// Проверка работы ORM-связи с историей (если есть записи в access_request_history)
$historyList = \Company\AccessRequest\AccessRequestHistoryTable::getList([
    'select' => ['*', 'REQUEST_EMPLOYEE' => 'REQUEST.EMPLOYEE_NAME'],
    'limit' => 5,
]);
echo '<h2>Связанные данные (история + заявка)</h2>';
echo '<table border="1" cellpadding="5"><tr><th>ID истории</th><th>Решение</th><th>Комментарий</th><th>Заявка (сотрудник)</th></tr>';
while ($hist = $historyList->fetch()) {
    echo "<tr>
            <td>{$hist['ID']}</td>
            <td>" . AccessRequestTable::getStatusName($hist['STATUS']) . "</td>
            <td>{$hist['COMMENT']}</td>
            <td>{$hist['REQUEST_EMPLOYEE']}</td>
          </tr>";
}
echo '</table>';

echo '<hr><p>Тестирование завершено.</p>';