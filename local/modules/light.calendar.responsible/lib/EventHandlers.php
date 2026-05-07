<?php
namespace Light\Calendar\Responsible;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use CCalendarEvent;
use Bitrix\Main\Diag\Debug;


class EventHandlers
{
    private static $isProcessing = false;

    /**
     * Обработчик события OnAfterCalendarEventEdit.
     * Назначает ответственного за встречу, если она создаётся в указанном календаре.
     *
     * @param array $arFields Поля события.
     */
    public static function onAfterCalendarEventEditHandler($arFields): void
    {
        if (!Loader::includeModule('calendar')) {
            // Если модуль не подключился, лучше прервать выполнение, чтобы не было ошибок
            Debug::writeToFile('Модуль calendar не найден!', '[calendar.responsible] ERROR', '/local/logs/calendar_responsible.log');
            return;
        }

        // 2. Теперь можно безопасно работать с CCalendarEvent
        if (self::$isProcessing) {
            return;
        }

        Debug::writeToFile(
            [
                'arFields' => $arFields,
            ],
            '[calendar.responsible] Successfully updated event',
            '/local/logs/calendar_responsible.log'
        );



        // ID целевого календаря (можно вынести в настройки при желании)
        $targetCalendarId = (int)Option::get('light.calendar.responsible', 'responsible_calendar_id');
        // Получаем ID ответственного из настроек модуля
        $responsibleUserId = (int)Option::get('light.calendar.responsible', 'responsible_user_id');

        // Проверяем, что ID ответственного задан, событие создаётся в компании и в нужном календаре
        if ($responsibleUserId > 0
            && $arFields['SECTION_ID'] == $targetCalendarId
        ) {

            $currentAttendees = $arFields['ATTENDEES_CODES'] ?? [];
            $attendeeCode = 'U'.$responsibleUserId;

            if (!in_array($attendeeCode, $currentAttendees)) {
                // Добавляем нового участника
                $currentAttendees[] = $attendeeCode;

                self::$isProcessing = true;

                // Обновляем событие
                $updateResult = CCalendarEvent::Edit([
                    'arFields' => [
                        'ID'                => $arFields['ID'],
                        'CAL_TYPE'          => $arFields['CAL_TYPE'],
                        'OWNER_ID'          => $arFields['OWNER_ID'],
                        'SECTION_ID'        => $arFields['SECTION_ID'],
                        'NAME'              => $arFields['NAME'],
                        'DESCRIPTION'       => $arFields['DESCRIPTION'], // ПЕРЕДАЕМ ОПИСАНИЕ
                        'ATTENDEES_CODES'   => $currentAttendees,
                        'LOCATION'          => $arFields['LOCATION'],
                        'REMIND'            => $arFields['REMIND'],      // ПЕРЕДАЕМ НАПОМИНАНИЯ
                        'IS_MEETING'        => true,
                        'MEETING' => array_merge($arFields['MEETING'], [
                            'REINVITE' => true,
                        ]),
                    ],
                    'sendInvitations'        => true, // Глобальный флаг отправки приглашений
                    'checkLocationOccupancy' => false, // Чтобы не ругалось на занятость той же переговорки
                    'fromWebservice' => true // Помогает избежать лишних проверок прав

                ],
                );
            }
        }
    }
}