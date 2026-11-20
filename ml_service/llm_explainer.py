import os
import requests

raw_host = os.getenv("OLLAMA_HOST", "http://127.0.0.1:11501")
if not raw_host.startswith("http://") and not raw_host.startswith("https://"):
    raw_host = f"http://{raw_host}"
OLLAMA_HOST = raw_host.replace("0.0.0.0", "127.0.0.1").rstrip("/")
QWEN_MODEL = os.getenv("QWEN_MODEL", "qwen3.5:2b")

SYSTEM_PROMPT = """You are a patient cybersecurity coach.
Your job:
1. Explain in plain language why the content may be a phishing risk, grounded in the model's detected signals.
2. If the student's judgment differs from the model, gently point out the likely misconception and the correct basis for judgment.
3. Give 1–2 targeted practice suggestions for each diagnosed misconception.
4. Use a friendly, concrete tone. Avoid jargon walls. Keep the reply under 200 words."""


def generate_explanation(feedback_payload: dict) -> str:
    prompt = f"""{SYSTEM_PROMPT}

[MODEL ANALYSIS]
- Phishing probability: {feedback_payload['ml_confidence']}%
- Model verdict: {'phishing' if feedback_payload['ml_prediction'] == 1 else 'legitimate'}

[MODEL-VERIFIED RISK SIGNALS]
{_format_indicators(feedback_payload['verified_risk_indicators'])}

[STUDENT RESPONSE]
- Student verdict: {'phishing' if feedback_payload['student_decision'] == 1 else 'legitimate'}
- Agrees with model: {'yes' if feedback_payload['student_agrees_with_model'] else 'no'}

[DIAGNOSED MISCONCEPTIONS]
{_format_misconceptions(feedback_payload['misconceptions'])}

[TARGETED PRACTICE]
{_format_practice(feedback_payload['targeted_practice'])}

Generate the explanatory feedback for the student."""

    try:
        print(f"🤖 Asking Ollama ({QWEN_MODEL}) for explanation...")
        resp = requests.post(
            f"{OLLAMA_HOST}/api/generate",
            json={
                "model": QWEN_MODEL,
                "prompt": prompt,
                "stream": False,
                "think": False,
                "options": {"temperature": 0.4, "num_predict": 400},
            },
            timeout=20,
        )
        resp.raise_for_status()
        data = resp.json()
        explanation = (data.get("response") or data.get("thinking") or "").strip()

        if not explanation or len(explanation) < 20:
            print(f"⚠️ Ollama returned empty/short response. Using fallback.")
            return _fallback_explanation(feedback_payload, "Empty response")

        print(f"✅ Ollama responded successfully ({len(explanation)} chars)")
        return explanation

    except Exception as e:
        print(f"❌ Ollama Failed: {e}")
        return _fallback_explanation(feedback_payload, str(e))


def _format_indicators(indicators):
    if not indicators:
        return "No strong risk signals detected."
    return "\n".join(
        f"- {i['plain_name']} (contribution {i['contribution']})" for i in indicators
    )


def _format_misconceptions(misconceptions):
    if not misconceptions:
        return "No obvious misconceptions detected."
    return "\n".join(f"- {m['name']}: {m['description']}" for m in misconceptions)


def _format_practice(practice_list):
    if not practice_list:
        return "Continue practicing similar samples."
    return "\n".join(f"- {p}" for p in practice_list)


def _fallback_explanation(payload, error):
    verdict = "phishing" if payload['ml_prediction'] == 1 else "legitimate"
    return (
        f"The Random Forest model classifies this content as {verdict} "
        f"with {payload['ml_confidence']}% confidence. "
        f"Key risk indicators detected:\n"
        f"{_format_indicators(payload['verified_risk_indicators'])}\n\n"
        f"Recommendations:\n{_format_practice(payload['targeted_practice'])}"
    )