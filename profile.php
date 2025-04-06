<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

// Error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session only if one doesn't already exist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];

// Check if avatar column exists
$check_column = $conn->query("SHOW COLUMNS FROM users LIKE 'avatar'");
if ($check_column->num_rows > 0) {
    $query = "SELECT username, email, phone, avatar FROM users WHERE id = ?";
} else {
    // If avatar column doesn't exist, use profile_pic instead (based on your database schema)
    $query = "SELECT username, email, phone, profile_pic AS avatar FROM users WHERE id = ?";
}

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Initialize the show_welcome variable
$show_welcome = false;
if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome']) {
    $show_welcome = true;
    // Reset the flag so it only shows once
    $_SESSION['show_welcome'] = false;
}

// Initialize message variable
$message = "";
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Add near the top of your PHP code
$upload_dir = 'uploads/avatars/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Also create a default avatar image if needed
$default_avatar = $upload_dir . 'default.png';
if (!file_exists($default_avatar)) {
    // You could copy a default image here or create a placeholder
    // For now, we'll use the URL fallback in the get_avatar_url function
}

/**
 * Get avatar URL for a user
 * 
 * @param array $user User data array
 * @return string URL to avatar image
 */
function get_avatar_url($user) {
    $avatar_path = 'uploads/avatars/';
    $default_avatar = 'default.png';
    
    // Get user's avatar or use default
    $avatar = isset($user['avatar']) && !empty($user['avatar']) ? $user['avatar'] : $default_avatar;
    
    // Check if file exists, otherwise use default
    $avatar_file = $avatar_path . $avatar;
    if (!file_exists($avatar_file)) {
        $avatar_file = $avatar_path . $default_avatar;
        // If even default doesn't exist, use a placeholder
        if (!file_exists($avatar_file)) {
            return 'https://via.placeholder.com/150';
        }
    }
    
    return $avatar_file;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Mental Health Chatbot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
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
        position: relative;
        font-weight: 700;
        transform: translateY(-2px);
    }
    
    .navbar-nav .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 5px;
        left: 50%;
        transform: translateX(-50%);
        width: 20px;
        height: 3px;
        background-color: #ffffff;
        border-radius: 3px;
    }
    
    .navbar-nav .nav-link.active i {
        transform: scale(1.2);
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
        position: relative;
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        border: none;
        color: white;
        font-weight: 600;
        overflow: hidden;
        z-index: 1;
        transition: all 0.3s ease;
        transform-style: preserve-3d;
        box-shadow: 0 6px 0 darken(#4f46e5, 10%);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 0 darken(#4f46e5, 10%);
    }
    
    .btn-primary:active {
        transform: translateY(3px);
        box-shadow: 0 3px 0 darken(#4f46e5, 10%);
    }
    
    .btn-primary::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, 
            rgba(255,255,255,0) 0%, 
            rgba(255,255,255,0.2) 50%, 
            rgba(255,255,255,0) 100%);
        z-index: 1;
        transition: all 0.6s ease;
    }
    
    .btn-primary:hover::after {
        left: 100%;
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

    /* Modern Profile UI */
    .profile-container {
        max-width: 1000px;
        margin: 50px auto;
    }

    .profile-container h1 {
        font-weight: 700;
        color: var(--dark-color);
        text-align: center;
        margin-bottom: 30px;
        position: relative;
        padding-bottom: 15px;
    }

    .profile-container h1:after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        border-radius: 2px;
    }
    
    /* Profile Sidebar */
    .profile-sidebar {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: var(--card-shadow);
        text-align: center;
        height: 100%;
    }

    .profile-image-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto 20px;
        border-radius: 50%;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .profile-image-container::before {
        content: '';
        position: absolute;
        top: -5px;
        left: -5px;
        right: -5px;
        bottom: -5px;
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        border-radius: 50%;
        z-index: -1;
        opacity: 0;
        transition: all 0.3s ease;
    }

    .profile-image-container:hover::before {
        opacity: 1;
        animation: spin 4s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .profile-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: all 0.5s ease;
        border: 4px solid white;
    }
    
    .profile-image-container:hover .profile-avatar {
        transform: scale(1.05);
    }
    
    .avatar-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.5);
        overflow: hidden;
        height: 0;
        transition: .5s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .profile-image-container:hover .avatar-overlay {
        height: 40px;
    }
    
    .avatar-change-btn {
        color: white;
        font-size: 18px;
        cursor: pointer;
        margin: 0;
    }
    
    .profile-name {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 5px;
    }
    
    .profile-email {
        color: var(--grey-color);
        margin-bottom: 15px;
    }
    
    .profile-status {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.875rem;
    }
    
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 8px;
    }
    
    .status-dot.active {
        position: relative;
        background-color: var(--success-color);
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
    }
    
    .status-dot.active::before {
        content: '';
        position: absolute;
        top: -4px;
        left: -4px;
        right: -4px;
        bottom: -4px;
        border-radius: 50%;
        border: 2px solid var(--success-color);
        opacity: 0;
        animation: statusPulse 2s infinite;
    }
    
    @keyframes statusPulse {
        0% {
            transform: scale(0.8);
            opacity: 0.8;
        }
        50% {
            transform: scale(1.5);
            opacity: 0;
        }
        100% {
            transform: scale(0.8);
            opacity: 0;
        }
    }
    
    /* Profile Content */
    .profile-content .card {
        border: none;
        border-radius: 15px;
        box-shadow: var(--card-shadow);
        margin-bottom: 25px;
        height: 100%;
    }
    
    .profile-content .card-header {
        background: white;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 20px 25px;
    }
    
    .profile-content .card-header h5 {
        color: var(--dark-color);
        font-weight: 600;
        display: flex;
        align-items: center;
    }
    
    .profile-content .card-header h5 i {
        position: relative;
        color: var(--primary-color);
        font-size: 18px;
        margin-right: 10px;
    }
    
    .profile-content .card-header h5 i::after {
        content: '';
        position: absolute;
        top: -5px;
        left: -5px;
        right: -5px;
        bottom: -5px;
        background: radial-gradient(circle, rgba(79, 70, 229, 0.4) 0%, rgba(79, 70, 229, 0) 70%);
        border-radius: 50%;
        z-index: -1;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .profile-content .card:hover .card-header h5 i::after {
        opacity: 1;
        animation: pulse 2s infinite;
    }
    
    .profile-content .card-body {
        padding: 30px;
    }
    
    .profile-content .form-label {
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 8px;
    }
    
    .profile-content .input-group-text {
        width: 45px;
        justify-content: center;
        font-size: 1rem;
    }

    .profile-content .input-group-text i {
        font-size: 16px;
        transition: transform 0.3s ease;
    }
    
    .profile-content .form-group:hover .input-group-text i {
        transform: scale(1.2) rotate(5deg);
    }
    
    .profile-content .form-control {
        border: 1px solid #e2e8f0;
        padding: 12px 15px;
        border-radius: 0 8px 8px 0;
        font-size: 0.95rem;
    }
    
    .profile-content .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.15);
        transform: translateY(-2px);
    }
    
    .profile-content .btn-primary {
        background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        border: none;
        padding: 12px 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .profile-content .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
    }

    .profile-content .btn-outline-secondary {
        border: 2px solid #e2e8f0;
        color: var(--grey-color);
        font-weight: 600;
        padding: 11px 25px;
    }
    
    .profile-content .btn-outline-secondary:hover {
        background-color: #f8fafc;
        color: var(--dark-color);
        border-color: #cbd5e1;
    }
    
    @media (max-width: 992px) {
        .profile-sidebar {
            margin-bottom: 30px;
        }
    }

    /* Success Animation Overlay */
    .success-animation-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
        backdrop-filter: blur(5px);
    }
    
    .success-animation-overlay.active {
        opacity: 1;
    }
    
    /* Success Animation Container */
    .success-animation {
        background: white;
        border-radius: 15px;
        padding: 40px;
        text-align: center;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        position: relative;
        transform: scale(0.8);
        opacity: 0;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        max-width: 400px;
        width: 90%;
    }
    
    .success-animation-overlay.active .success-animation {
        transform: scale(1);
        opacity: 1;
    }
    
    /* Success Icon */
    .success-icon {
        position: relative;
        width: 100px;
        height: 100px;
        margin: 0 auto 30px;
    }
    
    /* Checkmark animation */
    .checkmark-circle {
        stroke-dasharray: 240;
        stroke-dashoffset: 240;
        stroke-width: 4;
        stroke: var(--success-color);
        fill: none;
        animation: stroke-circle 1s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    
    .checkmark-check {
        stroke-dasharray: 70;
        stroke-dashoffset: 70;
        stroke: var(--success-color);
        stroke-width: 4;
        fill: none;
        animation: stroke-check 0.8s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }
    
    @keyframes stroke-circle {
        100% {
            stroke-dashoffset: 0;
        }
    }
    
    @keyframes stroke-check {
        100% {
            stroke-dashoffset: 0;
        }
    }
    
    /* Success Text */
    .success-text h3 {
        color: var(--dark-color);
        font-weight: 700;
        margin-bottom: 10px;
        opacity: 0;
        transform: translateY(20px);
        animation: fade-in-up 0.6s ease 1.2s forwards;
    }
    
    .success-text p {
        color: var(--grey-color);
        opacity: 0;
        transform: translateY(20px);
        animation: fade-in-up 0.6s ease 1.4s forwards;
    }
    
    @keyframes fade-in-up {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Ripple Effect */
    .ripple-container {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
    
    .success-ripple {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80px;
        height: 80px;
        border-radius: 50%;
        border: 2px solid var(--success-color);
        opacity: 0;
        animation: ripple 2s cubic-bezier(0.215, 0.61, 0.355, 1) forwards;
    }
    
    @keyframes ripple {
        0% {
            opacity: 0.5;
            transform: scale(1);
        }
        100% {
            opacity: 0;
            transform: scale(2.5);
        }
    }
    
    /* Confetti Animation */
    .confetti-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        pointer-events: none;
    }
    
    .confetti {
        position: absolute;
        width: 10px;
        height: 10px;
        background: var(--primary-color);
        top: -10px;
        animation: confetti-fall 5s linear infinite;
    }
    
    @keyframes confetti-fall {
        0% {
            top: -10px;
            transform: rotate(0deg) translateX(0);
            opacity: 1;
        }
        100% {
            top: 100%;
            transform: rotate(720deg) translateX(100px);
            opacity: 0;
        }
    }
    
    /* Ripple effect for buttons */
    .btn {
        position: relative;
        overflow: hidden;
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.4);
        width: 100px;
        height: 100px;
        margin-top: -50px;
        margin-left: -50px;
        animation: ripple 1s;
        opacity: 0;
    }
    
    @keyframes ripple {
        from {
            opacity: 1;
            transform: scale(0);
        }
        to {
            opacity: 0;
            transform: scale(3);
        }
    }

    /* Floating animation for success icon */
    .success-animation-overlay.active .success-icon {
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    /* Sparkle effect */
    .success-animation::before,
    .success-animation::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: rgba(79, 70, 229, 0.2);
        animation: sparkle 2s linear infinite;
    }
    
    .success-animation::before {
        top: 20px;
        right: 20px;
    }
    
    .success-animation::after {
        bottom: 20px;
        left: 20px;
        animation-delay: 1s;
    }
    
    @keyframes sparkle {
        0%, 100% {
            opacity: 0.2;
            transform: scale(1);
        }
        50% {
            opacity: 0.7;
            transform: scale(1.5);
        }
    }
    
    /* Fixed Icon Sizing - Add this at the end of your existing styles */
    .input-group {
        height: 50px;
    }
    
    .input-group-text {
        width: 50px !important;
        height: 100% !important;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
    }
    
    .input-group-text i {
        font-size: 18px;
        color: var(--primary-color);
    }
    
    .form-control {
        height: 50px;
        line-height: 50px;
        padding: 0 15px;
    }
    
    /* Input group hover effects */
    .input-group:hover .input-group-text {
        background-color: var(--primary-light);
    }
    
    .input-group:hover .input-group-text i {
        transform: scale(1.2) rotate(5deg);
        transition: transform 0.3s ease;
    }
    
    .input-group:focus-within .input-group-text {
        background-color: var(--primary-light);
        border-color: var(--primary-color);
    }

    /* Dark Mode Toggle Styles */
    .dark-mode-toggle {
        position: relative;
        padding: 8px 15px;
        border-radius: 30px;
        background: #f1f5f9;
        transition: all 0.3s ease;
        cursor: pointer;
        display: inline-block;
    }

    .dark-mode-toggle:hover {
        background: var(--primary-light);
    }

    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
        cursor: pointer;
    }

    .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .dark-icon, .light-icon {
        font-size: 1.2rem;
        transition: all 0.3s ease;
    }

    .dark-icon {
        color: #1e293b;
    }

    .light-icon {
        color: #f59e0b;
        display: none;
    }

    /* Dark Mode Styles */
    body.dark-mode {
        background-color: var(--dark-bg-primary);
        color: var(--dark-text-primary);
    }

    body.dark-mode .navbar {
        background: linear-gradient(90deg, #111827, #1e3a8a);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    body.dark-mode .card, 
    body.dark-mode .profile-sidebar {
        background-color: var(--dark-bg-secondary);
        box-shadow: var(--dark-card-shadow);
        border: 1px solid var(--dark-border);
    }

    body.dark-mode .card-header {
        background-color: rgba(0, 0, 0, 0.2);
        border-bottom: 1px solid var(--dark-border);
    }

    body.dark-mode .card-title,
    body.dark-mode .section-title,
    body.dark-mode .profile-name,
    body.dark-mode .form-label,
    body.dark-mode h1, 
    body.dark-mode h2, 
    body.dark-mode h3, 
    body.dark-mode h4, 
    body.dark-mode h5, 
    body.dark-mode h6 {
        color: var(--dark-text-primary);
    }

    body.dark-mode .profile-email,
    body.dark-mode .form-text,
    body.dark-mode p {
        color: var(--dark-text-secondary);
    }

    body.dark-mode .input-group-text {
        background-color: var(--dark-bg-tertiary);
        color: var(--dark-text-primary);
        border-color: var(--dark-border);
    }

    body.dark-mode .form-control {
        background-color: var(--dark-bg-tertiary);
        border-color: var(--dark-border);
        color: var(--dark-text-primary);
    }

    body.dark-mode .form-control:focus {
        background-color: var(--dark-bg-tertiary);
        color: var(--dark-text-primary);
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(79, 70, 229, 0.25);
    }

    body.dark-mode .btn-outline-secondary {
        color: var(--dark-text-secondary);
        border-color: var(--dark-border);
    }

    body.dark-mode .btn-outline-secondary:hover {
        background-color: var(--dark-bg-tertiary);
        color: var(--dark-text-primary);
    }

    body.dark-mode .profile-status {
        background: var(--dark-bg-tertiary);
    }

    body.dark-mode .dark-icon {
        display: none;
    }

    body.dark-mode .light-icon {
        display: inline-block;
    }

    body.dark-mode .dark-mode-toggle {
        background: var(--dark-bg-tertiary);
    }

    body.dark-mode .footer {
        background: linear-gradient(135deg, #111827, #1e3a8a);
    }

    /* Dark mode transition */
    body, .navbar, .card, .profile-sidebar, .footer, 
    .input-group-text, .form-control, .btn, 
    h1, h2, h3, h4, h5, h6, p, .profile-email, 
    .profile-name, .form-label, .dark-mode-toggle {
        transition: all 0.3s ease;
    }

    /* Add dark mode overrides for this page */
    body.dark-mode {
        background-color: var(--dark-bg-primary);
        color: var(--dark-text-primary);
    }

    body.dark-mode .card,
    body.dark-mode .form-container {
        background-color: var(--dark-bg-secondary);
        box-shadow: var(--dark-card-shadow);
        border: 1px solid var(--dark-border);
    }
    
    body.dark-mode .form-control,
    body.dark-mode .form-select {
        background-color: var(--dark-bg-tertiary);
        border-color: var(--dark-border);
        color: var(--dark-text-primary);
    }
    
    body.dark-mode .avatar-preview {
        background-color: var(--dark-bg-tertiary);
        border-color: var(--dark-border);
    }
    
    body.dark-mode .password-toggle-icon {
        color: var(--dark-text-secondary);
    }
    
    body.dark-mode label {
        color: var(--dark-text-secondary);
    }
    
    body.dark-mode .card-header {
        background-color: rgba(0, 0, 0, 0.2);
        border-bottom: 1px solid var(--dark-border);
    }
    
    body.dark-mode .section-title {
        border-bottom: 2px solid var(--dark-border);
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

    .input-group:focus-within .input-group-text {
        background-color: var(--primary-light);
        border-color: var(--primary-color);
    }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container my-5">
    <h1 class="mb-4 fadeInDown">My Profile</h1>
    
    <?php if (isset($message) && $message): ?>
        <div class="alert <?php echo strpos($message, 'success') !== false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-4 fadeInLeft" style="--delay: 0.2s">
            <div class="profile-sidebar">
                <div class="profile-image-container">
                    <img src="<?php echo htmlspecialchars(get_avatar_url($user)); ?>" alt="Profile Image" id="avatar-preview" class="profile-avatar">
                    <div class="avatar-overlay">
                        <label for="avatar" class="avatar-change-btn">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>
    </div>
    
                
    
                <div class="profile-details mt-4">
                    <h3 class="profile-name"><?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?></h3>
                    <p class="profile-email"><?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?></p>
                    <div class="profile-status">
                        <span class="status-dot active"></span> Active Member
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8 fadeInRight" style="--delay: 0.4s">
            <div class="profile-content">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Edit Profile Information</h5>
                    </div>
                    
                    <div class="card-body">
    <form action="update_profile.php" method="post" enctype="multipart/form-data">
                            <div class="form-group mb-3 fadeInUp" style="--delay: 0.5s">
                                <label for="name" class="form-label">Full Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>" required>
                                </div>
        </div>
        
                            <div class="form-group mb-3 fadeInUp" style="--delay: 0.6s">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" required>
                                </div>
        </div>
        
                            <div class="form-group mb-3 fadeInUp" style="--delay: 0.7s">
                                <label for="current_password" class="form-label">Current Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="form-text text-muted">Required to make any changes to your profile</div>
        </div>
        
                            <hr class="my-4 fadeInUp" style="--delay: 0.8s">
                            
                            <h6 class="mb-3 text-primary fadeInUp" style="--delay: 0.8s"><i class="fas fa-key me-2"></i>Password Change (Optional)</h6>
                            
                            <div class="form-group mb-3 fadeInUp" style="--delay: 0.9s">
                                <label for="new_password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                </div>
                                <div class="form-text text-muted">Leave blank to keep your current password</div>
        </div>
        
                            <div class="form-group mb-4 fadeInUp" style="--delay: 1s">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
        </div>
        
                            <div class="form-group mb-4 d-none">
            <label for="avatar">Profile Image:</label>
            <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewImage(this)">
        </div>
        
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end fadeInUp" style="--delay: 1.1s">
                                <button type="button" class="btn btn-outline-secondary me-md-2" onclick="window.location.href='index.php'">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </div>
    </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatar-preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Enhanced JavaScript for effects
    document.addEventListener('DOMContentLoaded', function() {
        // Link avatar overlay button to file input
        document.querySelector('.avatar-change-btn').addEventListener('click', function() {
            document.getElementById('avatar').click();
        });
        
        // Add focus/blur events for form fields
        const formInputs = document.querySelectorAll('.form-control');
        formInputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('.form-group').classList.add('input-focus');
            });
            
            input.addEventListener('blur', function() {
                this.closest('.form-group').classList.remove('input-focus');
            });
        });
        
        // Add ripple effect to buttons
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!this.classList.contains('btn-primary')) return;
                
                const rippleElement = document.createElement('span');
                rippleElement.classList.add('ripple');
                
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                rippleElement.style.left = x + 'px';
                rippleElement.style.top = y + 'px';
                
                this.appendChild(rippleElement);
                
                setTimeout(() => {
                    rippleElement.remove();
                }, 1000);
            });
        });
        
        // Animation for the submit button to trigger success animation
        const updateForm = document.querySelector('form');
        updateForm.addEventListener('submit', function(e) {
            // We don't want to prevent form submission, but we want to show animation
            const updateBtn = document.querySelector('.btn-primary');
            
            // Create success overlay
            showSuccessAnimation();
            
            // The form will still submit normally
        });
        
        // Auto-animate form elements as they come into view
        const animatedElements = document.querySelectorAll('.fadeInDown, .fadeInLeft, .fadeInRight, .fadeInUp');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Add an active class that will trigger the animation
                    entry.target.classList.add('animate-active');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        animatedElements.forEach(element => {
            observer.observe(element);
        });
        
        // Check if there's a success message to show success animation
        <?php if (isset($message) && strpos($message, 'success') !== false): ?>
            // If page loaded with success message, show the success animation
            setTimeout(() => {
                showSuccessAnimation();
            }, 500);
        <?php endif; ?>
        
        // Add hover effect for input icons
        const formGroups = document.querySelectorAll('.form-group');
        formGroups.forEach(group => {
            group.addEventListener('mouseenter', function() {
                const icon = this.querySelector('.input-group-text i');
                if (icon) {
                    icon.style.transform = 'scale(1.2) rotate(5deg)';
                }
            });
            
            group.addEventListener('mouseleave', function() {
                const icon = this.querySelector('.input-group-text i');
                if (icon) {
                    icon.style.transform = 'scale(1) rotate(0deg)';
                }
            });
        });
        
        // Add card shadow effect on scroll
        window.addEventListener('scroll', function() {
            const cards = document.querySelectorAll('.card, .profile-sidebar');
            cards.forEach(card => {
                const rect = card.getBoundingClientRect();
                const isInView = (
                    rect.top >= 0 &&
                    rect.left >= 0 &&
                    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
                
                if (isInView) {
                    card.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.1)';
                    card.style.transform = 'translateY(-5px)';
                } else {
                    card.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.05)';
                    card.style.transform = 'translateY(0)';
                }
            });
        });
    });
    
    // Function to show success animation
    function showSuccessAnimation() {
        // Create overlay container
        const overlay = document.createElement('div');
        overlay.className = 'success-animation-overlay';
        
        // Create success animation
        const successAnimation = document.createElement('div');
        successAnimation.className = 'success-animation';
        
        // Create success icon
        const successIcon = document.createElement('div');
        successIcon.className = 'success-icon';
        
        // Create checkmark
        const checkmark = document.createElement('div');
        checkmark.className = 'checkmark';
        checkmark.innerHTML = '<svg width="80" height="80" viewBox="0 0 80 80"><circle class="checkmark-circle" cx="40" cy="40" r="36"></circle><path class="checkmark-check" d="M23,40 L35,50 L58,30"></path></svg>';
        
        // Create success text
        const successText = document.createElement('div');
        successText.className = 'success-text';
        successText.innerHTML = '<h3>Profile Updated Successfully!</h3><p>Your changes have been saved.</p>';
        
        // Create confetti elements
        const confettiContainer = document.createElement('div');
        confettiContainer.className = 'confetti-container';
        
        // Add 30 confetti pieces
        for (let i = 0; i < 30; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.left = Math.random() * 100 + '%';
            confetti.style.animationDelay = Math.random() * 3 + 's';
            confetti.style.background = getRandomColor();
            confettiContainer.appendChild(confetti);
        }
        
        // Add ripple effects
        const rippleContainer = document.createElement('div');
        rippleContainer.className = 'ripple-container';
        
        for (let i = 0; i < 4; i++) {
            const ripple = document.createElement('div');
            ripple.className = 'success-ripple';
            ripple.style.animationDelay = i * 0.3 + 's';
            rippleContainer.appendChild(ripple);
        }
        
        // Assemble the elements
        successIcon.appendChild(checkmark);
        successAnimation.appendChild(rippleContainer);
        successAnimation.appendChild(successIcon);
        successAnimation.appendChild(successText);
        overlay.appendChild(confettiContainer);
        overlay.appendChild(successAnimation);
        
        // Add to body
        document.body.appendChild(overlay);
        
        // Add active class after a small delay to trigger animations
        setTimeout(() => {
            overlay.classList.add('active');
        }, 10);
        
        // Remove overlay after animation completes
        setTimeout(() => {
            overlay.classList.remove('active');
            setTimeout(() => {
                document.body.removeChild(overlay);
            }, 500);
        }, 4000);
    }
    
    // Helper to generate random colors for confetti
    function getRandomColor() {
        const colors = [
            '#4f46e5', // primary
            '#8b5cf6', // secondary
            '#10b981', // success
            '#f59e0b', // warning
            '#ef4444'  // danger
        ];
        return colors[Math.floor(Math.random() * colors.length)];
    }
