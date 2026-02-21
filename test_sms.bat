@echo off
REM Twilio SMS Test Script
REM Usage: test_sms.bat [phone_number] [message]

echo ========================================
echo Twilio SMS Test
echo ========================================
echo.

if "%1"=="" (
    echo ERROR: Phone number is required!
    echo.
    echo Usage: test_sms.bat PHONE_NUMBER [MESSAGE]
    echo.
    echo Examples:
    echo   test_sms.bat 98765432
    echo   test_sms.bat +21698765432
    echo   test_sms.bat 98765432 "Custom message"
    echo.
    pause
    exit /b 1
)

if "%2"=="" (
    echo Testing with default message...
    php bin/console app:test-twilio-sms %1
) else (
    echo Testing with custom message...
    php bin/console app:test-twilio-sms %1 %2
)

echo.
echo ========================================
echo Test Complete
echo ========================================
echo.
echo If SMS was sent successfully, check your phone!
echo.
echo For trial accounts, verify your number at:
echo https://console.twilio.com/us1/develop/phone-numbers/manage/verified
echo.
pause
