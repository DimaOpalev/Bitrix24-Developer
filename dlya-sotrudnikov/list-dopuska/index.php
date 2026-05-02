<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->SetTitle("Лист допуска");

\Bitrix\Main\Loader::includeModule('crm');
$type = \Bitrix\Crm\Service\Container::getInstance()->getTypeBroker()->getByTitle('ВАШЕ_НАЗВАНИЕ_СМАРТ_ПРОЦЕССА');
echo '<pre>';
var_dump($type);
echo '</pre>';

$APPLICATION->IncludeComponent(
    'company:accessrequest.list',
    '.default',
    []
);

?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>