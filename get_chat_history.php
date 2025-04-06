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

$user_id = $_SESSION['user_id'];

try {
    // First check if chat_sessions table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'chat_sessions'";
    $tableCheck = $conn->query($tableCheckQuery);
    
    if (!$tableCheck) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    if ($tableCheck->num_rows === 0) {
        // Table doesn't exist
        echo json_encode([
            'success' => true,
            'sessions' => []
        ]);
        exit;
    }
    
    // Check if title column exists in chat_sessions
    $columnCheckQuery = "SHOW COLUMNS FROM chat_sessions LIKE 'title'";
    $columnCheck = $conn->query($columnCheckQuery);
    $hasTitleColumn = ($columnCheck && $columnCheck->num_rows > 0);
    
    // Modify query based on whether title column exists
    if ($hasTitleColumn) {
        $query = "SELECT cs.id, cs.title, cs.created_at, MAX(cl.created_at) as updated_at 
                FROM chat_sessions cs 
                LEFT JOIN chat_logs cl ON cs.id = cl.chat_id 
                WHERE cs.user_id = ? 
                GROUP BY cs.id, cs.title, cs.created_at 
                ORDER BY updated_at DESC";
    } else {
        $query = "SELECT cs.id, cs.created_at, MAX(cl.created_at) as updated_at 
                FROM chat_sessions cs 
                LEFT JOIN chat_logs cl ON cs.id = cl.chat_id 
                WHERE cs.user_id = ? 
                GROUP BY cs.id, cs.created_at 
                ORDER BY updated_at DESC";
    }
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $success = $stmt->execute();
    
    if (!$success) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    $sessions = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Create a title if it doesn't exist in the result
            $title = isset($row['title']) ? $row['title'] : "Chat from " . date('M d, Y', strtotime($row['created_at']));
            
            $sessions[] = [
                'id' => $row['id'],
                'title' => $title,
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'sessions' => $sessions
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_chat_history.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?> 