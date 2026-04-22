# TicketHub — Deployment Guide

**Последнее обновление:** 2026-04-22

## Содержание

1. [Первичный деплой на свежий VPS](#1-первичный-деплой)
2. [Обновление](#2-обновление)
3. [Создание первого администратора](#3-создание-первого-администратора)
4. [Ручной откат](#4-ручной-откат)
5. [Восстановление из бэкапа](#5-восстановление-из-бэкапа)
6. [Healthcheck и диагностика](#6-healthcheck-и-диагностика)
7. [Переменные окружения](#7-переменные-окружения)

---

## 1. Первичный деплой

### 1.1 Требования

- VPS с Docker Engine + Docker Compose v2
- SSH-доступ с sudo
- Домен + реверс-прокси (Caddy / nginx — вне scope, TLS настраивается отдельно)
- `openssl` для генерации секретов

### 1.2 Подготовка директорий на VPS

Выполнить один раз вручную:

```bash
sudo mkdir -p /opt/apps/tickethub /opt/backups/tickethub
sudo chown $USER:$USER /opt/apps/tickethub /opt/backups/tickethub
cd /opt/apps/tickethub
```

### 1.3 Создание `.env` на VPS

```bash
cat > /opt/apps/tickethub/.env << 'EOF'
MYSQL_ROOT_PASSWORD=<openssl rand -base64 32>
MYSQL_DATABASE=tickethub
MYSQL_USER=tickethub
MYSQL_PASSWORD=<openssl rand -base64 32>
DB_HOST=db
DB_NAME=tickethub
DB_USER=tickethub
DB_PASS=<то же что MYSQL_PASSWORD>
SECRET_SALT=<openssl rand -base64 48>
DB_PREFIX=th_
APP_ENV=production
APP_DEBUG=0
APP_VERSION=1.0
APP_URL=https://support.example.ru
ADMIN_EMAIL=alerts@example.ru
IMAGE_TAG=latest
EOF
```

Опционально — для автоматического создания первого администратора при первом запуске:

```bash
cat >> /opt/apps/tickethub/.env << 'EOF'
ADMIN_USERNAME=admin
FIRST_ADMIN_EMAIL=admin@example.ru
ADMIN_PASSWORD_HASH=<php -r "echo password_hash('ВашПароль', PASSWORD_DEFAULT);">
EOF
```

`SECRET_SALT` — никогда не меняйте на работающей системе: это сломает все сессии и токены.

### 1.4 Первый запуск

```bash
cd /opt/apps/tickethub
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

Подождите 45–60 секунд и проверьте:

```bash
docker compose -f docker-compose.prod.yml ps
curl -sf http://localhost/healthz
```

Если `ADMIN_PASSWORD_HASH` не задан, ожидаемый ответ: `{"status":"no_admin",...}` — это нормально. Перейдите к §3.

### 1.5 Создание первого администратора

См. §3 ниже.

### 1.6 Проверка после создания администратора

```bash
curl -sf http://localhost/healthz
```

Ожидаемый ответ: `{"status":"ok","version":"1.0"}`

Войдите через браузер: `https://<your-domain>/scp/login.php`

### 1.7 Инициализация `.last-good-sha`

`.last-good-sha` создаётся автоматически после первого успешного деплоя через GitHub Actions. Откат до этого момента невозможен — в случае сбоя используйте §5.

Если деплой происходит вручную (не через CI), запишите SHA вручную после успешной проверки:

```bash
docker inspect --format='{{index .RepoDigests 0}}' ghcr.io/laviercasey/tickethub:latest > /opt/apps/tickethub/.last-good-sha
```

---

## 2. Обновление

### 2.1 Автоматическое обновление через GitHub Actions

Push в ветку `main`:
1. CI собирает и тестирует образ
2. `backup` job делает `mysqldump` на VPS перед деплоем
3. `deploy` job пушит новый образ и запускает `docker compose up -d`
4. Health-gate проверяет `/healthz` в течение 90 секунд
5. При успехе — записывает SHA в `.last-good-sha`
6. При неудаче — автоматически откатывается на предыдущий SHA

### 2.2 Ручное обновление

```bash
cd /opt/apps/tickethub
PREV_SHA=$(cat .last-good-sha 2>/dev/null || echo "none")
docker compose -f docker-compose.prod.yml pull
docker compose -f docker-compose.prod.yml up -d
```

Bootstrap при старте контейнера автоматически применяет все новые миграции. Проверьте результат:

```bash
for i in $(seq 1 18); do
  if curl -sf http://localhost/healthz | grep -q '"status":"ok"'; then
    echo "deploy healthy"
    docker inspect --format='{{index .RepoDigests 0}}' ghcr.io/laviercasey/tickethub:latest > /opt/apps/tickethub/.last-good-sha
    break
  fi
  sleep 5
done
```

---

## 3. Создание первого администратора

Три способа — выберите один.

### Метод A — Переменные окружения (рекомендуется для автоматизированного деплоя)

Задайте в `.env` перед первым `docker compose up`:

```bash
ADMIN_USERNAME=admin
FIRST_ADMIN_EMAIL=admin@example.ru
ADMIN_PASSWORD_HASH=<bcrypt hash>
```

Сгенерировать хеш:

```bash
php -r "echo password_hash('ВашСильныйПароль', PASSWORD_DEFAULT);"
```

Bootstrap создаст администратора автоматически при старте, если `th_staff` пуста.

### Метод B — CLI интерактивный (рекомендуется для первого раза на VPS)

```bash
docker compose -f docker-compose.prod.yml exec web php bin/first-admin.php
```

Скрипт спросит имя пользователя, email и пароль. Пароль вводится скрыто.

### Метод C — CLI с флагами (для скриптов и автоматизации)

```bash
docker compose -f docker-compose.prod.yml exec web php bin/first-admin.php \
  --username=admin \
  --email=admin@example.ru \
  --password=ВашСильныйПароль
```

Добавьте `--dry-run` для предварительного просмотра SQL без выполнения.

Все три метода отказывают, если `th_staff` уже содержит записи — для сброса пароля существующего администратора используйте штатный интерфейс `/scp/login.php` (ссылка "Забыл пароль") или SQL:

```sql
UPDATE th_staff SET change_passwd=1 WHERE username='admin';
```

---

## 4. Ручной откат

Используйте, если авто-rollback не сработал или нужно вернуться на конкретную версию.

```bash
cd /opt/apps/tickethub
docker image ls ghcr.io/laviercasey/tickethub
```

Выберите нужный тег и обновите `.env`:

```bash
TARGET_SHA=<sha_short>
sed -i "s/^IMAGE_TAG=.*/IMAGE_TAG=$TARGET_SHA/" .env
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d
curl -sf http://localhost/healthz
```

Откат не отменяет миграции базы данных. Если новая версия добавляла колонки или таблицы, а старая версия их не поддерживает — используйте §5 (восстановление из бэкапа).

---

## 5. Восстановление из бэкапа

Бэкапы создаются автоматически перед каждым деплоем и хранятся в `/opt/backups/tickethub/` в виде файлов `pre-deploy-YYYYMMDD-HHMMSS-<sha>.sql.gz`. Срок хранения — 30 дней.

```bash
ls -lt /opt/backups/tickethub/ | head -10
```

Выберите нужный файл и восстановите:

```bash
BACKUP_FILE=/opt/backups/tickethub/pre-deploy-<дата-sha>.sql.gz

docker compose -f docker-compose.prod.yml stop web

gunzip -c "$BACKUP_FILE" | docker compose -f docker-compose.prod.yml exec -T db \
  mysql -u root -p"$MYSQL_ROOT_PASSWORD" tickethub

docker compose -f docker-compose.prod.yml up -d
curl -sf http://localhost/healthz
```

После восстановления БД при необходимости откатите образ (§4).

---

## 6. Healthcheck и диагностика

### Endpoint `/healthz`

Публичный endpoint без авторизации. Используется Docker HEALTHCHECK и CI/CD health-gate.

| HTTP-статус | Тело ответа | Значение |
|---|---|---|
| 200 | `{"status":"ok","version":"1.0"}` | Всё работает |
| 503 | `{"status":"db_down",...}` | MySQL недоступен |
| 503 | `{"status":"not_installed"}` | `th_config` пуста — bootstrap не прошёл |
| 503 | `{"status":"no_admin"}` | `th_staff` пуста — нужно создать администратора |
| 503 | `{"status":"drift","db_version":"X","app_version":"Y"}` | Версия кода не совпадает с версией схемы БД |

### Типичные проблемы

**`drift` после деплоя**

Причина: bootstrap не применил миграцию (ошибка в SQL).

```bash
docker compose -f docker-compose.prod.yml logs web | grep bootstrap
```

Исправьте миграцию и повторите деплой.

**`no_admin` после свежей установки**

Причина: `ADMIN_PASSWORD_HASH` не задан в `.env` и `bin/first-admin.php` не запускался.

```bash
docker compose -f docker-compose.prod.yml exec web php bin/first-admin.php
```

**`not_installed`**

Причина: entrypoint упал до применения bootstrap (например, не задан обязательный env).

```bash
docker compose -f docker-compose.prod.yml logs web | tail -30
```

**Контейнер в restart-loop**

Причина: `docker-entrypoint.sh` упал из-за отсутствующего env или сломанной миграции.

```bash
docker compose -f docker-compose.prod.yml logs web | tail -50
```

Обязательные переменные: `DB_HOST`, `DB_PASS`, `SECRET_SALT`. Если они не заданы, entrypoint завершается с ошибкой до старта Apache.

---

## 7. Переменные окружения

Полный список: [`.env.example`](../.env.example). Ниже критичные переменные.

| Переменная | Обяз. | Описание |
|---|---|---|
| `DB_HOST` | да | Хост MySQL (обычно `db` внутри Compose) |
| `DB_NAME` | да | Имя базы данных |
| `DB_USER` | да | Пользователь БД |
| `DB_PASS` | да | Пароль БД (не root) |
| `DB_PREFIX` | нет | Префикс таблиц; по умолчанию `th_` |
| `SECRET_SALT` | да | 32+ случайных символа; **никогда не меняйте на работающей системе** |
| `ADMIN_EMAIL` | нет | Email для системных уведомлений (не для создания администратора) |
| `ADMIN_USERNAME` | нет | Логин первого администратора (seed при пустой `th_staff`) |
| `FIRST_ADMIN_EMAIL` | нет | Email первого администратора (seed при пустой `th_staff`) |
| `ADMIN_PASSWORD_HASH` | нет | bcrypt-хеш пароля; **не plaintext**; генерируется: `php -r "echo password_hash('пароль', PASSWORD_DEFAULT);"` |
| `APP_VERSION` | нет | Версия приложения; используется `/healthz` для проверки drift |
| `IMAGE_TAG` | да (prod) | Тег образа для деплоя и отката |
| `APP_ENV` | нет | `production` / `staging` / `local` |
| `APP_DEBUG` | нет | `0` в prod, `1` в dev |
| `MYSQL_ROOT_PASSWORD` | да (Compose) | Root-пароль MySQL; только для контейнера БД |

Разница между `ADMIN_EMAIL` и `FIRST_ADMIN_EMAIL`:
- `ADMIN_EMAIL` — адрес для системных уведомлений (алерты, email от сервиса).
- `FIRST_ADMIN_EMAIL` — email учётной записи первого администратора, создаваемой при первом запуске.

---

## Смотри также

- [`README.md`](../README.md) — обзор проекта и быстрый старт
- [`docs/API.md`](API.md) — документация REST API
- [`.env.example`](../.env.example) — все переменные окружения с комментариями
