<a id="top"></a>

<p align="right">
  <a href="README.en.md">English</a>
</p>

<p align="center">
  <img src="images/logo.svg" width="100" alt="TicketHub">
</p>

<h1 align="center">TicketHub</h1>

<p align="center">
  <em>Система управления тикетами, задачами и базой знаний</em><br>
  <em>PHP 8.4 + MySQL 8.0 + Tailwind CSS 3 + REST API</em>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/version-0.1.0-%23FF6B35.svg?style=for-the-badge" alt="v0.1.0">
  <img src="https://img.shields.io/badge/status-active_development-%2300C853.svg?style=for-the-badge" alt="Active Development">
  <img src="https://img.shields.io/badge/PHP-8.4-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.4">
  <img src="https://img.shields.io/badge/MySQL-8.0-%234479A1.svg?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL 8.0">
  <img src="https://img.shields.io/badge/Tailwind_CSS-3-%2306B6D4.svg?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 3">
  <img src="https://img.shields.io/badge/REST_API-v1-%2343853D.svg?style=for-the-badge&logo=json&logoColor=white" alt="REST API">
  <img src="https://img.shields.io/badge/Docker-Ready-%230db7ed.svg?style=for-the-badge&logo=docker&logoColor=white" alt="Docker">
  <img src="https://img.shields.io/badge/PHPUnit-10.5-%23366488.svg?style=for-the-badge&logo=php&logoColor=white" alt="PHPUnit">
</p>

<p align="center">
  <a href="https://tickethub.lavier.tech" target="_blank" rel="noopener">
    <img src="https://img.shields.io/badge/%F0%9F%8C%90%20Открыть%20сайт-tickethub.lavier.tech-%23FF6B35?style=for-the-badge&labelColor=2D3748" alt="Открыть TicketHub">
  </a>
</p>

<p align="center">
  <a href="#-демо">Демо</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#-о-проекте">О проекте</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#-возможности">Возможности</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#-быстрый-старт">Быстрый старт</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#-технологии">Технологии</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#-api">API</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#-тестирование">Тестирование</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#-архитектура">Архитектура</a>
</p>

---

## Демо

<p align="center">
  <a href="#">
    <img src="docs/demo-preview.jpg" alt="TicketHub - демонстрация" width="720">
  </a>
  <br>
  <sub>Нажмите на превью для просмотра видео на Rutube</sub>
</p>

---

## О проекте

**TicketHub v0.1.0** - полнофункциональная система управления обращениями (тикетами), проектами, задачами и базой знаний, выросшая из **osTicket v1.6 RUS**. Проект полностью переработан: миграция на **PHP 8.4** и **MySQL 8.0**, переписан фронтенд на **Tailwind CSS 3**, добавлен **REST API v1** с Bearer-токенами, реализован модуль управления задачами с Kanban-досками, система инвентаризации, база знаний и расширенная админ-панель. Email-подсистема построена на **Symfony Mailer/Mime**. Вся инфраструктура упакована в **Docker** и готова к деплою.

