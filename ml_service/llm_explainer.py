import os
import re
import json
import requests

def _load_groq_api_key():
    key = os.getenv("GROQ_API_KEY")
    if key:
        return key.strip()
    
    # Try finding .env file in current or parent directories
    search_paths = [
        ".env",
        os.path.join(os.path.dirname(__file__), ".env"),
        os.path.join(os.path.dirname(__file__), "..", ".env"),
    ]
    for p in search_paths:
        if os.path.exists(p):
            try:
                with open(p, "r", encoding="utf-8") as f:
                    for line in f:
                        line = line.strip()
                        if line.startswith("GROQ_API_KEY="):
                            val = line.split("=", 1)[1].strip().strip('"\'')
                            if val:
                                return val
            except Exception:
                pass
    return None

GROQ_API_KEY = _load_groq_api_key()
GROQ_MODELS = ["qwen/qwen3.8-27b", "openai/gpt-oss-120b"]

raw_host = os.getenv("OLLAMA_HOST", "http://127.0.0.1:11501")
if not raw_host.startswith("http://") and not raw_host.startswith("https://"):
    raw_host = f"http://{raw_host}"
OLLAMA_HOST = raw_host.replace("0.0.0.0", "127.0.0.1").rstrip("/")
QWEN_MODEL = os.getenv("QWEN_MODEL", "qwen3.5:2b")


def _call_groq_api(prompt: str, json_mode: bool = True) -> dict | str | None:
    api_key = _load_groq_api_key() or GROQ_API_KEY
    if not api_key:
        return None

    headers = {
        "Authorization": f"Bearer {api_key}",
        "Content-Type": "application/json"
    }

    for model_name in GROQ_MODELS:
        payload = {
            "model": model_name,
            "messages": [
                {
                    "role": "system",
                    "content": (
                        "You are a friendly, expert cybersecurity educator explaining analysis results "
                        "in plain language that any student or user can easily understand. "
                        "Never use artificial branding like [GROQ AI] or [AI Model]."
                    )
                },
                {"role": "user", "content": prompt}
            ],
            "temperature": 0.25,
            "max_tokens": 600
        }
        if json_mode:
            payload["response_format"] = {"type": "json_object"}

        try:
            resp = requests.post(
                "https://api.groq.com/openai/v1/chat/completions",
                headers=headers,
                json=payload,
                timeout=8.0
            )
            if resp.status_code == 200:
                raw_text = resp.json()["choices"][0]["message"]["content"].strip()
                # Clean any stray branding mentions if any model hallucinated them
                raw_text = re.sub(r'\[GROQ\s*AI[^\]]*\]', '', raw_text, flags=re.IGNORECASE).strip()
                if json_mode:
                    try:
                        return json.loads(raw_text)
                    except json.JSONDecodeError:
                        return None
                return raw_text
            else:
                print(f"⚠️ Groq ({model_name}) returned status {resp.status_code}: {resp.text[:120]}")
        except Exception as e:
            print(f"⚠️ Groq API connection error ({model_name}): {e}")

    return None


