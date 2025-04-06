<?php
session_start();
// Enable error reporting for debugging during development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log errors to a file instead of displaying them in production
// error_log("Chat process started: " . date('Y-m-d H:i:s'), 0);

// Enable CORS for security
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

// If this is a test request, return a simple JSON response
if (isset($_GET['action']) && $_GET['action'] === 'test') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Server connection is working',
        'session_status' => session_status(),
        'session_id' => session_id(),
        'user_logged_in' => isset($_SESSION['user_id']),
        'test_user_id' => isset($_SESSION['test_user_id']) ? $_SESSION['test_user_id'] : null,
        'time' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Set JSON header for all other responses
header('Content-Type: application/json');

// Use the database connection from config file
try {
    require_once 'config/database.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error: ' . $e->getMessage()
    ]);
    exit();
}

// Include Google Gemini API functions
try {
    require_once 'gemini_functions.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load Gemini functions: ' . $e->getMessage()
    ]);
    exit();
}

// Check if user is logged in - allow test user for debugging
if (!isset($_SESSION['user_id']) && !isset($_SESSION['test_user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get user ID from session (real or test)
$user_id = $_SESSION['user_id'] ?? $_SESSION['test_user_id'] ?? null;

// Check if this is a send_message action
if (isset($_POST['action']) && $_POST['action'] === 'send_message') {
    // Check if message is provided
    if (!isset($_POST['message']) || trim($_POST['message']) === '') {
        echo json_encode([
            'success' => false,
            'message' => 'No message provided'
        ]);
        exit;
    }
    
    $message = trim($_POST['message']);
    $anonymous_mode = isset($_POST['anonymous']) ? $_POST['anonymous'] === 'true' : false;
    $tts_enabled = isset($_POST['tts_enabled']) ? $_POST['tts_enabled'] === 'true' : false;
    $chat_id = isset($_POST['chat_id']) ? $_POST['chat_id'] : null;
    
    try {
        // Get sentiment from the user's message
        $sentimentResult = analyzeTextSentiment($message);
        $sentiment = $sentimentResult['score'] ?? 0;
        $magnitude = $sentimentResult['magnitude'] ?? 0;
        
        // Log the message to the database if not in anonymous mode
        if (!$anonymous_mode && isset($conn)) {
            try {
                $stmt = $conn->prepare("INSERT INTO chat_logs (user_id, user_input, sentiment) VALUES (?, ?, ?)");
                $stmt->bind_param("isd", $user_id, $message, $sentiment);
                $stmt->execute();
                $log_id = $stmt->insert_id;
            } catch (Exception $e) {
                // Log error but continue processing
                error_log("Database error logging chat: " . $e->getMessage());
            }
        }
        
        // Get user preferences if available
        $preferences = [];
        if (isset($conn)) {
            try {
                $stmt = $conn->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $prefResult = $stmt->get_result();
                    if ($prefResult && $prefResult->num_rows > 0) {
                        $preferences = $prefResult->fetch_assoc();
                    }
                }
            } catch (Exception $e) {
                // If table doesn't exist or other error, continue without preferences
                error_log("Error getting preferences: " . $e->getMessage());
            }
        }
        
        // Get chat history for context
        $chatHistory = [];
        if (isset($conn) && $chat_id) {
            try {
                $stmt = $conn->prepare("SELECT user_input, bot_response FROM chat_logs 
                                        WHERE user_id = ? AND chat_id = ? 
                                        ORDER BY created_at DESC LIMIT 5");
                if ($stmt) {
                    $stmt->bind_param("ii", $user_id, $chat_id);
                    $stmt->execute();
                    $historyResult = $stmt->get_result();
                    while ($row = $historyResult->fetch_assoc()) {
                        $chatHistory[] = $row;
                    }
                }
            } catch (Exception $e) {
                // If retrieval fails, continue without history
                error_log("Error getting chat history: " . $e->getMessage());
            }
        }
        
        // Generate response based on user message and context
        $responseData = generateResponse($message, [
            'sentiment' => $sentiment,
            'userName' => $_SESSION['username'] ?? '',
            'preferences' => $preferences,
            'chatHistory' => $chatHistory
        ]);
        
        // If generateResponse returns null, create a fallback response
        if ($responseData === null) {
            $responseData = [
                'text' => "I've received your message: \"$message\". I'm currently experiencing some issues with my advanced response system. How can I help you further?",
                'suggestions' => [
                    "Tell me more",
                    "How are you feeling?",
                    "What can I help with?"
                ]
            ];
        }
        
        // Check for crisis language and add a flag if detected
        $crisis_detected = false;
        if ($sentiment < -0.6 && stripos($message, 'suicide') !== false || 
            stripos($message, 'kill myself') !== false || 
            stripos($message, 'end my life') !== false) {
            $crisis_detected = true;
        }
        
        // Log the bot response if we logged the user message
        if (!$anonymous_mode && isset($conn) && isset($log_id)) {
            try {
                $stmt = $conn->prepare("UPDATE chat_logs SET bot_response = ? WHERE id = ?");
                $stmt->bind_param("si", $responseData['text'], $log_id);
                $stmt->execute();
            } catch (Exception $e) {
                // Log error but continue
                error_log("Database error updating bot response: " . $e->getMessage());
            }
        }
        
        // Send response
        echo json_encode([
            'success' => true,
            'message' => $responseData['text'],
            'response' => $responseData,
            'sentiment' => $sentiment,
            'magnitude' => $magnitude,
            'suggestions' => $responseData['suggestions'] ?? [],
            'crisis_detected' => $crisis_detected
        ]);
        
    } catch (Exception $e) {
        error_log("Error in process_chat.php: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error processing your message: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Check if this is a get_chat_history action
else if (isset($_POST['action']) && $_POST['action'] === 'get_chat_history') {
    try {
        if (!isset($conn)) {
            throw new Exception("Database connection not available");
        }
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        $stmt = $conn->prepare("SELECT id, user_input, bot_response, sentiment, created_at 
                                FROM chat_logs WHERE user_id = ? 
                                ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'history' => $history
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error retrieving chat history: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Default response if no action is specified
echo json_encode([
    'success' => false,
    'message' => 'Invalid request. Please specify an action.'
]);
?> 