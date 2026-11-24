import pandas as pd
import numpy as np
import os
import sys
import traceback
import csv

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from model import PhishingMLModel
from feature_extractor import FeatureExtractor

def train_sms_model():
    filename = os.path.join(BASE_DIR, "sms_dataset.csv")
    print(f"\n{'='*60}\n📂 TRAINING SMS MODEL\n{'='*60}")
    
    if not os.path.exists(filename):
        raise FileNotFoundError(f"File not found: {filename}")

    data, labels = [], []
    extractor = FeatureExtractor()

    with open(filename, 'r', encoding='utf-8-sig', newline='') as f:
        sample = f.read(2048)
        f.seek(0)
        dialect = csv.Sniffer().sniff(sample)
        delimiter = dialect.delimiter
        print(f"Detected delimiter: {repr(delimiter)}")

        reader = csv.reader(f, delimiter=delimiter)
        headers = next(reader)
        headers = [str(c).strip().lower() for c in headers]
        print(f"Columns found: {headers}")

        text_idx = None
        label_idx = None

        for i, col in enumerate(headers):
            if col in ['cleaned_message', 'original_message', 'text', 'message', 'sms', 'body']:
                text_idx = i
            if col in ['final_label', 'verified_label', 'original_label', 'label', 'class', 'target']:
                label_idx = i

        if text_idx is None or label_idx is None:
            raise ValueError(f"Could not find text/label columns. Found: {headers}")

        phish_count, legit_count = 0, 0
        max_samples_per_class = 4000

        for row_num, row in enumerate(reader):
            if not row or len(row) <= max(text_idx, label_idx):
                continue

            text = str(row[text_idx]).strip()
            label_str = str(row[label_idx]).strip().lower()

            if not text or text.lower() == 'original_message':
                continue

            if label_str in ('1', 'phishing', 'spam', 'smishing', 'malicious'):
                if phish_count >= max_samples_per_class:
                    continue
                label = 1
            elif label_str in ('0', 'legitimate', 'ham', 'benign'):
                if legit_count >= max_samples_per_class:
                    continue
                label = 0
            else:
                continue

            try:
                feats = extractor.extract_features(text, is_sms=True)
                vector = [feats.get(f, 0) for f in extractor.get_feature_names()]
                data.append(vector)
                labels.append(label)
                if label == 1:
                    phish_count += 1
                else:
                    legit_count += 1
            except Exception as e:
                continue

            if phish_count >= max_samples_per_class and legit_count >= max_samples_per_class:
                break

    if not data:
        raise ValueError("No valid samples extracted.")

    X = np.array(data)
    y = np.array(labels)
    print(f"📊 Generated {len(X)} samples (Phishing: {int(y.sum())}, Legitimate: {len(y) - int(y.sum())})")

    if len(set(y.tolist())) < 2:
        raise ValueError("Need both classes to train.")

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
        train_sms_model()
    except Exception:
        traceback.print_exc()
        sys.exit(1)