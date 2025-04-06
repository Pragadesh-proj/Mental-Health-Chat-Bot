<?php
session_start();
require_once 'config/database.php';

// Set page title for header include
$page_title = "Crisis Resources";
$additional_css = '<link href="assets/css/crisis.css" rel="stylesheet">';
$additional_js = '<script src="assets/js/crisis.js" defer></script>';

// Check if we're trying to get location-based resources via AJAX
if (isset($_POST['action']) && $_POST['action'] == 'get_location_resources') {
    if (isset($_POST['country']) && isset($_POST['lat']) && isset($_POST['lng'])) {
        $country = $conn->real_escape_string($_POST['country']);
        $lat = floatval($_POST['lat']);
        $lng = floatval($_POST['lng']);
        
        // For production, you would integrate with a real mental health provider API
        // For now, we'll return some sample data
        $resources = getLocationBasedResources($country, $lat, $lng);
        echo json_encode(['success' => true, 'resources' => $resources]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit;
}

/**
 * Get location-based mental health resources
 * In production, this would call an external API
 */
function getLocationBasedResources($country, $lat, $lng) {
    // This is mock data - in production you would use a real API
    $resources = [
        'us' => [
            [
                'name' => 'National Suicide Prevention Lifeline',
                'phone' => '1-800-273-8255',
                'website' => 'https://suicidepreventionlifeline.org/',
                'description' => '24/7 free support for people in distress'
            ],
            [
                'name' => 'Crisis Text Line',
                'phone' => 'Text HOME to 741741',
                'website' => 'https://www.crisistextline.org/',
                'description' => 'Text-based crisis support available 24/7'
            ],
            [
                'name' => 'SAMHSA Treatment Locator',
                'phone' => '1-800-662-4357',
                'website' => 'https://findtreatment.samhsa.gov/',
                'description' => 'Find mental health treatment facilities near you'
            ]
        ],
        'uk' => [
            [
                'name' => 'Samaritans',
                'phone' => '116 123',
                'website' => 'https://www.samaritans.org/',
                'description' => '24/7 support for anyone in emotional distress'
            ],
            [
                'name' => 'Mind',
                'phone' => '0300 123 3393',
                'website' => 'https://www.mind.org.uk/',
                'description' => 'Mental health charity offering support and advice'
            ]
        ],
        'canada' => [
            [
                'name' => 'Crisis Services Canada',
                'phone' => '1-833-456-4566',
                'website' => 'https://www.crisisservicescanada.ca/',
                'description' => 'National support services for those in crisis'
            ]
        ],
        'australia' => [
            [
                'name' => 'Lifeline Australia',
                'phone' => '13 11 14',
                'website' => 'https://www.lifeline.org.au/',
                'description' => '24/7 crisis support and suicide prevention'
            ]
        ],
        'global' => [
            [
                'name' => 'International Association for Suicide Prevention',
                'website' => 'https://www.iasp.info/resources/Crisis_Centres/',
                'description' => 'Directory of crisis centers worldwide'
            ]
        ]
    ];
    
    // Return country-specific resources or global if country not found
    return $resources[strtolower($country)] ?? $resources['global'];
}
?>

<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="crisis-header mb-4 text-center">
                <div class="crisis-icon mb-3">
                    <i class="fas fa-heart"></i>
                </div>
                <h1>Crisis Support Resources</h1>
                <p class="lead">If you're experiencing a mental health emergency, help is available right now.</p>
            </div>
            
            <div class="card crisis-card mb-4 pulse-animation">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="emergency-icon me-3">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div>
                            <h2 class="mb-0">Immediate Help</h2>
                            <p class="mb-0 text-muted">Available 24/7, completely confidential</p>
                        </div>
                    </div>
                    
                    <div class="emergency-numbers">
                        <div class="emergency-item">
                            <h5>United States</h5>
                            <div class="d-flex align-items-center">
                                <a href="tel:988" class="btn btn-emergency me-2">
                                    <i class="fas fa-phone-alt me-2"></i>988
                                </a>
                                <div>
                                    <strong>988 Suicide & Crisis Lifeline</strong><br>
                                    <small>Or text "HOME" to 741741</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="emergency-item">
                            <h5>International</h5>
                            <div class="d-flex align-items-center">
                                <a href="tel:911" class="btn btn-emergency me-2">
                                    <i class="fas fa-ambulance me-2"></i>Local Emergency
                                </a>
                                <div>
                                    <strong>Emergency Services</strong><br>
                                    <small>Dial local emergency number (911, 999, 112, etc.)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Find Help Near You</h3>
                </div>
                <div class="card-body">
                    <p>We can help you find local mental health resources based on your location.</p>
                    
                    <div class="location-resources">
                        <button id="find-resources-btn" class="btn btn-primary mb-3">
                            <i class="fas fa-location-arrow me-2"></i>Find Resources Near Me
                        </button>
                        
                        <div id="location-status" class="d-none mb-3 alert alert-info">
                            <i class="fas fa-spinner fa-spin me-2"></i> Finding resources near you...
                        </div>
                        
                        <div id="location-error" class="d-none mb-3 alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> 
                            <span id="location-error-message">Unable to determine your location. Please enable location services.</span>
                        </div>
                        
                        <div id="resources-container" class="d-none">
                            <h4 class="border-bottom pb-2 mb-3">Resources Near You</h4>
                            <div id="resources-list" class="list-group mb-3">
                                <!-- Resources will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-lungs me-2"></i>Guided Breathing Exercise</h3>
                </div>
                <div class="card-body">
                    <p>Take a moment to calm your mind with this guided breathing exercise.</p>
                    
                    <div class="text-center">
                        <div id="breathing-animation" class="breathing-circle">
                            <div class="breathing-text">Breathe</div>
                        </div>
                        
                        <div class="breathing-controls mt-4">
                            <button id="start-breathing" class="btn btn-outline-primary">
                                <i class="fas fa-play me-2"></i>Start Breathing Exercise
                            </button>
                            <button id="stop-breathing" class="btn btn-outline-secondary d-none">
                                <i class="fas fa-stop me-2"></i>Stop
                            </button>
                        </div>
                        
                        <div class="breathing-instruction mt-3">
                            <p id="breathing-instruction-text">Click start to begin a guided 4-7-8 breathing exercise.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-hands-helping me-2"></i>Grounding Techniques</h3>
                </div>
                <div class="card-body">
                    <p>Use these techniques to help yourself feel more present and centered.</p>
                    
                    <div class="grounding-technique mb-4">
                        <h5><span class="technique-number">1</span> The 5-4-3-2-1 Method</h5>
                        <p>Use your senses to ground yourself in the present moment:</p>
                        <ul>
                            <li><strong>5 things you can see</strong> - Look around and name them out loud</li>
                            <li><strong>4 things you can touch/feel</strong> - Notice the texture, temperature</li>
                            <li><strong>3 things you can hear</strong> - Focus on sounds near and far</li>
                            <li><strong>2 things you can smell</strong> - Or like the smell of</li>
                            <li><strong>1 thing you can taste</strong> - Notice what's in your mouth right now</li>
                        </ul>
                    </div>
                    
                    <div class="grounding-technique mb-4">
                        <h5><span class="technique-number">2</span> Body Awareness Exercise</h5>
                        <p>Slow down and pay attention to physical sensations:</p>
                        <ol>
                            <li>Sit comfortably in a chair with both feet on the ground</li>
                            <li>Feel the pressure of your body against the chair</li>
                            <li>Notice the sensation of your feet touching the floor</li>
                            <li>Press your fingertips together and focus on that sensation</li>
                            <li>Take slow, deep breaths and feel your chest rise and fall</li>
                        </ol>
                    </div>
                    
                    <div class="grounding-technique">
                        <h5><span class="technique-number">3</span> Mental Grounding</h5>
                        <p>Try these mental exercises to refocus your thoughts:</p>
                        <ul>
                            <li>Name animals alphabetically (ant, bear, cat...)</li>
                            <li>Count backwards from 100 by 7s</li>
                            <li>Think of 10 things that are a certain color</li>
                            <li>Recite a poem, prayer, or song that brings you comfort</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="chat.php" class="btn btn-lg btn-outline-primary">
                    <i class="fas fa-comment-dots me-2"></i>Return to Chat
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 