<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

//use Local\Lib\OtusLogger;
use Local\App\Debug\Log;
Log::writeToLog();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
?>