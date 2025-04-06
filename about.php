<?php
session_start();
require_once 'config/database.php';
                
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$show_welcome = false;
if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome']) {
    $show_welcome = true;
    // Reset the flag so it only shows once
    $_SESSION['show_welcome'] = false;
}

// Set page title for header include
$page_title = "About Us";
?>

<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <h2 class="section-title">
                <i class="fas fa-brain"></i> How Our AI Mental Health Chatbot Works
            </h2>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Core Technology</h3>
                </div>
                <div class="card-body">
                    <p>Our AI chatbot leverages advanced natural language processing and machine learning algorithms to provide personalized mental health support. Here's how it works:</p>
                    
                    <ul class="list-group list-group-flush mt-3">
                        <li class="list-group-item">
                            <i class="fas fa-comment-dots text-primary me-2"></i>
                            <strong>Conversational Interface:</strong> Engage in natural text conversations just like chatting with a supportive friend.
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-brain text-primary me-2"></i>
                            <strong>Evidence-Based Techniques:</strong> Applies principles from cognitive behavioral therapy, mindfulness practices, and stress management.
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-user-shield text-primary me-2"></i>
                            <strong>Personalized Support:</strong> Adapts responses based on your conversation history and reported mood patterns.
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            <strong>Progress Tracking:</strong> Monitors your emotional trends over time to provide more targeted support.
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-clock text-primary me-2"></i>
                            <strong>24/7 Availability:</strong> Access supportive conversations whenever you need them, day or night.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="process-flow-container">
                <h4 class="mb-4">The Conversation Process</h4>
                
                <div class="process-flow">
                    <div class="process-step active">
                        <div class="step-icon">
                            <i class="fas fa-comment-alt"></i>
                        </div>
                        <h5>You Share</h5>
                        <p>Express your thoughts, feelings, or concerns</p>
                    </div>
                    
                    <div class="process-connector"></div>
                    
                    <div class="process-step active">
                        <div class="step-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h5>AI Analyzes</h5>
                        <p>Processes your message using NLP</p>
                    </div>
                    
                    <div class="process-connector"></div>
                    
                    <div class="process-step active">
                        <div class="step-icon">
                            <i class="fas fa-reply"></i>
                        </div>
                        <h5>Response</h5>
                        <p>Delivers personalized support</p>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="chat.php" class="btn btn-primary">
                        <i class="fas fa-comments me-2"></i> Try It Now
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-12">
            <h2 class="section-title">
                <i class="fas fa-star"></i> Features Available
            </h2>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-4 mb-4">
            <div class="feature-card h-100">
                <div class="feature-icon" style="background-color: #e0f2fe; color: #0ea5e9;">
                    <i class="fas fa-microphone"></i>
                </div>
                <h4>Voice Recognition</h4>
                <p>Speak naturally to our AI using advanced voice recognition technology that understands your tone and context.</p>
                <div class="feature-progress">
                    <div class="progress-bar" style="width: 90%"></div>
                    <span>90%</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="feature-card h-100">
                <div class="feature-icon" style="background-color: #fef2f2; color: #ef4444;">
                    <i class="fas fa-globe"></i>
                </div>
                <h4>Multi-Language Support</h4>
                <p>Communicate in your preferred language with support for multiple languages to make mental health care more accessible.</p>
                <div class="feature-progress">
                    <div class="progress-bar" style="width: 85%"></div>
                    <span>85%</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="feature-card h-100">
                <div class="feature-icon" style="background-color: #ecfdf5; color: #10b981;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h4>Smart Scheduling</h4>
                <p>Set reminders for wellness activities, meditation sessions, and exercises to maintain consistent mental health practices.</p>
                <div class="feature-progress">
                    <div class="progress-bar" style="width: 95%"></div>
                    <span>95%</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="feature-card h-100">
                <div class="feature-icon" style="background-color: #f0f9ff; color: #3b82f6;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h4>Progress Tracking</h4>
                <p>Monitor your mental wellness journey with detailed analytics on mood patterns, goal completion, and growth over time.</p>
                <div class="feature-progress">
                    <div class="progress-bar" style="width: 88%"></div>
                    <span>88%</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="feature-card h-100">
                <div class="feature-icon" style="background-color: #fffbeb; color: #f59e0b;">
                    <i class="fas fa-lock"></i>
                </div>
                <h4>Privacy & Security</h4>
                <p>All conversations are completely confidential and encrypted to ensure your personal information remains secure and private.</p>
                <div class="feature-progress">
                    <div class="progress-bar" style="width: 100%"></div>
                    <span>100%</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="feature-card h-100">
                <div class="feature-icon" style="background-color: #f3e8ff; color: #8b5cf6;">
                    <i class="fas fa-book"></i>
                </div>
                <h4>Resource Library</h4>
                <p>Access a comprehensive collection of mental health resources, articles, exercises, and educational materials at any time.</p>
                <div class="feature-progress">
                    <div class="progress-bar" style="width: 92%"></div>
                    <span>92%</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-12">
            <h2 class="section-title">
                <i class="fas fa-exclamation-triangle"></i> Limitations to Consider
            </h2>
            <p class="text-muted">While our AI chatbot provides valuable support, it's important to understand its limitations:</p>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-warning text-white">
                    <h3 class="card-title">Important Considerations</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <i class="fas fa-exclamation-circle text-warning fs-3"></i>
                        </div>
                        <div>
                            <h5>Not a Crisis Service</h5>
                            <p>Our AI is not equipped to handle severe mental health crises. Please contact emergency services or a crisis helpline if you're experiencing a mental health emergency.</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <i class="fas fa-user-md text-warning fs-3"></i>
                        </div>
                        <div>
                            <h5>Not a Replacement for Professional Care</h5>
                            <p>The chatbot complements but does not replace therapy or treatment from qualified mental health professionals.</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-heart text-warning fs-3"></i>
                        </div>
                        <div>
                            <h5>Limited Emotional Understanding</h5>
                            <p>Despite advanced AI, our chatbot cannot truly empathize or understand emotions with the depth and nuance of a human therapist.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h3 class="card-title">Technical Limitations</h3>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex">
                        <i class="fas fa-robot text-secondary me-3 mt-1"></i>
                        <div>
                            <h5>Potential for Misinterpretation</h5>
                            <p>The AI may occasionally misinterpret nuanced emotional cues or complex situations.</p>
                        </div>
                    </li>
                    <li class="list-group-item d-flex">
                        <i class="fas fa-user-edit text-secondary me-3 mt-1"></i>
                        <div>
                            <h5>Reliance on User Honesty</h5>
                            <p>The effectiveness of support depends on users providing accurate information about their mental state.</p>
                        </div>
                    </li>
                    <li class="list-group-item d-flex">
                        <i class="fas fa-wifi text-secondary me-3 mt-1"></i>
                        <div>
                            <h5>Technical Dependencies</h5>
                            <p>Requires reliable internet connection and may experience occasional service interruptions.</p>
                        </div>
                    </li>
                    <li class="list-group-item d-flex">
                        <i class="fas fa-balance-scale text-secondary me-3 mt-1"></i>
                        <div>
                            <h5>Algorithmic Limitations</h5>
                            <p>AI systems may reflect biases present in their training data despite our best efforts to minimize these effects.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
                </div>

