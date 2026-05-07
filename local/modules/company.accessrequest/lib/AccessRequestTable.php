<?php

namespace Company\AccessRequest;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

class AccessRequestTable extends DataManager
{
    public const STATUS_NEW        = 0;
    public const STATUS_REVIEW     = 10;
    public const STATUS_APPROVED   = 20;
    public const STATUS_COMPLETED  = 30;
    public const STATUS_CANCELLED  = 80;
    public const STATUS_REJECTED   = 90;

    public const BADGE_STATUS = [
        self::STATUS_NEW => "text-bg-light",
        self::STATUS_REVIEW => "text-bg-warning",
        self::STATUS_APPROVED => "text-bg-success",
        self::STATUS_COMPLETED => "text-bg-success",
        self::STATUS_CANCELLED => "text-bg-danger",
        self::STATUS_REJECTED => "text-bg-danger",
    ];

    public static function getTableName()
    {
        return 'access_request';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new IntegerField('REF_CREATE_USER'))
                ->configureRequired(),
            (new IntegerField('REF_DEPARTMENT')),
            (new StringField('EMPLOYEE_NAME'))
                ->configureRequired()
                ->configureSize(255),
            (new TextField('REQUESTED_ACCESS')),
            (new IntegerField('STATUS'))
                ->configureRequired()
                ->configureDefaultValue(self::STATUS_NEW),
            (new IntegerField('REF_TASK')),
            (new DatetimeField('CREATED_DATE'))
                ->configureDefaultValue(new DateTime()),
            (new DatetimeField('UPDATED_DATE')),
            (new OneToMany('HISTORY', __NAMESPACE__ . '\AccessRequestHistoryTable', 'REQUEST'))
                ->configureJoinType('LEFT'),
        ];
    }

    public static function getStatusList(): array
    {
        static::loadMessages();
        return [
            self::STATUS_NEW        => Loc::getMessage('ACCESS_STATUS_NEW'),
            self::STATUS_REVIEW     => Loc::getMessage('ACCESS_STATUS_REVIEW'),
            self::STATUS_APPROVED   => Loc::getMessage('ACCESS_STATUS_APPROVED'),
            self::STATUS_CANCELLED  => Loc::getMessage('ACCESS_STATUS_CANCELLED'),
            self::STATUS_REJECTED   => Loc::getMessage('ACCESS_STATUS_REJECTED'),
            self::STATUS_COMPLETED  => Loc::getMessage('ACCESS_STATUS_COMPLETED'),
        ];
    }

    public static function getStatusName(int $status): string
    {
        return self::getStatusList()[$status] ?? Loc::getMessage('ACCESS_STATUS_UNKNOWN');
    }

    public static function generateText(int $requestId): string
    {
        Loader::includeModule('company.accessrequest');

        $request = AccessRequestTable::getById($requestId)->fetch();

        if (!$request) {
            throw new \Exception("Заявка #{$requestId} не найдена");
        }

        $arResult = self::loadAccessDirectory();

        $accessList = self::decodeAccess($request['REQUESTED_ACCESS']);
        $history = self::getHistory($requestId);

        $arDepartment = \CIBlockSection::GetList(
            ['SORT' => 'ASC'],
            ['ID' => $request['REF_DEPARTMENT'],],
            false,
            ['ID', 'NAME']
        )->fetch();

        $text = [];
        $text[] = "[b]ЛИСТ ДОПУСКА[/b] №{$request['ID']}";
        $text[] = "[b]Дата создания:[/b] " . $request['CREATED_DATE']->toString();
        $text[] = "";

        $text[] = "[b]Сотрудник:[/b] {$accessList['FIO']}";
        $text[] = "[b]Отдел:[/b] {$arDepartment['NAME']}";
        $text[] = "[b]Статус:[/b] " . AccessRequestTable::getStatusName($request['STATUS']);
        $text[] = "";

        $text[] = "[b]Запрашиваемые доступы:[/b]";
        foreach ($accessList["user_value"] as $access_key => $access_item)
        {
            if($access_item == "all") {
                $line = "[b]".$arResult["ACCESS_ELEMENTS"][$access_key]["NAME"]."[/b]".(isset($accessList["user_other"][$access_key]) ? " (".$accessList["user_other"][$access_key].")" : "") . " - предоставить";
                $text[] = $line;
                $text[] = "_____________";
            }
        }

        $text[] = "";
        $text[] = "[b]История согласований:[/b]";

        foreach ($history as $row)
        {
            $text[] = sprintf(
                "[%s] Пользователь %s → %s (%s)",
                $row['CREATED_DATE']->toString(),
                implode(" ", [
                    $row['USER_LAST_NAME'], 
                    $row['USER_NAME'], 
                    $row['USER_SECOND_NAME']
                ]),
                AccessRequestTable::getStatusName($row['STATUS']),
                $row['COMMENT'] ?? ''
            );
            $text[] = "_____________";
        }

        return implode("\n", $text);
    }

    public static function decodeAccess(?string $json): array
    {
        if (!$json) {
            return [];
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    private static function getHistory(int $requestId): array
    {
        $history = AccessRequestHistoryTable::getList([
            'filter' => ['=REF_REQUEST' => $requestId],
            'select' => [
                'ID',
                'STATUS',
                'COMMENT',
                'CREATED_DATE',

                // поля пользователя
                'USER_ID' => 'USER.ID',
                'USER_NAME' => 'USER.NAME',
                'USER_LAST_NAME' => 'USER.LAST_NAME',
                'USER_SECOND_NAME' => 'USER.SECOND_NAME',
            ],
            'order' => ['ID' => 'ASC']
        ])->fetchAll();
        return $history;
    }

    public static function loadAccessDirectory() {
        Loader::includeModule('iblock'); // для получения справочника доступов

        // Получаем структуру из инфоблока "list_dopuska"
        $sections = [];
        $elements = [];

        $rsSections = \CIBlockSection::GetList(
            ['SORT' => 'ASC'],
            ['IBLOCK_CODE' => 'list_dopuska', 'ACTIVE' => 'Y'],
            false,
            ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'DEPTH_LEVEL']
        );

        while ($section = $rsSections->Fetch()) {
            $sections[$section['ID']] = $section;
        }

        $rsElements = \CIBlockElement::GetList(
            ['IBLOCK_SECTION_ID' => 'ASC', 'SORT' => 'ASC'],
            ['IBLOCK_CODE' => 'list_dopuska', 'ACTIVE' => 'Y', 'SECTION_ID' => array_keys($sections)],
            false,
            false,
            ['ID', 'NAME', 'IBLOCK_SECTION_ID', 'CODE']
        );

        while ($element = $rsElements->Fetch()) {
            $elements[$element['ID']] = $element;
        }

        $arResult['ACCESS_SECTIONS'] = $sections;
        $arResult['ACCESS_ELEMENTS'] = $elements;

        return $arResult;
    }

    protected static function loadMessages(): void
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/company.accessrequest/lang/' . LANGUAGE_ID . '/lib/AccessRequestTable.php';
        if (file_exists($path)) {
            Loc::loadMessages($path);
        }
    }

    public static function getUserName(int $userId=0)
    {
        $user = \Bitrix\Main\UserTable::getList([
            'select' => ['NAME', 'LAST_NAME', 'LOGIN'],
            'filter' => ['=ID' => $userId],
        ])->fetch();
        if ($user) {
            $name = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
            return $name ?: $user['LOGIN'];
        }
        return (string)$userId;
    }
}