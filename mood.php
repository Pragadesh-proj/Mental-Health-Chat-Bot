<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$page_title = "Mood Tracker";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="app-wrapper">
    <div class="main-content">
        <div class="container my-5">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <!-- Page header -->
                    <div class="page-header mb-4">
                        <h1 class="display-5 fw-bold text-center">
                            <i class="fas fa-chart-line text-primary me-2"></i> Mood Tracker
                        </h1>
                        <p class="text-muted text-center">Track and visualize your emotional well-being over time</p>
                    </div>

                    <!-- Mood statistics cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center p-4">
                                    <div class="mood-stat-icon mb-3">
                                        <i class="fas fa-smile-beam"></i>
                                    </div>
                                    <h5 class="card-title">Predominant Mood</h5>
                                    <p class="mood-value" id="predominant-mood">Loading...</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center p-4">
                                    <div class="mood-stat-icon mb-3">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <h5 class="card-title">Entries</h5>
                                    <p class="mood-value" id="mood-entries">Loading...</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body text-center p-4">
                                    <div class="mood-stat-icon mb-3">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <h5 class="card-title">Trend</h5>
                                    <p class="mood-value" id="mood-trend">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mood chart and add new mood -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Mood History</h5>
                                    <div class="chart-controls">
                                        <button class="btn btn-sm btn-outline-primary time-filter active" data-days="7">Week</button>
                                        <button class="btn btn-sm btn-outline-primary time-filter" data-days="30">Month</button>
                                        <button class="btn btn-sm btn-outline-primary time-filter" data-days="90">3 Months</button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container" style="position: relative; height:300px;">
                                        <canvas id="moodChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-transparent border-0">
                                    <h5 class="mb-0">Track New Mood</h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">How are you feeling right now?</p>
                                    <div class="mood-tracking-options">
                                        <div class="mood-option" data-mood="very_happy" data-value="5">
                                            <div class="mood-emoji">😀</div>
                                            <div class="mood-label">Very Happy</div>
                                        </div>
                                        <div class="mood-option" data-mood="happy" data-value="4">
                                            <div class="mood-emoji">😊</div>
                                            <div class="mood-label">Happy</div>
                                        </div>
                                        <div class="mood-option" data-mood="neutral" data-value="3">
                                            <div class="mood-emoji">😐</div>
                                            <div class="mood-label">Neutral</div>
                                        </div>
                                        <div class="mood-option" data-mood="sad" data-value="2">
                                            <div class="mood-emoji">😔</div>
                                            <div class="mood-label">Sad</div>
                                        </div>
                                        <div class="mood-option" data-mood="very_sad" data-value="1">
                                            <div class="mood-emoji">😢</div>
                                            <div class="mood-label">Very Sad</div>
                                        </div>
                                    </div>
                                    <div class="form-floating mt-3">
                                        <textarea class="form-control" id="mood-notes" style="height: 100px"></textarea>
                                        <label for="mood-notes">Notes (optional)</label>
                                    </div>
                                    <div class="d-grid mt-3">
                                        <button id="save-mood-btn" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Mood
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mood entries list -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Entries</h5>
                            <button class="btn btn-sm btn-outline-danger" id="clear-moods-btn">
                                <i class="fas fa-trash-alt me-2"></i>Clear All
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush" id="mood-entries-list">
                                <!-- Mood entries will be loaded here -->
                                <div class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mood insights -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0">Mood Insights</h5>
                        </div>
                        <div class="card-body">
                            <div id="mood-insights">
                                <p class="text-muted text-center">Track your mood regularly to receive personalized insights.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Psychology resources -->
                    <div class="card border-0 shadow-sm mood-resources">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0"><i class="fas fa-brain me-2"></i>Psychology Resources</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="therapy-resource-card">
                                        <h5><i class="fas fa-lightbulb me-2"></i>Cognitive Behavioral Therapy</h5>
                                        <p>Learn how thoughts influence your emotions and behaviors, and practice techniques to reframe negative thinking patterns.</p>
                                        <a href="#" class="btn btn-sm btn-outline-primary">Learn More</a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="therapy-resource-card">
                                        <h5><i class="fas fa-heart me-2"></i>Mindfulness Practice</h5>
                                        <p>Discover how mindfulness can help reduce stress and improve your emotional well-being through present-moment awareness.</p>
                                        <a href="#" class="btn btn-sm btn-outline-primary">Learn More</a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="therapy-resource-card">
                                        <h5><i class="fas fa-comments me-2"></i>Dialectical Behavior Therapy</h5>
                                        <p>Explore DBT techniques for emotional regulation, distress tolerance, and interpersonal effectiveness.</p>
                                        <a href="#" class="btn btn-sm btn-outline-primary">Learn More</a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="therapy-resource-card">
                                        <h5><i class="fas fa-user-friends me-2"></i>Positive Psychology</h5>
                                        <p>Focus on strengths, virtues, and positive emotions to boost resilience and overall life satisfaction.</p>
                                        <a href="#" class="btn btn-sm btn-outline-primary">Learn More</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mood entry deletion confirmation modal -->
