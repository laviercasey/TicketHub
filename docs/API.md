<a id="top"></a>

<p align="right">
  <a href="../README.md">README</a>
</p>

<h1 align="center">TicketHub REST API v1</h1>

<p align="center">
  <em>Документация REST API с Bearer Token аутентификацией</em>
</p>

<p align="center">
  <a href="#аутентификация">Аутентификация</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#эндпоинты">Эндпоинты</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#фильтрация-и-пагинация">Фильтрация</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#формат-ответов">Ответы</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#rate-limiting">Rate Limiting</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#безопасность">Безопасность</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#примеры">Примеры</a>
</p>

---

## Быстрый старт

### 1. Создать API-токен

1. Войдите в панель управления TicketHub
2. Перейдите **Settings --> API**
3. Нажмите **Add New Token**
4. Укажите название, разрешения и лимит запросов
5. Скопируйте токен (отображается только один раз)

### 2. Первый запрос

```bash
curl -X GET http://localhost:8080/api/v1/tickets \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json'
```

Интерактивная документация доступна в панели управления: **Settings --> API Документация**

<p align="right"><a href="#top">наверх</a></p>

---

## Аутентификация

API использует Bearer Token без привязки к IP (опциональный IP whitelist).

```
Authorization: Bearer YOUR_TOKEN
```

### Разрешения

Каждый токен имеет гранулярный набор прав:

| Разрешение | Описание |
|:--|:--|
| `tickets:read` | Чтение тикетов |
| `tickets:create` | Создание тикетов |
| `tickets:write` | Изменение тикетов |
| `tickets:delete` | Закрытие/удаление тикетов |
| `users:read` / `users:create` / `users:write` / `users:delete` | Управление пользователями |
| `staff:read` / `staff:create` / `staff:write` | Управление сотрудниками |
| `departments:read` / `departments:create` / `departments:write` | Управление отделами |
| `tasks:read` / `tasks:create` / `tasks:write` / `tasks:delete` | Управление задачами |
| `kb:read` / `kb:create` / `kb:write` / `kb:delete` | База знаний |
| `admin:*` | Полный доступ |

### Свойства токена

| Свойство | Описание |
|:--|:--|
| Длина | 64 символа (hex), хранится как SHA-256 |
| IP whitelist | Опциональный список IP/CIDR |
| Rate limit | Настраивается на каждый токен |
| Срок действия | Бессрочный или с датой истечения |
| Статистика | Последнее использование, счётчик запросов |

<p align="right"><a href="#top">наверх</a></p>

---

## Эндпоинты

### Сводная таблица

| Ресурс | GET (список) | GET (один) | POST | PUT/PATCH | DELETE |
|:--|:--|:--|:--|:--|:--|
| **Тикеты** | `/tickets` | `/tickets/{id}` | `/tickets` | `/tickets/{id}` | `/tickets/{id}` |
| **Сообщения** | `/tickets/{id}/messages` | -- | `/tickets/{id}/messages` | -- | -- |
| **Заметки** | `/tickets/{id}/notes` | -- | `/tickets/{id}/notes` | -- | -- |
| **Вложения** | `/tickets/{id}/attachments` | -- | -- | -- | -- |
| **Пользователи** | `/users` | `/users/{email}` | `/users` | `/users/{email}` | `/users/{email}` |
| **Сотрудники** | `/staff` | `/staff/{id}` | `/staff` | `/staff/{id}` | -- |
| **Отделы** | `/departments` | `/departments/{id}` | `/departments` | `/departments/{id}` | -- |
| **Категории** | `/help-topics` | `/help-topics/{id}` | -- | -- | -- |
| **Приоритеты** | `/priorities` | -- | -- | -- | -- |
| **Задачи** | `/tasks` | `/tasks/{id}` | `/tasks` | `/tasks/{id}` | `/tasks/{id}` |
| **Статус задачи** | -- | -- | -- | `/tasks/{id}/status` | -- |
| **Исполнители** | -- | -- | `/tasks/{id}/assignees` | -- | `/tasks/{id}/assignees/{staff_id}` |
| **Доски** | `/taskboards` | `/taskboards/{id}` | `/taskboards` | `/taskboards/{id}` | `/taskboards/{id}` |
| **База знаний** | `/kb/documents` | `/kb/documents/{id}` | `/kb/documents` | `/kb/documents/{id}` | `/kb/documents/{id}` |
| **Поиск KB** | `/kb/documents/search` | -- | `/kb/documents/search` | -- | -- |
| **Токен** | `/tokens/current` | -- | -- | -- | -- |
| **Статистика** | `/tokens/usage` | -- | -- | -- | -- |
| **Информация** | `/` или `/info` | -- | -- | -- | -- |

