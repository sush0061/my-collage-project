<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "0000";
$dbname = "recommendation";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header("Location: Login Page.html?error=server");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Check if fields are empty
    if (empty($email) || empty($password)) {
        header("Location: Login Page.html?error=empty_fields");
        exit();
    }

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, name, pass FROM register WHERE email = ?");
    if ($stmt === false) {
        header("Location: Login Page.html?error=server");
        exit();
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $name, $hashedPassword);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashedPassword)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            // Redirect to dashboard or main page
            header("Location: Main Page.html?login=success");
            exit();
        } else {
            header("Location: Login Page.html?error=invalid_password");
            exit();
        }
    } else {
        header("Location: Login Page.html?error=no_account");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>
