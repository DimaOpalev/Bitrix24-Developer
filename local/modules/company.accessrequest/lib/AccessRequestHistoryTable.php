<?php

namespace Company\AccessRequest;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;

class AccessRequestHistoryTable extends DataManager
{
    public static function getTableName()
    {
        return 'access_request_history';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new IntegerField('REF_REQUEST'))
                ->configureRequired(),
            (new IntegerField('USER_DECISION_MAKER'))
                ->configureRequired(),
            (new IntegerField('STATUS'))
                ->configureRequired(),
            (new DatetimeField('CREATED_DATE'))
                ->configureDefaultValue(new DateTime()),
            (new TextField('COMMENT')),
            (new Reference('REQUEST', __NAMESPACE__ . '\AccessRequestTable', Join::on('this.REF_REQUEST', 'ref.ID')))
                ->configureJoinType('INNER'),
        ];
    }
}