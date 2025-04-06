<?php
// Turn off error display to prevent HTML errors in JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
require_once 'config/database.php';

// Catch any potential fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo json_encode([
            'success' => false,
            'message' => 'PHP Error: ' . $error['message']
        ]);
        exit;
    }
});

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if session ID was provided
if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No session ID provided'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$session_id = $_POST['session_id'];

try {
    // First verify that this chat session belongs to the user
    $verifyQuery = "SELECT id, title FROM chat_sessions WHERE id = ? AND user_id = ?";
    $verifyStmt = $conn->prepare($verifyQuery);
    
    if (!$verifyStmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $verifyStmt->bind_param("si", $session_id, $user_id);
    $success = $verifyStmt->execute();
    
    if (!$success) {
        throw new Exception("Execute failed: " . $verifyStmt->error);
    }
    
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows === 0) {
        // Chat session doesn't exist or doesn't belong to this user
        echo json_encode([
            'success' => false,
            'message' => 'Chat session not found or unauthorized'
        ]);
        exit;
    }
    
    $session = $verifyResult->fetch_assoc();
    
    // Get the messages for this session
    $messagesQuery = "SELECT user_input, bot_response, sentiment, created_at 
                     FROM chat_logs 
                     WHERE session_id = ? 
                     ORDER BY created_at ASC";
    $messagesStmt = $conn->prepare($messagesQuery);
    
    if (!$messagesStmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $messagesStmt->bind_param("s", $session_id);
    $success = $messagesStmt->execute();
    
    if (!$success) {
        throw new Exception("Execute failed: " . $messagesStmt->error);
    }
    
    $messagesResult = $messagesStmt->get_result();
    
    $messages = [];
    
    while ($row = $messagesResult->fetch_assoc()) {
        $messages[] = [
            'user_input' => $row['user_input'],
            'bot_response' => $row['bot_response'],
            'sentiment' => $row['sentiment'],
            'created_at' => $row['created_at']
        ];
    }
    
    // Update current session in the session variable
    $_SESSION['current_chat_session'] = $session_id;
    
    // Return the session and its messages
    echo json_encode([
        'success' => true,
        'session' => [
            'id' => $session['id'],
            'title' => $session['title']
        ],
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    error_log("Error in load_chat_session.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 