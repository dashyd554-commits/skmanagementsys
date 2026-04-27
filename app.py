from flask import Flask, request, jsonify
import pickle
import numpy as np
import json

app = Flask(__name__)

# Load trained model
model = pickle.load(open("model.pkl", "rb"))

# Optional: load results if needed
with open("ml_results.json") as f:
    results = json.load(f)

@app.route("/")
def home():
    return "SK ML API is running"

@app.route("/predict", methods=["POST"])
def predict():
    data = request.json["input"]

    input_array = np.array([data])
    prediction = model.predict(input_array)

    return jsonify({
        "prediction": prediction.tolist(),
        "status": "success"
    })

if __name__ == "__main__":
    app.run()