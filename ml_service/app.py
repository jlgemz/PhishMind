import os
import re
import sys
import shutil
import traceback

from typing import Optional
from fastapi import FastAPI, UploadFile, File, Form, HTTPException
from pydantic import BaseModel
import uvicorn

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from model import PhishingMLModel
from feature_extractor import FeatureExtractor
from llm_explainer import generate_explanation, _fallback_explanation

# Feature definitions for modality-specific indicator filtering
URL_FEATURES = {
    'url_length', 'domain_length', 'path_length', 'query_length',
    'num_dots', 'num_hyphens', 'num_underscores', 'num_slashes',
    'num_question_marks', 'num_equal_signs', 'num_at_signs',
    'num_percent_signs', 'num_digits', 'has_https', 'has_http', 'has_ip',
    'has_suspicious_keyword', 'has_suspicious_tld', 'subdomain_count',
    'domain_entropy', 'has_query_string', 'has_fragment',
    'domain_age_days', 'has_brand_spoofing', 'digit_ratio',
    'special_char_ratio', 'has_www', 'has_hex_path', 'has_suspicious_ext',
    'is_shortened_url', 'is_popular_domain',
}

EMAIL_FEATURES = {
    'email_body_length', 'email_subject_length', 'email_has_urls',
    'email_url_count', 'email_has_attachments', 'email_urgency_score',
    'sender_is_free_email', 'has_credential_request',
    'sender_domain_mismatch', 'has_threatening_language',
}

SMS_FEATURES = {
    'email_body_length', 'email_urgency_score', 'has_credential_request',
    'has_threatening_language', 'digit_ratio', 'has_suspicious_keyword',
    'has_ip', 'email_has_urls', 'email_url_count',
}

EMBEDDED_LINK_FEATURES = {
    'has_suspicious_tld', 'has_brand_spoofing', 'is_shortened_url',
    'is_popular_domain', 'has_suspicious_keyword', 'has_https',
    'has_http', 'has_ip', 'subdomain_count', 'domain_entropy',
    'domain_length', 'path_length', 'num_dots', 'num_hyphens', 'url_length'
}

def get_allowed_features_for_modality(modality: str, features_dict: dict) -> set:
    mod = modality.lower()
    if 'url' in mod or 'web' in mod:
        return set(URL_FEATURES)
    elif 'sms' in mod or 'message' in mod or 'social' in mod:
        allowed = set(SMS_FEATURES)
        if features_dict.get('email_has_urls', 0) == 1:
            allowed |= EMBEDDED_LINK_FEATURES
        return allowed
    elif 'email' in mod:
        allowed = set(EMAIL_FEATURES)
        if features_dict.get('email_has_urls', 0) == 1:
            allowed |= EMBEDDED_LINK_FEATURES
        return allowed
    return set(URL_FEATURES) | set(EMAIL_FEATURES) | set(SMS_FEATURES)

