import os
import sys
import html
import numpy as np
import pandas as pd
import traceback

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from model import PhishingMLModel
from feature_extractor import FeatureExtractor

def train_url_model():
    filename = os.path.join(BASE_DIR, "url_dataset.csv")
    print(f"\n{'='*60}\n📂 TRAINING URL MODEL\n{'='*60}")
    
    if not os.path.exists(filename):
        raise FileNotFoundError(f"File not found: {filename}")

    try:
        df = pd.read_csv(filename, sep=None, engine='python', encoding='utf-8-sig', on_bad_lines='skip')
    except TypeError:
        df = pd.read_csv(filename, sep=None, engine='python', encoding='utf-8-sig', error_bad_lines=False)
    except Exception:
        df = pd.read_csv(filename, sep='\t', encoding='utf-8-sig', on_bad_lines='skip')

    df.columns = [str(c).strip().lower() for c in df.columns]
    
    if 'url' not in df.columns or 'label' not in df.columns:
        raise ValueError(f"Missing 'url' or 'label'. Found: {df.columns.tolist()}")

    df = df.dropna(subset=['url', 'label'])
    df['url'] = df['url'].astype(str).str.strip()
    df['label'] = df['label'].astype(str).str.strip().str.lower()

    extractor = FeatureExtractor()
    data, labels = [], []

    for i, row in df.iterrows():
        url = html.unescape(row['url'])
        label_str = row['label']

        if 'phishing' in label_str or label_str == '1' or label_str == 'malicious':
            label = 1
        elif 'legitimate' in label_str or label_str == '0' or label_str == 'benign':
            label = 0
        else:
            continue

        try:
            feats = extractor.extract_features(url, is_email=False)
            vector = [feats.get(f, 0) for f in extractor.get_feature_names()]
            data.append(vector)
            labels.append(label)
        except Exception:
            continue

    if not data:
        raise ValueError("No valid samples extracted.")

    X = np.array(data)
    y = np.array(labels)
    print(f"📊 Generated {len(X)} samples (Phishing: {int(y.sum())}, Legitimate: {len(y) - int(y.sum())})")

    print("🧠 Training Random Forest...")
    model = PhishingMLModel()
    model.feature_names = extractor.get_feature_names()
    metrics = model.train(X, y)
    print(f"📈 Accuracy: {metrics['accuracy']:.4f}")

    out_path = os.path.join(BASE_DIR, "phishing_model.pkl")
    model.save_model(out_path)
    print(f"✅ Model saved to: {out_path}")

if __name__ == "__main__":
    try:
        train_url_model()
    except Exception:
        traceback.print_exc()
        sys.exit(1)