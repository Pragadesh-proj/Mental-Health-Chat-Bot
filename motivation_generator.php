<?php
/**
 * AI-Powered Motivation Generator
 * Creates personalized motivational content based on user preferences and emotional state
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'config/database.php';

class MotivationGenerator {
    private $db;
    private $user_id;
    private $user_name;
    private $last_mood;
    private $preferences;
    
    // Motivational content categories
    private $categories = [
        'daily' => 'Daily Affirmations',
        'challenge' => 'Overcoming Challenges',
        'growth' => 'Personal Growth',
        'calm' => 'Finding Peace',
        'success' => 'Achievement & Success',
        'gratitude' => 'Gratitude & Positivity'
    ];
    
    /**
     * Constructor initializes database connection and user data
     * 
     * @param mysqli $db Database connection
     * @param int $user_id User ID
     */
    public function __construct($db, $user_id = null) {
        $this->db = $db;
        $this->user_id = $user_id;
        
        // Load user data if user is logged in
        if ($this->user_id) {
            $this->loadUserData();
        }
    }
    
    /**
     * Load user data from database
     */
    private function loadUserData() {
        try {
            // Get user name
            $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
            if ($stmt === false) {
                error_log('Error preparing statement: ' . $this->db->error);
                return;
            }
            
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user) {
                $this->user_name = $user['username'];
            }
            
            // Check if user_preferences table exists
            $table_check = $this->db->query("SHOW TABLES LIKE 'user_preferences'");
            if ($table_check->num_rows == 0) {
                // If table doesn't exist, create it
                $sql = "CREATE TABLE user_preferences (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    user_id INT(11) NOT NULL,
                    interests TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
                
                if (!$this->db->query($sql)) {
                    error_log('Error creating user_preferences table: ' . $this->db->error);
                }
            }
            
            // Get user preferences
            $pref_stmt = $this->db->prepare("SELECT * FROM user_preferences WHERE user_id = ?");
            if ($pref_stmt === false) {
                error_log('Error preparing preferences statement: ' . $this->db->error);
                return;
            }
            
            $pref_stmt->bind_param("i", $this->user_id);
            $pref_stmt->execute();
            $pref_result = $pref_stmt->get_result();
            $this->preferences = $pref_result->fetch_assoc();
            
            // Check if user_state table exists
            $state_table_check = $this->db->query("SHOW TABLES LIKE 'user_state'");
            if ($state_table_check->num_rows == 0) {
                // If table doesn't exist, create it
                $sql = "CREATE TABLE user_state (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    user_id INT(11) NOT NULL,
                    last_mood VARCHAR(50) DEFAULT 'neutral',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
                
                if (!$this->db->query($sql)) {
                    error_log('Error creating user_state table: ' . $this->db->error);
                }
            }
            
            // Get user's last mood
            $mood_stmt = $this->db->prepare("SELECT last_mood FROM user_state WHERE user_id = ?");
            if ($mood_stmt === false) {
                error_log('Error preparing mood statement: ' . $this->db->error);
                return;
            }
            
            $mood_stmt->bind_param("i", $this->user_id);
            $mood_stmt->execute();
            $mood_result = $mood_stmt->get_result();
            $state = $mood_result->fetch_assoc();
            
            if ($state) {
                $this->last_mood = $state['last_mood'];
            }
        } catch (Exception $e) {
            error_log('Error loading user data: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate a personalized motivational quote
     * 
     * @param string $category Motivation category
     * @param string $mood Current user mood
     * @return array Generated content
     */
    public function generateQuote($category = null, $mood = null) {
        // If no category specified, select based on mood or random
        if (!$category) {
            $category = $this->getCategoryForMood($mood ?? $this->last_mood);
        }
        
        // Get quotes for the selected category
        $quotes = $this->getQuotesForCategory($category);
        
        // If no quotes found, use general quotes
        if (empty($quotes)) {
            $quotes = $this->getQuotesForCategory('daily');
        }
        
        // Select a random quote
        $index = array_rand($quotes);
        $quote = $quotes[$index];
        
        // Personalize the quote if user is logged in
        if ($this->user_id && $this->user_name) {
            $quote = $this->personalizeContent($quote);
        }
        
        return [
            'success' => true,
            'content' => $quote,
            'category' => $category,
            'category_name' => $this->categories[$category] ?? 'Motivation'
        ];
    }
    
    /**
     * Generate a motivational message based on user's mood and preferences
     * 
     * @param string $mood Current user mood
     * @return array Generated content
     */
    public function generateMotivationalMessage($mood = null) {
        $currentMood = $mood ?? $this->last_mood ?? 'neutral';
        
        // Get message templates based on mood
        $templates = $this->getMessageTemplatesForMood($currentMood);
        
        // Select a random template
        $index = array_rand($templates);
        $message = $templates[$index];
        
        // Personalize the message
        if ($this->user_id && $this->user_name) {
            $message = $this->personalizeContent($message);
        }
        
        // Add a relevant quote to enhance the message
        $quoteData = $this->generateQuote(null, $currentMood);
        
        return [
            'success' => true,
            'message' => $message,
            'quote' => $quoteData['content'],
            'mood' => $currentMood
        ];
    }
    
    /**
     * Generate a daily affirmation
     * 
     * @return array Generated affirmation
     */
    public function generateDailyAffirmation() {
        $affirmations = [
            "I am capable of overcoming any challenges that come my way.",
            "I choose to focus on what I can control and let go of what I cannot.",
            "My potential to succeed is infinite.",
            "Today, I am brimming with energy and overflowing with joy.",
            "I am conquering my fears and becoming stronger each day.",
            "I believe in my abilities and am confident in my future.",
            "I am in charge of how I feel and today I choose happiness.",
            "I am worthy of love, respect, and acceptance.",
            "My mind is open to new ideas and perspectives.",
            "I trust myself to make the right decisions for my wellbeing.",
            "I am growing and evolving into the best version of myself.",
            "I have the power to create positive change in my life.",
            "I radiate positivity and attract positive experiences.",
            "I am resilient and can recover from any setback.",
            "My contributions to the world are valuable and important."
        ];
        
        // Select a random affirmation
        $index = array_rand($affirmations);
        $affirmation = $affirmations[$index];
        
        // Personalize the affirmation
        if ($this->user_id && $this->user_name) {
            $affirmation = $this->personalizeContent($affirmation);
        }
        
        return [
            'success' => true,
            'affirmation' => $affirmation
        ];
    }
    
    /**
     * Create a personalized motivation plan based on user preferences
     * 
     * @return array Motivation plan
     */
    public function createMotivationPlan() {
        $plan = [
            'title' => 'Your Personal Motivation Plan',
            'introduction' => "Here's a customized plan to help you stay motivated and positive.",
            'sections' => []
        ];
        
        // Morning affirmation
        $affirmation = $this->generateDailyAffirmation();
        $plan['sections'][] = [
            'title' => 'Morning Affirmation',
            'content' => $affirmation['affirmation'],
            'instructions' => 'Repeat this affirmation to yourself each morning while looking in the mirror.'
        ];
        
        // Daily goal setting
        $plan['sections'][] = [
            'title' => 'Daily Goal Setting',
            'content' => 'Set one small achievable goal for today.',
            'instructions' => 'Write down your goal and place it somewhere visible. At the end of the day, reflect on your progress.'
        ];
        
        // Mindfulness practice
        $plan['sections'][] = [
            'title' => 'Mindfulness Practice',
            'content' => 'Take 5 minutes to practice mindful breathing.',
            'instructions' => 'Find a quiet space, close your eyes, and focus on your breath. Count each inhale and exhale.'
        ];
        
        // Personalized activity based on preferences
        if ($this->preferences && isset($this->preferences['interests'])) {
            $interests = json_decode($this->preferences['interests'], true);
            if (!empty($interests)) {
                $interest = $interests[array_rand($interests)];
                $plan['sections'][] = [
                    'title' => 'Do Something You Enjoy',
                    'content' => "Spend some time engaged in {$interest}.",
                    'instructions' => "Even 15 minutes doing something you love can boost your mood and motivation."
                ];
            }
        }
        
        // Evening reflection
        $plan['sections'][] = [
            'title' => 'Evening Reflection',
            'content' => 'Reflect on three positive things that happened today.',
            'instructions' => 'Write them down in a gratitude journal before going to bed.'
        ];
        
        // Personalize the plan
        if ($this->user_id && $this->user_name) {
            $plan['introduction'] = $this->personalizeContent($plan['introduction']);
            $plan['title'] = $this->personalizeContent($plan['title']);
        }
        
        return [
            'success' => true,
            'plan' => $plan
        ];
    }
    
    /**
     * Get the appropriate motivation category based on mood
     * 
     * @param string $mood User mood
     * @return string Category key
     */
    private function getCategoryForMood($mood = null) {
        // Default to random category if no mood specified
        if (!$mood) {
            $categories = array_keys($this->categories);
            return $categories[array_rand($categories)];
        }
        
        // Map moods to appropriate categories
        $moodMap = [
            'very_negative' => 'challenge',
            'negative' => 'challenge',
            'neutral' => 'daily',
            'positive' => 'success',
            'very_positive' => 'gratitude',
            'anxious' => 'calm',
            'sad' => 'growth',
            'angry' => 'calm',
            'stressed' => 'calm',
            'tired' => 'growth',
            'happy' => 'gratitude',
            'excited' => 'success'
        ];
        
        return $moodMap[$mood] ?? 'daily';
    }
    
    /**
     * Get quotes for a specific category
     * 
     * @param string $category Quote category
     * @return array Array of quotes
     */
    private function getQuotesForCategory($category) {
        $quotes = [
            'daily' => [
                "The only way to do great work is to love what you do.",
                "Believe you can and you're halfway there.",
                "Your attitude determines your direction.",
                "Every day is a new beginning.",
                "Success is not final, failure is not fatal: It is the courage to continue that counts."
            ],
            'challenge' => [
                "Challenges are what make life interesting. Overcoming them is what makes life meaningful.",
                "The greater the obstacle, the more glory in overcoming it.",
                "In the middle of difficulty lies opportunity.",
                "The only limit to our realization of tomorrow is our doubts of today.",
                "Strength doesn't come from what you can do, it comes from overcoming the things you once thought you couldn't."
            ],
            'growth' => [
                "Life is about growing and improving and getting better.",
                "Growth is the only evidence of life.",
                "Change is inevitable. Growth is optional.",
                "The only journey is the journey within.",
                "We must be willing to let go of the life we planned so as to have the life that is waiting for us."
            ],
            'calm' => [
                "Peace comes from within. Do not seek it without.",
                "Within you, there is a stillness and a sanctuary to which you can retreat at any time.",
                "Calm mind brings inner strength and self-confidence.",
                "The mind is like water. When it's turbulent, it's difficult to see. When it's calm, everything becomes clear.",
                "Feelings come and go like clouds in a windy sky. Conscious breathing is my anchor."
            ],
            'success' => [
                "Success is not the key to happiness. Happiness is the key to success.",
                "The secret of success is to do the common thing uncommonly well.",
                "Success is stumbling from failure to failure with no loss of enthusiasm.",
                "The road to success and the road to failure are almost exactly the same.",
                "Success usually comes to those who are too busy to be looking for it."
            ],
            'gratitude' => [
                "Gratitude turns what we have into enough.",
                "The more you are in a state of gratitude, the more you will attract things to be grateful for.",
                "Gratitude is the healthiest of all human emotions.",
                "When we focus on our gratitude, the tide of disappointment goes out and the tide of love rushes in.",
                "Gratitude is not only the greatest of virtues, but the parent of all others."
            ]
        ];
        
        return $quotes[$category] ?? $quotes['daily'];
    }
    
    /**
     * Get message templates for a specific mood
     * 
     * @param string $mood User's mood
     * @return array Array of message templates
     */
    private function getMessageTemplatesForMood($mood) {
        $templates = [
            'very_negative' => [
                "I understand you're going through a really tough time. Remember that even the darkest night will end and the sun will rise. Take one small step forward today.",
                "When everything feels overwhelming, focus on just the next breath, the next step. You have the strength within you to get through this difficult moment.",
                "It's okay to not be okay sometimes. Be gentle with yourself today, and remember that this feeling, however painful, is temporary."
            ],
            'negative' => [
                "I see that things aren't going well right now. Remember that every setback is setting you up for a comeback. What small positive action can you take today?",
                "Tough days are just that - days. They pass. Your resilience is stronger than any challenge you're facing right now.",
                "When life feels heavy, it's okay to put down the weight for a moment. Take time to rest, then remember your strength."
            ],
            'neutral' => [
                "Today is a fresh canvas waiting for your unique colors. What small positive change will you make today?",
                "Sometimes a neutral day is the perfect opportunity to plant seeds for future happiness. What seed could you plant today?",
                "Balance is a beautiful thing. Use this steady moment to set an intention that aligns with your values."
            ],
            'positive' => [
                "Your positive energy is contagious! Harness this good feeling and channel it into something meaningful today.",
                "It's wonderful to see you in good spirits! This positive momentum can help you tackle something you've been putting off.",
                "Enjoy this positive feeling and consider how you might store some of this energy for more challenging days ahead."
            ],
            'very_positive' => [
                "You're absolutely shining today! This is the perfect time to dream bigger and reach for those ambitious goals.",
                "What a wonderful state of mind you're in! Use this exceptional energy to bring joy to yourself and others today.",
                "You're on fire today! This peak state is perfect for making bold moves toward your most cherished dreams."
            ],
            'anxious' => [
                "When anxiety clouds your mind, remember to ground yourself. Focus on five things you can see, four you can touch, three you can hear, two you can smell, and one you can taste.",
                "Anxiety is like a wave - it rises, peaks, and eventually subsides. Breathe through it and know that calmer waters are ahead.",
                "Your worried thoughts are not facts. Gently challenge them by asking: What evidence supports this worry? What would I tell a friend who had this worry?"
            ],
            'sad' => [
                "It's okay to feel sad. Honor your emotions by acknowledging them, then remember that you are more than what you feel in this moment.",
                "Sadness often comes to teach us something. Listen to what it might be telling you, then take one small step toward light.",
                "On sad days, be extra gentle with yourself. What small comfort could you offer yourself today, just as you would to a dear friend?"
            ],
            'angry' => [
                "Anger often masks deeper emotions. When you feel ready, try to look beneath it. What is your anger protecting?",
                "Your anger is valid, but it doesn't have to control your actions. Take a moment to breathe before deciding how to respond.",
                "Channel the powerful energy of anger into constructive action. What positive change could this emotion fuel?"
            ],
            'stressed' => [
                "When stress feels overwhelming, return to the basics: deep breathing, staying hydrated, and taking breaks. Your work will still be there after you've centered yourself.",
                "Stress is often caused by trying to control too much. What can you influence right now, and what might you need to release?",
                "Your body carries the weight of your stress. Take a moment to scan for tension and consciously relax those areas, starting with your shoulders and jaw."
            ],
            'tired' => [
                "Fatigue is your body's way of asking for what it needs. Can you honor that request, even in small ways?",
                "Sometimes the most productive thing you can do is rest. Give yourself permission to recharge without guilt.",
                "Energy follows attention. When tired, focus on what truly matters today and let go of the rest."
            ],
            'happy' => [
                "Happiness looks beautiful on you! How might you spread some of this sunshine to others today?",
                "Moments of happiness are precious. Take a mental photograph of this feeling to revisit when skies are gray.",
                "Your happiness creates a ripple effect. Notice how your positive energy influences those around you."
            ],
            'excited' => [
                "Channel this wonderful excitement into focused action. What meaningful progress could this energy fuel today?",
                "Excitement is the spark of creation. What new possibility is emerging that has captured your imagination?",
                "Ride this wave of excitement while staying grounded in your values. What aligned action would make the most of this energy?"
            ]
        ];
        
        // Default to neutral if mood not found
        return $templates[$mood] ?? $templates['neutral'];
    }
    
    /**
     * Personalize content with user information
     * 
     * @param string $content Content to personalize
     * @return string Personalized content
     */
    private function personalizeContent($content) {
        // Add name if available
        if ($this->user_name) {
            // 50% chance to add name at beginning
            if (rand(0, 1) === 1) {
                $greetings = [
                    "{$this->user_name}, ",
                    "Hey {$this->user_name}, ",
                    "Dear {$this->user_name}, "
                ];
                $greeting = $greetings[array_rand($greetings)];
                $content = $greeting . lcfirst($content);
            } 
            // Otherwise, possibly replace "you" with name
            else if (strpos($content, "you") !== false && rand(0, 2) === 1) {
                $content = str_replace(" you ", " {$this->user_name} ", $content);
            }
        }
        
        return $content;
    }
    
    /**
     * Save a generated motivation to the database
     * 
     * @param string $content The motivational content
     * @param string $type Type of motivation (quote, message, affirmation, plan)
     * @return bool Success status
     */
    public function saveMotivation($content, $type) {
        if (!$this->user_id) return false;
        
        try {
            // Check if motivation_history table exists
            $table_check = $this->db->query("SHOW TABLES LIKE 'motivation_history'");
            if ($table_check->num_rows == 0) {
                // If table doesn't exist, create it
                $sql = "CREATE TABLE motivation_history (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    user_id INT(11) NOT NULL,
                    content TEXT NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
                
                if (!$this->db->query($sql)) {
                    error_log('Error creating motivation_history table: ' . $this->db->error);
                    return false;
                }
            }
            
            $stmt = $this->db->prepare("INSERT INTO motivation_history (user_id, content, type, created_at) VALUES (?, ?, ?, NOW())");
            if ($stmt === false) {
                error_log('Error preparing statement: ' . $this->db->error);
                return false;
            }
            
            $stmt->bind_param("iss", $this->user_id, $content, $type);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Error saving motivation: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's motivation history
     * 
     * @param int $limit Number of items to retrieve
     * @return array Motivation history
     */
    public function getMotivationHistory($limit = 10) {
        if (!$this->user_id) return [];
        
        try {
            // Check if motivation_history table exists
            $table_check = $this->db->query("SHOW TABLES LIKE 'motivation_history'");
            if ($table_check->num_rows == 0) {
                return [];
            }
            
            $stmt = $this->db->prepare("SELECT * FROM motivation_history WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
            if ($stmt === false) {
                error_log('Error preparing statement: ' . $this->db->error);
                return [];
            }
            
            $stmt->bind_param("ii", $this->user_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $history = [];
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
            
            return $history;
        } catch (Exception $e) {
            error_log('Error retrieving motivation history: ' . $e->getMessage());
            return [];
        }
    }
}

// AJAX handler
if (isset($_POST['action'])) {
    // Get database connection
    global $conn;
    
    // Get user ID from session if available
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // Initialize motivation generator
    $generator = new MotivationGenerator($conn, $user_id);
    
    // Process based on action
    $result = [];
    
    switch ($_POST['action']) {
        case 'generate_quote':
            $category = isset($_POST['category']) ? $_POST['category'] : null;
            $mood = isset($_POST['mood']) ? $_POST['mood'] : null;
            $result = $generator->generateQuote($category, $mood);
            
            // Save to history if user is logged in
            if ($user_id && $result['success']) {
                $generator->saveMotivation($result['content'], 'quote');
            }
            break;
            
        case 'generate_message':
            $mood = isset($_POST['mood']) ? $_POST['mood'] : null;
            $result = $generator->generateMotivationalMessage($mood);
            
            // Save to history if user is logged in
            if ($user_id && $result['success']) {
                $generator->saveMotivation($result['message'], 'message');
            }
            break;
            
        case 'generate_affirmation':
            $result = $generator->generateDailyAffirmation();
            
            // Save to history if user is logged in
            if ($user_id && $result['success']) {
                $generator->saveMotivation($result['affirmation'], 'affirmation');
            }
            break;
            
        case 'create_plan':
            $result = $generator->createMotivationPlan();
            
            // Save to history if user is logged in
            if ($user_id && $result['success']) {
                $generator->saveMotivation(json_encode($result['plan']), 'plan');
            }
            break;
            
        case 'get_history':
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
            $history = $generator->getMotivationHistory($limit);
            $result = ['success' => true, 'history' => $history];
            break;
            
        default:
            $result = ['success' => false, 'error' => 'Invalid action'];
    }
    
    // Return the result as JSON
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?> 