def get_modality_display_name(feature_key: str, modality: str) -> str:
    mod = modality.lower()
    if 'sms' in mod or 'message' in mod or 'social' in mod:
        sms_labels = {
            'email_body_length': 'SMS Message Character Length',
            'email_urgency_score': 'Urgent Call-to-Action Language',
            'has_credential_request': 'Requests Sensitive Credentials / PIN',
            'has_threatening_language': 'Account Suspension / Threatening Language',
            'digit_ratio': 'Digit & Keyword Density',
            'has_suspicious_keyword': 'Security Alert & Promo Keywords',
            'has_ip': 'Direct IP Address Host in Link',
            'email_has_urls': 'Contains Embedded Web Link',
            'email_url_count': 'Count of Embedded Links',
            'has_suspicious_tld': 'High-Risk Link Domain Extension (TLD)',
            'is_shortened_url': 'Shortened URL Redirection Link',
            'has_brand_spoofing': 'Brand Impersonation in Link',
            'is_popular_domain': 'Verified Official Brand Domain',
            'has_https': 'Secure HTTPS Protocol on Link',
            'subdomain_count': 'Subdomain Count on Link',
            'domain_entropy': 'Character Randomness in Link Domain',
        }
        if feature_key in sms_labels:
            return sms_labels[feature_key]

    elif 'email' in mod:
        email_labels = {
            'email_body_length': 'Email Body Character Length',
            'email_subject_length': 'Email Subject Line Length',
            'email_has_urls': 'Embedded Hyperlinks Present',
            'email_url_count': 'Count of Embedded Hyperlinks',
            'email_has_attachments': 'File Attachment Mention or References',
            'email_urgency_score': 'Urgency & Pressure Language Score',
            'sender_is_free_email': 'Sent from Free Webmail Service',
            'has_credential_request': 'Requests Passwords, PINs, or Credentials',
            'sender_domain_mismatch': 'Free Sender Claiming Institutional Brand',
            'has_threatening_language': 'Threatening or Coercive Language',
            'has_suspicious_tld': 'High-Risk Domain Extension (TLD) in Link',
            'has_brand_spoofing': 'Brand Lookalike / Impersonation in Link',
            'is_shortened_url': 'Shortened URL Redirection Service',
            'is_popular_domain': 'Verified Official Institutional Domain',
            'has_https': 'Secure HTTPS Protocol on Embedded Link',
        }
        if feature_key in email_labels:
            return email_labels[feature_key]

    url_labels = {
        'url_length': 'URL Character Length',
        'domain_length': 'Domain Character Length',
        'path_length': 'URL Path Length',
        'query_length': 'Query String Length',
        'num_dots': 'Dot Separator Count in URL',
        'num_hyphens': 'Hyphen Count in Hostname/Path',
        'num_underscores': 'Underscore Count in URL',
        'num_slashes': 'Path Slash Separators',
        'num_digits': 'Digit Character Count in URL',
        'has_https': 'HTTPS Protocol Encryption',
        'has_http': 'Unencrypted HTTP Connection',
        'has_ip': 'Raw IP Address Hostname',
        'has_suspicious_keyword': 'Suspicious Lure Keyword in URL',
        'has_suspicious_tld': 'Suspicious High-Risk Domain TLD',
        'subdomain_count': 'Excessive Subdomain Count',
        'domain_entropy': 'Domain Randomness (Entropy)',
        'has_brand_spoofing': 'Brand Impersonation / Lookalike Domain',
        'digit_ratio': 'Digit Character Ratio in URL',
        'special_char_ratio': 'Special Character Density',
        'has_www': 'Standard WWW Host Prefix',
        'has_suspicious_ext': 'Executable or Script File Extension',
        'is_shortened_url': 'Shortened URL Redirection Service',
        'is_popular_domain': 'Verified Official Brand Platform',
    }
    if feature_key in url_labels:
        return url_labels[feature_key]

    return feature_key.replace('_', ' ').title()

app = FastAPI()
model = PhishingMLModel()
extractor = FeatureExtractor()

try:
    model.load_model(os.path.join(BASE_DIR, "phishing_model.pkl"))
    model.feature_names = extractor.get_feature_names()
    print(f"✅ Model loaded. n_features_in_ = {model.model.n_features_in_}")
except Exception as e:
    print(f"⚠️ Error loading model: {e}")


DATASET_FILES = {
    "url":   "url_dataset.csv",
    "email": "email_dataset.csv",
    "sms":   "sms_dataset.csv",
}


@app.get("/")
def read_root():
    return {"status": "online", "model_loaded": model.model is not None}


@app.get("/health")
def health():
    if model.model is None:
        raise HTTPException(status_code=503, detail="Model not loaded")
    return {"status": "healthy", "model_loaded": True}


@app.post("/admin/upload-dataset")
async def upload_dataset(
    dataset_type: str = Form(...),
    file: UploadFile = File(...),
):
    try:
        if dataset_type not in DATASET_FILES:
            raise HTTPException(
                status_code=400,
                detail="dataset_type must be one of: " + ", ".join(DATASET_FILES.keys()),
            )

        filename = DATASET_FILES[dataset_type]
        filepath = os.path.join(BASE_DIR, filename)
        print(f"💾 Saving uploaded file to: {filepath}")

        await file.seek(0)
        with open(filepath, "wb") as buffer:
            shutil.copyfileobj(file.file, buffer)

        size = os.path.getsize(filepath)
        if size == 0:
            raise HTTPException(
                status_code=500,
                detail="File saved but is empty (0 bytes).",
            )

        print(f"✅ File saved successfully. Size: {size} bytes")
        return {
            "status": "success",
            "message": f"{filename} saved successfully ({size} bytes). "
                       "Click 'Train Model' next.",
        }
    except HTTPException:
        raise
    except Exception as e:
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=f"Upload failed: {e}")


