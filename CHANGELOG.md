# Changelog — veldora/framework

All notable changes to `veldora/framework` are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

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
