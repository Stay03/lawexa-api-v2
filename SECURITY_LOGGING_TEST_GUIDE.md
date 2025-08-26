# Security Logging Test Guide

This guide explains how to test the security logging implementation in your Laravel application.

## 🧪 Test Suite Overview

The security logging implementation includes comprehensive tests covering:

- **Unit Tests**: SecurityLoggerService and CleanupExpiredGuests job
- **Feature Tests**: AuthController and GoogleAuthController integration
- **Integration Tests**: End-to-end API testing with actual log file verification

## 🚀 Running the Tests

### Option 1: Run All Tests (Windows)
```bash
./run-security-tests.bat
```

### Option 2: Run Individual Test Categories

#### Unit Tests
```bash
# SecurityLoggerService tests
php vendor/bin/phpunit tests/Unit/SecurityLoggerServiceTest.php --testdox

# CleanupExpiredGuests job tests  
php vendor/bin/phpunit tests/Unit/CleanupExpiredGuestsTest.php --testdox
```

#### Feature Tests
```bash
# AuthController security logging tests
php vendor/bin/phpunit tests/Feature/AuthControllerSecurityLoggingTest.php --testdox

# GoogleAuthController security logging tests
php vendor/bin/phpunit tests/Feature/GoogleAuthControllerSecurityLoggingTest.php --testdox
```

#### Integration Tests
```bash
# Make sure your server is running on http://localhost:8000
php artisan serve

# In another terminal, run integration tests
./test-security-logging.sh
```

### Option 3: Run with Custom Configuration
```bash
# Set custom API URL for integration tests
export API_URL="https://your-api-domain.com/api"
./test-security-logging.sh
```

## 📋 Test Coverage

### SecurityLoggerService Tests
- ✅ Authentication attempt logging (success/failure)
- ✅ Guest session creation/deletion logging
- ✅ User logout logging
- ✅ Email verification logging
- ✅ Profile update logging
- ✅ Suspicious activity logging
- ✅ Role change logging
- ✅ Admin action logging
- ✅ Proper log channel usage
- ✅ Context data validation

### AuthController Tests  
- ✅ Login success/failure logging
- ✅ Guest session creation logging
- ✅ User logout logging
- ✅ Profile update logging
- ✅ Email verification logging
- ✅ Request validation
- ✅ Token generation verification

### CleanupExpiredGuests Tests
- ✅ Individual guest deletion logging
- ✅ Deletion reason classification (expired/inactive/no_activity)
- ✅ Token revocation verification
- ✅ Active guest preservation
- ✅ Regular user preservation
- ✅ Batch deletion logging
- ✅ Edge case handling

### GoogleAuthController Tests
- ✅ OAuth success logging
- ✅ OAuth failure logging  
- ✅ New user creation logging
- ✅ Existing user update logging
- ✅ Error handling logging
- ✅ State management verification

### Integration Tests
- ✅ Failed login attempt logging
- ✅ Guest session creation logging  
- ✅ Email verification attempt logging
- ✅ Security log file structure validation
- ✅ Log channel configuration verification
- ✅ Real API endpoint testing

## 📊 Expected Test Output

### Successful Test Run
```
🔒 Security Logging Test Suite
==============================

[1/4] SecurityLoggerService Unit Tests
✓ Logs successful authentication attempt
✓ Logs failed authentication attempt  
✓ Logs guest session created
✓ Logs guest session deleted
✓ Logs user logout
... (more tests)

✅ All tests passed!
```

### Integration Test Output
```
🔒 Security Logging Integration Test
====================================

ℹ️  Running test: Failed Login Attempt
✅ Failed login attempt - Found in security log
✅ Test passed: Failed Login Attempt

ℹ️  Running test: Guest Session Creation  
✅ Guest session creation - Found in security log
✅ Test passed: Guest Session Creation

Test Summary
Tests run: 7
Tests passed: 7
Tests failed: 0

✅ All tests passed! Security logging is working correctly.
```

## 🐛 Troubleshooting

### Common Issues

#### "Security log file does not exist"
- Make sure the Laravel app has created the log file by triggering a security event
- Check that the `storage/logs` directory is writable
- Verify the security log channel is properly configured

#### "Log entries missing timestamp field"
- Check that the SecurityLoggerService is properly injected
- Verify the log channel configuration in `config/logging.php`
- Ensure the security channel is using the correct formatter

#### "Authentication tests failing"
- Make sure test database is properly seeded
- Check that Sanctum middleware is configured correctly
- Verify mail faking is set up in test environment

#### Integration tests failing
- Ensure Laravel server is running (`php artisan serve`)
- Check API URL configuration
- Verify network connectivity to the test server

### Debug Commands

#### Check Security Log Entries
```bash
# View recent security log entries
tail -f storage/logs/security-$(date +%Y-%m-%d).log

# Search for specific log types
grep "Authentication attempt" storage/logs/security-*.log
grep "Guest session" storage/logs/security-*.log
```

#### Manual Security Event Testing
```bash
# Test failed login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"wrong"}'

# Test guest session creation
curl -X POST http://localhost:8000/api/guest-session

# Check logs immediately after
tail -5 storage/logs/security-$(date +%Y-%m-%d).log
```

## 📝 Writing Additional Tests

### Adding New Security Event Tests

1. **Extend SecurityLoggerService**: Add new logging methods
2. **Add Unit Tests**: Test new methods in `SecurityLoggerServiceTest.php`
3. **Add Feature Tests**: Test controller integration
4. **Update Integration Tests**: Add API endpoint testing

### Test Template
```php
public function test_logs_new_security_event(): void
{
    $this->securityLogger->logNewEvent($param1, $param2);

    Log::shouldHaveReceived('channel')
        ->with('security')
        ->once();
        
    Log::channel('security')->shouldHaveReceived('info')
        ->withArgs(function ($message, $context) {
            return $message === 'Expected message' &&
                   $context['expected_field'] === 'expected_value';
        })
        ->once();
}
```

## 🎯 Best Practices

1. **Always mock external dependencies** in unit tests
2. **Use database transactions** in feature tests for isolation  
3. **Test both success and failure scenarios** for each security event
4. **Verify log structure and content**, not just existence
5. **Include edge cases** and error conditions in tests
6. **Keep integration tests focused** on end-to-end behavior
7. **Use meaningful test data** that reflects real-world scenarios

## 📚 Additional Resources

- Laravel Testing Documentation: https://laravel.com/docs/testing
- PHPUnit Documentation: https://phpunit.de/documentation.html
- Laravel Logging Documentation: https://laravel.com/docs/logging