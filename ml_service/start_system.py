import subprocess
import time
import webbrowser
import sys
import os

def start_services():
    print("🚀 Starting System Services...")
    
    # 1. Start Ollama
    print("⏳ Starting Ollama on port 11434...")
    ollama_process = subprocess.Popen(
        ["ollama", "serve"], 
        stdout=subprocess.DEVNULL, 
        stderr=subprocess.DEVNULL
    )
    
    # 2. Start ML Service (app.py)
    print("⏳ Starting ML Service on port 8001...")
    ml_process = subprocess.Popen(
        [sys.executable, "app.py"],
        cwd=os.path.dirname(os.path.abspath(__file__))
    )
    
    # Give them 3 seconds to boot
    time.sleep(3)
    
    print("✅ Services Started!")
    print("   - Ollama: http://127.0.0.1:11434")
    print("   - ML Service: http://127.0.0.1:8001")
    print("   - App: http://127.0.0.1:8000")
    
    # Open the Laravel app in the default browser
    webbrowser.open("http://127.0.0.1:8000")
    
    print("\n⚠️ Keep this window open. Press Ctrl+C to stop both services.")
    
    try:
        ml_process.wait()
    except KeyboardInterrupt:
        print("\n🛑 Shutting down services...")
        ollama_process.terminate()
        ml_process.terminate()
        print("✅ Done.")

if __name__ == "__main__":
    start_services()