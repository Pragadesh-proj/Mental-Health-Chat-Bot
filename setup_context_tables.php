<?php
/**
 * Setup script for emotion detection and user context tracking system tables
 */

// Include database connection
require_once 'config/database.php';

// Create user_mood_history table
$sql_mood_history = "
CREATE TABLE IF NOT EXISTS user_mood_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(255) NOT NULL,
    emotion VARCHAR(50) NOT NULL,
    severity VARCHAR(20) NOT NULL,
    confidence FLOAT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (session_id),
    INDEX (created_at)
)";

// Create conversation_context table
$sql_context = "
CREATE TABLE IF NOT EXISTS conversation_context (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    session_id VARCHAR(255) NOT NULL,
    context_key VARCHAR(100) NOT NULL,
    context_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (session_id),
    INDEX (context_key)
)";

// Create conversation_logs table
$sql_logs = "
CREATE TABLE IF NOT EXISTS conversation_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    conversation_id VARCHAR(100) NOT NULL,
    message_type ENUM('user', 'bot') NOT NULL,
    message TEXT NOT NULL,
    emotion_detected VARCHAR(50) NULL,
    emotion_confidence FLOAT NULL,
    emotion_severity VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (conversation_id),
    INDEX (created_at)
)";

// Create tables and check for errors
$tables_created = true;
$error_messages = [];

if (!$conn->query($sql_mood_history)) {
    $tables_created = false;
    $error_messages[] = "Error creating user_mood_history table: " . $conn->error;
}

if (!$conn->query($sql_context)) {
    $tables_created = false;
    $error_messages[] = "Error creating conversation_context table: " . $conn->error;
}

if (!$conn->query($sql_logs)) {
    $tables_created = false;
    $error_messages[] = "Error creating conversation_logs table: " . $conn->error;
}

// Output results
if ($tables_created) {
    echo "All tables created successfully!<br>";
    echo "<p>The following tables were created:</p>";
    echo "<ul>";
    echo "<li>user_mood_history - Stores the detected emotions from user messages</li>";
    echo "<li>conversation_context - Stores context data for personalizing responses</li>";
    echo "<li>conversation_logs - Stores conversation history with emotional data</li>";
    echo "</ul>";
    echo "<p>You can now use the enhanced emotional detection and context-aware responses in your chatbot.</p>";
    echo "<p><a href='chat.php'>Go to the chat interface</a></p>";
} else {
    echo "<h2>Error creating tables:</h2>";
    echo "<ul>";
    foreach ($error_messages as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "<p>Please check your database configuration and try again.</p>";
}
?> 