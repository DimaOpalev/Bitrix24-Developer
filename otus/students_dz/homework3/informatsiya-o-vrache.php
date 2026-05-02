<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Информация о враче");

use Bitrix\Iblock\Elements\ElementDoctorsTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;

use Bitrix\Main\Page\Asset;
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Asset::getInstance()->addJs('//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js');


// Загружаем модули
Loader::includeModule('iblock');

$request = Application::getInstance()->getContext()->getRequest();

// Получить значение только из GET
$doctorId = $request->getQuery("doctor");

$doctor = ElementDoctorsTable::getByPrimary(
    $doctorId,
    array(
        'select' => [
            '*',
            'ID',
            "NAME",
            "LAST_NAME",
            "FIRST_NAME",
            "PATRONYMIC",
            'REF_PROCEDURE.ELEMENT.NAME'
        ]
    )
)->fetchObject();

$fioDoctor = [
    $doctor->get("LAST_NAME")->getValue(),
    $doctor->get("FIRST_NAME")->getValue(),
    $doctor->get("PATRONYMIC")->getValue(),
];

?>


<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="../homework3/">Врачи</a></li>
    <li class="breadcrumb-item active" aria-current="page">Врач</li>
  </ol>
</nav>

<h2><?=implode(" ", $fioDoctor)?></h2>
<div><?= $doctor->get("NAME")?></div>
<?
//получаем список процедур у врача
$procedures = $doctor->get("REF_PROCEDURE")->getAll();
?>
<ul>
<?
foreach($procedures as $procedure) {
    echo(implode(" ", [
        "<li>",
            $procedure->getElement()->get("NAME"),
        "</li>",
    ]));
}
/*?>
<form class="mt-4" action="sozdanie-izmenenie-vracha.php" method="POST">
    <input type="hidden" name="doctorId" value="<?=$doctor->get("ID")?>">
    <button class="btn btn-primary" name="action" value="editDoctor">
        Изменить
    </button>
    <button class="btn btn-danger" name="action" value="deleteDoctor">
        удалить
    </button>
</form>
<?
*/
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>