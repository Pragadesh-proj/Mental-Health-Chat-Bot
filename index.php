<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];

// Check if phone column exists
$checkPhone = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
$phoneExists = ($checkPhone && $checkPhone->num_rows > 0);

if ($phoneExists) {
    $query = "SELECT username, email, phone FROM users WHERE id = ?";
} else {
    $query = "SELECT username, email FROM users WHERE id = ?";
}

$stmt = $conn->prepare($query);

// Check if prepare statement succeeded
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error . " for query: " . $query);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$show_welcome = false;
$username = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['show_welcome']) && $_SESSION['show_welcome']) {
    $show_welcome = true;
    $username = $_SESSION['username'];
    // Reset the flag so it only shows once
    $_SESSION['show_welcome'] = false;
}

// Set page title for header include
$page_title = "Home";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mental Health Chatbot - Your Supportive Companion</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/chat.css">
    <link rel="stylesheet" href="assets/css/welcome-animation.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6">
                        <div class="hero-content">
                            <span class="hero-badge">Mental Health Support</span>
                            <h1 class="hero-title">Your Personal Mental Health Assistant</h1>
                            <p class="hero-description">
                                A supportive AI companion designed to help you through difficult times, track your mood, and provide personalized mental wellness tips.
                            </p>
                            <div class="hero-buttons">
                                <a href="chat.php" class="btn btn-primary btn-lg">Start Chatting <i class="fas fa-arrow-right ms-2"></i></a>
                                <a href="about.php" class="btn btn-outline-primary btn-lg">Learn More</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mt-5 mt-lg-0">
                        <div class="hero-image">
                            <div class="chat-preview">
                                <div class="chat-preview-header">
                                    <div class="preview-header-icon">
                                        <i class="fas fa-robot"></i>
                                    </div>
                                    <div class="preview-header-text">
                                        <h3>Mental Health Assistant</h3>
                                    </div>
                                </div>
                                <div class="chat-preview-messages">
                                    <div class="preview-ai-message">
                                        Hi there! I'm your mental health assistant. How are you feeling today?
                                    </div>
                                    <div class="preview-user-message">
                                        I've been feeling a bit overwhelmed lately with work and life.
                                    </div>
                                    <div class="preview-ai-message">
                                        I understand that feeling overwhelmed can be difficult. Let's talk about some strategies that might help you manage these feelings.
                                    </div>
                                    <div class="preview-typing">
                                        <div class="preview-typing-dot"></div>
                                        <div class="preview-typing-dot"></div>
                                        <div class="preview-typing-dot"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="blob-animation"></div>
        </section>

        <!-- Features Section -->
        <section class="features-section" id="features">
            <div class="container">
                <div class="section-header text-center">
                    <span class="section-badge">Key Features</span>
                    <h2>How We Can Help You</h2>
                    <p>Our mental health chatbot offers several features designed to support your emotional wellbeing and provide guidance when you need it most.</p>
                </div>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h3>Supportive Conversations</h3>
                            <p>Chat with an understanding AI that provides compassionate responses and helpful guidance for your mental health concerns.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Mood Tracking</h3>
                            <p>Record and monitor your emotional states over time to identify patterns and triggers that affect your mental wellbeing.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <h3>Wellness Tips</h3>
                            <p>Receive personalized mental health tips and exercises based on your mood and specific challenges you're facing.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <h3>Resource Library</h3>
                            <p>Access a curated collection of articles, videos, and tools about various mental health topics and coping strategies.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Privacy Focused</h3>
                            <p>Your conversations and personal information are kept completely private and secure, giving you peace of mind.</p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                            <h3>Crisis Support</h3>
                            <p>Get immediate guidance and resource referrals when you're experiencing a mental health crisis or emergency.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2 class="cta-title">Ready to Start Your Mental Health Journey?</h2>
                    <p class="cta-description">Begin chatting with our mental health assistant today and take the first step toward better emotional wellbeing.</p>
                    <div class="cta-buttons">
                        <a href="chat.php" class="cta-btn-primary">Start Chatting</a>
                        <a href="register.php" class="cta-btn-secondary">Create Account</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonial Section -->
        <section class="testimonial-section" id="testimonials">
            <div class="container">
                <div class="section-header text-center">
                    <span class="section-badge">Testimonials</span>
                    <h2>What Our Users Say</h2>
                    <p>Read how our mental health chatbot has helped people manage their emotional wellbeing and find support during difficult times.</p>
                </div>
                <div class="row g-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="testimonial-card">
                            <div class="testimonial-quote">
                                The daily check-ins and mood tracking have been game-changers for me. I can now see patterns in my anxiety and take proactive steps to manage it.
                            </div>
                            <div class="testimonial-author">
                                <img src="assets/images/testimonial-1.jpg" alt="User" class="testimonial-avatar">
                                <div class="testimonial-info">
                                    <h4>Sarah J.</h4>
                                    <p>Using for 3 months</p>
                                </div>
                            </div>
                            <span class="testimonial-badge">Anxiety Management</span>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="testimonial-card">
                            <div class="testimonial-quote">
                                As someone dealing with depression, having a supportive presence available 24/7 has been incredibly helpful. The wellness tips are practical and effective.
                            </div>
                            <div class="testimonial-author">
                                <img src="assets/images/testimonial-2.jpg" alt="User" class="testimonial-avatar">
                                <div class="testimonial-info">
                                    <h4>Michael T.</h4>
                                    <p>Using for 6 months</p>
                                </div>
                            </div>
                            <span class="testimonial-badge">Depression Support</span>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="testimonial-card">
                            <div class="testimonial-quote">
                                I was skeptical at first, but this chatbot has become an essential part of my mental health toolkit. It helps me reflect on my emotions in a structured way.
                            </div>
                            <div class="testimonial-author">
                                <img src="assets/images/testimonial-3.jpg" alt="User" class="testimonial-avatar">
                                <div class="testimonial-info">
                                    <h4>Elena R.</h4>
                                    <p>Using for 2 months</p>
                                </div>
                            </div>
                            <span class="testimonial-badge">Self-Reflection</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section" id="faq">
            <div class="container">
                <div class="section-header text-center">
                    <span class="section-badge">FAQs</span>
                    <h2>Frequently Asked Questions</h2>
                    <p>Find answers to common questions about our mental health chatbot and how it can support your emotional wellbeing.</p>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                        Is my information kept private?
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Yes, your privacy is our top priority. All conversations and personal information are encrypted and kept completely confidential. We do not share your data with third parties, and you can delete your chat history at any time.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        Can the chatbot replace therapy or medical advice?
                                    </button>
                                </h2>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>No, our mental health chatbot is designed to be a supportive tool, not a replacement for professional therapy or medical treatment. It can provide coping strategies and resources, but for serious mental health concerns, please consult with a qualified healthcare provider.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        How does mood tracking work?
                                    </button>
                                </h2>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Our mood tracking feature allows you to record your emotional state during each session. Over time, this creates a visual representation of your mood patterns, helping you identify triggers and track progress. You can view your mood history in your profile dashboard.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFour">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                        What happens during a mental health crisis?
                                    </button>
                                </h2>
                                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>If you indicate that you're experiencing a crisis, our chatbot will immediately provide emergency resources, including crisis hotline numbers and guidance for immediate support. It prioritizes your safety and directs you to professional help when needed.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingFive">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                        Is the chatbot available 24/7?
                                    </button>
                                </h2>
                                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Yes, our mental health chatbot is available 24 hours a day, 7 days a week. You can reach out for support whenever you need it, regardless of the time of day or night. This ensures that help is always accessible during difficult moments.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <div class="overlay-animation">
        <div class="welcome-container">
            <div class="welcome-icon">
                <i class="fas fa-brain"></i>
            </div>
            <h1>Welcome to Your Mental Wellness Companion</h1>
            <p>A safe space for support and growth</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/welcome-animation.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add welcome animation
            setTimeout(function() {
                document.body.classList.add('loaded');
            }, 500);
        });
    </script>
</body>
</html> 