<?php
return [
    'exception_handling' => [
        'value' => [
            'debug'=> true,
            'handled_errors types' => E_ALL & ~E_WARNING & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE & ~E_DEPRECATED,
            'exception_errors_types' => E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE & ~E_DEPRECATED,
            'ignore_silence' => false,
            'assertion_throws_exception' => true,
            'assertion_error_type' => 256,
            'log' => [
                'class_name' => Otus\Diag\FileExceptionHandlerLogCustom::class, 
                    // пользовательский класс, наследуемый от \ExceptionHandlerLog. Может быть не указан. В этом случае будет использоваться \Bitrix\Main\Diag\FileExceptionHandlerLog.
                // 'required_file' => 'php_interface/src/otus/Diag/FileExceptionHanlderLogCustom.php', // путь к включаемому файлу, содержащему описание класса, от директории local/
                'settings' => [
                    // настройки для класса, указанного в class name
                    'file' => 'local/logs/exceptions.log',
                    'log_size' => 1000000,
                ],
            ],
        ],
        'readonly' => false,
    ]
];
