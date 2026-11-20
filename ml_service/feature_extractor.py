import re
import urllib.parse
import tldextract
import math
import os  # 👈 ADDED: Required for file extension parsing
from collections import Counter


class FeatureExtractor:
    def __init__(self):
        self.suspicious_keywords = [
            'login', 'verify', 'update', 'confirm', 'secure', 'banking',
            'account', 'password', 'credential', 'signin', 'authenticate',
            'validate', 'recover', 'reset', 'activate', 'authorize',
            'verification', 'security', 'alert', 'warning', 'suspicious',
            'paypal', 'amazon', 'google', 'facebook', 'microsoft', 'apple',
            'suspended', 'restore', 'urgent', 'limited', 'action', 'now', 'billing'
        ]
        self.suspicious_tlds = [
            '.tk', '.ml', '.ga', '.cf', '.top', '.xyz', '.work',
            '.date', '.download', '.bid', '.win', '.club', '.online',
            '.site', '.space', '.tech', '.store', '.shop', '.live'
        ]
        self.urgency_words = [
            'urgent', 'immediate', 'action required', 'verify your',
            'confirm your', 'suspended', 'locked', 'security alert',
            'unauthorized access', 'account compromised', 'update your',
            'reactivate', 'pending verification', 'important notice',
            'final warning', 'limited time', 'expires today', '24 hours',
            'permanent', 'terminated'
        ]
        self.sensitive_keywords = [
            'social security', 'ssn', 'date of birth', 'dob', 'cvv',
            'card number', 'expiry date', 'online banking password',
            'pin', 'mother\'s maiden', 'passport'
        ]
        self.sms_phishing_keywords = [
            "win", "winner", "won", "prize", "claim", "reward", "free",
            "congrat", "selected", "urgent", "verify", "click", "act now",
            "limited time", "expires", "suspended", "locked", "confirm",
            "update your", "bank", "account", "pin", "otp", "code",
            "delivery", "package", "parcel", "customs", "tax",
        ]
        self.threat_words = [
            "terminate", "suspend", "legal action", "close your account",
            "blocked", "locked", "deactivated", "unauthorized", "penalty"
        ]
        self.attachment_hints = [
            "[file]", "attachment", ".pdf", ".zip", ".exe", ".docx", ".xls", ".html"
        ]

    def get_feature_names(self):
        return [
            'url_length', 'domain_length', 'path_length', 'query_length',
            'num_dots', 'num_hyphens', 'num_underscores', 'num_slashes',
            'num_question_marks', 'num_equal_signs', 'num_at_signs',
            'num_percent_signs', 'num_digits', 'has_https', 'has_ip',
            'has_suspicious_keyword', 'has_suspicious_tld', 'subdomain_count',
            'domain_entropy', 'has_query_string', 'has_fragment',
            'domain_age_days', 'has_brand_spoofing', 'digit_ratio',
            'special_char_ratio', 'has_http', 'has_www', 'has_hex_path',
            'has_suspicious_ext', # 👈 ADDED NEW FEATURE
            
            # EMAIL SPECIFIC
            'email_body_length', 'email_subject_length', 'email_has_urls',
            'email_url_count', 'email_has_attachments',
            'email_urgency_score', 'sender_is_free_email',
            'has_credential_request', 'sender_domain_mismatch',
            'has_threatening_language',
        ]

    def extract_features(self, content, is_email=False, is_sms=False):
        try:
            if is_sms:
                features = self.extract_sms_features(content)
            elif is_email:
                features = self.extract_email_features(content)
            else:
                features = self.extract_url_features(content)
                for f in self.get_feature_names()[29:]:
                    features[f] = 0
        except Exception as e:
            print(f"Error extracting features: {e}")
            features = {f: 0 for f in self.get_feature_names()}
        return features

    def extract_email_features(self, content):
        features = {}
        features['email_body_length'] = len(content)
        features['email_subject_length'] = (
            len(content.split('|')[0]) if '|' in content else len(content) // 2
        )

        urls = re.findall(r'(https?://[^\s]+)', content)
        emails = re.findall(r'[\w\.-]+@([\w\.-]+\.\w+)', content)
        
        features['email_has_urls'] = 1 if (urls or emails) else 0
        features['email_url_count'] = len(urls) + len(emails)

        content_lower = content.lower()
        urgency_score = sum(1 for w in self.urgency_words if w in content_lower)
        sensitive_score = sum(1 for w in self.sensitive_keywords if w in content_lower)
        features['email_urgency_score'] = urgency_score + sensitive_score

        free_domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com']
        sender_is_free = 1 if any(d in content_lower for d in free_domains) else 0
        features['sender_is_free_email'] = sender_is_free
        
        features['email_has_attachments'] = 1 if any(kw in content_lower for kw in self.attachment_hints) else 0

        credential_phrases = [
            'enter your password', 'verify your password', 'credit card',
            'social security', 'ssn', 'cvv', 'pin number', 'bank details',
            'login credentials', 'account number'
        ]
        features['has_credential_request'] = 1 if any(p in content_lower for p in credential_phrases) else 0

        features['has_threatening_language'] = 1 if any(t in content_lower for t in self.threat_words) else 0

        features['sender_domain_mismatch'] = 0
        if sender_is_free and self.check_brand_spoofing(content):
            features['sender_domain_mismatch'] = 1

        target_domain = None
        if urls:
            target_domain = urls[0]
        elif emails:
            target_domain = "http://" + emails[0]

        if target_domain:
            features.update(self.extract_url_features(target_domain))
        else:
            for f in self.get_feature_names()[:29]:
                features[f] = 0
                
        return features

    def extract_sms_features(self, text):
        features = {}
        text_lower = text.lower()

        urls = re.findall(r'(https?://[^\s]+|www\.[^\s]+)', text)
        if urls:
            url = urls[0]
            if not url.startswith("http"):
                url = "http://" + url
            features.update(self.extract_url_features(url))
        else:
            for f in self.get_feature_names()[:29]:
                features[f] = 0

        features['email_body_length'] = len(text)
        features['email_subject_length'] = 0
        features['email_has_urls'] = 1 if urls else 0
        features['email_url_count'] = len(urls)
        features['email_has_attachments'] = 0
        features['sender_is_free_email'] = 0
        features['email_urgency_score'] = sum(
            1 for w in self.urgency_words if w in text_lower
        )

        sms_hits = sum(1 for kw in self.sms_phishing_keywords if kw in text_lower)
        features['digit_ratio'] = min(1.0, sms_hits / 5.0)

        if re.search(r'\b\d{8,}\b', text):
            features['has_ip'] = 1

        letters = [c for c in text if c.isalpha()]
        if letters:
            caps_ratio = sum(1 for c in letters if c.isupper()) / len(letters)
            if caps_ratio > 0.3:
                features['has_suspicious_keyword'] = 1

        return features

    def extract_url_features(self, url):
        parsed = urllib.parse.urlparse(url)
        domain_info = tldextract.extract(url)
        features = {}

        features['url_length'] = len(url)
        features['domain_length'] = len(domain_info.domain)
        features['path_length'] = len(parsed.path)
        features['query_length'] = len(parsed.query) if parsed.query else 0
        features['num_dots'] = url.count('.')
        features['num_hyphens'] = url.count('-')
        features['num_underscores'] = url.count('_')
        features['num_slashes'] = url.count('/')
        features['num_question_marks'] = url.count('?')
        features['num_equal_signs'] = url.count('=')
        features['num_at_signs'] = url.count('@')
        features['num_percent_signs'] = url.count('%')
        features['num_digits'] = sum(c.isdigit() for c in url)
        features['has_https'] = 1 if url.startswith('https://') else 0
        features['has_ip'] = 1 if re.search(r'\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b', url) else 0
        
        url_lower = url.lower()
        features['has_suspicious_keyword'] = 1 if any(kw in url_lower for kw in self.suspicious_keywords) else 0
        features['has_suspicious_tld'] = 1 if any(url_lower.endswith(tld) for tld in self.suspicious_tlds) else 0
        
        features['subdomain_count'] = len(domain_info.subdomain.split('.')) if domain_info.subdomain else 0
        features['domain_entropy'] = self.calculate_entropy(domain_info.domain)
        features['has_query_string'] = 1 if parsed.query else 0
        features['has_fragment'] = 1 if parsed.fragment else 0
        features['domain_age_days'] = 0
        features['has_brand_spoofing'] = 1 if self.check_brand_spoofing(url) else 0
        features['digit_ratio'] = features['num_digits'] / max(1, len(url))
        features['special_char_ratio'] = (
            features['num_dots'] + features['num_hyphens']
            + features['num_underscores'] + features['num_slashes']
        ) / max(1, len(url))
        features['has_http'] = 1 if 'http://' in url else 0
        features['has_www'] = 1 if 'www.' in url else 0
        
        hex_pattern = re.compile(r'[a-f0-9]{16,}', re.IGNORECASE)
        features['has_hex_path'] = 1 if hex_pattern.search(parsed.path) else 0

        # 👈 NEW: Catch dangerous executable extensions
        ext = os.path.splitext(parsed.path)[1].lower()
        suspicious_exts = ['.php', '.exe', '.zip', '.cgi', '.bin', '.x86_64', '.tar.gz', '.dll', '.msi']
        features['has_suspicious_ext'] = 1 if ext in suspicious_exts else 0

        return features

    def calculate_entropy(self, string):
        if not string:
            return 0
        freq = Counter(string)
        length = len(string)
        entropy = 0
        for count in freq.values():
            p = count / length
            entropy -= p * math.log2(p)
        return entropy

    def check_brand_spoofing(self, url):
        popular_brands = [
            'google', 'facebook', 'amazon', 'microsoft', 'apple',
            'paypal', 'bank', 'secure', 'account', 'login',
        ]
        url_lower = url.lower()
        return any(brand in url_lower for brand in popular_brands)