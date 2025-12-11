<?php
session_start();
header('Content-Type: application/json'); // JSON response

$host = "localhost";
$user = "root";
$password = "0000"; // Update if needed
$database = "recommendation";

// Get logged-in user email
$user_email = $_SESSION['user_email'] ?? null;
if (!$user_email) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

// Get feedback data from POST request
$data = json_decode(file_get_contents("php://input"), true);
$recommendation_name = trim($data['recommendation_name'] ?? '');
$rating = intval($data['rating'] ?? 0);

// Validate inputs
if (empty($recommendation_name) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid recommendation name or rating']);
    exit();
}

// Create DB connection
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Insert feedback into database
$sql = "INSERT INTO feedback (user_email, recommendation_name, rating, created_at) VALUES (?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $user_email, $recommendation_name, $rating);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
