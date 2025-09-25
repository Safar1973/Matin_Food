MATIN FOOD — Full Stack (Frontend + Flask + PHP)
1) Flask:
   cd backend
   python -m venv .venv && .venv\Scripts\activate
   pip install -r requirements.txt
   set APP_SECRET=change_me
   set ADMIN_PASSWORD=change_me
   python app.py

2) Frontend:
   افتح frontend/index.html مباشرة أو عبر:
   cd frontend
   python -m http.server 8080

3) PHP (بديل اختياري):
   cd backend-php
   php -S 127.0.0.1:8000 -t public