def _reload_model():
    global model
    model.load_model(os.path.join(BASE_DIR, "phishing_model.pkl"))
    model.feature_names = extractor.get_feature_names()


@app.post("/admin/train-url")
def train_url():
    try:
        os.chdir(BASE_DIR)
        from train_url_csv import train_url_model
        train_url_model()
        _reload_model()
        return {"status": "success", "message": "URL Model trained and reloaded successfully!"}
    except Exception as e:
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/admin/train-email")
def train_email():
    try:
        os.chdir(BASE_DIR)
        from train_email import train_email_model
        train_email_model()
        _reload_model()
        return {"status": "success", "message": "Email Model trained and reloaded successfully!"}
    except Exception as e:
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/admin/train-sms")
def train_sms():
    try:
        os.chdir(BASE_DIR)
        from train_sms import train_sms_model
        train_sms_model()
        _reload_model()
        return {"status": "success", "message": "SMS Model trained and reloaded successfully!"}
    except Exception as e:
        traceback.print_exc()
        raise HTTPException(status_code=500, detail=str(e))


class PredictRequest(BaseModel):
    content: Optional[str] = None
    url: Optional[str] = None
    modality: str = "url"


@app.post("/predict")
async def predict_content(req: PredictRequest):
    raw_text = req.content if (req.content and req.content.strip()) else (req.url if (req.url and req.url.strip()) else "")
    content = raw_text.strip()
    modality = req.modality.lower().strip() if req.modality else "url"

    # 1. Validate Input Processability
    is_valid, error_msg = extractor.validate_input(content, modality)
    if not is_valid:
        return {
            "status": "invalid",
            "is_processable": False,
            "error": error_msg,
            "modality": modality,
            "verdict": "Invalid Input",
            "prediction": -1,
            "confidence": 0.0,
            "risk_score": 0,
            "probabilities": {"legitimate": 0.0, "phishing": 0.0},
            "reliability": "none",
            "is_borderline": False,
            "warning": f"Input validation failed: {error_msg}. Invalid or malformed inputs are not treated as legitimate.",
            "shap_risk_indicators": [
                {"feature": "Invalid Input Format", "value": "0.00", "type": "error"}
            ],
            "explanation": f"The submitted content could not be processed because it does not meet structural requirements for {modality.upper()} analysis ({error_msg}).",
            "recommendation": "Please verify your input formatting (e.g., ensure URLs include a valid domain or IP address, or provide sufficient message text) and try again."
        }

    is_sms = 'sms' in modality
    is_email = 'email' in modality and not is_sms

    features_dict = extractor.extract_features(content, is_email=is_email, is_sms=is_sms)
    feature_vector = [features_dict.get(f, 0) for f in model.feature_names]

    ml_result = model.predict_with_shap(feature_vector)
    prediction = int(ml_result["prediction"])
    confidence = float(ml_result["confidence"])
    probabilities = ml_result.get("probabilities", {
        "legitimate": 1.0 - confidence if prediction == 1 else confidence,
        "phishing": confidence if prediction == 1 else 1.0 - confidence
    })

    prob_phishing = float(probabilities.get("phishing", confidence if prediction == 1 else 1.0 - confidence))
    prob_legitimate = float(probabilities.get("legitimate", 1.0 - prob_phishing))

    # Borderline / Uncertainty check: probability between 40% and 60%
    is_borderline = 0.40 <= prob_phishing <= 0.60
    if is_borderline:
        reliability = "low"
    elif confidence >= 0.80:
        reliability = "high"
    else:
        reliability = "moderate"

    # Risk score (0-100 scale representing threat likelihood)
    risk_score = int(round(prob_phishing * 100))

    # Human-readable SHAP indicators filtered strictly by modality
    allowed_features = get_allowed_features_for_modality(modality, features_dict)
    raw_risk = [item for item in ml_result.get("top_risk_indicators", []) if item[0] in allowed_features]
    raw_safety = [item for item in ml_result.get("top_safety_indicators", []) if item[0] in allowed_features]
    raw_influential = [item for item in ml_result.get("top_influential_indicators", []) if item[0] in allowed_features]

    shap_indicators = []
    source_list = raw_risk if prediction == 1 else raw_safety
    if not source_list:
        source_list = raw_influential

    for feat, val in source_list:
        if len(shap_indicators) >= 5:
            break
        val_str = f"+{val:.2f}" if val > 0 else f"{val:.2f}"
        feat_name = get_modality_display_name(feat, modality)
        shap_indicators.append({"feature": feat_name, "value": val_str, "type": "risk" if val > 0 else "safety"})

    # Backfill with influential features if list is short
    if len(shap_indicators) < 3:
        for feat, val in raw_influential:
            if len(shap_indicators) >= 5:
                break
            feat_name = get_modality_display_name(feat, modality)
            if not any(i["feature"] == feat_name for i in shap_indicators):
                val_str = f"+{val:.2f}" if val > 0 else f"{val:.2f}"
                shap_indicators.append({"feature": feat_name, "value": val_str, "type": "risk" if val > 0 else "safety"})

    if not shap_indicators:
        shap_indicators = [{"feature": f"Standard {modality.upper()} Baseline Profile", "value": "0.00", "type": "neutral"}]

    # Warnings: Never claim an input is guaranteed safe simply because no known pattern was found
    if prediction == 1:
        warning_text = (
            f"Threat Detected: The Random Forest model identified patterns consistent with known phishing lures "
            f"(Model Confidence: {confidence*100:.1f}%). Exercise caution and avoid clicking links or providing sensitive information."
        )
        rec_text = "Do not click links, do not reply, and report this suspicious message to your security team immediately."
    else:
        warning_text = (
            f"Notice: The Random Forest model classified this input as Legitimate based on learned patterns "
            f"(Model Confidence: {confidence*100:.1f}%). However, absence of detected phishing signatures DOES NOT guarantee safety. "
            f"Novel attack vectors, zero-day links, or compromised legitimate accounts may not be recognized by the model. Always verify sender identity independently."
        )
        rec_text = "While this input does not match known phishing signatures, remain vigilant and verify unexpected requests through trusted out-of-band channels."

    if is_borderline:
        warning_text = (
            f"Borderline / Uncertain Result: The model prediction has low confidence ({confidence*100:.1f}%), near the 50/50 decision boundary. "
            f"This content displays ambiguous characteristics. Treat with extra suspicion."
        )

    feedback_payload = {
        "modality": modality.upper(),
        "ml_prediction": prediction,
        "ml_confidence": round(confidence * 100, 1),
        "student_decision": prediction,
        "student_agrees_with_model": True,
        "verified_risk_indicators": [{"plain_name": i["feature"], "contribution": i["value"]} for i in shap_indicators],
        "misconceptions": [],
        "targeted_practice": ["Review sender and domain carefully."],
    }

    try:
        explanation = generate_explanation(feedback_payload)
    except Exception as e:
        explanation = _fallback_explanation(feedback_payload, str(e))

    return {
        "status": "success",
        "is_processable": True,
        "modality": modality,
        "prediction": prediction,
        "verdict": "Phishing" if prediction == 1 else "Legitimate",
        "confidence": confidence,
        "risk_score": risk_score,
        "probabilities": probabilities,
        "reliability": reliability,
        "is_borderline": is_borderline,
        "warning": warning_text,
        "shap_risk_indicators": shap_indicators,
        "explanation": explanation,
        "recommendation": rec_text,
    }


