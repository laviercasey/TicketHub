INSERT IGNORE INTO `%TABLE_PREFIX%staff`
  (`staff_id`,`group_id`,`dept_id`,`username`,`firstname`,`lastname`,`passwd`,`email`,`phone`,`mobile`,`signature`,`isactive`,`isadmin`,`isvisible`,`onvacation`,`daylight_saving`,`append_signature`,`change_passwd`,`timezone_offset`,`max_page_size`,`created`,`lastlogin`)
VALUES
  (2, 2, 1, 'ivanov',  'Иван',    'Иванов',  '%STAFF_PASSWD%', 'ivanov@tickethub.local',  '+7-495-001-0002', '+7-900-100-0002', 'С уважением, Иван Иванов\nРуководитель тех. отдела',  1, 0, 1, 0, 0, 1, 0, 3.0, 25, DATE_SUB(NOW(), INTERVAL 90 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (3, 3, 1, 'petrova', 'Анна',    'Петрова',  '%STAFF_PASSWD%', 'petrova@tickethub.local',  '+7-495-001-0003', '+7-900-100-0003', 'С уважением, Анна Петрова',                           1, 0, 1, 0, 0, 1, 0, 3.0, 25, DATE_SUB(NOW(), INTERVAL 80 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (4, 3, 2, 'sidorov', 'Пётр',    'Сидоров',  '%STAFF_PASSWD%', 'sidorov@tickethub.local',  '+7-495-001-0004', '+7-900-100-0004', 'С уважением, Пётр Сидоров',                           1, 0, 1, 0, 0, 1, 0, 3.0, 25, DATE_SUB(NOW(), INTERVAL 75 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (5, 3, 1, 'kozlova', 'Мария',   'Козлова',  '%STAFF_PASSWD%', 'kozlova@tickethub.local',  '+7-495-001-0005', '+7-900-100-0005', 'С уважением, Мария Козлова',                           1, 0, 1, 0, 0, 1, 0, 3.0, 25, DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (6, 2, 2, 'volkov',  'Дмитрий', 'Волков',   '%STAFF_PASSWD%', 'volkov@tickethub.local',   '+7-495-001-0006', '+7-900-100-0006', 'С уважением, Дмитрий Волков\nРуководитель отдела продаж', 1, 0, 1, 0, 0, 1, 0, 3.0, 25, DATE_SUB(NOW(), INTERVAL 70 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY));

UPDATE `%TABLE_PREFIX%department` SET `manager_id`=2 WHERE `dept_id`=1;
UPDATE `%TABLE_PREFIX%department` SET `manager_id`=6 WHERE `dept_id`=2;
INSERT IGNORE INTO `%TABLE_PREFIX%department`
  (`dept_id`,`tpl_id`,`email_id`,`autoresp_email_id`,`manager_id`,`dept_name`,`dept_signature`,`ispublic`,`ticket_auto_response`,`message_auto_response`,`can_append_signature`,`updated`,`created`)
VALUES
  (3, 0, 1, 0, 0, 'Администрация', 'С уважением, Администрация', 0, 0, 0, 1, NOW(), NOW());
INSERT IGNORE INTO `%TABLE_PREFIX%help_topic`
  (`topic_id`,`isactive`,`noautoresp`,`priority_id`,`dept_id`,`topic`,`created`,`updated`)
VALUES
  (3, 1, 0, 2, 1, 'Установка ПО',          NOW(), NOW()),
  (4, 1, 0, 1, 1, 'Сбой оборудования',     NOW(), NOW()),
  (5, 1, 0, 3, 2, 'Запрос на обслуживание', NOW(), NOW()),
  (6, 1, 0, 2, 1, 'Настройка сети',         NOW(), NOW()),
  (7, 1, 0, 2, 2, 'Вопрос по лицензиям',   NOW(), NOW());
INSERT INTO `%TABLE_PREFIX%ticket`
  (`ticket_id`,`ticketID`,`dept_id`,`priority_id`,`topic_id`,`staff_id`,`andstaffs_id`,`email`,`name`,`subject`,`helptopic`,`phone`,`ip_address`,`status`,`source`,`isoverdue`,`isanswered`,`duedate`,`reopened`,`closed`,`lastmessage`,`lastresponse`,`created`,`updated`)
VALUES
  -- Открытые заявки
  (2,  384719, 1, 3, 1, 2, NULL,
   'user1@company.ru', 'Алексей Смирнов', 'Не работает 1С после обновления', 'Техническая проблема',
   '+7-495-200-0001', '192.168.1.10', 'open', 'Web', 0, 0,
   DATE_ADD(NOW(), INTERVAL 2 DAY), NULL, NULL,
   DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR),
   DATE_SUB(NOW(), INTERVAL 5 HOUR), NOW()),

  (3,  294057, 1, 4, 4, 3, '5',
   'user2@company.ru', 'Ольга Кузнецова', 'Критический сбой сервера печати', 'Сбой оборудования',
   '+7-495-200-0002', '192.168.1.22', 'open', 'Email', 1, 0,
   DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, NULL,
   DATE_SUB(NOW(), INTERVAL 2 HOUR), NULL,
   DATE_SUB(NOW(), INTERVAL 26 HOUR), NOW()),

  (4,  518432, 1, 2, 3, 0, NULL,
   'user3@company.ru', 'Дмитрий Попов', 'Установить Adobe Acrobat Pro', 'Установка ПО',
   '+7-495-200-0003', '192.168.1.35', 'open', 'Web', 0, 0,
   DATE_ADD(NOW(), INTERVAL 5 DAY), NULL, NULL,
   DATE_SUB(NOW(), INTERVAL 12 HOUR), NULL,
   DATE_SUB(NOW(), INTERVAL 12 HOUR), NOW()),

  (5,  673201, 2, 2, 5, 4, NULL,
   'user4@company.ru', 'Елена Соколова', 'Заказ картриджей для HP LaserJet', 'Запрос на обслуживание',
   '+7-495-200-0004', '192.168.1.48', 'open', 'Phone', 0, 1,
   DATE_ADD(NOW(), INTERVAL 3 DAY), NULL, NULL,
   DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 20 HOUR),
   DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),

  (6,  891245, 1, 3, 6, 5, NULL,
   'user5@company.ru', 'Сергей Лебедев', 'VPN не подключается из дома', 'Настройка сети',
   '+7-495-200-0005', '10.0.0.50', 'open', 'Web', 0, 0,
   DATE_ADD(NOW(), INTERVAL 1 DAY), NULL, NULL,
   DATE_SUB(NOW(), INTERVAL 45 MINUTE), NULL,
   DATE_SUB(NOW(), INTERVAL 45 MINUTE), NOW()),

  (7,  102384, 1, 2, 1, 2, '3',
   'user6@company.ru', 'Наталья Новикова', 'Outlook не синхронизирует почту', 'Техническая проблема',
   '+7-495-200-0006', '192.168.1.67', 'open', 'Email', 0, 1,
   DATE_ADD(NOW(), INTERVAL 4 DAY), NULL, NULL,
   DATE_SUB(NOW(), INTERVAL 6 HOUR), DATE_SUB(NOW(), INTERVAL 4 HOUR),
   DATE_SUB(NOW(), INTERVAL 8 HOUR), NOW()),

  (8,  445678, 2, 1, 7, 0, NULL,
   'user7@company.ru', 'Андрей Козлов', 'Продление лицензии MS Office', 'Вопрос по лицензиям',
   '+7-495-200-0007', '192.168.1.80', 'open', 'Web', 0, 0,
   NULL, NULL, NULL,
   DATE_SUB(NOW(), INTERVAL 30 MINUTE), NULL,
   DATE_SUB(NOW(), INTERVAL 30 MINUTE), NOW()),

  -- Закрытые (решённые) заявки
  (9,  557891, 1, 2, 1, 3, NULL,
   'user8@company.ru', 'Марина Федорова', 'Не открывается Excel файл', 'Техническая проблема',
   '+7-495-200-0008', '192.168.1.91', 'closed', 'Web', 0, 1,
   NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY),
   DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY),
   DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),

  (10, 663012, 1, 3, 4, 2, NULL,
   'user9@company.ru', 'Игорь Морозов', 'Не включается компьютер', 'Сбой оборудования',
   '+7-495-200-0009', '192.168.1.104', 'closed', 'Phone', 0, 1,
   NULL, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY),
   DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY),
   DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),

  (11, 774123, 1, 2, 3, 5, NULL,
   'user10@company.ru', 'Татьяна Волкова', 'Установка антивируса Kaspersky', 'Установка ПО',
   '+7-495-200-0010', '192.168.1.115', 'closed', 'Web', 0, 1,
   NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY),
   DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY),
   DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),

  (12, 885234, 2, 2, 5, 4, NULL,
   'user11@company.ru', 'Владимир Соловьёв', 'Заказ нового монитора', 'Запрос на обслуживание',
   '+7-495-200-0011', '192.168.1.128', 'closed', 'Email', 0, 1,
   NULL, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY),
   DATE_SUB(NOW(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY),
   DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),

  (13, 996345, 1, 4, 1, 2, '3,5',
   'user12@company.ru', 'Антон Петров', 'Массовый сбой сети на 3 этаже', 'Техническая проблема',
   '+7-495-200-0012', '192.168.1.5', 'closed', 'Phone', 0, 1,
   NULL, NULL, DATE_SUB(NOW(), INTERVAL 15 DAY),
   DATE_SUB(NOW(), INTERVAL 16 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY),
   DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY)),

  (14, 107456, 1, 1, 6, 5, NULL,
   'user13@company.ru', 'Юлия Белова', 'Как подключить сетевой диск', 'Настройка сети',
   '+7-495-200-0013', '192.168.1.140', 'closed', 'Web', 0, 1,
   NULL, NULL, DATE_SUB(NOW(), INTERVAL 20 DAY),
   DATE_SUB(NOW(), INTERVAL 21 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY),
   DATE_SUB(NOW(), INTERVAL 22 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY)),

  (15, 218567, 2, 2, 7, 6, NULL,
   'user14@company.ru', 'Роман Егоров', 'Лицензия AutoCAD истекает', 'Вопрос по лицензиям',
   '+7-495-200-0014', '192.168.1.155', 'closed', 'Web', 0, 1,
   NULL, NULL, DATE_SUB(NOW(), INTERVAL 6 DAY),
   DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY),
   DATE_SUB(NOW(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 6 DAY)),

  -- Просроченные открытые
  (16, 329678, 1, 3, 1, 3, NULL,
   'user15@company.ru', 'Ксения Давыдова', 'Ошибка при печати из SAP', 'Техническая проблема',
   '+7-495-200-0015', '192.168.1.170', 'open', 'Email', 1, 1,
   DATE_SUB(NOW(), INTERVAL 2 DAY), NULL, NULL,
   DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY),
   DATE_SUB(NOW(), INTERVAL 5 DAY), NOW()),

  (17, 430789, 1, 2, 3, 0, NULL,
   'user16@company.ru', 'Павел Тихонов', 'Установить Zoom на рабочий ПК', 'Установка ПО',
   NULL, '192.168.1.185', 'open', 'Web', 0, 0,
   DATE_ADD(NOW(), INTERVAL 7 DAY), NULL, NULL,
   DATE_SUB(NOW(), INTERVAL 2 HOUR), NULL,
   DATE_SUB(NOW(), INTERVAL 2 HOUR), NOW()),

  -- Переоткрытая заявка
  (18, 541890, 1, 3, 1, 2, NULL,
   'user1@company.ru', 'Алексей Смирнов', 'Повторный сбой 1С - проблема не решена', 'Техническая проблема',
   '+7-495-200-0001', '192.168.1.10', 'open', 'Web', 0, 0,
   DATE_ADD(NOW(), INTERVAL 1 DAY),
   DATE_SUB(NOW(), INTERVAL 1 DAY), NULL,
   DATE_SUB(NOW(), INTERVAL 1 DAY), NULL,
   DATE_SUB(NOW(), INTERVAL 4 DAY), NOW()),

  (19, 652901, 2, 1, 5, 4, NULL,
   'user17@company.ru', 'Виктория Орлова', 'Консультация по выбору ноутбука', 'Запрос на обслуживание',
   '+7-495-200-0017', '192.168.2.10', 'open', 'Phone', 0, 1,
   NULL, NULL, NULL,
   DATE_SUB(NOW(), INTERVAL 4 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR),
   DATE_SUB(NOW(), INTERVAL 6 HOUR), NOW());
