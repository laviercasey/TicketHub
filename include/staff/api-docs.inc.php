<?php
if(!defined('OSTADMININC') || !$thisuser->isadmin()) die('Доступ запрещён');

$title = 'API Документация';

$token = null;
$token_info = array();
if (isset($_SESSION['api_demo_token'])) {
    require_once(INCLUDE_DIR.'class.apitoken.php');
    $token = ApiToken::lookup($_SESSION['api_demo_token']);
    if ($token) {
        $token_info = array(
            'token' => $token->getToken(),
            'name' => $token->getName(),
            'permissions' => $token->getPermissions(),
            'rate_limit' => $token->getRateLimit()
        );
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo $title; ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .api-docs-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .docs-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
        }

        .docs-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }

        .docs-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }

        .docs-nav {
            background: #f8f9fa;
            padding: 20px 30px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .docs-nav a {
            padding: 8px 16px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }

        .docs-nav a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .docs-content {
            padding: 30px;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .endpoint {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            cursor: pointer;
        }

        .method {
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            text-transform: uppercase;
        }

        .method.get { background: #61affe; color: white; }
        .method.post { background: #49cc90; color: white; }
        .method.put { background: #fca130; color: white; }
        .method.patch { background: #50e3c2; color: white; }
        .method.delete { background: #f93e3e; color: white; }

        .endpoint-path {
            font-family: 'Courier New', monospace;
            font-size: 16px;
            color: #333;
            flex: 1;
        }

        .endpoint-description {
            color: #666;
            margin-bottom: 15px;
        }

        .endpoint-details {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }

        .endpoint-details.active {
            display: block;
        }

        .params-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
        }

        .params-table th {
            background: #f1f1f1;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: 600;
        }

        .params-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        .param-name {
            font-family: 'Courier New', monospace;
            color: #667eea;
            font-weight: bold;
        }

        .param-type {
            color: #999;
            font-size: 12px;
        }

        .code-block {
            background: #282c34;
            color: #abb2bf;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
        }

        .code-block .keyword { color: #c678dd; }
        .code-block .string { color: #98c379; }
        .code-block .number { color: #d19a66; }
        .code-block .comment { color: #5c6370; font-style: italic; }

        .try-it-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
        }

        .try-it-btn:hover {
            background: #5568d3;
        }

        .try-it-panel {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .try-it-panel.active {
            display: block;
        }

        .try-it-panel input,
        .try-it-panel textarea {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }

        .try-it-panel textarea {
            min-height: 100px;
            resize: vertical;
        }

        .response-panel {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            display: none;
        }

        .response-panel.active {
            display: block;
        }

        .response-status {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .response-status.success { color: #49cc90; }
        .response-status.error { color: #f93e3e; }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .warning-box {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .token-display {
            background: white;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }

        .token-display code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #667eea;
        }

        .toggle-icon {
            color: #999;
            font-size: 12px;
        }

        .search-box {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 8px;
        }

        .badge.required {
            background: #f93e3e;
            color: white;
        }

        .badge.optional {
            background: #e0e0e0;
            color: #666;
        }
    </style>
</head>
<body>

<div class="api-docs-container">
    <div class="docs-header">
        <h1>TicketHub REST API v1</h1>
        <p>Полная документация API с интерактивными примерами</p>
    </div>

    <div class="docs-nav">
        <a href="#introduction">Введение</a>
        <a href="#authentication">Аутентификация</a>
        <a href="#tickets">Тикеты</a>
        <a href="#users">Пользователи</a>
        <a href="#staff">Сотрудники</a>
        <a href="#departments">Отделы</a>
        <a href="#tasks">Задачи</a>
        <a href="#kb">База знаний</a>
        <a href="#errors">Ошибки</a>
    </div>

    <div class="docs-content">
        <div id="introduction" class="section">
            <h2 class="section-title">Введение</h2>

            <p>TicketHub REST API v1 предоставляет программный доступ к основным функциям системы.</p>

            <div class="info-box">
                <strong>Базовый URL:</strong> <code><?php echo $cfg->getUrl(); ?>api/v1</code>
            </div>

            <h3>Основные возможности</h3>
            <ul>
                <li>Управление тикетами (создание, обновление, закрытие)</li>
                <li>Работа с пользователями и сотрудниками</li>
                <li>Управление задачами и досками</li>
                <li>База знаний с полнотекстовым поиском</li>
                <li>Bearer Token аутентификация</li>
                <li>Rate Limiting (контроль частоты запросов)</li>
                <li>Пагинация, фильтрация, сортировка</li>
            </ul>

            <h3>Форматы данных</h3>
            <ul>
                <li><strong>Запрос:</strong> JSON (Content-Type: application/json)</li>
                <li><strong>Ответ:</strong> JSON</li>
                <li><strong>Кодировка:</strong> UTF-8</li>
            </ul>
        </div>

        <div id="authentication" class="section">
            <h2 class="section-title">Аутентификация</h2>

            <p>API использует Bearer Token аутентификацию. Токены создаются в админ-панели.</p>

            <h3>Создание токена</h3>
            <p>Перейдите в <strong>Настройки &rarr; Параметры API &rarr; Токены</strong> для создания нового токена.</p>

            <div class="warning-box">
                <strong>Важно:</strong> Сохраните токен в безопасном месте. Он отображается только один раз при создании.
            </div>

            <?php if ($token_info): ?>
            <div class="token-display">
                <strong>Ваш текущий токен для тестирования:</strong><br>
                <code><?php echo Format::htmlchars($token_info['token']); ?></code>
                <p style="margin-top: 10px; color: #666; font-size: 13px;">
                    Имя: <?php echo Format::htmlchars($token_info['name']); ?><br>
                    Лимит: <?php echo $token_info['rate_limit']; ?> запросов/час
                </p>
            </div>
            <?php else: ?>
            <div class="info-box">
                Создайте токен в <a href="admin.php?t=api">настройках API</a> для тестирования эндпоинтов.
            </div>
            <?php endif; ?>

            <h3>Использование токена</h3>
            <div class="code-block">
<span class="comment"># Пример запроса с токеном</span>
curl -X GET \
  <?php echo $cfg->getUrl(); ?>api/v1/tickets \
  -H <span class="string">'Authorization: Bearer YOUR_TOKEN_HERE'</span> \
  -H <span class="string">'Content-Type: application/json'</span>
            </div>

            <h3>Разрешения (Permissions)</h3>
            <p>Каждый токен имеет набор разрешений, определяющих доступные операции:</p>
            <ul>
                <li><code>tickets:read</code> - чтение тикетов</li>
                <li><code>tickets:write</code> - создание/изменение тикетов</li>
                <li><code>users:read</code> - чтение данных пользователей</li>
                <li><code>users:write</code> - изменение пользователей</li>
                <li><code>staff:read</code> - чтение данных сотрудников</li>
                <li><code>departments:read</code> - чтение отделов</li>
                <li><code>tasks:read</code> - чтение задач</li>
                <li><code>tasks:write</code> - создание/изменение задач</li>
                <li><code>kb:read</code> - чтение базы знаний</li>
                <li><code>kb:write</code> - изменение базы знаний</li>
                <li><code>admin:*</code> - полный административный доступ</li>
            </ul>
        </div>

        <div id="tickets" class="section">
            <h2 class="section-title">Тикеты (Tickets)</h2>

            <?php echo renderEndpoint(
                'GET',
                '/tickets',
                'Список тикетов',
                'Получение списка тикетов с фильтрацией и пагинацией',
                array(
                    array('page', 'integer', 'Номер страницы (по умолчанию: 1)', false),
                    array('per_page', 'integer', 'Количество на странице (по умолчанию: 20, макс: 100)', false),
                    array('status', 'string', 'Фильтр по статусу (open, closed)', false),
                    array('priority_id', 'integer', 'Фильтр по приоритету', false),
                    array('dept_id', 'integer', 'Фильтр по отделу', false),
                    array('staff_id', 'integer', 'Фильтр по исполнителю', false),
                    array('email', 'string', 'Фильтр по email пользователя', false),
                    array('search', 'string', 'Поиск в теме и содержании', false),
                    array('sort', 'string', 'Поле сортировки (ticket_id, created, updated)', false),
                    array('order', 'string', 'Направление сортировки (asc, desc)', false)
                ),
                'GET /api/v1/tickets?status=open&per_page=10',
'{
  "success": true,
  "data": [
    {
      "ticket_id": 123,
      "number": "123456",
      "subject": "Проблема с доступом",
      "status": "open",
      "priority": {
        "id": 2,
        "name": "Обычный"
      },
      "user": {
        "email": "user@example.com",
        "name": "Иван Иванов"
      },
      "created_at": "2025-02-16T10:30:00+00:00"
    }
  ],
  "meta": {
    "total": 45,
    "page": 1,
    "per_page": 10,
    "total_pages": 5
  }
}'
            ); ?>

            <?php echo renderEndpoint(
                'GET',
                '/tickets/{id}',
                'Детали тикета',
                'Получение подробной информации о тикете',
                array(
                    array('id', 'integer', 'ID тикета', true)
                ),
                'GET /api/v1/tickets/123',
'{
  "success": true,
  "data": {
    "ticket_id": 123,
    "number": "123456",
    "subject": "Проблема с доступом",
    "status": "open",
    "priority": {
      "id": 2,
      "name": "Обычный",
      "urgency": 2,
      "color": "#0000FF"
    },
    "user": {
      "email": "user@example.com",
      "name": "Иван Иванов",
      "phone": "+7 900 123-45-67"
    },
    "department": {
      "id": 1,
      "name": "Поддержка"
    },
    "help_topic": {
      "id": 5,
      "name": "Технические вопросы"
    },
    "assigned_to": {
      "id": 3,
      "name": "Петр Петров"
    },
    "created_at": "2025-02-16T10:30:00+00:00",
    "updated_at": "2025-02-16T14:25:00+00:00",
    "due_date": "2025-02-18T18:00:00+00:00"
  }
}'
            ); ?>

            <?php echo renderEndpoint(
                'POST',
                '/tickets',
                'Создать тикет',
                'Создание нового тикета',
                array(
                    array('name', 'string', 'Имя пользователя', true),
                    array('email', 'string', 'Email пользователя', true),
                    array('subject', 'string', 'Тема тикета', true),
                    array('message', 'string', 'Текст сообщения', true),
                    array('phone', 'string', 'Телефон пользователя', false),
                    array('priority_id', 'integer', 'ID приоритета', false),
                    array('help_topic_id', 'integer', 'ID категории', false),
                    array('dept_id', 'integer', 'ID отдела', false)
                ),
                'POST /api/v1/tickets',
'{
  "name": "Иван Иванов",
  "email": "user@example.com",
  "phone": "+7 900 123-45-67",
  "subject": "Не могу войти в систему",
  "message": "Забыл пароль, прошу помочь восстановить доступ.",
  "priority_id": 2,
  "help_topic_id": 5
}',
'{
  "success": true,
  "message": "Тикет успешно создан",
  "data": {
    "ticket_id": 124,
    "number": "789012",
    "subject": "Не могу войти в систему",
    "status": "open",
    "created_at": "2025-02-16T15:45:00+00:00"
  }
}'
            ); ?>

            <?php echo renderEndpoint(
                'PUT',
                '/tickets/{id}',
                'Обновить тикет',
                'Обновление информации о тикете',
                array(
                    array('id', 'integer', 'ID тикета', true),
                    array('subject', 'string', 'Новая тема', false),
                    array('priority_id', 'integer', 'Новый приоритет', false),
                    array('dept_id', 'integer', 'Новый отдел', false),
                    array('staff_id', 'integer', 'Назначить исполнителя', false),
                    array('status', 'string', 'Новый статус (open, closed)', false)
                ),
                'PUT /api/v1/tickets/123',
'{
  "priority_id": 3,
  "staff_id": 5
}'
            ); ?>

            <?php echo renderEndpoint(
                'DELETE',
                '/tickets/{id}',
                'Закрыть тикет',
                'Закрытие (удаление) тикета',
                array(
                    array('id', 'integer', 'ID тикета', true)
                ),
                'DELETE /api/v1/tickets/123'
            ); ?>

            <?php echo renderEndpoint(
                'GET',
                '/tickets/{id}/messages',
                'Сообщения тикета',
                'Получение всех сообщений в тикете',
                array(
                    array('id', 'integer', 'ID тикета', true)
                ),
                'GET /api/v1/tickets/123/messages'
            ); ?>

            <?php echo renderEndpoint(
                'POST',
                '/tickets/{id}/messages',
                'Добавить сообщение',
                'Добавление ответа в тикет',
                array(
                    array('id', 'integer', 'ID тикета', true),
                    array('message', 'string', 'Текст сообщения', true),
                    array('alert', 'boolean', 'Отправить уведомление пользователю', false)
                ),
                'POST /api/v1/tickets/123/messages',
'{
  "message": "Ваш пароль был сброшен. Проверьте email.",
  "alert": true
}'
            ); ?>
        </div>

        <div id="users" class="section">
            <h2 class="section-title">Пользователи (Users)</h2>

            <div class="info-box">
                <strong>Примечание:</strong> В TicketHub нет отдельной таблицы пользователей.
                Пользователи идентифицируются по email и данные агрегируются из таблицы тикетов.
            </div>

            <?php echo renderEndpoint(
                'GET',
                '/users',
                'Список пользователей',
                'Получение списка уникальных пользователей',
                array(
                    array('page', 'integer', 'Номер страницы', false),
                    array('per_page', 'integer', 'Количество на странице', false),
                    array('email', 'string', 'Фильтр по email', false),
                    array('search', 'string', 'Поиск по email или имени', false),
                    array('sort', 'string', 'Поле сортировки', false),
                    array('order', 'string', 'Направление сортировки', false)
                ),
                'GET /api/v1/users?search=ivanov'
            ); ?>

            <?php echo renderEndpoint(
                'GET',
                '/users/{email}',
                'Информация о пользователе',
                'Получение детальной информации о пользователе по email',
                array(
                    array('email', 'string', 'Email пользователя', true)
                ),
                'GET /api/v1/users/user@example.com',
'{
  "success": true,
  "data": {
    "email": "user@example.com",
    "name": "Иван Иванов",
    "phone": "+7 900 123-45-67",
    "tickets": {
      "total": 15,
      "open": 3,
      "closed": 12
    },
    "first_ticket_at": "2024-01-15T10:00:00+00:00",
    "last_ticket_at": "2025-02-16T14:30:00+00:00",
    "recent_tickets": [...]
  }
}'
            ); ?>

            <?php echo renderEndpoint(
                'PUT',
                '/users/{email}',
                'Обновить пользователя',
                'Обновление информации о пользователе во всех его тикетах',
                array(
                    array('email', 'string', 'Email пользователя', true),
                    array('name', 'string', 'Новое имя', false),
                    array('phone', 'string', 'Новый телефон', false)
                ),
                'PUT /api/v1/users/user@example.com',
'{
  "name": "Иван Петрович Иванов",
  "phone": "+7 900 111-22-33"
}'
            ); ?>
        </div>

        <div id="staff" class="section">
            <h2 class="section-title">Сотрудники (Staff)</h2>

            <?php echo renderEndpoint(
                'GET',
                '/staff',
                'Список сотрудников',
                'Получение списка сотрудников с фильтрацией',
                array(
                    array('dept_id', 'integer', 'Фильтр по отделу', false),
                    array('is_active', 'boolean', 'Фильтр по статусу активности', false),
                    array('is_admin', 'boolean', 'Фильтр по роли администратора', false),
                    array('search', 'string', 'Поиск по имени, email, username', false)
                ),
                'GET /api/v1/staff?dept_id=1&is_active=true'
            ); ?>

            <?php echo renderEndpoint(
                'GET',
                '/staff/{id}',
                'Информация о сотруднике',
                'Получение детальной информации о сотруднике',
                array(
                    array('id', 'integer', 'ID сотрудника', true)
                ),
                'GET /api/v1/staff/5'
            ); ?>
        </div>

        <div id="departments" class="section">
            <h2 class="section-title">Отделы (Departments)</h2>

            <?php echo renderEndpoint(
                'GET',
                '/departments',
                'Список отделов',
                'Получение списка отделов',
                array(
                    array('is_public', 'boolean', 'Фильтр по публичности', false),
                    array('search', 'string', 'Поиск по названию', false)
                ),
                'GET /api/v1/departments'
            ); ?>

            <?php echo renderEndpoint(
                'GET',
                '/departments/{id}',
                'Информация об отделе',
                'Получение детальной информации об отделе со статистикой',
                array(
                    array('id', 'integer', 'ID отдела', true)
                ),
                'GET /api/v1/departments/1'
            ); ?>
        </div>

        <div id="tasks" class="section">
            <h2 class="section-title">Задачи (Tasks)</h2>

            <?php echo renderEndpoint(
                'GET',
                '/tasks',
                'Список задач',
                'Получение списка задач с фильтрацией',
                array(
                    array('board_id', 'integer', 'Фильтр по доске', false),
                    array('list_id', 'integer', 'Фильтр по списку', false),
                    array('status', 'string', 'Фильтр по статусу (open, in_progress, blocked, completed, cancelled)', false),
                    array('priority', 'string', 'Фильтр по приоритету (low, normal, high, urgent)', false),
                    array('assignee_id', 'integer', 'Фильтр по исполнителю', false),
                    array('is_overdue', 'boolean', 'Только просроченные', false),
                    array('search', 'string', 'Поиск в названии и описании', false)
                ),
                'GET /api/v1/tasks?status=in_progress&assignee_id=5'
            ); ?>

            <?php echo renderEndpoint(
                'GET',
                '/tasks/{id}',
                'Детали задачи',
                'Получение подробной информации о задаче',
                array(
                    array('id', 'integer', 'ID задачи', true)
                ),
                'GET /api/v1/tasks/42'
            ); ?>

            <?php echo renderEndpoint(
                'POST',
                '/tasks',
                'Создать задачу',
                'Создание новой задачи',
                array(
                    array('title', 'string', 'Название задачи', true),
                    array('board_id', 'integer', 'ID доски', true),
                    array('description', 'string', 'Описание задачи', false),
                    array('list_id', 'integer', 'ID списка', false),
                    array('priority', 'string', 'Приоритет (low, normal, high, urgent)', false),
                    array('status', 'string', 'Статус', false),
                    array('deadline', 'datetime', 'Срок выполнения', false),
                    array('assignees', 'array', 'Массив ID исполнителей', false)
                ),
                'POST /api/v1/tasks',
'{
  "title": "Настроить сервер",
  "board_id": 1,
  "description": "Установить и настроить веб-сервер",
  "priority": "high",
  "deadline": "2025-02-20T18:00:00+00:00",
  "assignees": [5, 7]
}'
            ); ?>

            <?php echo renderEndpoint(
                'PUT',
                '/tasks/{id}/status',
                'Изменить статус задачи',
                'Обновление статуса задачи',
                array(
                    array('id', 'integer', 'ID задачи', true),
                    array('status', 'string', 'Новый статус (open, in_progress, blocked, completed, cancelled)', true)
                ),
                'PUT /api/v1/tasks/42/status',
'{
  "status": "completed"
}'
            ); ?>

            <?php echo renderEndpoint(
                'GET',
                '/taskboards',
                'Список досок',
                'Получение списка досок задач',
                array(
                    array('board_type', 'string', 'Фильтр по типу (department, project)', false),
                    array('dept_id', 'integer', 'Фильтр по отделу', false)
                ),
                'GET /api/v1/taskboards'
            ); ?>
        </div>

        <div id="kb" class="section">
            <h2 class="section-title">База знаний (Knowledge Base)</h2>

            <?php echo renderEndpoint(
                'GET',
                '/kb/documents',
                'Список документов',
                'Получение списка документов базы знаний',
                array(
                    array('doc_type', 'string', 'Фильтр по типу (file, link)', false),
                    array('audience', 'string', 'Фильтр по аудитории (staff, client, all)', false),
                    array('is_enabled', 'boolean', 'Фильтр по статусу активности', false),
                    array('search', 'string', 'Поиск в названии и описании', false)
                ),
                'GET /api/v1/kb/documents?audience=all'
            ); ?>

            <?php echo renderEndpoint(
                'GET',
                '/kb/documents/{id}',
                'Детали документа',
                'Получение подробной информации о документе',
                array(
                    array('id', 'integer', 'ID документа', true)
                ),
                'GET /api/v1/kb/documents/15'
            ); ?>

            <?php echo renderEndpoint(
                'POST',
                '/kb/documents',
                'Создать документ',
                'Создание нового документа (только ссылки, файлы через веб-интерфейс)',
                array(
                    array('title', 'string', 'Название документа', true),
                    array('doc_type', 'string', 'Тип документа (link)', true),
                    array('audience', 'string', 'Аудитория (staff, client, all)', true),
                    array('external_url', 'string', 'URL ссылки', true),
                    array('description', 'string', 'Описание', false),
                    array('is_enabled', 'boolean', 'Активен (по умолчанию true)', false)
                ),
                'POST /api/v1/kb/documents',
'{
  "title": "Инструкция по работе с API",
  "doc_type": "link",
  "audience": "staff",
  "external_url": "https://docs.google.com/document/d/abc123",
  "description": "Подробная инструкция для разработчиков"
}'
            ); ?>

            <?php echo renderEndpoint(
                'POST',
                '/kb/documents/search',
                'Поиск документов',
                'Полнотекстовый поиск в базе знаний',
                array(
                    array('q', 'string', 'Поисковый запрос', true)
                ),
                'POST /api/v1/kb/documents/search',
'{
  "q": "настройка сервера"
}',
'{
  "success": true,
  "data": {
    "query": "настройка сервера",
    "total": 3,
    "results": [
      {
        "doc_id": 12,
        "title": "Настройка веб-сервера",
        "relevance": 1.5,
        ...
      }
    ]
  }
}'
            ); ?>
        </div>

        <div id="errors" class="section">
            <h2 class="section-title">Коды ошибок</h2>

            <p>API использует стандартные HTTP коды состояния:</p>

            <table class="params-table">
                <tr>
                    <th>Код</th>
                    <th>Название</th>
                    <th>Описание</th>
                </tr>
                <tr>
                    <td><strong>200</strong></td>
                    <td>OK</td>
                    <td>Запрос выполнен успешно</td>
                </tr>
                <tr>
                    <td><strong>201</strong></td>
                    <td>Создано</td>
                    <td>Ресурс успешно создан</td>
                </tr>
                <tr>
                    <td><strong>400</strong></td>
                    <td>Некорректный запрос</td>
                    <td>Некорректные параметры запроса</td>
                </tr>
                <tr>
                    <td><strong>401</strong></td>
                    <td>Не авторизован</td>
                    <td>Отсутствует или неверный токен аутентификации</td>
                </tr>
                <tr>
                    <td><strong>403</strong></td>
                    <td>Запрещено</td>
                    <td>Недостаточно прав для выполнения операции</td>
                </tr>
                <tr>
                    <td><strong>404</strong></td>
                    <td>Не найдено</td>
                    <td>Ресурс не найден</td>
                </tr>
                <tr>
                    <td><strong>422</strong></td>
                    <td>Ошибка обработки</td>
                    <td>Ошибка валидации данных</td>
                </tr>
                <tr>
                    <td><strong>429</strong></td>
                    <td>Слишком много запросов</td>
                    <td>Превышен лимит запросов (Rate Limit)</td>
                </tr>
                <tr>
                    <td><strong>500</strong></td>
                    <td>Внутренняя ошибка</td>
                    <td>Внутренняя ошибка сервера</td>
                </tr>
            </table>

            <h3>Формат ответа с ошибкой</h3>
            <div class="code-block">
{
  <span class="string">"success"</span>: <span class="keyword">false</span>,
  <span class="string">"error"</span>: {
    <span class="string">"code"</span>: <span class="number">422</span>,
    <span class="string">"message"</span>: <span class="string">"Ошибка валидации"</span>,
    <span class="string">"errors"</span>: {
      <span class="string">"email"</span>: <span class="string">"Неверный формат email"</span>,
      <span class="string">"subject"</span>: <span class="string">"Тема обязательна"</span>
    }
  }
}
            </div>

            <h3>Rate Limiting</h3>
            <p>При превышении лимита запросов API вернет ошибку 429:</p>
            <div class="code-block">
{
  <span class="string">"success"</span>: <span class="keyword">false</span>,
  <span class="string">"error"</span>: {
    <span class="string">"code"</span>: <span class="number">429</span>,
    <span class="string">"message"</span>: <span class="string">"Превышен лимит запросов. Повторите позже."</span>,
    <span class="string">"retry_after"</span>: <span class="number">3600</span> <span class="comment">// секунд до сброса лимита</span>
  }
}
            </div>

            <p>Информация о лимитах возвращается в заголовках ответа:</p>
            <ul>
                <li><code>X-RateLimit-Limit</code> - максимальное количество запросов</li>
                <li><code>X-RateLimit-Remaining</code> - оставшееся количество запросов</li>
                <li><code>X-RateLimit-Reset</code> - время сброса лимита (Unix timestamp)</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.endpoint-header').forEach(header => {
    header.addEventListener('click', function() {
        const details = this.parentElement.querySelector('.endpoint-details');
        details.classList.toggle('active');

        const icon = this.querySelector('.toggle-icon');
        if (details.classList.contains('active')) {
            icon.textContent = '\u25BC';
        } else {
            icon.textContent = '\u25B6';
        }
    });
});

