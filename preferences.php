<?php
session_start();
require_once 'config/database.php';

// Set to display all errors during development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Check if preferences column exists
$preferences_check = $conn->query("SHOW COLUMNS FROM users LIKE 'preferences'");
if ($preferences_check && $preferences_check->num_rows === 0) {
    // Add preferences column if it doesn't exist
    $add_column = $conn->query("ALTER TABLE users ADD COLUMN preferences TEXT DEFAULT NULL");
    if (!$add_column) {
        die("Error adding preferences column: " . $conn->error);
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT username, preferences FROM users WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Initialize preferences
$preferences = [];
if (!empty($user['preferences'])) {
    $preferences = json_decode($user['preferences'], true) ?: [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect preferences from form
    $preferences = [
        'prefer_quotes' => isset($_POST['prefer_quotes']),
        'response_style' => $_POST['response_style'] ?? 'balanced',
        'nickname' => trim($_POST['nickname'] ?? ''),
        'topics_of_interest' => isset($_POST['topics']) ? array_filter($_POST['topics']) : [],
        'preferred_resources' => isset($_POST['resources']) ? array_filter($_POST['resources']) : []
    ];
    
    // Save preferences to database
    $json_preferences = json_encode($preferences);
    $updateStmt = $conn->prepare("UPDATE users SET preferences = ? WHERE id = ?");
    if ($updateStmt === false) {
        $error = "Prepare failed for update: " . $conn->error;
    } else {
        $updateStmt->bind_param("si", $json_preferences, $user_id);
        
        if ($updateStmt->execute()) {
            $message = "Your preferences have been updated successfully!";
        } else {
            $error = "Error updating preferences: " . $updateStmt->error;
        }
    }
}

// Set page title for header include
$page_title = "Chatbot Preferences";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Mental Health Chatbot</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
                <!-- Page header -->
                <div class="page-header mb-4 text-center">
                    <h1 class="fw-bold"><i class="fas fa-sliders-h me-2 text-primary"></i> Personalization Settings</h1>
                    <p class="text-muted">Customize your chat experience to better suit your needs</p>
                </div>

                    <?php if (!empty($message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                <form method="POST" action="preferences.php">
                    <!-- Main preferences card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body p-0">
                            <!-- Tabs navigation -->
                            <ul class="nav nav-tabs nav-fill" id="preferenceTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true">
                                        <i class="fas fa-user me-2"></i> Personal
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab" aria-controls="content" aria-selected="false">
                                        <i class="fas fa-comment-dots me-2"></i> Content
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="topics-tab" data-bs-toggle="tab" data-bs-target="#topics" type="button" role="tab" aria-controls="topics" aria-selected="false">
                                        <i class="fas fa-list me-2"></i> Topics
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="resources-tab" data-bs-toggle="tab" data-bs-target="#resources" type="button" role="tab" aria-controls="resources" aria-selected="false">
                                        <i class="fas fa-book me-2"></i> Resources
                                    </button>
                                </li>
                            </ul>
                            
                            <!-- Tab content -->
                            <div class="tab-content p-4" id="preferenceTabsContent">
                                <!-- Personal preferences tab -->
                                <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                                    <h4 class="card-title mb-4">Personal Preferences</h4>
                                    
                                    <div class="mb-4">
                            <label for="nickname" class="form-label">Preferred Nickname</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="fas fa-id-card text-primary"></i>
                                            </span>
                            <input type="text" class="form-control" id="nickname" name="nickname" 
                                   value="<?php echo htmlspecialchars($preferences['nickname'] ?? ''); ?>"
                                   placeholder="How would you like the chatbot to call you?">
                                        </div>
                            <div class="form-text">If left empty, your username will be used.</div>
                        </div>
                        
                                    <div class="alert alert-info d-flex align-items-center">
                                        <i class="fas fa-info-circle fs-4 me-3"></i>
                                        <div>
                                            Your personal preferences help our AI provide a more personalized experience tailored just for you.
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Content preferences tab -->
                                <div class="tab-pane fade" id="content" role="tabpanel" aria-labelledby="content-tab">
                                    <h4 class="card-title mb-4">Content Preferences</h4>
                                    
                                    <div class="mb-4">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="prefer_quotes" name="prefer_quotes"
                                       <?php echo (!empty($preferences['prefer_quotes'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="prefer_quotes">
                                                        <strong>Include inspirational quotes</strong> in responses
                                </label>
                                                </div>
                                            </div>
                            </div>
                        </div>
                        
                                    <div class="mb-4">
                            <label class="form-label">Response Style</label>
                                        
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="card h-100 response-style-card">
                                                    <div class="card-body text-center p-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="response_style" id="style_balanced" value="balanced"
                                       <?php echo (empty($preferences['response_style']) || $preferences['response_style'] === 'balanced') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="style_balanced">
                                                                <i class="fas fa-balance-scale fs-3 mb-3 text-primary"></i>
                                                                <h5>Balanced</h5>
                                                                <p class="small text-muted">Mix of encouragement and practical advice</p>
                                </label>
                            </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="card h-100 response-style-card">
                                                    <div class="card-body text-center p-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="response_style" id="style_practical" value="practical"
                                       <?php echo (!empty($preferences['response_style']) && $preferences['response_style'] === 'practical') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="style_practical">
                                                                <i class="fas fa-tools fs-3 mb-3 text-primary"></i>
                                                                <h5>Practical</h5>
                                                                <p class="small text-muted">Focus on actionable steps and techniques</p>
                                </label>
                            </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4">
                                                <div class="card h-100 response-style-card">
                                                    <div class="card-body text-center p-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="response_style" id="style_emotional" value="emotional"
                                       <?php echo (!empty($preferences['response_style']) && $preferences['response_style'] === 'emotional') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="style_emotional">
                                                                <i class="fas fa-heart fs-3 mb-3 text-primary"></i>
                                                                <h5>Emotional</h5>
                                                                <p class="small text-muted">Focus on validation and empathy</p>
                                </label>
                            </div>
                        </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Topics of interest tab -->
                                <div class="tab-pane fade" id="topics" role="tabpanel" aria-labelledby="topics-tab">
                                    <h4 class="card-title mb-4">Topics of Interest</h4>
                                    <p class="text-muted mb-4">Select topics you're interested in receiving information about:</p>
                                    
                                    <div class="row g-3">
                                <?php
                                $topics = [
                                            'mindfulness' => ['name' => 'Mindfulness & Meditation', 'icon' => 'spa'],
                                            'stress' => ['name' => 'Stress Management', 'icon' => 'wind'],
                                            'sleep' => ['name' => 'Sleep Improvement', 'icon' => 'moon'],
                                            'productivity' => ['name' => 'Productivity & Motivation', 'icon' => 'laptop'],
                                            'relationships' => ['name' => 'Relationships & Connection', 'icon' => 'users'],
                                            'selfcare' => ['name' => 'Self-Care Practices', 'icon' => 'heart'],
                                            'anxiety' => ['name' => 'Anxiety Management', 'icon' => 'brain'],
                                            'mood' => ['name' => 'Mood Enhancement', 'icon' => 'smile'],
                                            'resilience' => ['name' => 'Building Resilience', 'icon' => 'shield-alt'],
                                            'gratitude' => ['name' => 'Gratitude Practices', 'icon' => 'hand-holding-heart']
                                ];
                                
                                $userTopics = $preferences['topics_of_interest'] ?? [];
                                
                                        foreach ($topics as $value => $topic): 
                                ?>
                                <div class="col-md-6">
                                            <div class="card border topic-card h-100 <?php echo (in_array($value, $userTopics)) ? 'selected' : ''; ?>">
                                                <div class="card-body p-3">
                                                    <div class="form-check d-flex align-items-center">
                                                        <input class="form-check-input topic-checkbox" type="checkbox" 
                                               name="topics[]" value="<?php echo $value; ?>" 
                                               id="topic_<?php echo $value; ?>"
                                               <?php echo (in_array($value, $userTopics)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label ms-3 w-100" for="topic_<?php echo $value; ?>">
                                                            <div class="d-flex align-items-center">
                                                                <div class="topic-icon me-3">
                                                                    <i class="fas fa-<?php echo $topic['icon']; ?>"></i>
                                                                </div>
                                                                <div>
                                                                    <strong><?php echo $topic['name']; ?></strong>
                                                                </div>
                                                            </div>
                                        </label>
                                                    </div>
                                                </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                                <!-- Resource preferences tab -->
                                <div class="tab-pane fade" id="resources" role="tabpanel" aria-labelledby="resources-tab">
                                    <h4 class="card-title mb-4">Resource Preferences</h4>
                                    <p class="text-muted mb-4">Select types of resources you prefer:</p>
                                    
                                    <div class="row g-3">
                                <?php
                                $resources = [
                                            'articles' => ['name' => 'Articles & Reading Material', 'icon' => 'newspaper'],
                                            'videos' => ['name' => 'Videos & Tutorials', 'icon' => 'video'],
                                            'exercises' => ['name' => 'Interactive Exercises', 'icon' => 'dumbbell'],
                                            'quotes' => ['name' => 'Inspirational Quotes', 'icon' => 'quote-left'],
                                            'communities' => ['name' => 'Community Support Groups', 'icon' => 'users'],
                                            'apps' => ['name' => 'Mobile Apps & Tools', 'icon' => 'mobile-alt']
                                ];
                                
                                $userResources = $preferences['preferred_resources'] ?? [];
                                
                                        foreach ($resources as $value => $resource): 
                                ?>
                                <div class="col-md-6">
                                            <div class="card border resource-card h-100 <?php echo (in_array($value, $userResources)) ? 'selected' : ''; ?>">
                                                <div class="card-body p-3">
                                                    <div class="form-check d-flex align-items-center">
                                                        <input class="form-check-input resource-checkbox" type="checkbox" 
                                               name="resources[]" value="<?php echo $value; ?>" 
                                               id="resource_<?php echo $value; ?>"
                                               <?php echo (in_array($value, $userResources)) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label ms-3 w-100" for="resource_<?php echo $value; ?>">
                                                            <div class="d-flex align-items-center">
                                                                <div class="resource-icon me-3">
                                                                    <i class="fas fa-<?php echo $resource['icon']; ?>"></i>
                                                                </div>
                                                                <div>
                                                                    <strong><?php echo $resource['name']; ?></strong>
                                                                </div>
                                                            </div>
                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Card footer with save buttons -->
                        <div class="card-footer bg-light p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="chat.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Return to Chat
                                </a>
                                <div>
                                    <button type="reset" class="btn btn-outline-secondary me-2">
                                        <i class="fas fa-undo me-2"></i>Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-save me-2"></i>Save Preferences
                                    </button>
                                </div>
                            </div>
                        </div>
                        </div>
                    </form>
                
                <!-- Help card -->
                <div class="card border-0 bg-light">
                    <div class="card-body p-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-lightbulb text-warning fs-3"></i>
                            </div>
                            <div class="ms-3">
                                <h5>Why set preferences?</h5>
                                <p class="mb-0">Your preferences help our AI assistant provide more personalized support. The more we know about your interests and preferences, the more tailored your conversation experience will be.</p>
                            </div>
                        </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Custom styles for preference page */
        .page-header h1 {
            color: var(--primary-color);
            font-size: 2.25rem;
            margin-bottom: 0.5rem;
        }
        
        .nav-tabs .nav-link {
            color: var(--dark-color);
            font-weight: 500;
            padding: 1rem 1.5rem;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .nav-tabs .nav-link:hover:not(.active) {
            border-bottom: 3px solid rgba(79, 70, 229, 0.3);
            background-color: rgba(79, 70, 229, 0.03);
        }
        
        .topic-card, .resource-card {
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid #e5e7eb !important;
        }
        
        .topic-card:hover, .resource-card:hover {
            border-color: rgba(79, 70, 229, 0.3) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .topic-card.selected, .resource-card.selected {
            border-color: var(--primary-color) !important;
            background-color: rgba(79, 70, 229, 0.03);
        }
        
        .topic-icon, .resource-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            font-size: 1.25rem;
        }
        
        .response-style-card {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .response-style-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transform: translateY(-3px);
        }
        
        .form-check-input:checked ~ label .response-style-card {
            border-color: var(--primary-color);
            background-color: rgba(79, 70, 229, 0.03);
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Make entire topic and resource cards clickable
            document.querySelectorAll('.topic-card, .resource-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    // Don't trigger if clicking the checkbox itself
                    if (e.target.type !== 'checkbox') {
                        const checkbox = this.querySelector('input[type="checkbox"]');
                        checkbox.checked = !checkbox.checked;
                        
                        // Toggle selected class
                        this.classList.toggle('selected', checkbox.checked);
                    } else {
                        // For when checkbox itself is clicked
                        this.classList.toggle('selected', e.target.checked);
                    }
                });
            });
            
            // Initialize selected state based on checkbox state
            document.querySelectorAll('.topic-checkbox, .resource-checkbox').forEach(checkbox => {
                const card = checkbox.closest('.card');
                card.classList.toggle('selected', checkbox.checked);
            });
            
            // Show tab based on URL hash
            const hash = window.location.hash;
            if (hash) {
                const triggerEl = document.querySelector(`#preferenceTabs button[data-bs-target="${hash}"]`);
                if (triggerEl) {
                    new bootstrap.Tab(triggerEl).show();
                }
            }
            
            // Update URL hash when changing tabs
            document.querySelectorAll('#preferenceTabs button').forEach(button => {
                button.addEventListener('shown.bs.tab', function(e) {
                    window.location.hash = e.target.getAttribute('data-bs-target');
                });
            });
        });
    </script>

<?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>