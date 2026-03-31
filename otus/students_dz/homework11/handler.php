<?php
require_once('crest.php');

// Битрикс присылает данные в массиве $_POST
$event = $_POST['event'];
$data = $_POST['data']['FIELDS'];

if ($event == 'ONCRMCONTACTUPDATE') {
    $contactId = $data['ID'];
    // Получаем подробности об измененном контакте
    $contact = CRest::call('crm.contact.get', ['ID' => $contactId]);
    // Ваша логика здесь...
}

if ($event == 'ONCRMTIMELINECOMMENTADD') {
    $commentId = $data['ID'];
    // Получаем текст и автора комментария
    $comment = CRest::call('crm.timeline.comment.get', ['ID' => $commentId]);
    // Ваша логика здесь...
}
?>