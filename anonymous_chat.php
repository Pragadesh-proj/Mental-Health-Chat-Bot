<?php
session_start();

// Set page title for header include
$page_title = "Anonymous Chat";

// Add custom CSS and JS for chat interface
$additional_css = '<link href="assets/css/chat.css" rel="stylesheet">';
$additional_js = '<script src="assets/js/anonymous_chat.js" defer></script>';
?>

<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row g-4">
        <div class="col-lg-4 mb-4">
            <!-- Introduction Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Anonymous Support</h5>
                </div>
                <div class="card-body">
                    <p>Welcome to the anonymous chat. You can talk freely without creating an account or saving your history.</p>
                    <p>This is a safe space to discuss your thoughts and feelings. No personal information is stored.</p>
                    <div class="alert alert-info small">
                    <i class="fas fa-shield-alt me-2"></i> Your conversation will not be saved once you leave this page.
                    </div>
                </div>
            </div>
            
            <!-- Mood Tracker -->
            <div class="mood-tracker">
                <div class="mood-tracker-header">
                    <h4 class="mood-tracker-title">How are you feeling today?</h4>
                </div>
                <div class="mood-selector">
                    <div class="mood-option" data-mood="1">
                        <div class="mood-emoji">😢</div>
                        <div class="mood-label">Sad</div>
                    </div>
                    <div class="mood-option" data-mood="2">
                        <div class="mood-emoji">😕</div>
                        <div class="mood-label">Down</div>
                    </div>
                    <div class="mood-option" data-mood="3">
                        <div class="mood-emoji">😐</div>
                        <div class="mood-label">Neutral</div>
                    </div>
                    <div class="mood-option" data-mood="4">
                        <div class="mood-emoji">🙂</div>
                        <div class="mood-label">Good</div>
                    </div>
                    <div class="mood-option" data-mood="5">
                        <div class="mood-emoji">😄</div>
                        <div class="mood-label">Great</div>
                    </div>
                </div>
            </div>
            
            <!-- Chat Features -->
            <div class="chat-features mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Chat Options</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="voice-response-toggle">
                            <label class="form-check-label" for="voice-response-toggle">
                                <i class="fas fa-volume-up me-2"></i>Text-to-Speech Responses
                            </label>
                        </div>
                        <div class="alert alert-warning small">
                            <i class="fas fa-exclamation-triangle me-2"></i> Remember that this is an anonymous session. To save your chat history, please <a href="login.php">log in</a> or <a href="signup.php">create an account</a>.
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Resources Card -->
            <div class="card mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-hands-helping me-2"></i>Support Resources</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Crisis Text Line</strong>
                                <p class="mb-0 small text-muted">Text HOME to 741741</p>
                            </div>
                            <span class="badge bg-primary rounded-pill">24/7</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>National Suicide Prevention Lifeline</strong>
                                <p class="mb-0 small text-muted">1-800-273-8255</p>
                            </div>
                            <span class="badge bg-primary rounded-pill">24/7</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>SAMHSA Treatment Referral</strong>
                                <p class="mb-0 small text-muted">1-800-662-4357</p>
                            </div>
                            <span class="badge bg-primary rounded-pill">24/7</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <!-- AI Assistant -->
            <div class="ai-assistant mb-4">
                <div class="ai-header">
                    <div class="ai-avatar">
                        <i class="fas fa-brain"></i>
                    </div>
                    <div class="ai-info">
                        <h3>Anonymous Mental Health Assistant</h3>
                        <p>No login required - I'm here to listen and support you</p>
                    </div>
                    <div class="ai-controls ms-auto">
                        <button class="btn btn-sm btn-light rounded-circle" id="refresh-chat" title="Clear conversation">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="ai-body">
                    <div class="ai-message">
                        <p>Hello, I'm your anonymous mental health assistant. Feel free to share what's on your mind without worrying about your data being stored. How are you feeling today?</p>
                    </div>
                    
                    <div class="ai-suggestion">
                        <div class="ai-suggestion-title">You might want to talk about:</div>
                        <div class="suggestion-chips">
                            <div class="suggestion-chip"><i class="fas fa-brain me-2"></i>Managing stress</div>
                            <div class="suggestion-chip"><i class="fas fa-moon me-2"></i>Improving sleep</div>
                            <div class="suggestion-chip"><i class="fas fa-spa me-2"></i>Mindfulness</div>
                            <div class="suggestion-chip"><i class="fas fa-heartbeat me-2"></i>Anxiety</div>
                            <div class="suggestion-chip"><i class="fas fa-seedling me-2"></i>Healthy habits</div>
                        </div>
                    </div>
                </div>
                <div class="chat-input-container p-3">
                    <div class="chat-input">
                        <input type="text" placeholder="Type your message here..." aria-label="Type your message">
                        <button id="voice-input-button" type="button" class="voice-btn" title="Voice input">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button type="button" class="send-btn" title="Send message">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div class="privacy-note mt-2 text-center">
                        <small class="text-muted"><i class="fas fa-lock me-1"></i> Your conversations are private and not saved</small>
                    </div>
                </div>
            </div>
            
            <!-- Create Account Card -->
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h5 class="card-title">Want to save your chat history?</h5>
                            <p class="card-text">Create an account to track your mood over time, save conversations, and get personalized support.</p>
                        </div>
                        <div class="col-lg-4 text-center text-lg-end mt-3 mt-lg-0">
                            <a href="signup.php" class="btn btn-primary me-2">Sign Up</a>
                            <a href="login.php" class="btn btn-outline-secondary">Log In</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mental Health Tips -->
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Quick Wellness Tips</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-lungs text-primary fa-2x me-3"></i>
                                </div>
                                <div>
                                    <h6>Deep Breathing</h6>
                                    <p class="small text-muted">Practice 4-7-8 breathing: Inhale for 4 seconds, hold for 7, exhale for 8.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-walking text-primary fa-2x me-3"></i>
                                </div>
                                <div>
                                    <h6>Get Moving</h6>
                                    <p class="small text-muted">Even a 10-minute walk can boost your mood and reduce stress.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-tint text-primary fa-2x me-3"></i>
                                </div>
                                <div>
                                    <h6>Stay Hydrated</h6>
                                    <p class="small text-muted">Dehydration can worsen anxiety and affect your mood.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 