> Проект находится в стадии **активной разработки**. Обновления публикуются в разделе [Releases](https://github.com/laviercasey/TicketHub/releases).

---

## Возможности

<table>
<tr>
<td valign="top" width="50%">

### Клиентский портал

- Создание и отслеживание тикетов
- Просмотр статуса обращения по номеру тикета
- Система приоритетов (Срочный, Высокий, Обычный, Низкий)
- Прикрепление файлов к тикетам
- История переписки по обращению
- База знаний с документацией
- CAPTCHA-защита от спама
- Авторизация по email

</td>
<td valign="top" width="50%">

### Панель управления (SCP)

- Управление тикетами с назначением на сотрудников
- Kanban-доски, списки и календарь задач
- Автоматизация задач и шаблоны
- Учёт времени и подзадачи
- Управление отделами и группами
- Настройка email-аккаунтов (SMTP/POP3/IMAP)
- База знаний для сотрудников и клиентов
- Система инвентаризации
- REST API с управлением токенами
- Интерактивная API-документация

</td>
</tr>
</table>

<p align="right"><a href="#top">наверх</a></p>

---

## Быстрый старт

### Docker (рекомендуется)

```bash
git clone https://github.com/laviercasey/TicketHub.git
cd tickethub
cp .env.example .env
```

Задайте пароли в `.env`:

```env
MYSQL_ROOT_PASSWORD=ваш_root_пароль
MYSQL_PASSWORD=ваш_пароль_бд
DB_HOST=db
DB_NAME=tickethub
DB_USER=tickethub
DB_PASS=ваш_пароль_бд
SECRET_SALT=случайный_32_символьный_hex
ADMIN_EMAIL=admin@yourdomain.com
```

```bash
docker compose up -d --build
```

Откройте **http://localhost:8080** в браузере и перейдите в `/setup/` для первоначальной настройки.

<details>
<summary>Сервисы и порты</summary>

<br>

| Сервис | Порт | Назначение |
|:--|:--|:--|
| **Apache + PHP-FPM** | `8080` | Веб-сервер (PHP 8.4) |
| **MySQL** | `3307` (локально) | База данных (MySQL 8.0) |

</details>

<details>
<summary>Локальная разработка (без Docker)</summary>

<br>

Требования: PHP 8.4+, MySQL 8.0+, Apache с mod_rewrite.

1. Склонируйте репозиторий
2. Создайте базу данных MySQL
3. Скопируйте `include/th-config.sample.php` → `include/th-config.php`
4. Откройте `/setup/` в браузере - мастер создаст `.env` с настройками БД
5. Установите зависимости: `composer install`

</details>

<details>
<summary>Переменные окружения (.env)</summary>

<br>

| Переменная | Описание | По умолчанию |
|:--|:--|:--|
| `MYSQL_ROOT_PASSWORD` | Root-пароль MySQL (для Docker) | - |
| `MYSQL_DATABASE` | Имя базы данных (для Docker) | `tickethub` |
| `MYSQL_USER` | Пользователь БД (для Docker) | `tickethub` |
| `MYSQL_PASSWORD` | Пароль пользователя БД (для Docker) | - |
| `DB_HOST` | Хост БД для PHP | `db` (Docker) / `localhost` |
| `DB_NAME` | Имя БД для PHP | `tickethub` |
| `DB_USER` | Пользователь БД для PHP | `tickethub` |
| `DB_PASS` | Пароль БД для PHP | - |
| `SECRET_SALT` | Ключ шифрования (hex 32 символа) | - |
| `ADMIN_EMAIL` | Email администратора | `admin@localhost` |

</details>

<p align="right"><a href="#top">наверх</a></p>

---

## Технологии

<table>
<tr>
<td valign="top" width="50%">

### Бэкенд

| | Технология |
|:--|:--|
| Язык | **PHP 8.4** |
| База данных | **MySQL 8.0** (mysqli) |
| Веб-сервер | **Apache 2.4** + mod_rewrite |
| Тесты | **PHPUnit 10.5** |
| Зависимости | **Composer** |
| API | **REST API v1** (Bearer Token) |
| Email | **Symfony Mailer/Mime** (SMTP/POP3/IMAP) |
| Контейнеризация | **Docker** + Docker Compose |

</td>
<td valign="top" width="50%">

### Фронтенд

| | Технология |
|:--|:--|
| Стили | **Tailwind CSS 3** |
| Графики | **Chart.js** |
| Календарь | **FullCalendar.js** |
| Drag & Drop | **Sortable.js** |
| Иконки | **Lucide Icons** |
| Даты | **Moment.js** (рус. локализация) |
| Скрипты | **JavaScript** (Vanilla + jQuery) |

</td>
</tr>
</table>

<p align="right"><a href="#top">наверх</a></p>

---

## API

TicketHub предоставляет REST API v1 с Bearer-токен аутентификацией и гранулярными правами доступа.

### Основные эндпоинты

```
GET/POST        /api/v1/tickets              Тикеты
GET/PUT/DELETE  /api/v1/tickets/{id}         Управление тикетом
POST            /api/v1/tickets/{id}/messages Сообщения тикета
GET/POST        /api/v1/tasks                Задачи
GET/PUT/DELETE  /api/v1/tasks/{id}           Управление задачей
GET             /api/v1/staff                Сотрудники
GET             /api/v1/departments          Отделы
GET             /api/v1/helptopics           Категории обращений
GET             /api/v1/kb                   База знаний
GET             /api/v1/users                Пользователи
GET             /api/v1/priorities           Приоритеты
GET             /api/v1/tokens/current       Информация о токене
GET             /api/v1/tokens/usage         Статистика использования
```

### Пример запроса

```bash
curl -X GET http://localhost:8080/api/v1/tickets \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json'
```

<details>
<summary>Система разрешений</summary>

<br>

| Разрешение | Описание |
|:--|:--|
| `tickets:read` | Чтение тикетов |
| `tickets:write` | Создание/изменение тикетов |
| `users:read` / `users:write` | Управление пользователями |
| `staff:read` | Информация о сотрудниках |
| `departments:read` | Информация об отделах |
| `tasks:read` / `tasks:write` | Управление задачами |
| `kb:read` / `kb:write` | База знаний |
| `admin:*` | Полный доступ |

</details>

<details>
<summary>Возможности API</summary>

<br>

- Bearer Token без привязки к IP (опциональный IP whitelist)
- Гранулярные права доступа на каждый токен
- Rate limiting (по умолчанию 1000 запросов/час)
- Фильтрация, сортировка и пагинация
- Полное логирование всех запросов
- Мониторинг метрик и статистики
- Интерактивная документация в админ-панели
- Postman-коллекция для тестирования

</details>

Полная документация API: [`docs/API.md`](docs/API.md)

<p align="right"><a href="#top">наверх</a></p>

---

## Тестирование

Проект покрыт юнит- и интеграционными тестами через PHPUnit 10.5:

```bash
# Все тесты
composer test

# Юнит-тесты
composer test:unit

# Интеграционные тесты
composer test:integration
```

| Уровень | Покрытие |
|:--|:--|
| **Интеграционные тесты** | Banlist, Client, Dept, Document, Email, Group, Inventory, Priority, Staff |
| **Task Manager** | CRUD, Activity, Attachments, Automation, Boards, Comments, Custom Fields, Filters, Permissions, Recurring, Tags, Templates, Timelogs |
| **Юнит-тесты** | Ядро, утилиты |

<p align="right"><a href="#top">наверх</a></p>

---

## Архитектура

### Docker-инфраструктура

```
  Browser ──▶ Apache (:8080) ──▶ PHP 8.4 ──▶ MySQL 8.0 (:3307)
                   │
                   ├── Клиентский портал (/)
                   ├── Панель управления (/scp/)
                   └── REST API (/api/v1/)
```

<details>
<summary>Структура проекта</summary>

<br>

```
tickethub/
├── api/                          # REST API
│   ├── v1/
│   │   ├── controllers/          # Контроллеры API (9 шт.)
│   │   └── index.php             # Роутер API v1
│   ├── cron.php                  # Планировщик задач
│   └── pipe.php                  # Email piping
├── include/                      # Ядро системы
│   ├── class.ticket.php          # Управление тикетами
│   ├── class.task.php            # Управление задачами
│   ├── class.staff.php           # Сотрудники
│   ├── class.client.php          # Клиенты
│   ├── class.email.php           # Email-интеграция
│   ├── class.config.php          # Конфигурация
│   ├── class.apitoken.php        # API-токены
│   ├── class.apisecurity.php     # Безопасность API
│   ├── class.inventory*.php      # Инвентаризация
│   ├── class.document.php        # База знаний
│   ├── class.task*.php           # Задачи (15 классов)
│   ├── staff/                    # Шаблоны панели управления (30+)
│   ├── client/                   # Шаблоны клиентского портала
│   └── th-config.php             # Конфигурация подключения к БД
├── scp/                          # Панель управления (SCP)
│   ├── admin.php                 # Точка входа админки
│   ├── tasks.php                 # Управление задачами
│   ├── taskboards.php            # Kanban-доски
│   ├── tickets.php               # Управление тикетами
│   ├── documents.php             # База знаний
│   ├── inventory*.php            # Инвентаризация
│   ├── css/                      # Стили админки
│   └── js/                       # Скрипты (Kanban, Calendar, Filters)
├── styles/                       # Клиентские стили (Tailwind CSS)
├── tests/                        # PHPUnit-тесты
│   ├── Unit/                     # Юнит-тесты
│   └── Integration/              # Интеграционные тесты (20+)
├── setup/                        # Мастер установки и миграции
│   └── install/migrations/       # SQL-миграции
├── docker-compose.yml            # Docker Compose
├── Dockerfile                    # Образ PHP 8.4 + Apache
├── composer.json                 # PHP-зависимости
├── phpunit.xml                   # Конфигурация тестов
├── tailwind.config.js            # Настройка Tailwind CSS
├── docs/                         # Документация
│   ├── API.md                    # Документация REST API
│   ├── CONTRIBUTING.md           # Руководство для разработчиков
│   └── TicketHub_API_v1.postman_collection.json
├── .env.example                  # Шаблон переменных окружения
└── .htaccess                     # Защита конфигурации
```

</details>

<details>
<summary>Модули системы</summary>

<br>

| Модуль | Классы | Описание |
|:--|:--|:--|
| **Тикеты** | Ticket, Lock, MsgTpl | Обращения, блокировки, шаблоны ответов |
| **Задачи** | Task + 14 подклассов | Kanban, списки, комментарии, теги, автоматизация, учёт времени, повторяющиеся задачи |
| **Пользователи** | Staff, Client, Group, UserSession | Сотрудники, клиенты, группы, сессии |
| **Email** | Email, MailFetch, MailParse | SMTP-отправка, POP3/IMAP-приём, парсинг писем |
| **API** | Api, ApiToken, ApiController, ApiResponse, ApiSecurity, ApiMetrics, ApiMiddleware | REST API, токены, безопасность, метрики |
| **Контент** | Document, Topic, Dept | База знаний, категории обращений, отделы |
| **Инвентаризация** | Inventory, InventoryCatalog, InventoryLocation | Каталог, складские позиции, локации |
| **Утилиты** | Captcha, FileUpload, Format, Validator, Pagenate, Nav | CAPTCHA, загрузка файлов, форматирование, валидация |

</details>

<details>
<summary>Безопасность</summary>

<br>

| Механизм | Реализация |
|:--|:--|
| **Аутентификация** | Сессии (клиенты/сотрудники), Bearer Token (API) |
| **Пароли** | bcrypt (с автомиграцией с MD5) |
| **SQL-инъекции** | Параметризованные запросы через `db_input()` |
| **XSS** | Экранирование через `Format::htmlchars()` |
| **CSRF** | Токен-валидация в админ-панели |
| **Сессии** | HTTPOnly, Secure, SameSite=Strict |
| **API** | Rate limiting, IP whitelist, аудит-логирование |
| **Brute force** | Блокировка после неудачных попыток |
| **Заголовки** | HSTS, X-Frame-Options, CSP, X-Content-Type-Options |

</details>

<details>
<summary>Оптимизация производительности</summary>

<br>

Проведена комплексная оптимизация базы данных:

| Модуль | До | После | Улучшение |
|:--|:--|:--|:--|
| Kanban (50 задач) | 201 запрос | 2 запроса | 99% |
| Список задач (25 задач) | 78 запросов | 3 запроса | 96% |
| Список тикетов (25 тикетов) | 26 запросов | 2 запроса | 92% |
| Статистика тикетов | 5 self-join | 1 запрос | 80-90% |
| Просмотр тикета | 4-11 запросов | 2 запроса | 50-82% |
| **Общее за сессию** | **~400** | **~30** | **92.5%** |

Добавлено 28 индексов базы данных. Время загрузки страниц ускорено в 5-8 раз.

</details>

<p align="right"><a href="#top">наверх</a></p>

---

## Лицензия

Распространяется под лицензией GNU General Public License. Подробнее - [LICENSE](LICENSE).

Основан на [osTicket v1.6 RUS](http://osticket.com/).

<p align="right"><a href="#top">наверх</a></p>
