<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verify if the user exists in the database
$user_id = $_SESSION['user_id'];
$check_user = $conn->prepare("SELECT id FROM users WHERE id = ?");
if ($check_user === false) {
    die("Error preparing check user statement: " . $conn->error);
}

$check_user->bind_param("i", $user_id);
$check_user->execute();
$user_result = $check_user->get_result();

// If user doesn't exist in the database, create a test user and use that ID
if ($user_result->num_rows === 0) {
    // First try to get the testuser
    $test_user = $conn->query("SELECT id FROM users WHERE username = 'testuser'");
    
    if ($test_user->num_rows > 0) {
        // Use the test user's ID
        $test_user_data = $test_user->fetch_assoc();
        $user_id = $test_user_data['id'];
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = 'testuser';
    } else {
        // Create the test user
        $create_test = "INSERT INTO users (username, email, password, status, created_at) 
                         VALUES ('testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW())";
        
        if ($conn->query($create_test)) {
            $user_id = $conn->insert_id;
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = 'testuser';
        } else {
            die("Error creating test user: " . $conn->error);
        }
    }
}

// Check if messages table exists, create it if it doesn't
$tableCheck = $conn->query("SHOW TABLES LIKE 'messages'");
if ($tableCheck->num_rows == 0) {
    // Messages table doesn't exist, create it
    $createTable = "CREATE TABLE `messages` (
        `message_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `type` varchar(50) NOT NULL,
        `subject` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `created_at` datetime NOT NULL DEFAULT current_timestamp(),
        `reply_text` text DEFAULT NULL,
        `reply_date` datetime DEFAULT NULL,
        PRIMARY KEY (`message_id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if (!$conn->query($createTable)) {
        die("Error creating messages table: " . $conn->error);
    }
}

// Fetch user's messages and admin replies
$user_id = $_SESSION['user_id'];

// Update the query to correctly fetch replies from messages table
$query = "SELECT message_id, type, subject, message, created_at, reply_text, reply_date 
          FROM messages 
          WHERE user_id = ? 
          ORDER BY created_at DESC";
$stmt = $conn->prepare($query);

// Check if prepare statement succeeded
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch all messages
$messages = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
    $type = $_POST['type'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    // Make sure we have a valid user ID
    if (!isset($_SESSION['user_id'])) {
        die("Error: User session is invalid. Please log out and log in again.");
    }
    
    // Verify user exists one more time
    $user_id = $_SESSION['user_id'];
    $check_user = $conn->prepare("SELECT id FROM users WHERE id = ?");
    if ($check_user === false) {
        die("Error preparing check user statement: " . $conn->error);
    }
    
    $check_user->bind_param("i", $user_id);
    $check_user->execute();
    $user_result = $check_user->get_result();
    
    if ($user_result->num_rows === 0) {
        die("Error: User ID $user_id not found in the database. Please log out and log in again.");
    }
    
    // Insert new message
    $insert_query = "INSERT INTO messages (user_id, type, subject, message, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
    
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    
    if ($insert_stmt === false) {
        die("Error preparing insert statement: " . mysqli_error($conn));
    }
    
    if (!mysqli_stmt_bind_param($insert_stmt, "isss", $user_id, $type, $subject, $message)) {
        die("Error binding insert parameters: " . mysqli_stmt_error($insert_stmt));
    }
    
    if (mysqli_stmt_execute($insert_stmt)) {
        // Redirect to avoid form resubmission
        header('Location: contact.php?status=success');
        exit();
    } else {
        die("Error sending message: " . mysqli_stmt_error($insert_stmt) . 
            "<br>SQL Error: " . $conn->error . 
            "<br>User ID being used: " . $user_id);
    }
}

// Close statements
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($insert_stmt)) mysqli_stmt_close($insert_stmt);