</script>

<?php include 'includes/footer.php'; ?>

<?php if ($show_welcome) { ?>
<!-- Colorful Professional Welcome Animation -->
<div id="welcome-container" class="celebration-container">
    <div id="confetti-container" class="confetti-container"></div>
    <div id="bubbles-container" class="bubbles-container"></div>
    
    <div class="celebration-card">
        <div class="color-pulse-background"></div>
        
        <div class="logo-badge">
            <div class="logo-glow"></div>
            <i class="fas fa-brain"></i>
        </div>
        
        <h2 class="welcome-title">Welcome Back</h2>
        
        <div class="user-profile">
            <div class="user-avatar">
                <svg viewBox="0 0 24 24" width="100%" height="100%">
                    <path fill="currentColor" d="M12,19.2C9.5,19.2 7.29,17.92 6,16C6.03,14 10,12.9 12,12.9C14,12.9 17.97,14 18,16C16.71,17.92 14.5,19.2 12,19.2M12,5A3,3 0 0,1 15,8A3,3 0 0,1 12,11A3,3 0 0,1 9,8A3,3 0 0,1 12,5M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12C22,6.47 17.5,2 12,2Z" />
                </svg>
            </div>
            <div class="user-info">
                <h3 class="username"><?php echo htmlspecialchars($user['username']); ?></h3>
                <p class="session-status">Your session is ready</p>
            </div>
        </div>
        
        <div class="feature-highlights">
            <div class="feature-item">
                <div class="feature-icon" style="--color: #3b82f6;">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="feature-text">AI Support</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="--color: #8b5cf6;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="feature-text">Progress Tracking</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon" style="--color: #10b981;">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="feature-text">Confidential</div>
            </div>
        </div>
        
        <button class="continue-btn">
            <span>Continue</span>
            <div class="btn-shine"></div>
        </button>
        
        <div class="timer-bar"><div class="timer-progress"></div></div>
    </div>
</div>
<?php } ?>

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
<?php ob_end_flush(); // End and flush the output buffer ?>

