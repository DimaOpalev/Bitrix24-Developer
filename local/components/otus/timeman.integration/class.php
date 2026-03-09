<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

class OtusTimemanIntegrationComponent extends \CBitrixComponent implements Controllerable, Errorable
{
    protected ErrorCollection $errorCollection;
    
    public function __construct($component = null)
    {
        parent::__construct($component);
        $this->errorCollection = new ErrorCollection();
    }

    public function configureActions(): array
    {
        return [
            'getData' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST, ActionFilter\HttpMethod::METHOD_GET]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'startWorkDay' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'reportUnderwork' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    /**
     * ===== МЕТОД 1: ПОЛУЧЕНИЕ ДАННЫХ О РАБОЧЕМ ДНЕ =====
     * Исправленная версия с правильными методами API
     */
    public function getDataAction(int $userId = 0, string $date = ''): ?array
    {
        if ($userId <= 0) {
            global $USER;
            $userId = $USER->GetID();
        }

        if ($userId <= 0) {
            $this->errorCollection[] = new Error('Не удалось определить пользователя', 'no_user');
            return null;
        }

        if (empty($date)) {
            $date = date('Y-m-d');
        }

        try {
            if (!Loader::includeModule('timeman')) {
                $this->errorCollection[] = new Error('Модуль timeman не установлен', 'no_timeman_module');
                return null;
            }

            // ✅ ПРАВИЛЬНЫЙ СПОСОБ 1: Используем CTimeMan::GetRuntimeInfo()
            $runtimeInfo = \CTimeMan::GetRuntimeInfo($userId);
            
            // Получаем информацию о рабочем дне из runtime
            $dayInfo = $runtimeInfo['INFO'] ?? [];
            $scheduleInfo = $runtimeInfo['SCHEDULE'] ?? [];

            // Формируем результат
            $result = [
                'userId' => $userId,
                'date' => $date,
                'timemanData' => [
                    'status' => $dayInfo['CURRENT_STATUS'] ?? '',
                    'start' => $dayInfo['DATE_START'] ?? null,
                    'end' => $dayInfo['DATE_END'] ?? null,
                    'duration' => (int)($dayInfo['DURATION'] ?? 0),
                    'hours' => $dayInfo['DURATION'] ? round($dayInfo['DURATION'] / 3600, 2) : 0,
                    'paused' => ($dayInfo['PAUSED'] ?? 'N') === 'Y',
                    'pauseTime' => (int)($dayInfo['TIME_PAUSE'] ?? 0),
                    'ipOpen' => $dayInfo['IP_OPEN'] ?? '',
                    'ipClose' => $dayInfo['IP_CLOSE'] ?? '',
                ],
                'schedule' => [
                    'plannedHours' => $scheduleInfo['HOURS'] ?? 8,
                    'startTime' => $this->secondsToTime($scheduleInfo['WORK_TIME_START'] ?? 28800),
                    'endTime' => $this->secondsToTime($scheduleInfo['WORK_TIME_END'] ?? 61200),
                    'workDays' => $scheduleInfo['WORK_DAYS'] ?? '12345',
                    'runtimeInfo' => $runtimeInfo,
                ],
                'user' => $this->getUserData($userId),
            ];

            return $result;

        } catch (\Exception $e) {
            $this->errorCollection[] = new Error('Ошибка: ' . $e->getMessage(), 'exception');
            return null;
        }
    }

    /**
     * ===== МЕТОД 2: НАЧАЛО РАБОЧЕГО ДНЯ =====
     */
    public function startWorkDayAction(int $userId = 0): ?array
    {
        if ($userId <= 0) {
            global $USER;
            $userId = $USER->GetID();
        }

        if (!Loader::includeModule('timeman')) {
            $this->errorCollection[] = new Error('Модуль timeman не установлен', 'no_timeman_module');
            return null;
        }

        try {
            // Создаём объект CTimeMan для пользователя
            $timeman = new \CTimeMan($userId);
            
            // Открываем день
            $result = $timeman->OpenDay([
                'TIME_START' => date('H:i'),
                'REPORT' => 'Начало через OTUS компонент',
                'IP_OPEN' => $_SERVER['REMOTE_ADDR'],
            ]);

            if ($result) {
                $this->logAction('startWorkDay', $userId);
                
                return [
                    'success' => true,
                    'message' => 'Рабочий день начат',
                    'workDayId' => $result,
                ];
            }

            $this->errorCollection[] = new Error('Не удалось начать день', 'start_failed');
            return null;

        } catch (\Exception $e) {
            $this->errorCollection[] = new Error('Ошибка: ' . $e->getMessage(), 'exception');
            return null;
        }
    }

    /**
     * ===== МЕТОД 3: ОТПРАВКА ОТЧЁТА =====
     */
    public function reportUnderworkAction(string $reason = '', int $userId = 0): ?array
    {
        if (empty($reason)) {
            $this->errorCollection[] = new Error('Укажите причину', 'no_reason');
            return null;
        }

        if ($userId <= 0) {
            global $USER;
            $userId = $USER->GetID();
        }

        try {
            // Сохраняем отчёт (пример)
            $reportId = $this->saveReport($userId, $reason);
            
            return [
                'success' => true,
                'message' => 'Отчёт отправлен',
                'reportId' => $reportId,
            ];

        } catch (\Exception $e) {
            $this->errorCollection[] = new Error('Ошибка: ' . $e->getMessage(), 'exception');
            return null;
        }
    }

    /**
     * ===== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ =====
     */

    /**
     * Получение статуса рабочего времени через API
     */
    private function getWorkTimeStatus(int $userId): array
    {
        if (!Loader::includeModule('timeman')) {
            return [];
        }

        // Используем CTimeMan::GetRuntimeInfo() для получения актуального статуса
        $runtime = \CTimeMan::GetRuntimeInfo($userId);
        
        $status = $runtime['INFO']['CURRENT_STATUS'] ?? '';
        $state = $runtime['STATE'] ?? [];
        
        return [
            'status' => $status,
            'state' => $state,
            'isOpen' => $runtime['OPENED'] ?? false,
            'isPaused' => ($runtime['INFO']['PAUSED'] ?? 'N') === 'Y',
        ];
    }

    /**
     * Преобразование статуса в читаемый вид
     */
    private function mapStatus(string $status): string
    {
        $map = [
            'OPENED' => 'opened',
            'CLOSED' => 'closed',
            'PAUSED' => 'paused',
            'EXPIRED' => 'expired',
            'WAITING' => 'waiting',
        ];
        
        return $map[$status] ?? 'unknown';
    }

    /**
     * Получение данных пользователя
     */
    private function getUserData(int $userId): array
    {
        $user = \CUser::GetByID($userId)->Fetch();
        
        return [
            'name' => trim($user['NAME'] . ' ' . $user['LAST_NAME']),
            'email' => $user['EMAIL'] ?? '',
            'workPosition' => $user['WORK_POSITION'] ?? '',
        ];
    }

    /**
     * Сохранение отчёта (заглушка)
     */
    private function saveReport(int $userId, string $reason): int
    {
        return crc32($userId . $reason . time());
    }

    /**
     * Логирование
     */
    private function logAction(string $action, int $userId, array $data = []): void
    {
        $logEntry = sprintf(
            "[%s] %s: User %d, Data: %s\n",
            date('Y-m-d H:i:s'),
            $action,
            $userId,
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/timeman.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Конвертация секунд в время
     */
    private function secondsToTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function executeComponent()
    {
        global $USER;
        $this->arResult['USER_ID'] = $USER->GetID();
        $this->includeComponentTemplate();
    }

    public function getErrors(): array
    {
        return $this->errorCollection->toArray();
    }

    public function getErrorByCode($code)
    {
        return $this->errorCollection->getErrorByCode($code);
    }
}