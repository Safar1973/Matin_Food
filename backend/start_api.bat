@echo off
set PY="C:\Users\fsafa\AppData\Local\Programs\Python\Python314\python.exe"
set PORTS=5000 5050 7000

for %%P in (%PORTS%) do (
  start "" /B %PY% -c "import app; app.app.run(host='127.0.0.1', port=%%P, debug=True)"
  timeout /t 2 >nul
  curl --noproxy "*" http://127.0.0.1:%%P/api/products >nul 2>nul
  if not errorlevel 1 (
    echo Running on http://127.0.0.1:%%P
    set PORT=%%P
    goto done
  )
  taskkill /F /IM python.exe >nul 2>nul
)
echo Failed to start on 5000/5050/7000
exit /b 1

:done
start "" http://127.0.0.1:%PORT%/api/products
