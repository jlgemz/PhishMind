import numpy as np
import random
from model import PhishingMLModel
from feature_extractor import FeatureExtractor

class DataGenerator:
    def __init__(self):
        self.extractor = FeatureExtractor()

    def generate_training_data(self, num_samples=5000):
        data, labels = [], []
        legit_domains = ["google.com", "facebook.com", "amazon.com", "microsoft.com", "apple.com", "github.com", "university.edu"]
        phishing_words = ["login", "verify", "update", "secure", "account", "banking", "signin"]
        tlds = ['.tk', '.xyz', '.top', '.club', '.com', '.net']

        for _ in range(num_samples):
            if random.random() < 0.5:
                # --- PHISHING ---
                # Randomly use http:// or https:// so the model doesn't rely on it
                protocol = random.choice(["http://", "https://"])
                domain = random.choice(legit_domains)
                word = random.choice(phishing_words)
                
                # Create fake look-alike domains or subdomains
                if random.random() < 0.5:
                    url = f"{protocol}{word}-{domain}{random.choice(tlds)}/login.php"
                else:
                    url = f"{protocol}{domain}.{random.choice(tlds)}/{word}?id=12345"
                
                label = 1
            else:
                # --- LEGITIMATE ---
                protocol = random.choice(["http://", "https://"])
                domain = random.choice(legit_domains)
                
                paths = ["/about", "/contact", "/news/2024", "/index.html", "/"]
                url = f"{protocol}{domain}{random.choice(paths)}"
                
                label = 0

            features = self.extractor.extract_features(url)
            vector = [features.get(f, 0) for f in self.extractor.get_feature_names()]
            data.append(vector)
            labels.append(label)
        return np.array(data), np.array(labels)

if __name__ == "__main__":
    print("Generating balanced data...")
    X, y = DataGenerator().generate_training_data()
    print(f"Generated {len(X)} samples.")
    
    print("Training Random Forest...")
    model = PhishingMLModel()
    model.train(X, y)
    model.save_model("phishing_model.pkl")
    print("Model saved successfully to phishing_model.pkl!")