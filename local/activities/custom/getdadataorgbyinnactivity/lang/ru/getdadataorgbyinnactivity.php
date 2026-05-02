<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$MESS['HELLOWORLD_ACTIVITY_TARGET'] = 'Обращение (кому/чему)';
$MESS['GETDADATA_ACTIVITY_INN'] = 'ИНН организации';

$MESS['HELLOWORLD_ACTIVITY_MESSAGE'] = 'Текст сообщения';

// ===== ОШИБКИ ВАЛИДАЦИИ =====
$MESS['GETDADATA_ACTIVITY_ERROR_EMPTY_INN'] = 'Не указан ИНН организации';
$MESS['GETDADATA_ACTIVITY_ERROR_INN_FORMAT'] = 'Неверный формат ИНН. Должно быть 10 или 12 цифр';

$MESS['GETDADATA_ACTIVITY_API_KEY'] = 'API ключ DaData';
$MESS['GETDADATA_ACTIVITY_API_KEY_HINT'] = 'Получите на dadata.ru/profile#info';

$MESS['GETDADATA_ACTIVITY_SECRET_KEY'] = 'Секретный ключ DaData';
$MESS['GETDADATA_ACTIVITY_SECRET_KEY_HINT'] = 'Секретный ключ (необязательно, нужен для некоторых методов API)';

// ===== ОШИБКИ API =====
$MESS['GETDADATA_ACTIVITY_ERROR_NOT_FOUND'] = 'Организация с ИНН #INN# не найдена';
