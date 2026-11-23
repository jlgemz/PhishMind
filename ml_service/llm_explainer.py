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


def clean_plain_language_text(text: str) -> str:
    """
    Ensures plain-language explanation contains simple words and NO numbers, percentages,
    or statistical symbols.
    """
    if not text:
        return ""
    # Remove AI branding if any slipped in
    t = re.sub(r'\[GROQ\s*AI[^\]]*\]', '', text, flags=re.IGNORECASE)
    t = re.sub(r'\[AI\s*Model[^\]]*\]', '', t, flags=re.IGNORECASE)
    
    # Remove SHAP-style weights like (+0.32) or (-0.15)
    t = re.sub(r'\([+-]?\d+(\.\d+)?\)', '', t)
    # Remove percentage figures like 85% or 95.2%
    t = re.sub(r'\b\d+(\.\d+)?%\b', 'high', t)
    # Remove standalone decimal numbers
    t = re.sub(r'\b\d+\.\d+\b', '', t)
    # Remove feature count references like "(39 Features)" or "39 features"
    t = re.sub(r'\b\d+\s*features?\b', 'multiple security signals', t, flags=re.IGNORECASE)
    # Remove standalone large numbers (like 2026, 100, etc.) if they were output as statistics
    t = re.sub(r'\b(confidence|probability|score)\s*of\s*\d+\b', r'\1', t, flags=re.IGNORECASE)
    
    # Clean double spaces or broken punctuation
    t = re.sub(r'\s{2,}', ' ', t)
    t = re.sub(r'\s+([.,!?])', r'\1', t)
    return t.strip()


def _format_indicators_no_numbers(raw_indicators) -> str:
    """Format indicators for LLM context without numerical weights."""
    formatted = []
    for item in raw_indicators:
        if isinstance(item, dict):
            name = item.get("plain_name") or item.get("feature") or str(item)
            formatted.append(f"- {name}")
        else:
            cleaned = re.sub(r'\s*\([+-]?\d+\.?\d*\)', '', str(item)).strip()
            formatted.append(f"- {cleaned}")
    return "\n".join(formatted) or "- Standard communication profile"


