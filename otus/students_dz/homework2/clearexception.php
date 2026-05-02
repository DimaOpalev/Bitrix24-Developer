<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
// ТУТ ДОБАВИТЬ СВОЮ ФУНКЦИЮ ОЧИСТКИ ЛОГА

use Otus\Diag\FileExceptionHandlerLogCustom;
$logger = new \Otus\Diag\FileExceptionHandlerLogCustom();
$logger->clearLogFile();

LocalRedirect('/otus/students_dz/homework2/');
