<?php
require_once (__DIR__.'/crest.php');
// Убедитесь, что этот URL доступен извне по HTTPS
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$path = str_replace('install.php', 'handler.php', $_SERVER['REQUEST_URI']);

// Получаем полный путь к обработчику динамически
define('HANDLER_URL', $protocol . $domain . $path);

$result = CRest::installApp();

// Если это приложение с интерфейсом (не только REST)
if($result['rest_only'] === false):?>
    <head>
        <script src="//api.bitrix24.com/api/v1/"></script>
        <?php if($result['install'] == true):
            // 1. Регистрация изменения контакта (ИСПРАВЛЕНО)
            $contactEvent = CRest::call('event.bind', [
                'EVENT' => 'ONCRMCONTACTUPDATE', 
                'HANDLER' => HANDLER_URL
            ]);

            // 2. Регистрация добавления комментария
            $commentEvent = CRest::call('event.bind', [
                'EVENT' => 'ONCRMTIMELINECOMMENTADD',
                'HANDLER' => HANDLER_URL
            ]);
            
            // Проверка: успешно ли прошли регистрации
            $isSuccess = ($contactEvent['result'] && $commentEvent['result']);
        ?>
            <?php if($isSuccess): ?>
                <script>
                    BX24.init(function(){
                        // Сообщаем Битриксу, что установка завершена
                        BX24.installFinish();
                    });
                </script>
            <?php endif; ?>
        <?php endif;?>
    </head>
    <body>
        <?php if($result['install'] == true && $isSuccess):?>
            Установка успешно завершена! События зарегистрированы.
        <?php else:?>
            Ошибка при установке. 
            <pre><?php print_r($contactEvent); print_r($commentEvent); ?></pre>
        <?php endif;?>
    </body>
<?php endif;
