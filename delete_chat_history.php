<?php
// Turn off error display to prevent HTML errors in JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to prevent accidental output
ob_start();

session_start();
header('Content-Type: application/json');
require_once 'config/database.php';

// Catch any potential fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Clean the buffer
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'PHP Error: ' . $error['message']
        ]);
        exit;
    }
});

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

// Check if chat ID was provided
if (!isset($_POST['chat_id']) || empty($_POST['chat_id'])) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'No chat ID provided'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];
$chat_id = $_POST['chat_id'];

// Log for debugging
error_log("Attempting to delete chat ID: $chat_id for user ID: $user_id");

// Start transaction
$conn->begin_transaction();

try {
    // First verify that this chat session belongs to the user
    $verifyQuery = "SELECT id FROM chat_sessions WHERE id = ? AND user_id = ?";
    $verifyStmt = $conn->prepare($verifyQuery);
    
    if (!$verifyStmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $verifyStmt->bind_param("si", $chat_id, $user_id);
    $success = $verifyStmt->execute();
    
    if (!$success) {
        throw new Exception("Execute failed: " . $verifyStmt->error);
    }
    
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows === 0) {
        // Chat session doesn't exist or doesn't belong to this user
        $conn->rollback();
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Chat session not found or unauthorized'
        ]);
        exit;
    }
    
    // Delete chat messages first (foreign key constraint)
    $deleteMessagesQuery = "DELETE FROM chat_logs WHERE session_id = ?";
    $deleteMessagesStmt = $conn->prepare($deleteMessagesQuery);
    
    if (!$deleteMessagesStmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $deleteMessagesStmt->bind_param("s", $chat_id);
    $success = $deleteMessagesStmt->execute();
    
    if (!$success) {
        throw new Exception("Execute failed: " . $deleteMessagesStmt->error);
    }
    
    // Then delete the chat session
    $deleteSessionQuery = "DELETE FROM chat_sessions WHERE id = ? AND user_id = ?";
    $deleteSessionStmt = $conn->prepare($deleteSessionQuery);
    
    if (!$deleteSessionStmt) {
        throw new Exception("Prepare statement failed: " . $conn->error);
    }
    
    $deleteSessionStmt->bind_param("si", $chat_id, $user_id);
    $success = $deleteSessionStmt->execute();
    
    if (!$success) {
        throw new Exception("Execute failed: " . $deleteSessionStmt->error);
    }
    
    // Log results
    $messagesDeleted = $deleteMessagesStmt->affected_rows;
    $sessionDeleted = $deleteSessionStmt->affected_rows;
    error_log("Deleted $messagesDeleted messages and $sessionDeleted session");
    
    // Commit the transaction
    $conn->commit();
    
    // Return success response
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Chat history deleted successfully'
    ]);
    
} catch (Exception $e) {
    // Roll back the transaction on error
    $conn->rollback();
    
    error_log("Error deleting chat history: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>