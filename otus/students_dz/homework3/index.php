<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Список врачей");
use Bitrix\Main\Page\Asset;
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Asset::getInstance()->addJs('//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js');

\Bitrix\Main\Loader::includeModule('iblock');

?>
<ul class="nav nav-tabs">
  <li class="nav-item">
    <a class="nav-link active" aria-current="page" href="#">Список врачей</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" href="./spisok-uslug/">Список процедур</a>
  </li>
</ul>
<!--button type="button" class="btn btn-primary mt-4">Добавить врача</button-->

<?

$doctors = \Bitrix\Iblock\Elements\ElementDoctorsTable::getList([
    'select' => [
        'ID',
        "NAME",
        "LAST_NAME",
        "FIRST_NAME",
        "PATRONYMIC",
        'REF_PROCEDURE'
    ], 
    'filter' => [
        '=ACTIVE' => "Y",
    ],
])->fetchCollection();
?>
<div class="d-flex justify-content-center mt-4">
<?
foreach ($doctors as $doctor) {
    $fio_doctor = [
        $doctor->get("LAST_NAME")->getValue(),
        $doctor->get("FIRST_NAME")->getValue(),
        $doctor->get("PATRONYMIC")->getValue(),
    ];
    ?>
    <a class="card mx-4" style="width: 18rem;" href="informatsiya-o-vrache.php?doctor=<?= $doctor->get("ID")?>">
        <div class="card-body">
            <h6 class="card-title"><?=$doctor->get("NAME")?></h5>
            <h5 class="card-subtitle mb-2 text-muted"><?=implode(" ", $fio_doctor)?></h6>
        </div>
    </a>
    <?
}
?>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>