<!-- Add this script right before the closing </body> tag -->
<script>
// Immediate-execution function to fix icon sizing issues
(function() {
    // Direct DOM manipulation to fix the icon containers
    var iconContainers = document.querySelectorAll('.input-group-text');
    iconContainers.forEach(function(container) {
        // Force the width and height with !important
        container.setAttribute('style', 'width: 50px !important; height: 50px !important; display: flex !important; align-items: center !important; justify-content: center !important; padding: 0 !important;');
        
        // Get the icon inside
        var icon = container.querySelector('i');
        if (icon) {
            icon.setAttribute('style', 'font-size: 18px !important; color: #4f46e5 !important;');
        }
    });
    
    // Make inputs match height as well
    var inputs = document.querySelectorAll('.form-control');
    inputs.forEach(function(input) {
        input.setAttribute('style', 'height: 50px !important; line-height: 50px !important; padding: 0 15px !important;');
    });
    
    // Add direct event listeners for hover effects
    document.querySelectorAll('.input-group').forEach(function(group) {
        group.addEventListener('mouseenter', function() {
            var iconContainer = this.querySelector('.input-group-text');
            var icon = iconContainer?.querySelector('i');
            
            if (iconContainer) {
                iconContainer.style.backgroundColor = '#e0e7ff';  // primary-light
            }
            
            if (icon) {
                icon.style.transform = 'scale(1.2) rotate(5deg)';
                icon.style.transition = 'transform 0.3s ease';
            }
        });
        
        group.addEventListener('mouseleave', function() {
            var iconContainer = this.querySelector('.input-group-text');
            var icon = iconContainer?.querySelector('i');
            
            if (iconContainer) {
                iconContainer.style.backgroundColor = '#f8f9fa';
            }
            
            if (icon) {
                icon.style.transform = 'scale(1) rotate(0deg)';
            }
        });
    });
})();

