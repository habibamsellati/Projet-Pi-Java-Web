@echo off
REM AI Image Generation Test Script
echo ========================================
echo AI Image Generation Test
echo ========================================
echo.

echo Testing AI image generation API...
echo.

REM Test with curl (if available)
where curl >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo Using curl to test API...
    curl -X POST http://localhost:8000/api/ai/generate-image ^
         -H "Content-Type: application/json" ^
         -d "{\"description\":\"Une vieille chaise en bois recyclé\"}"
    echo.
    echo.
) else (
    echo curl not found, using PowerShell...
    powershell -Command "$body = @{description='Une vieille chaise en bois recyclé'} | ConvertTo-Json; Invoke-RestMethod -Uri 'http://localhost:8000/api/ai/generate-image' -Method POST -Body $body -ContentType 'application/json' | ConvertTo-Json -Depth 10"
    echo.
)

echo.
echo ========================================
echo Test Complete
echo ========================================
echo.
echo If successful, check public/uploads/ai_images/ for generated image
echo.
echo To test in browser:
echo 1. Go to http://localhost:8000/produit/new
echo 2. Enter a product description
echo 3. Click "Générer Image IA" button
echo.
pause
