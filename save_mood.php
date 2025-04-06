<?php
// Start session
session_start();
header('Content-Type: application/json');

// Use database connection from config file
require_once 'config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if mood value is provided
if (!isset($_POST['mood']) || !is_numeric($_POST['mood'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid mood value'
    ]);
    exit;
}

// Get user ID and mood
$user_id = $_SESSION['user_id'];
$mood = (int)$_POST['mood'];

// Validate mood value (1-5)
if ($mood < 1 || $mood > 5) {
    echo json_encode([
        'success' => false,
        'message' => 'Mood value must be between 1 and 5'
    ]);
    exit;
}

// Get optional note
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

// Check if pdo connection exists
if (!isset($pdo)) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error. Please check your database configuration.'
    ]);
    exit;
}

try {
    // Check if mood_tracker table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'mood_tracker'");
    $tableExists = $tableCheck && $tableCheck->rowCount() > 0;
    
    if (!$tableExists) {
        // Create mood_tracker table if it doesn't exist
        $sql = "CREATE TABLE mood_tracker (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            mood INT NOT NULL,
            note TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
    } else {
        // Check if mood column exists
        $columnCheck = $pdo->query("SHOW COLUMNS FROM mood_tracker LIKE 'mood'");
        $columnExists = $columnCheck && $columnCheck->rowCount() > 0;
        
        if (!$columnExists) {
            // Add mood column if it doesn't exist
            $pdo->exec("ALTER TABLE mood_tracker ADD COLUMN mood INT NOT NULL AFTER user_id");
        }
    }
    
    // Insert mood record
    $stmt = $pdo->prepare("INSERT INTO mood_tracker (user_id, mood, note) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $mood, $note]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Mood saved successfully',
        'mood' => $mood
    ]);
} catch (PDOException $e) {
    error_log("Error saving mood: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error saving mood: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} 