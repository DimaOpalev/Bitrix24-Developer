<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Currency\CurrencyTable;
use Bitrix\Main\Context;

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
        
        // Если валюта не выбрана, используем базовую
        if (empty($arParams['LIST_CURRENCY'])) {
            if (Loader::includeModule('currency')) {
                $arParams['LIST_CURRENCY'] = CurrencyManager::getBaseCurrency();
            } else {
                $arParams['LIST_CURRENCY'] = 'RUB';
            }
        }
        
        return $arParams;
    }

    /**
     * Получение информации о полях таблицы Currency
     */
    function getColumn()
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

    /**
     * Получение информации о валюте
     */
    private function getCurrencyInfo($currencyCode)
    {
        $result = [
            'CODE' => $currencyCode,
            'INFO' => null,
            'RATES' => [],
            'ERROR' => false,
            'ERROR_MESSAGE' => ''
        ];
        
        if (!Loader::includeModule('currency')) {
            $result['ERROR'] = true;
            $result['ERROR_MESSAGE'] = 'Модуль "Валюты" не установлен';
            return $result;
        }
        
        try {
            // Получаем основную информацию о валюте
            $currency = CurrencyTable::getList([
                'select' => [
                    'CURRENCY',
                    'AMOUNT',
                    'AMOUNT_CNT',
                    'SORT',
                    'DATE_UPDATE',
                    'NUMCODE',
                    'BASE',
                    'FULL_NAME' => 'CURRENT_LANG_FORMAT.FULL_NAME',
                    'FORMAT_STRING' => 'CURRENT_LANG_FORMAT.FORMAT_STRING',
                    'DEC_POINT' => 'CURRENT_LANG_FORMAT.DEC_POINT',
                    'THOUSANDS_SEP' => 'CURRENT_LANG_FORMAT.THOUSANDS_SEP',
                    'DECIMALS' => 'CURRENT_LANG_FORMAT.DECIMALS',
                ],
                'filter' => ['=CURRENCY' => $currencyCode],
            ])->fetch();
            
            if ($currency) {
                $result['data'] = $currency;
                
                // Получаем курсы других валют к выбранной
                $baseCurrency = CurrencyManager::getBaseCurrency();
                
                if ($currencyCode !== $baseCurrency) {
                    // Курс выбранной валюты к базовой
                    $result['RATES']['TO_BASE'] = $this->getCurrencyRate($currencyCode, $baseCurrency);
                }
                
            } else {
                $result['ERROR'] = true;
                $result['ERROR_MESSAGE'] = 'Валюта не найдена';
            }
        } catch (\Exception $e) {
            $result['ERROR'] = true;
            $result['ERROR_MESSAGE'] = $e->getMessage();
        }
        
        return $result;
    }
    
    
    /**
     * Получение курса валюты
     */
    private function getCurrencyRate($fromCurrency, $toCurrency)
    {
        if ($fromCurrency === $toCurrency) {
            return 1;
        }
        
        // Получаем курс через стандартный механизм Битрикс
        $fromRate = \CCurrency::GetByID($fromCurrency);
        $toRate = \CCurrency::GetByID($toCurrency);
        
        if ($fromRate && $toRate) {
            // Рассчитываем курс: 1 единица fromCurrency = X единиц toCurrency
            if ((float)$toRate['AMOUNT'] > 0) {
                return ((float)$fromRate['AMOUNT'] / (float)$toRate['AMOUNT']);
            }
        }
        
        return null;
    }
    
    /**
     * Получение списка всех доступных валют
     */
    private function getCurrencyList()
    {
        $currencies = [];
        
        if (Loader::includeModule('currency')) {
            $currencyList = CurrencyTable::getList([
                'select' => ['CURRENCY', 'FULL_NAME' => 'LANG.FULL_NAME'],
                'filter' => ['AMOUNT_CNT' => 1],
                'order' => ['SORT' => 'ASC'],
            ]);
            
            while ($currency = $currencyList->fetch()) {
                $currencies[] = [
                    'CODE' => $currency['CURRENCY'],
                    'NAME' => $currency['FULL_NAME'] ?: $currency['CURRENCY'],
                    'SELECTED' => $currency['CURRENCY'] === $this->arParams['LIST_CURRENCY']
                ];
            }
        }
        
        return $currencies;
    }

    private function getAvailableCurrencies()
    {
        $currenciesList = [];
        
        try {
            $currencies = CurrencyTable::getList([
                'select' => ['CURRENCY', 'FULL_NAME' => 'CURRENT_LANG_FORMAT.FULL_NAME'],
                'filter' => ['=AMOUNT_CNT' => 1],
                'order' => ['SORT' => 'ASC', 'CURRENCY' => 'ASC'],
            ]);
            
            while ($currency = $currencies->fetch()) {
                $currenciesList[$currency['CURRENCY']] = $currency['FULL_NAME'] ?: $currency['CURRENCY'];
            }
        } catch (Exception $e) {
            $currenciesList = ['RUB' => 'Российский рубль'];
        }
        
        return $currenciesList;
    }

    private function loadResources()
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
            
            // Путь к Bootstrap в шаблоне (предполагаем стандартную структуру)
            $bootstrapCssPath = '/local/templates/.default/css/bootstrap.min.css';
            $bootstrapJsPath = '/local/templates/.default/js/bootstrap.bundle.min.js';
            
            // Проверяем существование файлов
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . $bootstrapCssPath)) {
                $APPLICATION->SetAdditionalCSS($bootstrapCssPath);
                $APPLICATION->AddHeadScript($bootstrapJsPath);
            } else {
                // Или используем CDN как запасной вариант
                $APPLICATION->SetAdditionalCSS('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
                $APPLICATION->AddHeadScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js');
            }
        }
    }
    
    public function executeComponent()
    {
        $this->loadResources();

        $request = Context::getCurrent()->getRequest();
        
        // Обрабатываем POST запрос
        $selectedCurrency = $request->getPost('selected_currency');
        if ($selectedCurrency && $this->arParams['LIST_CURRENCY'] != $selectedCurrency) {
            // Обновляем параметр через GET параметр для обновления компонента
            LocalRedirect($this->request->getRequestUri() . '?' . http_build_query([
                'currency' => $selectedCurrency
            ]));
        }
        
        // Получаем выбранную валюту из GET параметров
        $getCurrency = $request->get('currency');
        if ($getCurrency) {
            $this->arParams['LIST_CURRENCY'] = $getCurrency;
        }
        
        // Получаем список доступных валют
        $this->arResult['CURRENCIES_LIST'] = $this->getAvailableCurrencies();
        
        // Получаем информацию о выбранной валюте
        $currentCurrency = $this->arParams['LIST_CURRENCY'];
        $this->arResult['CURRENT_CURRENCY'] = $this->getCurrencyInfo($currentCurrency);

        // получаем названия полей таблицы
        $this->arResult['COLUMNS'] = $this->getColumn();

        
        // Подключаем шаблон
        $this->includeComponentTemplate();
    }
}