Все пути относительно `/api/v1/`.

---

### Тикеты

<details>
<summary><code>GET /tickets</code> -- список тикетов</summary>

<br>

**Разрешение:** `tickets:read`

| Параметр | Тип | Описание |
|:--|:--|:--|
| `page` | int | Страница (по умолчанию 1) |
| `per_page` | int | На странице (по умолчанию 20, макс 100) |
| `sort` | string | Поле сортировки (`created`, `updated`, `priority`, `status`) |
| `order` | string | `ASC` / `DESC` |
| `status` | string | `open`, `closed` |
| `priority_id` | int | ID приоритета |
| `dept_id` | int | ID отдела |
| `staff_id` | int | ID сотрудника |
| `topic_id` | int | ID категории |
| `email` | string | Email клиента |
| `search` | string | Поиск по теме/имени/email |
| `is_overdue` | int | Только просроченные |
| `created_from` / `created_to` | string | ISO-8601 диапазон дат |

</details>

<details>
<summary><code>GET /tickets/{id}</code> -- детали тикета</summary>

<br>

**Разрешение:** `tickets:read`

Возвращает тикет с полной информацией: приоритет, отдел, категория, назначенный сотрудник, пользователь, даты, количество сообщений и вложений.

</details>

<details>
<summary><code>POST /tickets</code> -- создать тикет</summary>

<br>

**Разрешение:** `tickets:create`

```json
{
  "subject": "Проблема с доступом",
  "name": "Иван Иванов",
  "email": "user@example.com",
  "message": "Не могу войти в систему",
  "priority_id": 2,
  "dept_id": 1,
  "topic_id": 5
}
```

| Поле | Обязательное | Описание |
|:--|:--|:--|
| `subject` | да | Тема тикета |
| `name` | да | Имя клиента |
| `email` | да | Email клиента |
| `message` | нет | Текст обращения |
| `priority_id` | нет | ID приоритета (по умолчанию 2) |
| `dept_id` | нет | ID отдела (по умолчанию из настроек) |
| `topic_id` | нет | ID категории |

**Ответ:** `201 Created`

</details>

<details>
<summary><code>PUT /tickets/{id}</code> -- обновить тикет</summary>

<br>

**Разрешение:** `tickets:write`

```json
{
  "priority_id": 3,
  "staff_id": 5,
  "dept_id": 2
}
```

</details>

<details>
<summary><code>DELETE /tickets/{id}</code> -- закрыть тикет</summary>

<br>

**Разрешение:** `tickets:delete`

Закрывает тикет (soft delete). Ответ: `200 OK` или `204 No Content`.

</details>

<details>
<summary><code>GET /tickets/{id}/messages</code> -- сообщения тикета</summary>

<br>

**Разрешение:** `tickets:read`

Возвращает массив сообщений с вложениями.

</details>

<details>
<summary><code>POST /tickets/{id}/messages</code> -- добавить сообщение</summary>

<br>

**Разрешение:** `tickets:write`

```json
{
  "body": "Ваш пароль был сброшен"
}
```

**Ответ:** `201 Created`

</details>

<details>
<summary><code>GET|POST /tickets/{id}/notes</code> -- внутренние заметки</summary>

<br>

**Разрешение:** `tickets:read` (GET), `tickets:write` (POST)

```json
{
  "body": "Позвонили клиенту, проблема решена"
}
```

</details>

<p align="right"><a href="#top">наверх</a></p>

---

### Задачи

<details>
<summary><code>GET /tasks</code> -- список задач</summary>

<br>

**Разрешение:** `tasks:read`

| Параметр | Тип | Описание |
|:--|:--|:--|
| `board_id` | int | Фильтр по доске |
| `list_id` | int | Фильтр по списку |
| `status` | string | `open`, `in_progress`, `blocked`, `completed`, `cancelled` |
| `priority` | string | `low`, `normal`, `high`, `urgent` |
| `assignee_id` | int | ID исполнителя |
| `created_by` | int | ID создателя |
| `parent_task_id` | int | Родительская задача (0 -- только корневые) |
| `is_archived` | int | Архивные задачи |
| `is_overdue` | int | Только просроченные |
| `deadline_from` / `deadline_to` | string | ISO-8601 диапазон дедлайнов |
| `search` | string | Поиск в названии/описании |

</details>

<details>
<summary><code>POST /tasks</code> -- создать задачу</summary>

