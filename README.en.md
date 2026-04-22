<a id="top"></a>

<p align="right">
  <a href="README.md">Русский</a>
</p>

<p align="center">
  <img src="images/logo.svg" width="100" alt="TicketHub">
</p>

<h1 align="center">TicketHub</h1>

<p align="center">
  <em>Ticket management, task tracking & knowledge base system</em><br>
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
    <img src="https://img.shields.io/badge/%F0%9F%8C%90%20Visit%20Website-tickethub.lavier.tech-%23FF6B35?style=for-the-badge&labelColor=2D3748" alt="Visit TicketHub">
  </a>
</p>

<p align="center">
  <a href="#demo">Demo</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#about">About</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#features">Features</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#quick-start">Quick Start</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#tech-stack">Tech Stack</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#api">API</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#testing">Testing</a>&nbsp;&nbsp;|&nbsp;&nbsp;
  <a href="#architecture">Architecture</a>
</p>

---

## Demo

<p align="center">
  <a href="#">
    <img src="docs/demo-preview.jpg" alt="TicketHub - Demo" width="720">
  </a>
  <br>
  <sub>Click the preview to watch the video on Rutube</sub>
</p>

---

## About

**TicketHub v0.1.0** is a full-featured support ticket management system with project/task tracking and a knowledge base, evolved from **osTicket v1.6 RUS**. The project has been completely reworked: migrated to **PHP 8.4** and **MySQL 8.0**, frontend rebuilt with **Tailwind CSS 3**, added **REST API v1** with Bearer token authentication, implemented a task management module with Kanban boards, an inventory system, a knowledge base, and an extended admin panel. The email subsystem is powered by **Symfony Mailer/Mime**. The entire infrastructure is containerized with **Docker** and ready for deployment.

