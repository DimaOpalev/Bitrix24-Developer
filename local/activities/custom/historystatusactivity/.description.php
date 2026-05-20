<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
    "NAME" => "Лист допуска",
    "DESCRIPTION" => "Логирование и установка статуса заявления в листе допуска",
    "TYPE" => "activity",
    "CLASS" => "HistoryStatusActivity",
    "JSCLASS" => "BizProcActivity",
    "CATEGORY" => [
        "ID" => "other",
    ],
    "RETURN" => [
        "Text" => [
            "NAME" => "Логирование и установка статуса заявления в листе допуска",
            "TYPE" => "string",
        ],
        "ResultText" => [
            "NAME" => "Текст листа допуска",
            "TYPE" => "string",
        ],
        "ResultHTML" => [
            "NAME" => "HTML листа допуска",
            "TYPE" => "string",
        ],
        "RefDepartment" => [
            "NAME" => "Подразделение, в котором работает сотрудник",
            "TYPE" => "int",
        ],
        "DepartmenManager" => [
            "NAME" => "Руководитель подразделения",
            "TYPE" => "user",
        ],

    ],
];