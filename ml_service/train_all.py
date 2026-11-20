import os
import sys
import random
import numpy as np
import pandas as pd

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from model import PhishingMLModel
from feature_extractor import FeatureExtractor
from train_url_csv import train_url_model
from train_email import train_email_model
from train_sms import train_sms_model

def train_master_model():
    print(f"\n{'='*60}\n🧠 TRAINING MASTER RANDOM FOREST MODEL\n{'='*60}")
    
    # Option 1: Train individually and save. The LAST one trained will be the active model.
    # Uncomment the one you want to train, or run them all.
    
    print("\n👉 Training URL model...")
    try: train_url_model()
    except Exception as e: print(f"URL Training Failed: {e}")

    print("\n👉 Training Email model...")
    try: train_email_model()
    except Exception as e: print(f"Email Training Failed: {e}")

    print("\n👉 Training SMS model...")
    try: train_sms_model()
    except Exception as e: print(f"SMS Training Failed: {e}")
        
    print("\n🎉 Master training complete. The last successfully trained model is saved in phishing_model.pkl.")

if __name__ == "__main__":
    try:
        train_master_model()
    except Exception:
        import traceback
        traceback.print_exc()