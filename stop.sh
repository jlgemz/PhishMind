#!/bin/bash

echo "🛑 Stopping PhishGuard..."

# Kill ML Service
pkill -f "python app.py"
echo "✅ ML Service stopped"

# Kill Laravel
pkill -f "php artisan serve"
echo "✅ Laravel stopped"

echo "All services stopped!"
