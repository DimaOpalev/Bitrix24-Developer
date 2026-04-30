<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\DB\SqlQueryException;

class company_accessrequest extends CModule
{
    public $MODULE_ID = 'company.accessrequest';
    public $MODULE_VERSION = '1.0.0';
    public $MODULE_VERSION_DATE = '2026-04-29';
    public $MODULE_NAME = '';
    public $MODULE_DESCRIPTION = '';
    public $PARTNER_NAME = '';

    public function __construct()
    {
        $this->loadMessages();
        $this->MODULE_NAME = GetMessage('MODULE_NAME') ?: $this->MODULE_NAME;
        $this->MODULE_DESCRIPTION = GetMessage('MODULE_DESCRIPTION') ?: $this->MODULE_DESCRIPTION;
        $this->PARTNER_NAME = GetMessage('PARTNER_NAME') ?: $this->PARTNER_NAME;
    }

    private function loadMessages()
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $this->MODULE_ID . '/lang/ru/install/index.php';
        if (file_exists($path)) {
            include $path;
        }
    }

    public function DoInstall()
    {
        global $APPLICATION;
        if (!Loader::includeModule('main')) {
            $APPLICATION->ThrowException('Модуль main не установлен');
            return false;
        }
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
        $this->InstallFiles();
        return true;
    }

    public function DoUninstall()
    {
        $this->UninstallDB();
        $this->UninstallFiles();
        ModuleManager::unregisterModule($this->MODULE_ID);
        return true;
    }

    public function InstallDB()
    {
        $connection = Application::getConnection();
        $queries = [];

        $queries[] = "CREATE TABLE IF NOT EXISTS access_request (
            ID INT AUTO_INCREMENT NOT NULL,
            REF_CREATE_USER INT NOT NULL,
            REF_DEPARTMENT INT NULL,
            EMPLOYEE_NAME VARCHAR(255) NOT NULL,
            REQUESTED_ACCESS TEXT NULL,
            STATUS INT NOT NULL DEFAULT 0,
            REF_TASK INT NULL,
            CREATED_DATE DATETIME NOT NULL,
            UPDATED_DATE DATETIME NULL,
            PRIMARY KEY (ID),
            INDEX idx_status (STATUS),
            INDEX idx_create_user (REF_CREATE_USER)
        ) ENGINE=InnoDB";

        $queries[] = "CREATE TABLE IF NOT EXISTS access_request_history (
            ID INT AUTO_INCREMENT NOT NULL,
            REF_REQUEST INT NOT NULL,
            USER_DECISION_MAKER INT NOT NULL,
            STATUS INT NOT NULL,
            CREATED_DATE DATETIME NOT NULL,
            COMMENT TEXT NULL,
            PRIMARY KEY (ID),
            INDEX idx_request (REF_REQUEST)
        ) ENGINE=InnoDB";

        foreach ($queries as $query) {
            try {
                $connection->query($query);
            } catch (SqlQueryException $e) {
                AddMessage2Log($e->getMessage(), 'accessrequest');
            }
        }
    }

    public function UninstallDB()
    {
        $connection = Application::getConnection();
        $connection->query("DROP TABLE IF EXISTS access_request_history");
        $connection->query("DROP TABLE IF EXISTS access_request");
    }

    public function InstallFiles()
    {
        // Копирование компонентов (пока нет, добавим позже)
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components/company/',
            true,
            true
        );
    }

    public function UninstallFiles()
    {
        DeleteDirFilesEx('/local/components/company/accessrequest.list');
        DeleteDirFilesEx('/local/components/company/accessrequest.form');
        DeleteDirFilesEx('/local/components/company/accessrequest.detail');
    }
}