def _get_modality_recommendation(modality: str, is_phishing: bool) -> str:
    """Returns tailored cybersecurity recommendations strictly matching the modality."""
    m = (modality or "").lower()
    if "sms" in m or "text" in m or "message" in m:
        if is_phishing:
            return (
                "• Never tap on links or short URLs sent in unsolicited text messages.\n"
                "• Never share one-time passcodes (OTP), account passwords, or banking PINs over SMS—legitimate institutions never request them by text.\n"
                "• Forward spam or suspicious text messages to your phone carrier spam service (such as 7726) and check your account status directly inside the official mobile app."
            )
        else:
            return (
                "• This text message appears authentic. However, always remember that genuine organizations will never ask for your password, PIN, or security codes over SMS.\n"
                "• If a text message ever instructs you to take urgent account action, independently open the provider's official mobile application instead of following text links."
            )
    elif "email" in m:
        if is_phishing:
            return (
                "• Check the sender's full email address and domain name in the message header, not just the displayed name.\n"
                "• Avoid clicking links or opening unexpected attachments from unsolicited or urgent emails.\n"
                "• Forward the suspicious email to your organization's IT security team, and verify any urgent request by contacting the sender on a known official phone number."
            )
        else:
            return (
                "• This email appears authentic and follows standard communication formats.\n"
                "• Always verify the sender's complete email address if an unexpected request or attachment arrives, and never share credentials over email."
            )
    else:  # URL / Web
        if is_phishing:
            return (
                "• Carefully examine the full address in your browser address bar for misspelled names, extra subdomains, or deceptive extensions like .xyz or .top.\n"
                "• Never enter your login password, payment cards, or sensitive personal details on an unverified web page.\n"
                "• Navigate to trusted websites by typing their known official address directly into the browser address bar or using saved bookmarks."
            )
        else:
            return (
                "• This web address matches expected legitimate patterns. Always verify that the domain name is spelled correctly before logging in.\n"
                "• Remember that having HTTPS or a lock icon only encrypts the connection—it does not automatically guarantee who owns the website."
            )


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
    using simple words (zero numbers) and modality-specific cybersecurity recommendations via Groq API.
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

    raw_indicators = feedback_payload.get("verified_risk_indicators", [])
    indicators_str = _format_indicators_no_numbers(raw_indicators)

    prompt = f"""You are a friendly cybersecurity educator evaluating a student in a practice lab.
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

[OBSERVED SIGNALS]
{indicators_str}

[TASK]
Respond ONLY with a valid JSON object in this exact format:
{{
  "explanation": "A friendly, simple explanation (under 100 words) using ONLY simple words explaining why this {modality} is {ground_truth.lower()}.",
  "misconception": {{"name": "Misconception title diagnosed from what the student wrote", "description": "Why their reasoning was flawed and what deceptive trap led them astray"}} if student was incorrect, or null if student was correct,
  "recommendation": "2-3 bulleted cybersecurity action steps specifically tailored to handle {modality} threats.",
  "targeted_practice": ["Actionable focus question for their next attempt in this {modality}", "Second question to test the concept"]
}}

CRITICAL RULES:
1. ONLY SIMPLE WORDS AND ZERO NUMBERS: The explanation must use simple, everyday language that any student can easily grasp. DO NOT INCLUDE ANY NUMBERS, percentages, scores, decimal values, counts, or statistical weights anywhere in the explanation. Explain entirely in plain words.
2. MODALITY-SPECIFIC CYBERSECURITY RECOMMENDATION:
   - The recommendation must be specifically and directly tailored to the scenario's modality:
     * If URL / Web: Focus on inspecting the browser address bar for lookalike domains or deceptive extensions (.xyz, .top), never typing passwords on unverified web pages, and bookmarking official campus links.
     * If Email: Focus on checking sender email headers, never opening unexpected attachments, forwarding suspicious emails to the IT security office, and verifying requests via an official phone directory.
     * If SMS / Text: Focus on never tapping embedded links in text messages, never sharing OTPs or banking codes, forwarding smishing texts to carrier spam reporting (7726), and verifying directly in the official mobile app.
   Format with bullet points (•).
3. DYNAMIC MISCONCEPTION: If the student was incorrect, diagnose their specific misconception directly from what they wrote in their reasoning (e.g. assuming HTTPS equals safety, trusting a formal tone, falling for urgency, or overlooking unverified links).
4. NO AI BRANDING: Never include any branding or labels like [GROQ AI] or model names."""

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
                m["recommendation"] = rec_str or _get_modality_recommendation(modality, ground_truth_is_phish)
                misconceptions.append(m)

        if not misconceptions and not is_correct:
            misconceptions.append(_diagnose_misconception_heuristic(student_reasoning, modality))

        rec = groq_res.get("recommendation", "")
        if isinstance(rec, list):
            rec = "\n".join(f"• {r}" for r in rec)
        if not rec or len(rec.strip()) < 10:
            rec = _get_modality_recommendation(modality, ground_truth_is_phish)

        practice = groq_res.get("targeted_practice", [])
        if not isinstance(practice, list) or not practice:
            practice = [
                f"How would you independently verify the authenticity of this {modality}?",
                "What structural indicators differ between legitimate and lookalike messages?"
            ]

        clean_exp = clean_plain_language_text(groq_res.get("explanation", ""))

        return {
            "explanation": clean_exp,
            "misconceptions": misconceptions,
            "recommendation": rec.strip(),
            "targeted_practice": practice
        }

    # Fallback to local heuristic
    return _fallback_adaptive_feedback(feedback_payload)


