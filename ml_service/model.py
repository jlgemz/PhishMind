import joblib
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
import shap
import warnings

warnings.filterwarnings("ignore")


class PhishingMLModel:
    def __init__(self):
        self.model = None
        self.explainer = None
        # NOTE: feature_names will be overwritten by extractor.get_feature_names()
        # when training or loading. Do not hardcode a partial list here.
        self.feature_names = []

    def train(self, X, y):
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, random_state=42
        )
        self.model = RandomForestClassifier(
            n_estimators=100, max_depth=10, random_state=42, class_weight='balanced'
        )
        self.model.fit(X_train, y_train)
        self.explainer = shap.TreeExplainer(self.model)
        return {"accuracy": self.model.score(X_test, y_test)}

    def predict_with_shap(self, feature_vector):
        X = np.array([feature_vector], dtype=float)
        prediction = int(self.model.predict(X)[0])
        probabilities = self.model.predict_proba(X)[0]

        explanation = self.explainer(X)
        if isinstance(explanation.values, list):
            shap_vals = explanation.values[1][0]
        elif explanation.values.ndim == 3:
            shap_vals = explanation.values[0, :, 1]
        else:
            shap_vals = explanation.values[0]

        feature_shap = {name: float(val) for name, val in zip(self.feature_names, shap_vals)}
        positive = sorted(
            [(k, v) for k, v in feature_shap.items() if v > 0],
            key=lambda kv: kv[1],
            reverse=True,
        )
        negative = sorted(
            [(k, v) for k, v in feature_shap.items() if v < 0],
            key=lambda kv: kv[1],
        )

        influential = sorted(
            [(k, v) for k, v in feature_shap.items()],
            key=lambda kv: abs(kv[1]),
            reverse=True,
        )

        prob_phish = float(probabilities[1]) if len(probabilities) > 1 else float(probabilities[0])
        prob_legit = float(probabilities[0]) if len(probabilities) > 1 else 1.0 - prob_phish

        return {
            "prediction": prediction,
            "confidence": float(max(probabilities)),
            "probabilities": {
                "legitimate": prob_legit,
                "phishing": prob_phish,
            },
            "top_risk_indicators": positive[:5],
            "top_safety_indicators": negative[:5],
            "top_influential_indicators": influential[:5],
            "shap_values": feature_shap,
        }

    def save_model(self, filepath="phishing_model.pkl"):
        joblib.dump({"model": self.model, "feature_names": self.feature_names}, filepath)

    def load_model(self, filepath="phishing_model.pkl"):
        data = joblib.load(filepath)
        self.model = data["model"]
        self.feature_names = data["feature_names"]
        self.explainer = shap.TreeExplainer(self.model)