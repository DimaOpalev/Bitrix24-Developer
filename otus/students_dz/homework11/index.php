<?php
$logFile = __DIR__ . '/handler.log';

file_put_contents($logFile,
	date('Y-m-d H:i:s') . '\n' . 
	print_r($_POST, true) . "\n" .
	FILE_APPEND | LOCK_EX
);

require_once (__DIR__.'/crest.php');

$result = CRest::call('profile');

echo '<pre>';
	print_r($result);
echo '</pre>';

const HANDLER_URL = "https://cv174341.tw1.ru/otus/students_dz/homework11/handler.php";

/*
$events = CRest::call('event.get')['result'];
foreach ($events as $event) {
    CRest::call('event.unbind', [
        'EVENT' => $event['event'],
        'HANDLER' => $event['handler']
    ]);
}
echo "Все события удалены. Теперь запустите установщик один раз.";
*/

/*
$activityEvent = CRest::call('event.bind', [
    'EVENT' => 'OnCrmActivityAdd',
    'HANDLER' => 'https://cv174341.tw1.ru/otus/students_dz/homework11/handler.php'
]);
*/

$result = CRest::call('event.get', []);
print_r($result);


/*
$contact = CRest::call('crm.contact.get', ['ID' => 1]);
var_dump($contact);
*/

/*

            // 1. Регистрация изменения контакта (ИСПРАВЛЕНО)
            $contactEvent = CRest::call('event.bind', [
                'EVENT' => 'ONCRMCONTACTUPDATE', 
                'HANDLER' => HANDLER_URL
            ]);
*/
/*
            // 2. Регистрация добавления комментария
            $commentEvent = CRest::call('event.bind', [
                'EVENT' => 'ONCRMTIMELINECOMMENTADD',
                'HANDLER' => HANDLER_URL
            ]);

var_dump($commentEvent);
*/