INSERT INTO `%TABLE_PREFIX%ticket_message`
  (`msg_id`,`ticket_id`,`messageId`,`message`,`headers`,`source`,`ip_address`,`created`)
VALUES
  -- Заявка #2 (384719) — 1С
  (2, 2, NULL,
   'Добрый день!\n\nПосле последнего обновления Windows перестала запускаться 1С Предприятие 8.3. При запуске выдаёт ошибку \"Версия компоненты не совпадает\". Переустановка не помогла.\n\nРабочая станция: WS-FIN-012\nОС: Windows 11 Pro\n1С: Версия 8.3.24.1467\n\nПрошу помочь в кратчайшие сроки, так как идёт квартальный отчёт.',
   NULL, 'Web', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 5 HOUR)),

  (3, 2, NULL,
   'Уточнение: ошибка появляется именно при запуске конфигурации \"Бухгалтерия предприятия\". Тонкий клиент запускается нормально.',
   NULL, 'Web', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 3 HOUR)),

  -- Заявка #3 (294057) — сервер печати
  (4, 3, '<msg001@mail.company.ru>',
   'Здравствуйте!\n\nПринтеры на 2 этаже полностью перестали работать. Сервер печати (PRN-SRV-01) не отвечает на ping. Перезагрузка по питанию не помогла. Затронуты все 12 принтеров на этаже.\n\nЭто блокирует работу бухгалтерии и отдела кадров.',
   NULL, 'Email', '192.168.1.22', DATE_SUB(NOW(), INTERVAL 26 HOUR)),

  (5, 3, '<msg002@mail.company.ru>',
   'Дополнительно: светодиод на сетевой карте сервера не горит. Возможно проблема с сетевым адаптером.',
   NULL, 'Email', '192.168.1.22', DATE_SUB(NOW(), INTERVAL 2 HOUR)),

  -- Заявка #4 (518432) — Adobe
  (6, 4, NULL,
   'Добрый день! Прошу установить Adobe Acrobat Pro DC на мою рабочую станцию WS-MKT-003. Необходим для редактирования PDF-документов для маркетинговых материалов. Спасибо!',
   NULL, 'Web', '192.168.1.35', DATE_SUB(NOW(), INTERVAL 12 HOUR)),

  -- Заявка #5 (673201) — картриджи
  (7, 5, NULL,
   'Необходимо заказать картриджи:\n- HP CF226X (для HP LaserJet Pro M402) — 3 шт.\n- HP CF230A (для HP LaserJet Pro M203) — 2 шт.\n\nТекущие картриджи заканчиваются, запас примерно на 2-3 дня.',
   NULL, 'Phone', '192.168.1.48', DATE_SUB(NOW(), INTERVAL 2 DAY)),

  -- Заявка #6 (891245) — VPN
  (8, 6, NULL,
   'Не могу подключиться к корпоративному VPN из дома. При подключении через FortiClient выдаёт ошибку \"Unable to establish the VPN connection. The VPN server may be unreachable.\" \n\nДомашний интернет работает нормально. Вчера всё работало.',
   NULL, 'Web', '10.0.0.50', DATE_SUB(NOW(), INTERVAL 45 MINUTE)),

  -- Заявка #7 (102384) — Outlook
  (9, 7, '<msg003@mail.company.ru>',
   'Outlook перестал синхронизировать почту с Exchange сервером. Отправка и получение зависает на \"Подключение к серверу...\" Профиль пересоздавала — не помогает.\n\nOutlook 2021, Windows 11.',
   NULL, 'Email', '192.168.1.67', DATE_SUB(NOW(), INTERVAL 8 HOUR)),

  (10, 7, '<msg004@mail.company.ru>',
   'Спасибо за рекомендацию. Очистка кэша помогла частично — входящие появились, но папка \"Отправленные\" пуста. Письма за последние 2 дня пропали.',
   NULL, 'Email', '192.168.1.67', DATE_SUB(NOW(), INTERVAL 6 HOUR)),

  -- Заявка #8 (445678) — MS Office лицензия
  (11, 8, NULL,
   'Добрый день! Лицензия Microsoft Office 365 на нашем отделе (5 сотрудников) истекает через 2 недели. Прошу продлить подписку. Текущий план: Microsoft 365 Business Standard.',
   NULL, 'Web', '192.168.1.80', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),

  -- Заявка #9 (557891) — Excel (решена)
  (12, 9, NULL,
   'Не открывается файл Excel с отчётом за квартал. При открытии выдаёт ошибку \"Файл повреждён и не может быть открыт\". Файл получен по почте от контрагента.',
   NULL, 'Web', '192.168.1.91', DATE_SUB(NOW(), INTERVAL 7 DAY)),

  -- Заявка #10 (663012) — не включается ПК (решена)
  (13, 10, NULL,
   'Компьютер на рабочем месте HR-002 не включается. При нажатии кнопки питания ничего не происходит — ни индикаторов, ни звуков. Вчера работал нормально.',
   NULL, 'Phone', '192.168.1.104', DATE_SUB(NOW(), INTERVAL 8 DAY)),

  -- Заявка #13 (996345) — массовый сбой сети (решена)
  (14, 13, NULL,
   'СРОЧНО! На 3 этаже пропал интернет и доступ к сетевым ресурсам. Затронуты примерно 40 рабочих мест. Коммутатор в серверной мигает красным.',
   NULL, 'Phone', '192.168.1.5', DATE_SUB(NOW(), INTERVAL 18 DAY)),

  -- Заявка #16 (329678) — ошибка SAP (просрочена)
  (15, 16, '<msg005@mail.company.ru>',
   'При печати из SAP транзакции ME23N (просмотр заказа на поставку) выдаёт ошибку \"Spool request error\". Проблема появилась после миграции на новый сервер печати.\n\nЗатрагивает весь отдел закупок (8 человек).',
   NULL, 'Email', '192.168.1.170', DATE_SUB(NOW(), INTERVAL 5 DAY)),

  -- Заявка #17 (430789) — Zoom
  (16, 17, NULL,
   'Здравствуйте, прошу установить Zoom на мой компьютер WS-HR-007. Нужен для проведения собеседований с удалёнными кандидатами.',
   NULL, 'Web', '192.168.1.185', DATE_SUB(NOW(), INTERVAL 2 HOUR)),

  -- Заявка #18 (541890) — повторный сбой 1С
  (17, 18, NULL,
   'Проблема с 1С повторилась. После вашего исправления проработала 2 дня и снова та же ошибка. Прошу разобраться основательно.',
   NULL, 'Web', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 1 DAY)),

  -- Заявка #19 (652901) — ноутбук
  (18, 19, NULL,
   'Здравствуйте! Мне необходим ноутбук для командировок. Требования:\n- Экран 14-15 дюймов\n- Минимум 16 ГБ ОЗУ\n- SSD от 512 ГБ\n- Хорошая автономность (от 8 часов)\n\nБюджет: до 120 000 руб. Прошу подобрать варианты.',
   NULL, 'Phone', '192.168.2.10', DATE_SUB(NOW(), INTERVAL 6 HOUR));
