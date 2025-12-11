from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import mysql.connector
import os

app = FastAPI(
    title="PrakritiCare - Ayurvedic Drug Recommendation API",
    description="API for recommending Ayurvedic drugs, herbal formulations, and lifestyle remedies based on symptoms and disease, with user feedback support.",
    version="1.2"
)

# Enable CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Load Ayurvedic CSV
AYURVEDIC_DATA_FILE = "Ayurvedic2.csv"
if not os.path.exists(AYURVEDIC_DATA_FILE):
    raise FileNotFoundError(f"{AYURVEDIC_DATA_FILE} not found!")

ayurvedic_data = pd.read_csv(AYURVEDIC_DATA_FILE).dropna(subset=["Disease", "Remedy", "Symptoms"])

vectorizer = TfidfVectorizer(stop_words="english", ngram_range=(1, 2))
symptom_matrix = vectorizer.fit_transform(ayurvedic_data["Symptoms"])

# MySQL connection config
DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "recommendation"
}

# Pydantic models
class RecommendationRequest(BaseModel):
    input_symptoms: str
    threshold: float = 0.5
    user_email: str

class RecommendationResponse(BaseModel):
    input_symptoms: str
    recommended_remedies: list = []

class FeedbackRequest(BaseModel):
    user_email: str
    recommendation_name: str
    rating: int  # e.g., 1-5

# Recommendation logic
def recommend_remedies(input_symptoms: str, threshold: float):
    input_vector = vectorizer.transform([input_symptoms])
    scores = cosine_similarity(input_vector, symptom_matrix).flatten()

    results = []
    for idx, score in enumerate(scores):
        if score >= threshold:
            results.append({
                "disease": ayurvedic_data.iloc[idx]["Disease"],
                "remedy": ayurvedic_data.iloc[idx]["Remedy"],
                "similarity": round(score, 2)
            })
    return sorted(results, key=lambda x: -x['similarity'])[:5]

# Save recommendation to user history
def save_recommendation(user_email: str, entry: dict, input_symptoms: str):
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()
    sql = """
        INSERT INTO treatment_recommendations 
        (user_email, recommendation_type, age, symptoms, disease, recommendation)
        VALUES (%s, %s, %s, %s, %s, %s)
    """
    cursor.execute(sql, (user_email, "Ayurvedic", 0, input_symptoms, entry["disease"], entry["remedy"]))
    conn.commit()
    cursor.close()
    conn.close()

# Save feedback
def save_feedback(user_email: str, recommendation_name: str, rating: int):
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor()
    sql = "INSERT INTO feedback (user_email, recommendation_name, rating) VALUES (%s, %s, %s)"
    cursor.execute(sql, (user_email, recommendation_name, rating))
    conn.commit()
    cursor.close()
    conn.close()

# Get average ratings
def get_average_ratings():
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)
    sql = """
        SELECT recommendation_name, AVG(rating) AS avg_rating, COUNT(*) AS total_feedbacks
        FROM feedback
        GROUP BY recommendation_name
    """
    cursor.execute(sql)
    results = cursor.fetchall()
    cursor.close()
    conn.close()
    return results

# API endpoints
@app.post("/recommend_remedy/", response_model=RecommendationResponse)
async def get_recommendation(request: RecommendationRequest):
    remedies = recommend_remedies(request.input_symptoms, request.threshold)
    for r in remedies:
        save_recommendation(request.user_email, r, request.input_symptoms)
    return RecommendationResponse(input_symptoms=request.input_symptoms, recommended_remedies=remedies)

@app.get("/history/{user_email}")
async def get_user_history(user_email: str):
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)
    sql = """
        SELECT recommendation_type, age, symptoms, disease, recommendation, created_at
        FROM treatment_recommendations
        WHERE user_email = %s
        ORDER BY created_at DESC
        LIMIT 20
    """
    cursor.execute(sql, (user_email,))
    history = cursor.fetchall()
    cursor.close()
    conn.close()
    return history

@app.post("/feedback/")
async def submit_feedback(feedback: FeedbackRequest):
    if feedback.rating < 1 or feedback.rating > 5:
        raise HTTPException(status_code=400, detail="Rating must be between 1 and 5")
    save_feedback(feedback.user_email, feedback.recommendation_name, feedback.rating)
    return {"message": "Feedback submitted successfully"}

@app.get("/average_ratings/")
async def average_ratings():
    return get_average_ratings()

@app.get("/symptom_keywords/")
async def get_symptom_keywords():
    return sorted(set(ayurvedic_data["Symptoms"].dropna().unique().tolist()))

@app.get("/ayurvedic_disease_keywords/")
async def get_ayurvedic_disease_keywords():
    return sorted(set(ayurvedic_data["Disease"].dropna().unique().tolist()))

@app.get("/")
async def root():
    return {"message": "PrakritiCare Ayurvedic Drug Recommendation API is running!"}