class AdaptiveRequest(BaseModel):
    url: str
    scenario_type: str
    student_decision: int
    student_reasoning: str
    is_phishing: bool


@app.post("/adaptive/evaluate")
async def adaptive_evaluate(req: AdaptiveRequest):
    sc_type_lower = req.scenario_type.lower()
    is_sms = "sms" in sc_type_lower or "message" in sc_type_lower or "social" in sc_type_lower
    is_email = "email" in sc_type_lower and not is_sms
    modality_str = "SMS" if is_sms else ("Email" if is_email else "URL")

    features_dict = extractor.extract_features(
        req.url, is_email=is_email, is_sms=is_sms
    )
    feature_vector = [features_dict.get(f, 0) for f in model.feature_names]

    ml_result = model.predict_with_shap(feature_vector)
    ml_prediction = int(ml_result["prediction"])
    ml_confidence = float(ml_result["confidence"])

    print(f"\n--- ML DEBUG ({modality_str}) ---")
    print(f"Content: {req.url[:60]}...")
    print(f"Ground Truth: {'Phishing' if req.is_phishing else 'Legitimate'}")
    print(f"ML Prediction: {'Phishing' if ml_prediction == 1 else 'Legitimate'} ({ml_confidence*100:.1f}%)")
    print(f"----------------\n")

    ml_confidence = max(0.05, min(0.98, ml_confidence))
    ground_truth_prediction = 1 if req.is_phishing else 0
    is_correct = (ground_truth_prediction == req.student_decision)

    # Filter SHAP indicators strictly by what relates to the modality
    allowed_features = get_allowed_features_for_modality(modality_str, features_dict)
    raw_risk = [item for item in ml_result.get("top_risk_indicators", []) if item[0] in allowed_features]
    raw_safety = [item for item in ml_result.get("top_safety_indicators", []) if item[0] in allowed_features]
    raw_influential = [item for item in ml_result.get("top_influential_indicators", []) if item[0] in allowed_features]

    shap_ui, shap_llm = [], []
    candidate_list = raw_risk if ml_prediction == 1 else raw_safety
    if not candidate_list:
        candidate_list = raw_influential

    for feat, val in candidate_list:
        if len(shap_ui) >= 5:
            break
        val_str = f"+{val:.2f}" if val > 0 else f"{val:.2f}"
        feat_name = get_modality_display_name(feat, modality_str)
        shap_ui.append({"feature": feat_name, "value": val_str, "type": "risk" if val > 0 else "safety"})
        shap_llm.append({"plain_name": feat_name, "contribution": val_str})

    # Backfill with remaining allowed influential features if list is short
    if len(shap_ui) < 3:
        for feat, val in raw_influential:
            if len(shap_ui) >= 5:
                break
            feat_name = get_modality_display_name(feat, modality_str)
            if not any(i["feature"] == feat_name for i in shap_ui):
                val_str = f"+{val:.2f}" if val > 0 else f"{val:.2f}"
                shap_ui.append({"feature": feat_name, "value": val_str, "type": "risk" if val > 0 else "safety"})
                shap_llm.append({"plain_name": feat_name, "contribution": val_str})

    if not shap_ui:
        shap_ui = [{"feature": f"Standard {modality_str} Baseline Profile", "value": "0.00", "type": "neutral"}]
        shap_llm = [{"plain_name": f"Standard {modality_str} Baseline Profile", "contribution": "0.00"}]

    misconceptions = []
    practice_suggestions = []

    if not is_correct:
        misconceptions.append({
            "name": f"Deceptive {modality_str} Pattern",
            "description": f"The {modality_str.lower()} content contained cues that bypassed your verification."
        })
        practice_suggestions.extend([
            f"Carefully inspect {modality_str} sender authenticity and domain spelling.",
            "Verify unexpected or urgent requests through official contact directories."
        ])
    elif ml_prediction != ground_truth_prediction:
        misconceptions.append({
            "name": "Model Misclassification",
            "description": "You caught a deceptive signal that the automated model did not prioritize. Trust your evidence-based verification over AI."
        })
        practice_suggestions.append("Outstanding work! Your human inspection outperformed the AI model.")
    else:
        practice_suggestions.append("Excellent decision! Your judgment follows cybersecurity best practices.")

    feedback_payload = {
        "modality": modality_str,
        "ml_prediction": ground_truth_prediction,
        "ml_confidence": round(ml_confidence * 100, 1),
        "student_decision": req.student_decision,
        "student_agrees_with_model": is_correct,
        "verified_risk_indicators": shap_llm,
        "misconceptions": misconceptions,
        "targeted_practice": practice_suggestions,
    }

    try:
        ai_explanation = generate_explanation(feedback_payload)
    except Exception as e:
        ai_explanation = _fallback_explanation(feedback_payload, str(e))

    return {
        "prediction": ml_prediction,
        "ground_truth": ground_truth_prediction,
        "confidence": ml_confidence,
        "probabilities": ml_result.get("probabilities", {}),
        "mastery_achieved": is_correct,
        "shap_risk_indicators": shap_ui,
        "misconceptions": misconceptions,
        "targeted_practice": practice_suggestions,
        "explanation": ai_explanation,
    }


if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=8001)