<?php
require_once (__DIR__.'/crest.php');

CRest::checkServer();

$check = CRest::call('crm.timeline.item.fields', []); 
print_r($check);