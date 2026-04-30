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

class AccessRequestTable extends DataManager
{
    public const STATUS_NEW        = 0;
    public const STATUS_REVIEW     = 10;
    public const STATUS_APPROVED   = 20;
    public const STATUS_COMPLETED  = 30;
    public const STATUS_CANCELLED  = 80;
    public const STATUS_REJECTED   = 90;

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

    
    protected static function loadMessages(): void
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/company.accessrequest/lang/' . LANGUAGE_ID . '/lib/AccessRequestTable.php';
        if (file_exists($path)) {
            Loc::loadMessages($path);
        }
    }
}