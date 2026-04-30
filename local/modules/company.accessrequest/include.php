<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('company.accessrequest', [
    'Company\AccessRequest\AccessRequestTable' => 'lib/AccessRequestTable.php',
    'Company\AccessRequest\AccessRequestHistoryTable' => 'lib/AccessRequestHistoryTable.php',
]);