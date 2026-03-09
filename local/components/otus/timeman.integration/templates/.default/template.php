<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

// Добавляем JS
\Bitrix\Main\Page\Asset::getInstance()->addJs($templateFolder . '/script.js');

// Добавляем минимальные стили
\Bitrix\Main\Page\Asset::getInstance()->addCss($templateFolder . '/script.js');
?>

<div id="otus-timeman-container"></div>