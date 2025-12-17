<? use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("Список Домашних работ");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>
    <style>
        h2 {
            font-size: 15px;
            display: block;
            padding: 5px;
            border-bottom: 1px dashed #ccc;
        }

        .item-list {
            list-style: none;
        }

        .item .icon {
            display: inline-block;
            width: 15px;
            height: 15px;
            background-size: cover;
        }

        .done.item .icon {
            background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgZmlsbD0iZ3JlZW4iIGNsYXNzPSJiaSBiaS1jaGVjay1jaXJjbGUtZmlsbCIgdmlld0JveD0iMCAwIDE2IDE2Ij4KICAgICAgICA8cGF0aCBkPSJNMTYgOEE4IDggMCAxIDEgMCA4YTggOCAwIDAgMSAxNiAwbS0zLjk3LTMuMDNhLjc1Ljc1IDAgMCAwLTEuMDguMDIyTDcuNDc3IDkuNDE3IDUuMzg0IDcuMzIzYS43NS43NSAwIDAgMC0xLjA2IDEuMDZMNi45NyAxMS4wM2EuNzUuNzUgMCAwIDAgMS4wNzktLjAybDMuOTkyLTQuOTlhLjc1Ljc1IDAgMCAwLS4wMS0xLjA1eiIvPgogICAgICAgIDwvc3ZnPg==);
        }

        .clock.item .icon {
            background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgZmlsbD0iI2Q1NzIxMSIgY2xhc3M9ImJpIGJpLWFsYXJtLWZpbGwiIHZpZXdCb3g9IjAgMCAxNiAxNiI+CiAgICAgICAgPHBhdGggZD0iTTYgLjVhLjUuNSAwIDAgMSAuNS0uNWgzYS41LjUgMCAwIDEgMCAxSDl2MS4wN2E3LjAwMSA3LjAwMSAwIDAgMSAzLjI3NCAxMi40NzRsLjYwMS42MDJhLjUuNSAwIDAgMS0uNzA3LjcwOGwtLjc0Ni0uNzQ2QTYuOTcgNi45NyAwIDAgMSA4IDE2YTYuOTcgNi45NyAwIDAgMS0zLjQyMi0uODkybC0uNzQ2Ljc0NmEuNS41IDAgMCAxLS43MDctLjcwOGwuNjAyLS42MDJBNy4wMDEgNy4wMDEgMCAwIDEgNyAyLjA3VjFoLS41QS41LjUgMCAwIDEgNiAuNW0yLjUgNWEuNS41IDAgMCAwLTEgMHYzLjM2MmwtMS40MjkgMi4zOGEuNS41IDAgMSAwIC44NTguNTE1bDEuNS0yLjVBLjUuNSAwIDAgMCA4LjUgOXpNLjg2IDUuMzg3QTIuNSAyLjUgMCAxIDEgNC4zODcgMS44NiA4LjA0IDguMDQgMCAwIDAgLjg2IDUuMzg3TTExLjYxMyAxLjg2YTIuNSAyLjUgMCAxIDEgMy41MjcgMy41MjcgOC4wNCA4LjA0IDAgMCAwLTMuNTI3LTMuNTI3Ii8+CiAgICA8L3N2Zz4=);
        }

    </style>

    <section class="container-fluid">

        <h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>
        <div class="mb-3">Репозиторий: <a href="https://github.com/">https://github.com/</a> указать URL своего репозитория</div>
        <ul class="item-list">
            <li>
                <h2 class="item done">
                    <a href="homework1/">ДЗ #1: Создание и настройка проекта в VScode</a>
                    <i class="icon"></i>
                </h2>
            </li>
            <li>
                <h2 class="item clock"><a href="homework2/">ДЗ #2: Отладка и логирование</a>
                    <i class="icon"></i>
                </h2>
            </li>
            <li>
                <h2 class="item proc"><a href="homework3/">ДЗ #3: Связывание моделей</a>
                    <i class="icon"></i>
                </h2>
            </li>
            <li>
                <h2 class="item"><a href="homework4/">ДЗ #4: Создание своих таблиц БД и написание модели данных к ним</a>
                    <i class="icon"></i>
                </h2>
            </li>
            <li>
                <h2 class="item"><a href="homework5/">ДЗ #5: Компонент списка таблицы БД</a>
                    <i class="icon"></i>
                </h2>
            </li>
            <li>
                <h2 class="item"><a href="homework6/">ДЗ #6: Написание своего модуля</a>
                    <i class="icon"></i>
                </h2>
            </li>
            <li>
                <h2 class="item"><a href="homework7/">ДЗ #7: Создание кастомных полей и встраивание их в систему - в процессе</a>
                    <div class="prog proc"></div>
                </h2>
            </li>
            <li>
                <h2 class="item"><a href="homework8/">ДЗ #8: Учимся подключать свои скрипты, взаимодействовать с компонентами из фронтенда</a>
                    <i class="icon"></i>
                </h2>
            </li>
            <li>
                <h2 class="item"><a href="homework9/">ДЗ #9: Написание своих активити для БП - на проверке! </a>
                    <i class="icon"></i>
                </h2>
            </li>
            <li>
                <h2 class="item"><a href="homework10/">ДЗ #10: Обработка событий </a>
                    <i class="icon"></i>
                </h2>
            </li>
            <li>
                <h2 class="item"><a href="homework11/">ДЗ #11: Локальное REST приложение дата последней коммуникации </a>
                    <i class="icon"></i>
                </h2>
            </li>
            <li>
                <h2 class="item"><a href="homework12/">ДЗ #12: Собственные обработчики REST </a>
                    <i class="icon"></i>
                </h2>
            </li>
        </ul>
    </section>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>