> The project is under **active development**. Updates are published in [Releases](https://github.com/laviercasey/TicketHub/releases).

---

## Features

<table>
<tr>
<td valign="top" width="50%">

### Client Portal

- Create and track support tickets
- Check ticket status by ticket number
- Priority levels (Urgent, High, Normal, Low)
- File attachments on tickets
- Full conversation history per ticket
- Knowledge base with documentation
- CAPTCHA spam protection
- Email-based authentication

</td>
<td valign="top" width="50%">

### Staff Control Panel (SCP)

- Ticket management with staff assignment
- Kanban boards, list & calendar views for tasks
- Task automation and templates
- Time tracking and subtasks
- Department and group management
- Email account setup (SMTP/POP3/IMAP)
- Knowledge base for staff and clients
- Inventory management system
- REST API with token management
- Interactive API documentation

</td>
</tr>
</table>

<p align="right"><a href="#top">back to top</a></p>

---

## Quick Start

### Docker (recommended)

```bash
git clone https://github.com/laviercasey/TicketHub.git
cd tickethub
cp .env.example .env
```

Set the required values in `.env`:

```env
MYSQL_ROOT_PASSWORD=<openssl rand -base64 32>
MYSQL_PASSWORD=<openssl rand -base64 32>
DB_HOST=db
DB_NAME=tickethub
DB_USER=tickethub
DB_PASS=<same as MYSQL_PASSWORD>
SECRET_SALT=<openssl rand -hex 32>
```

Optionally, seed the first admin automatically on startup:

```env
ADMIN_USERNAME=admin
FIRST_ADMIN_EMAIL=admin@yourdomain.com
ADMIN_PASSWORD_HASH=<php -r "echo password_hash('YourPassword', PASSWORD_DEFAULT);">
```

```bash
docker compose up -d --build
```

Wait ~45 seconds, then check readiness:

```bash
curl -sf http://localhost:8080/healthz
```

Expected response on success: `{"status":"ok","version":"1.0"}`.

If `ADMIN_PASSWORD_HASH` was not set in `.env`, create the first admin interactively:

```bash
docker compose exec web php bin/first-admin.php
```

Log in via browser: **http://localhost:8080/scp/login.php**

For full deployment instructions (VPS, upgrades, rollback, backup restore): [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md)

### Upgrading

```bash
git pull
docker compose build
docker compose up -d
```

Bootstrap applies all pending migrations automatically. The healthcheck should turn green within ~45 seconds.

<details>
<summary>Services & Ports</summary>

<br>

| Service | Port | Purpose |
|:--|:--|:--|
| **Apache + PHP-FPM** | `8080` | Web server (PHP 8.4) |
| **MySQL** | `3307` (local) | Database (MySQL 8.0) |

</details>

<details>
<summary>First-time production deploy (VPS)</summary>

<br>

Before the first `push` to `main`, run once on the VPS:

```bash
sudo mkdir -p /opt/apps/tickethub /opt/backups/tickethub
sudo chown $USER:$USER /opt/apps/tickethub /opt/backups/tickethub
```

Create `/opt/apps/tickethub/.env` with production values (see [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) §1.3).

After the first successful deploy, `.last-good-sha` is created automatically and is used by the auto-rollback mechanism on failure.

</details>

<details>
<summary>Environment variables (.env)</summary>

<br>

| Variable | Description | Default |
|:--|:--|:--|
| `MYSQL_ROOT_PASSWORD` | MySQL root password (Docker) | - |
| `MYSQL_DATABASE` | Database name (Docker) | `tickethub` |
| `MYSQL_USER` | DB user (Docker) | `tickethub` |
| `MYSQL_PASSWORD` | DB user password (Docker) | - |
| `DB_HOST` | DB host for PHP | `db` (Docker) / `localhost` |
| `DB_NAME` | DB name for PHP | `tickethub` |
| `DB_USER` | DB user for PHP | `tickethub` |
| `DB_PASS` | DB password for PHP | - |
| `SECRET_SALT` | Encryption key (32+ chars) | - |
| `ADMIN_EMAIL` | Email for system alerts | `admin@localhost` |
| `ADMIN_USERNAME` | First admin username (seed on fresh install) | - |
| `FIRST_ADMIN_EMAIL` | First admin email (seed on fresh install) | - |
| `ADMIN_PASSWORD_HASH` | bcrypt hash of first admin password (not plaintext) | - |
| `IMAGE_TAG` | Image tag for versioned deploy and rollback | `latest` |

</details>

<p align="right"><a href="#top">back to top</a></p>

---

## Tech Stack

<table>
<tr>
<td valign="top" width="50%">

### Backend

| | Technology |
|:--|:--|
| Language | **PHP 8.4** |
| Database | **MySQL 8.0** (mysqli) |
| Web Server | **Apache 2.4** + mod_rewrite |
| Testing | **PHPUnit 10.5** |
| Dependencies | **Composer** |
| API | **REST API v1** (Bearer Token) |
| Email | **Symfony Mailer/Mime** (SMTP/POP3/IMAP) |
| Containers | **Docker** + Docker Compose |

</td>
<td valign="top" width="50%">

### Frontend

| | Technology |
|:--|:--|
| Styling | **Tailwind CSS 3** |
| Charts | **Chart.js** |
| Calendar | **FullCalendar.js** |
| Drag & Drop | **Sortable.js** |
| Icons | **Lucide Icons** |
| Dates | **Moment.js** (Russian locale) |
| Scripts | **JavaScript** (Vanilla + jQuery) |

</td>
</tr>
</table>

<p align="right"><a href="#top">back to top</a></p>

---

## API

TicketHub provides a REST API v1 with Bearer token authentication and granular permissions.

### Main Endpoints

```
GET/POST        /api/v1/tickets              Tickets
GET/PUT/DELETE  /api/v1/tickets/{id}         Ticket management
POST            /api/v1/tickets/{id}/messages Ticket messages
GET/POST        /api/v1/tasks                Tasks
GET/PUT/DELETE  /api/v1/tasks/{id}           Task management
GET             /api/v1/staff                Staff members
GET             /api/v1/departments          Departments
GET             /api/v1/helptopics           Help topics
GET             /api/v1/kb                   Knowledge base
GET             /api/v1/users                Users
GET             /api/v1/priorities           Priorities
GET             /api/v1/tokens/current       Token info
GET             /api/v1/tokens/usage         Usage statistics
```

### Example Request

```bash
curl -X GET http://localhost:8080/api/v1/tickets \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json'
```

<details>
<summary>Permissions</summary>

<br>

| Permission | Description |
|:--|:--|
| `tickets:read` | Read tickets |
| `tickets:write` | Create/update tickets |
| `users:read` / `users:write` | User management |
| `staff:read` | Staff information |
| `departments:read` | Department information |
| `tasks:read` / `tasks:write` | Task management |
| `kb:read` / `kb:write` | Knowledge base |
| `admin:*` | Full access |

</details>

<details>
<summary>API Capabilities</summary>

<br>

- Bearer Token without IP binding (optional IP whitelist)
- Granular permissions per token
- Rate limiting (default 1000 requests/hour)
- Filtering, sorting, and pagination
- Full request logging
- Metrics monitoring and statistics
- Interactive documentation in admin panel
- Postman collection for testing

</details>

Full API documentation: [`docs/API.md`](docs/API.md)

<p align="right"><a href="#top">back to top</a></p>

---

## Testing

The project is covered by unit and integration tests via PHPUnit 10.5:

```bash
# All tests
composer test

# Unit tests
composer test:unit

# Integration tests
composer test:integration
```

| Level | Coverage |
|:--|:--|
| **Integration tests** | Banlist, Client, Dept, Document, Email, Group, Inventory, Priority, Staff |
| **Task Manager** | CRUD, Activity, Attachments, Automation, Boards, Comments, Custom Fields, Filters, Permissions, Recurring, Tags, Templates, Timelogs |
| **Unit tests** | Core, utilities |

<p align="right"><a href="#top">back to top</a></p>

---

## Architecture

### Docker Infrastructure

```
  Browser ──▶ Apache (:8080) ──▶ PHP 8.4 ──▶ MySQL 8.0 (:3307)
                   │
                   ├── Client Portal (/)
                   ├── Staff Control Panel (/scp/)
                   └── REST API (/api/v1/)
```

<details>
<summary>Project Structure</summary>

<br>

```
tickethub/
├── api/                          # REST API
│   ├── v1/
│   │   ├── controllers/          # API controllers (9)
│   │   └── index.php             # API v1 router
│   ├── cron.php                  # Task scheduler
│   └── pipe.php                  # Email piping
├── include/                      # Core system
│   ├── class.ticket.php          # Ticket management
│   ├── class.task.php            # Task management
│   ├── class.staff.php           # Staff
│   ├── class.client.php          # Clients
│   ├── class.email.php           # Email integration
│   ├── class.config.php          # Configuration
│   ├── class.apitoken.php        # API tokens
│   ├── class.apisecurity.php     # API security
│   ├── class.inventory*.php      # Inventory
│   ├── class.document.php        # Knowledge base
│   ├── class.task*.php           # Tasks (15 classes)
│   ├── staff/                    # SCP templates (30+)
│   ├── client/                   # Client portal templates
│   └── th-config.php             # DB connection config
├── scp/                          # Staff Control Panel
│   ├── admin.php                 # Admin entry point
│   ├── tasks.php                 # Task management
│   ├── taskboards.php            # Kanban boards
│   ├── tickets.php               # Ticket management
│   ├── documents.php             # Knowledge base
│   ├── inventory*.php            # Inventory
│   ├── css/                      # Admin styles
│   └── js/                       # Scripts (Kanban, Calendar, Filters)
├── styles/                       # Client styles (Tailwind CSS)
├── tests/                        # PHPUnit tests
│   ├── Unit/                     # Unit tests
│   └── Integration/              # Integration tests (20+)
├── bin/                          # CLI scripts
│   ├── db-bootstrap.php          # Idempotent DB bootstrap (run by entrypoint)
│   ├── first-admin.php           # Create first admin (interactive / flags)
│   └── db-seed.php               # Apply named seed sets
├── db/seeds/                     # Seed data (dev/staging only)
│   └── dev_demo_data.php         # Demo data
├── setup/                        # Migrations and install utilities
│   └── install/migrations/       # PHP migrations (idempotent)
├── healthz.php                   # Healthcheck endpoint (/healthz)
├── docker-compose.yml            # Docker Compose
├── docker-entrypoint.sh          # Entrypoint: env validation + bootstrap + Apache
├── Dockerfile                    # PHP 8.4 + Apache image
├── composer.json                 # PHP dependencies
├── phpunit.xml                   # Test configuration
├── tailwind.config.js            # Tailwind CSS config
├── docs/                         # Documentation
│   ├── API.md                    # REST API documentation
│   ├── DEPLOYMENT.md             # Deployment, rollback and restore guide
│   ├── CONTRIBUTING.md           # Developer guide
│   └── TicketHub_API_v1.postman_collection.json
├── .env.example                  # Environment variables template
└── .htaccess                     # Configuration protection
```

</details>

<details>
<summary>System Modules</summary>

<br>

| Module | Classes | Description |
|:--|:--|:--|
| **Tickets** | Ticket, Lock, MsgTpl | Support tickets, locking, response templates |
| **Tasks** | Task + 14 subclasses | Kanban, lists, comments, tags, automation, time tracking, recurring tasks |
| **Users** | Staff, Client, Group, UserSession | Staff, clients, groups, sessions |
| **Email** | Email, MailFetch, MailParse | SMTP sending, POP3/IMAP receiving, email parsing |
| **API** | Api, ApiToken, ApiController, ApiResponse, ApiSecurity, ApiMetrics, ApiMiddleware | REST API, tokens, security, metrics |
| **Content** | Document, Topic, Dept | Knowledge base, help topics, departments |
| **Inventory** | Inventory, InventoryCatalog, InventoryLocation | Catalog, stock items, locations |
| **Utilities** | Captcha, FileUpload, Format, Validator, Pagenate, Nav | CAPTCHA, file uploads, formatting, validation |

</details>

<details>
<summary>Security</summary>

<br>

| Mechanism | Implementation |
|:--|:--|
| **Authentication** | Sessions (clients/staff), Bearer Token (API) |
| **Passwords** | bcrypt (with auto-migration from MD5) |
| **SQL Injection** | Parameterized queries via `db_input()` |
| **XSS** | Output escaping via `Format::htmlchars()` |
| **CSRF** | Token validation in admin panel |
| **Sessions** | HTTPOnly, Secure, SameSite=Strict |
| **API** | Rate limiting, IP whitelist, audit logging |
| **Brute Force** | Lockout after failed attempts |
| **Headers** | HSTS, X-Frame-Options, CSP, X-Content-Type-Options |

</details>

<details>
<summary>Performance Optimization</summary>

<br>

Comprehensive database optimization has been performed:

| Module | Before | After | Improvement |
|:--|:--|:--|:--|
| Kanban (50 tasks) | 201 queries | 2 queries | 99% |
| Task list (25 tasks) | 78 queries | 3 queries | 96% |
| Ticket list (25 tickets) | 26 queries | 2 queries | 92% |
| Ticket stats | 5 self-joins | 1 query | 80-90% |
| Ticket detail | 4-11 queries | 2 queries | 50-82% |
| **Total per session** | **~400** | **~30** | **92.5%** |

28 database indexes added. Page load times improved 5-8x.

</details>

<p align="right"><a href="#top">back to top</a></p>

---

## License

Distributed under the GNU General Public License. See [LICENSE](LICENSE) for more details.

Based on [osTicket v1.6 RUS](http://osticket.com/).

<p align="right"><a href="#top">back to top</a></p>