// Dark Mode Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    
    // Check for saved theme preference or respect OS preference
    const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
    const storedTheme = localStorage.getItem('theme');
    
    // If the user has explicitly chosen a theme, use it
    if (storedTheme) {
        document.body.classList.toggle('dark-mode', storedTheme === 'dark');
        darkModeToggle.checked = storedTheme === 'dark';
    } 
    // Otherwise, respect OS preference
    else if (prefersDarkScheme.matches) {
        document.body.classList.add('dark-mode');
        darkModeToggle.checked = true;
    }
    
    // Listen for toggle changes
    darkModeToggle.addEventListener('change', function() {
        // Toggle dark mode class on body
        document.body.classList.toggle('dark-mode', this.checked);
        
        // Store user preference
        const theme = this.checked ? 'dark' : 'light';
        localStorage.setItem('theme', theme);
        
        // Update icons based on theme
        updateThemeIcons(this.checked);
    });
    
    // Initialize icons based on current theme
    updateThemeIcons(document.body.classList.contains('dark-mode'));
    
    function updateThemeIcons(isDarkMode) {
        // Update icon in the toggle
        const darkIcons = document.querySelectorAll('.dark-icon');
        const lightIcons = document.querySelectorAll('.light-icon');
        
        darkIcons.forEach(icon => {
            icon.style.display = isDarkMode ? 'none' : 'inline-block';
        });
        
        lightIcons.forEach(icon => {
            icon.style.display = isDarkMode ? 'inline-block' : 'none';
        });
        
        // Fix any style overwrites in dark mode for icon containers
        if (isDarkMode) {
            document.querySelectorAll('.input-group-text i').forEach(icon => {
                icon.style.color = '#e2e8f0 !important';
            });
        }
    }
});