def generate_adaptive_feedback(feedback_payload: dict) -> dict:
    """
    Evaluates practice scenario attempts with dynamic misconception analysis
    based on the user's submitted reasoning, and generates plain-language explanations
    and cybersecurity recommendations via Groq API.
    """
    content = feedback_payload.get("content", "")
    modality = feedback_payload.get("modality", "Unknown")
    raw_student_dec = feedback_payload.get("student_decision")
    raw_ground_truth = feedback_payload.get("ground_truth")
    student_is_phish = bool(raw_student_dec == 1 or raw_student_dec is True)
    ground_truth_is_phish = bool(raw_ground_truth == 1 or raw_ground_truth is True)
    is_correct = (student_is_phish == ground_truth_is_phish)
    student_decision = "Phishing" if student_is_phish else "Legitimate"
    ground_truth = "Phishing" if ground_truth_is_phish else "Legitimate"
    student_reasoning = feedback_payload.get("student_reasoning", "").strip()
    conf = feedback_payload.get("ml_confidence", 85)

    raw_indicators = feedback_payload.get("verified_risk_indicators", [])
    formatted_indicators = []
    for item in raw_indicators:
        if isinstance(item, dict):
            name = item.get("plain_name") or item.get("feature") or str(item)
            val = item.get("contribution") or item.get("value") or ""
            formatted_indicators.append(f"- {name} ({val})".strip())
        else:
            formatted_indicators.append(f"- {item}")
    indicators_str = "\n".join(formatted_indicators) or "- Standard feature profile"

    prompt = f"""You are evaluating a student in a cybersecurity practice lab.
Analyze the scenario, the student's answer, and especially their submitted reasoning to diagnose their exact misconception.

[SCENARIO]
Modality: {modality}
Content:
\"\"\"{content[:400]}\"\"\"
Ground Truth: {ground_truth}

[STUDENT ATTEMPT]
Student Answer: {student_decision}
Student Reasoning:
\"\"\"{student_reasoning}\"\"\"
Result: {'Correct' if is_correct else 'Incorrect (Needs Review)'}

[MODEL-VERIFIED EVIDENCE]
{indicators_str}

[TASK]
Respond ONLY with a valid JSON object in this exact format:
{{
  "explanation": "A warm, conversational plain-language explanation (under 120 words) explaining why this {modality} is {ground_truth.lower()} so the student grasps the core concept clearly.",
  "misconception": {{"name": "Specific misconception title diagnosed from what the student wrote", "description": "Detailed explanation of why their reasoning was flawed and what deceptive trap led them astray"}} if student was incorrect, or null if student was correct,
  "recommendation": "2-3 practical, actionable cybersecurity steps the user should take.",
  "targeted_practice": ["Actionable focus question for their next attempt", "Second question to test the concept"]
}}

Important rules:
1. If the student was incorrect, identify the specific misconception DIRECTLY from their reasoning (e.g. assuming HTTPS means safety, trusting familiar logos, falling for urgency, ignoring strange domains).
2. Do NOT use static or generic misconceptions. Tailor it to what they actually wrote.
3. NEVER include any branding like [GROQ AI] or similar prefixes."""

    groq_res = _call_groq_api(prompt, json_mode=True)
    if isinstance(groq_res, dict) and "explanation" in groq_res:
        misconceptions = []
        if not is_correct and groq_res.get("misconception"):
            m = groq_res["misconception"]
            if isinstance(m, dict) and m.get("name") and m.get("description"):
                rec_val = groq_res.get("recommendation")
                if isinstance(rec_val, list):
                    rec_str = "\n".join(f"• {r}" for r in rec_val)
                else:
                    rec_str = str(rec_val or "")
                m["recommendation"] = rec_str
                misconceptions.append(m)

        if not misconceptions and not is_correct:
            # Fallback dynamic misconception from reasoning if JSON missed it
            misconceptions.append(_diagnose_misconception_heuristic(student_reasoning, modality))

        rec = groq_res.get("recommendation", "")
        if isinstance(rec, list):
            rec = "\n".join(f"• {r}" for r in rec)

        practice = groq_res.get("targeted_practice", [])
        if not isinstance(practice, list) or not practice:
            practice = [
                f"How would you independently verify the authenticity of this {modality}?",
                "What structural indicators differ between legitimate and lookalike messages?"
            ]

        return {
            "explanation": groq_res.get("explanation", "").strip(),
            "misconceptions": misconceptions,
            "recommendation": rec.strip(),
            "targeted_practice": practice
        }

    # Fallback to local heuristic / Ollama
    return _fallback_adaptive_feedback(feedback_payload)


def generate_explanation(feedback_payload: dict) -> str:
    """
    Generates plain-language explanation for Check Content (/analyze) and general results.
    """
    content = feedback_payload.get("content", "")
    modality = feedback_payload.get("modality", "content")
    verdict = "Phishing" if feedback_payload.get("ml_prediction") == 1 else "Legitimate"
    conf = feedback_payload.get("ml_confidence", 85)

    indicators_str = "\n".join(
        f"- {i['plain_name']} ({i['contribution']})"
        for i in feedback_payload.get("verified_risk_indicators", [])
    ) or "- Standard feature profile"

    prompt = f"""You are an encouraging AI cybersecurity assistant explaining content analysis to a user in plain language.

[ANALYZED CONTENT]
Modality: {modality}
Content:
\"\"\"{content[:400]}\"\"\"

[VERDICT & EVIDENCE]
Classification: {verdict} (Confidence: {conf}%)
Signals Detected:
{indicators_str}

Explain in clear, conversational terms why this content was classified as {verdict.lower()}. 
Make it easy for any non-technical user to understand what signals made it safe or risky.
Keep it under 140 words. Do NOT include any branding or prefixes like [GROQ AI]."""

    groq_res = _call_groq_api(prompt, json_mode=False)
    if groq_res and len(groq_res) > 20:
        return groq_res.strip()

    # Fallback to rule-based plain explanation
    return _fallback_plain_explanation(feedback_payload)


