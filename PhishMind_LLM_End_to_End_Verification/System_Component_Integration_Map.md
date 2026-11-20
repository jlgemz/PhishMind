# PhishMind System Component Integration Map

**Project:** PhishMind: Explainable AI-Based Phishing Detection and Adaptive Cybersecurity Awareness System  
**Audit Date:** November 2026  
**Auditor:** Senior Software Quality Assurance & Machine Learning Systems Auditor  

---

## 1. Architectural Overview & Component Inventory

PhishMind is an end-to-end multi-tier intelligent web platform that couples a Laravel 11 MVC PHP web application with a FastAPI Python Machine Learning microservice and a local Ollama Large Language Model inference engine.

```
+----------------------------------------------------------------------------------------------------+
|                                    PRESENTATION TIER (Laravel 11)                                  |
|                                                                                                    |
|  [Blade Templates]                                                                                 |
|  - resources/views/learning/select.blade.php    (Modality selection: URL, Email, SMS)              |
|  - resources/views/learning/start.blade.php     (Student judgment & reasoning input form)          |
|  - resources/views/learning/result.blade.php    (SHAP visual bars, Qwen LLM card, Feedback alerts) |
|  - resources/views/check/index.blade.php        (Direct content inspection interface)              |
|                                                                                                    |
|  [HTTP Controllers]                                                                                |
|  - app/Http/Controllers/AdaptiveLearningController.php  -> Submits to http://127.0.0.1:8001/adaptive/evaluate |
|  - app/Http/Controllers/CheckContentController.php      -> Submits to http://127.0.0.1:8001/adaptive/evaluate |
+-------------------------------------------------+--------------------------------------------------+
                                                  | HTTP JSON Request
                                                  v
+----------------------------------------------------------------------------------------------------+
|                                    INTELLIGENCE TIER (FastAPI / Port 8001)                         |
|                                                                                                    |
|  [API Routes - ml_service/app.py]                                                                   |
|  - POST /adaptive/evaluate                      (Primary adaptive feedback evaluation pipeline)     |
|  - POST /predict                                (Direct RF classification endpoint)                |
|  - POST /admin/train-url, /train-email, /train-sms (Admin model retraining endpoints)               |
|                                                                                                    |
|  [Feature Extraction - ml_service/feature_extractor.py]                                            |
|  - Class: FeatureExtractor                                                                         |
|  - Modality router: extract_features(content, is_email, is_sms)                                    |
|  - Feature count: 38 total features (28 URL lexical/structural + 10 Email/SMS specific)            |
|                                                                                                    |
|  [Classification & Explainability - ml_service/model.py]                                           |
|  - Class: PhishingMLModel                                                                          |
|  - Classifier: sklearn.ensemble.RandomForestClassifier (n_estimators=100, max_depth=10)            |
|  - Model storage: ml_service/phishing_model.pkl                                                    |
|  - XAI Engine: shap.TreeExplainer(self.model) -> predict_with_shap(feature_vector)                  |
|                                                                                                    |
|  [LLM Explainer - ml_service/llm_explainer.py]                                                     |
|  - Function: generate_explanation(feedback_payload)                                                |
|  - Prompt synthesis: Injects Model Probability, SHAP signals, Student Judgment, Misconceptions      |
|  - Fallback engine: _fallback_explanation(payload, error) (Deterministic rule-based backup)         |
+-------------------------------------------------+--------------------------------------------------+
                                                  | HTTP POST /api/generate
                                                  v
+----------------------------------------------------------------------------------------------------+
|                                    INFERENCE TIER (Ollama Local Daemon / Port 11501)               |
|                                                                                                    |
|  - Host URL: http://127.0.0.1:11501                                                                |
|  - Active Model: qwen3.5:2b (Q8_0 Quantization, 1.9B parameters, GGUF format)                      |
|  - Endpoints: /api/tags, /api/show, /api/generate                                                  |
|  - Execution Mode: CPU Inference (num_gpu: 0 pinned due to host CUDA PTX driver incompatibility)   |
+----------------------------------------------------------------------------------------------------+
```

---

