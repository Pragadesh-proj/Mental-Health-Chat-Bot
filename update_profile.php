<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "mental_health_chatbot");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add avatar column if it doesn't exist
$sql = "SHOW COLUMNS FROM users LIKE 'avatar'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $alter_sql = "ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT 'default.png'";
    if ($conn->query($alter_sql) === TRUE) {
        echo "Avatar column added successfully";
    } else {
        echo "Error adding avatar column: " . $conn->error;
    }
} else {
    echo "Avatar column already exists";
}

$user_id = $_SESSION['user_id'];
$name = $_POST['name'];
$email = $_POST['email'];
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Verify current password
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($current_password, $user['password'])) {
    $_SESSION['message'] = "Current password is incorrect!";
    header("Location: profile.php");
    exit();
}

// Check if email already exists for another user
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $_SESSION['message'] = "Email already in use by another account!";
    header("Location: profile.php");
    exit();
}
$stmt->close();

// Handle avatar upload
$avatar_update = false;
$avatar_name = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['avatar']['name'];
    $filetype = pathinfo($filename, PATHINFO_EXTENSION);
    
    // Verify file extension
    if (in_array(strtolower($filetype), $allowed)) {
        // Generate unique filename
        $new_filename = uniqid('avatar_') . '.' . $filetype;
        $upload_dir = 'uploads/avatars/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Move the file
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $new_filename)) {
            $avatar_update = true;
            $avatar_name = $new_filename;
            
            // Try to delete old avatar if it exists
            try {
                $stmt = $conn->prepare("SELECT avatar FROM users WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result && $result->num_rows > 0) {
                        $old_avatar = $result->fetch_assoc()['avatar'];
                        if ($old_avatar && $old_avatar != 'default.png' && file_exists($upload_dir . $old_avatar)) {
                            unlink($upload_dir . $old_avatar);
                        }
                    }
                    $stmt->close();
                }
            } catch (Exception $e) {
                // If column doesn't exist, just continue
            }
        } else {
            $_SESSION['message'] = "Failed to upload image!";
            header("Location: profile.php");
            exit();
        }
    } else {
        $_SESSION['message'] = "Invalid file type. Only JPG, JPEG, PNG and GIF are allowed.";
        header("Location: profile.php");
        exit();
    }
}

// Update user information
if (!empty($new_password)) {
    // Validate new password
    if ($new_password != $confirm_password) {
        $_SESSION['message'] = "New passwords do not match!";
        header("Location: profile.php");
        exit();
    }
    
    // Update with new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    if ($avatar_update) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, avatar = ? WHERE id = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssssi", $name, $email, $hashed_password, $avatar_name, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssi", $name, $email, $hashed_password, $user_id);
    }
} else {
    // Update without changing password
    if ($avatar_update) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, avatar = ? WHERE id = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssi", $name, $email, $avatar_name, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssi", $name, $email, $user_id);
    }
}

if ($stmt->execute()) {
    $_SESSION['message'] = "Profile updated successfully!";
} else {
    $_SESSION['message'] = "Error updating profile: " . $conn->error;
}
$stmt->close();
$conn->close();

header("Location: profile.php");
exit();