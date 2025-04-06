<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // For debugging
        if (!isset($conn) || $conn->connect_error) {
            $error = "Database connection issue: " . ($conn->connect_error ?? 'Not connected');
        } else {
            // Check if status column exists in users table
            $statusExists = false;
            $checkStatus = $conn->query("SHOW COLUMNS FROM users LIKE 'status'");
            if ($checkStatus && $checkStatus->num_rows > 0) {
                $statusExists = true;
                $query = "SELECT id, username, password, status FROM users WHERE email = ?";
            } else {
                // Status column doesn't exist, query without it
                $query = "SELECT id, username, password FROM users WHERE email = ?";
            }
            
            $stmt = $conn->prepare($query);
            
            // Check if prepare was successful
            if ($stmt === false) {
                $error = "Database error: " . $conn->error;
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if (mysqli_num_rows($result) == 1) {
                    $user = mysqli_fetch_assoc($result);
                    if (password_verify($password, $user['password'])) {
                        // Then check if the account is active (if we have status column)
                        if ($statusExists && $user['status'] == 0) {
                            $error = "Your account has been deactivated. Please contact administrator.";
                        } else {
                            // Account is active (or we don't have status info), proceed with login
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            
                            // Don't redirect immediately - let the animation show first
                            // (The JavaScript will handle the redirect after animation)
                            // header("Location: index.php");
                            // exit();
                        }
                    } else {
                        $error = "Invalid password.";
                    }
                } else {
                    $error = "No account found with that email.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mental Health Chatbot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <style>
        /* Professional textbox styling */
        .form-control {
            border: 1.5px solid #e2e8f0;
            background-color: #f8fafc;
            color: #334155;
            transition: all 0.3s ease;
            box-shadow: none;
            padding: 0.75rem 1rem;
            font-size: 1rem;
        }
        
        .form-control:hover {
            border-color: #cbd5e1;
            background-color: #fff;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            outline: none;
        }
        
        .form-control::placeholder {
            color: #94a3b8;
            font-weight: 300;
        }
        
        /* Password input specific styling with professional look */
        .password-container {
            position: relative;
        }
        
        #password {
            padding-right: 50px;
        }
        
        /* Refined eye icon styling */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            cursor: pointer;
            background: transparent;
            border: none;
            color: #64748b;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 0;
        }
        
        .password-toggle i {
            font-size: 1.25rem;
        }
        
        .password-toggle:hover {
            color: #3b82f6;
        }
        
        /* Form label styling with icons */
        .form-label {
            font-weight: 500;
            color: #334155;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-label i {
            color: #64748b;
        }
        
        /* Login button styling with professional look */
        .btn-primary {
            background-color: #3b82f6;
            border-color: #3b82f6;
            color: white;
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 0.375rem;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        
        /* Form container with refined professional look */
        .form-container {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin: 40px auto;
            max-width: 500px;
        }
        
        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        /* Professional form check styling */
        .form-check-input {
            border-color: #cbd5e1;
        }
        
        .form-check-input:checked {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        
        /* Professional link styling */
        a {
            color: #3b82f6;
            transition: color 0.3s ease;
            text-decoration: none;
        }
        
        a:hover {
            color: #2563eb;
            text-decoration: underline;
        }
        
        /* Alert box styling */
        .alert-danger {
            background-color: #fef2f2;
            border-color: #fee2e2;
            color: #b91c1c;
        }
        
        /* Remove scrollbar */
        body {
            overflow-y: auto;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        body::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        /* All-New Modern Welcome Experience */
        .welcome-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            background: linear-gradient(to right, rgba(30, 41, 59, 0.97), rgba(15, 23, 42, 0.97));
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }
        
        .welcome-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Glass morphism container */
        .glass-container {
            width: 90%;
            max-width: 880px;
            min-height: 480px;
            background: rgba(255, 255, 255, 0.07);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            display: flex;
            overflow: hidden;
            position: relative;
            transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1), 
                        box-shadow 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform: scale(0.9);
        }
        
        .welcome-overlay.active .glass-container {
            transform: scale(1);
        }
        
        /* Background shapes */
        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.3;
            transition: all 1.5s ease;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
            top: -150px;
            left: -100px;
            transform: translate(-50px, -50px);
            animation: floatAnimation 15s ease-in-out infinite alternate;
        }
        
        .shape-2 {
            width: 250px;
            height: 250px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            bottom: -120px;
            right: -80px;
            animation: floatAnimation 12s ease-in-out infinite alternate-reverse;
        }
        
        .welcome-overlay.active .shape {
            opacity: 0.5;
            transform: translate(0, 0);
        }
        
        @keyframes floatAnimation {
            0% {
                transform: translate(0, 0);
            }
            50% {
                transform: translate(-20px, 20px);
            }
            100% {
                transform: translate(20px, -20px);
            }
        }
        
        /* Left side with 3D effect */
        .welcome-left {
            flex: 1;
            padding: 40px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        
        .rotating-cube {
            width: 160px;
            height: 160px;
            position: relative;
            transform-style: preserve-3d;
            transform: rotateX(-20deg) rotateY(30deg);
            animation: rotate 20s infinite linear;
            margin-bottom: 30px;
            opacity: 0;
            transition: opacity 0.8s ease 0.5s;
        }
        
        .welcome-overlay.active .rotating-cube {
            opacity: 1;
        }
        
        @keyframes rotate {
            0% {
                transform: rotateX(-20deg) rotateY(0deg);
            }
            100% {
                transform: rotateX(-20deg) rotateY(360deg);
            }
        }
        
        .cube-face {
            position: absolute;
            width: 160px;
            height: 160px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.05), 
                        inset 0 0 20px rgba(255, 255, 255, 0.05);
        }
        
        .face-front {
            transform: translateZ(80px);
        }
        
        .face-back {
            transform: rotateY(180deg) translateZ(80px);
        }
        
        .face-right {
            transform: rotateY(90deg) translateZ(80px);
        }
        
        .face-left {
            transform: rotateY(-90deg) translateZ(80px);
        }
        
        .face-top {
            transform: rotateX(90deg) translateZ(80px);
        }
        
        .face-bottom {
            transform: rotateX(-90deg) translateZ(80px);
        }
        
        .cube-icon {
            font-size: 40px;
            color: white;
            opacity: 0.9;
        }
        
        .brand-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 15px;
            position: relative;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            transition-delay: 0.8s;
        }
        
        .brand-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            text-align: center;
            max-width: 260px;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            transition-delay: 1s;
        }
        
        .welcome-overlay.active .brand-title,
        .welcome-overlay.active .brand-subtitle {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Right side content */
        .welcome-right {
            flex: 1.3;
            background: white;
            border-radius: 0 24px 24px 0;
            padding: 50px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .welcome-right::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        }
        
        .greeting-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
        }
        
        .greeting-text {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 5px;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            transition-delay: 0.3s;
        }
        
        .greeting-name {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            transition-delay: 0.5s;
        }
        
        .time-badge {
            background: #f1f5f9;
            border-radius: 30px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            opacity: 0;
            transform: translateX(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            transition-delay: 0.7s;
        }
        
        .time-badge i {
            color: #3b82f6;
            font-size: 14px;
        }
        
        .time-text {
            color: #475569;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .welcome-right-content {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .welcome-message {
            color: #475569;
            font-size: 1.05rem;
            line-height: 1.7;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            transition-delay: 0.7s;
            position: relative;
            padding-left: 20px;
        }
        
        .welcome-message::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 3px;
            height: 100%;
            background: linear-gradient(to bottom, #3b82f6, transparent);
            border-radius: 3px;
        }
        
        .feature-cards {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        
        .feature-card {
            flex: 1;
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            gap: 10px;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.5s ease;
        }
        
        .feature-card:nth-child(1) {
            transition-delay: 0.9s;
        }
        
        .feature-card:nth-child(2) {
            transition-delay: 1.1s;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 18px;
            margin-bottom: 5px;
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        
        .feature-card:nth-child(2) .feature-icon {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }
        
        .feature-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        
        .feature-text {
            font-size: 0.85rem;
            color: #64748b;
            margin: 0;
        }
        
        .welcome-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            transition-delay: 1.3s;
        }
        
        .welcome-overlay.active .greeting-text,
        .welcome-overlay.active .greeting-name,
        .welcome-overlay.active .time-badge,
        .welcome-overlay.active .welcome-message,
        .welcome-overlay.active .feature-card,
        .welcome-overlay.active .welcome-actions {
            opacity: 1;
            transform: translate(0, 0);
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }
        
        .btn-continue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 12px 30px;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        
        .btn-continue::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .btn-continue:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }
        
        .btn-continue:hover::before {
            opacity: 1;
        }
        
        .btn-icon {
            transition: transform 0.3s ease;
        }
        
        .btn-continue:hover .btn-icon {
            transform: translateX(3px);
        }
        
        /* Responsive styles */
        @media (max-width: 900px) {
            .glass-container {
                flex-direction: column;
                max-width: 95%;
            }
            
            .welcome-left {
                padding: 30px 20px;
            }
            
            .welcome-right {
                border-radius: 0 0 24px 24px;
                padding: 30px;
            }
            
            .rotating-cube {
                width: 120px;
                height: 120px;
                margin-bottom: 20px;
            }
            
            .cube-face {
                width: 120px;
                height: 120px;
            }
            
            .face-front { transform: translateZ(60px); }
            .face-back { transform: rotateY(180deg) translateZ(60px); }
            .face-right { transform: rotateY(90deg) translateZ(60px); }
            .face-left { transform: rotateY(-90deg) translateZ(60px); }
            .face-top { transform: rotateX(90deg) translateZ(60px); }
            .face-bottom { transform: rotateX(-90deg) translateZ(60px); }
            
            .greeting-name {
                font-size: 2rem;
            }
            
            .feature-cards {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Mental Health Chatbot</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form id="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="bi bi-envelope"></i> Email
                    </label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock"></i> Password
                    </label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <span class="password-toggle" id="togglePassword">
                            <i class="bi bi-eye-slash fs-5" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <div class="text-center mt-3">
                <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                <p><a href="forgot-password.php">Forgot your password?</a></p>
            </div>
        </div>
    </div>

    <footer class="footer mt-5">
        <div class="container text-center">
            <p>&copy; 2024 Mental Health Chatbot. All rights reserved.</p>
        </div>
    </footer>

    <!-- All-new welcome animation overlay -->
    <div id="welcomeOverlay" class="welcome-overlay">
        <!-- Background shapes -->
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        
        <div class="glass-container">
            <div class="welcome-left">
                <!-- 3D Rotating Cube -->
                <div class="rotating-cube">
                    <div class="cube-face face-front">
                        <i class="bi bi-chat-heart cube-icon"></i>
                    </div>
                    <div class="cube-face face-back">
                        <i class="bi bi-heart-pulse cube-icon"></i>
                    </div>
                    <div class="cube-face face-right">
                        <i class="bi bi-person-hearts cube-icon"></i>
                    </div>
                    <div class="cube-face face-left">
                        <i class="bi bi-journal-medical cube-icon"></i>
                    </div>
                    <div class="cube-face face-top">
                        <i class="bi bi-chat-dots cube-icon"></i>
                    </div>
                    <div class="cube-face face-bottom">
                        <i class="bi bi-brightness-high cube-icon"></i>
                    </div>
                </div>
                
                <div class="brand-title">Mental Health Chatbot</div>
                <div class="brand-subtitle">Your personal companion for mental wellness and support</div>
            </div>
            
            <div class="welcome-right">
                <div class="greeting-row">
                    <div>
                        <div class="greeting-text">Welcome back,</div>
                        <div class="greeting-name" id="welcomeUsername"></div>
                    </div>
                    <div class="time-badge">
                        <i class="bi bi-clock"></i>
                        <span class="time-text" id="current-time">Loading...</span>
                    </div>
                </div>
                
                <div class="welcome-right-content">
                    <div class="welcome-message">
                        We're glad to see you again. Your mental wellness journey continues with personalized support and resources.
                    </div>
                    
                    <div class="feature-cards">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-chat-text"></i>
                            </div>
                            <h4 class="feature-title">Continue Your Conversations</h4>
                            <p class="feature-text">Pick up where you left off with your personal chatbot companion</p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="bi bi-journal-check"></i>
                            </div>
                            <h4 class="feature-title">Track Your Progress</h4>
                            <p class="feature-text">Continue monitoring your wellness journey with intuitive tools</p>
                        </div>
                    </div>
                </div>
                
                <div class="welcome-actions">
                    <button id="enterButton" class="btn-continue pulse-animation">
                        Continue to Dashboard <i class="bi bi-arrow-right btn-icon"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Check if login was successful
        <?php if (isset($_SESSION['username'])): ?>
        // Trigger welcome animation when user is logged in
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Login successful, showing welcome animation");
            const welcomeOverlay = document.getElementById('welcomeOverlay');
            const welcomeUsername = document.getElementById('welcomeUsername');
            const enterButton = document.getElementById('enterButton');
            const currentTimeElement = document.getElementById('current-time');
            
            // Set the username
            if (welcomeUsername) {
                welcomeUsername.textContent = "<?php echo $_SESSION['username']; ?>";
            }
            
            // Set the current time
            function updateTime() {
                if (!currentTimeElement) return;
                
                const now = new Date();
                let hours = now.getHours();
                const minutes = now.getMinutes();
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12; // the hour '0' should be '12'
                const minutesStr = minutes < 10 ? '0' + minutes : minutes;
                const timeStr = hours + ':' + minutesStr + ' ' + ampm;
                currentTimeElement.textContent = timeStr;
            }
            
            updateTime();
            setInterval(updateTime, 10000); // Update every 10 seconds
            
            // Check if overlay exists
            if (!welcomeOverlay) {
                console.log("Welcome overlay not found, redirecting immediately");
                window.location.href = 'index.php';
                return;
            }
            
            // Handle enter button click
            if (enterButton) {
                enterButton.addEventListener('click', function() {
                    welcomeOverlay.classList.remove('active');
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 600);
                });
            }
            
            // Show the overlay
            welcomeOverlay.classList.add('active');
            
            // After a few seconds, remove the pulse animation from the button
            setTimeout(function() {
                if (enterButton) {
                    enterButton.classList.remove('pulse-animation');
                }
            }, 4000);
            
            // Auto-redirect after animation (optional)
            setTimeout(function() {
                if (document.visibilityState !== 'hidden') {
                    if (enterButton) {
                        enterButton.click();
                    } else {
                        window.location.href = 'index.php';
                    }
                }
            }, 8000);
        });
        <?php else: ?>
        // If no user is logged in, just initialize any needed UI elements
        document.addEventListener('DOMContentLoaded', function() {
            console.log("No user logged in yet");
            // Initialize password toggle
            const togglePassword = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (togglePassword && passwordField && toggleIcon) {
                togglePassword.addEventListener('click', function() {
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        toggleIcon.classList.remove('bi-eye-slash');
                        toggleIcon.classList.add('bi-eye');
                    } else {
                        passwordField.type = 'password';
                        toggleIcon.classList.remove('bi-eye');
                        toggleIcon.classList.add('bi-eye-slash');
                    }
                });
            }
        });
        <?php endif; ?>
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Password visibility toggle
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            togglePassword.addEventListener('click', function() {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    toggleIcon.classList.remove('bi-eye-slash');
                    toggleIcon.classList.add('bi-eye');
                } else {
                    passwordField.type = 'password';
                    toggleIcon.classList.remove('bi-eye');
                    toggleIcon.classList.add('bi-eye-slash');
                }
            });
        });
    </script>
</body>
</html> 
