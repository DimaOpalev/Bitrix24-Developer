<?php

use Bitrix\Main\Loader;

define('COMPANY_ACCESSREQUEST_MODULE_ID', 'company.accessrequest');

Loader::registerAutoLoadClasses('company.accessrequest', [
    'Company\AccessRequest\AccessRequestTable' => 'lib/AccessRequestTable.php',
    'Company\AccessRequest\AccessRequestHistoryTable' => 'lib/AccessRequestHistoryTable.php',
]);