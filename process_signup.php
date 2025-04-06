<?php
session_start();
require_once 'config/database.php';

$registrationSuccessful = false;
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errorMessage = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $errorMessage = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $errorMessage = "Password must be at least 8 characters long.";
    } else {
        // Check if username exists
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        if ($stmt === false) {
            $errorMessage = "Database error: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $errorMessage = "Username already exists.";
            } else {
                // Check if email exists
                $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
                if ($stmt === false) {
                    $errorMessage = "Database error: " . mysqli_error($conn);
                } else {
                    mysqli_stmt_bind_param($stmt, "s", $email);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($result) > 0) {
                        $errorMessage = "Email already exists.";
                    } else {
                        // Hash password and insert user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)");
                        if ($stmt === false) {
                            $errorMessage = "Database error: " . mysqli_error($conn);
                        } else {
                            mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $phone, $hashed_password);
                            
                            if (mysqli_stmt_execute($stmt)) {
                                $registrationSuccessful = true;
                            } else {
                                $errorMessage = "Registration failed: " . mysqli_stmt_error($stmt);
                            }
                        }
                    }
                }
            }
        }
    }
}

// Return JSON response
if ($registrationSuccessful) {
    echo json_encode(['success' => true, 'message' => 'Account created successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $errorMessage]);
}
?> 