INSERT INTO `%TABLE_PREFIX%ticket_response`
  (`response_id`,`msg_id`,`ticket_id`,`staff_id`,`staff_name`,`response`,`ip_address`,`created`)
VALUES
  -- Заявка #2 (384719) — ответ на 1С
  (1, 2, 2, 2, 'Иванов И.',
   'Алексей, добрый день!\n\nОшибка связана с несовместимостью обновления Windows KB5034441 и платформы 1С 8.3.24. Известная проблема.\n\nДля исправления необходимо:\n1. Удалить обновление KB5034441\n2. Обновить платформу 1С до версии 8.3.25.1286\n3. Перезапустить рабочую станцию\n\nПодключусь к вашему ПК удалённо в течение часа.',
   '192.168.1.1', DATE_SUB(NOW(), INTERVAL 1 HOUR)),

  -- Заявка #5 (673201) — ответ про картриджи
  (2, 7, 5, 4, 'Сидоров П.',
   'Елена, здравствуйте!\n\nЗаказ на картриджи оформлен:\n- HP CF226X × 3 шт. — Артикул: #ORD-2024-0847\n- HP CF230A × 2 шт. — Артикул: #ORD-2024-0848\n\nОриентировочный срок доставки: 2-3 рабочих дня.\n\nЕсли картриджи закончатся раньше, можно временно перенаправить печать на принтер в комнате 215.',
   '192.168.1.1', DATE_SUB(NOW(), INTERVAL 20 HOUR)),

  -- Заявка #7 (102384) — ответ про Outlook
  (3, 9, 7, 2, 'Иванов И.',
   'Наталья, добрый день!\n\nПопробуйте следующее:\n1. Закройте Outlook\n2. Нажмите Win+R, введите: outlook.exe /resetnavpane\n3. Если не поможет — Win+R → outlook.exe /cleanviews\n4. Также очистите кэш: %localappdata%\\Microsoft\\Outlook\\RoamCache — удалите всё содержимое\n\nПосле этого Outlook пересинхронизирует данные с сервера.',
   '192.168.1.1', DATE_SUB(NOW(), INTERVAL 4 HOUR)),

  -- Заявка #9 (557891) — решение Excel
  (4, 12, 9, 3, 'Петрова А.',
   'Марина, добрый день!\n\nФайл удалось восстановить через встроенную функцию Excel:\nФайл → Открыть → выбрать файл → стрелка на кнопке \"Открыть\" → \"Открыть и восстановить\"\n\nВосстановленный файл отправлен вам на почту. Рекомендую попросить контрагента пересохранить оригинал в формате .xlsx (а не .xls).',
   '192.168.1.1', DATE_SUB(NOW(), INTERVAL 3 DAY)),

  -- Заявка #10 (663012) — решение ПК
  (5, 13, 10, 2, 'Иванов И.',
   'Диагностика завершена. Причина — вышел из строя блок питания (Seasonic 550W). Заменён на новый из склада.\n\nВсе данные на дисках сохранены. Компьютер возвращён на рабочее место HR-002 и проверен — работает штатно.',
   '192.168.1.1', DATE_SUB(NOW(), INTERVAL 5 DAY)),

  -- Заявка #13 (996345) — решение сети
  (6, 14, 13, 2, 'Иванов И.',
   'Проблема локализована и устранена.\n\nПричина: коммутатор Cisco Catalyst 2960X на 3 этаже перегрелся из-за забитых вентиляционных отверстий. После охлаждения и очистки — перезагружен.\n\nВсе 40 рабочих мест восстановлены. Время простоя: 2 часа 15 минут.\n\nПрофилактическая чистка всех сетевых шкафов запланирована на следующую неделю.',
   '192.168.1.1', DATE_SUB(NOW(), INTERVAL 15 DAY)),

  -- Заявка #16 (329678) — ответ SAP
  (7, 15, 16, 3, 'Петрова А.',
   'Ксения, здравствуйте!\n\nПроблема связана с некорректной настройкой спул-системы SAP после миграции. Мы обновили параметры вывода в транзакции SPAD.\n\nПожалуйста, проверьте печать сейчас и сообщите результат.',
   '192.168.1.1', DATE_SUB(NOW(), INTERVAL 2 DAY)),

  -- Заявка #19 (652901) — ответ про ноутбук
  (8, 18, 19, 4, 'Сидоров П.',
   'Виктория, добрый день!\n\nПодобрал 3 варианта под ваши требования:\n\n1. Lenovo ThinkPad T14s Gen 4 — 105 000 руб.\n   AMD Ryzen 7 PRO 7840U, 16 ГБ, 512 ГБ SSD, 14\", ~12ч батарея\n\n2. HP EliteBook 840 G10 — 112 000 руб.\n   Intel Core i7-1365U, 16 ГБ, 512 ГБ SSD, 14\", ~10ч батарея\n\n3. Dell Latitude 5540 — 98 000 руб.\n   Intel Core i5-1345U, 16 ГБ, 512 ГБ SSD, 15.6\", ~9ч батарея\n\nРекомендую вариант 1 — лучшее соотношение цена/качество.',
   '192.168.1.1', DATE_SUB(NOW(), INTERVAL 2 HOUR));