<br>

**Разрешение:** `tasks:create`

```json
{
  "title": "Настроить мониторинг",
  "board_id": 1,
  "list_id": 3,
  "description": "Установить Grafana и настроить алерты",
  "priority": "high",
  "deadline": "2026-04-15T18:00:00Z"
}
```

| Поле | Обязательное | Описание |
|:--|:--|:--|
| `title` | да | Название задачи |
| `board_id` | нет | ID доски |
| `list_id` | нет | ID списка |
| `description` | нет | Описание |
| `priority` | нет | Приоритет |
| `deadline` | нет | Дедлайн (ISO-8601) |

</details>

<details>
<summary><code>PUT /tasks/{id}/status</code> -- изменить статус</summary>

<br>

**Разрешение:** `tasks:write`

```json
{
  "status": "completed"
}
```

Допустимые статусы: `open`, `in_progress`, `blocked`, `completed`, `cancelled`.

</details>

<details>
<summary><code>POST /tasks/{id}/assignees</code> -- добавить исполнителя</summary>

<br>

**Разрешение:** `tasks:write`

```json
{
  "staff_id": 8
}
```

</details>

<details>
<summary><code>DELETE /tasks/{id}/assignees/{staff_id}</code> -- убрать исполнителя</summary>

<br>

**Разрешение:** `tasks:write`

</details>

---

### Доски задач

<details>
<summary><code>GET /taskboards</code> -- список досок</summary>

<br>

**Разрешение:** `tasks:read`

| Параметр | Тип | Описание |
|:--|:--|:--|
| `search` | string | Поиск по названию |

</details>

<details>
<summary><code>POST /taskboards</code> -- создать доску</summary>

<br>

**Разрешение:** `tasks:create`

```json
{
  "board_name": "Проект Alpha",
  "description": "Разработка нового продукта"
}
```

</details>

---

### Пользователи

<details>
<summary><code>GET /users</code> -- список пользователей</summary>

<br>

**Разрешение:** `users:read`

| Параметр | Тип | Описание |
|:--|:--|:--|
| `email` | string | Точный email |
| `search` | string | Поиск по email/имени |
| `created_from` / `created_to` | string | ISO-8601 диапазон |

Возвращает уникальных пользователей (по email) с количеством тикетов.

</details>

<details>
<summary><code>GET /users/{email}</code> -- информация о пользователе</summary>

<br>

**Разрешение:** `users:read`

Path-параметр -- email пользователя. Возвращает профиль с историей тикетов.

</details>

<details>
<summary><code>POST /users</code> -- создать пользователя</summary>

<br>

**Разрешение:** `users:create`

```json
{
  "email": "user@example.com",
  "name": "Иван Иванов",
  "phone": "+7 900 111-22-33"
}
```

</details>

---

### Сотрудники

<details>
<summary><code>GET /staff</code> -- список сотрудников</summary>

<br>

**Разрешение:** `staff:read`

| Параметр | Тип | Описание |
|:--|:--|:--|
| `dept_id` | int | Фильтр по отделу |
| `is_active` | int | Только активные |
| `is_admin` | int | Только администраторы |
| `search` | string | Поиск по имени/email/логину |

</details>

---

### Справочники

<details>
<summary><code>GET /departments</code> -- отделы</summary>

<br>

**Разрешение:** `departments:read`

| Параметр | Тип | Описание |
|:--|:--|:--|
| `search` | string | Поиск по названию |
| `is_public` | int | Только публичные |

`GET /departments/{id}` возвращает отдел с email, руководителем и статистикой тикетов.

</details>

<details>
<summary><code>GET /help-topics</code> -- категории обращений</summary>

<br>

**Разрешение:** `tickets:read`

| Параметр | Тип | Описание |
|:--|:--|:--|
| `dept_id` | int | Фильтр по отделу |
| `is_active` | int | Только активные |
| `search` | string | Поиск по названию |

</details>

<details>
<summary><code>GET /priorities</code> -- приоритеты</summary>

<br>

**Разрешение:** `tickets:read`

Возвращает массив приоритетов (без пагинации): `priority_id`, `name`, `urgency`, `color`.

</details>

---

### База знаний

<details>
<summary><code>GET /kb/documents</code> -- список документов</summary>

<br>

**Разрешение:** `kb:read`

| Параметр | Тип | Описание |
|:--|:--|:--|
| `doc_type` | string | `file` или `link` |
| `audience` | string | `staff`, `client`, `all` |
| `dept_id` | int | Фильтр по отделу |
| `is_enabled` | int | Только активные |
| `search` | string | Поиск по заголовку/описанию |

