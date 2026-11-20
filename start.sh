#!/bin/bash

echo "🚀 Starting PhishGuard..."

# Kill any existing processes
pkill -f "python app.py" 2>/dev/null
pkill -f "php artisan serve" 2>/dev/null

# Start ML Service in background
echo "Starting ML Service on port 8001..."
cd /Users/fdc.kevina-nc-web/phishguard/ml_service
source venv/bin/activate
nohup python app.py > /tmp/ml_service.log 2>&1 &
ML_PID=$!
echo "ML Service PID: $ML_PID"

# Wait for ML to start
echo "Waiting for ML Service to start..."
sleep 5

# Check if ML Service is running
if curl -s http://localhost:8001/health > /dev/null; then
    echo "✅ ML Service is running"
else
    echo "❌ ML Service failed to start. Check /tmp/ml_service.log"
    exit 1
fi

# Start Laravel in background
echo "Starting Laravel on port 8000..."
cd /Users/fdc.kevina-nc-web/phishguard
nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/laravel.log 2>&1 &
LARAVEL_PID=$!
echo "Laravel PID: $LARAVEL_PID"

echo ""
echo "✅ PhishGuard is running!"
echo "📊 Web Interface: http://localhost:8000"
echo "🤖 ML Service: http://localhost:8001"
echo ""
echo "Log files:"
echo "  ML Service: /tmp/ml_service.log"
echo "  Laravel: /tmp/laravel.log"
echo ""
echo "To stop all services: ./stop.sh"
echo "To view ML logs: tail -f /tmp/ml_service.log"
echo "To view Laravel logs: tail -f /tmp/laravel.log"
