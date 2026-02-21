@echo off
echo ========================================
echo Template Verification Script
echo ========================================
echo.

echo Checking base templates...
echo.

powershell -Command "Get-ChildItem 'templates\base*.twig' | Select-Object Name, Length, LastWriteTime | Format-Table -AutoSize"

echo.
echo Checking produit templates...
echo.

powershell -Command "Get-ChildItem 'templates\produit\*.twig' | Select-Object Name, Length, LastWriteTime | Format-Table -AutoSize"

echo.
echo Checking proposition templates...
echo.

powershell -Command "Get-ChildItem 'templates\proposition\*.twig' | Select-Object Name, Length, LastWriteTime | Format-Table -AutoSize"

echo.
echo Checking partials...
echo.

powershell -Command "Get-ChildItem 'templates\partials\*.twig' | Select-Object Name, Length, LastWriteTime | Format-Table -AutoSize"

echo.
echo ========================================
echo Verification Complete
echo ========================================
echo.
echo Expected file sizes:
echo   base.html.front.twig: 14,900 bytes
echo   base.back.html.twig: 4,806 bytes
echo   produit/new.html.twig: 28,241 bytes
echo   proposition/new.html.twig: 27,101 bytes
echo.
echo If sizes match, templates are correct!
echo.
echo Now:
echo 1. Clear your browser cache (Ctrl+Shift+Delete)
echo 2. Hard refresh (Ctrl+F5)
echo 3. Visit http://127.0.0.1:8000/produit
echo.
pause
