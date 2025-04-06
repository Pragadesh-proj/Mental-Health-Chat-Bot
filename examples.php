<?php
/**
 * Example Response Formats
 * A demonstration of the emotion-aware response system
 */

// Include required files
require_once 'config/database.php';
require_once 'api/emotion_detection.php';
require_once 'api/response_formatter.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mental Health Chatbot - Response Examples</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
        }
        .header {
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .response-card {
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            background-color: white;
        }
        .input-example {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #4f46e5;
        }
        .response-happy {
            background-color: rgba(16, 185, 129, 0.1);
            border-left: 4px solid #10b981;
        }
        .response-sad {
            background-color: rgba(96, 165, 250, 0.1);
            border-left: 4px solid #60a5fa;
        }
        .response-anxious {
            background-color: rgba(245, 158, 11, 0.1);
            border-left: 4px solid #f59e0b;
        }
        .response-angry {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
        }
        .response-numb {
            background-color: rgba(139, 92, 246, 0.1);
            border-left: 4px solid #8b5cf6;
        }
        .response-neutral {
            background-color: rgba(156, 163, 175, 0.1);
            border-left: 4px solid #9ca3af;
        }
        .response-crisis {
            background-color: rgba(220, 38, 38, 0.1);
            border-left: 4px solid #dc2626;
        }
        pre {
            background-color: #f1f1f1;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Mental Health Chatbot</h1>
            <h2>Response Examples</h2>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="alert alert-info mb-4">
                    <strong>Response Formatting Demo:</strong> This page demonstrates how the emotion-aware response system formats messages based on detected emotions.
                </div>

                <?php
                // Example user name for personalization
                $userName = "Pragadesh";
                
                // Example messages for each emotion type
                $sampleMessages = [
                    'happy' => "I just got a promotion at work today! I'm so excited!",
                    'sad' => "I've been feeling really down lately. Nothing seems to bring me joy anymore.",
                    'anxious' => "I can't stop worrying about my upcoming presentation. What if I mess up?",
                    'angry' => "I'm so frustrated with my situation right now. Nothing is going right.",
                    'numb' => "I don't feel anything anymore. It's like I'm just going through the motions.",
                    'neutral' => "I'm just checking in. How does this chatbot work?",
                    'crisis' => "I don't see the point in living anymore. I just want the pain to stop."
                ];
                
                // Process each sample message
                foreach ($sampleMessages as $emotionType => $message) {
                    echo '<div class="response-card">';
                    echo '<h3>' . ucfirst($emotionType) . ' Response Example</h3>';
                    
                    echo '<div class="input-example">';
                    echo '<strong>User Message:</strong> "' . htmlspecialchars($message) . '"';
                    echo '</div>';
                    
                    // Analyze message for emotion
                    if ($emotionType === 'crisis') {
                        $formattedResponse = formatCrisisResponse($userName);
                        $response = $formattedResponse['response'];
                        $detectedEmotion = 'crisis';
                    } else {
                        $emotionData = detectEmotion($message);
                        $contextData = analyzeContext($message);
                        
                        // Get base response
                        $baseResponse = getEmotionBasedResponse($emotionData, $contextData, $userName);
                        
                        // Format with our new formatter
                        $formattedResponse = formatEmotionalResponse($baseResponse, $emotionData, $userName);
                        $response = $formattedResponse['response'];
                        $detectedEmotion = $emotionData['emotion'];
                    }
                    
                    echo '<div class="response-' . $detectedEmotion . ' p-3 rounded">';
                    echo '<strong>AI Response:</strong> "' . $response . '"';
                    echo '</div>';
                    
                    echo '<p class="mt-3 mb-0"><strong>Response Features:</strong></p>';
                    echo '<ul>';
                    echo '<li>Detected Emotion: ' . ucfirst($detectedEmotion) . '</li>';
                    echo '<li>Name Personalization: ' . (strpos($response, $userName) !== false ? 'Yes' : 'No') . '</li>';
                    echo '<li>Emoji Use: ' . (preg_match('/[\x{1F300}-\x{1F64F}]/u', $response) ? 'Yes' : 'No') . '</li>';
                    echo '<li>Follow-up Question: ' . (strpos($response, '?') !== false ? 'Yes' : 'No') . '</li>';
                    echo '</ul>';
                    
                    echo '</div>';
                }
                ?>

                <div class="alert alert-warning mt-4">
                    <strong>Note:</strong> These examples show how the system formats responses based on the emotion detected in the user's message. In a real conversation, the system would also incorporate context from previous messages and the user's emotional history.
                </div>

                <div class="mt-5 text-center">
                    <a href="chat.php" class="btn btn-primary">Return to Chat Interface</a>
                    <a href="upgrade_guide.html" class="btn btn-outline-primary ms-2">View Upgrade Guide</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 