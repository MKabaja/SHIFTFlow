# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

SHIFTFlow is a shift-scheduling REST API for Wieliczka Salt Mine. It manages tourist guide schedules across 20+ mine positions (B1-B8, PW, WR, WS, TGT, etc.) with three roles: `admin`, `manager`, `employee`.

## Stack

- **Backend:** Laravel 12 + PHP 8.2, `tymon/jwt-auth` for authentication
- **Database:** MySQL 8.0 (Docker)
- **Testing:** PestPHP (Feature tests use `RefreshDatabase` + real MySQL test DB)
- **Frontend (planned):** React SPA — API spec in `Docks/frontend-api-spec.md`
- **Dev tooling:** Laravel Telescope, Pint (code style), IDE Helper

## Development Environment

Everything runs inside Docker — no local PHP or Composer required.

```bash
# Start containers
docker compose up -d

# Common commands (run from project root)
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan route:list
docker compose exec app php artisan tinker
docker compose exec app composer install
docker compose exec app composer require <package>
```

Services: API at `http://localhost:8000`, phpMyAdmin at `http://localhost:8080`.

Optional `shiftflow` shell alias is documented in `SHIFTFLOW_LOCAL_SETUP.md`.

## Running Tests

Tests run inside the container against a separate `shiftflow_test` database (configured in `.env.testing` and `phpunit.xml`).

```bash
# Run all tests
docker compose exec app php artisan test

# Run a specific test file
docker compose exec app php artisan test tests/Feature/Schedules/ScheduleBatchTest.php

# Run a specific test by name
docker compose exec app php artisan test --filter "batch validation fails"

# Run only unit tests
docker compose exec app php artisan test --testsuite=Unit
```

## Code Style

```bash
# Check style
docker compose exec app ./vendor/bin/pint --test

# Fix style
docker compose exec app ./vendor/bin/pint
```

## Architecture

### Request Lifecycle

```
Route (routes/api.php)
  → Middleware (auth:api, role:admin,manager,...)
  → FormRequest (validation)
  → Controller (thin — delegates to services)
  → Service Layer (business logic)
  → Repository (DB writes) / Model (DB reads)
  → Resource (JSON serialization)
```

### Key Layers

**Controllers** (`app/Http/Controllers/Api/`) — Thin. Inject services, return Resources or JSON responses. No business logic.

**Services** (`app/Services/`) — All business logic lives here:

- `ScheduleService` — schedule creation and the batch shift insertion flow
- `ValidationService` — runs a chain of 7 validators in order (fast → slow); stops on first failure
- `Batch/BatchPreprocessor` — validates array format and date consistency before batch ops
- `Batch/BatchValidationService` — runs `ValidationService` per-shift for batch operations
- `Import/ImportService` — CSV pipeline: `EmployeesCsvExtractor` → `EmployeeCsvValidator` → `EmployeeDataAssembler` → `EmployeeRepository`

**Validators** (`app/Services/Validation/Validators/`) — Each implements `ShiftValidatorInterface`. Execution order in `ValidationService`: `PositionPermission` → `Availability` → `PositionUniqueness` → `TimeConflict` → `MinimumBreak` → `MaxHoursPerMonth` → `MaxHoursPerQuarter`.

**Repositories** (`app/Repositories/`) — Only `EmployeeRepository` exists; handles create/update/upsert (CSV import uses `updateOrCreate` by name).

**DTOs / Value Objects:**

- `ShiftValidationData` — passed through every shift validator
- `EmployeeImportData` — output of CSV assembler
- `BatchResult` — readonly value object returned from `ScheduleService::addShiftsBatch()`

### Authentication

Two login flows via `POST /api/auth/login`:

1. `login` + `password` — for admin/manager (standard credentials)
2. `login` + `pin` via `POST /api/auth/login-pin` — for employees (PIN stored as hashed `pin_hashed`)

JWT token TTL: 9 hours. Guard: `auth:api`. Role enforcement: `RoleMiddleware` registered as `role` alias.

### Data Model Relationships

- `User` ↔ `Position`: many-to-many (`position_user` pivot)
- `User` → `Shift`: one-to-many
- `Schedule` → `Shift`: one-to-many (cascade delete)
- `User` → `Availability`: one-to-many
- `User` uses `SoftDeletes`

### Batch Shift Creation

`POST /api/schedules/{schedule}/shifts/batch` accepts an array of shift objects. Flow:

1. `BatchPreprocessor::prepare()` — validates format, checks dates match schedule month/year, groups by `user_id` and sorts chronologically
2. `BatchValidationService::validate()` — runs `ValidationService` on each shift; collects errors keyed by `client_temp_id`
3. All shifts saved in a single DB transaction; returns `BatchResult`

### Hour Tracking

Time is stored in **minutes** (`minutes_worked`, `max_minutes_per_month`, `max_minutes_per_quarter`, `min_break_minutes`) — not hours. `TimeHelper::calculateMinutesDifference()` handles overnight shifts (crosses midnight).

## Testing Conventions

- All Feature tests use Pest with `RefreshDatabase` and real MySQL (not SQLite)
- Use `User::factory()->manager()->create()` / `->employee()` / `->admin()` state methods
- Tests hit real API routes via `actingAs($user)` — no mocking of DB or services
- Test database: `shiftflow_test` (must exist in the MySQL container)

## Working Mode

You are my mentor and coding partner. Always communicate with me in Polish
(technical terms and code stay in English).

### Division of work:

- **Simple tasks** (boilerplate, migrations, factories, seeders, small components)
  → I delegate these to you, write them immediately without asking
- **Complex tasks** → I write them myself, you guide me
- **Rule:** only generate code I understand 100% —
  if something needs explanation, explain first, then write

### How to guide me when I don't know how to do something:

1. One concrete pseudocode OR one concrete guiding question — never both at once
2. Wait for my response
3. Only if I still don't know — give the next hint
4. Don't write the ready solution until I explicitly ask for it

### Answer immediately without questions when:

- I ask about syntax (PHP, JS, TS)
- I ask about CSS / Tailwind
- I ask about a specific method, hook, or function
- I ask you to explain an error

### Code review:

- When I show code I wrote — review it: what's good, what to improve and why
- Ask about my reasoning if something seems suboptimal

### Anki flashcards:

- When a complex concept worth remembering comes up during discussion
  — propose a flashcard in this format:
  `ANKI: Front | Back`

### Context about me:

- Self-taught developer transitioning from welding to junior developer
- Stack: Laravel 11 + Inertia.js + React + TypeScript
- I know the basics, I want to understand "why" not just "how"
- I value KISS and YAGNI — don't overcomplicate solutions

## Laravel & PHP Guidelines

See `.claude/laravel-coach.md` for full review checklist and patterns.
