<?php
session_start();
require_once 'config/database.php';

// Include the profile.php file to use the get_avatar_url function


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
    $query = "SELECT username, email, phone, avatar FROM users WHERE id = ?";
} else {
    $query = "SELECT username, email, avatar FROM users WHERE id = ?";
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
if (isset($_SESSION['show_welcome']) && $_SESSION['show_welcome']) {
    $show_welcome = true;
    // Reset the flag so it only shows once
    $_SESSION['show_welcome'] = false;
}

$page_title = "Chat";
$additional_css = '<link href="assets/css/chat.css" rel="stylesheet">';
$additional_js = '<script src="assets/js/chat.js" defer></script>';
?>
<?php include 'includes/header.php'; ?>
<!-- Add username meta tag for JavaScript -->
<meta name="username" content="<?php echo htmlspecialchars($user['username']); ?>">

<div class="app-wrapper">
    <div class="main-content">
        <div class="container">
            <div class="row">
                <!-- Left sidebar column with improved spacing and modern styling -->
                <div class="col-md-4">
                    <!-- Chat Options Panel -->
                    <div class="options-panel">
                        <h4 class="panel-title"><i class="fas fa-sliders-h me-2"></i>Chat Options</h4>
                        
                        <div class="option-item">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="voice-response-toggle">
                                <label class="form-check-label" for="voice-response-toggle">
                                    <i class="fas fa-volume-up me-2"></i>Text-to-Speech Responses
                                </label>
                            </div>
                        </div>
                        
                        <div class="option-item">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="anonymous-mode-toggle">
                                <label class="form-check-label" for="anonymous-mode-toggle">
                                    <i class="fas fa-user-secret me-2"></i>Anonymous Mode
                                </label>
                            </div>
                        </div>
                        
                        <div class="option-item">
                            <button id="export-chat-btn" class="btn btn-outline-primary w-100">
                                <i class="fas fa-download me-2"></i>Export Chat History
                            </button>
                        </div>
                        
                        <div class="option-item">
                            <button id="view-previous-chats-btn" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-history me-2"></i>View Previous Chats
                            </button>
                        </div>
                        
                        <div class="option-item">
                            <a href="motivation.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-bolt me-2"></i>Get Motivation
                            </a>
                        </div>
                        
                        <!-- Mood Tracker Widget -->
                        <div class="mood-tracker-widget mt-4">
                            <div class="mood-tracker-header">
                                <h5><i class="fas fa-chart-line me-2"></i>Mood Tracker</h5>
                                <a href="mood.php" class="btn btn-sm btn-light">Full History</a>
                            </div>
                            <div class="mood-tracker-body">
                                <p class="small mb-2">Your recent mood history:</p>
                                <div class="mood-history">
                                    <?php
                                    // Sample mood data - in a real app this would come from database
                                    $moodData = [
                                        ['date' => date('M d', strtotime('-4 days')), 'emoji' => '😊', 'mood' => 'Happy'],
                                        ['date' => date('M d', strtotime('-3 days')), 'emoji' => '😐', 'mood' => 'Neutral'],
                                        ['date' => date('M d', strtotime('-2 days')), 'emoji' => '😔', 'mood' => 'Sad'],
                                        ['date' => date('M d', strtotime('-1 days')), 'emoji' => '😊', 'mood' => 'Happy'],
                                        ['date' => date('M d'), 'emoji' => '😀', 'mood' => 'Very Happy'],
                                    ];
                                    
                                    foreach ($moodData as $mood) {
                                        echo '<div class="mood-day" title="' . $mood['mood'] . '">';
                                        echo '<div class="mood-emoji">' . $mood['emoji'] . '</div>';
                                        echo '<div class="mood-date">' . $mood['date'] . '</div>';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                                <div class="d-grid">
                                    <button id="track-mood-btn" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus-circle me-2"></i>Track Today's Mood
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Daily Wellness Tip -->
                        <div class="option-item">
                            <div class="wellness-panel">
                                <div class="panel-header">
                                    <h4><i class="fas fa-lightbulb me-2"></i>Daily Wellness Tip</h4>
                                </div>
                                <div class="panel-body scrollable">
                                    <?php
                                    // Array of mental health tips
                                    $tips = [
                                        "Take regular breaks from screens to reduce mental fatigue.",
                                        "Try to get at least 7-8 hours of sleep each night to support your mental well-being.",
                                        "Practice deep breathing for 5 minutes when feeling stressed.",
                                        "Spend time in nature to boost your mood and reduce stress levels.",
                                        "Practice gratitude by noting three things you're thankful for each day.",
                                        "Stay hydrated - dehydration can negatively impact your mood and cognitive function.",
                                        "Regular physical activity can help reduce symptoms of depression and anxiety.",
                                        "Connect with others - social support is crucial for mental health.",
                                        "Set realistic goals and celebrate small wins to build confidence.",
                                        "Practice mindfulness by focusing on the present moment without judgment."
                                    ];
                                    
                                    // Select a random tip
                                    $randomTip = $tips[array_rand($tips)];
                                    ?>
                                    
                                    <div class="wellness-tip">
                                        <p><?php echo $randomTip; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main content column with improved chat interface -->
                <div class="col-md-8">
                    <!-- Chat Interface with modern header design -->
                    <div class="chat-container">
                        <div class="chat-header">
                            <h3>
                                <i class="fas fa-brain"></i>
                                Mental Health Assistant
                            </h3>
                            <div class="chat-controls">
                                <button id="view-history-btn" title="View chat history">
                                    <i class="fas fa-history"></i>
                                </button>
                                <button id="refresh-chat" title="Clear conversation">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="chat-messages scrollable" id="chat-messages">
                            <div class="ai-message" id="welcome-message">
                                <p>Hello <?php echo htmlspecialchars($user['username']); ?>, how can I help you today? Feel free to share what's on your mind.</p>
                            </div>
                            
                            <div class="chat-suggestions" id="default-suggestions">
                                <p class="suggestion-title">You might want to talk about:</p>
                                <div class="suggestion-items">
                                    <div class="suggestion-chip" onclick="suggestTopic('Managing stress')"><i class="fas fa-brain me-2"></i>Managing stress</div>
                                    <div class="suggestion-chip" onclick="suggestTopic('Improving sleep')"><i class="fas fa-moon me-2"></i>Improving sleep</div>
                                    <div class="suggestion-chip" onclick="suggestTopic('Mindfulness techniques')"><i class="fas fa-spa me-2"></i>Mindfulness</div>
                                    <div class="suggestion-chip" onclick="suggestTopic('Help with anxiety')"><i class="fas fa-heartbeat me-2"></i>Anxiety</div>
                                    <div class="suggestion-chip" onclick="suggestTopic('Developing healthy habits')"><i class="fas fa-seedling me-2"></i>Healthy habits</div>
                                </div>
                            </div>
                            </div>
                        
                        <div class="chat-input-container">
                            <form id="chat-form">
                                <div class="chat-input-wrapper">
                                    <textarea id="message-input" class="chat-input" placeholder="Type your message..." rows="2"></textarea>
                                    <button type="button" id="voice-input-btn" class="voice-input-button" title="Voice input">
                                        <i class="fas fa-microphone"></i>
                                    </button>
                                </div>
                                <button type="submit" id="send-message-btn" class="chat-send-button">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                            </form>
                            <div id="voice-feedback" class="voice-feedback">Listening...</div>
                        </div>
                    </div>
                    
                    <!-- Activity & Wellness Section -->
                    <div class="row mt-1">
                        <!-- Recent Chat History -->
                        <div class="col-lg-5">
                            <!-- Daily Wellness Tip removed from here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Updated notification container for better positioning and animations -->
<div id="notification-container" class="notification-container"></div>

<!-- More visible crisis alert that appears when crisis is detected -->
<div id="crisis-alert" class="crisis-alert alert alert-danger d-none">
    <div class="d-flex">
        <div class="crisis-alert-icon me-3">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="crisis-alert-content">
            <h4 class="alert-heading">Your well-being matters</h4>
            <p>If you're experiencing thoughts of suicide or severe emotional distress, please reach out for immediate help:</p>
            <div class="crisis-resources mt-2">
                <a href="tel:988" class="btn btn-danger me-2 mb-2">
                    <i class="fas fa-phone-alt me-2"></i>Call 988 Crisis Lifeline
                </a>
                <a href="sms:741741&body=HOME" class="btn btn-outline-danger me-2 mb-2">
                    <i class="fas fa-comment me-2"></i>Text HOME to 741741
                </a>
                <a href="crisis_resources.php" class="btn btn-outline-danger mb-2">
                    <i class="fas fa-first-aid me-2"></i>View All Resources
                </a>
            </div>
        </div>
        <button type="button" class="btn-close ms-auto" aria-label="Close" id="crisis-alert-close"></button>
    </div>
</div>

<!-- Improved motivation suggestion container -->
<div id="motivation-suggestion" class="motivation-suggestion d-none">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Need a motivation boost?</h5>
            <p class="card-text">Visit our motivation generator for personalized inspiration based on your current mood.</p>
            <div class="text-end">
                <button id="motivation-dismiss" class="btn btn-sm btn-link">Not now</button>
                <a href="motivation.php" class="btn btn-sm btn-primary">Get Motivated</a>
            </div>
        </div>
    </div>
</div>

<!-- Chat History Sidebar -->
<div class="chat-history-sidebar" id="chat-history-sidebar">
    <div class="history-sidebar-header">
        <h4><i class="fas fa-history me-2"></i>Chat History</h4>
        <button id="close-history-sidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="history-sidebar-content" id="chat-history-content">
        <!-- Chat history will be loaded here -->
        </div>
    <div class="history-sidebar-footer">
        <button id="new-chat-btn" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>New Chat
        </button>
    </div>
</div>

<!-- Success Notification Template -->
<div class="success-notification" id="success-notification">
    <i class="fas fa-check"></i>
    <div class="success-notification-content">Operation completed successfully!</div>
    <button class="btn-close" id="close-success-notification">
        <i class="fas fa-times"></i>
    </button>
</div>

<!-- Notification Template -->
<div class="notification notification-success" id="notification-template" style="display: none;">
    <div class="notification-content">
        <i class="fas fa-check-circle"></i>
        <span>Operation completed successfully!</span>
    </div>
    <button class="notification-close">
        <i class="fas fa-times"></i>
    </button>
</div>

<!-- Mood Tracking Modal -->
<div class="modal fade" id="mood-tracking-modal" tabindex="-1" aria-labelledby="mood-tracking-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="mood-tracking-modal-label"><i class="fas fa-smile me-2"></i>Track Your Mood</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">How are you feeling today?</p>
                <div class="mood-options">
                    <div class="mood-option" data-mood="very_happy">
                        <div class="mood-emoji">😀</div>
                        <div class="mood-label">Very Happy</div>
                    </div>
                    <div class="mood-option" data-mood="happy">
                        <div class="mood-emoji">😊</div>
                        <div class="mood-label">Happy</div>
                    </div>
                    <div class="mood-option" data-mood="neutral">
                        <div class="mood-emoji">😐</div>
                        <div class="mood-label">Neutral</div>
                    </div>
                    <div class="mood-option" data-mood="sad">
                        <div class="mood-emoji">😔</div>
                        <div class="mood-label">Sad</div>
                    </div>
                    <div class="mood-option" data-mood="very_sad">
                        <div class="mood-emoji">😢</div>
                        <div class="mood-label">Very Sad</div>
                    </div>
                    <div class="mood-option" data-mood="anxious">
                        <div class="mood-emoji">😰</div>
                        <div class="mood-label">Anxious</div>
                    </div>
                </div>
                <div class="form-floating mt-4">
                    <textarea class="form-control" id="mood-notes" style="height: 100px"></textarea>
                    <label for="mood-notes">What made you feel this way? (optional)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-mood-btn">Save Mood</button>
            </div>
        </div>
    </div>
</div>

<!-- New Chat Animation Container -->
<div id="new-chat-animation" class="fullscreen-animation">
    <div class="animation-content">
        <div class="animation-icon">
            <i class="fas fa-comments"></i>
        </div>
        <h3>New chat is opened. Enjoy!</h3>
    </div>
</div>

<!-- Mood Saved Animation Container -->
<div id="mood-saved-animation" class="fullscreen-animation">
    <div class="animation-content">
        <div class="animation-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3>You have saved successfully! Thanks for your response</h3>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    // Updated JavaScript for chat functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Load saved chat messages from localStorage
        loadChatMessages();
        
        // Initialize text-to-speech toggle
        const ttsToggle = document.getElementById('voice-response-toggle');
        if (ttsToggle) {
            // Set initial state based on localStorage
            ttsToggle.checked = localStorage.getItem('tts_enabled') === 'true';
            
            // Update localStorage when changed
            ttsToggle.addEventListener('change', function() {
                localStorage.setItem('tts_enabled', this.checked);
                
                // Show notification about voice setting
                if (this.checked) {
                    showNotification('Text-to-speech responses enabled', 'success');
                } else {
                    showNotification('Text-to-speech responses disabled', 'info');
                }
            });
        }
        
        // Handle anonymous mode toggle
        const anonymousToggle = document.getElementById('anonymous-mode-toggle');
        if (anonymousToggle) {
            // Set initial state based on localStorage
            anonymousToggle.checked = localStorage.getItem('anonymous_mode') === 'true';
            
            anonymousToggle.addEventListener('change', function() {
                localStorage.setItem('anonymous_mode', this.checked);
                if (this.checked) {
                    showNotification('Anonymous mode activated. Your chat history will not be saved.', 'info');
                } else {
                    showNotification('Anonymous mode deactivated. Your chat history will now be saved.', 'info');
                    // Save current chat to localStorage
                    saveChatMessages();
                }
            });
        }
        
        // Voice input functionality
        const voiceInputBtn = document.getElementById('voice-input-btn');
        const messageInput = document.getElementById('message-input');
        const voiceFeedback = document.getElementById('voice-feedback');
        let recognition;
        
        // Check if browser supports SpeechRecognition
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            recognition = new (window.SpeechRecognition || window.webkitSpeechRecognition)();
            recognition.continuous = false;
            recognition.interimResults = true;
            recognition.lang = 'en-US';
            
            recognition.onstart = function() {
                voiceFeedback.classList.add('active');
                voiceInputBtn.classList.add('recording');
            };
            
            recognition.onresult = function(event) {
                const transcript = Array.from(event.results)
                    .map(result => result[0])
                    .map(result => result.transcript)
                    .join('');
                    
                messageInput.value = transcript;
                
                // If it's a final result, update the voice feedback
                if (event.results[0].isFinal) {
                    voiceFeedback.textContent = "Processing...";
                }
            };
            
            recognition.onend = function() {
                voiceFeedback.classList.remove('active');
                voiceInputBtn.classList.remove('recording');
                voiceFeedback.textContent = "Listening...";
            };
            
            recognition.onerror = function(event) {
                voiceFeedback.textContent = "Error: " + event.error;
                setTimeout(() => {
                    voiceFeedback.classList.remove('active');
                    voiceInputBtn.classList.remove('recording');
                }, 1500);
            };
            
            if (voiceInputBtn) {
                voiceInputBtn.addEventListener('click', function() {
                    if (voiceInputBtn.classList.contains('recording')) {
                        recognition.stop();
                    } else {
                        messageInput.value = '';
                        recognition.start();
                    }
                });
            }
        } else {
            // Speech Recognition not supported
            if (voiceInputBtn) {
                voiceInputBtn.style.display = 'none';
                showNotification('Voice input is not supported in your browser', 'warning');
            }
        }
        
        // Mood tracking button
        const trackMoodBtn = document.getElementById('track-mood-btn');
        if (trackMoodBtn) {
            trackMoodBtn.addEventListener('click', function() {
                // Initialize the mood tracking modal
                const moodModal = new bootstrap.Modal(document.getElementById('mood-tracking-modal'));
                moodModal.show();
            });
        }
        
        // Set up mood option selection
        const moodOptions = document.querySelectorAll('.mood-option');
        moodOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                moodOptions.forEach(opt => opt.classList.remove('selected'));
                // Add selected class to clicked option
                this.classList.add('selected');
            });
        });
        
        // Save mood button
        const saveMoodBtn = document.getElementById('save-mood-btn');
        if (saveMoodBtn) {
            saveMoodBtn.addEventListener('click', function() {
                const selectedMood = document.querySelector('.mood-option.selected');
                if (!selectedMood) {
                    showNotification('Please select a mood', 'warning');
                    return;
                }
                
                const moodData = {
                    mood: selectedMood.dataset.mood,
                    emoji: selectedMood.querySelector('.mood-emoji').textContent,
                    notes: document.getElementById('mood-notes').value,
                    date: new Date().toISOString()
                };
                
                // Save mood data to localStorage (in a real app, this would save to a database)
                const moodHistory = JSON.parse(localStorage.getItem('moodHistory') || '[]');
                moodHistory.unshift(moodData);
                localStorage.setItem('moodHistory', JSON.stringify(moodHistory));
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('mood-tracking-modal')).hide();
                
                // Show success animation
                showMoodSavedAnimation();
                
                // Show success notification
                showNotification('Mood tracked successfully!', 'success');
                
                // Refresh mood history display
                updateMoodHistory();
            });
        }
        
        // Refresh chat button
        const refreshBtn = document.getElementById('refresh-chat');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear this conversation?')) {
                    startNewChat();
                }
            });
        }
        
        // Export chat button with fixed functionality
        const exportBtn = document.getElementById('export-chat-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', exportChatHistory);
        }
        
        // View previous chats buttons
        const viewPreviousBtn = document.getElementById('view-previous-chats-btn');
        const viewHistoryBtn = document.getElementById('view-history-btn');
        const closeHistorySidebarBtn = document.getElementById('close-history-sidebar');
        const chatHistorySidebar = document.getElementById('chat-history-sidebar');
        
        // Set up event listeners for chat history sidebar
        if (viewPreviousBtn) {
            viewPreviousBtn.addEventListener('click', function() {
                openChatHistorySidebar();
            });
        }
        
        if (viewHistoryBtn) {
            viewHistoryBtn.addEventListener('click', function() {
                openChatHistorySidebar();
            });
        }
        
        if (closeHistorySidebarBtn && chatHistorySidebar) {
            closeHistorySidebarBtn.addEventListener('click', function() {
                chatHistorySidebar.classList.remove('active');
            });
        }
        
        // Send button click event
        const sendBtn = document.getElementById('send-message-btn');
        if (sendBtn) {
            sendBtn.addEventListener('click', function() {
                const messageInput = document.getElementById('message-input');
                const message = messageInput.value.trim();
                
                if (message) {
                    // Add user message to chat
                    addUserMessage(message);
                    messageInput.value = '';
                    
                    // Process the message
                    processUserMessage(message);
                } else {
                    // Show error if message is empty
                    showNotification('Please enter a message', 'warning');
                }
            });
        }
        
        // Form submission
        const chatForm = document.getElementById('chat-form');
        if (chatForm) {
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                sendBtn.click();
            });
        }
        
        // New chat button
        const newChatBtn = document.getElementById('new-chat-btn');
        if (newChatBtn) {
            newChatBtn.addEventListener('click', function() {
                if (confirm('Start a new conversation? Your current conversation will be saved.')) {
                    startNewChat();
                }
            });
        }
        
        // Initial load of mood history
        updateMoodHistory();
    });
    
    // Function to update mood history display
    function updateMoodHistory() {
        const moodHistoryContainer = document.querySelector('.mood-history');
        if (!moodHistoryContainer) return;
        
        // Get mood history from localStorage
        const moodHistory = JSON.parse(localStorage.getItem('moodHistory') || '[]');
        
        // Use stored mood history or sample data if empty
        if (moodHistory.length > 0) {
            let historyHTML = '';
            
            // Show last 5 moods
            const moods = moodHistory.slice(0, 5);
            
            moods.forEach(mood => {
                const date = new Date(mood.date);
                const formattedDate = date.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
                
                historyHTML += `
                    <div class="mood-day" title="${mood.notes || mood.mood}">
                        <div class="mood-emoji">${mood.emoji}</div>
                        <div class="mood-date">${formattedDate}</div>
                    </div>
                `;
            });
            
            moodHistoryContainer.innerHTML = historyHTML;
        }
    }

    // Function to export chat history
    function exportChatHistory() {
        const chatMessages = document.querySelectorAll('.user-message, .ai-message');
        if (chatMessages.length <= 1) { // Only welcome message
            showNotification('No messages to export', 'warning');
            return;
        }
        
        let chatContent = "Mental Health Assistant - Chat Export\n";
        chatContent += "Date: " + new Date().toLocaleString() + "\n\n";
        
        chatMessages.forEach(message => {
            if (message.id === 'welcome-message') return;
            
            const isUser = message.classList.contains('user-message');
            const text = message.querySelector('p').textContent;
            const prefix = isUser ? "You: " : "Assistant: ";
            
            chatContent += prefix + text + "\n\n";
        });
        
        // Create blob and download
        const blob = new Blob([chatContent], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'chat_export_' + new Date().toISOString().slice(0, 10) + '.txt';
        document.body.appendChild(a);
        a.click();
        
        // Cleanup
            setTimeout(() => {
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }, 0);
        
        showNotification('Chat exported successfully', 'success');
    }
    
    // Function to open chat history sidebar
    function openChatHistorySidebar() {
        const sidebar = document.getElementById('chat-history-sidebar');
        if (sidebar) {
            sidebar.classList.add('active');
            loadPreviousChats();
        }
    }
    
    // Function to load previous chats
    function loadPreviousChats() {
        const historyContent = document.getElementById('chat-history-content');
        if (!historyContent) return;
        
        // Show loading state
        historyContent.innerHTML = `
            <div class="p-4 text-center text-muted">
                <div class="spinner-border spinner-border-sm me-2" role="status">
                    <span class="visually-hidden">Loading...</span>
            </div>
                Loading chat history...
            </div>
        `;
        
        // In a real application, this would fetch from a database
        // For demo purposes, we'll retrieve from localStorage
        setTimeout(() => {
            let historyHTML = '';
            
            // Check if we have any saved chats
            const savedChats = JSON.parse(localStorage.getItem('savedChats') || '[]');
            
            if (savedChats.length > 0) {
                savedChats.forEach((chat, index) => {
                    const date = new Date(chat.timestamp).toLocaleString();
                    const preview = chat.messages && chat.messages[0]?.text.substring(0, 40) + '...' || 'Empty conversation';
                    
                    historyHTML += `
                        <div class="chat-session-item" data-index="${index}">
                            <div class="chat-session-info">
                                <h6>Conversation ${index + 1}</h6>
                                <p>${date}</p>
                                <small class="text-muted">${preview}</small>
                            </div>
                            <div class="chat-session-actions">
                                <button class="btn-delete-session" data-index="${index}">
                                    <i class="fas fa-trash-alt"></i>
            </button>
                            </div>
                        </div>
                    `;
                });
            } else {
                historyHTML = `
                    <div class="p-4 text-center">
                        <i class="fas fa-comment-slash fs-1 text-muted mb-3"></i>
                        <p>No previous conversations found.</p>
                        <p class="small text-muted">Start chatting to create your first conversation.</p>
                    </div>
                `;
            }
            
            historyContent.innerHTML = historyHTML;
            
            // Add event listeners to chat session items
            document.querySelectorAll('.chat-session-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    if (!e.target.closest('.btn-delete-session')) {
                        const index = this.dataset.index;
                        loadChatSession(index);
                    }
                });
            });
            
            // Add event listeners to delete buttons
            document.querySelectorAll('.btn-delete-session').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const index = this.dataset.index;
                    deleteChat(index);
                });
            });
        }, 500); // Simulate loading delay
    }
    
    // Function to load a specific chat session
    function loadChatSession(index) {
        const savedChats = JSON.parse(localStorage.getItem('savedChats') || '[]');
        const chat = savedChats[index];
        
        if (chat) {
            const chatMessages = document.getElementById('chat-messages');
            chatMessages.innerHTML = '<div class="ai-message" id="welcome-message"><p>Hello, how can I help you today?</p></div>';
            
            chat.messages.forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.className = message.type === 'user' ? 'user-message' : 'ai-message';
                messageDiv.innerHTML = `<p>${message.text}</p>`;
                chatMessages.appendChild(messageDiv);
            });
            
            // Close sidebar
            document.getElementById('chat-history-sidebar').classList.remove('active');
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            showNotification('Previous conversation loaded', 'success');
        }
    }
    
    // Function to delete a chat
    function deleteChat(index) {
        if (confirm('Are you sure you want to delete this conversation?')) {
            const savedChats = JSON.parse(localStorage.getItem('savedChats') || '[]');
            savedChats.splice(index, 1);
            localStorage.setItem('savedChats', JSON.stringify(savedChats));
            
            showNotification('Conversation deleted', 'success');
            loadPreviousChats(); // Reload the list
        }
    }
    
    // Function to save current chat
    function saveCurrentChat() {
        const chatMessages = document.querySelectorAll('.user-message, .ai-message');
        if (chatMessages.length <= 1) return; // Only welcome message
        
        const messages = [];
        chatMessages.forEach(message => {
            if (message.id === 'welcome-message') return;
            
            const type = message.classList.contains('user-message') ? 'user' : 'ai';
            const text = message.querySelector('p').textContent;
            
            messages.push({ type, text });
        });
        
        const chatData = {
            timestamp: new Date().getTime(),
            messages: messages
        };
        
        const savedChats = JSON.parse(localStorage.getItem('savedChats') || '[]');
        savedChats.unshift(chatData); // Add to beginning
        
        // Limit to last 20 chats
        if (savedChats.length > 20) {
            savedChats.pop();
        }
        
        localStorage.setItem('savedChats', JSON.stringify(savedChats));
    }
    
    // Function to save chat messages to localStorage
    function saveChatMessages() {
        const chatMessages = document.getElementById('chat-messages');
        const messages = chatMessages.querySelectorAll('.user-message, .ai-message');
        const messageData = [];
        
        messages.forEach(message => {
            // Skip the default welcome message
            if (message.id === 'welcome-message') return;
            
            const messageType = message.classList.contains('user-message') ? 'user' : 'ai';
            const messageText = message.querySelector('p').textContent;
            
            messageData.push({
                type: messageType,
                text: messageText,
                timestamp: new Date().getTime()
            });
        });
        
        localStorage.setItem('chatMessages', JSON.stringify(messageData));
    }
    
    // Function to load chat messages from localStorage
    function loadChatMessages() {
        const storedMessages = localStorage.getItem('chatMessages');
        if (!storedMessages) return;
        
        const messages = JSON.parse(storedMessages);
        const chatMessages = document.getElementById('chat-messages');
        const defaultSuggestions = document.getElementById('default-suggestions');
        const welcomeMessage = document.getElementById('welcome-message');
        const emptyChatState = document.getElementById('empty-chat-state');
        
        // If we have stored messages, hide empty state and show welcome message
        if (messages.length > 0) {
            if (emptyChatState) emptyChatState.style.display = 'none';
            if (welcomeMessage) welcomeMessage.style.display = 'block';
            
            // Hide default suggestions if we're loading saved messages
            if (defaultSuggestions) defaultSuggestions.style.display = 'none';
            
            // Add all saved messages to the chat
            messages.forEach(message => {
                if (message.type === 'user') {
                    const userMessageDiv = document.createElement('div');
                    userMessageDiv.className = 'user-message';
                    userMessageDiv.innerHTML = `<p>${message.text}</p>`;
                    chatMessages.appendChild(userMessageDiv);
                } else {
                    const aiMessageDiv = document.createElement('div');
                    aiMessageDiv.className = 'ai-message';
                    aiMessageDiv.innerHTML = `<p>${message.text}</p>`;
                    chatMessages.appendChild(aiMessageDiv);
                }
            });
            
            // Scroll to bottom of chat
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }
    
    // Modified addUserMessage function
    function addUserMessage(message) {
        const chatMessages = document.getElementById('chat-messages');
        const userMessageDiv = document.createElement('div');
        userMessageDiv.className = 'user-message';
        userMessageDiv.innerHTML = `<p>${message}</p>`;
        chatMessages.appendChild(userMessageDiv);
        
        // Hide default suggestions when user starts chatting
        const defaultSuggestions = document.getElementById('default-suggestions');
        if (defaultSuggestions) {
            defaultSuggestions.style.display = 'none';
        }
        
        // Hide empty state if visible
        const emptyState = document.getElementById('empty-chat-state');
        if (emptyState) {
            emptyState.style.display = 'none';
        }
        
        // Scroll to bottom of chat
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Save to local storage if not in anonymous mode
        if (document.getElementById('anonymous-mode-toggle') && !document.getElementById('anonymous-mode-toggle').checked) {
            saveChatMessages();
        }
    }
    
    // Modified addAIResponse function to save chat after response and handle text-to-speech
    function addAIResponse(message, emotion = null) {
        const chatMessages = document.getElementById('chat-messages');
        const aiMessageDiv = document.createElement('div');
        aiMessageDiv.className = 'ai-message';
        
        // Add emotion class if provided
        if (emotion) {
            aiMessageDiv.classList.add('emotion-' + emotion);
        }
        
        aiMessageDiv.innerHTML = `<p>${message}</p>`;
        chatMessages.appendChild(aiMessageDiv);
        
        // Scroll to bottom of chat
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Text to speech if enabled
        if (document.getElementById('voice-response-toggle').checked) {
            const utterance = new SpeechSynthesisUtterance(message);
            window.speechSynthesis.speak(utterance);
        }
        
        // Save messages to localStorage if not in anonymous mode
        if (!document.getElementById('anonymous-mode-toggle').checked) {
            saveChatMessages();
            saveCurrentChat(); // Save to chat history
        }
    }
    
    // Function to start a new chat
    function startNewChat() {
        // Save current conversation if it has content
        const messages = document.querySelectorAll('.user-message');
        if (messages.length > 0 && !document.getElementById('anonymous-mode-toggle').checked) {
            saveCurrentChat();
        }
        
        // Clear messages except welcome message
        const chatMessages = document.getElementById('chat-messages');
        if (chatMessages) {
            chatMessages.innerHTML = `
                <div class="ai-message" id="welcome-message">
                    <p>Hello ${document.querySelector('meta[name="username"]')?.content || ''}, how can I help you today? Feel free to share what's on your mind.</p>
                </div>
                <div class="chat-suggestions" id="default-suggestions">
                    <p class="suggestion-title">You might want to talk about:</p>
                    <div class="suggestion-items">
                            <div class="suggestion-chip" onclick="suggestTopic('Managing stress')"><i class="fas fa-brain me-2"></i>Managing stress</div>
                            <div class="suggestion-chip" onclick="suggestTopic('Improving sleep')"><i class="fas fa-moon me-2"></i>Improving sleep</div>
                            <div class="suggestion-chip" onclick="suggestTopic('Mindfulness techniques')"><i class="fas fa-spa me-2"></i>Mindfulness</div>
                            <div class="suggestion-chip" onclick="suggestTopic('Help with anxiety')"><i class="fas fa-heartbeat me-2"></i>Anxiety</div>
                            <div class="suggestion-chip" onclick="suggestTopic('Developing healthy habits')"><i class="fas fa-seedling me-2"></i>Healthy habits</div>
                    </div>
                </div>
            `;
        }
        
        // Clear input
        const messageInput = document.getElementById('message-input');
        if (messageInput) {
            messageInput.value = '';
            messageInput.focus();
        }
        
        // Clear localStorage for current chat
        localStorage.removeItem('chatMessages');
        
        // Show new chat animation
        showNewChatAnimation();
        
        // Show notification
        showNotification('Started a new conversation', 'success');
    }
    
    // Function to process user message
    function processUserMessage(message) {
        // Show error container
        const errorContainer = document.getElementById('error-container');
        if (errorContainer) {
            errorContainer.textContent = '';
            errorContainer.style.display = 'none';
        }
        
        // Show typing indicator
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'typing-indicator ai-message';
        typingIndicator.innerHTML = '<span>.</span><span>.</span><span>.</span>';
        document.getElementById('chat-messages').appendChild(typingIndicator);
        
        // Scroll to make typing indicator visible
        scrollToBottom();
        
        // Prepare form data for the request
        const formData = new FormData();
        formData.append('message', message);
        
        // Add anonymous mode flag
        const anonymousMode = document.getElementById('anonymous-mode-toggle').checked;
        formData.append('anonymous_mode', anonymousMode);
        
        // Add user name if available
        const username = document.querySelector('meta[name="username"]')?.content;
        if (username) formData.append('user_name', username);
        
        // Add timeout to the fetch request
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 second timeout
        
        // Send request to simple-chat.php API which has no database dependencies
        fetch('api/simple-chat.php', {
            method: 'POST',
            body: formData,
            signal: controller.signal
        })
        .then(response => {
            clearTimeout(timeoutId); // Clear the timeout
            
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            
            // Check content type to avoid parsing HTML as JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.indexOf('application/json') === -1) {
                return response.text().then(text => {
                    throw new Error('Server returned non-JSON response: ' + text);
                });
            }
            
            return response.json();
        })
        .then(data => {
            // Remove typing indicator
            if (typingIndicator) {
                typingIndicator.remove();
            }
            
            if (data.response) {
                // Create AI message element
                const chatMessages = document.getElementById('chat-messages');
                const aiMessageDiv = document.createElement('div');
                aiMessageDiv.className = 'ai-message';
                
                // Add emotion class if available
                if (data.emotion) {
                    aiMessageDiv.classList.add('emotion-' + data.emotion);
                }
                
                // Add message content
                aiMessageDiv.innerHTML = `<p>${data.response}</p>`;
                chatMessages.appendChild(aiMessageDiv);
                
                // Scroll to bottom of chat
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Text to speech if enabled
                if (document.getElementById('voice-response-toggle').checked) {
                    const utterance = new SpeechSynthesisUtterance(data.response);
                    window.speechSynthesis.speak(utterance);
                }
                
                // Save messages to localStorage if not in anonymous mode
                if (!document.getElementById('anonymous-mode-toggle').checked) {
                    saveChatMessages();
                }
            } else {
                // Show error message
                if (errorContainer) {
                    errorContainer.textContent = 'Error processing your message';
                    errorContainer.style.display = 'block';
                }
                
                // Add a friendly message to the chat about the error
                addAIResponse("I'm sorry, I couldn't process your message. Please try again or refresh the page if the problem persists.");
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to send message. Please try again.');
            
            // Remove typing indicator
            if (typingIndicator) {
                typingIndicator.remove();
            }
            
            // Show error message with more specific details
            if (errorContainer) {
                let errorMessage = 'Unable to communicate with the server. Please try again.';
                
                if (error.name === 'AbortError') {
                    errorMessage = 'Request timed out. The server is taking too long to respond.';
                } else if (error.message.includes('Failed to fetch')) {
                    errorMessage = 'Network connection issue. Please check your internet connection.';
                } else if (error.message.includes('non-JSON response')) {
                    errorMessage = 'The server returned an unexpected response format.';
                }
                
                errorContainer.textContent = errorMessage;
                errorContainer.style.display = 'block';
            }
            
            // Add a message to the chat about the error
            const chatMessages = document.getElementById('chat-messages');
            const aiMessageDiv = document.createElement('div');
            aiMessageDiv.className = 'ai-message error-message';
            aiMessageDiv.innerHTML = `<p>I'm having trouble connecting to the server. Please try again in a moment.</p>`;
            chatMessages.appendChild(aiMessageDiv);
            
            // Scroll to bottom
            scrollToBottom();
        });
    }
    
    // Function to scroll to bottom of chat
    function scrollToBottom() {
        const chatMessages = document.getElementById('chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }
    
    // Function to suggest a topic in the chat input
    function suggestTopic(topic) {
        const messageInput = document.getElementById('message-input');
        messageInput.value = topic;
        messageInput.focus();
    }
    function analyzeCrisisRisk(message) {              
        fetch('crisis_detection.php', {    
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=analyze_message&message=' + encodeURIComponent(message)
        })
        .then(response => response.json())
        .then(data => {
            if (data.crisis_detected) {
                // Update crisis alert message
                document.getElementById('crisis-message').textContent = data.response;
                
                // Show crisis alert
                document.getElementById('crisis-alert').classList.remove('d-none');
                
                // Scroll to make sure the alert is visible
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        })
        .catch(error => {
            console.error('Error analyzing message:', error);
        });
    }
    
    // Add event listener for message sending to check for crisis
    document.getElementById('send-message-btn').addEventListener('click', function() {
        const message = document.getElementById('message-input').value.trim();
        if (message) {
            analyzeCrisisRisk(message);
        }
    });
    
    // Close crisis alert when button is clicked
    document.getElementById('crisis-alert-close').addEventListener('click', function() {
        document.getElementById('crisis-alert').classList.add('d-none');
    });
    
    // Show motivation suggestion after some time
    setTimeout(function() {
        document.getElementById('motivation-suggestion').classList.remove('d-none');
    }, 60000); // Show after 1 minute
    
    // Dismiss motivation suggestion
    document.getElementById('motivation-dismiss').addEventListener('click', function() {
        document.getElementById('motivation-suggestion').classList.add('d-none');
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Check if there are any messages in the chat
        const chatMessages = document.getElementById('chat-messages');
        const emptyChatState = document.getElementById('empty-chat-state');
        const welcomeMessage = document.getElementById('welcome-message');
        
        // If there's only the welcome message, show the empty state
        if (chatMessages && emptyChatState) {
            // Check if no user messages exist
            if (chatMessages.querySelectorAll('.user-message').length === 0) {
                // Hide welcome message
                if (welcomeMessage) {
                    welcomeMessage.style.display = 'none';
                }
                
                // Hide default suggestions
                const defaultSuggestions = document.getElementById('default-suggestions');
                if (defaultSuggestions) {
                    defaultSuggestions.style.display = 'none';
                }
                
                // Show empty state
                emptyChatState.style.display = 'flex';
            }
        }
        
        // Start conversation button event
        const startConversationBtn = document.getElementById('start-conversation-btn');
        if (startConversationBtn) {
            startConversationBtn.addEventListener('click', function() {
                // Hide empty state
                if (emptyChatState) {
                    emptyChatState.style.display = 'none';
                }
                
                // Show welcome message
                if (welcomeMessage) {
                    welcomeMessage.style.display = 'block';
                }
                
                // Show default suggestions
                const defaultSuggestions = document.getElementById('default-suggestions');
                if (defaultSuggestions) {
                    defaultSuggestions.style.display = 'block';
                }
                
                // Focus on input
                const messageInput = document.getElementById('message-input');
                if (messageInput) {
                    messageInput.focus();
                }
            });
        }
        
        // Make sure dark mode is correctly applied on page load
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
        }
    });

    // Function to show notification
    function showNotification(message, type = 'info') {
        // Create notification container if it doesn't exist
        let container = document.getElementById('notification-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'notification-container';
            document.body.appendChild(container);
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        // Determine icon based on type
        let icon;
        switch (type) {
            case 'success':
                icon = 'check-circle';
                break;
            case 'error':
                icon = 'exclamation-circle';
                break;
            case 'warning':
                icon = 'exclamation-triangle';
                break;
            default:
                icon = 'info-circle';
        }
        
        // Set notification content
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Add to notification container
        container.appendChild(notification);
        
        // Add event listener to close button
        notification.querySelector('.notification-close').addEventListener('click', function() {
            notification.classList.add('fadeOut');
            setTimeout(() => notification.remove(), 400);
        });
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.add('fadeOut');
                setTimeout(() => notification.remove(), 400);
            }
        }, 5000);
    }

    // Backwards compatibility for old showError function
    function showError(message) {
        showNotification(message, 'error');
    }

    // Function to show new chat animation
    function showNewChatAnimation() {
        const container = document.getElementById('new-chat-animation');
        
        container.classList.add('active');
        
        setTimeout(() => {
            container.classList.remove('active');
        }, 2000); // Animation will display for 2 seconds
    }
    
    // Function to show mood saved animation
    function showMoodSavedAnimation() {
        const container = document.getElementById('mood-saved-animation');
        
        container.classList.add('active');
        
        setTimeout(() => {
            container.classList.remove('active');
        }, 2000); // Animation will display for 2 seconds
    }
</script>

    <style>
    /* Updated professional styling for chat interface */
    .chat-container {
        display: flex;
        flex-direction: column;
        height: 80vh;
        max-height: 800px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        border-radius: 16px;
        overflow: hidden;
        background-color: #fff;
        margin-bottom: 20px;
    }
    
    .chat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: white;
        border-radius: 16px 16px 0 0;
    }
    
    .chat-header-left {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .chat-header-info {
        text-align: center;
    }
    
    .chat-header-info h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .chat-header-info p {
        margin: 0;
        font-size: 0.9rem;
        opacity: 0.8;
    }
    
    .chat-header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .btn-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        background-color: rgba(255, 255, 255, 0.2);
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-icon:hover {
        background-color: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }
    
    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background-color: #f8fafc;
    }
    
    .user-message {
        background-color: #4f46e5;
        color: white;
        border-radius: 18px 18px 0 18px;
        padding: 12px 16px;
        margin: 10px 0 10px auto;
        max-width: 80%;
        animation: fadeIn 0.3s ease-out, slideInRight 0.4s ease-out;
        word-wrap: break-word;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    .ai-message {
        background-color: white;
        color: #333;
        border-radius: 18px 18px 18px 0;
        padding: 12px 16px;
        margin: 10px 0;
        max-width: 80%;
        animation: fadeIn 0.3s ease-out, slideInLeft 0.4s ease-out;
        word-wrap: break-word;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }
    
    .user-message p, .ai-message p {
        margin: 0;
        line-height: 1.5;
    }
    
    .chat-input-container {
        padding: 15px 20px;
        border-top: 1px solid #e2e8f0;
        background-color: white;
    }
    
    .chat-input {
        width: 100%;
        padding: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        resize: none;
    }
    
    .chat-send-button {
        background-color: #4f46e5;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .chat-send-button:hover {
        background-color: #4338ca;
        transform: translateY(-2px);
    }
    
    .typing-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 60px;
    }
    
    .typing-indicator span {
        width: 8px;
        height: 8px;
        background-color: #64748b;
        border-radius: 50%;
        display: inline-block;
        margin: 0 1px;
        opacity: 0.4;
        animation: typing 1.4s infinite ease-in-out;
    }
    
    .typing-indicator span:nth-child(1) {
        animation-delay: 0s;
    }
    
    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }
    
    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }
    
    @keyframes typing {
        0% { transform: translateY(0); opacity: 0.4; }
        50% { transform: translateY(-10px); opacity: 1; }
        100% { transform: translateY(0); opacity: 0.4; }
    }
    
    .chat-history-sidebar {
        position: fixed;
        top: 0;
        left: -350px;
        width: 350px;
        height: 100%;
        background-color: white;
        z-index: 1000;
        box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
        transition: left 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    
    .chat-history-sidebar.active {
        left: 0;
    }
    
    .history-sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: white;
    }
    
    .history-sidebar-header h4 {
        margin: 0;
        font-size: 1.2rem;
    }
    
    .btn-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
    }
    
    .history-sidebar-content {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
    }
    
    .chat-session-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 10px;
        background-color: #f8fafc;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .chat-session-item:hover {
        background-color: #e2e8f0;
        transform: translateY(-2px);
    }
    
    .chat-session-info {
        flex: 1;
    }
    
    .chat-session-info h6 {
        margin: 0 0 5px 0;
        font-size: 1rem;
        color: #1e293b;
    }
    
    .chat-session-info p {
        margin: 0;
        font-size: 0.8rem;
        color: #64748b;
    }
    
    .chat-session-actions {
        display: flex;
    }
    
    .btn-delete-session {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #fee2e2;
        color: #ef4444;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .btn-delete-session:hover {
        background-color: #fecaca;
        transform: scale(1.05);
    }
    
    .no-sessions, .error-message, .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 30px;
        text-align: center;
        color: #64748b;
    }
    
    .no-sessions i, .error-message i, .loading-spinner i {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    .error-message {
        color: #ef4444;
    }
    
    .loading-spinner i {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    @keyframes fadeOutRight {
        from { opacity: 1; transform: translateX(0); }
        to { opacity: 0; transform: translateX(20px); }
    }
    
    @keyframes slideInRight {
        from { transform: translateX(20px); }
        to { transform: translateX(0); }
    }
    
    @keyframes slideInLeft {
        from { transform: translateX(-20px); }
        to { transform: translateX(0); }
    }
    
    .notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        max-width: 350px;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        z-index: 1000;
        animation: slideInUp 0.3s ease-out, fadeIn 0.3s ease-out;
    }
    
    .notification.fadeOut {
        animation: fadeOut 0.3s ease-out forwards;
    }
    
    .notification-content {
        display: flex;
        align-items: center;
        padding: 15px;
    }
    
    .notification-content i {
        margin-right: 15px;
        font-size: 1.5rem;
    }
    
    .notification-success i {
        color: #10b981;
    }
    
    .notification-error i {
        color: #ef4444;
    }
    
    .notification-info i {
        color: #0ea5e9;
    }
    
    .notification-close {
        position: absolute;
        top: 10px;
        right: 10px;
        background: none;
        border: none;
        color: #64748b;
        cursor: pointer;
    }
    
    @keyframes slideInUp {
        from { transform: translateY(20px); }
        to { transform: translateY(0); }
    }
    
    .options-panel {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 20px;
    }
    
    .panel-title {
        display: flex;
        align-items: center;
        font-size: 1.2rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .option-item {
        margin-bottom: 15px;
    }
    
    .form-check-input {
        width: 3em;
        height: 1.5em;
    }
    
    .form-check-label {
        padding-left: 8px;
        cursor: pointer;
    }
    
    .btn-outline-primary {
        color: #4f46e5;
        border-color: #4f46e5;
    }
    
    .btn-outline-primary:hover {
        background-color: #4f46e5;
        color: white;
    }
    
    .wellness-panel {
        background: linear-gradient(135deg, #f9f9ff, #f0f4ff);
        border-radius: 12px;
        overflow: hidden;
        margin-top: 20px;
    }
    
    .panel-header {
        padding: 15px 20px;
        background: linear-gradient(90deg, #4f46e5, #6366f1);
        color: white;
    }
    
    .panel-header h4 {
        margin: 0;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
    }
    
    .panel-body {
        padding: 20px;
    }
    
    .wellness-tip {
        background-color: white;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border-left: 4px solid #4f46e5;
    }
    
    .wellness-tip p {
        margin: 0;
        line-height: 1.5;
        color: #1e293b;
        font-style: italic;
    }
    
    .chat-suggestions {
        background-color: white;
        border-radius: 12px;
        padding: 15px;
        margin: 20px 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        animation: fadeIn 0.5s ease-out;
    }
    
    .suggestion-title {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 10px;
    }
    
    .suggestion-items {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .suggestion-chip {
        background-color: #f1f5f9;
        color: #1e293b;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
    }

    .suggestion-chip:hover {
        background-color: #e2e8f0;
        transform: translateY(-2px);
    }
    
    .empty-chat-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 50px 20px;
        text-align: center;
        height: 100%;
    }
    
    .empty-chat-state i {
        font-size: 4rem;
        color: #94a3b8;
        margin-bottom: 20px;
    }
    
    .empty-chat-state h3 {
        color: #1e293b;
        margin-bottom: 15px;
    }
    
    .empty-chat-state p {
        color: #64748b;
        margin-bottom: 25px;
    }
    
    .start-chat-btn {
        background-color: #4f46e5;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 30px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .start-chat-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
    }
    
    /* Dark mode styles for chat interface */
    body.dark-mode .chat-container {
        background-color: #1e1e1e;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
    }
    
    body.dark-mode .chat-messages {
        background-color: #121212;
    }
    
    body.dark-mode .user-message {
        background-color: #6366f1;
    }

    body.dark-mode .ai-message {
        background-color: #2a2a3a;
        color: #e2e8f0;
    }

    body.dark-mode .chat-input-container {
        background-color: #1e1e1e;
        border-top: 1px solid #333;
    }

    body.dark-mode .chat-input {
        color: #e2e8f0;
    }

    body.dark-mode .chat-send-button {
        background-color: #2a2a3a;
        color: #94a3b8;
    }
    
    body.dark-mode .options-panel,
    body.dark-mode .chat-suggestions,
    body.dark-mode .suggestion-chip,
    body.dark-mode .wellness-tip {
        background-color: #1e1e1e;
        color: #e2e8f0;
    }

    body.dark-mode .panel-title,
    body.dark-mode .wellness-tip p {
        color: #e2e8f0;
    }

    body.dark-mode .chat-history-sidebar {
        background-color: #1e1e1e;
    }
    
    body.dark-mode .chat-session-item {
        background-color: #2a2a3a;
    }
    
    body.dark-mode .chat-session-info h6 {
        color: #e2e8f0;
    }
    
    body.dark-mode .chat-session-info p {
        color: #94a3b8;
    }

    /* Add emotion-specific styling for messages */
    .ai-message.emotion-happy {
        border-left: 4px solid #10b981; /* green */
        background-color: rgba(16, 185, 129, 0.05);
    }
    
    .ai-message.emotion-sad {
        border-left: 4px solid #60a5fa; /* blue */
        background-color: rgba(96, 165, 250, 0.05);
    }
    
    .ai-message.emotion-anxious {
        border-left: 4px solid #f59e0b; /* amber */
        background-color: rgba(245, 158, 11, 0.05);
    }
    
    .ai-message.emotion-angry {
        border-left: 4px solid #ef4444; /* red */
        background-color: rgba(239, 68, 68, 0.05);
    }
    
    .ai-message.emotion-numb {
        border-left: 4px solid #8b5cf6; /* purple */
        background-color: rgba(139, 92, 246, 0.05);
    }
    
    .ai-message.emotion-neutral {
        border-left: 4px solid #6b7280; /* gray */
        background-color: rgba(107, 114, 128, 0.05);
    }
    
    .ai-message.emotion-crisis {
        border-left: 4px solid #dc2626; /* bright red */
        background-color: rgba(220, 38, 38, 0.08);
        font-weight: 500;
    }

    /* Fullscreen Animation Styles */
    .fullscreen-animation {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }

    .fullscreen-animation.active {
        opacity: 1;
        visibility: visible;
    }

    .animation-content {
        text-align: center;
        transform: translateY(20px);
        transition: transform 0.4s ease;
    }

    .fullscreen-animation.active .animation-content {
        transform: translateY(0);
    }

    .animation-icon {
        font-size: 5rem;
        color: #4f46e5;
        margin-bottom: 1rem;
        animation: pulse 1s infinite alternate;
    }

    .fullscreen-animation h3 {
        font-size: 1.5rem;
        color: #1f2937;
        font-weight: 600;
    }

    @keyframes pulse {
        from {
            transform: scale(1);
            opacity: 0.8;
        }
        to {
            transform: scale(1.1);
            opacity: 1;
        }
    }

    /* Dark mode styles */
    body.dark-mode .fullscreen-animation {
        background-color: rgba(30, 30, 45, 0.9);
    }

    body.dark-mode .fullscreen-animation h3 {
        color: #e5e7eb;
    }

    body.dark-mode .animation-icon {
        color: #818cf8;
    }
</style>
</body>
</html> 