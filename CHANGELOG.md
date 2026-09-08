# Changelog — veldora/framework

All notable changes to `veldora/framework` are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [0.6.0] — 2026-09-08

### Added
- **`Blueprint` Schema Helpers**: Added `date()`, `dateTime()`, `decimal()`, `float()`, `bigInteger()`, `unsignedInteger()`, `foreignId()`, `json()`, and `enum()` with SQLite & MySQL SQL compilation.
- **ORM Model Methods**: Native `Model::create()`, `Model::firstOrCreate()`, and `Model::updateOrCreate()` implementations.
- **Advanced & Polymorphic Database Relations**: Implemented `HasOneThrough`, `MorphTo`, `MorphOne`, `MorphMany`, `MorphToMany`, and `MorphedByMany` relation classes along with Model helper methods (`hasOneThrough`, `morphTo`, `morphOne`, `morphMany`, `morphToMany`, `morphedByMany`).
- **`Relation::__call()` & `get()` Proxying**: `Relation` base class proxies unknown query calls to the underlying `QueryBuilder` and provides `get()` alias, so queries like `$post->comments()->where('approved', '=', 1)->count()` work natively.
- **Dedicated HTTP Response Layer**:
  - `JsonResponse`: Dedicated JSON response extending base `Response` with `::success()`, `::error()`, `::paginate()`, `merge()`, and `setData()`.
  - `RedirectResponse`: Extended redirect response with `::to()`, `::back()`, `::intended()`, and fluent `with()`, `withErrors()`, and `withInput()`.
  - `Session`: Clean framework HTTP session wrapper with flash data lifecycle, old-input persistence, and CSRF token generation/verification.
  - `UploadedFile`: Uploaded file handler with MIME type sniffing, size validation, and `store()` / `storeAs()` persistence.
  - `ResponseFactory`: Injectable factory for creating plain, HTML, text, JSON, redirect, download, inline file, and view responses.
- **Global HTTP Helpers**: Added `response()` helper and updated `redirect()`, `back()`, and `json()` to return dedicated response instances.
- **`Model::observe()` Observer Wiring**: Class-based lifecycle event listener auto-wiring supporting `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting`, `deleted`, `restoring`, `restored`, and `forceDeleted`.
- **`make:observer` CLI Generator**: Generate dedicated Model Observer classes in `app/Observers/` with optional `--model=` flag.
- **`make:rule` CLI Generator**: Generate custom Validation Rule classes in `app/Rules/` implementing `Veldora\Framework\Validation\Rule`.
- **`make:policy` CLI Generator**: Added built-in authorization policy class generator (`app/Policies/{Name}.php`), expanding CLI suite to **51 built-in commands**.
- **Expanded Unit Test Suite**: Added `tests/Unit/ObserverAndRuleTest.php` (13 tests) and comprehensive relation/HTTP tests, bringing test coverage to **110 passing tests and 458 assertions**.

### Fixed
- **`SessionGuard` Resilient Remember Token**: Guarded `remember_token` updates with try-catch so schemas omitting `remember_token` column do not throw `PDOException` on login/logout.

---

## [0.5.7] — 2026-08-30

### Added
- **`down` / `up` commands** — Both are now fully registered in the standalone CLI runner (previously missing from `bin/veldora` switch).
- **`executeDirect()` on all remaining commands** — `MakeControllerCommand`, `MakeModelCommand`, `MakeMigrationCommand`, `MakeSeederCommand`, `MakeMiddlewareCommand`, `MakeRequestCommand`, `MakeResourceCommand`, `MakeFactoryCommand`, `DownCommand`, `UpCommand` — all now callable without Symfony Console bootstrap.
- **48 CLI commands** — Complete standalone + Symfony Console dual-mode runner with all 48 commands properly wired.
- **Queue commands** (`queue:work`, `queue:failed`, `queue:retry`, `queue:clear`) added to standalone CLI runner.

### Fixed
- `make:migration` now generates anonymous `return new class extends Migration {}` format (consistent with `make:auth`).
- `make:model -m` now correctly delegates to `MakeMigrationCommand::executeDirect()` instead of dead Symfony Application lookup.
- `make:auth` now callable directly via `executeDirect()` without Symfony Console guard.

---

## [0.5.6] — 2026-08-30

### Fixed
- **Dynamic Versioning**: Unified runtime versioning across exception handling and CLI diagnostics.

---

