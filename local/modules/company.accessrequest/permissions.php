<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

return [
    'view_own_requests' => [
        'title' => Loc::getMessage('PERM_VIEW_OWN_REQUESTS'),
        'description' => Loc::getMessage('PERM_VIEW_OWN_REQUESTS_DESC'),
    ],
    'view_department_requests' => [
        'title' => Loc::getMessage('PERM_VIEW_DEPARTMENT_REQUESTS'),
        'description' => Loc::getMessage('PERM_VIEW_DEPARTMENT_REQUESTS_DESC'),
    ],
    'view_all_requests' => [
        'title' => Loc::getMessage('PERM_VIEW_ALL_REQUESTS'),
        'description' => Loc::getMessage('PERM_VIEW_ALL_REQUESTS_DESC'),
    ],
    'approve_request' => [
        'title' => Loc::getMessage('PERM_APPROVE_REQUEST'),
        'description' => Loc::getMessage('PERM_APPROVE_REQUEST_DESC'),
    ],
];