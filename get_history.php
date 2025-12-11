<?php
session_start();
header('Content-Type: application/json');

// Database credentials
$host = "localhost";
$user = "root";
$password = "0000"; // Update according to your setup
$dbname = "recommendation";

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Check if user is logged in
$user_email = $_SESSION['user_email'] ?? null;
if (!$user_email) {
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

// Fetch the last 10 recommendations for the logged-in user
$sql = "SELECT symptoms, recommendation, recommendation_type, created_at 
        FROM treatment_recommendations 
        WHERE user_email = ? 
        ORDER BY created_at DESC 
        LIMIT 10";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Failed to prepare query"]);
    exit();
}

$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            "symptoms" => $row['symptoms'],
            "recommendation" => $row['recommendation'],
            "recommendation_type" => $row['recommendation_type'],
            "created_at" => $row['created_at']
        ];
    }
}

// Return JSON response
echo json_encode($history);

// Close connections
$stmt->close();
$conn->close();
?>
