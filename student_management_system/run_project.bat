@echo off
title EduManage - Student Management System Launcher
echo =====================================================
echo       EduManage - Student Management System
echo =====================================================
echo.
echo [1/3] Checking MySQL database service...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo       MySQL service is already running!
) else (
    echo       Starting MySQL...
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini"
    timeout /t 2 >nul
)

echo [2/3] Checking Apache web server...
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo       Apache service is already running!
) else (
    echo       Starting Apache...
    start "" /B "C:\xampp\apache\bin\httpd.exe"
    timeout /t 2 >nul
)

echo [3/3] Opening EduManage SMS in your web browser...
start http://localhost/student_management_system/

echo.
echo =====================================================
echo  EduManage is running at:
echo  URL: http://localhost/student_management_system/
echo.
echo  Default Logins:
echo   - Admin:   admin    / admin123
echo   - Teacher: teacher1 / teacher123
echo   - Student: student1 / student123
echo =====================================================
echo.
echo You can close this window at any time.
pause >nul
