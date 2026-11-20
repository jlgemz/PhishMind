Step 1: php artisan optimize:clear

Step 2: npm run build

Step 3: cd ml_service
        source venv/bin/activate

        python3 train_email.py
        python3 train_url_csv.py
        python3 train_sms.py
        python3 train_spear.py
        python3 llm_explainer.py

        python3 model.py
        python3 app.py

        Note: Terminal 1 Python ML Services (http://0.0.0.0:8001/)


Step 4: php artisan serve --port=8002
        
        php artisan migrate:fresh --seed (Reset)

        Note: Terminal 2 Laravel App (http://127.0.0.1:8000)