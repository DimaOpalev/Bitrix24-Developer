<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/**
 * @var CMain $APPLICATION
 */

$APPLICATION->SetTitle('Пример использования dump() из symfony');

dump([
    'A' => [
        'A' => [
            2
        ],
    ],
    'B' => [
        'A' => [
            1
        ],
    ],
]);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
?>