INSERT INTO `%TABLE_PREFIX%ticket_note`
  (`note_id`,`ticket_id`,`staff_id`,`source`,`title`,`note`,`created`)
VALUES
  (1, 3, 3, 'Внутренняя заметка', 'Диагностика сервера',
   'Сервер PRN-SRV-01: сетевой адаптер Intel I350 неисправен. Заказал замену — артикул #HW-2024-0091. Ожидание 1-2 дня. Временное решение: подключил USB-Ethernet адаптер, восстановил 4 из 12 принтеров.',
   DATE_SUB(NOW(), INTERVAL 20 HOUR)),

  (2, 10, 2, 'Внутренняя заметка', 'Замена БП',
   'Блок питания Seasonic SSR-550FX — неисправен. Взят из ЗИП на складе (инв. номер SP-PSU-007). Необходимо заказать замену в ЗИП.',
   DATE_SUB(NOW(), INTERVAL 6 DAY)),

  (3, 13, 2, 'Внутренняя заметка', 'Анализ инцидента',
   'Инцидент INC-2024-031. Причина: перегрев коммутатора. Серверная комната 3 этажа — кондиционер работает некорректно с прошлой недели. Подал заявку в АХО на ремонт кондиционера.',
   DATE_SUB(NOW(), INTERVAL 16 DAY)),

  (4, 16, 3, 'Внутренняя заметка', 'Эскалация',
   'Проблема требует привлечения SAP Basis администратора. Связалась с подрядчиком (ООО \"САП Консалтинг\"), ожидаю ответ.',
   DATE_SUB(NOW(), INTERVAL 4 DAY)),

  (5, 18, 2, 'Внутренняя заметка', 'Повторная проблема 1С',
   'Клиент обратился повторно. Предыдущее решение (заявка #384719) — временное. Необходима полная переустановка платформы и обновление конфигурации. Согласовать с бухгалтерией окно обслуживания.',
   DATE_SUB(NOW(), INTERVAL 1 DAY));
INSERT INTO `%TABLE_PREFIX%task_boards`
  (`board_id`,`board_name`,`board_type`,`dept_id`,`description`,`color`,`is_archived`,`created_by`,`created`,`updated`)
