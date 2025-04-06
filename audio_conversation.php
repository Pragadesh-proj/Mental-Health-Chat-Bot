<?php
/**
 * Audio Conversation Handler
 * Processes voice input and provides spoken responses
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once 'config/db_connect.php';
require_once 'process_chat.php';
require_once 'google_nlp.php';
require_once 'crisis_detection.php';

/**
 * Class to handle voice-based conversations
 */
class AudioConversation {
    private $db;
    private $chatProcessor;
    private $sentimentAnalyzer;
    private $crisisDetector;
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
        $this->chatProcessor = new ChatProcessor($db);
        $this->sentimentAnalyzer = new GoogleNLP();
        $this->crisisDetector = new CrisisDetection($db);
    }
    
    /**
     * Process audio input and generate response
     * 
     * @param string $audioBlob Base64 encoded audio data
     * @param int $user_id User ID
     * @return array Response data
     */
    public function processAudioInput($audioBlob, $user_id = null) {
        // Convert audio to text
        $transcription = $this->speechToText($audioBlob);
        
        if (empty($transcription)) {
            return [
                'success' => false,
                'error' => 'Could not transcribe audio'
            ];
        }
        
        // Check for crisis indicators in the transcribed text
        $crisisAnalysis = $this->crisisDetector->analyzeMessage($transcription, $user_id);
        
        // If crisis detected, prioritize crisis response
        if ($crisisAnalysis['crisis_detected']) {
            $response = [
                'success' => true,
                'user_message' => $transcription,
                'bot_response' => $crisisAnalysis['response'],
                'audio_response' => $this->textToSpeech($crisisAnalysis['response']),
                'sentiment' => null,
                'crisis_detected' => true,
                'crisis_level' => $crisisAnalysis['level']
            ];
            
            // Add emergency resources if recommended
            if ($crisisAnalysis['recommend_resources']) {
                $response['emergency_resources'] = $crisisAnalysis['emergency_resources'];
            }
            
            return $response;
        }
        
        // Process the message normally
        $sentiment = $this->sentimentAnalyzer->analyzeSentiment($transcription);
        $botResponse = $this->chatProcessor->generateResponse($transcription, $sentiment, $user_id);
        
        // Convert response text to speech
        $audioResponse = $this->textToSpeech($botResponse);
        
        return [
            'success' => true,
            'user_message' => $transcription,
            'bot_response' => $botResponse,
            'audio_response' => $audioResponse,
            'sentiment' => $sentiment,
            'crisis_detected' => false
        ];
    }
    
    /**
     * Convert speech to text
     * 
     * @param string $audioBlob Base64 encoded audio data
     * @return string Transcribed text
     */
    private function speechToText($audioBlob) {
        // For a production environment, integrate with a speech-to-text API like Google Speech-to-Text
        // This is a placeholder implementation
        
        // In a real implementation, you would:
        // 1. Decode the base64 audio data
        // 2. Send it to a speech-to-text service
        // 3. Return the transcribed text
        
        // For testing purposes, we'll send the audio to Google's Speech-to-Text API
        // Note: In a real implementation, you would need to set up authentication
        
        try {
            // Save the audio blob to a temporary file
            $audioData = base64_decode(str_replace('data:audio/webm;base64,', '', $audioBlob));
            $tempFile = tempnam(sys_get_temp_dir(), 'audio_') . '.webm';
            file_put_contents($tempFile, $audioData);
            
            // Configure the request to Google's Speech-to-Text API
            // This is a simplified version and would need proper setup in production
            $url = 'https://speech.googleapis.com/v1/speech:recognize?key=' . $_ENV['GOOGLE_API_KEY'];
            
            // Read the audio file
            $audio = base64_encode(file_get_contents($tempFile));
            
            // Create the request data
            $data = [
                'config' => [
                    'languageCode' => 'en-US',
                    'enableAutomaticPunctuation' => true,
                    'model' => 'command_and_search'
                ],
                'audio' => [
                    'content' => $audio
                ]
            ];
            
            // Initialize cURL session
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            // Execute cURL session and get the response
            $response = curl_exec($ch);
            curl_close($ch);
            
            // Remove the temporary file
            unlink($tempFile);
            
            // Parse the response
            $result = json_decode($response, true);
            
            // Extract the transcription
            if (isset($result['results'][0]['alternatives'][0]['transcript'])) {
                return $result['results'][0]['alternatives'][0]['transcript'];
            }
            
            return '';
        } catch (Exception $e) {
            error_log('Speech-to-text error: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Convert text to speech
     * 
     * @param string $text Text to convert to speech
     * @return string Base64 encoded audio data
     */
    private function textToSpeech($text) {
        // For a production environment, integrate with a text-to-speech API like Google Text-to-Speech
        // This is a placeholder implementation
        
        // In a real implementation, you would:
        // 1. Send the text to a text-to-speech service
        // 2. Get the audio data
        // 3. Return the audio data as a base64 encoded string
        
        // For testing purposes, we'll send the text to Google's Text-to-Speech API
        // Note: In a real implementation, you would need to set up authentication
        
        try {
            // Configure the request to Google's Text-to-Speech API
            // This is a simplified version and would need proper setup in production
            $url = 'https://texttospeech.googleapis.com/v1/text:synthesize?key=' . $_ENV['GOOGLE_API_KEY'];
            
            // Get user voice preferences if available
            $voice = 'en-US-Standard-C'; // Default female voice
            $pitch = 0;
            $speakingRate = 1;
            
            if ($user_id && isset($_SESSION['preferences'])) {
                $voice = $_SESSION['preferences']['voice_type'] ?? $voice;
                $pitch = $_SESSION['preferences']['voice_pitch'] ?? $pitch;
                $speakingRate = $_SESSION['preferences']['voice_speed'] ?? $speakingRate;
            }
            
            // Create the request data
            $data = [
                'input' => [
                    'text' => $text
                ],
                'voice' => [
                    'languageCode' => 'en-US',
                    'name' => $voice
                ],
                'audioConfig' => [
                    'audioEncoding' => 'MP3',
                    'pitch' => $pitch,
                    'speakingRate' => $speakingRate
                ]
            ];
            
            // Initialize cURL session
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            // Execute cURL session and get the response
            $response = curl_exec($ch);
            curl_close($ch);
            
            // Parse the response
            $result = json_decode($response, true);
            
            // Extract the audio content
            if (isset($result['audioContent'])) {
                return 'data:audio/mp3;base64,' . $result['audioContent'];
            }
            
            return '';
        } catch (Exception $e) {
            error_log('Text-to-speech error: ' . $e->getMessage());
            return '';
        }
    }
    
    /**
     * Save voice preferences
     * 
     * @param int $user_id User ID
     * @param string $voice Voice type
     * @param float $pitch Voice pitch
     * @param float $speakingRate Voice speaking rate
     * @return bool Success status
     */
    public function saveVoicePreferences($user_id, $voice, $pitch, $speakingRate) {
        if (!$user_id) return false;
        
        try {
            // Prepare the SQL query
            $stmt = $this->db->prepare("
                UPDATE user_preferences
                SET voice_type = :voice, voice_pitch = :pitch, voice_speed = :speed
                WHERE user_id = :user_id
            ");
            
            // Bind parameters
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':voice', $voice, PDO::PARAM_STR);
            $stmt->bindParam(':pitch', $pitch, PDO::PARAM_STR);
            $stmt->bindParam(':speed', $speakingRate, PDO::PARAM_STR);
            
            // Execute the query
            $success = $stmt->execute();
            
            // If successful, update session preferences
            if ($success) {
                if (!isset($_SESSION['preferences'])) {
                    $_SESSION['preferences'] = [];
                }
                
                $_SESSION['preferences']['voice_type'] = $voice;
                $_SESSION['preferences']['voice_pitch'] = $pitch;
                $_SESSION['preferences']['voice_speed'] = $speakingRate;
            }
            
            return $success;
        } catch (PDOException $e) {
            error_log('Save voice preferences error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get available voice options
     * 
     * @return array Voice options
     */
    public function getVoiceOptions() {
        return [
            'en-US-Standard-A' => 'Male (Deep)',
            'en-US-Standard-B' => 'Male (Medium)',
            'en-US-Standard-C' => 'Female (Medium)',
            'en-US-Standard-D' => 'Male (Soft)',
            'en-US-Standard-E' => 'Female (Soft)',
            'en-US-Standard-F' => 'Female (Expressive)',
            'en-US-Standard-G' => 'Female (Young)',
            'en-US-Standard-H' => 'Female (Mature)',
            'en-US-Standard-I' => 'Male (Young)',
            'en-US-Standard-J' => 'Male (Mature)'
        ];
    }
}

// AJAX handler
if (isset($_POST['action'])) {
    // Get database connection
    global $conn;
    
    // Initialize audio conversation handler
    $audioHandler = new AudioConversation($conn);
    
    // Process based on action
    switch ($_POST['action']) {
        case 'process_audio':
            // Get audio data from POST
            $audioBlob = isset($_POST['audio_data']) ? $_POST['audio_data'] : '';
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            // Process the audio input
            $result = $audioHandler->processAudioInput($audioBlob, $user_id);
            
            // Return the result as JSON
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        case 'save_voice_preferences':
            // Get voice preferences from POST
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $voice = isset($_POST['voice_type']) ? $_POST['voice_type'] : 'en-US-Standard-C';
            $pitch = isset($_POST['voice_pitch']) ? (float) $_POST['voice_pitch'] : 0;
            $speakingRate = isset($_POST['voice_speed']) ? (float) $_POST['voice_speed'] : 1;
            
            // Save the preferences
            $success = $audioHandler->saveVoicePreferences($user_id, $voice, $pitch, $speakingRate);
            
            // Return the result as JSON
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
            break;
            
        case 'get_voice_options':
            // Get the voice options
            $options = $audioHandler->getVoiceOptions();
            
            // Return the result as JSON
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'options' => $options]);
            break;
            
        default:
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
    exit;
}
?> 