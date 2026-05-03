# SHIFTFlow

[![PHP](https://img.shields.io/badge/PHP-8.2-8892BF?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Tests](https://img.shields.io/badge/tests-122%20passing-brightgreen)](#running-tests)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%206-blue)](https://phpstan.org)

REST API for managing employee shift schedules at Wieliczka Salt Mine. Handles three roles (admin, manager, employee), 20+ mine positions, business validation for shift conflicts and hour limits, JWT authentication, and CSV import for bulk employee onboarding.

---

## Tech Stack

- **PHP** 8.2
- **Laravel** 12
- **MySQL** 8.0
- **Redis** 7 — JWT blacklist, cache
- **Docker** + Docker Compose
- **JWT Auth** — `tymon/jwt-auth`
- **Testing** — PestPHP (feature tests against real MySQL, no SQLite)
- **Static analysis** — PHPStan / Larastan, level 6, no baseline

---

## Architecture

### Request lifecycle

```
Route (routes/api.php)
  → Middleware (auth:api, role:admin,manager,...)
  → FormRequest (input validation)
  → Controller (thin — delegates to services)
  → Service Layer (business logic)
  → Repository (DB writes) / Model (DB reads)
  → Resource (JSON serialization)
```

### Design patterns

**Chain of Responsibility — shift validation**
`ValidationService` runs 7 validators in order, stopping on the first failure:

```
PositionPermission → Availability → PositionUniqueness → TimeConflict
  → MinimumBreak → MaxHoursPerMonth → MaxHoursPerQuarter
```

Each validator implements `ShiftValidatorInterface`. The execution order is defined in `AppServiceProvider`, not hardcoded in the service — adding or reordering a validator requires no changes to `ValidationService` itself (Dependency Inversion).

**Pipeline — CSV import**
`EmployeesCsvExtractor → EmployeeCsvValidator → EmployeeDataAssembler → EmployeeRepository`

Each stage has a single responsibility and operates on typed DTOs (`EmployeeImportData`). The separator is auto-detected; column `B` maps to positions B1–B8, `WR` maps to WR/WR2/WR3, etc.

**Value Objects — batch shift creation**
`BatchResult` is a readonly DTO returned from `ScheduleService::addShiftsBatch()`. `ShiftValidationData` carries validated shift data through the validator chain without mutation.

---

## Getting Started

**Prerequisites:** Docker and Docker Compose installed.

**1. Clone and configure**

```bash
git clone <repository-url>
cd SHIFTFlow
cp .env.example .env
cp backend/.env.example backend/.env
```

Open `.env` and set `DB_PASSWORD` and `DB_ROOT_PASSWORD` to any values.
Open `backend/.env` and change `APP_ENV=production` to `APP_ENV=local` and `APP_DEBUG=false` to `APP_DEBUG=true`.

**2. Start containers**

```bash
docker compose up -d
```

API available at `http://localhost:8000`.

**3. Initialize the application**

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan jwt:secret
docker compose exec app php artisan migrate:fresh --seed
```

**4. Verify the setup**

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"password"}'
```

A successful response returns a JWT token in `access_token`. Use it as `Authorization: Bearer <token>` on all subsequent requests.

---

## Demo Accounts

Seeded by `php artisan migrate:fresh --seed`. Includes one published schedule (April 2026, 26 shifts) and one draft schedule (May 2026).

| Login | Password / PIN | Role |
|-------|----------------|------|
| `admin` | `password` | admin |
| `akowal` | `password` | manager |
| `tnowak` | PIN `1234` | employee |
| `jwisn` | PIN `1111` | employee |
| `knowak` | PIN `2222` | employee |
| `pzajac` | PIN `3333` | employee |
| `adabrow` | PIN `4444` | employee |

Admins and managers log in via `POST /api/auth/login` with `login` + `password`.
Employees log in via `POST /api/auth/login-pin` with `login` + `pin`.

---

## Testing the API

### Postman collection

Import `docs/SHIFTFLOW_API.postman_collection.json`. The collection uses a `base_url` variable (default: `http://localhost:8000`). A second environment targeting the production hosting URL will be added after deployment.

### Automated tests

```bash
docker compose exec app php artisan test
```

122 tests across 19 test files. Tests run against a separate `shiftflow_test` database (created automatically on first `docker compose up` by `docker/mysql/init.sql`).

Run a single file:

```bash
docker compose exec app php artisan test tests/Feature/Schedules/ScheduleBatchTest.php
```

---

## API Reference

All authenticated endpoints require `Authorization: Bearer {token}`.
Base URL: `http://localhost:8000/api`

---

### Authentication

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | `/auth/login` | — | JWT login for admin/manager |
| POST | `/auth/login-pin` | — | PIN login for employee |
| GET | `/auth/me` | Bearer | Return current user data |
| POST | `/auth/logout` | Bearer | Invalidate token (Redis blacklist) |

---

#### `POST /auth/login`

**Request:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `login` | string | ✓ | — |
| `password` | string | ✓ | min 6 chars |

**Response 200:**
```json
{
  "access_token": "eyJ...",
  "token_type": "bearer",
  "expires_in": 32400,
  "user": { "id": 1, "login": "admin", "name": "Admin User", "role": "admin" }
}
```

**Errors:** `401` Wrong credentials | `403` Account inactive | `422` Validation failed

---

#### `POST /auth/login-pin`

**Request:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `login` | string | ✓ | — |
| `pin` | numeric | ✓ | exactly 4 digits |

**Response 200:** same shape as `/auth/login`

**Errors:** `401` Wrong credentials | `403` Account inactive | `422` Validation failed

---

#### `GET /auth/me`

**Response 200:**
```json
{
  "data": {
    "id": 3,
    "name": "Tomasz Nowak",
    "email": "tnowak@example.com",
    "login": "tnowak",
    "role": "employee",
    "is_active": true,
    "hourly_rate": 25,
    "monthly_hour_limit": 160,
    "quarter_hour_limit": 480,
    "break_limit": 11,
    "contract_type": "employment_contract",
    "positions": [
      { "id": 2, "name": "B1", "description": "Ticketing Officer 1", "color": "#FFF200" }
    ],
    "created_at": "2026-05-02T16:07:47+00:00"
  }
}
```

`monthly_hour_limit`, `quarter_hour_limit`, `break_limit` are returned in hours (converted from minutes stored in DB).

---

#### `POST /auth/logout`

**Response 200:** `{ "message": "Logged out successfully" }`

The `jti` claim is stored in Redis with TTL equal to the token's remaining lifetime. Re-using the token after logout returns `401`.

**Errors:** `401` No token provided

---

### Schedules

**Auth:** Admin or Manager

| Method | Path | Description |
|--------|------|-------------|
| GET | `/schedules` | List schedules (paginated) |
| POST | `/schedules` | Create schedule |
| GET | `/schedules/{id}` | Get schedule with shifts |
| PATCH | `/schedules/{id}` | Update name / description |
| DELETE | `/schedules/{id}` | Delete schedule (cascades to shifts) |
| POST | `/schedules/{id}/shifts/batch` | Add shifts in bulk |
| POST | `/schedules/{id}/publish` | Publish draft schedule |

---

#### `GET /schedules`

**Query parameters:**

| Param | Type | Notes |
|-------|------|-------|
| `month` | integer | 1–12 |
| `year` | integer | — |
| `search` | string | search by name |
| `per_page` | integer | default 20, max 150 |

**Response 200:** Paginated. Each item:
```json
{
  "id": 1,
  "name": "April 2026 Schedule",
  "description": "Schedule for April 2026",
  "month": 4,
  "year": 2026,
  "status": "published",
  "published_at": "2026-05-02T16:07:48+00:00",
  "created_by": "Admin User",
  "total_shifts": 26,
  "created_at": "2026-05-02T16:07:48+00:00"
}
```

`status`: `draft` | `published`. `published_at` is `null` when draft.

---

#### `POST /schedules`

**Request:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | ✓ | max 255 |
| `month` | integer | ✓ | 1–12, unique per year |
| `year` | integer | ✓ | current year to current+5 |
| `description` | string | — | — |

**Response 201:**
```json
{
  "data": {
    "id": 4, "name": "June 2026 Schedule", "month": 6, "year": 2026,
    "status": "draft", "published_at": null, "total_shifts": 0,
    "created_by": "Admin User", "created_at": "...", "updated_at": "..."
  },
  "message": "Schedule created successfully"
}
```

---

#### `GET /schedules/{id}`

Full schedule including `shifts` array and `updated_at`.

**Response 200:**
```json
{
  "data": {
    "id": 4, "name": "June 2026 Schedule", "month": 6, "year": 2026,
    "status": "draft", "shifts": [], "total_shifts": 0,
    "created_at": "...", "updated_at": "..."
  }
}
```

---

#### `PATCH /schedules/{id}`

Updates `name` and/or `description` only. Month and year are immutable after creation.

**Response 200:** Schedule object (without `shifts` array) + `message`.

---

#### `DELETE /schedules/{id}`

Cascade-deletes all associated shifts.

**Response 200:** `{ "message": "Schedule deleted successfully" }`

---

#### `POST /schedules/{id}/shifts/batch`

Add multiple shifts in a single atomic transaction. If any shift fails validation, none are saved.

**Request:**
```json
{
  "shifts": [
    {
      "client_temp_id": "row_1",
      "user_id": 3,
      "position_id": 2,
      "date": "2026-05-10",
      "shift_start": "07:00",
      "shift_end": "15:00",
      "notes": "Morning shift"
    }
  ]
}
```

**Per-shift fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `client_temp_id` | string | ✓ | distinct within batch — used to key errors in response |
| `user_id` | integer | ✓ | must exist |
| `position_id` | integer | ✓ | must exist |
| `date` | string | ✓ | Y-m-d, must match schedule month/year |
| `shift_start` | string | ✓ | H:i |
| `shift_end` | string | ✓ | H:i |
| `notes` | string | — | max 500 |
| `status` | string | — | `scheduled` \| `cancelled` |

**Business rules checked per shift:** user assigned to position, user active, no time overlap on same date, minimum break between shifts, monthly hour limit, quarterly hour limit.

**Response 201:**
```json
{
  "message": "Batch created successfully",
  "count": 1,
  "shifts": [
    {
      "id": 27, "schedule_id": 2, "user_id": 3, "user_name": "Tomasz Nowak",
      "position_id": 2, "position_name": "B1",
      "date": "2026-05-10", "shift_start": "07:00", "shift_end": "15:00",
      "minutes_worked": 480, "hours_worked": 8,
      "status": "scheduled", "notes": "Morning shift",
      "created_at": "...", "updated_at": "..."
    }
  ]
}
```

No `data` wrapper — flat response with `message`, `count`, `shifts`.

**Error 422 — business validation failed:**
```json
{
  "message": "Batch validation failed",
  "errors": {
    "row_1": {
      "conflict": ["User has no permission for the selected position"]
    }
  }
}
```

Errors are keyed by `client_temp_id`. Error type (`conflict`, etc.) is the inner key.

---

#### `POST /schedules/{id}/publish`

**Response 200:** Full schedule with `status: "published"` and `published_at` set to current timestamp.

**Error 409:** `{ "message": "Schedule is already published" }`

---

### Shifts

**Auth:** Admin/Manager for write. Employees can read their own shifts from published schedules only.

| Method | Path | Description |
|--------|------|-------------|
| GET | `/shifts` | List shifts (paginated) |
| GET | `/shifts/{id}` | Get single shift |
| PATCH | `/shifts/{id}` | Update shift |
| DELETE | `/shifts/{id}` | Delete shift |
| POST | `/shifts` | **Deprecated** — use batch endpoint |

---

#### `GET /shifts`

**Query parameters:**

| Param | Type | Notes |
|-------|------|-------|
| `user_id` | integer | filter by user |
| `date` | string | Y-m-d, exact date |
| `from` | string | Y-m-d, range start |
| `to` | string | Y-m-d, range end |
| `per_page` | integer | default 50, max 150 |

**Response 200:** Paginated list. Each shift:
```json
{
  "id": 1, "schedule_id": 1, "user_id": 3, "user_name": "Tomasz Nowak",
  "position_id": 2, "position_name": "B1",
  "date": "2026-04-07", "shift_start": "07:00", "shift_end": "15:00",
  "minutes_worked": 480, "hours_worked": 8,
  "status": "scheduled", "notes": null,
  "created_at": "...", "updated_at": "..."
}
```

---

#### `PATCH /shifts/{id}`

**Request:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `user_id` | integer | ✓ | — |
| `position_id` | integer | ✓ | — |
| `date` | string | ✓ | Y-m-d |
| `shift_start` | string | ✓ | H:i |
| `shift_end` | string | ✓ | H:i |
| `schedule_id` | integer | — | — |
| `status` | string | — | `scheduled` \| `cancelled` |
| `notes` | string | — | max 500 |

**Response 200:** Updated shift + `message`.

---

#### `DELETE /shifts/{id}`

**Response 200:** `{ "message": "Shift deleted successfully" }`

---

#### `POST /shifts` (Deprecated)

**Response 410:** `{ "message": "Use POST /api/schedules/{id}/shifts/batch instead" }`

---

### Positions

**Auth:** Admin/Manager for read. Admin only for write.

| Method | Path | Description |
|--------|------|-------------|
| GET | `/positions` | List positions (paginated, 20/page) |
| GET | `/positions/{id}` | Get single position |
| GET | `/positions/{id}/shifts` | List shifts for a position |
| POST | `/positions` | Create position |
| PATCH | `/positions/{id}` | Update position |
| DELETE | `/positions/{id}` | Delete position |

**System vs user-created positions:** Positions seeded with no creator return only `id`, `name`, `description`, `color`. Positions created via the API additionally include `creator_name` and `created_at`.

---

#### `GET /positions`

**Response 200:** Paginated (20/page):
```json
{ "id": 1, "name": "PD", "description": "Dispatcher Assistant", "color": "#F7C58A" }
```

---

#### `POST /positions`

**Request:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | ✓ | unique, max 4 chars |
| `description` | string | — | max 255 |
| `color` | string | — | hex `#RRGGBB` |

**Response 201:**
```json
{
  "data": {
    "id": 28, "name": "B9", "description": "New cashier position",
    "color": "#FFF200", "creator_name": "Admin User", "created_at": "..."
  },
  "message": "Position created successfully"
}
```

---

#### `DELETE /positions/{id}`

**Response 200:** `{ "message": "Position deleted successfully" }`

**Error 409:** `{ "message": "Cannot delete position with linked shifts." }`

---

### Employees

**Auth:** Admin only. Returns only `role: employee` — admins and managers are excluded.

| Method | Path | Description |
|--------|------|-------------|
| GET | `/employees` | List employees (paginated) |
| GET | `/employees/{id}` | Get employee |
| POST | `/employees` | Create employee |
| PATCH | `/employees/{id}` | Update employee |
| DELETE | `/employees/{id}` | Soft delete (preserves shift history) |
| POST | `/employees/import` | Bulk import from CSV |

---

#### `GET /employees`

**Query parameters:** `search` (name), `per_page` (default 50, max 150)

**Response 200:** Paginated. Each employee:
```json
{
  "id": 3, "name": "Tomasz Nowak", "email": "tnowak@example.com",
  "login": "tnowak", "role": "employee", "is_active": true,
  "hourly_rate": 25, "monthly_hour_limit": 160,
  "quarter_hour_limit": 480, "break_limit": 11,
  "contract_type": "employment_contract",
  "positions": [
    { "id": 2, "name": "B1", "description": "Ticketing Officer 1", "color": "#FFF200" }
  ],
  "created_at": "..."
}
```

---

#### `POST /employees`

**Request:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `name` | string | ✓ | max 255 |
| `pin` | numeric | ✓ | exactly 4 digits |
| `positions` | array | ✓ | min 1 position ID |
| `hourly_rate` | numeric | — | min 0 |
| `contract_type` | string | — | `employment_contract` \| `mandate_contract` |
| `max_minutes_per_month` | integer | — | min 0 |
| `max_minutes_per_quarter` | integer | — | min 0 |
| `min_break_minutes` | integer | — | min 0 |

New employees start as `is_active: false`. Login is auto-generated from name (`LoginGeneratorService`). Email is not required.

**Response 201:** Full employee object + `message`.

---

#### `PATCH /employees/{id}`

Same fields as create, all optional. `positions` is fully replaced (sync), not merged. `login` is immutable after creation.

**Response 200:** Updated employee + `message`.

---

#### `DELETE /employees/{id}`

Soft delete — historical shift records are preserved.

**Response 200:** `{ "message": "Employee deleted successfully" }`

---

#### `POST /employees/import`

**Content-Type:** `multipart/form-data` | **Form field:** `csv` (file, max 500 rows)

See `docs/employees_import_template.csv` for the format.

**CSV format:**

- Separator: auto-detected (`,` `;` `\t` `|` `:`)
- Column 0: row number (ignored)
- Column 1: employee name — exactly two words (`Jan Kowalski`)
- Column 2: contract type — keyword detection: `UOP` / `EMPLOY` / `ETAT` → `employment_contract`; `ZLEC` / `MANDATE` → `mandate_contract`
- Columns 3+: position headers — value `TAK` = assigned

| CSV column | Maps to DB positions |
|------------|---------------------|
| `B` | B1, B2, B3, B4, B5, B6, B7, B8 |
| `WR` | WR, WR2, WR3 |
| `WS` | WS, WS2 |
| `PW` | PW, PW2 |
| `K` | K1, K2 |
| `OTG` | OTG, OTG2 |
| `PTG` | PTG, PTG2 |
| `PD`, `SR`, `TG`, `TGT`, `BT` | one-to-one |

**Response 200:**
```json
{ "success": true, "created": 98, "updated": 2, "total": 100, "validation_issues": [] }
```

Existing employees matched by name are updated, not duplicated. All imported accounts receive the default PIN from `DEFAULT_EMPLOYEE_PIN` env (default `1234`).

**Error 422:** `validation_issues` is populated per-row when names are invalid or contract type is missing.

---

### Availabilities

**Auth:** All authenticated users. Employees see only their own records.

| Method | Path | Description |
|--------|------|-------------|
| GET | `/availabilities` | List (paginated, 20/page) |
| POST | `/availabilities` | Create or update (upsert by user + date) |
| DELETE | `/availabilities/{id}` | Delete |

---

#### `GET /availabilities`

**Query parameter:** `user_id` (admin/manager only — employees are scoped automatically)

**Response 200:** Paginated list.

---

#### `POST /availabilities`

Same user + date = update (returns 200). New record = create (returns 201).

**Request:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `date` | string | ✓ | date format |
| `is_available` | boolean | ✓ | — |
| `notes` | string | — | max 255 |
| `user_id` | integer | — | auto-assigned for employees; required when admin/manager targets another user |

**Response 201:**
```json
{
  "data": {
    "id": 1, "user_id": 3, "date": "2026-06-10",
    "is_available": true, "notes": "Morning only",
    "created_at": "...", "updated_at": "..."
  },
  "message": "Availability created successfully"
}
```

**Response 200:** Same shape, `message: "Availability updated successfully"`. `created_at` unchanged, `updated_at` reflects the update.

---

#### `DELETE /availabilities/{id}`

**Response 200:** `{ "message": "Availability deleted successfully" }`

---

### Error Response Format

| Status | Shape | When |
|--------|-------|------|
| `401` | `{ "message": "Unauthenticated." }` | Missing, expired, or blacklisted token |
| `403` | `{ "message": "Role not allowed" }` | Role not permitted for endpoint |
| `403` | `{ "message": "Account deactivated." }` | Login attempt with inactive account |
| `404` | `{ "message": "Not Found." }` | Resource does not exist |
| `409` | `{ "message": "..." }` | Conflict — already published, or position has linked shifts |
| `410` | `{ "message": "Use POST /api/schedules/{id}/shifts/batch instead" }` | Deprecated endpoint |
| `422` | `{ "message": "...", "errors": { "field": ["message"] } }` | Validation failed |

---

## Technical Decisions

**Time stored in minutes, not hours**
All duration and limit fields (`minutes_worked`, `max_minutes_per_month`, `max_minutes_per_quarter`, `min_break_minutes`) are stored as integers in minutes. This avoids floating-point precision issues for 7.5-hour shifts, makes arithmetic exact, and prevents limit-check drift from fractional-hour accumulation across many shifts.

**PIN onboarding flow**
Employees imported via CSV receive a default PIN from the `DEFAULT_EMPLOYEE_PIN` environment variable (defaults to `1234`). The employee changes the PIN on first login. This avoids generating and distributing unique PINs for bulk imports, while keeping the default configurable per deployment environment.

**JWT blacklist via Redis**
Standard JWT is stateless — once issued, a token is valid until its expiry time regardless of logout. To make logout actually revoke a token, the `jti` claim is stored in Redis on logout with TTL equal to the token's remaining lifetime. Every authenticated request checks the blacklist before processing.

**Reports module disabled**
Routes and controller for `/api/reports/*` are removed from the codebase. The underlying `ReportService` was querying the `Schedule` model instead of `Shift` — a structural mismatch left from an early refactor. The module will be rewritten from scratch once React SPA frontend requirements are defined.

**ValidationService with Dependency Inversion**
Validators are injected as an ordered array into `ValidationService` via `AppServiceProvider`. Adding or reordering a validator requires no changes to `ValidationService` itself. The chain runs fast→slow (permission and availability checks before expensive query-heavy validations) to fail early and avoid unnecessary DB queries.

---

## Security

- **Rate limiting** — `throttle:5,1` on `/api/auth/login` and `/api/auth/login-pin` (5 requests per minute per IP)
- **Account deactivation** — `is_active` checked on both login flows; inactive accounts return `403` before token is issued
- **JWT secret rotation** — the original secret was committed in an early version of the repo; a new secret was generated and all active tokens were invalidated (commit `b57ddd5`)
- **`APP_DEBUG=false`** — set as default in `backend/.env.example`; stack traces are never exposed in production
- **CORS** — currently allows all origins (`*`); planned restriction to the frontend domain after first deployment

---

## Known Issues

- **CORS wildcard** — `*` is acceptable during development, must be restricted to the frontend domain before any public deployment
- **CSV import timeout** — files over ~300 rows time out due to N+1 writes: `EmployeeRepository::saveMany()` runs one `updateOrCreate` query per row inside a loop; no batching
- **Reports module non-functional** — intentionally removed; `ReportService` was querying the wrong model (`Schedule` instead of `Shift`); routes and controller are absent from the codebase

---

## Planned Improvements

- **Fix CSV import** — replace the per-row loop with `DB::upsert()` and dispatch to a queued job (Laravel Horizon) for files over ~50 rows
- **Rewrite reports module** — rebuild from scratch once React SPA frontend requirements are finalized
- **Restrict CORS** — lock `ALLOWED_ORIGINS` to the frontend domain after deployment
- **React SPA frontend** — planned as a separate repository; all required API fields are documented in `docs/SHIFTFLOW_API.postman_collection.json`
- **Position color coding** — `color` column is already in the DB and returned by all position and shift responses; ready for frontend rendering

---

## Running Tests

```bash
docker compose exec app php artisan test
```

122 tests across 19 test files. Feature tests run against a real MySQL `shiftflow_test` database (no SQLite, no mocks). Unit tests cover `ValidationService` validators, `LoginGeneratorService`, and `TimeHelper`.

Run PHPStan (level 6, no baseline):

```bash
docker compose exec app php -d memory_limit=512M vendor/bin/phpstan analyse --no-progress
```
