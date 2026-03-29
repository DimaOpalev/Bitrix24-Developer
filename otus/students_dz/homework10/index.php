<?php
use Bitrix\Main\Page\Asset;

require_once $_SERVER["DOCUMENT_ROOT"]."/local/php_interface/src/Events/IBLOCK_DEAL_ID.php";

global $APPLICATION;
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Учимся подключать свои скрипты, взаимодействовать с компонентами из фронтенда");
?>
<div class="container-fluid">
    <h3>Домашнее задание</h3>
    <p>
        Обработчик изменений в элементе <a href="/services/lists/<?=IBLOCK_DEAL_ID?>/view/0/">инфоблока</a>
    </p>
    <p>
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Finit.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">отслеживание изменений сущностей CRM</a>.
    </p>
    <p>
        Цель:
    </p>
    <p>
        <a href="/bitrix/admin/fileman_admin.php?PAGEN_1=1&SIZEN_1=20&lang=ru&site=s1&path=%2Flocal%2Fphp_interface%2Fsrc%2FEvents&show_perms_for=0&fu_action=">обработка событий добавления/изменения/удаления элементов</a>;
    </p>
    <p>
        отслеживание изменений сущностей CRM.
    </p>
    <p>
         Пошаговая инструкция:
    </p>
    <p>
        Написать обработчики событий создания/изменения/удаления:
    </p>
    <ul>
        <li>
            <a href="/bitrix/admin/fileman_file_edit.php?lang=ru&site=s1&path=%2Flocal%2Fphp_interface%2Fsrc%2FEvents%2FIblockEventHandler.php&full_src=Y">элементов инфоблока, которые будут записывать изменения, внесенные пользователем, в сделку</a>;
        </li>
        <li>
            <a href="/bitrix/admin/fileman_file_edit.php?lang=ru&site=s1&path=%2Flocal%2Fphp_interface%2Fsrc%2FEvents%2FCrmDealEventHandler.php&full_src=Y">сделок, которые будут реагировать на изменения и обновлять элементы инфоблока</a>.
        </li>
    </ul>
</div>

<?php 

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");

?>