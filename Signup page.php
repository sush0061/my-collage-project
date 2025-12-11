<?php
session_start();

header('Content-Type: application/json');

$servername = "localhost";
$dbUsername = "root";
$dbPassword = "0000";
$dbname = "recommendation";

// Create connection
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "server_error"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($fullname) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        echo json_encode(["success" => false, "error" => "empty_fields"]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "error" => "invalid_email"]);
        exit();
    }

    if ($password !== $confirm_password) {
        echo json_encode(["success" => false, "error" => "password_mismatch"]);
        exit();
    }

    // Check if email or username already exists
    $stmt = $conn->prepare("SELECT id FROM register WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        echo json_encode(["success" => false, "error" => "exists"]);
        exit();
    }
    $stmt->close();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO register (name, email, username, pass) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $fullname, $email, $username, $hashedPassword);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo json_encode(["success" => true, "message" => "registered"]);
        exit();
    } else {
        $stmt->close();
        $conn->close();
        echo json_encode(["success" => false, "error" => "insert_failed"]);
        exit();
    }
}
?>
