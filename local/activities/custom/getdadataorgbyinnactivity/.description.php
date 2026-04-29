<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
    "NAME" => Loc::getMessage("GETDADATA_DESCR_NAME"),
    "DESCRIPTION" => Loc::getMessage("GETDADATA_DESCR_DESCR"),
    "TYPE" => "activity",
    "CLASS" => "GetDadataOrgByInnActivity",
    "JSCLASS" => "BizProcActivity",
    "CATEGORY" => [
        "ID" => "other",
    ],
    "RETURN" => [
        "Text" => [
            "NAME" => Loc::getMessage("HELLOWORLD_DESCR_FIELD_TEXT"),
            "TYPE" => "string",
        ],
        "CompanyId" => [
            "NAME" => "ID организации в CRM",
            "TYPE" => "int",
        ],
        "OrgName" => [
            "NAME" => "Название организации",
            "TYPE" => "string",
        ],
        "Address" => [
            "NAME" => "Адрес",
            "TYPE" => "string",
        ],
        "Kpp" => [
            "NAME" => "КПП",
            "TYPE" => "string",
        ],
        "Ogrn" => [
            "NAME" => "ОГРН",
            "TYPE" => "string",
        ],
        "Director" => [
            "NAME" => "Руководитель",
            "TYPE" => "string",
        ],
        "Okved" => [
            "NAME" => "ОКВЭД",
            "TYPE" => "string",
        ],
        "Status" => [
            "NAME" => "Статус",
            "TYPE" => "string",
        ],
        "ErrorText" => [
            "NAME" => "Текст ошибки",
            "TYPE" => "string",
        ],
    ],
];