// Handle password visibility toggle
const togglePassword = document.querySelectorAll('.password-toggle');

togglePassword.forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        const input = this.previousElementSibling;
        const icon = this.querySelector('i');
        
        // Toggle the type attribute
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        
        // Toggle the icon
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });
});

// Dark Mode Toggle Functionality

const darkModeToggle = document.getElementById('darkModeToggle');
if (darkModeToggle) {
    // Check for saved theme preference
    const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
    const storedTheme = localStorage.getItem('theme');
    
    // If a theme preference exists in localStorage
    if (storedTheme) {
        // Apply the stored theme preference
        document.body.classList.toggle('dark-mode', storedTheme === 'dark');
        darkModeToggle.checked = storedTheme === 'dark';
    }
    // If no theme preference in localStorage but system prefers dark
    else if (prefersDarkScheme.matches) {
        document.body.classList.add('dark-mode');
        darkModeToggle.checked = true;
    }
    
    // Add event listener to the dark mode toggle
    darkModeToggle.addEventListener('change', function() {
        // Toggle dark mode class on body
        document.body.classList.toggle('dark-mode', this.checked);
        
        // Save preference to localStorage
        const theme = this.checked ? 'dark' : 'light';
        localStorage.setItem('theme', theme);
        
        // Update icons visibility
        updateThemeIcons(document.body.classList.contains('dark-mode'));
    });
    
    // Set initial icon state
    updateThemeIcons(document.body.classList.contains('dark-mode'));
}

function updateThemeIcons(isDarkMode) {
    // Update the icon visibility based on the current mode
    const darkIcons = document.querySelectorAll('.dark-icon');
    const lightIcons = document.querySelectorAll('.light-icon');
    
    darkIcons.forEach(icon => {
        icon.style.display = isDarkMode ? 'none' : 'inline-block';
    });
    
    lightIcons.forEach(icon => {
        icon.style.display = isDarkMode ? 'inline-block' : 'none';
    });
    
    // Fix any style overwrites in dark mode for icon containers
    const iconContainers = document.querySelectorAll('.feature-icon');
    iconContainers.forEach(container => {
        if (container.querySelector('.light-icon, .dark-icon')) {
            container.style.display = 'flex';
        }
    });
}
</script>
<script src="assets/js/theme.js"></script>
</body>
</html> 