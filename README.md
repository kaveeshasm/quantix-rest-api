# Quantix Service Request & Approval API

A Laravel REST API where employees submit internal service requests, managers approve, reject or return them, and Operations users assign and complete the work. Every request has a status, an owner and a full activity history.

## Stack

Laravel 11+, PHP 8.2+, SQLite, Laravel Sanctum (token auth), PHPUnit, Laravel Pint, Larastan.

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for key design decisions and trade-offs.

## Setup

```bash
git clone https://github.com/kaveeshasm/quantix-rest-api.git
cd quantix-rest-api
composer install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
```

Create the SQLite database file:

```bash
touch database/database.sqlite          # macOS / Linux
type nul > database\database.sqlite     # Windows (cmd / PowerShell)
```

Then migrate, seed and start the server:

```bash
php artisan migrate:fresh --seed
php artisan serve
```

Queued notifications use the database queue. To process them:

```bash
php artisan queue:work
```

## Seeded logins (password for all: `password`)

| Email | Role |
|---|---|
| manager@example.com | Manager (Alice and Bob report to her) |
| alice@example.com | Employee |
| bob@example.com | Employee |
| ops@example.com | Operations |
| ops2@example.com | Operations |

The seeder also creates 10 requests covering every status (ids 1 to 10).

## Run the tests

```bash
php artisan test
```

Tests use an in-memory SQLite database and the sync queue, so no extra setup is needed. Unit tests cover the workflow rules. Feature tests cover the happy path per role, every forbidden action (403), invalid transitions (422), and the notification job being dispatched.

## Code quality

```bash
./vendor/bin/pint
./vendor/bin/phpstan analyse --memory-limit=1G
```

Formatting uses Laravel Pint. Larastan runs at level 5 and still reports findings, mainly Resource properties and Eloquent casts that static analysis cannot see. Cleaning these up with `@mixin` annotations is listed under next steps.

## API documentation

Import `docs/Quantix-Service-Requests.postman_collection.json` into Postman. Send the three Login requests first. Tokens are saved automatically into collection variables, so you do not paste anything. Run `php artisan migrate:fresh --seed` before each full run, since statuses move forward and tokens are wiped.

| Method | Endpoint | Role |
|---|---|---|
| POST | /api/login | Any |
| POST | /api/logout | Any (extra) |
| POST | /api/requests | Employee |
| GET | /api/requests | All, scoped by role |
| GET | /api/requests/{id} | All, via policy |
| PUT | /api/requests/{id} | Employee (draft or returned) |
| POST | /api/requests/{id}/submit | Employee |
| POST | /api/requests/{id}/decision | Manager |
| POST | /api/requests/{id}/assign | Operations |
| POST | /api/requests/{id}/status | Operations |
| POST | /api/requests/{id}/comments | Participants |

`logout` is an extra endpoint so tokens can be revoked. List supports `search`, `status`, `sort`, `direction`, `per_page` and returns pagination `links` and `meta`.

## Workflow

| From | Allowed next |
|---|---|
| draft, returned | submitted |
| submitted | approved, rejected, returned |
| approved | assigned |
| assigned | in_progress |
| in_progress | completed |
| rejected, completed | none (final) |

Invalid transitions return 422. Wrong person returns 403.

## Assumptions

- The model is named `ServiceRequest` (Laravel already has a `Request` class); URLs stay `/api/requests`.
- Managers do not see drafts. Employees see only their own requests. Operations see requests from `approved` onward.
- Comment is required when a manager rejects or returns a request.
- Participants who can comment: the requester, their manager (once submitted), and Operations (once approved). Comments are allowed on final requests.
- Any Operations user can work any request; the assignee must have the Operations role.
- Notifications use the database channel. Roles are a single `role` column, not a roles table.

## What I would do next

- Lock the row (`lockForUpdate`) inside the transition transaction to prevent races.
- Add a mail notification channel.
- File attachments, rate limiting, SLA due dates and an overdue flag.
- Clean up Larastan findings with `@mixin` annotations.
- Docker/Sail setup.

## Packages, references and AI usage

- Packages: laravel/framework, laravel/sanctum, phpunit, laravel/pint, larastan/larastan.
- References: Laravel documentation (Eloquent, Policies, Form Requests, Queues,