</details>

<details>
<summary><code>POST /kb/documents</code> -- создать документ</summary>

<br>

**Разрешение:** `kb:create`

```json
{
  "title": "Инструкция по API",
  "doc_type": "link",
  "audience": "staff",
  "external_url": "https://docs.google.com/document/d/abc123",
  "description": "Руководство для разработчиков"
}
```

Загрузка файлов через API не поддерживается -- только ссылки.

</details>

<details>
<summary><code>GET|POST /kb/documents/search</code> -- полнотекстовый поиск</summary>

<br>

**Разрешение:** `kb:read`

```json
{
  "q": "настройка сервера"
}
```

Или через query-параметр: `GET /kb/documents/search?q=настройка`.

</details>

---

### Токен и статистика

<details>
<summary><code>GET /tokens/current</code> -- информация о текущем токене</summary>

<br>

**Разрешение:** любой валидный токен

```json
{
  "token_id": 5,
  "name": "Production Token",
  "type": "permanent",
  "permissions": ["tickets:read", "tickets:write"],
  "is_active": true,
  "is_expired": false,
  "rate_limit": {
    "limit": 1000,
    "remaining": 950,
    "reset_at": "2026-03-29T11:00:00Z",
    "window_seconds": 3600
  },
  "usage": {
    "total_requests": 15234,
    "last_used_at": "2026-03-29T10:45:00Z"
  }
}
```

</details>

<details>
<summary><code>GET /tokens/usage</code> -- статистика использования</summary>

<br>

**Разрешение:** любой валидный токен

| Параметр | Тип | Описание |
|:--|:--|:--|
| `days` | int | Период в днях (1-90, по умолчанию 7) |

Возвращает: общее количество запросов, среднее время ответа, распределение по кодам статуса, топ эндпоинтов.

</details>

<p align="right"><a href="#top">наверх</a></p>

---

## Фильтрация и пагинация

### Пагинация

```
GET /api/v1/tickets?page=2&per_page=50
```

| Параметр | По умолчанию | Макс |
|:--|:--|:--|
| `page` | 1 | -- |
| `per_page` | 20 | 100 |

Ответ содержит блок `meta.pagination`:

```json
{
  "pagination": {
    "total": 245,
    "count": 20,
    "per_page": 20,
    "current_page": 1,
    "total_pages": 13,
    "links": {
      "next": "/api/v1/tickets?page=2",
      "prev": null
    }
  }
}
```

### Сортировка

```
GET /api/v1/tickets?sort=created&order=DESC
```

По умолчанию: `created DESC`.

### Фильтрация

Фильтры передаются query-параметрами. Каждый эндпоинт поддерживает свой набор фильтров (см. описания эндпоинтов выше).

<p align="right"><a href="#top">наверх</a></p>

---

## Формат ответов

### Успешный ответ

```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "version": "1.0",
    "timestamp": "2026-03-29T10:30:00Z",
    "request_id": "a1b2c3d4-e5f6-7890"
  }
}
```

### Ошибка

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "details": {
      "email": ["Email is required"],
      "subject": ["Subject must be at least 3 characters"]
    }
  },
  "meta": { ... }
}
```

### HTTP-коды

| Код | Использование |
|:--|:--|
| `200` | Успешный GET, PUT, PATCH |
| `201` | Успешный POST (создание) |
| `204` | Успешный DELETE |
| `400` | Ошибка валидации |
| `401` | Нет или невалидный токен |
| `403` | Недостаточно прав |
| `404` | Ресурс не найден |
| `409` | Ресурс уже существует |
| `422` | Бизнес-логика запрещает операцию |
| `429` | Превышен rate limit |
| `500` | Ошибка сервера |

### Коды ошибок

| Код | Описание |
|:--|:--|
| `BAD_REQUEST` | Некорректный формат запроса |
| `VALIDATION_ERROR` | Ошибка валидации с деталями |
| `UNAUTHORIZED` | Токен не предоставлен |
| `INVALID_TOKEN` | Токен невалиден, истёк или IP не в whitelist |
| `INSUFFICIENT_PERMISSIONS` | Недостаточно прав |
| `RESOURCE_NOT_FOUND` | Ресурс не найден |
| `DUPLICATE_RESOURCE` | Ресурс уже существует |
| `RATE_LIMIT_EXCEEDED` | Превышен лимит запросов |
| `INTERNAL_ERROR` | Внутренняя ошибка |

<p align="right"><a href="#top">наверх</a></p>

---

## Rate Limiting

Лимит запросов настраивается для каждого токена. По умолчанию: 1000 запросов в час.

### Заголовки ответа

```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 950
X-RateLimit-Reset: 1711706400
X-RateLimit-Window: 3600
```

### Превышение лимита

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Try again in 3600 seconds.",
    "retry_after": 3600
  }
}
```

