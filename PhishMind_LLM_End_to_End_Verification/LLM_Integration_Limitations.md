# PhishMind LLM Explanation Component: Identified Limitations & Engineering Recommendations

**Document ID:** PHISHMIND-LIM-2026-01  
**Classification:** SQA / Machine Learning Engineering Audit Report  
**Target System:** PhishMind Cybersecurity Awareness Platform  

---

## 1. Architectural & Configuration Defects

### Defect 1: Missing URL Protocol Scheme in `OLLAMA_HOST`
- **Severity:** HIGH (Blocks Live LLM Inference in As-Is Configuration)
- **Root Cause:** The environment variable `OLLAMA_HOST` is set to `"0.0.0.0:11501"` in the environment. In `ml_service/llm_explainer.py`, the endpoint URL is assembled via `f"{OLLAMA_HOST}/api/generate"`, resulting in `"0.0.0.0:11501/api/generate"`. Because `requests` requires an explicit HTTP/HTTPS scheme, it raises:
  ```text
  requests.exceptions.InvalidSchema: No connection adapters were found for '0.0.0.0:11501/api/generate'
  ```
- **Consequence:** The exception handler at line 59 intercepts this error and immediately redirects the execution to `_fallback_explanation()`. As a result, the live LLM is never invoked in the unpatched environment.
- **Recommended Remediation:** Normalize the host string in `llm_explainer.py`:
  ```python
  raw_host = os.getenv("OLLAMA_HOST", "http://127.0.0.1:11501")
  if not raw_host.startswith("http://") and not raw_host.startswith("https://"):
      OLLAMA_HOST = f"http://{raw_host}"
  else:
      OLLAMA_HOST = raw_host
  OLLAMA_HOST = OLLAMA_HOST.replace("0.0.0.0", "127.0.0.1")
  ```

---

### Defect 2: Qwen3.5:2B Reasoning Token Starvation
- **Severity:** HIGH (Silent Fallback Trigger)
- **Root Cause:** `qwen3.5:2b` in Ollama is a reasoning model featuring chain-of-thought capabilities (`'thinking'`). Ollama routes tokens to the `thinking` output stream until the thought process is complete. In `llm_explainer.py`:
  ```python
  "options": {"temperature": 0.4, "num_predict": 400}
  ```
  Because the internal thinking process frequently consumes 350–450 tokens, the request terminates due to `done_reason: 'length'` before emitting text to the `response` field. Ollama returns `{"response": "", "thinking": "...", "done_reason": "length"}`. At line 52:
  ```python
  if not explanation or len(explanation) < 20:
      return _fallback_explanation(feedback_payload, "Empty response")
  ```
  The empty response string causes the explainer to silently discard the inference and deliver the template fallback.
- **Recommended Remediation:** Either disable thinking tokens for rapid real-time coaching or expand the token limit:
  ```python
  # Option A: Bypass thinking for instant sub-3-second responses (Recommended for web coaching)
  json={
      "model": QWEN_MODEL,
      "prompt": prompt,
      "stream": False,
      "think": False,
      "options": {"num_gpu": 0, "temperature": 0.4, "num_predict": 300}
  }
  
  # Option B: Increase num_predict to 1000 to allow CoT to complete
  ```

---

### Defect 3: Host GPU Driver CUDA PTX Incompatibility
- **Severity:** MEDIUM (Host Environment Dependent)
- **Root Cause:** When Ollama attempts CUDA runner offload on the current Windows host, NVIDIA driver mismatch triggers:
  ```text
  CUDA error: the provided PTX was compiled with an unsupported toolchain (Exit code 0xc0000409)
  ```
- **Consequence:** Ollama crashes with an HTTP 500 Server Error if GPU execution is attempted.
- **Recommended Remediation:** Pass `"options": {"num_gpu": 0}` in all Ollama API calls from Python to ensure stable, reliable CPU inference across any host environment.

---

### Defect 4: Single Model File Overwrite (`phishing_model.pkl`)
- **Severity:** MEDIUM (Multi-Modality Collision)
- **Root Cause:** In `ml_service/app.py`, the training endpoints `/admin/train-url`, `/admin/train-email`, and `/admin/train-sms` all persist their trained Random Forest models to the same identical file path: `ml_service/phishing_model.pkl`.
- **Consequence:** Running the SMS training script overwrites the model previously trained on URL or Email datasets. The system lacks concurrent modality-specific model persistence.
- **Recommended Remediation:** Segregate the artifact storage into three distinct model files:
  - `ml_service/models/url_rf_model.pkl`
  - `ml_service/models/email_rf_model.pkl`
  - `ml_service/models/sms_rf_model.pkl`
  And route requests in `app.py` based on `scenario_type`.

---

## 2. Model & Explainability Design Observations

1. **Ground Truth Override in Student Feedback:**
   In `ml_service/app.py` line 165, the system assigns `ground_truth_prediction = 1 if req.is_phishing else 0` and passes this to the feedback payload instead of `ml_prediction`.
   *Auditor Evaluation:* This is a sound pedagogical design choice for an adaptive awareness system—it prevents students from being mis-coached when the underlying machine learning model produces a false positive or false negative. If the model misclassifies, the system generates an explicit "Model Misclassification" alert advising the student that they correctly identified a pattern that the AI missed.

2. **SHAP Feature Thresholding:**
   The explainer only extracts features where `shap_value > 0` for class 1 (pushing toward phishing). For legitimate samples where all features have negative SHAP values, `top_risk_indicators` can be empty.
   *Auditor Evaluation:* The fallback line in `app.py` line 178 (`[{"feature": "No strong risk indicators", "value": "0.00"}]`) correctly prevents index errors and provides clear negative evidence to both the student and the LLM.
