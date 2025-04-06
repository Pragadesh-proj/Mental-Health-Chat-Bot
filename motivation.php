<?php
session_start();
require_once 'config/database.php';
require_once 'motivation_generator.php';

// Set page title for header include
$page_title = "Motivation Generator";
$additional_css = '<link href="assets/css/motivation.css" rel="stylesheet">';
$additional_js = '<script src="assets/js/motivation.js" defer></script>';

// Initialize motivation generator
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$generator = new MotivationGenerator($conn, $user_id);

// Get user's current mood if available
$current_mood = 'neutral';
if ($user_id && isset($_SESSION['last_mood'])) {
    $current_mood = $_SESSION['last_mood'];
}

// Generate initial motivational content
$daily_affirmation = $generator->generateDailyAffirmation();
$motivational_quote = $generator->generateQuote(null, $current_mood);

// Get motivation categories for the category selector
$categories = [
    'daily' => 'Daily Affirmations',
    'challenge' => 'Overcoming Challenges',
    'growth' => 'Personal Growth',
    'calm' => 'Finding Peace',
    'success' => 'Achievement & Success',
    'gratitude' => 'Gratitude & Positivity'
];

// Get mood options for the mood selector
$moods = [
    'very_negative' => 'Very Negative',
    'negative' => 'Negative',
    'neutral' => 'Neutral',
    'positive' => 'Positive',
    'very_positive' => 'Very Positive',
    'anxious' => 'Anxious',
    'sad' => 'Sad',
    'angry' => 'Angry',
    'stressed' => 'Stressed',
    'tired' => 'Tired',
    'happy' => 'Happy',
    'excited' => 'Excited'
];

include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="motivation-header text-center mb-5">
                <div class="motivation-icon mb-3">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h1>Personal Motivation Generator</h1>
                <p class="lead">Discover personalized inspiration to boost your mental wellbeing</p>
            </div>
            
            <!-- Current Mood Selection -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-smile me-2"></i>How are you feeling today?</h3>
                </div>
                <div class="card-body">
                    <p>Select your current mood to get more personalized motivational content.</p>
                    
                    <div class="mood-selector mb-4">
                        <div class="row">
                            <?php foreach ($moods as $mood_key => $mood_name): ?>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="mood-option <?php echo ($mood_key == $current_mood) ? 'active' : ''; ?>" data-mood="<?php echo $mood_key; ?>">
                                    <span class="mood-emoji">
                                        <?php
                                        $emoji = '😐'; // Default neutral
                                        switch ($mood_key) {
                                            case 'very_negative': $emoji = '😣'; break;
                                            case 'negative': $emoji = '😕'; break;
                                            case 'neutral': $emoji = '😐'; break;
                                            case 'positive': $emoji = '🙂'; break;
                                            case 'very_positive': $emoji = '😁'; break;
                                            case 'anxious': $emoji = '😰'; break;
                                            case 'sad': $emoji = '😢'; break;
                                            case 'angry': $emoji = '😠'; break;
                                            case 'stressed': $emoji = '😫'; break;
                                            case 'tired': $emoji = '😴'; break;
                                            case 'happy': $emoji = '😊'; break;
                                            case 'excited': $emoji = '🤩'; break;
                                        }
                                        echo $emoji;
                                        ?>
                                    </span>
                                    <span class="mood-label"><?php echo $mood_name; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <input type="hidden" id="selected-mood" value="<?php echo $current_mood; ?>">
                    </div>
                </div>
            </div>
            
            <!-- Daily Affirmation -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-sun me-2"></i>Your Daily Affirmation</h3>
                </div>
                <div class="card-body">
                    <div class="daily-affirmation">
                        <blockquote class="blockquote text-center">
                            <p id="affirmation-text"><?php echo $daily_affirmation['affirmation']; ?></p>
                        </blockquote>
                        
                        <div class="text-center mt-4">
                            <button id="new-affirmation-btn" class="btn btn-outline-primary">
                                <i class="fas fa-sync-alt me-2"></i>Generate New Affirmation
                            </button>
                            
                            <button id="speak-affirmation-btn" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-volume-up me-2"></i>Speak
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Motivational Quote -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-quote-left me-2"></i>Inspirational Quote</h3>
                    
                    <div class="category-selector">
                        <select id="quote-category" class="form-select">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $cat_key => $cat_name): ?>
                            <option value="<?php echo $cat_key; ?>"><?php echo $cat_name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="motivational-quote">
                        <blockquote class="blockquote text-center">
                            <p id="quote-text"><?php echo $motivational_quote['content']; ?></p>
                        </blockquote>
                        
                        <div class="text-center mt-4">
                            <button id="new-quote-btn" class="btn btn-outline-primary">
                                <i class="fas fa-sync-alt me-2"></i>Generate New Quote
                            </button>
                            
                            <button id="speak-quote-btn" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-volume-up me-2"></i>Speak
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Personalized Motivation Plan -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-map-signs me-2"></i>Your Motivation Plan</h3>
                </div>
                <div class="card-body">
                    <p>Generate a personalized motivation plan based on your current mood and preferences.</p>
                    
                    <div id="motivation-plan-container" class="d-none">
                        <div id="plan-title" class="h4 text-center mb-3"></div>
                        <div id="plan-introduction" class="text-center mb-4"></div>
                        
                        <div id="plan-sections" class="plan-sections">
                            <!-- Plan sections will be inserted here -->
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button id="generate-plan-btn" class="btn btn-primary">
                            <i class="fas fa-magic me-2"></i>Create My Motivation Plan
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Motivation History (for logged in users) -->
            <?php if ($user_id): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-history me-2"></i>Your Motivation History</h3>
                </div>
                <div class="card-body">
                    <div id="motivation-history-container">
                        <p class="text-center">Loading your motivation history...</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-4">
                <a href="chat.php" class="btn btn-lg btn-outline-primary">
                    <i class="fas fa-comment-dots me-2"></i>Return to Chat
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 