<?php
if(file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once(__DIR__ . '/../../vendor/autoload.php');
}

//require_once $_SERVER['DOCUMENT_ROOT'] . '/local/lib/otuslogger.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/App/Debug/Log.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/src/Otus/Diag/FileExceptionHanlderLogCustom.php';


?>