import csv
import os
import sys
import traceback
import numpy as np

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from model import PhishingMLModel
from feature_extractor import FeatureExtractor

def train_spear_model():
    filename = os.path.join(BASE_DIR, "spear_dataset.csv")
    print(f"\n{'='*60}\n📂 TRAINING SPEAR PHISHING MODEL\n{'='*60}")
    
    if not os.path.exists(filename):
        raise FileNotFoundError(f"File not found: {filename}")

    extractor = FeatureExtractor()
    data, labels = [], []

    print("Parsing TSV manually to handle multi-line email bodies...")
    
    # Open with newline='' to let the csv module handle embedded newlines
    with open(filename, 'r', encoding='utf-8-sig', newline='') as f:
        # Read first few bytes to detect delimiter just in case
        sample = f.read(2048)
        f.seek(0)
        delimiter = '\t' if '\t' in sample else ','
        
        reader = csv.reader(f, delimiter=delimiter)
        
        for row_num, row in enumerate(reader):
            # Skip empty rows or rows that are too short to contain a body and label
            if not row or len(row) < 10:
                continue
                
            # Skip header row if it contains the word "body"
            if 'body' in row[7].lower() or 'label' in row[-1].lower():
                continue

            try:
                # According to your data structure:
                # Index 7 is the email body
                # The very last column is the label (0 or 1)
                text = str(row[7]).strip()
                label_str = str(row[-1]).strip()

                if label_str == '1':
                    label = 1
                elif label_str == '0':
                    label = 0
                else:
                    continue
                
                if not text or text == 'nan' or text == '':
                    continue

                feats = extractor.extract_features(text, is_email=True)
                vector = [feats.get(f, 0) for f in extractor.get_feature_names()]
                data.append(vector)
                labels.append(label)
            except Exception as e:
                print(f"  skipped row {row_num}: {e}")
                continue

    if not data:
        raise ValueError("No valid samples extracted. Check the dataset format.")

    X = np.array(data)
    y = np.array(labels)
    print(f"📊 Generated {len(X)} samples (Phishing: {int(y.sum())}, Legitimate: {len(y) - int(y.sum())})")

    if len(set(y.tolist())) < 2:
        raise ValueError("Need both classes (0 and 1) to train. Check your dataset labels.")

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
        train_spear_model()
    except Exception:
        traceback.print_exc()
        sys.exit(1)