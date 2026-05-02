<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

Asset::getInstance()->addCss('/dlya-sotrudnikov/assets/css/style.css');
$APPLICATION->SetTitle(Loc::getMessage("TITLE"));

?>
<ul class="list_services">
    <li class="item">
        <a href="list-dopuska/">
            <div class="img"><img src="./assets/img/list-dopuska.jpg"></div>
            <div class="title">Лист допуска</div>
        </a>
    </li>
</ul>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
