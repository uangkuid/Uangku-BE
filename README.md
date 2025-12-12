<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/oratakashi/Uangku-BE/actions"><img src="https://github.com/oratakashi/Uangku-BE/actions/workflows/docker-image.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Basic Setup

### Generate APP KEY

```
php artisan key:generate
```

### Configure JWT Services

#### Publish Config
```
php artisan vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"
```

#### Generate Key
```
php artisan jwt:secret
```

### Service Library

```
php artisan vendor:publish --provider="LaravelEasyRepository\LaravelEasyRepositoryServiceProvider" --tag="easy-repository-config"
```

### Filament

```
php artisan vendor:publish --tag=filament-config
```

### Laravel Debugbar

```
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
```

## Docker & CI/CD

### Docker Images

The project uses automated Docker builds with the following tags:

- **Production (latest)**: Built from git tags, multi-architecture (AMD64 + ARM64)
  - `oratakashi/uangku-be:latest`
  - `ghcr.io/uangkuid/uangku-be:latest`

- **Development (dev)**: Built from main branch, AMD64 only
  - `oratakashi/uangku-be:dev`
  - `ghcr.io/uangkuid/uangku-be:dev`

### Running with Docker Compose

#### Development Environment
```bash
docker-compose -f docker-compose-dev.yaml up
```

#### Production Environment
```bash
docker-compose up
```

### Release Process

To create a new release:
```bash
git tag v1.0.0
git push origin v1.0.0
```

This will automatically:
- Build multi-architecture Docker images (AMD64 + ARM64)
- Tag the images as `latest` and `v1.0.0`
- Push to both Docker Hub and GitHub Container Registry

For more details, see [CI-CD-CHANGES.md](CI-CD-CHANGES.md) and [WORKFLOW-DECISION-TREE.md](WORKFLOW-DECISION-TREE.md).

## Testing

This project includes comprehensive unit and feature tests for all API endpoints and helper functions.

### Running Tests

#### Quick Start with Test Runner Script

We provide a convenient test runner script that handles environment setup:

```bash
# Run all tests
./run-tests.sh

# Run specific test suite
./run-tests.sh --suite Unit
./run-tests.sh --suite Feature

# Run tests matching a pattern
./run-tests.sh --filter EncryptionHelper

# Run with coverage
./run-tests.sh --coverage

# Show help
./run-tests.sh --help
```

#### Run All Tests
```bash
php artisan test
```

Or using PHPUnit directly:
```bash
./vendor/bin/phpunit
```

#### Run Specific Test Suites

Run only unit tests:
```bash
php artisan test --testsuite=Unit
```

Run only feature tests:
```bash
php artisan test --testsuite=Feature
```

#### Run Specific Test Files

Run a specific test file:
```bash
php artisan test tests/Unit/Helpers/EncryptionHelperTest.php
```

Run a specific test method:
```bash
php artisan test --filter test_encrypt_returns_array_with_iv_and_data
```

#### Run Tests with Coverage

Generate code coverage report (requires Xdebug or PCOV):
```bash
php artisan test --coverage
```

Generate HTML coverage report:
```bash
./vendor/bin/phpunit --coverage-html coverage
```

### Test Organization

Tests are organized into two main directories:

- **`tests/Unit/`** - Unit tests for helper functions and isolated components
  - `tests/Unit/Helpers/` - Tests for encryption, token generation, and other helper functions

- **`tests/Feature/`** - Integration tests for API endpoints
  - `tests/Feature/Api/` - Tests for all API controllers

### Writing New Tests

#### Unit Test Example

```php
<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use App\Helpers\EncryptionHelper;

class EncryptionHelperTest extends TestCase
{
    public function test_encryption_works(): void
    {
        $data = 'test data';
        $result = EncryptionHelper::encrypt($data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('iv', $result);
        $this->assertArrayHasKey('data', $result);
    }
}
```

#### Feature Test Example

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_returns_success(): void
    {
        $response = $this->getJson('/api/endpoint');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data'
            ]);
    }
}
```

### Test Coverage

The test suite covers:

- ✅ All helper functions (EncryptionHelper, TokenHelper)
- ✅ All API endpoints across 11 controllers:
  - AuthController (authentication, registration, password management)
  - GeneralController (system configuration, feature flags)
  - OtpController (OTP generation and verification)
  - PinController (PIN management)
  - UserController (user profile management)
  - CategoryController (category management)
  - SubCategoryController (subcategory management)
  - TransactionTypeController (transaction type management)
  - FamilyController (family group management)
  - WalletController (wallet management)
  - TransactionController (transaction management)

### Testing Best Practices

1. **Use RefreshDatabase trait** for feature tests that interact with the database
2. **Mock external services** to avoid dependencies on external systems
3. **Test edge cases** including validation failures and error conditions
4. **Keep tests isolated** - each test should be independent
5. **Use descriptive test names** that explain what is being tested
6. **Test both success and failure scenarios**

### Environment Configuration

Make sure your `.env.testing` file is configured properly for running tests:

```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_DRIVER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
```

Alternatively, the test configuration is already set in `phpunit.xml`.