def generate_explanation_and_recommendation(feedback_payload: dict) -> dict:
    """
    Generates plain-language explanation (simple words, zero numbers) AND
    modality-specific cybersecurity recommendations for Check Content (/analyze).
    """
    content = feedback_payload.get("content", "")
    modality = feedback_payload.get("modality", "content")
    verdict = "Phishing" if feedback_payload.get("ml_prediction") == 1 else "Legitimate"
    is_phish = (feedback_payload.get("ml_prediction") == 1)

    indicators_str = _format_indicators_no_numbers(feedback_payload.get("verified_risk_indicators", []))

    prompt = f"""You are a friendly cybersecurity educator explaining content analysis to a regular user in plain language.

[ANALYZED CONTENT]
Modality: {modality}
Content:
\"\"\"{content[:400]}\"\"\"

[VERDICT & EVIDENCE]
Classification: {verdict}
Observed Signals:
{indicators_str}

Respond ONLY with a valid JSON object in this exact format:
{{
  "explanation": "A simple, non-technical plain-language explanation of why this was identified as {verdict.lower()}.",
  "recommendation": "2-3 bulleted cybersecurity steps specifically tailored to handle {modality} phishing threats."
}}

CRITICAL INSTRUCTIONS:
1. ONLY SIMPLE WORDS AND ZERO NUMBERS: Explain entirely in simple words. DO NOT INCLUDE ANY NUMBERS, percentages, scores, decimal values, counts, or statistical weights anywhere in the explanation.
2. MODALITY-SPECIFIC CYBERSECURITY RECOMMENDATION:
   - If {modality} is URL / Web: Focus on inspecting the browser address bar for subtle misspellings or odd extensions, avoiding entering passwords on unverified sites, and typing trusted addresses directly.
   - If {modality} is Email: Focus on checking sender email headers, not opening unexpected attachments, and forwarding suspicious emails to IT security.
   - If {modality} is SMS: Focus on never tapping links in unsolicited text messages, never sharing OTPs or banking codes, and reporting smishing to carrier spam (7726).
   Format with bullet points (•).
3. NO AI BRANDING: Never include any branding or labels like [GROQ AI] or model names."""

    groq_res = _call_groq_api(prompt, json_mode=True)
    if isinstance(groq_res, dict) and "explanation" in groq_res:
        exp = clean_plain_language_text(groq_res.get("explanation", ""))
        rec = groq_res.get("recommendation", "")
        if isinstance(rec, list):
            rec = "\n".join(f"• {r}" for r in rec)
        return {
            "explanation": exp,
            "recommendation": rec.strip() or _get_modality_recommendation(modality, is_phish)
        }

    return {
        "explanation": clean_plain_language_text(_fallback_plain_explanation(feedback_payload)),
        "recommendation": _get_modality_recommendation(modality, is_phish)
    }


def generate_explanation(feedback_payload: dict) -> str:
    """
    Generates plain-language explanation for Check Content (/analyze) and general results.
    Strictly uses simple words and zero numbers.
    """
    res = generate_explanation_and_recommendation(feedback_payload)
    return res.get("explanation", "")


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

    rec = _get_modality_recommendation(modality, ground_truth == "Phishing")

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
    modality = payload.get('modality', 'content')

    if is_phish:
        return (
            f"This {modality} was identified as suspicious because it displays warning signs commonly found in phishing scams. "
            f"The message attempts to create artificial urgency, directs you to an unverified destination, or requests sensitive details under false pretenses. "
            f"Always treat unexpected requests with extreme caution."
        )
    else:
        return (
            f"This {modality} appears legitimate based on standard communication patterns. "
            f"It does not contain suspicious redirection links, credential harvesting lures, or alarming pressure tactics. "
            f"Even so, always remain attentive before sharing any private information."
        )


def _format_indicators(indicators):
    return _format_indicators_no_numbers(indicators)


def _fallback_explanation(payload, error):
    return _fallback_plain_explanation(payload)
