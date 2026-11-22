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
    print(f"\n{'='*60}\n🧠 TRAINING MASTER MULTI-MODAL RANDOM FOREST MODEL\n{'='*60}")
    
    extractor = FeatureExtractor()
    data, labels = [], []

    # 1. Collect URL Samples
    url_file = os.path.join(BASE_DIR, "url_dataset.csv")
    if os.path.exists(url_file):
        print("👉 Extracting URL dataset samples...")
        try:
            df_url = pd.read_csv(url_file, on_bad_lines='skip')
            df_url.columns = [str(c).strip().lower() for c in df_url.columns]
            if 'url' in df_url.columns and 'label' in df_url.columns:
                df_url = df_url.dropna(subset=['url', 'label'])
                df_url['label'] = df_url['label'].astype(str).str.strip().str.lower()
                u_legit = df_url[df_url['label'].isin(['0', 'legitimate', 'benign'])]
                u_phish = df_url[df_url['label'].isin(['1', 'phishing', 'malicious'])]
                
                # Sample balanced subsets
                target_count = min(len(u_legit), 2000)
                u_legit_sample = u_legit.sample(n=target_count, random_state=42) if len(u_legit) > target_count else u_legit
                u_phish_sample = u_phish.sample(n=target_count * 2, random_state=42) if len(u_phish) > target_count * 2 else u_phish

                for _, r in pd.concat([u_legit_sample, u_phish_sample]).iterrows():
                    u = str(r['url']).strip()
                    if not u.startswith(('http://', 'https://')):
                        u = 'https://' + u
                    lbl = 1 if r['label'] in ('1', 'phishing', 'malicious') else 0
                    feats = extractor.extract_features(u, is_email=False)
                    data.append([feats.get(f, 0) for f in extractor.get_feature_names()])
                    labels.append(lbl)
                print(f"   Collected {len(u_legit_sample)} legitimate and {len(u_phish_sample)} phishing URL samples.")
        except Exception as e:
            print(f"   ⚠️ URL extraction error: {e}")

    # 2. Collect Email Samples
    email_file = os.path.join(BASE_DIR, "email_dataset.csv")
    if os.path.exists(email_file):
        print("👉 Extracting Email dataset samples...")
        try:
            df_email = pd.read_csv(email_file, on_bad_lines='skip')
            df_email.columns = [str(c).strip().lower() for c in df_email.columns]
            label_col = 'final_label' if 'final_label' in df_email.columns else 'label'
            msg_col = 'cleaned_message' if 'cleaned_message' in df_email.columns else 'original_message'
            
            if msg_col in df_email.columns and label_col in df_email.columns:
                df_email = df_email.dropna(subset=[msg_col, label_col])
                df_email[label_col] = df_email[label_col].astype(str).str.strip().str.lower()
                e_phish = df_email[df_email[label_col].isin(['1', 'phishing', 'smishing', 'spam'])]
                e_legit = df_email[df_email[label_col].isin(['0', 'legitimate', 'ham', 'benign'])]
                
                target_count = min(len(e_phish), 1000)
                e_phish_sample = e_phish.sample(n=target_count, random_state=42) if len(e_phish) > target_count else e_phish
                e_legit_sample = e_legit.sample(n=target_count * 2, random_state=42) if len(e_legit) > target_count * 2 else e_legit

                for _, r in pd.concat([e_legit_sample, e_phish_sample]).iterrows():
                    text = str(r[msg_col])
                    lbl = 1 if r[label_col] in ('1', 'phishing', 'smishing', 'spam') else 0
                    feats = extractor.extract_features(text, is_email=True)
                    data.append([feats.get(f, 0) for f in extractor.get_feature_names()])
                    labels.append(lbl)
                print(f"   Collected {len(e_legit_sample)} legitimate and {len(e_phish_sample)} phishing Email samples.")
        except Exception as e:
            print(f"   ⚠️ Email extraction error: {e}")

    # 3. Collect SMS Samples
    sms_file = os.path.join(BASE_DIR, "sms_dataset.csv")
    if os.path.exists(sms_file):
        print("👉 Extracting SMS dataset samples...")
        try:
            df_sms = pd.read_csv(sms_file, on_bad_lines='skip')
            df_sms.columns = [str(c).strip().lower() for c in df_sms.columns]
            label_col = 'final_label' if 'final_label' in df_sms.columns else 'label'
            msg_col = 'cleaned_message' if 'cleaned_message' in df_sms.columns else 'original_message'

            if msg_col in df_sms.columns and label_col in df_sms.columns:
                df_sms = df_sms.dropna(subset=[msg_col, label_col])
                df_sms[label_col] = df_sms[label_col].astype(str).str.strip().str.lower()
                s_phish = df_sms[df_sms[label_col].isin(['1', 'phishing', 'smishing', 'spam'])]
                s_legit = df_sms[df_sms[label_col].isin(['0', 'legitimate', 'ham', 'benign'])]

                target_count = min(len(s_phish), 1000)
                s_phish_sample = s_phish.sample(n=target_count, random_state=42) if len(s_phish) > target_count else s_phish
                s_legit_sample = s_legit.sample(n=target_count * 2, random_state=42) if len(s_legit) > target_count * 2 else s_legit

                for _, r in pd.concat([s_legit_sample, s_phish_sample]).iterrows():
                    text = str(r[msg_col])
                    lbl = 1 if r[label_col] in ('1', 'phishing', 'smishing', 'spam') else 0
                    feats = extractor.extract_features(text, is_sms=True)
                    data.append([feats.get(f, 0) for f in extractor.get_feature_names()])
                    labels.append(lbl)
                print(f"   Collected {len(s_legit_sample)} legitimate and {len(s_phish_sample)} phishing SMS samples.")
        except Exception as e:
            print(f"   ⚠️ SMS extraction error: {e}")

    if not data:
        raise ValueError("No valid training samples could be extracted across datasets.")

    X = np.array(data)
    y = np.array(labels)
    total_phish = int(y.sum())
    total_legit = len(y) - total_phish
    print(f"\n📊 Total Master Dataset: {len(X)} samples (Phishing: {total_phish}, Legitimate: {total_legit})")

    print("🧠 Training Random Forest model...")
    model = PhishingMLModel()
    model.feature_names = extractor.get_feature_names()
    metrics = model.train(X, y)
    print(f"📈 Master Model Accuracy: {metrics['accuracy']:.4f}")

    out_path = os.path.join(BASE_DIR, "phishing_model.pkl")
    model.save_model(out_path)
    print(f"✅ Master Multi-Modal model saved to: {out_path}")

if __name__ == "__main__":
    try:
        train_master_model()
    except Exception:
        import traceback
        traceback.print_exc()