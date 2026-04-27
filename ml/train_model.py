import pymysql
import pandas as pd
import json
from sklearn.ensemble import RandomForestRegressor

# ---------------- DATABASE CONNECTION ----------------
conn = pymysql.connect(
    host="localhost",
    user="root",
    password="",
    database="sk_system"
)

# ---------------- LOAD DATA ----------------
query = """
SELECT a.title, a.participants, b.amount
FROM activities a
CROSS JOIN budgets b
"""

df = pd.read_sql(query, conn)

# ---------------- CREATE SUCCESS LABEL ----------------
# thesis logic: successful if many participants and efficient budget use
df["success_score"] = (df["participants"] / df["amount"]) * 10000

# ---------------- FEATURES ----------------
X = df[["participants", "amount"]]
y = df["success_score"]

# ---------------- TRAIN MODEL ----------------
model = RandomForestRegressor(n_estimators=100, random_state=42)
model.fit(X, y)

# ---------------- PREDICT ----------------
df["predicted_score"] = model.predict(X)

# ---------------- SORT BEST ACTIVITIES ----------------
df = df.sort_values(by="predicted_score", ascending=False)

# ---------------- SAVE RESULTS ----------------
results = []

for _, row in df.iterrows():
    results.append({
        "title": row["title"],
        "participants": int(row["participants"]),
        "budget": float(row["amount"]),
        "predicted_score": round(float(row["predicted_score"]), 2)
    })

with open("ml_results.json", "w") as f:
    json.dump(results, f, indent=4)

print("ML model trained successfully!")