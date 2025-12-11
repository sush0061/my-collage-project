<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$password = "";
$database = "recommendation";

// Check if user is logged in via session
if (!isset($_SESSION['user_email'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["success" => false, "error" => "User not logged in"]);
    exit;
}

$conn = new mysqli($host, $user, $password, $database);

// Check database connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

$email = $_SESSION['user_email'];

// Prepare statement to fetch full recommendation history
$stmt = $conn->prepare("
    SELECT recommendation_type, age, symptoms, disease, recommendation, created_at
    FROM treatment_recommendations
    WHERE user_email = ?
    ORDER BY created_at DESC
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Failed to prepare statement"]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$history = [];

while ($row = $result->fetch_assoc()) {
    $history[] = [
        "type" => htmlspecialchars($row['recommendation_type']),
        "age" => (int)$row['age'],
        "symptoms" => htmlspecialchars($row['symptoms']),
        "disease" => htmlspecialchars($row['disease']),
        "recommendation" => htmlspecialchars($row['recommendation']),
        "created_at" => $row['created_at']
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    "success" => true,
    "history" => $history
]);
?>
