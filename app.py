from flask import Flask, request, jsonify
import pandas as pd
from sklearn.ensemble import RandomForestRegressor
import os

app = Flask(__name__)

@app.route("/")
def home():
    return "SK ML API Connected"

@app.route("/ml", methods=["POST"])
def ml():

    try:
        incoming = request.get_json()

        activities = incoming.get("activities", [])
        budget = incoming.get("budget", 0)

        if len(activities) == 0:
            return jsonify([])

        rows = []

        for item in activities:
            rows.append({
                "title": item["title"],
                "participants": item["participants"],
                "amount": budget
            })

        df = pd.DataFrame(rows)

        # thesis ML logic
        df["success_score"] = (df["participants"] / df["amount"]) * 10000

        X = df[["participants", "amount"]]
        y = df["success_score"]

        model = RandomForestRegressor(n_estimators=100, random_state=42)
        model.fit(X, y)

        df["predicted_score"] = model.predict(X)

        df = df.sort_values(by="predicted_score", ascending=False)

        results = []

        for _, row in df.iterrows():
            results.append({
                "title": row["title"],
                "participants": int(row["participants"]),
                "budget": float(row["amount"]),
                "predicted_score": round(float(row["predicted_score"]),2)
            })

        return jsonify(results)

    except Exception as e:
        return jsonify({"error": str(e)})

if __name__ == "__main__":
    port = int(os.environ.get("PORT",10000))
    app.run(host="0.0.0.0", port=port)