// Add success message display
$success_message = '';
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $success_message = 'Your message has been sent successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Mental Health Chatbot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/dark-mode.css" rel="stylesheet">
    <script src="assets/js/dark-mode.js" defer></script>
    <style>
    /* Professional UI/UX Design Elements */
    :root {
        --primary-color: #4f46e5;
        --primary-light: #e0e7ff;
        --secondary-color: #8b5cf6;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --dark-color: #1e293b;
        --light-color: #f8fafc;
        --grey-color: #64748b;
        --card-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        --hover-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
    
    body {
        background-color: #f9fafb;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    /* Navigation styling */
    .navbar {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 12px 0;
        background: linear-gradient(90deg, #1e40af, #3b82f6);
    }
    
    .navbar-nav {
        align-items: center;
    }
    
    .navbar-nav .nav-item {
        position: relative;
        margin: 0 8px;
        display: flex;
        align-items: center;
    }
    
    .navbar-nav .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 600;
        padding: 0.8rem 1.2rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        white-space: nowrap;
        text-align: center;
        border-radius: 8px;
    }
    
    .navbar-nav .nav-link i {
        margin-right: 8px;
        font-size: 16px;
        display: inline-flex;
        align-items: center;
    }
    
    .navbar-nav .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.15);
        color: #ffffff !important;
        transform: translateY(-2px);
    }
    
    .navbar-nav .nav-item .nav-link.active {
        background-color: rgba(255, 255, 255, 0.2);
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .navbar-brand {
        font-weight: 700;
        font-size: 1.5rem;
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 0;
    }
    
    .navbar-brand i {
        margin-right: 10px;
        font-size: 1.75rem;
    }

    /* Cards with Subtle Hover Effects */
    .card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
        overflow: hidden;
        margin-bottom: 25px;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: var(--hover-shadow);
    }
    
    .card-header {
        background-color: white;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 20px 25px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .card-title {
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 0;
    }
    
    .card-body {
        padding: 25px;
    }

    /* Section Titles */
    .section-title {
        display: flex;
        align-items: center;
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .section-title i {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-right: 10px;
        font-size: 1.5rem;
    }
    
    /* Dashboard Stats Cards */
    .stats-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 30px;
    }
    
    .stats-card {
        flex: 1;
        min-width: 220px;
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--hover-shadow);
    }
    
    .stats-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-right: 20px;
        background: var(--primary-light);
        color: var(--primary-color);
    }
    
    .stats-icon.success {
        background: #ecfdf5;
        color: var(--success-color);
    }
    
    .stats-icon.warning {
        background: #fffbeb;
        color: var(--warning-color);
    }
    
    .stats-icon.danger {
        background: #fee2e2;
        color: var(--danger-color);
    }
    
    .stats-info h4 {
        font-size: 1.25rem;
        font-weight: 700;
        margin: 0 0 5px;
    }
    
    .stats-info p {
        font-size: 0.875rem;
        color: var(--grey-color);
        margin: 0;
    }
    
    .stats-change {
        display: flex;
        align-items: center;
        margin-top: 5px;
        font-size: 0.875rem;
    }
    
    .stats-change.positive {
        color: var(--success-color);
    }
    
    .stats-change.negative {
        color: var(--danger-color);
    }

    /* Feature Cards */
    .feature-card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
        height: 100%;
        background: white;
        position: relative;
        overflow: hidden;
    }
    
    .feature-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    }
    
    .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--hover-shadow);
    }
    
    .feature-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 15px;
        background: var(--primary-light);
        color: var(--primary-color);
        transition: all 0.3s ease;
    }
    
    .feature-card:hover .feature-icon {
        transform: scale(1.1);
    }
    
    .feature-card h4 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 15px;
        color: var(--dark-color);
    }
    
    .feature-card p {
        color: var(--grey-color);
        margin-bottom: 20px;
    }
    
    /* User Profile Card */
    .user-profile-card {
        background: linear-gradient(135deg, #f9f9ff, #f0f4ff);
        border-radius: 15px;
        padding: 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .user-avatar-container {
        position: relative;
        width: 110px;
        height: 110px;
        margin: 0 auto 20px;
    }
    
    .user-avatar {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 40px;
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
        position: relative;
        z-index: 1;
    }
    
    .user-avatar-glow {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border-radius: 50%;
        background: radial-gradient(circle at center, rgba(79, 70, 229, 0.5) 0%, rgba(79, 70, 229, 0) 70%);
        animation: pulse 2s infinite;
    }

    .user-status {
        position: absolute;
        bottom: 5px;
        right: 5px;
        z-index: 2;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: var(--success-color);
        border: 3px solid white;
    }
    
    .user-info h4 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 5px;
        color: var(--dark-color);
    }
    
    .user-info p {
        color: var(--grey-color);
        margin-bottom: 20px;
    }
    
    .user-stats {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .stat-item {
        text-align: center;
    }

    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 0.875rem;
        color: var(--grey-color);
    }
    
    /* Smart Content Card */
    .smart-content-card {
        border-radius: 15px;
        overflow: hidden;
    }
    
    .smart-content-header {
        background: linear-gradient(45deg, #4338ca, #6366f1);
        padding: 25px;
        color: white;
    }
    
    .smart-content-header h3 {
        margin: 0;
        font-weight: 700;
    }
    
    .smart-content-header p {
        margin: 5px 0 0;
        opacity: 0.8;
    }
    
    .smart-content-body {
        padding: 0;
    }
    
    .smart-content-item {
        display: flex;
        align-items: center;
        padding: 20px 25px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .smart-content-item:last-child {
        border-bottom: none;
    }
    
    .smart-content-item:hover {
        background-color: #f8fafd;
    }
    
    .smart-content-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-right: 20px;
        flex-shrink: 0;
    }
    
    .smart-content-icon.article {
        background-color: #e0f2fe;
        color: #0ea5e9;
    }
    
    .smart-content-icon.video {
        background-color: #fef2f2;
        color: #ef4444;
    }
    
    .smart-content-icon.exercise {
        background-color: #ecfdf5;
        color: #10b981;
    }
    
    .smart-content-info {
        flex: 1;
    }
    
    .smart-content-info h4 {
        margin: 0 0 5px;
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .smart-content-info p {
        margin: 0;
        color: var(--grey-color);
        font-size: 0.875rem;
    }
    
    .smart-content-action {
        color: var(--primary-color);
        font-size: 18px;
    }
    
    /* Modern Buttons */
    .btn {
        font-weight: 600;
        padding: 12px 24px;
        border-radius: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .btn-primary {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        border: none;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }
    
    .btn-outline-primary {
        color: var(--primary-color);
        border: 2px solid var(--primary-color);
    }
    
    .btn-outline-primary:hover {
        background-color: var(--primary-color);
        color: white;
        transform: translateY(-2px);
    }
    
    .action-buttons .btn {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .action-buttons .btn i {
        margin-right: 8px;
    }
    
    /* Mood Tracker */
    .mood-tracker {
        background: linear-gradient(135deg, #f9f9ff, #f0f4ff);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 25px;
    }
    
    .mood-tracker-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    
    .mood-tracker-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--dark-color);
        margin: 0;
    }
    
    .mood-selector {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    
    .mood-option {
        flex: 1;
        text-align: center;
        padding: 15px 10px;
        background: white;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        margin: 0 5px;
    }
    
    .mood-option:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
    .mood-emoji {
        font-size: 1.75rem;
        margin-bottom: 8px;
    }
    
    .mood-label {
        font-size: 0.8rem;
        color: var(--grey-color);
    }
    
    .mood-active {
        border: 2px solid var(--primary-color);
        box-shadow: 0 5px 15px rgba(79, 70, 229, 0.15);
    }
    
    /* AI Assistant Widget */
    .ai-assistant {
        border-radius: 15px;
        overflow: hidden;
        background: white;
        margin-bottom: 25px;
        position: relative;
    }
    
    .ai-header {
        background: linear-gradient(45deg, #3b82f6, #8b5cf6);
        padding: 20px 25px;
        color: white;
        display: flex;
        align-items: center;
    }
    
    .ai-avatar {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.2);
        margin-right: 15px;
        font-size: 24px;
    }
    
    .ai-info h3 {
        margin: 0 0 5px;
        font-weight: 700;
        font-size: 1.25rem;
    }
    
    .ai-info p {
        margin: 0;
        opacity: 0.9;
        font-size: 0.875rem;
    }
    
    .ai-body {
        padding: 25px;
    }
    
    .ai-message {
        background: #f1f5f9;
        border-radius: 0 15px 15px 15px;
        padding: 15px 20px;
        margin-bottom: 10px;
        display: inline-block;
        max-width: 80%;
    }
    
    .ai-suggestion {
        margin-top: 20px;
    }
    
    .ai-suggestion-title {
        font-weight: 600;
        margin-bottom: 10px;
        color: var(--dark-color);
    }
    
    .suggestion-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .suggestion-chip {
        background: #f1f5f9;
        border-radius: 50px;
        padding: 8px 15px;
        font-size: 0.875rem;
        color: var(--dark-color);
        border: 1px solid transparent;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .suggestion-chip:hover {
        background: var(--primary-light);
        color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    /* Chat Input */
    .chat-input {
        display: flex;
        margin-top: 20px;
    }
    
    .chat-input input {
        flex: 1;
        border: 1px solid #e2e8f0;
        border-radius: 8px 0 0 8px;
        padding: 12px 15px;
        outline: none;
        transition: all 0.3s ease;
    }
    
    .chat-input input:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }
    
    .chat-input button {
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 0 8px 8px 0;
        padding: 0 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .chat-input button:hover {
        background: var(--secondary-color);
    }
    
    /* Activity Timeline */
    .timeline-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        margin-bottom: 25px;
    }
    
    .timeline-header {
        background: linear-gradient(45deg, #10b981, #34d399);
        padding: 20px 25px;
        color: white;
    }
    
    .timeline-header h3 {
        margin: 0;
        font-weight: 700;
    }
    
    .timeline-body {
        padding: 0;
    }
    
    .timeline-item {
        position: relative;
        padding: 20px 25px 20px 60px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .timeline-item:last-child {
        border-bottom: none;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 25px;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #e2e8f0;
    }
    
    .timeline-icon {
        position: absolute;
        left: 19px;
        top: 22px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: white;
        border: 2px solid var(--primary-color);
        z-index: 1;
    }
    
    .timeline-content h4 {
        margin: 0 0 5px;
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .timeline-content p {
        margin: 0 0 5px;
        color: var(--grey-color);
        font-size: 0.875rem;
    }
    
    .timeline-time {
        font-size: 0.75rem;
        color: #94a3b8;
    }
    
    /* Daily Tip Section */
    .daily-tip-section {
        background: linear-gradient(135deg, #f8fafc, #e0f2fe);
        border-radius: 15px;
        padding: 30px;
        margin-top: 40px;
        box-shadow: var(--card-shadow);
        position: relative;
        overflow: hidden;
    }
    
    .daily-tip {
        font-style: italic;
        color: #4b5563;
        border-left: 4px solid var(--primary-color);
        padding-left: 20px;
        margin: 20px 0;
        position: relative;
        z-index: 1;
    }
    
    .daily-tip-image {
        max-height: 200px;
        position: relative;
        z-index: 1;
    }
    
    .daily-tip-background {
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle at center, rgba(79, 70, 229, 0.1) 0%, rgba(79, 70, 229, 0) 70%);
        border-radius: 50%;
        z-index: 0;
    }
    
    /* Upcoming Events */
    .event-card {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: white;
        border-radius: 12px;
        margin-bottom: 15px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .event-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
    .event-date {
        width: 60px;
        height: 75px;
        background: var(--primary-light);
        color: var(--primary-color);
        border-radius: 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        flex-shrink: 0;
    }
    
    .event-month {
        font-size: 0.75rem;
        text-transform: uppercase;
    }
    
    .event-day {
        font-size: 1.5rem;
    }
    
    .event-info {
        flex: 1;
    }
    
    .event-info h4 {
        margin: 0 0 5px;
        font-weight: 600;
        color: var(--dark-color);
    }
    
    .event-info p {
        margin: 0;
        color: var(--grey-color);
        font-size: 0.875rem;
        display: flex;
        align-items: center;
    }
    
    .event-info p i {
        margin-right: 5px;
        font-size: 0.75rem;
    }
    
    .event-action {
        color: var(--primary-color);
        font-size: 1.25rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .event-action:hover {
        color: var(--secondary-color);
        transform: scale(1.1);
    }
    
    /* Animations */
    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 0.5;
        }
        70% {
            transform: scale(1.3);
            opacity: 0;
        }
        100% {
            transform: scale(1);
            opacity: 0;
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideRight {
        from { transform: translateX(-20px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideUp {
        from { transform: translateY(20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
        .stats-row {
            flex-wrap: wrap;
        }
        
        .stats-card {
            min-width: calc(50% - 10px);
        }
    }
    
    @media (max-width: 768px) {
        .stats-card {
            min-width: 100%;
        }
        
        .mood-selector {
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .mood-option {
            min-width: calc(33.33% - 10px);
            margin-bottom: 10px;
        }
    }

    /* How It Works Section */
    .how-it-works-section {
        padding: 40px 0;
    }

    .feature-progress {
        margin-top: 15px;
        background: #f1f5f9;
        border-radius: 10px;
        height: 6px;
        position: relative;
    }

    .feature-progress .progress-bar {
        height: 100%;
        border-radius: 10px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        transition: width 1s ease-in-out;
    }

    .feature-progress span {
        position: absolute;
        right: 0;
        top: -25px;
        font-size: 0.875rem;
        color: var(--primary-color);
        font-weight: 600;
    }

    /* Process Flow */
    .process-flow {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 40px 0;
    }

    .process-step {
        flex: 1;
        text-align: center;
        position: relative;
        opacity: 0.7;
        transition: all 0.3s ease;
    }

    .process-step.active {
        opacity: 1;
        transform: scale(1.05);
    }

    .step-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-light), #fff);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 24px;
        color: var(--primary-color);
        box-shadow: 0 5px 15px rgba(79, 70, 229, 0.15);
        transition: all 0.3s ease;
    }

    .process-step:hover .step-icon {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.2);
    }

    .process-connector {
        flex: 0.5;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        position: relative;
        margin-top: -50px;
    }

    .process-connector::after {
        content: '';
        position: absolute;
        top: 50%;
        right: -5px;
        transform: translateY(-50%);
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: var(--secondary-color);
    }

    /* Smart Feature Cards */
    .smart-feature-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        height: 100%;
        position: relative;
        overflow: hidden;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
    }

    .smart-feature-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--hover-shadow);
    }

    .feature-icon-wrapper {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        background: linear-gradient(135deg, var(--primary-light), #fff);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: var(--primary-color);
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }

    .smart-feature-card:hover .feature-icon-wrapper {
        transform: scale(1.1) rotate(5deg);
    }

    .feature-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: linear-gradient(90deg, #f97316, #fb923c);
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    /* Add these new animation styles after your existing animations */
    .animate-text {
        opacity: 0;
        transform: translateY(20px);
        animation: textFadeIn 0.8s ease forwards;
    }

    .animate-text.delay-1 { animation-delay: 0.2s; }
    .animate-text.delay-2 { animation-delay: 0.4s; }
    .animate-text.delay-3 { animation-delay: 0.6s; }

    @keyframes textFadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes textFadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }

    /* Add a class for text that fades in from left */
    .animate-text-left {
        opacity: 0;
        transform: translateX(-30px);
        animation: textFadeInLeft 0.8s ease forwards;
    }

    @keyframes textFadeInLeft {
        from {
            opacity: 0;
            transform: translateX(-30px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    /* Add these new footer styles */
    .footer {
        background: linear-gradient(135deg, #1e40af, #3b82f6);
        color: #fff;
        padding: 50px 0 20px;
        margin-top: 60px;
        box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.1);
    }

    .footer h5 {
        color: #fff;
        font-weight: 600;
        margin-bottom: 20px;
        position: relative;
        padding-bottom: 10px;
    }

    .footer h5::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 40px;
        height: 3px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 2px;
    }

    .footer p {
        color: rgba(255, 255, 255, 0.8);
        line-height: 1.6;
    }

    .footer .list-unstyled li {
        margin-bottom: 10px;
    }

    .footer .list-unstyled a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
    }

    .footer .list-unstyled a:hover {
        color: #fff;
        transform: translateX(5px);
    }

    .footer .social-links a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        color: #fff;
        text-decoration: none;
        transition: all 0.3s ease;
        margin-right: 10px;
    }

    .footer .social-links a:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .footer hr {
        border-color: rgba(255, 255, 255, 0.1);
        margin: 30px 0;
    }

    .footer .text-center {
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.875rem;
    }

    /* Add animation for social icons */
    .footer .social-links a i {
        transition: transform 0.3s ease;
    }

    .footer .social-links a:hover i {
        transform: scale(1.2);
    }

    /* Add subtle hover effect for links */
    .footer a {
        position: relative;
    }

    .footer a::before {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 0;
        height: 1px;
        background: #fff;
        transition: width 0.3s ease;
    }

    .footer a:hover::before {
        width: 100%;
    }

    /* Add these styles to your existing CSS */
    .admin-contact-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        background: linear-gradient(135deg, #ffffff, #f8fafc);
    }

    .admin-avatar {
        width: 80px;
        height: 80px;
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        margin: 0 auto;
    }

    .admin-name {
        color: var(--dark-color);
        margin-bottom: 5px;
    }

    .admin-title {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 10px;
    }

    .admin-email {
        color: var(--grey-color);
        margin-bottom: 0;
    }

    .message-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
        max-height: 500px;
        overflow-y: auto;
        padding: 20px;
        background-color: #f8fafc;
        border-radius: 15px;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .message-item {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: transform 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .message-item:hover {
        transform: translateY(-2px);
    }

    .message-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .message-type {
        color: var(--grey-color);
        font-size: 0.9rem;
        margin-bottom: 10px;
    }

    .message-content {
        color: var(--dark-color);
        margin-bottom: 15px;
        line-height: 1.5;
    }

    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-badge.pending {
        background-color: #fff3cd;
        color: #856404;
    }

    .status-badge.responded {
        background-color: #d4edda;
        color: #155724;
    }

    .admin-reply {
        background: #f8f9fa;
        border-left: 4px solid var(--primary-color);
        padding: 15px;
        margin-top: 15px;
        border-radius: 0 8px 8px 0;
    }

    .reply-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        color: var(--primary-color);
    }

    .reply-date {
        font-size: 0.85rem;
        color: var(--grey-color);
    }

    .reply-content {
        color: var(--dark-color);
        line-height: 1.5;
    }

    /* Empty state styling */
    .text-center .fas.fa-inbox {
        color: var(--grey-color);
    }

    /* Success Animation Modal */
    .success-animation-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    .success-animation {
        background: white;
        padding: 40px;
        border-radius: 20px;
        text-align: center;
        transform: scale(0.7);
        opacity: 0;
        transition: all 0.3s ease;
    }

    .success-animation.show {
        transform: scale(1);
        opacity: 1;
    }

    .success-checkmark {
        width: 80px;
        height: 80px;
        margin: 0 auto;
        position: relative;
    }

    .check-icon {
        width: 80px;
        height: 80px;
        position: relative;
        border-radius: 50%;
        box-sizing: content-box;
        border: 4px solid #4CAF50;
    }

    .check-icon::before {
        top: 3px;
        left: -2px;
        width: 30px;
        transform-origin: 100% 50%;
        border-radius: 100px 0 0 100px;
    }

    .check-icon::after {
        top: 0;
        left: 30px;
        width: 60px;
        transform-origin: 0 50%;
        border-radius: 0 100px 100px 0;
        animation: rotate-circle 4.25s ease-in;
    }

    .check-icon::before, .check-icon::after {
        content: '';
        height: 100px;
        position: absolute;
        background: #FFFFFF;
        transform: rotate(-45deg);
    }

    .icon-line {
        height: 5px;
        background-color: #4CAF50;
        display: block;
        border-radius: 2px;
        position: absolute;
        z-index: 10;
    }

    .icon-line.line-tip {
        top: 46px;
        left: 14px;
        width: 25px;
        transform: rotate(45deg);
        animation: icon-line-tip 0.75s;
    }

    .icon-line.line-long {
        top: 38px;
        right: 8px;
        width: 47px;
        transform: rotate(-45deg);
        animation: icon-line-long 0.75s;
    }

    .icon-circle {
        top: -4px;
        left: -4px;
        z-index: 10;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        position: absolute;
        box-sizing: content-box;
        border: 4px solid rgba(76, 175, 80, 0.5);
    }

    .icon-fix {
        top: 8px;
        width: 5px;
        left: 26px;
        z-index: 1;
        height: 85px;
        position: absolute;
        transform: rotate(-45deg);
        background-color: #FFFFFF;
    }

    @keyframes rotate-circle {
        0% {
            transform: rotate(-45deg);
        }
        5% {
            transform: rotate(-45deg);
        }
        12% {
            transform: rotate(-405deg);
        }
        100% {
            transform: rotate(-405deg);
        }
    }

    @keyframes icon-line-tip {
        0% {
            width: 0;
            left: 1px;
            top: 19px;
        }
        54% {
            width: 0;
            left: 1px;
            top: 19px;
        }
        70% {
            width: 50px;
            left: -8px;
            top: 37px;
        }
        84% {
            width: 17px;
            left: 21px;
            top: 48px;
        }
        100% {
            width: 25px;
            left: 14px;
            top: 46px;
        }
    }

    @keyframes icon-line-long {
        0% {
            width: 0;
            right: 46px;
            top: 54px;
        }
        65% {
            width: 0;
            right: 46px;
            top: 54px;
        }
        84% {
            width: 55px;
            right: 0px;
            top: 35px;
        }
        100% {
            width: 47px;
            right: 8px;
            top: 38px;
        }
    }

    .success-title {
        color: #4CAF50;
        margin: 20px 0 10px;
        font-weight: 700;
        opacity: 0;
        transform: translateY(10px);
        animation: fade-in-up 0.5s forwards;
        animation-delay: 0.75s;
    }

    .success-message {
        color: #666;
        opacity: 0;
        transform: translateY(10px);
        animation: fade-in-up 0.5s forwards;
        animation-delay: 1s;
    }

    @keyframes fade-in-up {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Enhanced Form Styling */
    .card-body {
        padding: 2rem;
    }

    .form-control, .form-select {
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        line-height: 1.5;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        outline: none;
    }

    .form-label {
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 0.5rem;
        display: block;
    }

    textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }

    .btn-primary {
        padding: 1rem 2rem;
        font-size: 1rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    /* Form Spacing */
    .mb-4 {
        margin-bottom: 1.5rem;
    }

    /* Placeholder styling */
    ::placeholder {
        color: #a0aec0;
        opacity: 0.7;
    }

    /* Card header styling */
    .card-header {
        padding: 1.5rem 2rem;
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
    }

    .card-header h4 {
        color: white;
        margin: 0;
    }

    /* Custom scrollbar styling */
    .message-list::-webkit-scrollbar {
        width: 8px;
    }

    .message-list::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }

    .message-list::-webkit-scrollbar-thumb {
        background: var(--primary-color);
        border-radius: 4px;
    }

    .message-list::-webkit-scrollbar-thumb:hover {
        background: var(--secondary-color);
    }

    /* Dark mode overrides for this page */
    body.dark-mode {
        background-color: #121212;
        color: #f8fafc;
    }

    body.dark-mode .card,
    body.dark-mode .stats-card,
    body.dark-mode .feature-card {
        background-color: #1e1e1e;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
    }

    body.dark-mode .card-header {
        background-color: #1e1e1e;
        border-bottom: 1px solid #333;
    }

    body.dark-mode .section-title {
        color: #f8fafc;
        border-bottom: 2px solid #333;
    }

    body.dark-mode .feature-card::before {
        background: linear-gradient(90deg, #6366f1, #8b5cf6);
    }

    body.dark-mode .text-muted,
    body.dark-mode .stats-info p,
    body.dark-mode .card-text {
        color: #adbac7 !important;
    }

    /* Dark mode toggle specific for this page */
    .dark-mode-toggle {
        position: relative;
        width: 60px;
        height: 30px;
        border-radius: 30px;
        background: #f1f5f9;
        transition: all 0.3s ease;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 5px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .dark-mode-toggle::before {
        content: '';
        position: absolute;
        left: 2px;
        top: 2px;
        width: 26px;
        height: 26px;
        background-color: white;
        border-radius: 50%;
        transition: transform 0.3s ease, background-color 0.3s ease;
        z-index: 2;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    body.dark-mode .dark-mode-toggle {
        background: #0f172a;
    }

    body.dark-mode .dark-mode-toggle::before {
        transform: translateX(30px);
        background-color: #1e293b;
    }

    .dark-mode-toggle .fa-moon {
        color: #4f46e5;
        font-size: 14px;
        z-index: 1;
        margin-right: 5px;
    }

    .dark-mode-toggle .fa-sun {
        color: #f59e0b;
        font-size: 14px;
        z-index: 1;
        margin-left: 5px;
    }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <!-- Admin Contact Card with Messages -->
        <div class="col-md-4">
            <div class="card admin-contact-card">
                <div class="card-body text-center">
                    <div class="admin-avatar mb-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <h5 class="admin-name">Support Team</h5>
                    <p class="admin-title">24/7 Customer Support</p>
                    <p class="admin-email">
                        <i class="fas fa-envelope me-2"></i>
                        support@mentalhealth.com
                    </p>
                </div>
            </div>
            
            <!-- Message History -->
            <div class="message-list mt-4">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php foreach ($messages as $row): ?>
                        <div class="message-item">
                            <div class="message-header">
                                <div>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($row['type']); ?></span>
                                    <span class="ms-2 text-muted"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></span>
                                </div>
                                <span class="status-badge <?php echo empty($row['reply_text']) ? 'pending' : 'responded'; ?>">
                                    <?php echo empty($row['reply_text']) ? 'Pending' : 'Responded'; ?>
                                </span>
                            </div>
                            <h5 class="message-subject"><?php echo htmlspecialchars($row['subject']); ?></h5>
                            <p class="message-content"><?php echo htmlspecialchars(stripslashes($row['message'])); ?></p>
                            <?php if (!empty($row['reply_text'])): ?>
                                <div class="admin-reply">
                                    <div class="reply-header">
                                        <span><i class="fas fa-reply me-2"></i>Admin Response</span>
                                        <span class="reply-date"><?php echo date('M d, Y H:i', strtotime($row['reply_date'])); ?></span>
                                    </div>
                                    <p class="reply-content"><?php echo htmlspecialchars(stripslashes($row['reply_text'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <h5>No Messages Yet</h5>
                        <p class="text-muted">Your message history will appear here</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Send us a Message</h4>
                </div>
                <div class="card-body">
                    <form action="contact.php" method="POST">
                        <input type="hidden" name="action" value="submit_feedback">
                        
                        <div class="mb-4">
                            <label for="type" class="form-label">Message Type</label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="">Select message type</option>
                                <option value="feedback">Feedback</option>
                                <option value="bug">Report an Issue</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required 
                                   placeholder="Enter your subject">
                        </div>

                        <div class="mb-4">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required 
                                      placeholder="Type your message here..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3">
                            <i class="fas fa-paper-plane me-2"></i>Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animate process steps on scroll
        const processSteps = document.querySelectorAll('.process-step');
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px'
        };

        const processObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, observerOptions);

        processSteps.forEach(step => {
            processObserver.observe(step);
        });

        // Animate feature progress bars
        const progressBars = document.querySelectorAll('.feature-progress .progress-bar');
        const progressObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const width = entry.target.style.width;
                    entry.target.style.width = '0%';
                    setTimeout(() => {
                        entry.target.style.width = width;
                    }, 100);
                }
            });
        }, observerOptions);

        progressBars.forEach(bar => {
            progressObserver.observe(bar);
        });
    });
</script>

<!-- Add this HTML for the success animation modal right before the closing </body> tag -->
<div class="success-animation-modal" id="successModal">
    <div class="success-animation">
        <div class="success-checkmark">
            <div class="check-icon">
                <span class="icon-line line-tip"></span>
                <span class="icon-line line-long"></span>
                <div class="icon-circle"></div>
                <div class="icon-fix"></div>
            </div>
        </div>
        <h3 class="success-title">Thank You!</h3>
        <p class="success-message">Your feedback has been sent successfully</p>
    </div>
</div>

<!-- Add this JavaScript before the closing </body> tag -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if there's a success message
        <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
            showSuccessAnimation();
        <?php endif; ?>

        function showSuccessAnimation() {
            const modal = document.getElementById('successModal');
            const animation = modal.querySelector('.success-animation');
            
            modal.style.display = 'flex';
            
            // Trigger reflow to ensure animation plays
            void modal.offsetWidth;
            
            // Add show class after a brief delay
            setTimeout(() => {
                animation.classList.add('show');
            }, 10);

            // Hide modal and redirect after animation
            setTimeout(() => {
                animation.classList.remove('show');
                setTimeout(() => {
                    modal.style.display = 'none';
                    window.location.href = 'contact.php';
                }, 300);
            }, 3000);
        }
    });
</script>
</body>
</html> 