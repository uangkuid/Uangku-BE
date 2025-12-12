# Testing Guide

This document provides comprehensive information about testing in the Uangku-BE project.

## Table of Contents

1. [Overview](#overview)
2. [Test Structure](#test-structure)
3. [Running Tests](#running-tests)
4. [Writing Tests](#writing-tests)
5. [Test Coverage](#test-coverage)
6. [Continuous Integration](#continuous-integration)
7. [Troubleshooting](#troubleshooting)

## Overview

The Uangku-BE project uses PHPUnit for testing. We have two types of tests:

- **Unit Tests**: Test individual classes and methods in isolation
- **Feature Tests**: Test complete features and API endpoints

All tests are located in the `tests/` directory.

## Test Structure

```
tests/
├── Feature/
│   ├── Api/
│   │   ├── AuthControllerTest.php
│   │   ├── CategoryControllerTest.php
│   │   ├── FamilyControllerTest.php
│   │   ├── GeneralControllerTest.php
│   │   ├── OtpControllerTest.php
│   │   ├── PinControllerTest.php
│   │   ├── SubCategoryControllerTest.php
│   │   ├── TransactionControllerTest.php
│   │   ├── TransactionTypeControllerTest.php
│   │   ├── UserControllerTest.php
│   │   └── WalletControllerTest.php
│   └── ExampleTest.php
├── Unit/
│   ├── Helpers/
│   │   ├── EncryptionHelperTest.php
│   │   └── TokenHelperTest.php
│   └── ExampleTest.php
├── TestCase.php
└── TestSetup.php
```

## Running Tests

### Using the Test Runner Script (Recommended)

The easiest way to run tests is using the provided test runner script:

```bash
# Run all tests
./run-tests.sh

# Run specific test suite
./run-tests.sh --suite Unit
./run-tests.sh --suite Feature

# Run tests matching a pattern
./run-tests.sh --filter EncryptionHelper
./run-tests.sh --filter AuthController

# Run with code coverage
./run-tests.sh --coverage

# Show help
./run-tests.sh --help
```

### Using Artisan

```bash
# Run all tests
php artisan test

# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests
php artisan test --testsuite=Feature

# Run a specific test file
php artisan test tests/Unit/Helpers/EncryptionHelperTest.php

# Run a specific test method
php artisan test --filter test_encrypt_returns_array_with_iv_and_data

# Run tests with code coverage
php artisan test --coverage

# Run tests in parallel (faster)
php artisan test --parallel
```

### Using PHPUnit Directly

```bash
# Run all tests
./vendor/bin/phpunit

# Run specific suite
./vendor/bin/phpunit --testsuite=Unit

# Run with coverage HTML report
./vendor/bin/phpunit --coverage-html coverage

# Run with coverage text output
./vendor/bin/phpunit --coverage-text
```

## Writing Tests

### Unit Test Example

Unit tests should test individual classes or methods in isolation:

```php
<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use App\Helpers\EncryptionHelper;

class EncryptionHelperTest extends TestCase
{
    public function test_encrypt_returns_valid_data(): void
    {
        $data = 'test data';
        $result = EncryptionHelper::encrypt($data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('iv', $result);
        $this->assertArrayHasKey('data', $result);
    }
    
    public function test_encrypt_and_decrypt_cycle(): void
    {
        $original = 'secret message';
        $encrypted = EncryptionHelper::encrypt($original);
        $decrypted = EncryptionHelper::decrypt($encrypted['data'], $encrypted['iv']);
        
        $this->assertEquals($original, $decrypted);
    }
}
```

### Feature Test Example

Feature tests should test complete workflows and API endpoints:

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'token',
                    'user'
                ]
            ]);
    }
    
    public function test_login_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }
}
```

### Test Naming Conventions

- Test methods should start with `test_`
- Use descriptive names that explain what is being tested
- Use snake_case for test method names
- Format: `test_<action>_<expected_result>`

Examples:
- `test_encrypt_returns_array_with_iv_and_data`
- `test_login_with_valid_credentials`
- `test_create_transaction_requires_authentication`

### Common Assertions

#### HTTP Response Assertions

```php
$response->assertStatus(200);
$response->assertSuccessful();
$response->assertOk();
$response->assertCreated();
$response->assertNoContent();
$response->assertNotFound();
$response->assertForbidden();
$response->assertUnauthorized();

$response->assertJson(['key' => 'value']);
$response->assertJsonStructure(['key', 'nested' => ['key']]);
$response->assertJsonPath('data.user.email', 'test@example.com');
```

#### Database Assertions

```php
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'deleted@example.com']);
$this->assertDatabaseCount('users', 5);
```

#### General Assertions

```php
$this->assertTrue($value);
$this->assertFalse($value);
$this->assertEquals($expected, $actual);
$this->assertNotEquals($expected, $actual);
$this->assertSame($expected, $actual);
$this->assertInstanceOf(User::class, $object);
$this->assertNull($value);
$this->assertNotNull($value);
$this->assertEmpty($value);
$this->assertNotEmpty($value);
$this->assertCount(5, $array);
$this->assertArrayHasKey('key', $array);
$this->assertStringContainsString('needle', 'haystack');
```

## Test Coverage

### Viewing Coverage

To generate and view code coverage:

```bash
# Generate HTML coverage report
./vendor/bin/phpunit --coverage-html coverage

