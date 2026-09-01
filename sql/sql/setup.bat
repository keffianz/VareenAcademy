@echo off
echo VAREEN Academy Database Setup
echo =============================
echo.

echo This script will set up the VAREEN Academy database.
echo Make sure you have MySQL installed and running.
echo.

set /p mysql_user="Enter MySQL root username (default: root): "
if "%mysql_user%"=="" set mysql_user=root

set /p mysql_pass="Enter MySQL root password: "
if "%mysql_pass%"=="" (
    echo Error: MySQL password is required.
    pause
    exit /b 1
)

echo.
echo Setting up database...
echo.

mysql -u %mysql_user% -p%mysql_pass% < setup.sql

if %errorlevel% equ 0 (
    echo.
    echo Database setup completed successfully!
    echo.
    echo Database: vereen_academy
    echo Username: vereenacademy
    echo Password: Abubakar11@
    echo.
    echo You can now access your VAREEN Academy database.
) else (
    echo.
    echo Error: Database setup failed.
    echo Please check your MySQL credentials and try again.
)

echo.
pause

