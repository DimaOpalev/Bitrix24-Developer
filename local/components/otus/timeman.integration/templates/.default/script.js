(function() {
    'use strict';

    /**
     * Интеграция с таймменом через события
     * Версия с использованием class (ES6)
     */
    class OtusTimemanIntegration {
        constructor() {
            this.container = null;
            this.workDayStarted = false;
            this.workDayData = null;
            this.modalShown = false;
            
            // Автоматически вызываем init при создании экземпляра
            this.init();
        }

        init() {
            console.log('OtusTimemanIntegration: init');
            
            // Создаём контейнер, если его нет
            this.container = BX('otus-timeman-container');
            if (!this.container) {
                this.container = BX.create('div', {
                    props: { id: 'otus-timeman-container' },
                    style: { display: 'none' }
                });
                document.body.appendChild(this.container);
            }

            // Подписываемся на все события тайммена
            this.subscribeToEvents();
            this.getDataAction();
        }

        getDataAction() {
            // В классе можно использовать стрелочные функции для сохранения контекста
            BX.ajax.runComponentAction('otus:timeman.integration', 'getData', {
                mode: 'class',
                data: {}
            }).then((response) => {
                if (typeof(response.data.schedule?.runtimeInfo) !== "undefined") {
                    this.workDayData = response.data.schedule.runtimeInfo;
                    this.checkWorkDayStatus(this.workDayData);
                    
                    console.log('Response:', typeof(response.data.schedule.runtimeInfo1));
                    console.log('Успех:', response.data);
                    console.log('Статус:', response.status);
                    console.log('Ошибки:', response.errors);
                }
            }).catch((response) => {
                console.error('Ошибка:', response.errors);
            });
        }

        subscribeToEvents() {
            // В классе можно использовать bind или стрелочные функции
            BX.addCustomEvent('onTimeManDataRecieved', this.onTimeManDataRecieved.bind(this));
            BX.addCustomEvent('onPlannerDataRecieved', this.onPlannerDataRecieved.bind(this));
            BX.addCustomEvent('onTimeManNeedRebuild', this.onTimeManNeedRebuild.bind(this));
            BX.addCustomEvent('onTopPanelCollapse', this.onTopPanelCollapse.bind(this));
            
            BX.addCustomEvent('onTimeManWindowBuild', this.onTimeManWindowBuild.bind(this));
            BX.addCustomEvent('onTimemanInit', this.onTimemanInit.bind(this));
            BX.addCustomEvent('onTimeManWindowBind', this.onTimeManWindowBind.bind(this));
            
            console.log('OtusTimemanIntegration: подписались на события');
        }

        // ===== ОСНОВНАЯ ЛОГИКА ПРОВЕРКИ РАБОЧЕГО ДНЯ =====
        onTimeManDataRecieved(data) {
            console.log('onTimeManDataRecieved:', data);
            
            this.workDayData = data;
            this.checkWorkDayStatus(data);
        }

        checkWorkDayStatus(data) {
            const info = data.INFO || {};
            const schedule = data.SCHEDULE || {};
            
            const status = info.CURRENT_STATUS;
            const dateStart = info.DATE_START;
            const dateEnd = info.DATE_FINISH;
            const plannedHours = schedule.HOURS || 8;
            
            console.log('Статус рабочего дня:', status);
            console.log('Плановых часов:', plannedHours);
            
            // Случай 1: Рабочий день ещё не начался
            if (status === 'CLOSED' && !dateStart) {
                console.log('Рабочий день не начат — показываем модалку');
                this.createBitrixModal('start');
                this.modalShown = true;
            }
            
            // Случай 2: Рабочий день закончен, но отработано меньше плана
            if ((status === 'CLOSED' || status === 'PAUSED') && dateEnd) {
                const workedSeconds = dateEnd - dateStart;
                const workedHours = workedSeconds / 3600;
                
                console.log('Отработано часов:', workedHours.toFixed(2));
                
                if (workedHours < plannedHours) {
                    console.log('Отработано меньше плана — показываем модалку');
                    this.createBitrixModal('underworked', {
                        worked: workedHours.toFixed(2),
                        planned: plannedHours
                    });
                    this.modalShown = true;
                }
            }
        }

        // ===== СОЗДАНИЕ МОДАЛЬНОГО ОКНА =====
        createBitrixModal(type, data = {}) {
            console.log('Создаём модальное окно, тип:', type);
            
            let title = '';
            let content = '';
            let buttonText = '';
            
            if (type === 'start') {
                title = 'Начало рабочего дня';
                content = `
                    <div class="content-popup-window">
                        <h3 class="content-popup-window-header">Доброе утро! ☀️</h3>
                        <div class="content-popup-window-body">
                            <p>Желаем вам продуктивного рабочего дня!</p>
                            <p style="font-weight: bold; margin-top: 15px;">Не забудьте проверить:</p>
                            <ul style="margin-left: 20px; margin-top: 5px;">
                                <li>📋 Планы на сегодня</li>
                                <li>📅 Запланированные встречи</li>
                                <li>⚡ Срочные задачи</li>
                            </ul>
                        </div>
                    </div>
                `;
                buttonText = 'Начать рабочий день';
            } 
            else if (type === 'underworked') {
                title = 'Завершение рабочего дня';
                content = `
                    <div class="content-popup-window">
                        <h3 class="content-popup-window-header">Рабочий день завершён</h3>
                        <div class="content-popup-window-body">
                            <p>Вы отработали <strong>${data.worked} часов</strong> из ${data.planned} запланированных.</p>
                            <p style="color: #e67e22; margin-top: 15px;">⏰ У вас недоработка!</p>
                            <p>Пожалуйста, укажите причину:</p>
                            <textarea id="underwork-reason" style="width: 100%; height: 80px; margin-top: 10px; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Опишите причину..."></textarea>
                        </div>
                    </div>
                `;
                buttonText = 'Отправить отчёт';
            }

            let modal = BX.PopupWindowManager.getCurrentPopup();
            if (modal) {
                modal.close();
            }

            modal = BX.PopupWindowManager.create(
                'workday-modal-' + type,
                null,
                {
                    content: content,
                    titleBar: title,
                    width: 450,
                    closeIcon: true,
                    closeByEsc: true,
                    autoHide: false,
                    draggable: true,
                    resizable: false,
                    lightShadow: true,
                    angle: false,
                    
                    overlay: {
                        backgroundColor: 'black',
                        opacity: 50
                    },
                    
                    buttons: [
                        new BX.PopupWindowButton({
                            text: buttonText,
                            className: 'popup-window-button-accept',
                            events: {
                                click: () => {
                                    if (type === 'start') {
                                        this.startWorkDay();
                                    } else if (type === 'underworked') {
                                        const reason = BX('underwork-reason')?.value || 'Не указано';
                                        this.reportUnderwork(reason);
                                    }
                                    this.popupWindow.close();
                                }
                            }
                        }),
                        new BX.PopupWindowButton({
                            text: 'Закрыть',
                            className: 'popup-window-button-link',
                            events: {
                                click: function() {
                                    console.log('Модальное окно закрыто');
                                    this.popupWindow.close();
                                }
                            }
                        })
                    ]
                }
            );

            modal.show();
        }

        // ===== ДЕЙСТВИЯ С РАБОЧИМ ДНЁМ =====
        startWorkDay() {
            console.log('Начинаем рабочий день...');
            
            BX.ajax.runAction('timeman.api.worktime.open', {
                data: {}
            }).then((response) => {
                console.log('Рабочий день успешно начат:', response);
                BX.UI.Notification.Center.notify({
                    content: 'Рабочий день начат! Удачи!'
                });
            }, (response) => {
                console.error('Ошибка при начале рабочего дня:', response);
                BX.UI.Notification.Center.notify({
                    content: 'Не удалось начать рабочий день',
                    autoHideDelay: 5000
                });
            });
        }

        reportUnderwork(reason) {
            console.log('Отправляем отчёт о недоработке:', reason);
            
            BX.ajax.runAction('otus:timeman.api.reportUnderwork', {
                data: {
                    reason: reason,
                    date: new Date().toISOString().split('T')[0]
                }
            }).then((response) => {
                console.log('Отчёт отправлен:', response);
                BX.UI.Notification.Center.notify({
                    content: 'Спасибо! Отчёт отправлен.'
                });
            }, (response) => {
                console.error('Ошибка при отправке отчёта:', response);
                BX.UI.Notification.Center.notify({
                    content: 'Не удалось отправить отчёт',
                    autoHideDelay: 5000
                });
            });
        }

        // Обработчики остальных событий
        onPlannerDataRecieved(data) {
            console.log('onPlannerDataRecieved:', data);
        }

        onTimeManNeedRebuild(data) {
            console.log('onTimeManNeedRebuild');
        }

        onTopPanelCollapse(collapsed) {
            console.log('onTopPanelCollapse:', collapsed);
        }

        onTimeManWindowBuild(params) {
            console.log('onTimeManWindowBuild:', params);
        }

        onTimemanInit(data) {
            console.log('onTimemanInit:', data);
        }

        onTimeManWindowBind(window) {
            console.log('onTimeManWindowBind:', window);
        }

        updateUI(data) {
            console.log('updateUI:', data);
        }
    }

    // Запускаем после загрузки DOM
    BX.ready(() => {
        new OtusTimemanIntegration();
    });

})();