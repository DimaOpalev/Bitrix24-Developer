<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Cоздание компонента курсов валют");
?>
<div class="container-fluid">
    <h3>Домашнее задание</h3>
    <p>
         Создать собственный компонент
    </p>
    <p>
         Пошаговая инструкция:
    </p>
    <ul>
        <li>Он будет иметь всего один параметр - выпадающий список, в котором можно будет выбрать валюту из списка, доступного по адресу /bitrix/admin/currencies.php.</li>
        <li>Компонент будет выводить в шаблон текущий курс выбранной валюты, который можно узнать в том же справочнике по адресу /bitrix/admin/currencies.php.</li>
        <li>Разместить компонент следует на странице /otus/currencies.php.</li>
    </ul>

    <div class="mt-4">
        <?php
        $APPLICATION->IncludeComponent(
            "otus:currency.rates",
            "",
            Array(
                "CACHE_TIME" => "3600",
                "CACHE_TYPE" => "A",
                "LIST_CURRENCY" => ""
            )
        );?>
    </div>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>