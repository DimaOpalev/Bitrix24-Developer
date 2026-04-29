<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Ошибка для exeption");
?>
<ul class="list-group">
    <li class="list-group-item">
        <a href="/local/logs/exceptions.log">Файл лога</a>
    </li>
</ul>
<?
// ошибка для exeptio
echo(1/0);

?>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