def _diagnose_misconception_heuristic(reasoning: str, modality: str) -> dict:
    r = reasoning.lower()
    if any(k in r for k in ["https", "padlock", "lock", "ssl", "secure"]):
        return {
            "name": "Believing HTTPS Guarantees Authenticity",
            "description": "You noted that the link uses HTTPS and assumed it was safe. In reality, HTTPS only encrypts communication—attackers can easily set up HTTPS on malicious sites. Always verify the actual domain name."
        }
    elif any(k in r for k in ["urgent", "hurry", "immediately", "deadline", "suspended", "expire", "action required"]):
        return {
            "name": "Artificial Urgency Compliance",
            "description": "The message used urgent pressure to trigger panic. Scammers rely on artificial time pressure to rush users into acting before verifying the request."
        }
    elif any(k in r for k in ["logo", "official", "formal", "looks like", "signature", "clean", "grammar"]):
        return {
            "name": "Superficial Visual / Professional Trust",
            "description": "You trusted the message because the formatting, tone, or branding looked formal. Fraudsters routinely copy official logos, signatures, and university layouts."
        }
    elif any(k in r for k in ["short", "bitly", "tinyurl", "link", "qr"]):
        return {
            "name": "Unverified Redirection Trust",
            "description": "You interacted with a shortened or obscured link without previewing its destination. Always inspect where the destination URL actually leads before trusting it."
        }
    elif any(k in r for k in ["otp", "code", "pin", "verify account", "password"]):
        return {
            "name": "Credential Request Blind Spot",
            "description": "The content prompted for sensitive credentials or security codes. Legitimate organizations never request your password, PIN, or OTP through unprompted messages."
        }
    else:
        return {
            "name": f"Deceptive {modality} Pattern Recognition",
            "description": f"The {modality.lower()} content contained deceptive cues that mimicked authentic communications. Examine sender addresses, link spellings, and unexpected calls-to-action carefully."
        }


def _fallback_adaptive_feedback(payload: dict) -> dict:
    is_correct = bool(payload.get("student_agrees_with_model", False))
    reasoning = payload.get("student_reasoning", "")
    modality = payload.get("modality", "URL")
    ground_truth = "Phishing" if payload.get("ground_truth") == 1 else "Legitimate"

    misconceptions = []
    if not is_correct:
        misconceptions.append(_diagnose_misconception_heuristic(reasoning, modality))

    rec = (
        "• Never click unknown links or disclose sensitive credentials like passwords or OTPs.\n"
        "• Independently verify unexpected requests through official contact numbers or direct portal logins.\n"
        "• Report suspicious correspondence to your security administrator immediately."
    ) if ground_truth == "Phishing" else (
        "• Authentic correspondence. Always verify sender address before taking sensitive actions.\n"
        "• Remember that authentic institutions never ask for your password or PIN via SMS or unprompted email."
    )

    practice = [
        f"In the next example, how can you independently verify who controls this {modality.lower()}?",
        "Are you relying on one familiar clue (like a logo or HTTPS) as proof of legitimacy?"
    ]

    plain_exp = _fallback_plain_explanation(payload)

    return {
        "explanation": plain_exp,
        "misconceptions": misconceptions,
        "recommendation": rec,
        "targeted_practice": practice
    }


def _fallback_plain_explanation(payload: dict) -> str:
    is_phish = (payload.get('ml_prediction') == 1) or (payload.get('ground_truth') == 1)
    conf = payload.get('ml_confidence', 85)
    indicators = payload.get('verified_risk_indicators', [])
    modality = payload.get('modality', 'content')

    if is_phish:
        lines = [
            f"Here is what our AI detected: This {modality} displays distinct indicators associated with phishing attempts (AI Confidence: {conf}%). "
            f"The analysis flagged the following warning factors:",
        ]
        for ind in indicators[:3]:
            lines.append(f"• {ind['plain_name']}: {ind['contribution']} contribution to risk")
        lines.append("\nThis content uses deceptive techniques to prompt sensitive action under false pretenses.")
    else:
        lines = [
            f"Here is what our AI detected: This {modality} appears to be legitimate based on learned structural and behavioral patterns (AI Confidence: {conf}%). "
            f"No malicious lures or credential harvesting requests were identified:",
        ]
        for ind in indicators[:3]:
            lines.append(f"• {ind['plain_name']}: {ind['contribution']} safety alignment")
        lines.append("\nThe communication matches standard operational patterns without suspicious redirect triggers.")

    return "\n".join(lines)


def _format_indicators(indicators):
    if not indicators:
        return "No strong risk signals detected."
    return "\n".join(
        f"- {i['plain_name']} ({i['contribution']})" for i in indicators
    )


def _fallback_explanation(payload, error):
    return _fallback_plain_explanation(payload)
