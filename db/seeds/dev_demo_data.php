<?php
declare(strict_types=1);

class SeedDevDemoData
{
    public function run(): bool
    {
        $prefix = defined('TABLE_PREFIX') ? TABLE_PREFIX : ((string)getenv('DB_PREFIX') ?: 'th_');
        $staffPasswd = password_hash('TestPassword1!', PASSWORD_DEFAULT);

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}staff`
             (staff_id, group_id, dept_id, username, firstname, lastname, passwd, email, phone, mobile, signature, isactive, isadmin, isvisible, onvacation, daylight_saving, append_signature, change_passwd, timezone_offset, max_page_size, created, lastlogin)
             VALUES
             (2, 2, 1, 'ivanov',  %s, %s, %s, 'ivanov@tickethub.local',  '+7-495-001-0002', '+7-900-100-0002', %s, 1, 0, 1, 0, 0, 1, 0, 3.0, 25, DATE_SUB(NOW(), INTERVAL 90 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
             (3, 3, 1, 'petrova', %s, %s, %s, 'petrova@tickethub.local', '+7-495-001-0003', '+7-900-100-0003', %s, 1, 0, 1, 0, 0, 1, 0, 3.0, 25, DATE_SUB(NOW(), INTERVAL 80 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
             (4, 3, 2, 'sidorov', %s, %s, %s, 'sidorov@tickethub.local', '+7-495-001-0004', '+7-900-100-0004', %s, 1, 0, 1, 0, 0, 1, 0, 3.0, 25, DATE_SUB(NOW(), INTERVAL 75 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY)),
             (5, 3, 1, 'kozlova', %s, %s, %s, 'kozlova@tickethub.local', '+7-495-001-0005', '+7-900-100-0005', %s, 1, 0, 1, 0, 0, 1, 0, 3.0, 25, DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
             (6, 2, 2, 'volkov',  %s, %s, %s, 'volkov@tickethub.local',  '+7-495-001-0006', '+7-900-100-0006', %s, 1, 0, 1, 0, 0, 1, 0, 3.0, 25, DATE_SUB(NOW(), INTERVAL 70 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY))",
            db_input('Иван'),    db_input('Иванов'),  db_input($staffPasswd), db_input('С уважением, Иван Иванов\nРуководитель тех. отдела'),
            db_input('Анна'),    db_input('Петрова'), db_input($staffPasswd), db_input('С уважением, Анна Петрова'),
            db_input('Пётр'),    db_input('Сидоров'), db_input($staffPasswd), db_input('С уважением, Пётр Сидоров'),
            db_input('Мария'),   db_input('Козлова'), db_input($staffPasswd), db_input('С уважением, Мария Козлова'),
            db_input('Дмитрий'), db_input('Волков'),  db_input($staffPasswd), db_input('С уважением, Дмитрий Волков\nРуководитель отдела продаж')
        ));

        db_query("UPDATE `{$prefix}department` SET manager_id=2 WHERE dept_id=1");
        db_query("UPDATE `{$prefix}department` SET manager_id=6 WHERE dept_id=2");

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}department` (dept_id, tpl_id, email_id, autoresp_email_id, manager_id, dept_name, dept_signature, ispublic, ticket_auto_response, message_auto_response, can_append_signature, updated, created)
             VALUES (3, 0, 1, 0, 0, %s, %s, 0, 0, 0, 1, NOW(), NOW())",
            db_input('Администрация'),
            db_input('С уважением, Администрация')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}help_topic` (topic_id, isactive, noautoresp, priority_id, dept_id, topic, created, updated)
             VALUES
             (3, 1, 0, 2, 1, %s, NOW(), NOW()),
             (4, 1, 0, 1, 1, %s, NOW(), NOW()),
             (5, 1, 0, 3, 2, %s, NOW(), NOW()),
             (6, 1, 0, 2, 1, %s, NOW(), NOW()),
             (7, 1, 0, 2, 2, %s, NOW(), NOW())",
            db_input('Установка ПО'),
            db_input('Сбой оборудования'),
            db_input('Запрос на обслуживание'),
            db_input('Настройка сети'),
            db_input('Вопрос по лицензиям')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}ticket`
             (ticket_id,ticketID,dept_id,priority_id,topic_id,staff_id,andstaffs_id,email,name,subject,helptopic,phone,ip_address,status,source,isoverdue,isanswered,duedate,reopened,closed,lastmessage,lastresponse,created,updated)
             VALUES
             (2,  384719, 1, 3, 1, 2, NULL,       %s, %s, %s, %s, %s, '192.168.1.10',  'open',   'Web',   0, 0, DATE_ADD(NOW(), INTERVAL 2 DAY),  NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 HOUR),  DATE_SUB(NOW(), INTERVAL 1 HOUR),  DATE_SUB(NOW(), INTERVAL 5 HOUR),  NOW()),
             (3,  294057, 1, 4, 4, 3, '5',        %s, %s, %s, %s, %s, '192.168.1.22',  'open',   'Email', 1, 0, DATE_SUB(NOW(), INTERVAL 1 DAY),  NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR),  NULL,                             DATE_SUB(NOW(), INTERVAL 26 HOUR), NOW()),
             (4,  518432, 1, 2, 3, 0, NULL,        %s, %s, %s, %s, %s, '192.168.1.35',  'open',   'Web',   0, 0, DATE_ADD(NOW(), INTERVAL 5 DAY),  NULL, NULL, DATE_SUB(NOW(), INTERVAL 12 HOUR), NULL,                             DATE_SUB(NOW(), INTERVAL 12 HOUR), NOW()),
             (5,  673201, 2, 2, 5, 4, NULL,        %s, %s, %s, %s, %s, '192.168.1.48',  'open',   'Phone', 0, 1, DATE_ADD(NOW(), INTERVAL 3 DAY),  NULL, NULL, DATE_SUB(NOW(), INTERVAL 1 DAY),   DATE_SUB(NOW(), INTERVAL 20 HOUR), DATE_SUB(NOW(), INTERVAL 2 DAY),  NOW()),
             (6,  891245, 1, 3, 6, 5, NULL,        %s, %s, %s, %s, %s, '10.0.0.50',     'open',   'Web',   0, 0, DATE_ADD(NOW(), INTERVAL 1 DAY),  NULL, NULL, DATE_SUB(NOW(), INTERVAL 45 MINUTE),NULL,                             DATE_SUB(NOW(), INTERVAL 45 MINUTE),NOW()),
             (7,  102384, 1, 2, 1, 2, '3',        %s, %s, %s, %s, %s, '192.168.1.67',  'open',   'Email', 0, 1, DATE_ADD(NOW(), INTERVAL 4 DAY),  NULL, NULL, DATE_SUB(NOW(), INTERVAL 6 HOUR),  DATE_SUB(NOW(), INTERVAL 4 HOUR),  DATE_SUB(NOW(), INTERVAL 8 HOUR),  NOW()),
             (8,  445678, 2, 1, 7, 0, NULL,        %s, %s, %s, %s, NULL,'192.168.1.80',  'open',   'Web',   0, 0, NULL,                             NULL, NULL, DATE_SUB(NOW(), INTERVAL 30 MINUTE),NULL,                             DATE_SUB(NOW(), INTERVAL 30 MINUTE),NOW()),
             (9,  557891, 1, 2, 1, 3, NULL,        %s, %s, %s, %s, %s, '192.168.1.91',  'closed', 'Web',   0, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY),  DATE_SUB(NOW(), INTERVAL 4 DAY),  DATE_SUB(NOW(), INTERVAL 3 DAY),   DATE_SUB(NOW(), INTERVAL 7 DAY),  DATE_SUB(NOW(), INTERVAL 3 DAY)),
             (10, 663012, 1, 3, 4, 2, NULL,        %s, %s, %s, %s, %s, '192.168.1.104', 'closed', 'Phone', 0, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 5 DAY),  DATE_SUB(NOW(), INTERVAL 6 DAY),  DATE_SUB(NOW(), INTERVAL 5 DAY),   DATE_SUB(NOW(), INTERVAL 8 DAY),  DATE_SUB(NOW(), INTERVAL 5 DAY)),
             (11, 774123, 1, 2, 3, 5, NULL,        %s, %s, %s, %s, %s, '192.168.1.115', 'closed', 'Web',   0, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY),  DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
             (12, 885234, 2, 2, 5, 4, NULL,        %s, %s, %s, %s, %s, '192.168.1.128', 'closed', 'Email', 0, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 8 DAY),  DATE_SUB(NOW(), INTERVAL 9 DAY),  DATE_SUB(NOW(), INTERVAL 8 DAY),   DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
             (13, 996345, 1, 4, 1, 2, '3,5',      %s, %s, %s, %s, %s, '192.168.1.5',   'closed', 'Phone', 0, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_SUB(NOW(), INTERVAL 16 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY),  DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY)),
             (14, 107456, 1, 1, 6, 5, NULL,        %s, %s, %s, %s, %s, '192.168.1.140', 'closed', 'Web',   0, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 21 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY),  DATE_SUB(NOW(), INTERVAL 22 DAY), DATE_SUB(NOW(), INTERVAL 20 DAY)),
             (15, 218567, 2, 2, 7, 6, NULL,        %s, %s, %s, %s, %s, '192.168.1.155', 'closed', 'Web',   0, 1, NULL, NULL, DATE_SUB(NOW(), INTERVAL 6 DAY),  DATE_SUB(NOW(), INTERVAL 7 DAY),  DATE_SUB(NOW(), INTERVAL 6 DAY),   DATE_SUB(NOW(), INTERVAL 9 DAY),  DATE_SUB(NOW(), INTERVAL 6 DAY)),
             (16, 329678, 1, 3, 1, 3, NULL,        %s, %s, %s, %s, %s, '192.168.1.170', 'open',   'Email', 1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY),  NULL, NULL, DATE_SUB(NOW(), INTERVAL 3 DAY),  DATE_SUB(NOW(), INTERVAL 2 DAY),   DATE_SUB(NOW(), INTERVAL 5 DAY),  NOW()),
             (17, 430789, 1, 2, 3, 0, NULL,        %s, %s, %s, %s, NULL,'192.168.1.185', 'open',   'Web',   0, 0, DATE_ADD(NOW(), INTERVAL 7 DAY),  NULL, NULL, DATE_SUB(NOW(), INTERVAL 2 HOUR),  NULL,                             DATE_SUB(NOW(), INTERVAL 2 HOUR),  NOW()),
             (18, 541890, 1, 3, 1, 2, NULL,        %s, %s, %s, %s, %s, '192.168.1.10',  'open',   'Web',   0, 0, DATE_ADD(NOW(), INTERVAL 1 DAY),  DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL, DATE_SUB(NOW(), INTERVAL 4 DAY), NOW()),
             (19, 652901, 2, 1, 5, 4, NULL,        %s, %s, %s, %s, %s, '192.168.2.10',  'open',   'Phone', 0, 1, NULL,                             NULL, NULL, DATE_SUB(NOW(), INTERVAL 4 HOUR),  DATE_SUB(NOW(), INTERVAL 2 HOUR),  DATE_SUB(NOW(), INTERVAL 6 HOUR),  NOW())",
            db_input('user1@company.ru'), db_input('Алексей Смирнов'),   db_input('Не работает 1С после обновления'),        db_input('Техническая проблема'),   db_input('+7-495-200-0001'),
            db_input('user2@company.ru'), db_input('Ольга Кузнецова'),   db_input('Критический сбой сервера печати'),        db_input('Сбой оборудования'),       db_input('+7-495-200-0002'),
            db_input('user3@company.ru'), db_input('Дмитрий Попов'),     db_input('Установить Adobe Acrobat Pro'),           db_input('Установка ПО'),            db_input('+7-495-200-0003'),
            db_input('user4@company.ru'), db_input('Елена Соколова'),    db_input('Заказ картриджей для HP LaserJet'),       db_input('Запрос на обслуживание'),  db_input('+7-495-200-0004'),
            db_input('user5@company.ru'), db_input('Сергей Лебедев'),    db_input('VPN не подключается из дома'),            db_input('Настройка сети'),          db_input('+7-495-200-0005'),
            db_input('user6@company.ru'), db_input('Наталья Новикова'),  db_input('Outlook не синхронизирует почту'),        db_input('Техническая проблема'),   db_input('+7-495-200-0006'),
            db_input('user7@company.ru'), db_input('Андрей Козлов'),     db_input('Продление лицензии MS Office'),           db_input('Вопрос по лицензиям'),
            db_input('user8@company.ru'), db_input('Марина Федорова'),   db_input('Не открывается Excel файл'),              db_input('Техническая проблема'),   db_input('+7-495-200-0008'),
            db_input('user9@company.ru'), db_input('Игорь Морозов'),     db_input('Не включается компьютер'),                db_input('Сбой оборудования'),       db_input('+7-495-200-0009'),
            db_input('user10@company.ru'),db_input('Татьяна Волкова'),   db_input('Установка антивируса Kaspersky'),         db_input('Установка ПО'),            db_input('+7-495-200-0010'),
            db_input('user11@company.ru'),db_input('Владимир Соловьёв'), db_input('Заказ нового монитора'),                  db_input('Запрос на обслуживание'),  db_input('+7-495-200-0011'),
            db_input('user12@company.ru'),db_input('Антон Петров'),      db_input('Массовый сбой сети на 3 этаже'),          db_input('Техническая проблема'),   db_input('+7-495-200-0012'),
            db_input('user13@company.ru'),db_input('Юлия Белова'),       db_input('Как подключить сетевой диск'),            db_input('Настройка сети'),          db_input('+7-495-200-0013'),
            db_input('user14@company.ru'),db_input('Роман Егоров'),      db_input('Лицензия AutoCAD истекает'),              db_input('Вопрос по лицензиям'),     db_input('+7-495-200-0014'),
            db_input('user15@company.ru'),db_input('Ксения Давыдова'),   db_input('Ошибка при печати из SAP'),               db_input('Техническая проблема'),   db_input('+7-495-200-0015'),
            db_input('user16@company.ru'),db_input('Павел Тихонов'),     db_input('Установить Zoom на рабочий ПК'),          db_input('Установка ПО'),
            db_input('user1@company.ru'), db_input('Алексей Смирнов'),   db_input('Повторный сбой 1С - проблема не решена'), db_input('Техническая проблема'),   db_input('+7-495-200-0001'),
            db_input('user17@company.ru'),db_input('Виктория Орлова'),   db_input('Консультация по выбору ноутбука'),        db_input('Запрос на обслуживание'),  db_input('+7-495-200-0017')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}ticket_message`
             (msg_id, ticket_id, messageId, message, headers, source, ip_address, created)
             VALUES
             (2,  2,  NULL, %s, NULL, 'Web',   '192.168.1.10',  DATE_SUB(NOW(), INTERVAL 5 HOUR)),
             (3,  2,  NULL, %s, NULL, 'Web',   '192.168.1.10',  DATE_SUB(NOW(), INTERVAL 3 HOUR)),
             (4,  3,  '<msg001@mail.company.ru>', %s, NULL, 'Email', '192.168.1.22', DATE_SUB(NOW(), INTERVAL 26 HOUR)),
             (5,  3,  '<msg002@mail.company.ru>', %s, NULL, 'Email', '192.168.1.22', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
             (6,  4,  NULL, %s, NULL, 'Web',   '192.168.1.35',  DATE_SUB(NOW(), INTERVAL 12 HOUR)),
             (7,  5,  NULL, %s, NULL, 'Phone', '192.168.1.48',  DATE_SUB(NOW(), INTERVAL 2 DAY)),
             (8,  6,  NULL, %s, NULL, 'Web',   '10.0.0.50',     DATE_SUB(NOW(), INTERVAL 45 MINUTE)),
             (9,  7,  '<msg003@mail.company.ru>', %s, NULL, 'Email', '192.168.1.67', DATE_SUB(NOW(), INTERVAL 8 HOUR)),
             (10, 7,  '<msg004@mail.company.ru>', %s, NULL, 'Email', '192.168.1.67', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
             (11, 8,  NULL, %s, NULL, 'Web',   '192.168.1.80',  DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
             (12, 9,  NULL, %s, NULL, 'Web',   '192.168.1.91',  DATE_SUB(NOW(), INTERVAL 7 DAY)),
             (13, 10, NULL, %s, NULL, 'Phone', '192.168.1.104', DATE_SUB(NOW(), INTERVAL 8 DAY)),
             (14, 13, NULL, %s, NULL, 'Phone', '192.168.1.5',   DATE_SUB(NOW(), INTERVAL 18 DAY)),
             (15, 16, '<msg005@mail.company.ru>', %s, NULL, 'Email', '192.168.1.170', DATE_SUB(NOW(), INTERVAL 5 DAY)),
             (16, 17, NULL, %s, NULL, 'Web',   '192.168.1.185', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
             (17, 18, NULL, %s, NULL, 'Web',   '192.168.1.10',  DATE_SUB(NOW(), INTERVAL 1 DAY)),
             (18, 19, NULL, %s, NULL, 'Phone', '192.168.2.10',  DATE_SUB(NOW(), INTERVAL 6 HOUR))",
            db_input('Добрый день! После последнего обновления Windows перестала запускаться 1С Предприятие 8.3. При запуске выдаёт ошибку "Версия компоненты не совпадает". Переустановка не помогла. Рабочая станция: WS-FIN-012'),
            db_input('Уточнение: ошибка появляется именно при запуске конфигурации "Бухгалтерия предприятия". Тонкий клиент запускается нормально.'),
            db_input('Здравствуйте! Принтеры на 2 этаже полностью перестали работать. Сервер печати (PRN-SRV-01) не отвечает на ping. Перезагрузка по питанию не помогла. Затронуты все 12 принтеров на этаже.'),
            db_input('Дополнительно: светодиод на сетевой карте сервера не горит. Возможно проблема с сетевым адаптером.'),
            db_input('Добрый день! Прошу установить Adobe Acrobat Pro DC на мою рабочую станцию WS-MKT-003.'),
            db_input("Необходимо заказать картриджи:\n- HP CF226X (для HP LaserJet Pro M402) — 3 шт.\n- HP CF230A (для HP LaserJet Pro M203) — 2 шт."),
            db_input('Не могу подключиться к корпоративному VPN из дома. При подключении через FortiClient выдаёт ошибку "Unable to establish the VPN connection".'),
            db_input('Outlook перестал синхронизировать почту с Exchange сервером. Отправка и получение зависает. Профиль пересоздавала — не помогает.'),
            db_input('Спасибо за рекомендацию. Очистка кэша помогла частично — входящие появились, но папка "Отправленные" пуста.'),
            db_input('Добрый день! Лицензия Microsoft Office 365 на нашем отделе (5 сотрудников) истекает через 2 недели. Прошу продлить подписку.'),
            db_input('Не открывается файл Excel с отчётом за квартал. При открытии выдаёт ошибку "Файл повреждён и не может быть открыт".'),
            db_input('Компьютер на рабочем месте HR-002 не включается. При нажатии кнопки питания ничего не происходит.'),
            db_input('СРОЧНО! На 3 этаже пропал интернет и доступ к сетевым ресурсам. Затронуты примерно 40 рабочих мест. Коммутатор в серверной мигает красным.'),
            db_input('При печати из SAP транзакции ME23N выдаёт ошибку "Spool request error". Проблема появилась после миграции на новый сервер печати.'),
            db_input('Здравствуйте, прошу установить Zoom на мой компьютер WS-HR-007.'),
            db_input('Проблема с 1С повторилась. После вашего исправления проработала 2 дня и снова та же ошибка.'),
            db_input("Здравствуйте! Мне необходим ноутбук для командировок. Требования:\n- Экран 14-15 дюймов\n- Минимум 16 ГБ ОЗУ\n- SSD от 512 ГБ\nБюджет: до 120 000 руб.")
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}ticket_response`
             (response_id, msg_id, ticket_id, staff_id, staff_name, response, ip_address, created)
             VALUES
             (1, 2,  2,  2, %s, %s, '192.168.1.1', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
             (2, 7,  5,  4, %s, %s, '192.168.1.1', DATE_SUB(NOW(), INTERVAL 20 HOUR)),
             (3, 9,  7,  2, %s, %s, '192.168.1.1', DATE_SUB(NOW(), INTERVAL 4 HOUR)),
             (4, 12, 9,  3, %s, %s, '192.168.1.1', DATE_SUB(NOW(), INTERVAL 3 DAY)),
             (5, 13, 10, 2, %s, %s, '192.168.1.1', DATE_SUB(NOW(), INTERVAL 5 DAY)),
             (6, 14, 13, 2, %s, %s, '192.168.1.1', DATE_SUB(NOW(), INTERVAL 15 DAY)),
             (7, 15, 16, 3, %s, %s, '192.168.1.1', DATE_SUB(NOW(), INTERVAL 2 DAY)),
             (8, 18, 19, 4, %s, %s, '192.168.1.1', DATE_SUB(NOW(), INTERVAL 2 HOUR))",
            db_input('Иванов И.'),  db_input('Алексей, добрый день! Ошибка связана с несовместимостью обновления Windows KB5034441 и платформы 1С 8.3.24. Подключусь к вашему ПК удалённо в течение часа.'),
            db_input('Сидоров П.'), db_input("Елена, здравствуйте! Заказ на картриджи оформлен:\n- HP CF226X × 3 шт.\n- HP CF230A × 2 шт.\nОриентировочный срок доставки: 2-3 рабочих дня."),
            db_input('Иванов И.'),  db_input("Наталья, добрый день! Попробуйте: 1. Закройте Outlook. 2. Win+R → outlook.exe /resetnavpane. 3. Очистите кэш: %localappdata%\\Microsoft\\Outlook\\RoamCache"),
            db_input('Петрова А.'), db_input('Марина, добрый день! Файл удалось восстановить через встроенную функцию Excel: Файл → Открыть → Открыть и восстановить. Восстановленный файл отправлен вам на почту.'),
            db_input('Иванов И.'),  db_input('Диагностика завершена. Причина — вышел из строя блок питания (Seasonic 550W). Заменён на новый из склада. Все данные на дисках сохранены.'),
            db_input('Иванов И.'),  db_input('Проблема локализована и устранена. Причина: коммутатор Cisco Catalyst 2960X на 3 этаже перегрелся. После охлаждения и очистки перезагружен. Все 40 рабочих мест восстановлены.'),
            db_input('Петрова А.'), db_input('Ксения, здравствуйте! Проблема связана с некорректной настройкой спул-системы SAP после миграции. Обновили параметры вывода в транзакции SPAD.'),
            db_input('Сидоров П.'), db_input("Виктория, добрый день! Подобрал 3 варианта:\n1. Lenovo ThinkPad T14s Gen 4 — 105 000 руб.\n2. HP EliteBook 840 G10 — 112 000 руб.\n3. Dell Latitude 5540 — 98 000 руб.")
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}ticket_note`
             (note_id, ticket_id, staff_id, source, title, note, created)
             VALUES
             (1, 3,  3, %s, %s, %s, DATE_SUB(NOW(), INTERVAL 20 HOUR)),
             (2, 10, 2, %s, %s, %s, DATE_SUB(NOW(), INTERVAL 6 DAY)),
             (3, 13, 2, %s, %s, %s, DATE_SUB(NOW(), INTERVAL 16 DAY)),
             (4, 16, 3, %s, %s, %s, DATE_SUB(NOW(), INTERVAL 4 DAY)),
             (5, 18, 2, %s, %s, %s, DATE_SUB(NOW(), INTERVAL 1 DAY))",
            db_input('Внутренняя заметка'), db_input('Диагностика сервера'), db_input('Сервер PRN-SRV-01: сетевой адаптер Intel I350 неисправен. Заказал замену — артикул #HW-2024-0091. Временное решение: подключил USB-Ethernet адаптер.'),
            db_input('Внутренняя заметка'), db_input('Замена БП'), db_input('Блок питания Seasonic SSR-550FX — неисправен. Взят из ЗИП на складе (инв. номер SP-PSU-007). Необходимо заказать замену в ЗИП.'),
            db_input('Внутренняя заметка'), db_input('Анализ инцидента'), db_input('Инцидент INC-2024-031. Причина: перегрев коммутатора. Серверная комната 3 этажа — кондиционер работает некорректно. Подал заявку в АХО.'),
            db_input('Внутренняя заметка'), db_input('Эскалация'), db_input('Проблема требует привлечения SAP Basis администратора. Связалась с подрядчиком, ожидаю ответ.'),
            db_input('Внутренняя заметка'), db_input('Повторная проблема 1С'), db_input('Клиент обратился повторно. Предыдущее решение — временное. Необходима полная переустановка платформы. Согласовать с бухгалтерией окно обслуживания.')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}task_boards`
             (board_id, board_name, board_type, dept_id, description, color, is_archived, created_by, created, updated)
             VALUES
             (1, %s, 'department', 1, %s, '#3498db', 0, 2, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
             (2, %s, 'project',    0, %s, '#e74c3c', 0, 2, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
             (3, %s, 'department', 2, %s, '#2ecc71', 0, 6, DATE_SUB(NOW(), INTERVAL 45 DAY), NOW())",
            db_input('Тех. отдел — Текущие задачи'), db_input('Операционные задачи технического отдела'),
            db_input('Редизайн корп. портала'),       db_input('Проект по модернизации внутреннего портала компании'),
            db_input('Отдел продаж — Закупки'),       db_input('Управление заказами и закупками оборудования')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}task_lists`
             (list_id, board_id, list_name, status, list_order, is_archived, created, updated)
             VALUES
             (1,  1, %s, 'open',         0, 0, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
             (2,  1, %s, 'in_progress',  1, 0, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
             (3,  1, %s, 'review',       2, 0, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
             (4,  1, %s, 'completed',    3, 0, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
             (5,  2, %s, 'open',         0, 0, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
             (6,  2, %s, 'in_progress',  1, 0, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
             (7,  2, %s, 'review',       2, 0, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
             (8,  2, %s, 'completed',    3, 0, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
             (9,  3, %s, 'open',         0, 0, DATE_SUB(NOW(), INTERVAL 45 DAY), NOW()),
             (10, 3, %s, 'in_progress',  1, 0, DATE_SUB(NOW(), INTERVAL 45 DAY), NOW()),
             (11, 3, %s, 'review',       2, 0, DATE_SUB(NOW(), INTERVAL 45 DAY), NOW()),
             (12, 3, %s, 'completed',    3, 0, DATE_SUB(NOW(), INTERVAL 45 DAY), NOW())",
            db_input('Бэклог'), db_input('В работе'), db_input('На проверке'), db_input('Выполнено'),
            db_input('To Do'), db_input('In Progress'), db_input('Review'), db_input('Done'),
            db_input('Новые заявки'), db_input('Согласование'), db_input('Заказано'), db_input('Получено')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}tasks`
             (task_id,board_id,list_id,parent_task_id,ticket_id,title,description,task_type,priority,status,start_date,end_date,deadline,time_estimate,position,created_by,created,updated,completed_date,is_archived)
             VALUES
             (1,  1, 2, NULL, NULL, %s, %s, 'action', 'normal',   'in_progress', DATE_SUB(NOW(), INTERVAL 3 DAY),  NULL, DATE_ADD(NOW(), INTERVAL 4 DAY),   480, 0, 2, DATE_SUB(NOW(), INTERVAL 5 DAY),  NOW(), NULL, 0),
             (2,  1, 2, NULL, 3,   %s, %s, 'action', 'urgent',   'in_progress', DATE_SUB(NOW(), INTERVAL 1 DAY),  NULL, DATE_ADD(NOW(), INTERVAL 1 DAY),   120, 1, 3, DATE_SUB(NOW(), INTERVAL 1 DAY),  NOW(), NULL, 0),
             (3,  1, 1, NULL, NULL, %s, %s, 'action', 'high',     'open',        NULL, NULL, DATE_ADD(NOW(), INTERVAL 10 DAY), 240, 0, 2, DATE_SUB(NOW(), INTERVAL 7 DAY),  NOW(), NULL, 0),
             (4,  1, 4, NULL, NULL, %s, %s, 'action', 'normal',   'completed',   DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), 180, 0, 2, DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), 0),
             (5,  1, 3, NULL, NULL, %s, %s, 'action', 'normal',   'review',      DATE_SUB(NOW(), INTERVAL 5 DAY),  NULL, DATE_ADD(NOW(), INTERVAL 2 DAY),   360, 0, 5, DATE_SUB(NOW(), INTERVAL 10 DAY), NOW(), NULL, 0),
             (6,  1, 1, NULL, NULL, %s, %s, 'action', 'low',      'open',        NULL, NULL, NULL,                              60, 1, 2, DATE_SUB(NOW(), INTERVAL 2 DAY),  NOW(), NULL, 0),
             (7,  2, 6, NULL, NULL, %s, %s, 'action', 'high',     'in_progress', DATE_SUB(NOW(), INTERVAL 10 DAY), NULL, DATE_ADD(NOW(), INTERVAL 5 DAY),  1200, 0, 5, DATE_SUB(NOW(), INTERVAL 15 DAY), NOW(), NULL, 0),
             (8,  2, 5, NULL, NULL, %s, %s, 'action', 'high',     'open',        NULL, NULL, DATE_ADD(NOW(), INTERVAL 20 DAY),  960, 0, 2, DATE_SUB(NOW(), INTERVAL 15 DAY), NOW(), NULL, 0),
             (9,  2, 8, NULL, NULL, %s, %s, 'meeting','normal',   'completed',   DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 15 DAY), 480, 0, 2, DATE_SUB(NOW(), INTERVAL 28 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 18 DAY), 0),
             (10, 2, 5, NULL, NULL, %s, %s, 'action', 'normal',   'open',        NULL, NULL, DATE_ADD(NOW(), INTERVAL 30 DAY),  720, 1, 3, DATE_SUB(NOW(), INTERVAL 10 DAY), NOW(), NULL, 0),
             (11, 3, 10,NULL, NULL, %s, %s, 'action', 'normal',   'in_progress', DATE_SUB(NOW(), INTERVAL 3 DAY),  NULL, DATE_ADD(NOW(), INTERVAL 7 DAY),    60, 0, 4, DATE_SUB(NOW(), INTERVAL 5 DAY),  NOW(), NULL, 0),
             (12, 3, 11,NULL, NULL, %s, %s, 'action', 'normal',   'review',      DATE_SUB(NOW(), INTERVAL 5 DAY),  NULL, DATE_ADD(NOW(), INTERVAL 2 DAY),    30, 0, 4, DATE_SUB(NOW(), INTERVAL 7 DAY),  NOW(), NULL, 0),
             (13, 3, 12,NULL, NULL, %s, %s, 'action', 'normal',   'completed',   DATE_SUB(NOW(), INTERVAL 20 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY), 240, 0, 4, DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY), 0),
             (14, 3, 9, NULL, 19,  %s, %s, 'action', 'normal',   'open',        NULL, NULL, DATE_ADD(NOW(), INTERVAL 5 DAY),   60, 0, 4, DATE_SUB(NOW(), INTERVAL 4 HOUR), NOW(), NULL, 0)",
            db_input('Обновить антивирусные базы на всех ПК'), db_input('Обновить Kaspersky Endpoint Security до последней версии на всех рабочих станциях (87 шт.)'),
            db_input('Замена сетевого адаптера PRN-SRV-01'),   db_input('Сервер печати PRN-SRV-01 — неисправен сетевой адаптер Intel I350. Связано с заявкой #294057.'),
            db_input('Настроить бэкап NAS хранилища'),         db_input('Настроить ежедневное резервное копирование Synology NAS на внешний диск.'),
            db_input('Замена коммутатора на 2 этаже'),          db_input('Заменён старый D-Link DGS-1210 на Cisco Catalyst 2960X. Всё подключено, проверено, работает.'),
            db_input('Ревизия учётных записей AD'),             db_input('Проверить все учётные записи Active Directory. Заблокировать неактивные >90 дней.'),
            db_input('Обновить прошивку на UPS'),               db_input('Обновить firmware на ИБП APC Smart-UPS 3000 в серверной. Текущая версия: 2.1, доступна: 3.0.'),
            db_input('Разработка макетов новых страниц'),       db_input('Создать макеты в Figma для: главная, каталог услуг, база знаний, профиль сотрудника.'),
            db_input('Интеграция с Active Directory (SSO)'),    db_input('Реализовать Single Sign-On через LDAP/AD. Пользователи должны входить по корпоративным учёткам.'),
            db_input('Сбор требований от отделов'),             db_input('Провести интервью со всеми руководителями отделов. Собрать пожелания по функциональности нового портала.'),
            db_input('Миграция контента со старого портала'),   db_input('Перенести все актуальные документы, новости и справочные материалы на новую платформу.'),
            db_input('Заказ 5 мониторов Dell 27"'),             db_input('Заказать мониторы Dell U2723QE для нового офиса на 4 этаже. Бюджет согласован.'),
            db_input('Заказ картриджей — ежемесячный'),         db_input('Ежемесячный заказ расходных материалов для принтеров. Заказ отправлен поставщику.'),
            db_input('Получены ноутбуки Lenovo ThinkPad ×3'),   db_input('Три ноутбука Lenovo ThinkPad T14s получены, проверены, настроены. Переданы сотрудникам.'),
            db_input('Подбор ноутбука для Орловой В.'),         db_input('Связано с заявкой #652901. Подобрать и согласовать модель ноутбука.')
        ));

        db_query(
            "INSERT IGNORE INTO `{$prefix}task_assignees`
             (assignment_id, task_id, staff_id, role, assigned_date)
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
             (15, 14, 6, 'watcher',  DATE_SUB(NOW(), INTERVAL 4 HOUR))"
        );

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}task_tags`
             (tag_id, tag_name, tag_color, board_id, created)
             VALUES
             (1, %s, '#e74c3c', 1, DATE_SUB(NOW(), INTERVAL 60 DAY)),
             (2, %s, '#3498db', 1, DATE_SUB(NOW(), INTERVAL 60 DAY)),
             (3, %s, '#e67e22', 1, DATE_SUB(NOW(), INTERVAL 60 DAY)),
             (4, %s, '#9b59b6', 2, DATE_SUB(NOW(), INTERVAL 30 DAY)),
             (5, %s, '#2ecc71', 2, DATE_SUB(NOW(), INTERVAL 30 DAY)),
             (6, %s, '#f39c12', 3, DATE_SUB(NOW(), INTERVAL 45 DAY)),
             (7, %s, '#1abc9c', 3, DATE_SUB(NOW(), INTERVAL 45 DAY))",
            db_input('Срочно'), db_input('Инфраструктура'), db_input('Безопасность'),
            db_input('UI/UX'), db_input('Backend'), db_input('Согласование'), db_input('Бюджет')
        ));

        db_query(
            "INSERT IGNORE INTO `{$prefix}task_tag_associations` (association_id, task_id, tag_id)
             VALUES
             (1, 2, 1), (2, 2, 2), (3, 3, 2), (4, 3, 3),
             (5, 5, 3), (6, 7, 4), (7, 8, 5), (8, 11, 6),
             (9, 11, 7), (10, 14, 7)"
        );

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}task_comments`
             (comment_id, task_id, staff_id, comment_text, created)
             VALUES
             (1, 1,  5, %s, DATE_SUB(NOW(), INTERVAL 1 DAY)),
             (2, 1,  2, %s, DATE_SUB(NOW(), INTERVAL 23 HOUR)),
             (3, 2,  3, %s, DATE_SUB(NOW(), INTERVAL 18 HOUR)),
             (4, 7,  5, %s, DATE_SUB(NOW(), INTERVAL 2 DAY)),
             (5, 7,  2, %s, DATE_SUB(NOW(), INTERVAL 1 DAY)),
             (6, 11, 4, %s, DATE_SUB(NOW(), INTERVAL 2 DAY)),
             (7, 11, 6, %s, DATE_SUB(NOW(), INTERVAL 1 DAY))",
            db_input('Обновил 34 из 87 станций. Остальные — завтра и послезавтра.'),
            db_input('Хорошо. Приоритет — бухгалтерия и отдел кадров.'),
            db_input('Сетевой адаптер Intel I350-T2 заказан, ожидаем завтра. Временно работает через USB-Ethernet.'),
            db_input('Макет главной страницы готов, загружен в Figma. Жду ревью.'),
            db_input('Посмотрел. Нужно добавить блок с последними новостями компании на главную.'),
            db_input('Отправил запрос поставщику. Ожидаю коммерческое предложение.'),
            db_input('Бюджет на мониторы согласован с финансовым директором.')
        ));

        db_query(
            "INSERT IGNORE INTO `{$prefix}task_time_logs`
             (log_id, task_id, staff_id, time_spent, log_date, notes)
             VALUES
             (1, 1, 5, 180, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Обновление станций: корпус A, 1-2 этаж'),
             (2, 1, 5, 210, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Обновление станций: корпус A, 3-4 этаж'),
             (3, 2, 3,  60, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Диагностика сервера печати, подключение временного адаптера'),
             (4, 4, 2, 120, DATE_SUB(NOW(), INTERVAL 13 DAY),'Монтаж и настройка коммутатора'),
             (5, 4, 2,  45, DATE_SUB(NOW(), INTERVAL 12 DAY),'Тестирование, проверка всех портов'),
             (6, 7, 5, 360, DATE_SUB(NOW(), INTERVAL 5 DAY), 'Дизайн макета главной страницы'),
             (7, 7, 5, 240, DATE_SUB(NOW(), INTERVAL 3 DAY), 'Дизайн макета каталога услуг'),
             (8, 9, 2, 120, DATE_SUB(NOW(), INTERVAL 22 DAY),'Интервью: тех. отдел, бухгалтерия'),
             (9, 9, 2,  90, DATE_SUB(NOW(), INTERVAL 20 DAY),'Интервью: HR, маркетинг, продажи')"
        );

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}task_activity_log`
             (activity_id, task_id, staff_id, activity_type, activity_data, created)
             VALUES
             (1,  1,  2, 'created',        %s, DATE_SUB(NOW(), INTERVAL 5 DAY)),
             (2,  1,  2, 'assigned',       %s, DATE_SUB(NOW(), INTERVAL 3 DAY)),
             (3,  1,  5, 'status_changed', %s, DATE_SUB(NOW(), INTERVAL 3 DAY)),
             (4,  2,  3, 'created',        %s, DATE_SUB(NOW(), INTERVAL 1 DAY)),
             (5,  4,  2, 'created',        %s, DATE_SUB(NOW(), INTERVAL 20 DAY)),
             (6,  4,  2, 'status_changed', %s, DATE_SUB(NOW(), INTERVAL 12 DAY)),
             (7,  4,  2, 'completed',      %s, DATE_SUB(NOW(), INTERVAL 12 DAY)),
             (8,  7,  5, 'created',        %s, DATE_SUB(NOW(), INTERVAL 15 DAY)),
             (9,  7,  5, 'status_changed', %s, DATE_SUB(NOW(), INTERVAL 10 DAY)),
             (10, 9,  2, 'completed',      %s, DATE_SUB(NOW(), INTERVAL 18 DAY)),
             (11, 13, 4, 'completed',      %s, DATE_SUB(NOW(), INTERVAL 10 DAY))",
            db_input('{"title":"Обновить антивирусные базы на всех ПК"}'),
            db_input('{"staff_id":5,"staff_name":"Козлова М."}'),
            db_input('{"from":"open","to":"in_progress"}'),
            db_input('{"title":"Замена сетевого адаптера PRN-SRV-01","ticket_id":3}'),
            db_input('{"title":"Замена коммутатора на 2 этаже"}'),
            db_input('{"from":"in_progress","to":"completed"}'),
            db_input('{}'),
            db_input('{"title":"Разработка макетов новых страниц"}'),
            db_input('{"from":"open","to":"in_progress"}'),
            db_input('{}'),
            db_input('{}')
        ));

        db_query(
            "INSERT IGNORE INTO `{$prefix}task_board_permissions` (permission_id, board_id, staff_id, dept_id, permission_level)
             VALUES (1, 1, NULL, 1, 'edit'), (2, 2, 2, NULL, 'admin'), (3, 2, 3, NULL, 'edit'), (4, 2, 5, NULL, 'edit'), (5, 3, NULL, 2, 'edit')"
        );

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}task_saved_filters` (filter_id, staff_id, filter_name, filter_config, is_default, created)
             VALUES
             (1, 2, %s, %s, 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),
             (2, 5, %s, %s, 0, DATE_SUB(NOW(), INTERVAL 15 DAY))",
            db_input('Мои срочные задачи'), db_input('{"priority":"urgent","assignee":"me","status":["open","in_progress"]}'),
            db_input('Задачи на ревью'),    db_input('{"status":["review"],"board_id":1}')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}kb_documents`
             (doc_id, title, description, doc_type, file_name, file_key, file_size, file_mime, external_url, audience, dept_id, staff_id, isenabled, created, updated)
             VALUES
             (1, %s, %s, 'file', 'vpn-setup-guide.pdf',        'kb_vpn_setup_2024', 2457600, 'application/pdf', NULL, 'all',   0, 2, 1, DATE_SUB(NOW(), INTERVAL 45 DAY), DATE_SUB(NOW(), INTERVAL 10 DAY)),
             (2, %s, %s, 'file', 'security-policy-v3.pdf',     'kb_sec_policy_v3',  1843200, 'application/pdf', NULL, 'all',   0, 2, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), DATE_SUB(NOW(), INTERVAL 30 DAY)),
             (3, %s, %s, 'file', 'equipment-order-process.pdf','kb_equip_order',     921600, 'application/pdf', NULL, 'staff', 0, 6, 1, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
             (4, %s, %s, 'link', NULL, NULL, 0, NULL, NULL, 'staff', 1, 2, 1, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
             (5, %s, %s, 'file', 'network-topology-2024.pdf',  'kb_net_topology',   5242880, 'application/pdf', NULL, 'staff', 1, 2, 1, DATE_SUB(NOW(), INTERVAL 20 DAY), NOW())",
            db_input('Инструкция по подключению к VPN'), db_input('Пошаговая инструкция по настройке FortiClient для удалённого подключения.'),
            db_input('Политика информационной безопасности'), db_input('Основные правила работы с корпоративными данными, паролями и внешними носителями.'),
            db_input('Регламент заказа оборудования'), db_input('Порядок оформления заявок на закупку компьютерной техники и расходных материалов.'),
            db_input('FAQ: Частые вопросы по 1С'), db_input('Решения типичных проблем с 1С Предприятие: ошибки запуска, производительность, обновление.'),
            db_input('Схема корпоративной сети'), db_input('Актуальная схема сетевой инфраструктуры: VLAN, коммутаторы, маршрутизаторы, серверы.')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}locations`
             (location_id, location_name, parent_id, location_type, description, sort_order, is_active, created, updated)
             VALUES
             (1,  %s, NULL, 'building', %s,  1, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (2,  %s, 1, 'floor', %s,          1, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (3,  %s, 1, 'floor', %s,          2, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (4,  %s, 1, 'floor', %s,          3, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (5,  %s, 1, 'floor', %s,          4, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (6,  %s, 2, 'room',  %s,          1, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (7,  %s, 4, 'room',  %s,          1, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (8,  %s, 1, 'storage', %s,        5, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (9,  %s, 6, 'rack',  %s,          1, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (10, %s, 6, 'rack',  %s,          2, 1, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW())",
            db_input('Главный офис'), db_input('Основное здание компании, ул. Примерная, д. 1'),
            db_input('1 этаж'), db_input('Ресепшен, переговорные'),
            db_input('2 этаж'), db_input('Бухгалтерия, отдел кадров'),
            db_input('3 этаж'), db_input('Технический отдел, разработка'),
            db_input('4 этаж'), db_input('Руководство, продажи'),
            db_input('Серверная 1 этаж'), db_input('Основная серверная комната'),
            db_input('Серверная 3 этаж'), db_input('Коммутационная'),
            db_input('Склад ИТ'), db_input('Склад ЗИП и расходных материалов'),
            db_input('Стойка A'), db_input('Основная серверная стойка (серверы, NAS)'),
            db_input('Стойка B'), db_input('Сетевое оборудование (коммутаторы, роутеры)')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}inventory_brands` (brand_id, brand_name, is_active, created)
             VALUES
             (1, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (2, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (3, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (4, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (5, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (6, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (7, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (8, %s, 1, DATE_SUB(NOW(), INTERVAL 60 DAY))",
            db_input('Lenovo'), db_input('HP'), db_input('Dell'), db_input('Cisco'),
            db_input('Samsung'), db_input('APC'), db_input('Synology'), db_input('Apple')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}inventory_models` (model_id, brand_id, category_id, model_name, is_active, created)
             VALUES
             (1,  1, 8, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (2,  1, 9, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (3,  2, 8, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (4,  2, 3, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (5,  2, 3, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (6,  3, 8, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (7,  3, 2, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (8,  4, 4, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (9,  5, 2, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (10, 6, 7, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (11, 7, 6, %s, 1, DATE_SUB(NOW(), INTERVAL 90 DAY)),
             (12, 8, 8, %s, 1, DATE_SUB(NOW(), INTERVAL 60 DAY))",
            db_input('ThinkPad T14s Gen 4'), db_input('ThinkCentre M70q Gen 4'),
            db_input('EliteBook 840 G10'), db_input('LaserJet Pro M402dn'),
            db_input('LaserJet Pro MFP M428fdw'), db_input('Latitude 5540'),
            db_input('U2723QE 27"'), db_input('Catalyst 2960X-24TS'),
            db_input('S24D390HL 24"'), db_input('Smart-UPS 3000'),
            db_input('DS920+'), db_input('MacBook Pro 14 M3')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}inventory_items`
             (item_id,inventory_number,category_id,brand_id,model_id,custom_model,serial_number,part_number,location_id,assigned_staff_id,assignment_type,status,purchase_date,warranty_until,cost,description,created_by,created,updated)
             VALUES
             (1,  'INV-NB-001',  8, 1, 1,  NULL, 'PF4ABCDE',   '21F6CTO1WW',    NULL, 2,    'workplace', 'active',        '2024-03-15','2027-03-15',105000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
             (2,  'INV-NB-002',  8, 1, 1,  NULL, 'PF4FGHIJ',   '21F6CTO1WW',    NULL, 3,    'workplace', 'active',        '2024-03-15','2027-03-15',105000.00, NULL, 2, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
             (3,  'INV-NB-003',  8, 2, 3,  NULL, '5CG4KLMN',   '6T258EA',       NULL, 6,    'workplace', 'active',        '2024-01-20','2027-01-20',112000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 80 DAY), NOW()),
             (4,  'INV-NB-004',  8, 3, 6,  NULL, 'DLATUVWX',   'N007L5540',     8,    NULL, 'storage',   'active',        '2024-06-01','2027-06-01', 98000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 30 DAY), NOW()),
             (5,  'INV-PC-001',  9, 1, 2,  NULL, 'MJ0AOPQR',   '11T1CTO1WW',    3,    NULL, 'workplace', 'active',        '2023-09-01','2026-09-01', 58000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (6,  'INV-PC-002',  9, 1, 2,  NULL, 'MJ0BSTUV',   '11T1CTO1WW',    3,    NULL, 'workplace', 'active',        '2023-09-01','2026-09-01', 58000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (7,  'INV-PC-003',  9, 1, 2,  NULL, 'MJ0CWXYZ',   '11T1CTO1WW',    3,    NULL, 'workplace', 'active',        '2023-09-01','2026-09-01', 58000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (8,  'INV-MON-001', 2, 3, 7,  NULL, 'CN0DEFGH',   'U2723QE',       NULL, 2,    'workplace', 'active',        '2024-02-10','2027-02-10', 42000.00, NULL, 2, DATE_SUB(NOW(), INTERVAL 75 DAY), NOW()),
             (9,  'INV-MON-002', 2, 5, 9,  NULL, 'HVZAIJKL',   'LS24D390HL',    3,    NULL, 'workplace', 'active',        '2022-05-15','2025-05-15', 15000.00, NULL, 2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (10, 'INV-PRN-001', 3, 2, 4,  NULL, 'VNB4MNOP',   'C5F95A',        3,    NULL, 'workplace', 'active',        '2023-06-01','2026-06-01', 28000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (11, 'INV-PRN-002', 3, 2, 5,  NULL, 'VNB4QRST',   'W1A30A',        4,    NULL, 'workplace', 'active',        '2023-06-01','2026-06-01', 35000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (12, 'INV-NET-001', 4, 4, 8,  NULL, 'FCW2AUVWX',  'WS-C2960X-24TS-L', 7, NULL,'workplace', 'active',        '2024-04-01','2029-04-01', 95000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 12 DAY), NOW()),
             (13, 'INV-NET-002', 4, 4, 8,  NULL, 'FCW1BYZA1',  'WS-C2960X-24TS-L', 10,NULL,'workplace', 'active',        '2022-01-15','2027-01-15', 85000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (14, 'INV-SRV-001', 6, 7, 11, NULL, '2040BCDEF',  'DS920+',        9,    NULL, 'workplace', 'active',        '2023-03-01','2026-03-01', 78000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (15, 'INV-UPS-001', 7, 6, 10, NULL, 'AS2040GHI',  'SMT3000RMI2U',  9,    NULL, 'workplace', 'active',        '2022-08-01','2025-08-01',120000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 90 DAY), NOW()),
             (16, 'INV-NET-OLD', 4, NULL, NULL, %s, 'OLD123JKLM', NULL,          8,    NULL, 'storage',   'decommissioned','2018-06-01','2021-06-01', 25000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 12 DAY)),
             (17, 'INV-NB-005', 8, 8, 12,  NULL, 'FVFG4NOPQ',  'MRX73',         NULL, NULL, 'repair',    'in_repair',     '2024-05-01','2027-05-01',210000.00, %s, 2, DATE_SUB(NOW(), INTERVAL 5 DAY), NOW())",
            db_input('Ноутбук руководителя тех. отдела'),
            db_input('Ноутбук руководителя отдела продаж'),
            db_input('Резерв на складе ИТ'),
            db_input('Бухгалтерия, раб. место BUH-001'),
            db_input('Бухгалтерия, раб. место BUH-002'),
            db_input('HR, раб. место HR-002'),
            db_input('Принтер бухгалтерии, 2 этаж, каб. 203'),
            db_input('МФУ технического отдела, 3 этаж, каб. 312'),
            db_input('Коммутатор 3 этаж (новый, после замены)'),
            db_input('Коммутатор серверная, стойка B'),
            db_input('NAS хранилище — файловый сервер'),
            db_input('ИБП серверной стойки A'),
            db_input('D-Link DGS-1210-24'), db_input('Старый коммутатор 2 этаж — заменён на Cisco'),
            db_input('MacBook Pro директора — замена дисплея')
        ));

        db_query(
            "INSERT IGNORE INTO `{$prefix}inventory_history`
             (history_id, item_id, action, old_value, new_value, staff_id, created)
             VALUES
             (1, 1,  'created',        '', 'Создано',                    2, DATE_SUB(NOW(), INTERVAL 60 DAY)),
             (2, 12, 'created',        '', 'Создано',                    2, DATE_SUB(NOW(), INTERVAL 12 DAY)),
             (3, 16, 'status_changed', 'active', 'decommissioned',       2, DATE_SUB(NOW(), INTERVAL 12 DAY)),
             (4, 16, 'moved',          'Серверная 3 этаж', 'Склад ИТ',  2, DATE_SUB(NOW(), INTERVAL 12 DAY)),
             (5, 17, 'status_changed', 'active', 'in_repair',            2, DATE_SUB(NOW(), INTERVAL 5 DAY)),
             (6, 4,  'moved',          '', 'Склад ИТ',                   2, DATE_SUB(NOW(), INTERVAL 30 DAY)),
             (7, 7,  'assigned',       '', 'HR-002',                     2, DATE_SUB(NOW(), INTERVAL 90 DAY))"
        );

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}api_tokens`
             (token_id,token,name,description,staff_id,token_type,permissions,ip_whitelist,ip_check_enabled,rate_limit,rate_window,is_active,expires_at,last_used_at,total_requests,created_at,updated_at)
             VALUES
             (1, 'th_test_token_a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6', %s, %s, 2, 'permanent', '[\"tickets.read\",\"tickets.write\",\"tasks.read\"]', '127.0.0.1,192.168.0.0/16', 0, 1000, 3600, 1, DATE_ADD(NOW(), INTERVAL 365 DAY), NULL, 0, NOW(), NOW()),
             (2, 'th_readonly_e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4', %s, %s, 2, 'readonly', '[\"tickets.read\",\"tasks.read\",\"stats.read\"]', NULL, 0, 500, 3600, 1, DATE_ADD(NOW(), INTERVAL 180 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), 47, NOW(), NOW())",
            db_input('Тестовый токен'), db_input('Токен для тестирования API v2 (seed data)'),
            db_input('Мониторинг'),     db_input('Только чтение для дашборда мониторинга')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}priority_users` (id, email, description, is_active, created, updated)
             VALUES
             (1, %s, %s, 1, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
             (2, %s, %s, 1, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW()),
             (3, %s, %s, 1, DATE_SUB(NOW(), INTERVAL 60 DAY), NOW())",
            db_input('director@company.ru'), db_input('Генеральный директор'),
            db_input('cfo@company.ru'),      db_input('Финансовый директор'),
            db_input('cto@company.ru'),      db_input('Технический директор')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}kb_premade` (premade_id, dept_id, isenabled, title, answer, created, updated)
             VALUES
             (3, 0, 1, %s, %s, NOW(), NOW()),
             (4, 1, 1, %s, %s, NOW(), NOW()),
             (5, 0, 1, %s, %s, NOW(), NOW())",
            db_input('Инструкция по VPN'), db_input("%name,\r\n\r\nДля подключения к VPN:\r\n1. Установите FortiClient\r\n2. Настройте подключение: сервер — vpn.company.ru, порт — 443\r\n\r\n%signature"),
            db_input('Сброс пароля'),      db_input("%name,\r\n\r\nДля сброса пароля:\r\n1. Перейдите на https://portal.company.ru/password-reset\r\n2. Введите вашу корпоративную почту\r\n\r\n%signature"),
            db_input('Заказ оборудования'),db_input("%name,\r\n\r\nДля заказа оборудования:\r\n1. Заполните форму заказа на корпоративном портале\r\n2. Получите согласование руководителя отдела\r\n\r\nСрок обработки: 3-5 рабочих дней.\r\n\r\n%signature")
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}email_banlist` (id, email, submitter, added)
             VALUES
             (2, %s, %s, DATE_SUB(NOW(), INTERVAL 30 DAY)),
             (3, %s, %s, DATE_SUB(NOW(), INTERVAL 15 DAY))",
            db_input('spam@spammer.com'),    db_input('Система'),
            db_input('noreply@phishing.ru'), db_input('Иванов И.')
        ));

        db_query(sprintf(
            "INSERT IGNORE INTO `{$prefix}syslog` (log_id, log_type, title, log, logger, ip_address, created, updated)
             VALUES
             (2, 'Debug',   'Staff login', %s, 'ivanov',  '192.168.1.1', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
             (3, 'Debug',   'Staff login', %s, 'petrova', '192.168.1.2', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
             (4, 'Warning', %s, %s, '', '10.0.0.99', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
             (5, 'Debug',   'User login',  %s, '', '192.168.1.10', DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_SUB(NOW(), INTERVAL 5 HOUR))",
            db_input('ivanov logged in [192.168.1.1]'),
            db_input('petrova logged in [192.168.1.2]'),
            db_input('Неудачная попытка входа (клиент)'), db_input("Email: use***@company.ru\nIP: 10.0.0.99\nПопыток #2"),
            db_input('user1@company.ru/384719 logged in [192.168.1.10]')
        ));

        return true;
    }
}
