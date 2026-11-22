import os
import requests

raw_host = os.getenv("OLLAMA_HOST", "http://127.0.0.1:11501")
if not raw_host.startswith("http://") and not raw_host.startswith("https://"):
    raw_host = f"http://{raw_host}"
OLLAMA_HOST = raw_host.replace("0.0.0.0", "127.0.0.1").rstrip("/")
QWEN_MODEL = os.getenv("QWEN_MODEL", "qwen3.5:2b")

SYSTEM_PROMPT = """You are a patient, encouraging AI cybersecurity coach explaining analysis results directly to a student or user.

Follow this exact structure:
1. Plain-Language Explanation:
Explain clearly in simple, conversational terms why this content was classified as legitimate or phishing. Reference the specific indicators detected (e.g. domain structure, urgent tone, link destinations, or absence of threats) without using dense machine learning jargon. Speak directly to the user ("Here is what our AI detected: ...").

2. Cybersecurity Recommendation:
Provide an explicit section titled "Cybersecurity Recommendation:" containing 1-2 practical, actionable steps the user should take (e.g., how to verify sender authenticity, what to avoid clicking, or best security hygiene practices).

Keep your response friendly, clear, under 200 words, and directly applicable to the scenario's modality."""


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
            timeout=1.5,
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
    is_phish = (payload.get('ml_prediction') == 1)
    conf = payload.get('ml_confidence', 85)
    indicators = payload.get('verified_risk_indicators', [])
    modality = payload.get('modality', 'content')

    if is_phish:
        lines = [
            f"Here is what our AI detected: This {modality} displays distinct indicators associated with phishing attempts (AI Confidence: {conf}%). "
            f"The analysis flagged the following risk factors:",
        ]
        for ind in indicators[:3]:
            lines.append(f"• {ind['plain_name']}: {ind['contribution']} contribution to risk")
        
        lines.append("\nCybersecurity Recommendation:")
        lines.append("• Do not click any links, open attachments, or input sensitive credentials.")
        lines.append("• Always verify unexpected or urgent requests through a trusted, independent channel (such as an official phone number or website).")
        lines.append("• Report this message to your security team or service provider immediately.")
    else:
        lines = [
            f"Here is what our AI detected: This {modality} appears to be legitimate based on learned structural and behavioral patterns (AI Confidence: {conf}%). "
            f"No malicious lures or credential harvesting requests were identified:",
        ]
        for ind in indicators[:3]:
            lines.append(f"• {ind['plain_name']}: {ind['contribution']} safety alignment")
        
        lines.append("\nCybersecurity Recommendation:")
        lines.append("• You can proceed with standard precautions, but remain cautious of unexpected requests.")
        lines.append("• Remember that authentic institutions never ask for your password, PIN, or one-time code over SMS or unsolicited email.")
        lines.append("• Double-check the sender's verified address whenever sensitive account actions are requested.")

    return "\n".join(lines)