<div class="modal fade" id="delete-mood-modal" tabindex="-1" aria-labelledby="delete-mood-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="delete-mood-modal-label">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete all mood entries? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">Delete All</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Animation Container -->
<div id="success-animation-container" class="fullscreen-animation">
    <div class="animation-content">
        <div class="animation-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h3 id="success-message">You have saved successfully! Thanks for your response</h3>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<style>
    /* Mood tracker specific styles */
    .mood-stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: rgba(79, 70, 229, 0.1);
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin: 0 auto;
    }
    
    .mood-value {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
    }
    
    .time-filter {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
    
    .time-filter.active {
        background-color: #4f46e5;
        color: white;
    }
    
    .mood-tracking-options {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 15px;
    }
    
    .mood-option {
        flex: 1 1 calc(20% - 8px);
        min-width: 60px;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px 5px;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .mood-option:hover {
        background-color: rgba(79, 70, 229, 0.05);
        transform: translateY(-2px);
    }
    
    .mood-option.selected {
        background-color: rgba(79, 70, 229, 0.1);
        border: 2px solid #4f46e5;
    }
    
    .mood-emoji {
        font-size: 24px;
        margin-bottom: 5px;
    }
    
    .mood-label {
        font-size: 12px;
        text-align: center;
        color: #6b7280;
    }
    
    .list-group-item-action:hover {
        background-color: rgba(79, 70, 229, 0.03);
    }
    
    .therapy-resource-card {
        background-color: #f8fafc;
        border-radius: 10px;
        padding: 20px;
        height: 100%;
        transition: all 0.3s ease;
    }
    
    .therapy-resource-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
    }
    
    .therapy-resource-card h5 {
        color: #4f46e5;
        margin-bottom: 10px;
    }
    
    .therapy-resource-card p {
        color: #6b7280;
        margin-bottom: 15px;
        font-size: 0.95rem;
    }
    
    .mood-chart-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 300px;
        color: #9ca3af;
    }
    
    .mood-chart-empty i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    /* Dark mode adjustments */
    body.dark-mode .therapy-resource-card {
        background-color: #1e1e2d;
    }
    
    body.dark-mode .therapy-resource-card h5 {
        color: #818cf8;
    }
    
    body.dark-mode .therapy-resource-card p {
        color: #9ca3af;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mood options selection
        const moodOptions = document.querySelectorAll('.mood-option');
        let selectedMood = null;
        
        moodOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove selected class from all options
                moodOptions.forEach(opt => opt.classList.remove('selected'));
                // Add selected class to clicked option
                this.classList.add('selected');
                selectedMood = this;
            });
        });
        
        // Save mood button
        const saveMoodBtn = document.getElementById('save-mood-btn');
        if (saveMoodBtn) {
            saveMoodBtn.addEventListener('click', function() {
                if (!selectedMood) {
                    showNotification('Please select a mood', 'warning');
                    return;
                }
                
                const moodData = {
                    mood: selectedMood.dataset.mood,
                    value: parseInt(selectedMood.dataset.value),
                    emoji: selectedMood.querySelector('.mood-emoji').textContent,
                    notes: document.getElementById('mood-notes').value,
                    date: new Date().toISOString()
                };
                
                // Save mood data to localStorage
                const moodHistory = JSON.parse(localStorage.getItem('moodHistory') || '[]');
                moodHistory.unshift(moodData);
                localStorage.setItem('moodHistory', JSON.stringify(moodHistory));
                
                // Clear form
                moodOptions.forEach(opt => opt.classList.remove('selected'));
                document.getElementById('mood-notes').value = '';
                selectedMood = null;
                
                // Show success animation
                showSuccessAnimation();
                
                // Update UI
                loadMoodData();
                
                // Show notification
                showNotification('Mood saved successfully!', 'success');
            });
        }
        
        // Clear moods button
        const clearMoodsBtn = document.getElementById('clear-moods-btn');
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
        
        if (clearMoodsBtn) {
            clearMoodsBtn.addEventListener('click', function() {
                const deleteModal = new bootstrap.Modal(document.getElementById('delete-mood-modal'));
                deleteModal.show();
            });
        }
        
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                // Clear mood data
                localStorage.removeItem('moodHistory');
                
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('delete-mood-modal')).hide();
                
                // Show notification
                showNotification('All mood entries have been deleted', 'info');
                
                // Update UI
                loadMoodData();
            });
        }
        
        // Time filter buttons
        const timeFilters = document.querySelectorAll('.time-filter');
        timeFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                timeFilters.forEach(f => f.classList.remove('active'));
                this.classList.add('active');
                loadMoodData(parseInt(this.dataset.days));
            });
        });
        
        // Function to load mood data
        function loadMoodData(days = 7) {
            const moodHistory = JSON.parse(localStorage.getItem('moodHistory') || '[]');
            
            // Update mood entries count
            document.getElementById('mood-entries').textContent = moodHistory.length;
            
            // Calculate predominant mood
            if (moodHistory.length > 0) {
                const moodCounts = {};
                moodHistory.forEach(entry => {
                    moodCounts[entry.mood] = (moodCounts[entry.mood] || 0) + 1;
                });
                
                let predominantMood = '';
                let maxCount = 0;
                
                for (const mood in moodCounts) {
                    if (moodCounts[mood] > maxCount) {
                        maxCount = moodCounts[mood];
                        predominantMood = mood;
                    }
                }
                
                const moodEmoji = moodHistory.find(entry => entry.mood === predominantMood)?.emoji || '';
                document.getElementById('predominant-mood').innerHTML = `${moodEmoji} ${predominantMood.replace('_', ' ')}`;
                
                // Calculate mood trend
                const recentMoods = moodHistory.slice(0, Math.min(5, moodHistory.length));
                let trendDirection = 'Stable';
                let trendIcon = '<i class="fas fa-minus"></i>';
                
                if (recentMoods.length >= 3) {
                    const avgRecent = recentMoods.slice(0, 3).reduce((sum, entry) => sum + entry.value, 0) / 3;
                    const avgOlder = recentMoods.slice(Math.max(0, recentMoods.length - 3)).reduce((sum, entry) => sum + entry.value, 0) / Math.min(3, recentMoods.length);
                    
                    if (avgRecent > avgOlder + 0.5) {
                        trendDirection = 'Improving';
                        trendIcon = '<i class="fas fa-arrow-up text-success"></i>';
                    } else if (avgRecent < avgOlder - 0.5) {
                        trendDirection = 'Declining';
                        trendIcon = '<i class="fas fa-arrow-down text-danger"></i>';
                    }
                }
                
                document.getElementById('mood-trend').innerHTML = `${trendIcon} ${trendDirection}`;
                
                // Load mood entries list
                const entriesList = document.getElementById('mood-entries-list');
                
                if (moodHistory.length > 0) {
                    let entriesHTML = '';
                    
                    moodHistory.slice(0, 10).forEach((entry, index) => {
                        const date = new Date(entry.date);
                        const formattedDate = date.toLocaleDateString('en-US', {
                            weekday: 'short',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        entriesHTML += `
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <span class="me-2">${entry.emoji}</span>
                                            ${entry.mood.replace('_', ' ')}
                                        </h6>
                                        <p class="mb-1 text-muted small">${formattedDate}</p>
                                    </div>
                                    <div>
                                        ${entry.notes ? `<button class="btn btn-sm btn-link view-notes" data-index="${index}">View Notes</button>` : ''}
                                    </div>
                                </div>
                                ${entry.notes ? `<div class="mood-notes d-none">${entry.notes}</div>` : ''}
                            </div>
                        `;
                    });
                    
                    entriesList.innerHTML = entriesHTML;
                    
                    // Add event listeners to view notes buttons
                    document.querySelectorAll('.view-notes').forEach(button => {
                        button.addEventListener('click', function() {
                            const notes = this.closest('.list-group-item').querySelector('.mood-notes');
                            notes.classList.toggle('d-none');
                            this.textContent = notes.classList.contains('d-none') ? 'View Notes' : 'Hide Notes';
                        });
                    });
                } else {
                    entriesList.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-folder-open text-muted mb-3" style="font-size: 3rem;"></i>
                            <p>No mood entries yet. Start tracking your mood daily!</p>
                        </div>
                    `;
                }
                
                // Load mood insights
                const insightsContainer = document.getElementById('mood-insights');
                
                if (moodHistory.length >= 5) {
                    const positiveCount = moodHistory.filter(entry => entry.value >= 4).length;
                    const negativeCount = moodHistory.filter(entry => entry.value <= 2).length;
                    const positivePercentage = Math.round((positiveCount / moodHistory.length) * 100);
                    
                    let insightHTML = '';
                    
                    if (positivePercentage >= 70) {
                        insightHTML = `
                            <div class="alert alert-success">
                                <h6><i class="fas fa-chart-line me-2"></i>Positive Trend</h6>
                                <p class="mb-0">You've been feeling positive ${positivePercentage}% of the time. Keep up the great work with your wellness routine!</p>
                            </div>
                        `;
                    } else if (negativeCount >= 3 && (negativeCount / moodHistory.length) >= 0.5) {
                        insightHTML = `
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>Support Recommended</h6>
                                <p class="mb-0">You've recorded several low moods recently. Consider practicing self-care activities or reaching out to a supportive friend.</p>
                            </div>
                        `;
                    } else {
                        insightHTML = `
                            <div class="alert alert-info">
                                <h6><i class="fas fa-lightbulb me-2"></i>Balanced Outlook</h6>
                                <p class="mb-0">Your mood has been fairly balanced lately. Continue monitoring and notice what factors influence your emotional well-being.</p>
                            </div>
                        `;
                    }
                    
                    insightsContainer.innerHTML = insightHTML;
                } else {
                    insightsContainer.innerHTML = `
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>More Data Needed</h6>
                            <p class="mb-0">Track your mood for at least 5 days to receive personalized insights about your emotional patterns.</p>
                        </div>
                    `;
                }
                
                // Load chart data
                loadMoodChart(moodHistory, days);
                
            } else {
                // No mood data
                document.getElementById('predominant-mood').textContent = 'No data';
                document.getElementById('mood-trend').textContent = 'No data';
                
                document.getElementById('mood-entries-list').innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open text-muted mb-3" style="font-size: 3rem;"></i>
                        <p>No mood entries yet. Start tracking your mood daily!</p>
                    </div>
                `;
                
                document.getElementById('mood-insights').innerHTML = `
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Getting Started</h6>
                        <p class="mb-0">Begin tracking your mood daily to gain insights into your emotional patterns and trends.</p>
                    </div>
                `;
                
                // Show empty state for chart
                const chartContainer = document.querySelector('.chart-container');
                chartContainer.innerHTML = `
                    <div class="mood-chart-empty">
                        <i class="fas fa-chart-bar"></i>
                        <p>No mood data to display</p>
                    </div>
                `;
            }
        }
        
        // Function to load mood chart
        function loadMoodChart(moodHistory, days) {
            if (moodHistory.length === 0) return;
            
            const ctx = document.getElementById('moodChart').getContext('2d');
            
            // Clear previous chart
            if (window.moodChart) {
                window.moodChart.destroy();
            }
            
            // Filter data for selected date range
            const cutoffDate = new Date();
            cutoffDate.setDate(cutoffDate.getDate() - days);
            
            const filteredData = moodHistory.filter(entry => new Date(entry.date) >= cutoffDate);
            
            if (filteredData.length === 0) {
                const chartContainer = document.querySelector('.chart-container');
                chartContainer.innerHTML = `
                    <div class="mood-chart-empty">
                        <i class="fas fa-chart-bar"></i>
                        <p>No mood data for the selected period</p>
                    </div>
                `;
                return;
            }
            
            // Sort by date
            filteredData.sort((a, b) => new Date(a.date) - new Date(b.date));
            
            // Prepare data for chart
            const labels = filteredData.map(entry => {
                const date = new Date(entry.date);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            
            const values = filteredData.map(entry => entry.value);
            
            // Create gradient
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(79, 70, 229, 0.5)');
            gradient.addColorStop(1, 'rgba(79, 70, 229, 0.0)');
            
            // Create chart
            window.moodChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Mood Level',
                        data: values,
                        borderColor: '#4f46e5',
                        backgroundColor: gradient,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#4f46e5',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    scales: {
                        y: {
                            min: 0,
                            max: 5,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    const moodLabels = ['', 'Very Sad', 'Sad', 'Neutral', 'Happy', 'Very Happy'];
                                    return moodLabels[value];
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const moodLabels = ['', 'Very Sad', 'Sad', 'Neutral', 'Happy', 'Very Happy'];
                                    const value = context.parsed.y;
                                    return moodLabels[value];
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        // Function to show success animation
        function showSuccessAnimation() {
            const container = document.getElementById('success-animation-container');
            
            container.classList.add('active');
            
            setTimeout(() => {
                container.classList.remove('active');
            }, 2000); // Animation will display for 2 seconds
        }
        
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
        
        // Load initial data
        loadMoodData();
    });
</script>
</body>
</html> 