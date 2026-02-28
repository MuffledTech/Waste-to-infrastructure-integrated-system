@echo off
echo Downloading Assets for Offline Use...
echo.

powershell -Command "Invoke-WebRequest -Uri 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' -OutFile 'assets/css/bootstrap.min.css'"
powershell -Command "Invoke-WebRequest -Uri 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js' -OutFile 'assets/js/bootstrap.bundle.min.js'"
powershell -Command "Invoke-WebRequest -Uri 'https://cdn.jsdelivr.net/npm/chart.js' -OutFile 'assets/js/chart.js'"

echo.
if exist "assets/css/bootstrap.min.css" (
    echo [OK] Bootstrap CSS downloaded.
) else (
    echo [ERROR] Failed to download Bootstrap CSS.
)

if exist "assets/js/bootstrap.bundle.min.js" (
    echo [OK] Bootstrap JS downloaded.
) else (
    echo [ERROR] Failed to download Bootstrap JS.
)

if exist "assets/js/chart.js" (
    echo [OK] Chart.js downloaded.
) else (
    echo [ERROR] Failed to download Chart.js.
)

echo.
echo Setup Complete. You can now run the system offline.
pause
