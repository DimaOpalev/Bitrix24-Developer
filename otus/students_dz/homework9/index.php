<?php
use Bitrix\Main\Page\Asset;

global $APPLICATION;
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Учимся подключать свои скрипты, взаимодействовать с компонентами из фронтенда");
?>
<div class="container-fluid">
    <h3>Домашнее задание</h3>
    <p>
        Бизнес-процесс для обработки элементов инфоблока при создании.
    </p>
    <p>
        Цель:
    </p>
    <p>
        автоматизация бизнес-процессов компании;
    </p>
    <p>
        написание интеграций со сторонними сервисами;
    </p>
    <p>
        создание собственных активити для упрощении работы коллег.
    </p>
    <p>
        Описание/Пошаговая инструкция выполнения домашнего задания:
    </p>
    <p>
        Создать бизнес-процесс, который при создании элементов инфоблока, будет создавать компании, с автоматическим заполнением реквизитов из сервиса DADATA.
    </p>
    <p>
         Пошаговая инструкция:
    </p>
    <ul>
        <li>
            создать инфоблок с полями: "Сумма, Заказчик ИНН, Заказчик, Вид работ";
            <br><a href="/services/lists/18/view/0/?list_section_id=" target="_blank">Инфоблок заявки</a>
        </li>
        <li>
            создать БП, который при создании элемента инфоблока, будет автоматически получать данные компании из сервиса DADATA, добавлять компанию в CRM, и обновлять поле Заказчик элемента
            <br>Активити: <a href="/bitrix/admin/fileman_admin.php?PAGEN_1=1&SIZEN_1=20&lang=ru&site=s1&path=%2Flocal%2Factivities%2Fcustom%2Fgetdadataorgbyinnactivity&show_perms_for=0&fu_action=" target="_blank">getdadataorgbyinnactivity.php</a>
        </li>
    </ul>
</div>

<?php 

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");

?>