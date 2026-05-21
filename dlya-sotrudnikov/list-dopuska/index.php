<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("Лист допуска");


$APPLICATION->IncludeComponent(
    'company:accessrequest.list',
    '.default',
    [
        'ADD_BUTTON_URL' => '/dlya-sotrudnikov/list-dopuska/item/',
        'ITEM_URL' => '/dlya-sotrudnikov/list-dopuska/item/?ID=#ID#',
    ]
);


?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>