document.querySelectorAll('.try-it-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const panel = this.nextElementSibling;
        panel.classList.toggle('active');
        this.textContent = panel.classList.contains('active') ? 'Скрыть' : 'Попробовать';
    });
});

const searchBox = document.getElementById('search-endpoints');
if (searchBox) {
    searchBox.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.endpoint').forEach(endpoint => {
            const text = endpoint.textContent.toLowerCase();
            endpoint.style.display = text.includes(query) ? 'block' : 'none';
        });
    });
}

document.querySelectorAll('.docs-nav a').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const target = document.querySelector(targetId);
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

</body>
</html>

<?php
function renderEndpoint($method, $path, $title, $description, $params = array(), $example = '', $request_body = '', $response_body = '') {
    $html = '<div class="endpoint">';
    $html .= '<div class="endpoint-header">';
    $html .= '<span class="toggle-icon">&#9654;</span>';
    $html .= '<span class="method ' . strtolower($method) . '">' . $method . '</span>';
    $html .= '<span class="endpoint-path">' . Format::htmlchars($path) . '</span>';
    $html .= '</div>';
    $html .= '<div class="endpoint-description"><strong>' . Format::htmlchars($title) . '</strong><br>' . Format::htmlchars($description) . '</div>';

    $html .= '<div class="endpoint-details">';

    if (!empty($params)) {
        $html .= '<h4>Параметры</h4>';
        $html .= '<table class="params-table">';
        $html .= '<tr><th>Параметр</th><th>Тип</th><th>Описание</th></tr>';
        foreach ($params as $param) {
            $badge = $param[3] ? '<span class="badge required">обязательный</span>' : '<span class="badge optional">опционально</span>';
            $html .= '<tr>';
            $html .= '<td><span class="param-name">' . Format::htmlchars($param[0]) . '</span>' . $badge . '</td>';
            $html .= '<td><span class="param-type">' . Format::htmlchars($param[1]) . '</span></td>';
            $html .= '<td>' . Format::htmlchars($param[2]) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
    }

    if ($example) {
        $html .= '<h4>Пример запроса</h4>';
        $html .= '<div class="code-block">' . Format::htmlchars($example) . '</div>';
    }

    if ($request_body) {
        $html .= '<h4>Тело запроса</h4>';
        $html .= '<div class="code-block">' . Format::htmlchars($request_body) . '</div>';
    }

    if ($response_body) {
        $html .= '<h4>Пример ответа</h4>';
        $html .= '<div class="code-block">' . Format::htmlchars($response_body) . '</div>';
    }

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}
?>
