<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Currency\CurrencyTable;
use Bitrix\Main\Context;
use Bitrix\Main\Page\Asset;


class CurrencyRatesComponent extends CBitrixComponent
{
    /**
     * Подготовка параметров компонента
     */
    public function onPrepareComponentParams($arParams)
    {
        // Приводим параметры к нужному типу
        $arParams['LIST_CURRENCY'] = trim($arParams['LIST_CURRENCY'] ?? '');
        $arParams['CACHE_TIME'] = (int) ($arParams['CACHE_TIME'] ?? 3600);
        return $arParams;
    }

    /**
     * Получение списка всех доступных валют
     */
    private function getCurrenciesList(): array
    {
        $list = [];

        try {
            Loader::includeModule('currency');

            $currencies = CurrencyTable::getList([
                'select' => ['CURRENCY', 'FULL_NAME' => 'CURRENT_LANG_FORMAT.FULL_NAME'],
                'order' => ['CURRENCY' => 'ASC']
            ]);

            while ($currency = $currencies->fetch()) {
                $code = $currency['CURRENCY'];
                $name = $currency['FULL_NAME'] ?: $code;
                $list[$code] = $name;
            }
        } catch (Exception $e) {
            // Заглушка на случай ошибки
            $list = [
                'RUB' => 'Российский рубль',
            ];
        }

        return $list;
    }

    /*
     * проверка загруженного bootstrap
     * */
    private function loadResources(): void
    {
        global $APPLICATION;
        
        // Проверяем, загружен ли Bootstrap
        $isBootstrapLoaded = false;
        
        // Проверяем через REGISTERED_STYLES
        $registeredStyles = $APPLICATION->sPath2css;
        if (is_array($registeredStyles)) {
            foreach ($registeredStyles as $style) {
                if (strpos($style, 'bootstrap') !== false) {
                    $isBootstrapLoaded = true;
                    break;
                }
            }
        }
        
        // Если Bootstrap не загружен, загружаем из шаблона
        if (!$isBootstrapLoaded) {
            $componentPath = $this->GetPath();

            // Путь к Bootstrap в шаблоне (предполагаем стандартную структуру)
            $bootstrapCssPath = $componentPath.'/templates/css/bootstrap.min.css';
            $bootstrapJsPath = $componentPath.'/templates/js/bootstrap.bundle.min.js';

            // Проверяем существование файлов
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $bootstrapCssPath)) {
                Asset::getInstance()->addCss($bootstrapCssPath);
                Asset::getInstance()->addJs($bootstrapJsPath);
            } else {
                // Или используем CDN как запасной вариант
                Asset::getInstance()->addCss('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
                Asset::getInstance()->addJs('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js');
            }
        }
    }

    /*
     * Получение информации по выбранной валюте
     * */
    private function getCurrencyDataForGrid($currencyCode): ?array
    {
        try {
            Loader::includeModule('currency');

            $currency = CurrencyTable::getList([
                'select' => [
                    'CURRENCY',
                    'AMOUNT',
                    'AMOUNT_CNT',
                    'SORT',
                    'NUMCODE',
                    'BASE',
                    'DATE_UPDATE',
                    'MODIFIED_BY',
                    'DATE_CREATE',
                    'CREATED_BY',
                    'FULL_NAME' => 'CURRENT_LANG_FORMAT.FULL_NAME',
                    'FORMAT_STRING' => 'CURRENT_LANG_FORMAT.FORMAT_STRING',
                    'DEC_POINT' => 'CURRENT_LANG_FORMAT.DEC_POINT',
                    'THOUSANDS_SEP' => 'CURRENT_LANG_FORMAT.THOUSANDS_SEP',
                    'DECIMALS' => 'CURRENT_LANG_FORMAT.DECIMALS',
                ],
                'filter' => ['=CURRENCY' => $currencyCode],
            ])->fetch();

            if ($currency) {
                // Преобразуем значения для отображения
                $currency['AMOUNT'] = number_format($currency['AMOUNT'], 4);
                $currency['BASE'] = $currency['BASE'] === 'Y' ? 'Да' : 'Нет';

                // Формируем строку для таблицы
                return [
                    'id' => $currency['CURRENCY'],
                    'columns' => $currency,
                    'actions' => [],
                    'editable' => false
                ];
            }

        } catch (Exception $e) {
            // Обработка ошибки
        }

        return null;
    }

    /*
     * Получение списка колонок
     * */
    private function getGridColumns(): array
    {
        $fieldMap = CurrencyTable::getMap();
        $columns = [];
        foreach ($fieldMap as $key => $field) {
            $columns[] = array(
                'id' => $field->getName(),
                'name' => $field->getTitle()
            );
        }
        return $columns;
    }

    /*
     * Формирование компонента
     * */
    public function executeComponent(): void
    {
        $this->loadResources();

        $request = Context::getCurrent()->getRequest();
        
        // Получаем выбранную валюту из GET параметров
        $getCurrency = $request->get('currency');
        if ($getCurrency) {
            $this->arParams['LIST_CURRENCY'] = $getCurrency;
        }
        
        // Получаем список доступных валют
        $this->arResult['CURRENCIES_LIST'] = $this->getCurrenciesList();
        $this->arResult['GRID_DATA'] = $this->getCurrencyDataForGrid($getCurrency);
        $this->arResult['GRID_COLUMNS'] = $this->getGridColumns();
        $this->arResult['CURRENT_CURRENCY'] = $this->arParams['LIST_CURRENCY'];

        // Подключаем шаблон
        $this->includeComponentTemplate();
    }
}