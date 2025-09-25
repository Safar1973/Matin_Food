@echo off
set "PY=C:\Users\fsafa\AppData\Local\Programs\Python\Python314\python.exe"
"%PY%" - <<PYCODE
import app
app.app.run(host="127.0.0.1", port=5050, debug=True)
PYCODE
