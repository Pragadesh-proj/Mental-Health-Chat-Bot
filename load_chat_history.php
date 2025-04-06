<?php
session_start();
require_once 'config/database.php';

// Set appropriate header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

// Check if chat_id is provided
$chatId = null;
if (isset($_GET['chat_id'])) {
    $chatId = $_GET['chat_id'];
}

try {
    // Prepare query based on whether chat_id is provided
    if ($chatId) {
        // Load messages for a specific chat session
        $query = "SELECT cl.id, cl.user_input, cl.bot_response, cl.sentiment, cl.created_at 
                 FROM chat_logs cl 
                 WHERE cl.chat_id = ? AND cl.user_id = ? 
                 ORDER BY cl.created_at ASC 
                 LIMIT 100";
        
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("si", $chatId, $userId);
    } else {
        // Load most recent messages (regardless of chat session)
        $query = "SELECT id, user_input, bot_response, sentiment, created_at 
                 FROM chat_logs 
                 WHERE user_id = ? 
                 ORDER BY created_at ASC 
                 LIMIT 20";
        
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $userId);
    }
    
    // Execute the query
    $success = $stmt->execute();
    
    if (!$success) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $messages = [];
    
    while ($row = $result->fetch_assoc()) {
        $messageData = [
            'id' => $row['id'],
            'user_input' => $row['user_input'],
            'sentiment' => $row['sentiment'],
            'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
        ];
        
        // Process bot response - it might be JSON or plain text
        if ($row['bot_response']) {
            // Check if bot_response is already JSON
            $botResponse = json_decode($row['bot_response'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $messageData['bot_response'] = $botResponse;
            } else {
                // Create a JSON structure if it's just text
                $messageData['bot_response'] = [
                    'text' => $row['bot_response'],
                    'suggestions' => []
                ];
            }
        } else {
            $messageData['bot_response'] = null;
        }
        
        $messages[] = $messageData;
    }
    
    // Return the chat history
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'chat_id' => $chatId
    ]);
    
} catch (Exception $e) {
    error_log("Error retrieving chat history: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving chat history: ' . $e->getMessage()
    ]);
}
?>
