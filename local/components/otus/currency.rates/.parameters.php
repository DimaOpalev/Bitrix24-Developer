<?php
use Bitrix\Main\Loader;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Currency\CurrencyTable;

if (!Loader::includeModule('currency')) {
    die("Модуль \"Курс валют\" не установлен");
}

$baseCurrency = CurrencyManager::getBaseCurrency();

$currencyList = CurrencyTable::getList([
    'select' => ['CURRENCY', 'FULL_NAME' => 'CURRENT_LANG_FORMAT.FULL_NAME'],
    'filter' => ['=AMOUNT_CNT' => 1], // Только основные валюты
    'order' => ['SORT' => 'ASC', 'CURRENCY' => 'ASC'],
    'cache' => ['ttl' => 1]
]);

$listCurrency = [];

while($currency = $currencyList->fetch()) {
    $listCurrency[$currency["CURRENCY"]] = $currency['FULL_NAME'] . ' (' . $currency['CURRENCY'] . ')';
}
        

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = [
    'GROUPS' => [],
    'PARAMETERS' => [
        'LIST_CURRENCY' => [
            'PARENT' => 'BASE',
            'NAME' => 'Выберите валюту',
            'TYPE' => 'LIST',
            'VALUES' => $listCurrency,
            'DEFAULT' => $defaultCurrency,
            'MULTIPLE' => 'N',
            'ADDITIONAL_VALUES' => 'N',
            'REFRESH' => 'Y',
        ],
        'CACHE_TIME' => [
            'DEFAULT' => 3600,
        ],
    ],
];