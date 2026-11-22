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
from llm_explainer import generate_explanation

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

    # Human-readable SHAP indicators
    shap_indicators = []
    if prediction == 1:
        for feat, val in ml_result.get("top_risk_indicators", []):
            val_str = f"+{val:.2f}" if val > 0 else f"{val:.2f}"
            feat_name = feat.replace("_", " ").title()
            shap_indicators.append({"feature": feat_name, "value": val_str, "type": "risk"})
    else:
        for feat, val in ml_result.get("top_safety_indicators", []):
            val_str = f"{val:.2f}"
            feat_name = feat.replace("_", " ").title()
            shap_indicators.append({"feature": feat_name, "value": val_str, "type": "safety"})

    # Backfill with influential features if list is short
    if len(shap_indicators) < 3:
        for feat, val in ml_result.get("top_influential_indicators", []):
            val_str = f"+{val:.2f}" if val > 0 else f"{val:.2f}"
            feat_name = feat.replace("_", " ").title()
            if not any(i["feature"] == feat_name for i in shap_indicators):
                shap_indicators.append({"feature": feat_name, "value": val_str, "type": "risk" if val > 0 else "safety"})
            if len(shap_indicators) >= 5:
                break

    if not shap_indicators:
        shap_indicators = [{"feature": "Baseline Feature Distribution", "value": "0.00", "type": "neutral"}]

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
    except Exception:
        verdict_str = "Phishing" if prediction == 1 else "Legitimate"
        explanation = (
            f"The Random Forest model evaluated this {modality.upper()} content using 39 structural and lexical features, "
            f"classifying it as {verdict_str} with {confidence*100:.1f}% confidence. "
            f"Key feature contributions: " + ", ".join(f"{i['feature']} ({i['value']})" for i in shap_indicators[:3]) + "."
        )

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
    is_sms = req.scenario_type in ("SMS Phishing", "Messaging & Social Media")
    is_email = req.scenario_type in ("Email Phishing",) and not is_sms

    features_dict = extractor.extract_features(
        req.url, is_email=is_email, is_sms=is_sms
    )
    feature_vector = [features_dict.get(f, 0) for f in model.feature_names]

    ml_result = model.predict_with_shap(feature_vector)
    ml_prediction = int(ml_result["prediction"])
    ml_confidence = float(ml_result["confidence"])

    print(f"\n--- ML DEBUG ---")
    print(f"URL: {req.url[:60]}...")
    print(f"Ground Truth: {'Phishing' if req.is_phishing else 'Legitimate'}")
    print(f"ML Prediction: {'Phishing' if ml_prediction == 1 else 'Legitimate'} ({ml_confidence*100:.1f}%)")
    print(f"Top SHAP: {ml_result.get('top_risk_indicators', [])}")
    print(f"----------------\n")

    ml_confidence = max(0.05, min(0.98, ml_confidence))
    ground_truth_prediction = 1 if req.is_phishing else 0
    is_correct = (ground_truth_prediction == req.student_decision)

    shap_ui, shap_llm = [], []
    for feat, val in ml_result.get("top_risk_indicators", []):
        if len(shap_ui) >= 5:
            break
        val_str = f"+{val:.2f}" if val > 0 else f"{val:.2f}"
        feat_name = feat.replace("_", " ").title()
        shap_ui.append({"feature": feat_name, "value": val_str, "type": "risk"})
        shap_llm.append({"plain_name": feat_name, "contribution": val_str})

    if not shap_ui:
        for feat, val in ml_result.get("top_safety_indicators", []):
            if len(shap_ui) >= 5:
                break
            val_str = f"{val:.2f}"
            feat_name = feat.replace("_", " ").title()
            shap_ui.append({"feature": feat_name, "value": val_str, "type": "safety"})

    if not shap_ui:
        shap_ui = [{"feature": "No strong risk indicators", "value": "0.00", "type": "neutral"}]
        shap_llm = [{"plain_name": "No strong risk indicators", "contribution": "0.00"}]

    misconceptions = []
    practice_suggestions = []

    if not is_correct:
        misconceptions.append({
            "name": "Fell for a deceptive pattern",
            "description": "The content used visual or structural tricks that bypassed your initial check."
        })
        practice_suggestions.extend([
            "Review the domain spelling carefully.",
            "Check if the sender email matches the official domain."
        ])
    elif ml_prediction != ground_truth_prediction:
        misconceptions.append({
            "name": "AI Misclassification",
            "description": "You spotted a risk that the Random Forest model missed. Always trust your evidence-based judgment over the AI."
        })
        practice_suggestions.append("Keep up the good work! You outperformed the AI on this one.")
    else:
        practice_suggestions.append("Keep up the good work!")

    feedback_payload = {
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
    except Exception:
        ai_explanation = "The language model could not generate an explanation."

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