## 2. Component Integration Matrix

| Component | Source File Path | Key Functions / Classes | Input Schema | Output Schema | Target Downstream |
|---|---|---|---|---|---|
| **Presentation (Web)** | `resources/views/learning/result.blade.php` | Blade templating engine | `$record` (Eloquent LearningRecord model) | HTML/Tailwind CSS responsive DOM | End-User Browser |
| **Backend Controller** | `app/Http/Controllers/AdaptiveLearningController.php` | `submit(Request $request)` | `scenario_id`, `student_decision`, `student_reasoning` | JSON `{success, redirect}` & DB record | FastAPI `/adaptive/evaluate` |
| **Direct Checker** | `app/Http/Controllers/CheckContentController.php` | `analyze(Request $request)` | `content`, `type` | JSON `{verdict, risk_score, indicators, explanation}` | Web UI view |
| **API Gateway** | `ml_service/app.py` | `adaptive_evaluate(req: AdaptiveRequest)` | JSON: `url`, `scenario_type`, `student_decision`, `student_reasoning`, `is_phishing` | JSON: `prediction`, `confidence`, `shap_risk_indicators`, `explanation` | Laravel Backend |
| **Feature Extraction** | `ml_service/feature_extractor.py` | `FeatureExtractor.extract_features()` | Raw text string, boolean flags | Dict of 38 numeric feature values | ML Model Feature Vector |
| **Random Forest Classifier** | `ml_service/model.py` | `PhishingMLModel.predict_with_shap()` | 38-dimensional float numpy array | Dict: `prediction`, `confidence`, `top_risk_indicators` | Explainability & Prompt Synthesizer |
| **SHAP Explainer** | `ml_service/model.py` | `shap.TreeExplainer(self.model)` | 38-dimensional float array | `shap_values` array (contributions to class 1) | `top_risk_indicators` list |
| **Prompt Synthesizer** | `ml_service/llm_explainer.py` | `generate_explanation()` | Formatted `feedback_payload` dict | Structured plain-text prompt | Ollama `/api/generate` |
| **LLM Inference** | Ollama daemon (`qwen3.5:2b`) | `/api/generate` | JSON `{model, prompt, stream, think, options}` | JSON `{response, done, done_reason, ...}` | LLM Explainer return value |

---

## 3. Data Flow & Transformation Pipeline

1. **User Submission**: The student submits a decision (0=Legitimate, 1=Phishing) and qualitative reasoning text via the web interface.
2. **Payload Dispatch**: Laravel validates the payload, fetches the canonical scenario from MySQL (`scenarios` table), and dispatches an HTTP POST request to `http://127.0.0.1:8001/adaptive/evaluate`.
3. **Modality Conditioning**: FastAPI inspects `scenario_type`:
   - `URL / Web Phishing`: Computes 28 URL structural and lexical features (zeroes the 10 text features).
   - `Email Phishing`: Parses header, sender, body length, urgency words, and target URL.
   - `SMS Phishing`: Parses SMS length, capitalization ratio, urgency words, and embedded link structure.
4. **Random Forest Inference**: The 38-feature vector is passed to `phishing_model.pkl`. The model computes class probabilities and the predicted label.
5. **SHAP Feature Attribution**: `TreeExplainer` calculates exact marginal feature contributions. The system extracts the top 5 positive contributors toward the phishing class.
6. **Ground Truth Calibration**: In practice mode (`app.py` line 165), `ground_truth_prediction` replaces the raw ML verdict for student grading. If the ML model erred, a specific "Model Misclassification" misconception is created.
7. **Prompt Construction**: The payload is assembled with tagged markdown blocks (`[MODEL ANALYSIS]`, `[MODEL-VERIFIED RISK SIGNALS]`, `[STUDENT RESPONSE]`, `[DIAGNOSED MISCONCEPTIONS]`, `[TARGETED PRACTICE]`).
8. **LLM Inference**: The prompt is dispatched to Ollama `qwen3.5:2b`.
9. **UI Presentation**: The resulting natural language explanation, along with SHAP indicator bars and risk scores, is rendered on the student's result page.
