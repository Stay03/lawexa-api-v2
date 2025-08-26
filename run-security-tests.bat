@echo off
REM Security Logging Test Runner for Windows

echo 🔒 Security Logging Test Suite
echo ==============================
echo.

REM Run PHPUnit tests
echo Running PHPUnit tests...
echo.

REM Unit tests
echo [1/4] SecurityLoggerService Unit Tests
php vendor\bin\phpunit tests\Unit\SecurityLoggerServiceTest.php --testdox
if %ERRORLEVEL% neq 0 (
    echo ❌ SecurityLoggerService unit tests failed
    exit /b 1
)
echo.

echo [2/4] CleanupExpiredGuests Unit Tests  
php vendor\bin\phpunit tests\Unit\CleanupExpiredGuestsTest.php --testdox
if %ERRORLEVEL% neq 0 (
    echo ❌ CleanupExpiredGuests unit tests failed
    exit /b 1
)
echo.

REM Feature tests
echo [3/4] AuthController Security Logging Feature Tests
php vendor\bin\phpunit tests\Feature\AuthControllerSecurityLoggingTest.php --testdox
if %ERRORLEVEL% neq 0 (
    echo ❌ AuthController feature tests failed
    exit /b 1
)
echo.

echo [4/4] GoogleAuthController Security Logging Feature Tests
php vendor\bin\phpunit tests\Feature\GoogleAuthControllerSecurityLoggingTest.php --testdox
if %ERRORLEVEL% neq 0 (
    echo ❌ GoogleAuthController feature tests failed
    exit /b 1
)
echo.

echo ✅ All PHPUnit tests passed!
echo.

REM Integration tests (if server is running)
echo Running integration tests...
echo Note: Make sure your Laravel server is running on http://localhost:8000
echo.

REM Check if bash is available for integration tests
where bash >nul 2>nul
if %ERRORLEVEL% equ 0 (
    echo Running bash integration tests...
    bash test-security-logging.sh
) else (
    echo ⚠️  Bash not found. Skipping integration tests.
    echo    Install Git Bash or WSL to run integration tests.
)

echo.
echo 🎉 Security logging test suite completed!
pause