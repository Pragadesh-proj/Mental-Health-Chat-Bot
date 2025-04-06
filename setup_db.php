<?php
/**
 * Database Setup Script for Mental Health Chatbot
 * This script creates the necessary database schema for the chatbot functionality
 */

// Include database connection
require_once 'config/db_connect.php';

// Start the setup process
echo "<h1>Mental Health Chatbot Database Setup</h1>";
echo "<p>Setting up database tables...</p>";

try {
    // Create users table if not exists
    $conn->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Users table created or already exists.</p>";
    
    // Create chat_history table if not exists
    $conn->exec("
        CREATE TABLE IF NOT EXISTS chat_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            message TEXT NOT NULL,
            response TEXT NOT NULL,
            sentiment VARCHAR(20),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Chat history table created or already exists.</p>";
    
    // Create user_preferences table if not exists
    $conn->exec("
        CREATE TABLE IF NOT EXISTS user_preferences (
            user_id INT PRIMARY KEY,
            theme VARCHAR(20) DEFAULT 'light',
            font_size VARCHAR(10) DEFAULT 'medium',
            notification_enabled BOOLEAN DEFAULT TRUE,
            interests JSON,
            color_scheme VARCHAR(20) DEFAULT 'blue',
            voice_enabled BOOLEAN DEFAULT TRUE,
            voice_type VARCHAR(50) DEFAULT 'en-US-Standard-C',
            voice_pitch FLOAT DEFAULT 0,
            voice_speed FLOAT DEFAULT 1,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ User preferences table created or already exists.</p>";
    
    // Create user_state table if not exists
    $conn->exec("
        CREATE TABLE IF NOT EXISTS user_state (
            user_id INT PRIMARY KEY,
            last_mood VARCHAR(20),
            conversation_context TEXT,
            last_interaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ User state table created or already exists.</p>";
    
    // Create topics table if not exists
    $conn->exec("
        CREATE TABLE IF NOT EXISTS topics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Topics table created or already exists.</p>";
    
    // Create user_topics table if not exists
    $conn->exec("
        CREATE TABLE IF NOT EXISTS user_topics (
            user_id INT,
            topic_id INT,
            interest_level INT DEFAULT 5,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, topic_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ User topics relationship table created or already exists.</p>";
    
    // Create crisis_events table if not exists
    $conn->exec("
        CREATE TABLE IF NOT EXISTS crisis_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            message TEXT NOT NULL,
            level VARCHAR(20) NOT NULL,
            matched_terms TEXT,
            actions_taken TEXT,
            resolved BOOLEAN DEFAULT FALSE,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "<p>✅ Crisis events table created or already exists.</p>";
    
    // Create motivation_history table if not exists
    $conn->exec("
        CREATE TABLE IF NOT EXISTS motivation_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            type VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "<p>✅ Motivation history table created or already exists.</p>";
    
    // Insert default topics if topics table is empty
    $stmt = $conn->query("SELECT COUNT(*) as count FROM topics");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        $topics = [
            ['name' => 'Anxiety', 'description' => 'Discussing feelings of anxiety and worry'],
            ['name' => 'Depression', 'description' => 'Talking about depression and low mood'],
            ['name' => 'Stress Management', 'description' => 'Techniques for managing stress'],
            ['name' => 'Mindfulness', 'description' => 'Practicing mindfulness and present-moment awareness'],
            ['name' => 'Self-Care', 'description' => 'Taking care of your physical and mental wellbeing'],
            ['name' => 'Personal Growth', 'description' => 'Working on self-improvement and development'],
            ['name' => 'Resilience', 'description' => 'Building strength to overcome challenges'],
            ['name' => 'Positive Psychology', 'description' => 'Focusing on strengths and positive aspects of life'],
            ['name' => 'Sleep Health', 'description' => 'Improving sleep quality and habits'],
            ['name' => 'Work-Life Balance', 'description' => 'Finding balance between work and personal life']
        ];
        
        $stmt = $conn->prepare("INSERT INTO topics (name, description) VALUES (?, ?)");
        
        foreach ($topics as $topic) {
            $stmt->execute([$topic['name'], $topic['description']]);
        }
        
        echo "<p>✅ Default topics inserted successfully.</p>";
    }
    
    // Check if we need to update existing tables to add new columns
    
    // Check for voice_* columns in user_preferences
    $stmt = $conn->query("SHOW COLUMNS FROM user_preferences LIKE 'voice_type'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("
            ALTER TABLE user_preferences
            ADD COLUMN voice_type VARCHAR(50) DEFAULT 'en-US-Standard-C',
            ADD COLUMN voice_pitch FLOAT DEFAULT 0,
            ADD COLUMN voice_speed FLOAT DEFAULT 1
        ");
        echo "<p>✅ Added voice preference columns to user_preferences table.</p>";
    }
    
    // Check for conversation_context column in user_state
    $stmt = $conn->query("SHOW COLUMNS FROM user_state LIKE 'conversation_context'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("
            ALTER TABLE user_state
            ADD COLUMN conversation_context TEXT AFTER last_mood
        ");
        echo "<p>✅ Added conversation_context column to user_state table.</p>";
    }
    
    echo "<h2>Database setup completed successfully!</h2>";
    echo "<p>The Mental Health Chatbot database has been set up with all necessary tables.</p>";
    echo "<p><a href='index.php'>Return to homepage</a></p>";
    
} catch(PDOException $e) {
    echo "<h2>Error setting up database</h2>";
    echo "<p>An error occurred during database setup: " . $e->getMessage() . "</p>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        margin: 20px;
        padding: 20px;
        color: #333;
    }
    
    h1 {
        color: #4F46E5;
        border-bottom: 2px solid #E5E7EB;
        padding-bottom: 10px;
    }
    
    .success {
        color: #10B981;
        font-weight: bold;
    }
    
    .error {
        color: #EF4444;
        font-weight: bold;
    }
    
    a {
        display: inline-block;
        margin-top: 20px;
        background-color: #4F46E5;
        color: white;
        padding: 10px 15px;
        text-decoration: none;
        border-radius: 5px;
    }
    
    a:hover {
        background-color: #4338CA;
    }
</style>

<h1>Mental Health Chatbot Setup</h1>

<p>The database has been updated to support enhanced personalization features.</p>

<p>You can now:</p>
<ul>
    <li>Store user preferences</li>
    <li>Track conversation context over time</li>
    <li>Remember user mood patterns</li>
    <li>Organize topics of interest</li>
</ul>

<a href="chat.php">Go to Chatbot</a> 