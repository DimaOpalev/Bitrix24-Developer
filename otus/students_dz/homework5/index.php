<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
use Bitrix\Currency\CurrencyManager;
use Bitrix\Currency\CurrencyTable;

$APPLICATION->SetTitle("создание компонента курсов валют");

?><h3>Домашнее задание</h3>
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

<?
function getColumn()
{
	$fieldMap = CurrencyTable::getMap(); 
	$columns = [];
	foreach ($fieldMap as $key => $field) {
		$columns[] = array(
			'id' => $field->getName(),
			'name' => $field->getTitle()
		);
	}
	return $columns;
}
$column = getColumn();
$currencyCode = "RUB";

$currency = [
	'data' => CurrencyTable::getList([
		'select' => [
			'CURRENCY',
		],
		'filter' => ['=CURRENCY' => $currencyCode],
	])->fetch()
];

$APPLICATION->includeComponent(
	"bitrix:main.ui.grid",
	"",
	[
		"GRID_ID" => "CURRENT_CURRENCY",
		"COLUMNS" => $column,
		"ROWS" => $currency,
		"AJAX_MODE" => "Y",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_HISTORY" => "N",
		"SHOW_SELECTED_COUNTER" => false,
		"SHOW_PAGESIZE" => false,
	]
);

?>


 <?$APPLICATION->IncludeComponent(
	"otus:currency.rates",
	"",
	Array(
		"CACHE_TIME" => "3600",
		"CACHE_TYPE" => "A",
		"LIST_CURRENCY" => "RUB"
	)
);?>