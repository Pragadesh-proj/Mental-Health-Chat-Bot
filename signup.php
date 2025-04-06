<?php
ini_set('display_errors', 1); error_reporting(E_ALL);

session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Debug: Check the table structure
        $table_info = mysqli_query($conn, "DESCRIBE users");
        if (!$table_info) {
            $error = "Error getting table structure: " . mysqli_error($conn);
        } else {
            $columns = [];
            while ($row = mysqli_fetch_assoc($table_info)) {
                $columns[] = $row['Field'];
            }
            
            // Check if username column exists
            if (!in_array('username', $columns)) {
                $error = "Table structure issue: username column not found. Available columns: " . implode(", ", $columns);
            } else {
                // Check if username exists
                $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
                if ($stmt === false) {
                    $error = "Database error (username check): " . mysqli_error($conn);
                } else {
                    mysqli_stmt_bind_param($stmt, "s", $username);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($result) > 0) {
                        $error = "Username already exists.";
                    } else {
                        // Check if email exists
                        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
                        if ($stmt === false) {
                            $error = "Database error (email check): " . mysqli_error($conn);
                        } else {
                            mysqli_stmt_bind_param($stmt, "s", $email);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            
                            if (mysqli_num_rows($result) > 0) {
                                $error = "Email already exists.";
                            } else {
                                // Hash password and insert user
                                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                
                                // Prepare SQL for user insertion - check if phone column exists
                                if (in_array('phone', $columns)) {
                                    $insertSQL = "INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)";
                                    $stmt = mysqli_prepare($conn, $insertSQL);
                                    if ($stmt === false) {
                                        $error = "Database error (prepare insert): " . mysqli_error($conn) . ". SQL: " . $insertSQL;
                                    } else {
                                        mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $phone, $hashed_password);
                                        
                                        if (mysqli_stmt_execute($stmt)) {
                                            $success = "Registration successful! Please login.";
                                        } else {
                                            $error = "Registration failed: " . mysqli_stmt_error($stmt);
                                        }
                                    }
                                } else {
                                    // Phone column doesn't exist, insert without it
                                    $insertSQL = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
                                    $stmt = mysqli_prepare($conn, $insertSQL);
                                    if ($stmt === false) {
                                        $error = "Database error (prepare insert): " . mysqli_error($conn) . ". SQL: " . $insertSQL;
                                    } else {
                                        mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashed_password);
                                        
                                        if (mysqli_stmt_execute($stmt)) {
                                            $success = "Registration successful! Please login.";
                                        } else {
                                            $error = "Registration failed: " . mysqli_stmt_error($stmt);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // If this is an AJAX request, return JSON response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        if ($error) {
            echo json_encode(['success' => false, 'message' => $error]);
        } else {
            echo json_encode(['success' => true, 'message' => $success]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Mental Health Chatbot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Updated Success Animation Styles */
        .success-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(41, 128, 185, 0.2) 0%, rgba(255, 255, 255, 0.95) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999; /* Increased z-index to ensure it's on top */
            opacity: 0;
            visibility: hidden;
            transition: all 0.5s ease;
        }
        
        .success-container.active {
            opacity: 1;
            visibility: visible;
        }
        
        .success-container.active .success-content {
            opacity: 1;
            transform: translateY(0);
        }
        
        .success-content {
            text-align: center;
            padding: 40px 60px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1),
                        0 0 40px rgba(0, 128, 255, 0.2);
            transform: translateY(30px);
            opacity: 0;
            transition: all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        }
        
        .success-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.8),
                transparent
            );
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { left: -100% }
            100% { left: 200% }
        }
        
        .success-icon {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 30px;
            animation: floatIcon 3s ease-in-out infinite;
        }
        
        .success-icon::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 160px;
            height: 160px;
            background: radial-gradient(circle, rgba(46, 213, 115, 0.2) 0%, transparent 70%);
            transform: translate(-50%, -50%);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes floatIcon {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        
        @keyframes pulse {
            0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.5; }
            50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.8; }
            100% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.5; }
        }
        
        .checkmark-circle {
            stroke-dasharray: 190;
            stroke-dashoffset: 190;
            animation: drawCheck 1s cubic-bezier(0.65, 0, 0.45, 1) forwards;
            stroke: #2ecc71;
            stroke-width: 3;
        }
        
        .checkmark {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            animation: drawCheckmark 0.5s 0.5s cubic-bezier(0.65, 0, 0.45, 1) forwards;
            stroke: #2ecc71;
            stroke-width: 3;
        }
        
        @keyframes drawCheck {
            100% { stroke-dashoffset: 0; }
        }
        
        @keyframes drawCheckmark {
            100% { stroke-dashoffset: 0; }
        }
        
        .success-stars {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }
        
        .star {
            position: absolute;
            width: 3px;
            height: 3px;
            background: #ffd700;
            border-radius: 50%;
            animation: twinkle 1s infinite;
        }
        
        @keyframes twinkle {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.3; transform: scale(0.3); }
        }
        
        .success-content h2 {
            background: linear-gradient(45deg, #2ecc71, #3498db);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(20px);
            animation: slideUpFade 0.8s 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        
        .success-content p {
            color: #34495e;
            font-size: 22px;
            line-height: 1.6;
            margin-bottom: 15px;
            opacity: 0;
            transform: translateY(20px);
            animation: slideUpFade 0.8s 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }
        
        .redirect-text {
            color: #7f8c8d;
            font-size: 18px;
            opacity: 0;
            animation: fadeInOut 2s 1s infinite;
        }
        
        @keyframes slideUpFade {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInOut {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
        
        /* Form interaction */
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            transition: box-shadow 0.3s ease;
            border: 2px solid #3498db;
        }
        
        .form-label {
            transition: color 0.3s ease;
        }
        
        /* Button hover effect */
        .btn-primary {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        /* Form container improvements */
        .form-container {
            transition: all 0.3s ease;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            background-color: #fff;
            max-width: 500px;
            margin: 40px auto;
        }
        
        .form-container:hover {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }
        
        /* Improved field animations */
        @keyframes field-focus {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .form-control:focus {
            animation: field-focus 0.3s ease;
        }
        
        /* Accessibility improvements */
        input:focus-visible {
            outline: 3px solid #0d6efd;
            outline-offset: 1px;
        }
        
        /* Footer styling */
        .footer {
            background-color: #3a506b;
            color: white;
            padding: 40px 0;
            margin-top: 50px !important;
            min-height: 120px;
            display: flex;
            align-items: center;
        }
        
        .footer p {
            margin-bottom: 0;
            color: white;
            font-size: 1.05rem;
        }
        
        /* Password field with inside icon styling */
        .password-field-container {
            position: relative;
        }
        
        .password-field-container input {
            padding-right: 40px;
        }
        
        .password-toggle-inside {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .password-toggle-inside:hover,
        .password-toggle-inside:focus {
            color: #495057;
            outline: none;
        }
        
        .password-toggle-inside i {
            font-size: 1.1rem;
        }
        
        /* Hide scrollbar but keep scrolling functionality */
        html {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        body {
            overflow-y: scroll;
        }
        
        /* Hide scrollbar for Chrome, Safari and Opera */
        ::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }
        
        /* Make sure the page still has a smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Add these styles to ensure the animation is visible */
        .success-particles,
        .success-stars {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .success-content {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px 60px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1),
                        0 0 40px rgba(0, 128, 255, 0.2);
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        /* Ensure the SVG icon is visible */
        .success-icon svg {
            width: 100%;
            height: 100%;
            fill: none;
            stroke: #2ecc71;
            stroke-width: 3;
            filter: drop-shadow(0 0 10px rgba(46, 204, 113, 0.5));
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
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">Create Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form id="signupForm" method="post" action="signup.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone" name="phone" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-field-container">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button type="button" class="password-toggle-inside" tabindex="-1">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                    <div class="form-text">Password must be at least 8 characters long and contain at least one uppercase letter, one number, and one special character.</div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-field-container">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <button type="button" class="password-toggle-inside" tabindex="-1">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" id="signupButton" class="btn btn-primary w-100">Sign Up</button>
            </form>

            <div class="text-center mt-3">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <!-- Success Animation Container -->
    <div id="successAnimation" class="success-container">
        <div class="success-particles" id="particles"></div>
        <div class="success-stars" id="stars"></div>
        <div class="success-content">
            <div class="success-icon">
                <svg viewBox="0 0 52 52">
                    <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                    <path class="checkmark" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                </svg>
            </div>
            <h2>Account Created Successfully! 🎉</h2>
            <p>Welcome to Mental Health Chatbot! 🌟</p>
            <p class="redirect-text">✨ Taking you to login page... ✨</p>
        </div>
    </div>

    <footer class="footer mt-5">
        <div class="container text-center">
            <p>&copy; 2024 Mental Health Chatbot. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('signupForm');
        const successAnimation = document.getElementById('successAnimation');
        
        // Password visibility toggle - updated selector for inside toggle
        const passwordToggles = document.querySelectorAll('.password-toggle-inside');
        
        passwordToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                // Get the parent container and then find the input within it
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                
                // Toggle password visibility
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            });
        });
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(form);
            
            // Send AJAX request
            fetch('signup.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Clear any existing error messages
                    const existingErrors = document.querySelectorAll('.alert-danger');
                    existingErrors.forEach(error => error.remove());

                    // Get the success animation container
                    const successAnimation = document.getElementById('successAnimation');
                    const successContent = successAnimation.querySelector('.success-content');

                    // Show the success animation
                    successAnimation.classList.add('active');
                    successContent.style.opacity = '1';
                    successContent.style.transform = 'translateY(0)';

                    // Create animation effects
                    createStars();
                    createParticles();

                    // Redirect after delay
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 5000);
                } else {
                    // Handle registration failure
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-danger';
                    errorDiv.textContent = data.message;
                    
                    // Remove any existing error messages
                    const existingErrors = document.querySelectorAll('.alert-danger');
                    existingErrors.forEach(error => error.remove());
                    
                    // Insert new error message before the form
                    form.parentNode.insertBefore(errorDiv, form);
                    console.error('Registration failed:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });

    function createStars() {
        const starsContainer = document.getElementById('stars');
        const numberOfStars = 50;

        for (let i = 0; i < numberOfStars; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            
            // Random position
            star.style.left = `${Math.random() * 100}%`;
            star.style.top = `${Math.random() * 100}%`;
            
            // Random delay
            star.style.animationDelay = `${Math.random() * 2}s`;
            
            // Random size
            const size = Math.random() * 4 + 2;
            star.style.width = `${size}px`;
            star.style.height = `${size}px`;
            
            starsContainer.appendChild(star);
        }
    }

    function createParticles() {
        const particles = document.getElementById('particles');
        const colors = [
            '#4CAF50', '#45a049', '#66bb6a', '#81c784',
            '#FFD700', '#FFA500', '#98FB98', '#87CEEB'
        ];
        
        // Create glowing particles
        for (let i = 0; i < 40; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.transform = `scale(${Math.random() * 0.5 + 0.5})`;
            particle.style.setProperty('--tx', (Math.random() * 400 - 200) + 'px');
            particle.style.setProperty('--ty', (Math.random() * 400 - 200) + 'px');
            particle.style.animationDelay = Math.random() * 2 + 's';
            particles.appendChild(particle);
        }

        // Create confetti
        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.left = Math.random() * 100 + '%';
            confetti.style.animationDelay = Math.random() * 3 + 's';
            confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
            particles.appendChild(confetti);
        }
    }
    </script>
</body>
</html> 