VALUES
  (1, 'Тех. отдел — Текущие задачи', 'department', 1,
   'Операционные задачи технического отдела', '#3498db', 0, 2, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
  (2, 'Редизайн корп. портала', 'project', 0,
   'Проект по модернизации внутреннего портала компании', '#e74c3c', 0, 2, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
  (3, 'Отдел продаж — Закупки', 'department', 2,
   'Управление заказами и закупками оборудования', '#2ecc71', 0, 6, DATE_SUB(NOW(), INTERVAL 45 DAY), NOW());

INSERT INTO `%TABLE_PREFIX%task_lists`
  (`list_id`,`board_id`,`list_name`,`status`,`list_order`,`is_archived`,`created`,`updated`)
VALUES
  -- Доска 1: Тех. отдел
  (1, 1, 'Бэклог',      'open',         0, 0, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
  (2, 1, 'В работе',     'in_progress',  1, 0, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
  (3, 1, 'На проверке',  'review',       2, 0, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
  (4, 1, 'Выполнено',    'completed',    3, 0, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
  -- Доска 2: Редизайн портала
  (5, 2, 'To Do',         'open',         0, 0, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
  (6, 2, 'In Progress',   'in_progress',  1, 0, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
  (7, 2, 'Review',        'review',       2, 0, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
  (8, 2, 'Done',          'completed',    3, 0, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
  -- Доска 3: Закупки
  (9,  3, 'Новые заявки',   'open',         0, 0, DATE_SUB(NOW(), INTERVAL 45 DAY), NOW()),
  (10, 3, 'Согласование',   'in_progress',  1, 0, DATE_SUB(NOW(), INTERVAL 45 DAY), NOW()),
  (11, 3, 'Заказано',       'review',       2, 0, DATE_SUB(NOW(), INTERVAL 45 DAY), NOW()),
  (12, 3, 'Получено',       'completed',    3, 0, DATE_SUB(NOW(), INTERVAL 45 DAY), NOW());

INSERT INTO `%TABLE_PREFIX%tasks`
  (`task_id`,`board_id`,`list_id`,`parent_task_id`,`ticket_id`,`title`,`description`,`task_type`,`priority`,`status`,`start_date`,`end_date`,`deadline`,`time_estimate`,`position`,`created_by`,`created`,`updated`,`completed_date`,`is_archived`)
VALUES
  -- Доска 1: Тех. отдел
  (1, 1, 2, NULL, NULL, 'Обновить антивирусные базы на всех ПК',
   'Обновить Kaspersky Endpoint Security до последней версии на всех рабочих станциях (87 шт.)',
   'action', 'normal', 'in_progress',
   DATE_SUB(NOW(), INTERVAL 3 DAY), NULL, DATE_ADD(NOW(), INTERVAL 4 DAY), 480,
   0, 2, DATE_SUB(NOW(), INTERVAL 5 DAY), NOW(), NULL, 0),

  (2, 1, 2, NULL, 3, 'Замена сетевого адаптера PRN-SRV-01',
   'Сервер печати PRN-SRV-01 — неисправен сетевой адаптер Intel I350. Связано с заявкой #294057.',
   'action', 'urgent', 'in_progress',
   DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, DATE_ADD(NOW(), INTERVAL 1 DAY), 120,
   1, 3, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW(), NULL, 0),

  (3, 1, 1, NULL, NULL, 'Настроить бэкап NAS хранилища',
   'Настроить ежедневное резервное копирование Synology NAS на внешний диск.\nПолитика: 7 ежедневных + 4 еженедельных + 3 ежемесячных.',
   'action', 'high', 'open',
   NULL, NULL, DATE_ADD(NOW(), INTERVAL 10 DAY), 240,
   0, 2, DATE_SUB(NOW(), INTERVAL 7 DAY), NOW(), NULL, 0),

  (4, 1, 4, NULL, NULL, 'Замена коммутатора на 2 этаже',
   'Заменён старый D-Link DGS-1210 на Cisco Catalyst 2960X. Всё подключено, проверено, работает.',
   'action', 'normal', 'completed',
   DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), 180,
   0, 2, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), 0),

  (5, 1, 3, NULL, NULL, 'Ревизия учётных записей AD',
   'Проверить все учётные записи Active Directory. Заблокировать неактивные >90 дней.',
   'action', 'normal', 'review',
   DATE_SUB(NOW(), INTERVAL 5 DAY), NULL, DATE_ADD(NOW(), INTERVAL 2 DAY), 360,
   0, 5, DATE_SUB(NOW(), INTERVAL 10 DAY), NOW(), NULL, 0),

  (6, 1, 1, NULL, NULL, 'Обновить прошивку на UPS',
   'Обновить firmware на ИБП APC Smart-UPS 3000 в серверной. Текущая версия: 2.1, доступна: 3.0.',
   'action', 'low', 'open',
   NULL, NULL, NULL, 60,
   1, 2, DATE_SUB(NOW(), INTERVAL 2 DAY), NOW(), NULL, 0),

  -- Доска 2: Редизайн портала
  (7, 2, 6, NULL, NULL, 'Разработка макетов новых страниц',
   'Создать макеты в Figma для: главная, каталог услуг, база знаний, профиль сотрудника.',
   'action', 'high', 'in_progress',
   DATE_SUB(NOW(), INTERVAL 10 DAY), NULL, DATE_ADD(NOW(), INTERVAL 5 DAY), 1200,
   0, 5, DATE_SUB(NOW(), INTERVAL 15 DAY), NOW(), NULL, 0),

  (8, 2, 5, NULL, NULL, 'Интеграция с Active Directory (SSO)',
   'Реализовать Single Sign-On через LDAP/AD. Пользователи должны входить по корпоративным учёткам.',
   'action', 'high', 'open',
   NULL, NULL, DATE_ADD(NOW(), INTERVAL 20 DAY), 960,
   0, 2, DATE_SUB(NOW(), INTERVAL 15 DAY), NOW(), NULL, 0),

  (9, 2, 8, NULL, NULL, 'Сбор требований от отделов',
   'Провести интервью со всеми руководителями отделов. Собрать пожелания по функциональности нового портала.',
   'meeting', 'normal', 'completed',
   DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY), 480,
   0, 2, DATE_SUB(NOW(), INTERVAL 28 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY), 0),

  (10, 2, 5, NULL, NULL, 'Миграция контента со старого портала',
   'Перенести все актуальные документы, новости и справочные материалы на новую платформу.',
   'action', 'normal', 'open',
   NULL, NULL, DATE_ADD(NOW(), INTERVAL 30 DAY), 720,
   1, 3, DATE_SUB(NOW(), INTERVAL 10 DAY), NOW(), NULL, 0),

  -- Доска 3: Закупки
  (11, 3, 10, NULL, NULL, 'Заказ 5 мониторов Dell 27"',
   'Заказать мониторы Dell U2723QE для нового офиса на 4 этаже. Бюджет согласован.',
   'action', 'normal', 'in_progress',
   DATE_SUB(NOW(), INTERVAL 3 DAY), NULL, DATE_ADD(NOW(), INTERVAL 7 DAY), 60,
   0, 4, DATE_SUB(NOW(), INTERVAL 5 DAY), NOW(), NULL, 0),

  (12, 3, 11, NULL, NULL, 'Заказ картриджей — ежемесячный',
   'Ежемесячный заказ расходных материалов для принтеров. Заказ отправлен поставщику.',
   'action', 'normal', 'review',
   DATE_SUB(NOW(), INTERVAL 5 DAY), NULL, DATE_ADD(NOW(), INTERVAL 2 DAY), 30,
   0, 4, DATE_SUB(NOW(), INTERVAL 7 DAY), NOW(), NULL, 0),

  (13, 3, 12, NULL, NULL, 'Получены ноутбуки Lenovo ThinkPad ×3',
   'Три ноутбука Lenovo ThinkPad T14s получены, проверены, настроены. Переданы сотрудникам.',
   'action', 'normal', 'completed',
   DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), 240,
   0, 4, DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), 0),

  (14, 3, 9, NULL, 19, 'Подбор ноутбука для Орловой В.',
   'Связано с заявкой #652901. Подобрать и согласовать модель ноутбука.',
   'action', 'normal', 'open',
   NULL, NULL, DATE_ADD(NOW(), INTERVAL 5 DAY), 60,
   0, 4, DATE_SUB(NOW(), INTERVAL 4 HOUR), NOW(), NULL, 0);

INSERT INTO `%TABLE_PREFIX%task_assignees`
  (`assignment_id`,`task_id`,`staff_id`,`role`,`assigned_date`)
VALUES
  (1,  1,  5, 'assignee', DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (2,  1,  3, 'watcher',  DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (3,  2,  3, 'assignee', DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (4,  2,  2, 'watcher',  DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (5,  3,  2, 'assignee', DATE_SUB(NOW(), INTERVAL 7 DAY)),
  (6,  5,  5, 'assignee', DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (7,  5,  2, 'watcher',  DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (8,  7,  5, 'assignee', DATE_SUB(NOW(), INTERVAL 10 DAY)),
  (9,  7,  3, 'co-author',DATE_SUB(NOW(), INTERVAL 10 DAY)),
  (10, 8,  2, 'assignee', DATE_SUB(NOW(), INTERVAL 15 DAY)),
  (11, 10, 3, 'assignee', DATE_SUB(NOW(), INTERVAL 10 DAY)),
  (12, 11, 4, 'assignee', DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (13, 12, 4, 'assignee', DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (14, 14, 4, 'assignee', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
  (15, 14, 6, 'watcher',  DATE_SUB(NOW(), INTERVAL 4 HOUR));

INSERT INTO `%TABLE_PREFIX%task_tags`
  (`tag_id`,`tag_name`,`tag_color`,`board_id`,`created`)
VALUES
  (1, 'Срочно',       '#e74c3c', 1, DATE_SUB(NOW(), INTERVAL 60 DAY)),
  (2, 'Инфраструктура','#3498db', 1, DATE_SUB(NOW(), INTERVAL 60 DAY)),
  (3, 'Безопасность',  '#e67e22', 1, DATE_SUB(NOW(), INTERVAL 60 DAY)),
  (4, 'UI/UX',         '#9b59b6', 2, DATE_SUB(NOW(), INTERVAL 30 DAY)),
  (5, 'Backend',       '#2ecc71', 2, DATE_SUB(NOW(), INTERVAL 30 DAY)),
  (6, 'Согласование',  '#f39c12', 3, DATE_SUB(NOW(), INTERVAL 45 DAY)),
  (7, 'Бюджет',        '#1abc9c', 3, DATE_SUB(NOW(), INTERVAL 45 DAY));

INSERT INTO `%TABLE_PREFIX%task_tag_associations`
  (`association_id`,`task_id`,`tag_id`)
VALUES
  (1, 2, 1), (2, 2, 2), (3, 3, 2), (4, 3, 3),
  (5, 5, 3), (6, 7, 4), (7, 8, 5), (8, 11, 6),
  (9, 11, 7), (10, 14, 7);

INSERT INTO `%TABLE_PREFIX%task_comments`
  (`comment_id`,`task_id`,`staff_id`,`comment_text`,`created`)
VALUES
  (1, 1, 5, 'Обновил 34 из 87 станций. Остальные — завтра и послезавтра.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (2, 1, 2, 'Хорошо. Приоритет — бухгалтерия и отдел кадров.', DATE_SUB(NOW(), INTERVAL 23 HOUR)),
  (3, 2, 3, 'Сетевой адаптер Intel I350-T2 заказан, ожидаем завтра. Временно работает через USB-Ethernet.', DATE_SUB(NOW(), INTERVAL 18 HOUR)),
  (4, 7, 5, 'Макет главной страницы готов, загружен в Figma. Жду ревью.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (5, 7, 2, 'Посмотрел. Нужно добавить блок с последними новостями компании на главную.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (6, 11, 4, 'Отправил запрос поставщику. Ожидаю коммерческое предложение.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
  (7, 11, 6, 'Бюджет на мониторы согласован с финансовым директором.', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO `%TABLE_PREFIX%task_time_logs`
  (`log_id`,`task_id`,`staff_id`,`time_spent`,`log_date`,`notes`)
VALUES
  (1, 1, 5, 180, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Обновление станций: корпус A, 1-2 этаж'),
  (2, 1, 5, 210, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Обновление станций: корпус A, 3-4 этаж'),
  (3, 2, 3,  60, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Диагностика сервера печати, подключение временного адаптера'),
  (4, 4, 2, 120, DATE_SUB(NOW(), INTERVAL 13 DAY), 'Монтаж и настройка коммутатора'),
  (5, 4, 2,  45, DATE_SUB(NOW(), INTERVAL 12 DAY), 'Тестирование, проверка всех портов'),
  (6, 7, 5, 360, DATE_SUB(NOW(), INTERVAL 5 DAY), 'Дизайн макета главной страницы'),
  (7, 7, 5, 240, DATE_SUB(NOW(), INTERVAL 3 DAY), 'Дизайн макета каталога услуг'),
  (8, 9, 2, 120, DATE_SUB(NOW(), INTERVAL 22 DAY), 'Интервью: тех. отдел, бухгалтерия'),
  (9, 9, 2,  90, DATE_SUB(NOW(), INTERVAL 20 DAY), 'Интервью: HR, маркетинг, продажи');

INSERT INTO `%TABLE_PREFIX%task_activity_log`
  (`activity_id`,`task_id`,`staff_id`,`activity_type`,`activity_data`,`created`)
VALUES
  (1,  1, 2, 'created',        '{"title":"Обновить антивирусные базы на всех ПК"}',              DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (2,  1, 2, 'assigned',       '{"staff_id":5,"staff_name":"Козлова М."}',                        DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (3,  1, 5, 'status_changed', '{"from":"open","to":"in_progress"}',                              DATE_SUB(NOW(), INTERVAL 3 DAY)),
  (4,  2, 3, 'created',        '{"title":"Замена сетевого адаптера PRN-SRV-01","ticket_id":3}',  DATE_SUB(NOW(), INTERVAL 1 DAY)),
  (5,  4, 2, 'created',        '{"title":"Замена коммутатора на 2 этаже"}',                       DATE_SUB(NOW(), INTERVAL 20 DAY)),
  (6,  4, 2, 'status_changed', '{"from":"in_progress","to":"completed"}',                         DATE_SUB(NOW(), INTERVAL 12 DAY)),
  (7,  4, 2, 'completed',      '{}',                                                              DATE_SUB(NOW(), INTERVAL 12 DAY)),
  (8,  7, 5, 'created',        '{"title":"Разработка макетов новых страниц"}',                    DATE_SUB(NOW(), INTERVAL 15 DAY)),
  (9,  7, 5, 'status_changed', '{"from":"open","to":"in_progress"}',                              DATE_SUB(NOW(), INTERVAL 10 DAY)),
  (10, 9, 2, 'completed',      '{}',                                                              DATE_SUB(NOW(), INTERVAL 18 DAY)),
  (11, 13, 4, 'completed',     '{}',                                                              DATE_SUB(NOW(), INTERVAL 10 DAY));

INSERT INTO `%TABLE_PREFIX%task_board_permissions`
  (`permission_id`,`board_id`,`staff_id`,`dept_id`,`permission_level`)
VALUES
  (1, 1, NULL, 1, 'edit'),
  (2, 2, 2,  NULL, 'admin'),
  (3, 2, 3,  NULL, 'edit'),
  (4, 2, 5,  NULL, 'edit'),
  (5, 3, NULL, 2, 'edit');

INSERT INTO `%TABLE_PREFIX%task_saved_filters`
  (`filter_id`,`staff_id`,`filter_name`,`filter_config`,`is_default`,`created`)
VALUES
  (1, 2, 'Мои срочные задачи', '{"priority":"urgent","assignee":"me","status":["open","in_progress"]}', 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),
  (2, 5, 'Задачи на ревью',    '{"status":["review"],"board_id":1}',                                    0, DATE_SUB(NOW(), INTERVAL 15 DAY));
INSERT INTO `%TABLE_PREFIX%kb_documents`
  (`doc_id`,`title`,`description`,`doc_type`,`file_name`,`file_key`,`file_size`,`file_mime`,`external_url`,`audience`,`dept_id`,`staff_id`,`isenabled`,`created`,`updated`)
VALUES
  (1, 'Инструкция по подключению к VPN',
   'Пошаговая инструкция по настройке FortiClient для удалённого подключения к корпоративной сети. Включает настройки для Windows, macOS и Linux.',
   'file', 'vpn-setup-guide.pdf', 'kb_vpn_setup_2024', 2457600, 'application/pdf',
   NULL, 'all', 0, 2, 1, DATE_SUB(NOW(), INTERVAL 45 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),

  (2, 'Политика информационной безопасности',
   'Основные правила работы с корпоративными данными, паролями и внешними носителями.',
   'file', 'security-policy-v3.pdf', 'kb_sec_policy_v3', 1843200, 'application/pdf',
   NULL, 'all', 0, 2, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)),

  (3, 'Регламент заказа оборудования',
   'Порядок оформления заявок на закупку компьютерной техники и расходных материалов.',
   'file', 'equipment-order-process.pdf', 'kb_equip_order', 921600, 'application/pdf',
   NULL, 'staff', 0, 6, 1, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),

  (4, 'FAQ: Частые вопросы по 1С',
   'Решения типичных проблем с 1С Предприятие: ошибки запуска, производительность, обновление.',
   'link', NULL, NULL, 0, NULL,
   NULL, 'staff', 1, 2, 1, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),

  (5, 'Схема корпоративной сети',
   'Актуальная схема сетевой инфраструктуры: VLAN, коммутаторы, маршрутизаторы, серверы.',
   'file', 'network-topology-2024.pdf', 'kb_net_topology', 5242880, 'application/pdf',
   NULL, 'staff', 1, 2, 1, DATE_SUB(NOW(), INTERVAL 20 DAY), NOW());
INSERT INTO `%TABLE_PREFIX%locations`
  (`location_id`,`location_name`,`parent_id`,`location_type`,`description`,`sort_order`,`is_active`,`created`,`updated`)
VALUES
  (1,  'Главный офис',         NULL, 'building', 'Основное здание компании, ул. Примерная, д. 1',  1, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
  (2,  '1 этаж',               1,    'floor',    'Ресепшен, переговорные',                         1, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
  (3,  '2 этаж',               1,    'floor',    'Бухгалтерия, отдел кадров',                      2, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
  (4,  '3 этаж',               1,    'floor',    'Технический отдел, разработка',                   3, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
  (5,  '4 этаж',               1,    'floor',    'Руководство, продажи',                            4, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
  (6,  'Серверная 1 этаж',     2,    'room',     'Основная серверная комната',                      1, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
  (7,  'Серверная 3 этаж',     4,    'room',     'Коммутационная',                                  1, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
  (8,  'Склад ИТ',            1,    'storage',  'Склад ЗИП и расходных материалов',                5, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
  (9,  'Стойка A',            6,    'rack',     'Основная серверная стойка (серверы, NAS)',          1, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
  (10, 'Стойка B',            6,    'rack',     'Сетевое оборудование (коммутаторы, роутеры)',      2, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW());
INSERT INTO `%TABLE_PREFIX%inventory_brands`
  (`brand_id`,`brand_name`,`is_active`,`created`)
VALUES
  (1, 'Lenovo',    1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (2, 'HP',        1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (3, 'Dell',      1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (4, 'Cisco',     1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (5, 'Samsung',   1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (6, 'APC',       1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (7, 'Synology',  1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (8, 'Apple',     1, DATE_SUB(NOW(), INTERVAL 60 DAY));

INSERT INTO `%TABLE_PREFIX%inventory_models`
  (`model_id`,`brand_id`,`category_id`,`model_name`,`is_active`,`created`)
VALUES
  (1,  1, 8,  'ThinkPad T14s Gen 4',     1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (2,  1, 9,  'ThinkCentre M70q Gen 4',  1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (3,  2, 8,  'EliteBook 840 G10',       1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (4,  2, 3,  'LaserJet Pro M402dn',     1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (5,  2, 3,  'LaserJet Pro MFP M428fdw',1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (6,  3, 8,  'Latitude 5540',           1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (7,  3, 2,  'U2723QE 27"',             1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (8,  4, 4,  'Catalyst 2960X-24TS',     1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (9,  5, 2,  'S24D390HL 24"',           1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (10, 6, 7,  'Smart-UPS 3000',          1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (11, 7, 6,  'DS920+',                  1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
  (12, 8, 8,  'MacBook Pro 14 M3',       1, DATE_SUB(NOW(), INTERVAL 60 DAY));
INSERT INTO `%TABLE_PREFIX%inventory_items`
  (`item_id`,`inventory_number`,`category_id`,`brand_id`,`model_id`,`custom_model`,`serial_number`,`part_number`,`location_id`,`assigned_staff_id`,`assignment_type`,`status`,`purchase_date`,`warranty_until`,`cost`,`description`,`created_by`,`created`,`updated`)
VALUES
  -- Ноутбуки
  (1,  'INV-NB-001', 8, 1, 1, NULL, 'PF4ABCDE', '21F6CTO1WW', NULL, 2, 'workplace', 'active',
   '2024-03-15', '2027-03-15', 105000.00, 'Ноутбук руководителя тех. отдела',
   2, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),

  (2,  'INV-NB-002', 8, 1, 1, NULL, 'PF4FGHIJ', '21F6CTO1WW', NULL, 3, 'workplace', 'active',
   '2024-03-15', '2027-03-15', 105000.00, NULL,
   2, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),

  (3,  'INV-NB-003', 8, 2, 3, NULL, '5CG4KLMN', '6T258EA', NULL, 6, 'workplace', 'active',
   '2024-01-20', '2027-01-20', 112000.00, 'Ноутбук руководителя отдела продаж',
   2, DATE_SUB(NOW(), INTERVAL 80 DAY), NOW()),

  (4,  'INV-NB-004', 8, 3, 6, NULL, 'DLATUVWX', 'N007L5540', 8, NULL, 'storage', 'active',
   '2024-06-01', '2027-06-01', 98000.00, 'Резерв на складе ИТ',
   2, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),

  -- Десктопы
  (5,  'INV-PC-001', 9, 1, 2, NULL, 'MJ0AOPQR', '11T1CTO1WW', 3, NULL, 'workplace', 'active',
   '2023-09-01', '2026-09-01', 58000.00, 'Бухгалтерия, раб. место BUH-001',
   2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),

  (6,  'INV-PC-002', 9, 1, 2, NULL, 'MJ0BSTUV', '11T1CTO1WW', 3, NULL, 'workplace', 'active',
   '2023-09-01', '2026-09-01', 58000.00, 'Бухгалтерия, раб. место BUH-002',
   2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),

  (7,  'INV-PC-003', 9, 1, 2, NULL, 'MJ0CWXYZ', '11T1CTO1WW', 3, NULL, 'workplace', 'active',
   '2023-09-01', '2026-09-01', 58000.00, 'HR, раб. место HR-002',
   2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),

  -- Мониторы
  (8,  'INV-MON-001', 2, 3, 7, NULL, 'CN0DEFGH', 'U2723QE', NULL, 2, 'workplace', 'active',
   '2024-02-10', '2027-02-10', 42000.00, NULL,
   2, DATE_SUB(NOW(), INTERVAL 75 DAY), NOW()),

  (9,  'INV-MON-002', 2, 5, 9, NULL, 'HVZAIJKL', 'LS24D390HL', 3, NULL, 'workplace', 'active',
   '2022-05-15', '2025-05-15', 15000.00, NULL,
   2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),

  -- Принтеры
  (10, 'INV-PRN-001', 3, 2, 4, NULL, 'VNB4MNOP', 'C5F95A', 3, NULL, 'workplace', 'active',
   '2023-06-01', '2026-06-01', 28000.00, 'Принтер бухгалтерии, 2 этаж, каб. 203',
   2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),

  (11, 'INV-PRN-002', 3, 2, 5, NULL, 'VNB4QRST', 'W1A30A', 4, NULL, 'workplace', 'active',
   '2023-06-01', '2026-06-01', 35000.00, 'МФУ технического отдела, 3 этаж, каб. 312',
   2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),

  -- Сетевое оборудование
  (12, 'INV-NET-001', 4, 4, 8, NULL, 'FCW2AUVWX', 'WS-C2960X-24TS-L', 7, NULL, 'workplace', 'active',
   '2024-04-01', '2029-04-01', 95000.00, 'Коммутатор 3 этаж (новый, после замены)',
   2, DATE_SUB(NOW(), INTERVAL 12 DAY), NOW()),

  (13, 'INV-NET-002', 4, 4, 8, NULL, 'FCW1BYZA1', 'WS-C2960X-24TS-L', 10, NULL, 'workplace', 'active',
   '2022-01-15', '2027-01-15', 85000.00, 'Коммутатор серверная, стойка B',
   2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),

  -- Серверное оборудование
  (14, 'INV-SRV-001', 6, 7, 11, NULL, '2040BCDEF', 'DS920+', 9, NULL, 'workplace', 'active',
   '2023-03-01', '2026-03-01', 78000.00, 'NAS хранилище — файловый сервер',
   2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),

  -- ИБП
  (15, 'INV-UPS-001', 7, 6, 10, NULL, 'AS2040GHI', 'SMT3000RMI2U', 9, NULL, 'workplace', 'active',
   '2022-08-01', '2025-08-01', 120000.00, 'ИБП серверной стойки A',
   2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),

  -- Списанное
  (16, 'INV-NET-OLD', 4, NULL, NULL, 'D-Link DGS-1210-24', 'OLD123JKLM', NULL, 8, NULL, 'storage', 'decommissioned',
   '2018-06-01', '2021-06-01', 25000.00, 'Старый коммутатор 2 этаж — заменён на Cisco',
   2, DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY)),

  -- На ремонте
  (17, 'INV-NB-005', 8, 8, 12, NULL, 'FVFG4NOPQ', 'MRX73', NULL, NULL, 'repair', 'in_repair',
   '2024-05-01', '2027-05-01', 210000.00, 'MacBook Pro директора — замена дисплея',
   2, DATE_SUB(NOW(), INTERVAL 5 DAY), NOW());
INSERT INTO `%TABLE_PREFIX%inventory_history`
  (`history_id`,`item_id`,`action`,`old_value`,`new_value`,`staff_id`,`created`)
VALUES
  (1,  1,  'created',        '', 'Создано',                    2, DATE_SUB(NOW(), INTERVAL 60 DAY)),
  (2,  12, 'created',        '', 'Создано',                    2, DATE_SUB(NOW(), INTERVAL 12 DAY)),
  (3,  16, 'status_changed', 'active', 'decommissioned',       2, DATE_SUB(NOW(), INTERVAL 12 DAY)),
  (4,  16, 'moved',          'Серверная 3 этаж', 'Склад ИТ',  2, DATE_SUB(NOW(), INTERVAL 12 DAY)),
  (5,  17, 'status_changed', 'active', 'in_repair',            2, DATE_SUB(NOW(), INTERVAL 5 DAY)),
  (6,  4,  'moved',          '', 'Склад ИТ',                   2, DATE_SUB(NOW(), INTERVAL 30 DAY)),
  (7,  7,  'assigned',       '', 'HR-002',                     2, DATE_SUB(NOW(), INTERVAL 90 DAY));
INSERT INTO `%TABLE_PREFIX%api_tokens`
  (`token_id`,`token`,`name`,`description`,`staff_id`,`token_type`,`permissions`,`ip_whitelist`,`ip_check_enabled`,`rate_limit`,`rate_window`,`is_active`,`expires_at`,`last_used_at`,`total_requests`,`created_at`,`updated_at`)
VALUES
  (1, 'th_test_token_a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6',
   'Тестовый токен', 'Токен для тестирования API v2 (seed data)',
   2, 'permanent', '["tickets.read","tickets.write","tasks.read"]',
   '127.0.0.1,192.168.0.0/16', 0, 1000, 3600, 1,
   DATE_ADD(NOW(), INTERVAL 365 DAY), NULL, 0, NOW(), NOW()),

  (2, 'th_readonly_e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4',
   'Мониторинг', 'Только чтение для дашборда мониторинга',
   2, 'readonly', '["tickets.read","tasks.read","stats.read"]',
   NULL, 0, 500, 3600, 1,
   DATE_ADD(NOW(), INTERVAL 180 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 47, NOW(), NOW());
INSERT INTO `%TABLE_PREFIX%priority_users`
  (`id`,`email`,`description`,`is_active`,`created`,`updated`)
VALUES
  (1, 'director@company.ru',  'Генеральный директор',         1, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
  (2, 'cfo@company.ru',       'Финансовый директор',           1, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
  (3, 'cto@company.ru',       'Технический директор',          1, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW());
INSERT IGNORE INTO `%TABLE_PREFIX%kb_premade`
  (`premade_id`,`dept_id`,`isenabled`,`title`,`answer`,`created`,`updated`)
VALUES
  (3, 0, 1, 'Инструкция по VPN',
   '%name,\r\n\r\nДля подключения к VPN:\r\n1. Установите FortiClient: https://portal.company.ru/vpn\r\n2. Настройте подключение: сервер — vpn.company.ru, порт — 443\r\n3. Логин и пароль — ваши корпоративные учётные данные.\r\n\r\nПодробная инструкция: https://portal.company.ru/kb/vpn-guide\r\n\r\n%signature',
   NOW(), NOW()),

  (4, 1, 1, 'Сброс пароля',
   '%name,\r\n\r\nДля сброса пароля:\r\n1. Перейдите на https://portal.company.ru/password-reset\r\n2. Введите вашу корпоративную почту\r\n3. Следуйте инструкциям в письме\r\n\r\nЕсли письмо не приходит — обратитесь к вашему руководителю для подтверждения личности.\r\n\r\n%signature',
   NOW(), NOW()),

  (5, 0, 1, 'Заказ оборудования',
   '%name,\r\n\r\nДля заказа оборудования:\r\n1. Заполните форму заказа на корпоративном портале\r\n2. Получите согласование руководителя отдела\r\n3. Передайте заявку в ИТ-отдел\r\n\r\nСрок обработки: 3-5 рабочих дней.\r\nСрок доставки: 7-14 рабочих дней (зависит от наличия).\r\n\r\n%signature',
   NOW(), NOW());
INSERT IGNORE INTO `%TABLE_PREFIX%email_banlist`
  (`id`,`email`,`submitter`,`added`)
VALUES
  (2, 'spam@spammer.com',    'Система', DATE_SUB(NOW(), INTERVAL 30 DAY)),
  (3, 'noreply@phishing.ru', 'Иванов И.', DATE_SUB(NOW(), INTERVAL 15 DAY));
INSERT INTO `%TABLE_PREFIX%syslog`
  (`log_id`,`log_type`,`title`,`log`,`logger`,`ip_address`,`created`,`updated`)
VALUES
  (2, 'Debug', 'Staff login',
   'ivanov logged in [192.168.1.1]',
   'ivanov', '192.168.1.1', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),

  (3, 'Debug', 'Staff login',
   'petrova logged in [192.168.1.2]',
   'petrova', '192.168.1.2', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),

  (4, 'Warning', 'Неудачная попытка входа (клиент)',
   'Email: use***@company.ru\nЗапрос #: ***\nIP: 10.0.0.99\nВремя: Apr 5, 2026, 3:15 pm MSK\n\nПопыток #2',
   '', '10.0.0.99', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),

  (5, 'Debug', 'User login',
   'user1@company.ru/384719 logged in [192.168.1.10]',
   '', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(NOW(), INTERVAL 5 HOUR));
