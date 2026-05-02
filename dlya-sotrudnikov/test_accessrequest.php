<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Loader;
use Company\AccessRequest\AccessRequestTable;
use Bitrix\Main\Type\DateTime;

use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Main\Engine\Component\EngineComponent;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\UI\PageNavigation;

$entityTypeId = 1042; // мой смарт-процесс
GLOBAL $USER;

Loader::includeModule('crm');
Loader::includeModule('intranet');

$container = Container::getInstance();
$factory = $container->getFactory($entityTypeId);

if (!$factory)
{
    throw new \Exception('Factory not found');
}

$userId = $USER->GetID();

$filter = [
    
];

// //Определяем отдел у пользователя
// $departments = [];

// $rs = \CIBlockSection::GetList(
//     [],
//     [
//         'IBLOCK_ID' => \CIntranetUtils::GetIBlockId(),
//         'UF_HEAD' => $userId
//     ],
//     false,
//     ['ID', 'NAME']
// );

// while ($section = $rs->Fetch())
// {
//     $departments[] = $section['ID'];
// }

// if(!empty($departments)) {
//     $filter['=UF_DEPARTMENT'] = $departments;
// }


$nav = new PageNavigation("smart_process_list");
$nav->allowAllRecords(true)
    ->setPageSize(10)
    ->initFromUri();



$items = $factory->getItemsFilteredByPermissions(
    [
        'filter' => $filter,
        'select' => ['ID', 'TITLE', 'CREATED_BY', 'STAGE_ID'],
        'order'  => ['ID' => 'DESC'],
        'limit'  => $nav->getLimit(),
        'offset' => $nav->getOffset(),
    ],
    $USER->GetID()
);

$total = $factory->getItemsCountFilteredByPermissions(
    $filter,
    $USER->GetID()
);

$result = [];
$stages = $factory->getStages();
$stageMap = [];

foreach ($stages as $stage)
{
    $stageMap[$stage->getStatusId()] = $stage->getName();
}

foreach ($items as $item)
{
    $stageId = $item->getStageId();
    $result[] = [
        'ID' => $item->getId(),
        'TITLE' => $item->getTitle(),
        'STAGE_ID' => $stageId,
        'STATUS' => $stageMap[$stageId] ?? 'Неизвестный статус'
    ];
}

var_dump($result);
