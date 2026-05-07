<?
use Bitrix\Main\Application;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$request = Application::getInstance()->getContext()->getRequest();

$APPLICATION->SetTitle("Бланк листа допуска");

$APPLICATION->IncludeComponent(
    'company:accessrequest.form',
    '.default',
    [
        'ID' => $request->get('ID') ?? 0,
        'ACTION' => $request->get('ACTION') ?? 'new',
        'BACK_URL' => '/dlya-sotrudnikov/list-dopuska/',
    ]
);

?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>