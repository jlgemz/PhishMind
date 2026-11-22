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
            'suspended', 'restore', 'urgent', 'limited', 'action', 'now', 'billing',
            'wallet', 'unlock', 'unauthorized', 're-activate'
        ]
        self.popular_brand_domains = {
            'google': {'google', 'youtube', 'gmail', 'gstatic', 'googlevideo', 'blogger', 'withgoogle'},
            'youtube': {'youtube', 'youtu'},
            'facebook': {'facebook', 'fb', 'messenger'},
            'meta': {'meta', 'facebook', 'instagram', 'whatsapp'},
            'instagram': {'instagram'},
            'whatsapp': {'whatsapp'},
            'twitter': {'twitter', 'x', 't'},
            'tiktok': {'tiktok'},
            'linkedin': {'linkedin'},
            'apple': {'apple', 'icloud'},
            'microsoft': {'microsoft', 'live', 'office', 'outlook', 'windows', 'msn', 'bing'},
            'amazon': {'amazon', 'aws'},
            'netflix': {'netflix'},
            'paypal': {'paypal'},
            'yahoo': {'yahoo'},
            'github': {'github'},
            'reddit': {'reddit'},
            'telegram': {'telegram'},
            'spotify': {'spotify'},
            'gcash': {'gcash'},
            'maya': {'maya', 'paymaya'},
            'bdo': {'bdo'},
            'bpi': {'bpi'},
            'landbank': {'landbank'},
            'metrobank': {'metrobank'},
            'unionbank': {'unionbank'},
            'securitybank': {'securitybank'},
            'smart': {'smart'},
            'globe': {'globe'},
            'shopee': {'shopee'},
            'lazada': {'lazada'},
            'slack': {'slack'},
            'zoom': {'zoom'},
            'wikipedia': {'wikipedia'},
            'openai': {'openai'},
            'cloudflare': {'cloudflare'},
            'adobe': {'adobe'},
            'bitly': {'bit', 'bitly'},
            'tinyurl': {'tinyurl'}
        }
        self.known_shorteners = {
            'bit.ly', 'tinyurl.com', 't.co', 'goo.gl', 'ow.ly', 'is.gd',
            'buff.ly', 'adf.ly', 'bl.ink', 'cutt.ly', 'tiny.cc', 'shorturl.at',
            'youtu.be', 'fb.me', 'rb.gy', 'qr.ae', 'rebrand.ly', 't.me'
        }
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
            'has_suspicious_ext',
            'is_shortened_url', 'is_popular_domain',
            
            # EMAIL SPECIFIC
            'email_body_length', 'email_subject_length', 'email_has_urls',
            'email_url_count', 'email_has_attachments',
            'email_urgency_score', 'sender_is_free_email',
            'has_credential_request', 'sender_domain_mismatch',
            'has_threatening_language',
        ]

    def validate_input(self, content: str, modality: str = 'url') -> tuple:
        """
        Validates whether an input is processable for the given modality.
        Returns (is_valid: bool, error_message: str).
        """
        if not content or not str(content).strip():
            return False, "Input content is empty or contains only whitespace."

        cleaned = str(content).strip()
        mod_lower = modality.lower()

        if 'url' in mod_lower:
            if len(cleaned) < 3:
                return False, "URL is too short to constitute a valid web address."

            # Reject multiline inputs (e.g. emails or SMS messages pasted into the URL modality)
            if '\n' in cleaned or '\r' in cleaned:
                return False, "Invalid URL: content contains line breaks. It appears you pasted an email or message into the 'URL' modality. Please select the 'Email' or 'SMS' modality above."

            # Reject natural language sentences with multiple spaces
            if cleaned.count(' ') > 2 and not re.match(r'^[a-zA-Z]+://[^\s]+$', cleaned):
                return False, "Invalid URL: content contains sentence text with spaces. Please enter a valid web address (e.g., https://example.com) or switch to the 'Email' modality."

            # Reject email greetings / headers
            if re.match(r'^(?:dear|to|hi|hello|subject:|from:|greetings)\b', cleaned, re.IGNORECASE):
                return False, "Invalid URL: content begins with an email greeting or header. Please select the 'Email' modality to analyze emails."

            # Reject impossible protocols or malformed schemes
            if re.match(r'^[a-zA-Z]+:///$', cleaned) or re.match(r'^[a-zA-Z]+://$', cleaned):
                return False, "Malformed URL: missing domain or host."

            target = cleaned if re.match(r'^[a-zA-Z]+://', cleaned) else 'https://' + cleaned
            try:
                parsed = urllib.parse.urlparse(target)
                domain_info = tldextract.extract(target)
            except Exception as e:
                return False, f"Failed to parse URL: {e}"

            host = parsed.netloc or ''
            if any(ch in host for ch in (' ', '\n', '\r', '\t', ',', ';', '!', '?', '"', "'", '(', ')')):
                return False, "Invalid URL: host contains illegal characters or spaces. If you meant to analyze an email or message, please select the corresponding modality."

            has_ip = bool(re.search(r'\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b', target))
            has_domain = bool(domain_info.domain and domain_info.suffix and re.match(r'^[a-zA-Z0-9.-]+$', domain_info.domain))
            has_netloc = bool(parsed.netloc and ('.' in parsed.netloc or has_ip or 'localhost' in parsed.netloc))

            if not (has_ip or (has_domain and has_netloc) or 'localhost' in parsed.netloc):
                return False, "Malformed or invalid URL: unable to identify a valid domain name, hostname, or IP address."

            return True, ""

        elif 'email' in mod_lower:
            if len(cleaned) < 8:
                return False, "Email content is too short to analyze for phishing signals (minimum 8 characters)."
            return True, ""

        elif 'sms' in mod_lower:
            if len(cleaned) < 4:
                return False, "SMS content is too short to evaluate (minimum 4 characters)."
            return True, ""

        return True, ""

    def extract_features(self, content, is_email=False, is_sms=False):
        try:
            if is_sms:
                features = self.extract_sms_features(content)
            elif is_email:
                features = self.extract_email_features(content)
            else:
                features = self.extract_url_features(content)
        except Exception as e:
            print(f"Error extracting features: {e}")
            features = {}
            
        # Ensure all registered features exist with safe defaults
        for f in self.get_feature_names():
            if f not in features:
                features[f] = 0
                
        return features

    def extract_email_features(self, content):
        features = {}
        content_str = str(content).strip()
        features['email_body_length'] = len(content_str)

        # Subject extraction: Check for explicit Subject header or pipe delimiter
        subj_match = re.search(r'^(?:subject|re|fwd):\s*([^\n\r]+)', content_str, re.IGNORECASE | re.MULTILINE)
        if subj_match:
            features['email_subject_length'] = len(subj_match.group(1).strip())
        elif '|' in content_str:
            features['email_subject_length'] = len(content_str.split('|')[0].strip())
        else:
            first_line = content_str.split('\n')[0].strip()
            # If the first line is a greeting / salutation, there is no subject line in the body
            if re.match(r'^(?:dear|to|hi|hello|greetings|good\s+(?:morning|afternoon|day|evening))\b', first_line, re.IGNORECASE):
                features['email_subject_length'] = 0
            else:
                features['email_subject_length'] = min(len(first_line), 80)

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
            target_domain = "https://" + emails[0]

        if target_domain:
            features.update(self.extract_url_features(target_domain))
        else:
            for f in self.get_feature_names()[:31]:
                features[f] = 0
                
        return features

    def extract_sms_features(self, text):
        features = {}
        text_lower = text.lower()

        urls = re.findall(r'(https?://[^\s]+|www\.[^\s]+|[a-zA-Z0-9-]+\.[a-zA-Z0-9-]+\.[^\s]+)', text)
        if urls:
            url = urls[0]
            if not url.startswith("http"):
                url = "https://" + url
            features.update(self.extract_url_features(url))
        else:
            for f in self.get_feature_names()[:31]:
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

        # Real IP addresses only (not arbitrary 8-digit OTP reference numbers)
        if re.search(r'\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b', text):
            features['has_ip'] = 1
        else:
            features['has_ip'] = 0

        letters = [c for c in text if c.isalpha()]
        if letters:
            caps_ratio = sum(1 for c in letters if c.isupper()) / len(letters)
            if caps_ratio > 0.35:
                features['has_suspicious_keyword'] = 1

        return features

    def extract_url_features(self, url):
        raw_url = str(url).strip()
        has_explicit_scheme = bool(re.match(r'^[a-zA-Z]+://', raw_url))
        normalized_url = raw_url if has_explicit_scheme else 'https://' + raw_url

        parsed = urllib.parse.urlparse(normalized_url)
        domain_info = tldextract.extract(normalized_url)
        features = {}

        features['url_length'] = len(normalized_url)
        features['domain_length'] = len(domain_info.domain)
        features['path_length'] = len(parsed.path.lstrip('/'))
        features['query_length'] = len(parsed.query) if parsed.query else 0
        features['num_dots'] = normalized_url.count('.')
        features['num_hyphens'] = normalized_url.count('-')
        features['num_underscores'] = normalized_url.count('_')
        features['num_slashes'] = normalized_url.count('/')
        features['num_question_marks'] = normalized_url.count('?')
        features['num_equal_signs'] = normalized_url.count('=')
        features['num_at_signs'] = normalized_url.count('@')
        features['num_percent_signs'] = normalized_url.count('%')
        features['num_digits'] = sum(c.isdigit() for c in normalized_url)

        features['has_https'] = 1 if (raw_url.startswith('https://') or not has_explicit_scheme) else 0
        features['has_http'] = 1 if raw_url.startswith('http://') else 0
        features['has_ip'] = 1 if (re.search(r'\b(?:[0-9]{1,3}\.){3}[0-9]{1,3}\b', domain_info.domain)
                                   or re.search(r'^[0-9.]+$', domain_info.domain)) else 0
        
        url_lower = normalized_url.lower()
        curr_domain = (domain_info.domain or '').lower()
        curr_suffix = (domain_info.suffix or '').lower()
        registered_domain = f"{curr_domain}.{curr_suffix}" if curr_suffix else curr_domain

        # URL Shortener detection
        is_shortener = (
            registered_domain in self.known_shorteners
            or (curr_domain in ('bit', 'tinyurl', 't', 'ow', 'is', 'buff', 'adf', 'bl', 'cutt', 'tiny', 'shorturl', 'youtu', 'fb', 'rb', 'rebrand')
                and curr_suffix in ('ly', 'com', 'co', 'gl', 'gd', 'ink', 'cc', 'at', 'be', 'me', 'gy'))
        )
        features['is_shortened_url'] = 1 if is_shortener else 0

        # Popular / legitimate platform detection
        is_popular = (
            (curr_domain in self.popular_brand_domains or any(curr_domain in domains for domains in self.popular_brand_domains.values()))
            and curr_suffix in ('com', 'org', 'net', 'edu', 'gov', 'io', 'be', 'me', 'co', 'ly', 'app', 'dev', 'ai', 'tv', 'link', 'ph', 'com.ph', 'edu.ph', 'gov.ph', 'org.ph')
        )
        features['is_popular_domain'] = 1 if is_popular else 0

        # Brand spoofing: brand name used on an untrusted or lookalike domain
        features['has_brand_spoofing'] = self.check_brand_spoofing(normalized_url, domain_info)

        # Suspicious keywords
        if is_popular and parsed.path.lower() in ('/login', '/signin', '/account', '/watch', '/home', '/explore', '/watch/'):
            features['has_suspicious_keyword'] = 0
        else:
            features['has_suspicious_keyword'] = 1 if any(kw in url_lower for kw in self.suspicious_keywords) else 0

        features['has_suspicious_tld'] = 1 if any(url_lower.endswith(tld) for tld in self.suspicious_tlds) else 0
        
        # Subdomains (exclude www)
        subdomains = [s for s in domain_info.subdomain.split('.') if s and s != 'www']
        features['subdomain_count'] = len(subdomains)
        features['domain_entropy'] = self.calculate_entropy(domain_info.domain)
        features['has_query_string'] = 1 if parsed.query else 0
        features['has_fragment'] = 1 if parsed.fragment else 0
        features['domain_age_days'] = 0
        features['digit_ratio'] = features['num_digits'] / max(1, len(normalized_url))
        features['special_char_ratio'] = (
            features['num_dots'] + features['num_hyphens']
            + features['num_underscores'] + features['num_slashes']
        ) / max(1, len(normalized_url))
        features['has_www'] = 1 if 'www.' in url_lower else 0
        
        hex_pattern = re.compile(r'[a-f0-9]{16,}', re.IGNORECASE)
        features['has_hex_path'] = 1 if hex_pattern.search(parsed.path) else 0

        # Suspicious file extensions
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

    def check_brand_spoofing(self, url, domain_info=None):
        if domain_info is None:
            domain_info = tldextract.extract(url)

        curr_domain = (domain_info.domain or '').lower()
        url_lower = url.lower()

        for brand, legitimate_domains in self.popular_brand_domains.items():
            # If current registered domain is an authentic domain for this brand, it is NOT spoofing!
            if curr_domain in legitimate_domains:
                continue
            if len(brand) < 3:
                continue
            # Look for brand as a distinct token or keyword in domain, subdomain, or path
            # e.g., paypal.somewhere.com, login-paypal.com, or test.com/paypal/login
            if re.search(r'(?:^|[^a-z0-9])' + re.escape(brand) + r'(?:[^a-z0-9]|$)', url_lower):
                return 1
        return 0