<?php include 'includes/footer.php'; ?>

    <?php if ($show_welcome) { ?>
    <!-- Colorful Professional Welcome Animation -->
    <div id="welcome-container" class="celebration-container">
        <div id="confetti-container" class="confetti-container"></div>
        <div id="bubbles-container" class="bubbles-container"></div>
        
        <div class="celebration-card">
            <div class="color-pulse-background"></div>
            
            <div class="logo-badge">
                <div class="logo-glow"></div>
                <i class="fas fa-brain"></i>
            </div>
            
            <h2 class="welcome-title">Welcome Back</h2>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <svg viewBox="0 0 24 24" width="100%" height="100%">
                        <path fill="currentColor" d="M12,19.2C9.5,19.2 7.29,17.92 6,16C6.03,14 10,12.9 12,12.9C14,12.9 17.97,14 18,16C16.71,17.92 14.5,19.2 12,19.2M12,5A3,3 0 0,1 15,8A3,3 0 0,1 12,11A3,3 0 0,1 9,8A3,3 0 0,1 12,5M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12C22,6.47 17.5,2 12,2Z" />
                    </svg>
                </div>
                <div class="user-info">
                    <h3 class="username"><?php echo htmlspecialchars($user['username']); ?></h3>
                    <p class="session-status">Your session is ready</p>
                </div>
            </div>
            
            <div class="feature-highlights">
                <div class="feature-item">
                    <div class="feature-icon" style="--color: #3b82f6;">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="feature-text">AI Support</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon" style="--color: #8b5cf6;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="feature-text">Progress Tracking</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon" style="--color: #10b981;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="feature-text">Confidential</div>
                </div>
            </div>
            
            <button class="continue-btn">
                <span>Continue</span>
                <div class="btn-shine"></div>
            </button>
            
            <div class="timer-bar"><div class="timer-progress"></div></div>
        </div>
    </div>
    <?php } ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animate process steps on scroll
        const processSteps = document.querySelectorAll('.process-step');
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px'
        };

        const processObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, observerOptions);

        processSteps.forEach(step => {
            processObserver.observe(step);
        });

        // Animate feature progress bars
        const progressBars = document.querySelectorAll('.feature-progress .progress-bar');
        const progressObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const width = entry.target.style.width;
                    entry.target.style.width = '0%';
                    setTimeout(() => {
                        entry.target.style.width = width;
                    }, 100);
                }
            });
        }, observerOptions);

        progressBars.forEach(bar => {
            progressObserver.observe(bar);
        });
    });
    </script>
</body>
</html> 