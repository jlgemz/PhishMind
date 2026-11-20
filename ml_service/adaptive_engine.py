
from typing import Dict, List
import re


MISCONCEPTIONS: Dict[str, Dict] = {
    "M01": {
        "name": "HTTPS means safe",
        "pattern": r"https|ssl|lock icon|padlock|certificate",
        "description": (
            "Assumes any HTTPS URL with a lock icon is safe. Phishing sites can "
            "obtain free SSL certificates, so HTTPS alone is not proof of legitimacy."
        ),
    },
    "M02": {
        "name": "Short URLs are safe",
        "pattern": r"short|bit\.ly|t\.co|goo\.gl|tinyurl|shortened",
        "description": (
            "Assumes short URLs are inherently safe. Attackers commonly use "
            "URL shorteners to hide malicious destinations."
        ),
    },
    "M03": {
        "name": "Brand domain confusion",
        "pattern": r"official|brand|looks the same|similar|same as|trusted name",
        "description": (
            "Judges legitimacy only by visual similarity to a brand. "
            "Lookalike domains (paypa1.com, faceb00k.com) are commonly used."
        ),
    },
    "M04": {
        "name": "No URL means safe",
        "pattern": r"no link|no url|hasn't a link|without a link|no url present",
        "description": (
            "Assumes that the absence of a URL means no phishing risk, "
            "ignoring attachments, QR codes, or reply-based lures."
        ),
    },
    "M05": {
        "name": "Ignores urgency cues",
        "pattern": r"urgent|immediately|right away|otherwise|expires|account suspended",
        "description": (
            "Fails to treat urgency language (\"account suspended\", "
            "\"verify now\") as a social-engineering red flag."
        ),
    },
    "M06": {
        "name": "IP address misjudgment",
        "pattern": r"\bip\b|numeric address|\b\d{1,3}(\.\d{1,3}){3}\b",
        "description": (
            "Lacks vigilance around links that use raw IP addresses, "
            "which bypass domain reputation checks."
        ),
    },
}


FEATURE_NAME_MAP: Dict[str, str] = {
    "url_length": "URL length",
    "domain_length": "Domain length",
    "path_length": "Path length",
    "query_length": "Query length",
    "num_dots": "Number of dots",
    "num_hyphens": "Number of hyphens",
    "num_underscores": "Number of underscores",
    "num_slashes": "Number of slashes",
    "num_question_marks": "Number of question marks",
    "num_equal_signs": "Number of '=' signs",
    "num_at_signs": "Number of @ symbols",
    "num_percent_signs": "Number of % symbols",
    "num_digits": "Digit count",
    "has_https": "Uses HTTPS",
    "has_ip": "Uses raw IP address",
    "has_suspicious_keyword": "Suspicious keyword",
    "has_suspicious_tld": "Suspicious TLD",
    "subdomain_count": "Subdomain count",
    "domain_entropy": "Domain entropy",
    "has_query_string": "Has query string",
    "has_fragment": "Has URL fragment",
    "domain_age_days": "Domain age (days)",
    "has_brand_spoofing": "Brand-spoofing signal",
    "digit_ratio": "Digit ratio",
    "special_char_ratio": "Special-char ratio",
    "has_http": "Uses HTTP (insecure)",
    "has_www": "Contains 'www'",
}


PRACTICE_MAP: Dict[str, str] = {
    "M01": "Practice spotting phishing domains behind free SSL certificates. Compare HTTPS status vs. domain trust.",
    "M02": "Practice expanding short URLs and inspecting the final landing domain.",
    "M03": "Practice character-level comparison of lookalike domains (paypa1.com vs paypal.com).",
    "M04": "Practice reviewing phishing emails with no links but with attachments or reply lures.",
    "M05": "Practice labeling urgency language and separating benign notices from pressure tactics.",
    "M06": "Practice identifying raw-IP links and explaining why they are risky.",
    "M_UNKNOWN": "Re-inspect each URL component (protocol, domain, path, query) individually.",
}


class MisconceptionEngine:
    """Steps 3B / 4B: detect misconceptions from a student's reasoning."""

    def analyze_reasoning(self, student_reasoning: str, ml_result: Dict) -> List[Dict]:
        matched: List[Dict] = []
        text = student_reasoning.lower()

        for code, info in MISCONCEPTIONS.items():
            if re.search(info["pattern"], text, re.IGNORECASE):
                matched.append(
                    {"code": code, "name": info["name"], "description": info["description"]}
                )

        # Fallback: if the student reasoning disagrees with the model but no
        # misconception pattern matched, flag it as an unclassified gap.
        if not matched and _decision_mismatch(student_reasoning, ml_result):
            matched.append(
                {
                    "code": "M_UNKNOWN",
                    "name": "Unclassified reasoning gap",
                    "description": (
                        "Student reasoning diverges from the model's risk signals; "
                        "further analysis of the specific clues is needed."
                    ),
                }
            )
        return matched

    def build_feedback_payload(
        self,
        ml_result: Dict,
        student_decision: int,
        student_reasoning: str,
        misconceptions: List[Dict],
    ) -> Dict:
        """Assemble the Step 6 LLM payload and Step 7 practice suggestions."""

        verified_indicators = [
            {
                "feature": feat,
                "contribution": round(float(val), 4),
                "plain_name": FEATURE_NAME_MAP.get(feat, feat),
            }
            for feat, val in ml_result.get("top_risk_indicators", [])
        ]

        targeted_practice = [
            PRACTICE_MAP.get(m["code"], PRACTICE_MAP["M_UNKNOWN"])
            for m in misconceptions
        ]
        if not targeted_practice:
            targeted_practice = [
                "Continue practicing with similar URLs to reinforce correct patterns."
            ]

        return {
            "ml_prediction": ml_result["prediction"],
            "ml_confidence": round(ml_result["confidence"] * 100, 1),
            "student_decision": student_decision,
            "student_agrees_with_model": student_decision == ml_result["prediction"],
            "verified_risk_indicators": verified_indicators,
            "misconceptions": misconceptions,
            "targeted_practice": targeted_practice,
            "shap_summary": ml_result.get("shap_values", {}),
        }


def _decision_mismatch(reasoning: str, ml_result: Dict) -> bool:
    """Heuristic: student text contradicts the model verdict."""
    if ml_result["prediction"] == 1:
        return bool(
            re.search(r"\bsafe\b|\blegit\b|\bfine\b|\btrustworthy\b|\bnot phishing\b", reasoning, re.IGNORECASE)
        )
    return bool(
        re.search(r"\bphish\b|\bdangerous\b|\bsuspicious\b|\bscam\b|\bmalicious\b", reasoning, re.IGNORECASE)
    )