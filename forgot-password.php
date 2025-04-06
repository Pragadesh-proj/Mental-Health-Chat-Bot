<?php
session_start();
require_once 'config/database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$status = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = "Please enter your email address.";
        $status = "danger";
    } else {
        // Check if email exists in database
        $stmt = mysqli_prepare($conn, "SELECT id, username FROM users WHERE email = ?");
        if ($stmt === false) {
            $message = "An error occurred preparing the statement. Please try again later.";
            $status = "danger";
            error_log("Statement preparation error: " . mysqli_error($conn));
        } else {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (!mysqli_stmt_execute($stmt)) {
                $message = "An error occurred executing the query. Please try again later.";
                $status = "danger";
                error_log("Statement execution error: " . mysqli_stmt_error($stmt));
            } else {
                $result = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($result) > 0) {
                    $user = mysqli_fetch_assoc($result);
                    // For development/testing only - in production use email
                    $resetCode = generateTemporaryResetCode($user['id']);
                    
                    if ($resetCode !== false) {
                        $message = "Your password reset code is: <strong>$resetCode</strong><br>Use this code on the reset page within 1 hour.";
                        $status = "success";
                        // Add a link to the reset page
                        $resetUrl = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/reset-password.php";
                        $message .= "<br><a href='$resetUrl' class='btn btn-primary btn-sm mt-2'>Go to Reset Page</a>";
                    } else {
                        $message = "An error occurred while generating the reset code. Please try again later.";
                        $status = "danger";
                    }
                } else {
                    $message = "No user found with this email address.";
                    $status = "danger";
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
} 

// Function to generate a secure reset token - with better error handling
function generateResetLink($userId) {
    try {
        $token = bin2hex(random_bytes(32)); // Generate a secure random token
        global $conn;
        
        // Check if connection is still valid
        if (!$conn || mysqli_connect_errno()) {
            error_log("Database connection lost: " . mysqli_connect_error());
            return false;
        }
        
        // Check if password_reset table exists
        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'password_reset'");
        if (mysqli_num_rows($table_check) == 0) {
            // Table doesn't exist, create it
            $create_table = "CREATE TABLE password_reset (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id),
                INDEX (token)
            )";
            
            if (!mysqli_query($conn, $create_table)) {
                error_log("Error creating password_reset table: " . mysqli_error($conn));
                return false;
            }
        }
        
        // Delete any existing tokens for this user
        $stmt = mysqli_prepare($conn, "DELETE FROM password_reset WHERE user_id = ?");
        if ($stmt === false) {
            error_log("Error preparing DELETE statement: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "i", $userId);
        $delete_result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        if (!$delete_result) {
            error_log("Error executing DELETE statement: " . mysqli_error($conn));
            return false;
        }
        
        // Set expiration time (1 hour from now)
        $expires = date('Y-m-d H:i:s', time() + 3600);
        
        // Store the token in the database
        $stmt = mysqli_prepare($conn, "INSERT INTO password_reset (user_id, token, expires) VALUES (?, ?, ?)");
        if ($stmt === false) {
            error_log("Error preparing INSERT statement: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "iss", $userId, $token, $expires);
        $insert_result = mysqli_stmt_execute($stmt);
        
        if (!$insert_result) {
            error_log("Error executing INSERT statement: " . mysqli_error($conn));
            mysqli_stmt_close($stmt);
            return false;
        }
        
        mysqli_stmt_close($stmt);
        
        // Return the reset link with full absolute URL
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $path = "/reset-password.php";
        return $protocol . "://" . $host . $path . "?token=" . $token;
        
    } catch (Exception $e) {
        error_log("Exception in generateResetLink: " . $e->getMessage());
        return false;
    }
}

// Function to send the reset email
function sendResetEmail($email, $resetLink) {
    // First, download PHPMailer if it doesn't exist
    $phpmailer_dir = 'phpmailer';
    if (!file_exists($phpmailer_dir)) {
        mkdir($phpmailer_dir, 0777, true);
        
        // Download PHPMailer files
        $files = [
            'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php',
            'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
            'SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php'
        ];
        
        foreach ($files as $filename => $url) {
            $content = file_get_contents($url);
            if ($content !== false) {
                file_put_contents("$phpmailer_dir/$filename", $content);
            }
        }
    }
    
    // Now include the PHPMailer files
    if (file_exists("$phpmailer_dir/PHPMailer.php")) {
        require "$phpmailer_dir/Exception.php";
        require "$phpmailer_dir/PHPMailer.php";
        require "$phpmailer_dir/SMTP.php";
        
        // Create a new PHPMailer instance
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'myselfipragadesh@gmail.com';
            $mail->Password   = 'eqei whhe gufq xzmo'; // Your app password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Debugging information (comment out in production)
            $mail->SMTPDebug = 2; // Enable verbose debug output
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer [$level] : $str");
            };
            
            // Recipients
            $mail->setFrom('myselfipragadesh@gmail.com', 'Mental Health Chatbot');
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "Password Reset Request";
            $mail->Body    = generateEmailBody($resetLink);
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], generateEmailBody($resetLink, false)));
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    } else {
        // If still can't load PHPMailer, log the error
        error_log("Failed to load PHPMailer. Check your file permissions and internet connection.");
        
        // Create and save the email to a file instead of sending it
        $email_content = generateEmailBody($resetLink);
        $filename = 'reset_email_' . time() . '.html';
        file_put_contents($filename, $email_content);
        
        error_log("Reset email saved to file: $filename");
        return false;
    }
}

// Helper function to generate email body
function generateEmailBody($resetLink, $html = true) {
    if ($html) {
        return "
            <html>
            <head>
                <title>Password Reset</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .btn { display: inline-block; padding: 10px 20px; background-color: #5469d4; color: white; 
                           text-decoration: none; border-radius: 5px; font-weight: bold; }
                    .footer { margin-top: 30px; font-size: 12px; color: #777; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Password Reset Request</h2>
                    <p>You recently requested to reset your password.</p>
                    <p>Please click the button below to reset your password:</p>
                    <p><a href='$resetLink' class='btn'>Reset Password</a></p>
                    <p>Or copy and paste this link in your browser:</p>
                    <p>$resetLink</p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you did not request a password reset, please ignore this email.</p>
                    <div class='footer'>
                        <p>This is an automated email, please do not reply.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    } else {
        return "
            Password Reset Request
            
            You recently requested to reset your password.
            
            Please go to this link to reset your password: $resetLink
            
            This link will expire in 1 hour.
            
            If you did not request a password reset, please ignore this email.
            
            This is an automated email, please do not reply.
        ";
    }
}

// Add this function as an alternative
function generateTemporaryResetCode($userId) {
    try {
        $resetCode = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)); // Generate 8-character code
        global $conn;
        
        // Check if connection is still valid
        if (!$conn || mysqli_connect_errno()) {
            error_log("Database connection lost: " . mysqli_connect_error());
            return false;
        }
        
        // Check if password_reset table exists
        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'password_reset'");
        if (mysqli_num_rows($table_check) == 0) {
            // Table doesn't exist, create it
            $create_table = "CREATE TABLE password_reset (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL,
                token VARCHAR(64) NOT NULL,
                expires DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id),
                INDEX (token)
            )";
            
            if (!mysqli_query($conn, $create_table)) {
                error_log("Error creating password_reset table: " . mysqli_error($conn));
                return false;
            }
            error_log("Created password_reset table successfully");
        }
        
        // Store the code in the database
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry
        
        // Delete any existing codes
        $stmt = mysqli_prepare($conn, "DELETE FROM password_reset WHERE user_id = ?");
        if ($stmt === false) {
            error_log("Error preparing DELETE statement: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Insert new code
        $stmt = mysqli_prepare($conn, "INSERT INTO password_reset (user_id, token, expires) VALUES (?, ?, ?)");
        if ($stmt === false) {
            error_log("Error preparing INSERT statement: " . mysqli_error($conn));
            return false;
        }
        
        mysqli_stmt_bind_param($stmt, "iss", $userId, $resetCode, $expires);
        $result = mysqli_stmt_execute($stmt);
        
        if (!$result) {
            error_log("Error executing INSERT statement: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
        
        mysqli_stmt_close($stmt);
        
        return $resetCode;
    } catch (Exception $e) {
        error_log("Error generating reset code: " . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
            color: var(--light-text);
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border-color);
        }
        
        .divider::before {
            margin-right: 12px;
        }
        
        .divider::after {
            margin-left: 12px;
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
                        <i class="fas fa-key"></i>
                    </div>
                    <h1 class="auth-title">Forgot your password?</h1>
                    <p class="auth-subtitle">Enter your email and we'll send you instructions to reset your password</p>
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
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="resetForm">
                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                            <label for="email"><i class="far fa-envelope me-2"></i>Email address</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            Send Reset Link <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                        
                        <div class="divider">or</div>
                        
                        <div class="auth-footer">
                            <a href="login.php" class="auth-link">
                                <i class="fas fa-arrow-left me-2"></i>Back to Login
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <small class="text-muted">
                    Don't have an account? <a href="register.php" class="auth-link">Sign up</a>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Simple Footer -->
    <footer class="site-footer">
        <div class="copyright">© <?php echo date('Y'); ?> Your Company. All rights reserved.</div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('resetForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                // Show loading state
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
                submitBtn.disabled = true;
                
                // Form will submit normally - this just provides feedback
                // The form submits to the same page and PHP handles the logic
            });
        }
    });
    </script>
</body>
</html> 