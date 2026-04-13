# Contributing to TicketHub

Thank you for your interest in contributing to TicketHub! This guide will help you get started.

## Table of Contents

- [Development Setup](#development-setup)
- [Project Structure](#project-structure)
- [Coding Conventions](#coding-conventions)
- [Testing](#testing)
- [Pull Request Workflow](#pull-request-workflow)

---

## Development Setup

### Prerequisites

- PHP 8.4+
- MySQL 8.0+
- Composer
- Node.js + npm (for Tailwind CSS)
- Docker + Docker Compose (optional, recommended)

### Quick Start with Docker

```bash
git clone https://github.com/YOUR_USERNAME/tickethub.git
cd tickethub
cp .env.example .env
# Edit .env with your passwords
docker compose up -d --build
```

Open `http://localhost:8080/setup/` for initial configuration.

### Local Setup (without Docker)

1. Clone the repository
2. Create a MySQL 8.0 database
3. Copy `include/th-config.sample.php` to `include/th-config.php`
4. Run `composer install`
5. Run `npm install` (for Tailwind CSS)
6. Point Apache with mod_rewrite to the project root
7. Open `/setup/` in your browser

---

## Project Structure

```
tickethub/
├── api/v1/controllers/    # REST API controllers
├── include/               # Core PHP classes (class.*.php)
│   ├── staff/             # SCP (admin panel) templates
│   ├── client/            # Client portal templates
│   └── Mail/              # Mail adapters (PSR-4)
├── scp/                   # Staff Control Panel entry points
│   ├── css/               # Admin styles
│   └── js/                # Admin scripts (Kanban, Calendar, etc.)
├── styles/                # Client-facing styles (Tailwind CSS)
├── tests/
│   ├── Unit/              # Unit tests
│   └── Integration/       # Integration tests
├── setup/install/migrations/  # SQL migration files
└── docs/                  # Documentation
```

### Key Files

| File | Purpose |
|:--|:--|
| `main.inc.php` | Application bootstrap |
| `secure.inc.php` | Security middleware |
| `include/mysql.php` | Database abstraction layer |
| `include/class.config.php` | System configuration |
| `include/class.format.php` | Output formatting and XSS protection |
| `include/class.validator.php` | Input validation |

---

## Coding Conventions

### PHP

- **Version**: Use PHP 8.4 features (typed properties, match expressions, named arguments, enums, readonly properties)
- **Classes**: One class per file, named `class.{name}.php` in `include/`
- **Methods**: Use camelCase for method names
- **Properties**: Use camelCase for property names
- **SQL**: Always use `db_input()` for parameterized queries - never concatenate user input into SQL
- **XSS**: Always escape output with `Format::htmlchars()` or `Format::sanitize()`
- **Error handling**: Use exceptions, not error codes or `die()`

### JavaScript

- Vanilla JS or jQuery for DOM manipulation
- Files go in `scp/js/` for admin panel scripts
- Use strict mode (`'use strict'`)
- No `console.log()` in production code

### CSS

- Tailwind CSS 3 for all styling
- Client styles in `styles/`
- Admin styles in `scp/css/`
- Run `npx tailwindcss` to rebuild after changes

### Database

- All migrations go in `setup/install/migrations/`
- Use `th_` prefix for table names
- Add indexes for columns used in WHERE, JOIN, ORDER BY
- Avoid N+1 queries - use JOINs or batch loading

---

## Testing

The project uses PHPUnit 10.5. Tests are organized into two suites:

```bash
# Run all tests
composer test

# Run only unit tests
composer test:unit

# Run only integration tests
composer test:integration
```

### Writing Tests

- **Unit tests** go in `tests/Unit/` - test individual classes in isolation
- **Integration tests** go in `tests/Integration/` - test class interactions with DB mocks
- Test class naming: `{ClassName}Test.php`
- Use `tests/Helpers/DatabaseMock.php` for database mocking
- Aim for meaningful assertions over high coverage numbers

### Test Example

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testSomething(): void
    {
        $result = someFunction();
        $this->assertEquals('expected', $result);
    }
}
```

---

## Pull Request Workflow

1. **Fork** the repository
2. **Create a branch** from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Make changes** following the coding conventions above
4. **Write/update tests** for your changes
5. **Run tests** to make sure nothing is broken:
   ```bash
   composer test
   ```
6. **Commit** with a clear message:
   ```bash
   git commit -m "Add: webhook notification system for tickets"
   ```
7. **Push** and open a Pull Request against `main`

### Commit Message Format

Use a prefix to describe the type of change:

| Prefix | Purpose |
|:--|:--|
| `Add:` | New feature |
| `Fix:` | Bug fix |
| `Update:` | Enhancement to existing feature |
| `Refactor:` | Code restructuring without behavior change |
| `Docs:` | Documentation only |
| `Test:` | Adding or updating tests |
| `Chore:` | Build, CI, dependency updates |

### PR Checklist

- [ ] Tests pass (`composer test`)
- [ ] No debug statements (`var_dump`, `print_r`, `die`, `dd`)
- [ ] SQL queries use `db_input()` for user input
- [ ] Output is escaped with `Format::htmlchars()`
- [ ] New features have corresponding tests
- [ ] Documentation updated if needed

---

## Questions?

If you have questions about the codebase or need help getting started, open an issue with the `question` label.