## [0.5.5] — 2026-08-30

### Added
- Composer Packagist release tag version metadata unified across core and UI packages.
- Enhanced `executeDirect()` invocation support for command runners.
- Bumped Application version to `0.5.2`.

### Fixed
- Tag version mismatch synchronization for Composer VCS driver.

---

## [0.5.1] — 2026-08-28

### Added
- Route constraint matching optimization.
- View partial cache key resolution.

---

## [0.5.0] — 2026-08-25

### Added
- **DB Facade** (`Veldora\Framework\Database\DB`) — `statement()`, `select()`, `selectOne()`, `insert()`, `update()`, `delete()`, `transaction()`, and dynamic `__call` magic forwarding to `QueryBuilder`.
- **SoftDeletes Trait** — `deleted_at` column auto-management with `withTrashed()`, `onlyTrashed()`, `restore()`, and `forceDelete()` scopes.
- **Model Lifecycle Events** — `creating`, `created`, `updating`, `updated`, `deleting`, `deleted` hooks with `Model::creating(fn)` / `Observer` class support.
- **Named Route URLs** — `route('name', ['param' => 'value'])` global helper for parameter-substituted URL generation.
- **ThrottleRequests Middleware** — Token-bucket rate limiter with configurable `maxAttempts:decayMinutes`, `429 Too Many Requests` header response, and `clear()` method.
- **CheckForMaintenanceMode Middleware** — Reads `storage/framework/.down` status file, returns 503 with bypass `?secret=` query parameter support.
- **Complete Auth Scaffold** — `php veldora make:auth` generates Login, Register, ForgotPassword, ResetPassword, Profile, EmailVerification controllers, User model, migration, routes, and **100% native `.veldora.php`** views (zero raw PHP tags, zero CDN CSS).
- **PasswordBroker** — Token-based password reset with HMAC generation, expiry validation, and password update flow.
- **Console Polyfill** (`src/Console/Polyfill.php`) — Zero-dependency Symfony\Console compatibility shim; all commands work in environments without Composer Symfony packages.
- **Anonymous Migration Classes** — `MakeAuthCommand` generates anonymous `return new class extends Migration {}` migrations to prevent class-name collisions.
- **`executeDirect()` on all Commands** — `MigrateCommand`, `RollbackCommand`, `FreshCommand`, `ListComponentsCommand`, `AddComponentCommand` all expose `executeDirect()` for zero-dependency CLI invocation.
- **View Compiler** — Added `@method('PUT')` / `@method('DELETE')` directive compilation to hidden `<input>` fields.
- **`Request::create()`** factory method and `query()` getter.
- **`Response::setHeader()`** / `getHeader()` utilities.
- **`ComponentRegistry`** — Added `footer` (41st) and `rating` (42nd) components.
- **`Engine::renderFile()`** and `renderString()` methods for standalone template compilation.

### Fixed
- `Blueprint::boolean()` default value compiled as `0`/`1` instead of empty `DEFAULT ,` SQL bug.
- `QueryBuilder::get()` and `first()` now auto-hydrate rows into Model instances when `modelClass` is set.
- `Model::find()` and `all()` handle pre-hydrated model instances returned by QueryBuilder correctly.
- `ThrottleRequests` and `CheckForMaintenanceMode` `\Closure $next` type hints fixed.

---

## [0.4.0] — 2026-07-15

### Added
- 41+ UI components in `veldora-ui` package.
- `php veldora ui:list` and `php veldora add <component>` CLI commands.
- Session auth guards, `Auth::attempt()`, `Auth::logout()`, `auth()` helper.
- Middleware pipeline with `web`, `auth`, `guest`, `throttle` groups.
- `php veldora make:controller`, `make:model`, `make:migration`, `make:middleware`, `make:mail` commands.
- Queue system with `dispatch()`, workers, and failed jobs table.
- Mail system with `mailer()->to()->send()` and Mailable classes.
- Cache system with file/array drivers and `cache()->remember()`.
- HTTP client (`Http::get()`, `Http::post()`, `withToken()`).
- Event / Listener system.
- Storage system with `storage('disk')->put/get/delete/url`.

---

## [0.3.0] — 2026-07-13

### Added
- Initial public release of `veldora/framework`.
- MVC architecture, Router, Service Container, View Compiler, Database QueryBuilder, Migrator, Schema Blueprint.
- `php veldora serve`, `php veldora migrate`, `php veldora make:*` commands.
