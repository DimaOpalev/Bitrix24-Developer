<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Список процедур");
use Bitrix\Main\Page\Asset;
Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Asset::getInstance()->addJs('//cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js');

?>
<ul class="nav nav-tabs">
  <li class="nav-item">
    <a class="nav-link" href="../">Список врачей</a>
  </li>
  <li class="nav-item">
    <a class="nav-link active" href="#" aria-current="page">Список процедур</a>
  </li>
</ul>
<!--button type="button" class="btn btn-primary mt-4">Добавить процедуру</button-->

<div class="d-flex justify-content-center mt-4">
  <?
  $procedures = \Bitrix\Iblock\Elements\ElementTypesproceduresTable::getList([
      'select' => [
          'ID',
          "NAME",
      ], 
      'filter' => [
          '=ACTIVE' => "Y",
      ],
  ])->fetchCollection();

  foreach ($procedures as $procedure) {
    ?>
    <div class="card mx-4" style="width: 18rem;" href="informatsiya-o-vrache.php?doctor=<?=$procedure->get("ID")?>">
        <div class="card-body">
            <h6 class="card-title"><?=$procedure->get("NAME")?></h5>
        </div>
  </div>
    <?
  }
  ?>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>