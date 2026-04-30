<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("Лист допуска");

$APPLICATION->IncludeComponent(
    'company:accessrequest.list',
    '.default',
    []
);

?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>