<p align="right"><a href="#top">наверх</a></p>

---

## Безопасность

### Транспорт

В production рекомендуется HTTPS. Включение принудительного HTTPS:

```sql
UPDATE th_config SET api_require_https = 1;
```

### IP Whitelist

Токены могут иметь список разрешённых IP с поддержкой CIDR:

```
192.168.1.100
10.0.0.0/24
```

### Заголовки безопасности

API автоматически устанавливает:

```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'none'
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000 (HTTPS)
```

### Brute Force Protection

После 5 неудачных попыток аутентификации IP блокируется на 5 минут. Настройка:

```sql
UPDATE th_config SET api_brute_force_max_attempts = 5;
UPDATE th_config SET api_brute_force_window = 300;
```

### Хранение токенов

- Используйте переменные окружения, не хардкод
- Ротируйте токены каждые 90 дней
- При компрометации -- немедленно деактивируйте токен

<p align="right"><a href="#top">наверх</a></p>

---

## Примеры

### PHP

```php
$token = getenv('TICKETHUB_API_TOKEN');
$url   = 'http://localhost:8080/api/v1';

$ch = curl_init("$url/tickets");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'name'    => 'Иван Иванов',
        'email'   => 'user@example.com',
        'subject' => 'Проблема с доступом',
        'message' => 'Не могу войти в систему',
    ]),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        'Content-Type: application/json',
    ],
]);

$result = json_decode(curl_exec($ch), true);
curl_close($ch);

if ($result['success']) {
    echo "Тикет создан: #{$result['data']['number']}";
}
```

### Python

```python
import os, requests

TOKEN = os.environ['TICKETHUB_API_TOKEN']
URL   = 'http://localhost:8080/api/v1'

headers = {
    'Authorization': f'Bearer {TOKEN}',
    'Content-Type': 'application/json',
}

# Список открытых тикетов
resp = requests.get(f'{URL}/tickets', headers=headers,
                    params={'status': 'open', 'per_page': 10})

for ticket in resp.json()['data']:
    print(f"#{ticket['number']}: {ticket['subject']}")

# Создать тикет
resp = requests.post(f'{URL}/tickets', headers=headers, json={
    'name':    'Иван Иванов',
    'email':   'user@example.com',
    'subject': 'Проблема с доступом',
    'message': 'Не могу войти в систему',
})

if resp.status_code == 201:
    print(f"Создан: #{resp.json()['data']['number']}")
```

### JavaScript

```javascript
const TOKEN = process.env.TICKETHUB_API_TOKEN;
const URL   = 'http://localhost:8080/api/v1';

const headers = {
  Authorization: `Bearer ${TOKEN}`,
  'Content-Type': 'application/json',
};

// Список тикетов
const resp = await fetch(`${URL}/tickets?status=open&per_page=10`, { headers });
const { data } = await resp.json();
data.forEach(t => console.log(`#${t.number}: ${t.subject}`));

// Создать тикет
const created = await fetch(`${URL}/tickets`, {
  method: 'POST',
  headers,
  body: JSON.stringify({
    name:    'Иван Иванов',
    email:   'user@example.com',
    subject: 'Проблема с доступом',
    message: 'Не могу войти в систему',
  }),
});

if (created.status === 201) {
  const { data } = await created.json();
  console.log(`Создан: #${data.number}`);
}
```

<p align="right"><a href="#top">наверх</a></p>

---

## Отладка

### Логирование

API автоматически логирует все запросы в таблицу `th_api_logs`:

```sql
SELECT endpoint, method, response_code, response_time, created_at
FROM th_api_logs
ORDER BY created_at DESC
LIMIT 50;
```

### Проверка токена

```bash
curl http://localhost:8080/api/v1/tokens/current \
  -H 'Authorization: Bearer YOUR_TOKEN'
```

### Postman

Коллекция для Postman: [`TicketHub_API_v1.postman_collection.json`](TicketHub_API_v1.postman_collection.json)

<p align="right"><a href="#top">наверх</a></p>

---

## Лицензия

TicketHub распространяется под лицензией GNU General Public License.

<p align="right"><a href="#top">наверх</a></p>
