<?php
session_start();
require_once 'config/database.php';

$message = '';
$status = '';
$token = '';
$valid_token = false;
$user_id = null;

// Check if token is present in URL
if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    // Validate token
    $stmt = mysqli_prepare($conn, "SELECT user_id, expires FROM password_reset WHERE token = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $token_data = mysqli_fetch_assoc($result);
            $current_time = date('Y-m-d H:i:s');
            
            if ($token_data['expires'] > $current_time) {
                $valid_token = true;
                $user_id = $token_data['user_id'];
            } else {
                $message = "This reset link has expired. Please request a new one.";
                $status = "danger";
            }
        } else {
            $message = "Invalid reset token. Please request a new password reset link.";
            $status = "danger";
        }
        mysqli_stmt_close($stmt);
    }
}

// Process form submission to reset password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $reset_code = isset($_POST['reset_code']) ? trim($_POST['reset_code']) : '';
    
    // Validate password and confirm password
    if (empty($password) || empty($confirm_password)) {
        $message = "Please enter and confirm your new password.";
        $status = "danger";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
        $status = "danger";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $status = "danger";
    } else {
        // Verify token/code
        if (!empty($token)) {
            // Use token from the URL
            $stmt = mysqli_prepare($conn, "SELECT user_id, expires FROM password_reset WHERE token = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $token);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    $token_data = mysqli_fetch_assoc($result);
                    $current_time = date('Y-m-d H:i:s');
                    
                    if ($token_data['expires'] > $current_time) {
                        $user_id = $token_data['user_id'];
                        $valid_token = true;
                    } else {
                        $message = "This reset link has expired. Please request a new one.";
                        $status = "danger";
                    }
                } else {
                    $message = "Invalid reset token. Please request a new password reset link.";
                    $status = "danger";
                }
                mysqli_stmt_close($stmt);
            }
        } elseif (!empty($reset_code)) {
            // Use manually entered reset code
            $stmt = mysqli_prepare($conn, "SELECT user_id, expires FROM password_reset WHERE token = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $reset_code);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    $token_data = mysqli_fetch_assoc($result);
                    $current_time = date('Y-m-d H:i:s');
                    
                    if ($token_data['expires'] > $current_time) {
                        $user_id = $token_data['user_id'];
                        $valid_token = true;
                    } else {
                        $message = "This reset code has expired. Please request a new one.";
                        $status = "danger";
                    }
                } else {
                    $message = "Invalid reset code. Please check and try again.";
                    $status = "danger";
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $message = "No reset token or code provided.";
            $status = "danger";
        }
        
        // If token is valid, update the password
        if ($valid_token && $user_id) {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update user's password
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
                $update_result = mysqli_stmt_execute($stmt);
                
                if ($update_result) {
                    // Delete used token
                    $delete_stmt = mysqli_prepare($conn, "DELETE FROM password_reset WHERE user_id = ?");
                    if ($delete_stmt) {
                        mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
                        mysqli_stmt_execute($delete_stmt);
                        mysqli_stmt_close($delete_stmt);
                    }
                    
                    $message = "Your password has been successfully reset. You can now <a href='login.php'>login</a> with your new password.";
                    $status = "success";
                    $valid_token = false; // Hide the form after successful reset
                } else {
                    $message = "An error occurred while updating your password. Please try again.";
                    $status = "danger";
                }
                mysqli_stmt_close($stmt);
            } else {
                $message = "An error occurred while processing your request. Please try again.";
                $status = "danger";
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
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #5469d4;
            --primary-hover: #4054b3;
            --secondary-color: #f7fafc;
            --text-color: #1a202c;
            --light-text: #718096;
            --border-color: #e2e8f0;
            --footer-bg: #f8fafc;
        }
        
        /* Enhanced scrollbar hiding for all browsers */
        html, body {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
            overflow: hidden; /* Prevent any scrolling */
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        html::-webkit-scrollbar, body::-webkit-scrollbar, 
        div::-webkit-scrollbar, *::-webkit-scrollbar {
            width: 0 !important;
            height: 0 !important;
            display: none !important; /* Chrome/Safari/Opera */
            background: transparent !important;
        }
        
        * {
            -ms-overflow-style: none !important;
            scrollbar-width: none !important;
        }
        
        .main-content {
            height: calc(100% - 80px); /* Updated to account for taller footer */
            overflow-y: auto;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        .main-content::-webkit-scrollbar {
            display: none;
        }
        
        body {
            background: linear-gradient(135deg, #f6f9fc 0%, #eef2f7 100%);
            color: var(--text-color);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            display: flex;
            flex-direction: column;
        }
        
        .auth-container {
            max-width: 480px;
            width: 100%;
            padding: 0 24px;
            margin: 0 auto;
        }
        
        @media (max-width: 576px) {
            .auth-container {
                max-width: 100%;
                padding: 0 16px;
            }
            
            .auth-header, .auth-body {
                padding-left: 20px;
                padding-right: 20px;
            }
        }
        
        @media (min-width: 1400px) {
            .auth-container {
                max-width: 520px;
            }
        }
        
        .auth-card {
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: none;
            width: 100%;
        }
        
        .auth-header {
            padding: 32px 32px 0;
        }
        
        .auth-logo {
            margin-bottom: 24px;
            display: flex;
            justify-content: center;
        }
        
        .auth-logo img {
            height: 40px;
        }
        
        .auth-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
            color: var(--text-color);
        }
        
        .auth-subtitle {
            font-size: 16px;
            color: var(--light-text);
            text-align: center;
            margin-bottom: 24px;
        }
        
        .auth-body {
            padding: 24px 32px 32px;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-floating > .form-control {
            padding: 16px 16px;
            height: 60px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
        }
        
        .form-floating > label {
            padding: 16px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(84, 105, 212, 0.15);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 600;
            height: 48px;
            width: 100%;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 24px;
        }
        
        .auth-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .auth-link:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 12px;
            padding: 16px;
            border: none;
            margin-bottom: 24px;
        }
        
        .alert-success {
            background-color: #f0fff4;
            color: #2f855a;
        }
        
        .alert-danger {
            background-color: #fff5f5;
            color: #c53030;
        }
        
        .icon-box {
            width: 80px;
            height: 80px;
            background-color: rgba(84, 105, 212, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        
        .icon-box i {
            font-size: 32px;
            color: var(--primary-color);
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--light-text);
            z-index: 10;
        }
        
        /* Footer styles - update the height and padding */
        .site-footer {
            background-color: #2b3a4a; /* Dark blue/slate color as shown in the image */
            color: #ffffff;
            text-align: center;
            padding: 30px 0; /* Increased padding */
            font-size: 14px;
            flex-shrink: 0;
            width: 100%;
            margin-top: auto;
            height: 80px; /* Added explicit height */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .copyright {
            margin-bottom: 0;
            font-weight: 400;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">
                        <!-- Replace with your actual logo -->
                    </div>
                    <div class="icon-box">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h1 class="auth-title">Reset Your Password</h1>
                    <p class="auth-subtitle">Create a new password for your account</p>
                </div>
                
                <div class="auth-body">
                    <?php if(!empty($message)): ?>
                        <div class="alert alert-<?php echo $status; ?>" role="alert">
                            <?php if($status == "success"): ?>
                                <i class="fas fa-check-circle me-2"></i>
                            <?php else: ?>
                                <i class="fas fa-exclamation-circle me-2"></i>
                            <?php endif; ?>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!$valid_token && empty($token)): ?>
                    <!-- No token in URL, show code entry form -->
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="reset_code" name="reset_code" placeholder="Enter reset code" required>
                            <label for="reset_code"><i class="fas fa-key me-2"></i>Reset Code</label>
                        </div>
                        
                        <div class="form-floating position-relative">
                            <input type="password" class="form-control" id="password" name="password" placeholder="New password" required>
                            <label for="password"><i class="fas fa-lock me-2"></i>New Password</label>
                            <span class="password-toggle" onclick="togglePassword('password')">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                        
                        <div class="form-floating position-relative">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                            <label for="confirm_password"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                            <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            Reset Password <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </form>
                    <?php elseif($valid_token): ?>
                    <!-- Token is valid, show password reset form -->
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-floating position-relative">
                            <input type="password" class="form-control" id="password" name="password" placeholder="New password" required>
                            <label for="password"><i class="fas fa-lock me-2"></i>New Password</label>
                            <span class="password-toggle" onclick="togglePassword('password')">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                        
                        <div class="form-floating position-relative">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                            <label for="confirm_password"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                            <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            Reset Password <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <div class="auth-footer">
                        <a href="login.php" class="auth-link">
                            <i class="fas fa-arrow-left me-2"></i>Back to Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Simple Footer -->
    <footer class="site-footer">
        <div class="copyright">© <?php echo date('Y'); ?> Your Company. All rights reserved.</div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Add password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            // Add your password strength logic here
        });
        
        // Check password match
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password === confirmPassword) {
                this.setCustomValidity('');
            } else {
                this.setCustomValidity('Passwords do not match');
            }
        });
    </script>
</body>
</html>