# Open the report in your browser
open coverage/index.html
```

### Coverage Requirements

Our test suite aims for:
- **Overall coverage**: 80%+
- **Helper functions**: 100%
- **API endpoints**: 90%+
- **Critical business logic**: 100%

### What We Test

✅ **Helper Functions**
- EncryptionHelper (all encryption/decryption methods)
- TokenHelper (JWT token generation)

✅ **API Endpoints**
- Authentication & Authorization
- User Management
- Category & SubCategory Management
- Transaction Management
- Family & Wallet Management
- OTP & PIN Operations

✅ **Validation**
- Input validation
- Business rule validation
- Authorization checks

✅ **Error Handling**
- Exception handling
- Error responses
- Edge cases

## Continuous Integration

Tests are automatically run on:
- Every push to the repository
- Every pull request
- Before deployment

GitHub Actions configuration is in `.github/workflows/`.

## Troubleshooting

### Common Issues

#### Issue: Tests fail with database errors

**Solution**: Make sure you're using the in-memory SQLite database for tests. Check `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

#### Issue: Encryption tests fail

**Solution**: Set environment variables for encryption:

```bash
export MAIN_SECRET_KEY="test_secret_key_12345"
export MAIN_SALT_KEY="test_salt_key_67890"
```

Or add them to `.env.testing`:

```env
MAIN_SECRET_KEY=test_secret_key_12345
MAIN_SALT_KEY=test_salt_key_67890
```

#### Issue: JWT tests fail

**Solution**: Generate a JWT secret:

```bash
php artisan jwt:secret
```

#### Issue: Tests are slow

**Solution**: Run tests in parallel:

```bash
php artisan test --parallel
```

Or use SQLite in-memory database (already configured in phpunit.xml).

#### Issue: Coverage report not generating

**Solution**: Install and enable Xdebug or PCOV:

```bash
# For Xdebug
pecl install xdebug

# For PCOV (faster)
pecl install pcov
```

### Best Practices

1. **Keep tests independent**: Each test should be able to run in isolation
2. **Use factories**: Create test data using factories instead of hardcoding
3. **Mock external services**: Don't rely on external APIs or services
4. **Test edge cases**: Test both success and failure scenarios
5. **Keep tests fast**: Use in-memory databases and avoid unnecessary operations
6. **Use descriptive names**: Test names should clearly describe what they test
7. **Follow AAA pattern**: Arrange, Act, Assert

### Getting Help

If you encounter issues with tests:

1. Check this documentation
2. Review the test examples in the `tests/` directory
3. Check Laravel testing documentation: https://laravel.com/docs/testing
4. Check PHPUnit documentation: https://phpunit.de/documentation.html

## Examples of Complete Test Files

See the following files for complete examples:

- `tests/Unit/Helpers/EncryptionHelperTest.php` - Comprehensive unit test example
- `tests/Feature/Api/AuthControllerTest.php` - Complete API endpoint test example
- `tests/Feature/Api/GeneralControllerTest.php` - Simple feature test example

## Contributing

When adding new features:

1. Write tests for new code
2. Ensure all tests pass before submitting PR
3. Aim for high code coverage
4. Follow existing test patterns and conventions
