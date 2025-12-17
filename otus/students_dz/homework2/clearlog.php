<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
use Local\App\Debug\Log;

// СВОЯ ФУНКЦИЯ ОЧИСТКИ ЛОГА
Log::clearLogFile();

LocalRedirect('/otus/students_dz/homework2/');
