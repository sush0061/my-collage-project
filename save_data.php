<?php
session_start();
header('Content-Type: application/json'); // JSON response for frontend

$host = "localhost";
$user = "root";
$password = "0000"; // update if needed
$dbname = "recommendation";

// Create DB connection
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Get logged-in user email
$user_email = $_SESSION['user_email'] ?? null;
if (!$user_email) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

// Get POSTed JSON data
$data = json_decode(file_get_contents("php://input"), true);
$recommendationType = trim($data['recommendationType'] ?? '');
$age = intval($data['age'] ?? 0);
$symptoms = trim($data['symptoms'] ?? '');
$disease = trim($data['disease'] ?? '');
$recommendation = trim($data['recommendation'] ?? '');

// Basic validation
if (empty($recommendationType) || $age <= 0 || empty($symptoms) || empty($disease) || empty($recommendation)) {
    echo json_encode(['success' => false, 'error' => 'All fields are required and age must be greater than 0']);
    exit();
}

// Prepare and execute insert query
$stmt = $conn->prepare("INSERT INTO treatment_recommendations (user_email, recommendation_type, age, symptoms, disease, recommendation, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("ssisss", $user_email, $recommendationType, $age, $symptoms, $disease, $recommendation);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Data saved successfully']);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
