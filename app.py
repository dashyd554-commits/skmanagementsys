from flask import Flask, request, jsonify

app = Flask(__name__)

@app.route("/")
def home():
    return "ML API is running (no model yet)"

@app.route("/predict", methods=["POST"])
def predict():
    data = request.json

    return jsonify({
        "message": "Working API (no ML model yet)",
        "input_received": data
    })

if __name__ == "__main__":
    app.run()