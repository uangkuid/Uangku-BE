<?php

/*
|--------------------------------------------------------------------------
| Test Environment Configuration
|--------------------------------------------------------------------------
|
| This file contains helper configurations and setup instructions for
| running tests in this Laravel application.
|
*/

// Test Database Configuration
// The tests use SQLite in-memory database by default (configured in phpunit.xml)
// This provides fast, isolated test execution without affecting your main database.

// Environment Variables for Testing
// These are automatically set via phpunit.xml, but can be customized:
//
// APP_ENV=testing
// BCRYPT_ROUNDS=4 (faster password hashing for tests)
// CACHE_STORE=array
// MAIL_MAILER=array
// QUEUE_CONNECTION=sync
// SESSION_DRIVER=array

// Required Environment Variables for Encryption Tests
// Make sure these are set in your .env or .env.testing file:
//
// MAIN_SECRET_KEY=your_secret_key_here
// MAIN_SALT_KEY=your_salt_key_here
// JWT_SECRET=your_jwt_secret_here

// Running Tests
// ============
// 
// 1. Install dependencies (if not already done):
//    composer install
//
// 2. Run all tests:
//    php artisan test
//    or
//    ./vendor/bin/phpunit
//
// 3. Run specific test suite:
//    php artisan test --testsuite=Unit
//    php artisan test --testsuite=Feature
//
// 4. Run specific test file:
//    php artisan test tests/Unit/Helpers/EncryptionHelperTest.php
//
// 5. Run with coverage (requires Xdebug or PCOV):
//    php artisan test --coverage
//
// 6. Run tests in parallel (faster, requires paratest):
//    php artisan test --parallel

// Test Best Practices
// ===================
//
// 1. Use RefreshDatabase trait for tests that need database
// 2. Keep tests independent and isolated
// 3. Use factories for creating test data
// 4. Mock external services
// 5. Test both success and failure scenarios
// 6. Use descriptive test method names

// Common Test Assertions
// ======================
//
// HTTP Response Assertions:
// - $response->assertStatus(200)
// - $response->assertJson(['key' => 'value'])
// - $response->assertJsonStructure(['key'])
//
// Database Assertions:
// - $this->assertDatabaseHas('table', ['column' => 'value'])
// - $this->assertDatabaseMissing('table', ['column' => 'value'])
//
// General Assertions:
// - $this->assertTrue($value)
// - $this->assertEquals($expected, $actual)
// - $this->assertInstanceOf(Class::class, $object)
