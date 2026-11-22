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

def train_master_model():
    print(f"\n{'='*60}\n🧠 TRAINING MASTER MULTI-MODAL RANDOM FOREST MODEL\n{'='*60}")
    
    extractor = FeatureExtractor()
    data, labels = [], []

    # =========================================================================
    # 1. Collect URL Samples
    # =========================================================================
    print("👉 Collecting URL training samples...")
    url_samples = []  # list of (url_string, label)

    # 1a. url_dataset.csv
    url_file = os.path.join(BASE_DIR, "url_dataset.csv")
    if os.path.exists(url_file):
        try:
            df_url = pd.read_csv(url_file, on_bad_lines='skip')
            df_url.columns = [str(c).strip().lower() for c in df_url.columns]
            if 'url' in df_url.columns and 'label' in df_url.columns:
                df_url = df_url.dropna(subset=['url', 'label'])
                df_url['label'] = df_url['label'].astype(str).str.strip().str.lower()
                u_legit = df_url[df_url['label'].isin(['0', 'legitimate', 'benign'])]
                u_phish = df_url[df_url['label'].isin(['1', 'phishing', 'malicious'])]

                target_count = min(len(u_legit), 2000)
                u_legit_sample = u_legit.sample(n=target_count, random_state=42) if len(u_legit) > target_count else u_legit
                u_phish_sample = u_phish.sample(n=target_count, random_state=42) if len(u_phish) > target_count else u_phish

                for _, r in u_legit_sample.iterrows():
                    url_samples.append((str(r['url']).strip(), 0))
                for _, r in u_phish_sample.iterrows():
                    url_samples.append((str(r['url']).strip(), 1))
                print(f"   + url_dataset.csv: {len(u_legit_sample)} legit, {len(u_phish_sample)} phish")
        except Exception as e:
            print(f"   ⚠️ url_dataset.csv error: {e}")

    # 1b. PhishMind_URL_dict.csv
    dict_url_file = os.path.join(BASE_DIR, "PhishMind_URL_dict.csv")
    if os.path.exists(dict_url_file):
        try:
            df_dict_url = pd.read_csv(dict_url_file, on_bad_lines='skip')
            df_dict_url.columns = [str(c).strip().lower() for c in df_dict_url.columns]
            text_col = 'input_text' if 'input_text' in df_dict_url.columns else 'url'
            if text_col in df_dict_url.columns and 'label' in df_dict_url.columns:
                df_dict_url = df_dict_url.dropna(subset=[text_col, 'label'])
                for _, r in df_dict_url.iterrows():
                    lbl = 1 if str(r['label']).strip() in ('1', 'phishing') else 0
                    url_samples.append((str(r[text_col]).strip(), lbl))
                print(f"   + PhishMind_URL_dict.csv: {len(df_dict_url)} samples added")
        except Exception as e:
            print(f"   ⚠️ PhishMind_URL_dict.csv error: {e}")

    # 1c. Popular legitimate websites, social media, and shortened URLs
    popular_legit_urls = [
        "https://www.youtube.com", "https://youtube.com", "www.youtube.com", "youtube.com",
        "https://www.youtube.com/watch?v=dQw4w9WgXcQ", "https://www.youtube.com/feed/subscriptions",
        "https://www.youtube.com/results?search_query=cybersecurity", "https://youtu.be/dQw4w9WgXcQ",
        "https://youtu.be/kJQP7kiw5Fk", "https://youtu.be/9bZkp7q19f0", "youtu.be/dQw4w9WgXcQ",
        "https://www.google.com", "https://google.com", "www.google.com", "google.com",
        "https://www.google.com/search?q=phishing+detection", "https://mail.google.com/mail/u/0/#inbox",
        "https://www.facebook.com", "https://facebook.com", "www.facebook.com", "facebook.com",
        "https://www.facebook.com/login", "https://www.facebook.com/groups/feed", "https://fb.me/help",
        "https://fb.me/security", "fb.me/about",
        "https://twitter.com", "https://twitter.com/home", "https://x.com", "https://x.com/explore",
        "https://t.co/abc12345", "https://t.co/XyZ890", "t.co/news123",
        "https://instagram.com", "https://www.instagram.com/p/C0xyz",
        "https://linkedin.com", "https://www.linkedin.com/feed/", "https://reddit.com", "https://reddit.com/r/technology",
        "https://github.com", "https://github.com/torvalds/linux", "https://gitlab.com", "https://stackoverflow.com",
        
        # Legitimate Shortened URLs (social media shares, marketing, announcements)
        "https://bit.ly/3x8kL", "https://bit.ly/annual-report-2025", "https://bit.ly/phishmind-app",
        "bit.ly/3x8kL", "bit.ly/cybersecurity-guide", "https://bit.ly/research-paper-2025",
        "https://bit.ly/tech-news-daily", "https://bit.ly/student-portal-info", "https://bit.ly/conference-schedule",
        "https://bit.ly/dev-summit-2025", "https://bit.ly/community-invite", "https://bit.ly/project-docs",
        "https://bit.ly/marketing-brief", "https://bit.ly/product-launch", "https://bit.ly/team-calendar",
        "https://tinyurl.com/abc123", "https://tinyurl.com/abc1234", "https://tinyurl.com/phishmind-demo",
        "tinyurl.com/abc123", "https://tinyurl.com/webinar-replay", "https://tinyurl.com/campus-event",
        "https://tinyurl.com/fall-symposium", "https://tinyurl.com/student-handbook", "https://tinyurl.com/guest-lecture",
        "https://is.gd/updateinfo", "https://is.gd/campusmap", "https://buff.ly/2kP98x", "https://buff.ly/socialpost",
        "https://ow.ly/i/5e8y", "https://ow.ly/press-release", "https://cutt.ly/learning-hub",
        "https://rebrand.ly/cyber-defense", "https://t.me/techcommunity",
    ]

    shortener_hosts = ["https://bit.ly/", "bit.ly/", "https://tinyurl.com/", "tinyurl.com/", "https://t.co/", "t.co/", "https://is.gd/", "https://buff.ly/", "https://ow.ly/", "https://cutt.ly/", "https://rb.gy/"]
    benign_slugs = [
        "3x8kL", "annual-report", "phishmind-app", "cybersecurity-guide", "research-2025",
        "tech-news", "student-info", "conference-schedule", "dev-summit", "community-invite",
        "project-docs", "marketing-brief", "product-launch", "team-calendar", "webinar-replay",
        "campus-event", "fall-symposium", "student-handbook", "guest-lecture", "campusmap",
        "socialpost", "press-release", "learning-hub", "cyber-defense", "newsletter-jan",
        "spring-festival", "alumni-meet", "library-hours", "course-syllabus", "sports-recap",
        "hackathon", "career-fair", "internship-tips", "code-repo", "design-system"
    ]
    for host in shortener_hosts:
        for slug in benign_slugs:
            popular_legit_urls.append(f"{host}{slug}")

    for u in popular_legit_urls:
        url_samples.append((u, 0))

    # Add realistic phishing shortened URLs for discrimination
    phishing_shortened_urls = [
        "https://bit.ly/paypal-login-update", "https://bit.ly/bdo-verify-now", "https://bit.ly/secure-banking-alert",
        "https://tinyurl.com/bank-account-locked", "https://tinyurl.com/claim-500-reward", "https://is.gd/reset-password-now",
        "https://bit.ly/gcash-unblock-wallet", "https://tinyurl.com/bpi-express-login", "https://cutt.ly/urgent-card-suspended",
        "https://bit.ly/apple-id-suspended", "https://tinyurl.com/facebook-security-check", "https://buff.ly/verify-account-now"
    ]
    for u in phishing_shortened_urls:
        url_samples.append((u, 1))

    # Extract URL features
    for u, lbl in url_samples:
        if not u:
            continue
        feats = extractor.extract_features(u, is_email=False, is_sms=False)
        data.append([feats.get(f, 0) for f in extractor.get_feature_names()])
        labels.append(lbl)
    print(f"   Total URL feature vectors: {len(url_samples)}")

    # =========================================================================
    # 2. Collect Email Samples
    # =========================================================================
    print("👉 Collecting Email training samples...")
    email_samples = []

    # 2a. email_dataset.csv
    email_file = os.path.join(BASE_DIR, "email_dataset.csv")
    if os.path.exists(email_file):
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
                e_legit_sample = e_legit.sample(n=target_count, random_state=42) if len(e_legit) > target_count else e_legit

                for _, r in e_legit_sample.iterrows():
                    email_samples.append((str(r[msg_col]), 0))
                for _, r in e_phish_sample.iterrows():
                    email_samples.append((str(r[msg_col]), 1))
                print(f"   + email_dataset.csv: {len(e_legit_sample)} legit, {len(e_phish_sample)} phish")
        except Exception as e:
            print(f"   ⚠️ email_dataset.csv error: {e}")

    # 2b. PhishMind_Email_dict.csv
    dict_email_file = os.path.join(BASE_DIR, "PhishMind_Email_dict.csv")
    if os.path.exists(dict_email_file):
        try:
            df_dict_email = pd.read_csv(dict_email_file, on_bad_lines='skip')
            df_dict_email.columns = [str(c).strip().lower() for c in df_dict_email.columns]
            msg_col = 'input_text' if 'input_text' in df_dict_email.columns else 'body'
            if msg_col in df_dict_email.columns and 'label' in df_dict_email.columns:
                df_dict_email = df_dict_email.dropna(subset=[msg_col, 'label'])
                for _, r in df_dict_email.iterrows():
                    lbl = 1 if str(r['label']).strip() in ('1', 'phishing') else 0
                    email_samples.append((str(r[msg_col]), lbl))
                print(f"   + PhishMind_Email_dict.csv: {len(df_dict_email)} samples added")
        except Exception as e:
            print(f"   ⚠️ PhishMind_Email_dict.csv error: {e}")

    # 2c. Authentic institutional, academic notices and university announcements (Legitimate, label = 0)
    academic_legit_emails = [
        "Dear Students,\n\nPlease be informed that the midterm examinations will begin on November 25, 2026.\n\nStudents are advised to review their respective subject schedules and prepare the necessary examination materials.\n\nFor questions or concerns, kindly contact your department office during regular working hours.\n\nThank you.\n\nOffice of Academic Affairs",
        "Dear Students,\n\nPlease be advised that final examinations for the current semester will take place from December 15 to December 20, 2026. Ensure all clearance requirements are submitted to the registrar before the examination week.\n\nBest regards,\nOffice of the University Registrar",
        "Dear Campus Community,\n\nIn view of the approaching typhoon, classes and university administrative operations are suspended tomorrow. Please stay indoors, remain safe, and monitor official campus channels for updates.\n\nOffice of Student Affairs",
        "Dear Students,\n\nAcademic advising for the upcoming semester is now open. Please schedule an appointment with your designated faculty advisor during consulting hours to finalize your course enrollment.\n\nCollege of Computer Studies",
        "Dear Students and Faculty,\n\nThe University Main Library will operate on extended hours during the midterm examination period to accommodate student study groups. Quiet study rooms and computer terminals are available.\n\nUniversity Library Services",
        "Dear Graduating Class,\n\nPlease ensure that your graduation clearance forms are completed and submitted to your respective department heads by the end of this month. For questions, visit the student records office.\n\nRegistrar's Office",
        "Dear Students,\n\nThis is a friendly reminder that the deadline for the second installment of tuition fees is on the 30th of this month. Payments may be settled at the university cashier or designated partner banks.\n\nFinance Department",
        "Subject: Midterm Examination Guidelines\n\nDear Students,\n\nPlease find the examination room assignments posted on your department bulletin boards. Make sure to bring your student ID and required testing materials.\n\nAcademic Evaluation Committee",
        "Subject: Faculty Consultation Hours Update\n\nDear Students,\n\nStarting this week, faculty consultation hours for Computer Science courses will be held every Tuesday and Thursday from 2:00 PM to 5:00 PM in Room 304.\n\nDepartment of Information Technology",
        "Dear Student Body,\n\nThe annual university career fair will be held next Friday at the campus auditorium. Local and international partner employers will be conducting on-site interviews.\n\nCareer Placement Office",
        "Dear Students,\n\nPlease check your student portal for your preliminary grades. If you have any inquiries regarding your grade computation, kindly coordinate directly with your subject instructor.\n\nDean's Office",
        "Subject: Campus Health and Wellness Week\n\nDear All,\n\nWe invite all students and staff to participate in the upcoming Health and Wellness seminar series starting Monday at the gymnasium.\n\nUniversity Health Services"
    ]
    # Multiply to ensure solid representation across decision tree splits
    for email_text in academic_legit_emails * 10:
        email_samples.append((email_text, 0))

    # 2d. Representative Phishing Emails (urgent credential theft, fake IT alerts, label = 1)
    phishing_credential_emails = [
        "Urgent: Your account will be closed within 24 hours. Verify your password and login credentials now: https://fake-bank-auth.com",
        "Subject: Urgent Security Notice - Account Suspension\n\nDear Customer,\n\nYour account has been flagged for unauthorized activity and will be deactivated immediately. To prevent account closure, verify your password and identity here: https://security-update-portal.com/verify\n\nIT Support",
        "Subject: Password Expiry Notification\n\nYour Microsoft 365 password expires today. Keep your current password by verifying your credentials at: https://outlook-auth-verify.xyz/login\n\nSystem Administrator",
        "Subject: Immediate Action Required: Payroll Discrepancy\n\nWe detected an issue processing your monthly salary direct deposit. Please update your banking credentials and card details immediately: https://payroll-update-support.online/auth",
        "Subject: Account Warning: Unusual Sign-In Detected\n\nSomeone attempted to access your online banking from an unknown device. Verify your credentials and confirm your account now: https://bank-verify-alert.net/security",
    ]
    for email_text in phishing_credential_emails * 15:
        email_samples.append((email_text, 1))

    # Extract Email features
    for text, lbl in email_samples:
        if not text:
            continue
        feats = extractor.extract_features(text, is_email=True, is_sms=False)
        data.append([feats.get(f, 0) for f in extractor.get_feature_names()])
        labels.append(lbl)
    print(f"   Total Email feature vectors: {len(email_samples)}")

    # =========================================================================
    # 3. Collect SMS Samples
    # =========================================================================
    print("👉 Collecting SMS training samples...")
    sms_samples = []

    # 3a. sms_dataset.csv
    sms_file = os.path.join(BASE_DIR, "sms_dataset.csv")
    if os.path.exists(sms_file):
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
                s_legit_sample = s_legit.sample(n=target_count, random_state=42) if len(s_legit) > target_count else s_legit

                for _, r in s_legit_sample.iterrows():
                    sms_samples.append((str(r[msg_col]), 0))
                for _, r in s_phish_sample.iterrows():
                    sms_samples.append((str(r[msg_col]), 1))
                print(f"   + sms_dataset.csv: {len(s_legit_sample)} legit, {len(s_phish_sample)} phish")
        except Exception as e:
            print(f"   ⚠️ sms_dataset.csv error: {e}")

    # 3b. PhishMind_SMS_dict.csv
    dict_sms_file = os.path.join(BASE_DIR, "PhishMind_SMS_dict.csv")
    if os.path.exists(dict_sms_file):
        try:
            df_dict_sms = pd.read_csv(dict_sms_file, on_bad_lines='skip')
            df_dict_sms.columns = [str(c).strip().lower() for c in df_dict_sms.columns]
            msg_col = 'input_text' if 'input_text' in df_dict_sms.columns else 'text'
            if msg_col in df_dict_sms.columns and 'label' in df_dict_sms.columns:
                df_dict_sms = df_dict_sms.dropna(subset=[msg_col, 'label'])
                for _, r in df_dict_sms.iterrows():
                    lbl = 1 if str(r['label']).strip() in ('1', 'phishing', 'smishing') else 0
                    sms_samples.append((str(r[msg_col]), lbl))
                print(f"   + PhishMind_SMS_dict.csv: {len(df_dict_sms)} samples added")
        except Exception as e:
            print(f"   ⚠️ PhishMind_SMS_dict.csv error: {e}")

    # 3c. tagalog-sms.csv (All Legitimate authentic Filipino notifications and advisories, label = 0)
    tagalog_file = os.path.join(BASE_DIR, "tagalog-sms.csv")
    if os.path.exists(tagalog_file):
        try:
            df_tagalog = pd.read_csv(tagalog_file, on_bad_lines='skip')
            df_tagalog.columns = [str(c).strip().lower() for c in df_tagalog.columns]
            msg_col = 'input_text' if 'input_text' in df_tagalog.columns else ('text' if 'text' in df_tagalog.columns else None)
            if msg_col and msg_col in df_tagalog.columns:
                df_tagalog = df_tagalog.dropna(subset=[msg_col])
                for _, r in df_tagalog.iterrows():
                    # All Tagalog SMS samples are verified legitimate communications
                    sms_samples.append((str(r[msg_col]), 0))
                print(f"   + tagalog-sms.csv: {len(df_tagalog)} legitimate Filipino SMS messages added")
        except Exception as e:
            print(f"   ⚠️ tagalog-sms.csv error: {e}")

    # 3d. Representative Phishing SMS / Smishing lures (label = 1)
    phishing_sms_samples = [
        "ALERT: Your bank account has been suspended. Verify your identity now: https://secure-bank.xyz/verify",
        "BDO Alert: Your online banking account has been temporarily blocked due to irregular login attempts. Unlock now: https://bdo-security-online.xyz/reactivate",
        "GCash Notice: Your wallet has been placed on hold due to SIM card unverified status. Verify your details within 12 hours: https://gcash-verify-user.top/wallet",
        "Maya Advisory: Your account requires mandatory security update. Failure to update will result in account closure. Click here: https://maya-auth-update.club",
        "DHL Express: Your delivery parcel #938210 is detained at customs clearance. Settle fee or update address: https://dhl-express-track.info/fee",
        "Security Alert: We detected an unrecognized device login to your mobile banking. If this was not you, cancel transaction: https://security-cancel-auth.net",
    ]
    for sms_text in phishing_sms_samples * 15:
        sms_samples.append((sms_text, 1))

    # Extract SMS features
    for text, lbl in sms_samples:
        if not text:
            continue
        feats = extractor.extract_features(text, is_email=False, is_sms=True)
        data.append([feats.get(f, 0) for f in extractor.get_feature_names()])
        labels.append(lbl)
    print(f"   Total SMS feature vectors: {len(sms_samples)}")

    # =========================================================================
    # 4. Train Master Random Forest Model
    # =========================================================================
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
    print(f"📈 Master Model Metrics:")
    print(f"   Accuracy: {metrics['accuracy']:.4f}")

    out_path = os.path.join(BASE_DIR, "phishing_model.pkl")
    model.save_model(out_path)
    print(f"✅ Master Multi-Modal model saved to: {out_path}")

if __name__ == "__main__":
    try:
        train_master_model()
    except Exception:
        import traceback
        traceback.print_exc()
