# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Uangku-BE is a Laravel 13 REST API backend for a personal finance management app. It manages users, family groups, wallets, and transactions with end-to-end encryption support. The server runs via Laravel Octane with FrankenPHP.

## Commands

```bash
# Run all tests
php artisan test
./run-tests.sh

# Run a specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# Run a specific test file or method
php artisan test tests/Unit/Helpers/EncryptionHelperTest.php
php artisan test --filter test_encrypt_returns_array_with_iv_and_data

# Code style (Laravel Pint)
./vendor/bin/pint

# Start dev server locally (Octane)
php artisan octane:start

# Database migrations & seeding
php artisan migrate
php artisan db:seed

# Regenerate Filament Shield permissions for the admin panel (also run by DatabaseSeeder)
php artisan shield:generate --all --panel=staffsus --option=permissions

# Start via Docker (development)
docker-compose -f docker-compose-dev.yaml up

# Start via Docker (production)
docker-compose up
```

## Architecture

### Layer Pattern
Controllers → Services → Repositories → Models. Uses `yaza/laravel-repository-service` (formerly `laravel-easy-repository`). Each domain (Auth, User, Wallet, etc.) has an interface + `Implement` class pair for both Service and Repository. Not every repository has a matching service — some (e.g. `Redis`, `S3`, `UserKey`, `FamilyKey`, `WalletAccess`, `WalletSnapshot`) are consumed directly by other domains' services.

```
app/
├── Http/Controllers/Api/   # Thin controllers — validate input, call service, return BaseResponse
├── Services/               # Business logic (interface + Implement per domain)
├── Repositories/           # DB queries (interface + Implement per domain)
├── Models/                 # Eloquent models, all extend BaseModel
├── Http/Resources/         # BaseResponse wraps all API responses
├── Http/Middleware/        # Route guards: family, wallet, wallet-admin, family-admin, session, transaction
├── Helpers/                # EncryptionHelper, TokenHelper (static utility classes)
├── Enums/                  # RoleWallet, RoleFamily, FamilyMemberStatus, UserStatus, WalletStatus, WalletType, OtpType, RedisKey
├── Exceptions/              # AuthException, EncryptionException, FamilyException, GeneralException, PinException, SecurityException, SessionException, UserException
├── Filament/               # Admin panel resources (Filament v5, panel id "staffsus")
└── Policies/, Observers/   # Model-level authorization and lifecycle hooks
```

### API Response Shape
All responses go through `BaseResponse` which returns:
```json
{ "status": 200, "message": "...", "data": {...} }
```
When `IS_NEED_ENCRYPT=true` in `.env`, the `data` field is AES-256-CBC encrypted and returned as `{ "iv": "...", "data": "..." }`.

### Authentication
JWT via `php-open-source-saver/jwt-auth`. Authenticated routes use `auth:api` middleware. The token is issued on login and refreshed via `POST /api/auth/refresh-token`. Auth-adjacent flows (pre-register OTP, forgot/reset password, PIN setup) live under `routes/api.php`'s `auth`/`otp`/`pin` prefixes.

### Encryption System
`EncryptionHelper` is central to security. Key patterns:
- AES-256-CBC (`encrypt`/`decrypt`) for API payload encryption
- RSA key pairs per user (`generateAsymmetricKey`) stored in `user_keys` table
- Per-user secret key (`UANGKU-XXXXXX-...` format) used to derive user-specific encryption keys
- Family-level encryption key derived from the family's secret key

### Models
All models extend `BaseModel` which serializes dates to ISO 8601 in Asia/Jakarta timezone. All primary keys are UUIDs (`HasUuids` trait). Many models use `SoftDeletes`. Foreign keys store IDs as column names matching the related table (e.g., `wallets`, `families`, `users` column names).

### Infrastructure (Docker Compose)
- **MariaDB** — primary database
- **Redis** — caching and session (via Predis)
- **MinIO** — S3-compatible object storage for avatars (via `league/flysystem-aws-s3-v3`)

### Admin Panel
Filament v5 admin panel, panel id `staffsus`, served at `/admin` (`app/Providers/Filament/StaffsusPanelProvider.php`). Resources: Users, Categories, Wallets, Transactions, Families, FeatureStatuses, SystemConfigs, StaffAccounts, AuditLogs.

- Admin auth model is `StaffAccount` (guard `web`), authorized via `spatie/laravel-permission` + `bezhansalleh/filament-shield` roles/permissions — **not** a legacy `role` column (that column was decommissioned; `HasRoles` on `StaffAccount` is the source of truth now).
- `database/seeders/DatabaseSeeder.php` runs `shield:generate` before seeding so permission rows always exist on a fresh DB, then seeds roles via `ShieldRoleSeeder` and staff accounts via `StaffAccountSeeder`.
- Panel UI is localized (English/Indonesian) via `lang/{en,id}/staffsus/*.php` translation files (one file per resource domain, e.g. `wallets.php`, `transactions.php`, `enums.php`, `navigation.php`).

## Testing Notes

Tests use a real database (not SQLite in-memory — the `<!-- <env name="DB_DATABASE" value=":memory:"/> -->` lines in `phpunit.xml` are commented out). Configure `.env.testing` with a real test DB connection. Feature tests use `RefreshDatabase`. The `run-tests.sh` script handles environment setup automatically.

## Key Environment Variables

| Variable | Purpose |
|---|---|
| `IS_NEED_ENCRYPT` | Enable AES response encryption |
| `MAIN_SECRET_KEY` | AES encryption key |
| `MAIN_SALT_KEY` | Salt for key derivation |
| `JWT_SECRET` | JWT signing secret |
