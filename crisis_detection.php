<?php
/**
 * Crisis Detection System
 * Monitors user messages for signs of crisis and provides appropriate interventions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/db_connect.php';

class CrisisDetection {
    private $db;
    private $urgent_keywords = [
        // Suicide related
        'kill myself', 'suicide', 'end my life', 'take my life', 'don\'t want to live',
        'want to die', 'better off dead', 'going to end it', 'harm myself', 'hurt myself',
        
        // Self-harm related
        'cut myself', 'cutting myself', 'self harm', 'self-harm', 'hurt myself',
        
        // Severe distress
        'can\'t take it anymore', 'no way out', 'hopeless', 'helpless', 'unbearable pain',
        'worthless', 'nobody cares', 'never get better', 'no reason to live',
        
        // Crisis indicators
        'emergency', 'crisis', 'urgent help', 'need help now', 'dangerous',
        'unsafe', 'scared for my life', 'terrified', 'panic attack'
    ];
    
    private $concerning_keywords = [
        // Distress indicators
        'depressed', 'anxious', 'overwhelmed', 'stressed', 'struggling',
        'lonely', 'isolated', 'sad', 'grief', 'trauma', 'abuse',
        
        // Moderate risk phrases
        'losing hope', 'giving up', 'too much pain', 'tired of living',
        'can\'t cope', 'exhausted', 'trapped', 'burden', 'abandoned',
        
        // Mental health conditions
        'depression', 'anxiety', 'ptsd', 'bipolar', 'schizophrenia',
        'eating disorder', 'anorexia', 'bulimia', 'ocd', 'addiction'
    ];
    
    private $crisis_response_template = [
        'urgent' => [
            "I'm concerned about what you've shared. It sounds like you're going through a really difficult time right now. Your safety is the most important thing, and there are people who can help immediately.",
            "Based on what you're saying, it seems like you need immediate support. Please remember that you don't have to face this alone - trained professionals are available right now to help.",
            "I want you to know that I take what you're sharing very seriously. It sounds like you're in crisis, and it's important that you connect with someone who can provide immediate help."
        ],
        'concerning' => [
            "I notice you mentioned some things that sound difficult to deal with. How are you coping right now? Would you like some resources that might help?",
            "It sounds like you're going through a challenging time. There are supportive resources available that might help you work through these feelings.",
            "I'm hearing that things are really tough for you right now. Would it help to talk about some specific strategies or resources that could support you during this difficult time?"
        ]
    ];
    
    /**
     * Constructor initializes the database connection
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Analyzes user message for signs of crisis
     * 
     * @param string $message User message
     * @param int $user_id User ID
     * @return array Analysis results
     */
    public function analyzeMessage($message, $user_id = null) {
        $message = strtolower($message);
        $result = [
            'crisis_detected' => false,
            'level' => 'none',
            'matched_terms' => [],
            'response' => null,
            'recommend_resources' => false
        ];
        
        // Check for urgent keywords
        foreach ($this->urgent_keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                $result['crisis_detected'] = true;
                $result['level'] = 'urgent';
                $result['matched_terms'][] = $keyword;
            }
        }
        
        // If no urgent keywords, check for concerning keywords
        if (!$result['crisis_detected']) {
            foreach ($this->concerning_keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $result['crisis_detected'] = true;
                    $result['level'] = 'concerning';
                    $result['matched_terms'][] = $keyword;
                }
            }
        }
        
        // Generate appropriate response based on crisis level
        if ($result['crisis_detected']) {
            // Log the crisis event
            $this->logCrisisEvent($user_id, $message, $result['level'], $result['matched_terms']);
            
            // Generate response
            $responses = $this->crisis_response_template[$result['level']];
            $response_index = array_rand($responses);
            $result['response'] = $responses[$response_index];
            
            // For urgent crises, always recommend resources
            if ($result['level'] === 'urgent') {
                $result['recommend_resources'] = true;
            } else {
                // For concerning messages, recommend resources 50% of the time
                // or if this is a repeated concerning message
                if (rand(0, 1) === 1 || $this->hasRecentCrisisEvents($user_id)) {
                    $result['recommend_resources'] = true;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Logs a crisis event to the database
     * 
     * @param int $user_id User ID
     * @param string $message Message that triggered crisis detection
     * @param string $level Crisis level (urgent or concerning)
     * @param array $matched_terms Terms that matched crisis keywords
     * @return bool Success status
     */
    private function logCrisisEvent($user_id, $message, $level, $matched_terms) {
        try {
            // Prepare the SQL query
            $stmt = $this->db->prepare("
                INSERT INTO crisis_events (user_id, message, level, matched_terms, timestamp)
                VALUES (:user_id, :message, :level, :matched_terms, NOW())
            ");
            
            // Bind parameters
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':message', $message, PDO::PARAM_STR);
            $stmt->bindParam(':level', $level, PDO::PARAM_STR);
            $stmt->bindParam(':matched_terms', json_encode($matched_terms), PDO::PARAM_STR);
            
            // Execute the query
            return $stmt->execute();
        } catch (PDOException $e) {
            // Log the error (in a production environment, you'd use a proper logging system)
            error_log('Crisis event logging error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Checks if the user has had recent crisis events
     * 
     * @param int $user_id User ID
     * @param int $hours Hours to look back
     * @return bool True if recent events exist
     */
    private function hasRecentCrisisEvents($user_id, $hours = 24) {
        if (!$user_id) return false;
        
        try {
            // Prepare the SQL query
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM crisis_events 
                WHERE user_id = :user_id 
                AND timestamp > DATE_SUB(NOW(), INTERVAL :hours HOUR)
            ");
            
            // Bind parameters
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':hours', $hours, PDO::PARAM_INT);
            
            // Execute the query
            $stmt->execute();
            
            // Get the result
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (PDOException $e) {
            error_log('Crisis event check error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gets immediate support resources
     * 
     * @return array Support resources
     */
    public function getEmergencyResources() {
        return [
            [
                'name' => 'National Suicide Prevention Lifeline',
                'description' => 'Free and confidential support for people in distress, 24/7.',
                'phone' => '1-800-273-8255',
                'website' => 'https://suicidepreventionlifeline.org/'
            ],
            [
                'name' => 'Crisis Text Line',
                'description' => 'Text HOME to 741741 from anywhere in the USA to text with a trained Crisis Counselor.',
                'phone' => '741741 (TEXT)',
                'website' => 'https://www.crisistextline.org/'
            ],
            [
                'name' => 'SAMHSA\'s National Helpline',
                'description' => 'Treatment referral and information service for individuals facing mental health or substance use disorders.',
                'phone' => '1-800-662-4357',
                'website' => 'https://www.samhsa.gov/find-help/national-helpline'
            ]
        ];
    }
}

// AJAX handler for crisis detection
if (isset($_POST['action']) && $_POST['action'] === 'analyze_message') {
    // Get database connection
    global $conn;
    
    // Initialize crisis detection
    $crisis_detector = new CrisisDetection($conn);
    
    // Get message and user ID from POST
    $message = isset($_POST['message']) ? $_POST['message'] : '';
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Analyze the message
    $result = $crisis_detector->analyzeMessage($message, $user_id);
    
    // If crisis detected and resources are recommended, include emergency resources
    if ($result['crisis_detected'] && $result['recommend_resources']) {
        $result['emergency_resources'] = $crisis_detector->getEmergencyResources();
    }
    
    // Return the result as JSON
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?> 