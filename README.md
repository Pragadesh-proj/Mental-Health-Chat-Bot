# Mental Health Chatbot

A supportive AI companion designed to assist with mental wellness through interactive conversations, mood tracking, and personalized support.

## Overview

This web-based application provides users with a safe space to express their thoughts and concerns while receiving supportive responses from an AI chatbot. The system incorporates features for mood tracking, crisis detection, and personalized mental health resources.

## Features

- **Supportive Conversations**: Engage in natural dialogue with an AI companion that responds with empathy
- **Mood Tracking**: Monitor emotional well-being through conversation analysis
- **Crisis Support**: Access immediate resources during difficult moments
- **Privacy-Focused**: Secure conversations with anonymous chat options
- **Wellness Tips**: Receive personalized suggestions based on evidence-based techniques
- **Conversation History**: Review past interactions to track progress

## Technical Stack

- **Frontend**: HTML, CSS, JavaScript, Bootstrap 5
- **Backend**: PHP
- **Database**: MySQL
- **APIs**: Flask-based API for NLP processing
- **Additional Libraries**: NLTK for natural language processing

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Python 3.8 or higher (for API components)
- Web server (Apache/Nginx)
- Composer (PHP package manager)
- pip (Python package manager)

### Detailed Setup Instructions

#### 1. Environment Setup

1. **Web Server Configuration**:
   - For Apache: Enable mod_rewrite module and set AllowOverride to All in your virtual host configuration
   - For Nginx: Configure proper URL rewriting rules for PHP processing

2. **PHP Configuration**:
   - Ensure these PHP extensions are enabled: mysqli, pdo_mysql, curl, mbstring, xml
   - Set appropriate memory_limit (at least 128M recommended)
   - Increase max_execution_time to 60 seconds or more
   - In php.ini, set display_errors = Off for production

#### 2. Project Installation

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/your-username/mental-health-chatbot.git
   cd mental-health-chatbot
   ```

2. **Database Setup**:
   - Create a new MySQL database:
     ```sql
     CREATE DATABASE mental_health_chatbot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     CREATE USER 'chatbot_user'@'localhost' IDENTIFIED BY 'your_secure_password';
     GRANT ALL PRIVILEGES ON mental_health_chatbot.* TO 'chatbot_user'@'localhost';
     FLUSH PRIVILEGES;
     ```
   - Import the database schema:
     ```bash
     mysql -u chatbot_user -p mental_health_chatbot < mental_health_chatbot.sql
     ```

3. **Configuration Files**:
   - Copy the sample configuration files:
     ```bash
     cp config/database.sample.php config/database.php
     cp config/app.sample.php config/app.php
     ```
   - Edit `config/database.php` with your database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'chatbot_user');
     define('DB_PASS', 'your_secure_password');
     define('DB_NAME', 'mental_health_chatbot');
     ```
   - Edit `config/app.php` to configure application settings like API keys and paths

4. **PHP Dependencies**:
   ```bash
   composer install
   ```

5. **Python Environment Setup**:
   - Create a virtual environment (recommended):
     ```bash
     python -m venv venv
     source venv/bin/activate  # For Linux/Mac
     venv\Scripts\activate     # For Windows
     ```
   - Install Python dependencies:
     ```bash
     pip install -r requirements.txt
     ```
   - Download NLTK data:
     ```python
     python -c "import nltk; nltk.download('punkt'); nltk.download('stopwords'); nltk.download('wordnet')"
     ```

6. **Initialize Database Structure**:
   - Run the database setup script:
     ```bash
     php setup_db.php
     ```
   - Configure context tables for enhanced conversation capabilities:
     ```bash
     php setup_context_tables.php
     ```

7. **Directory Permissions**:
   - Set proper permissions for writable directories:
     ```bash
     chmod -R 755 .
     chmod -R 777 uploads/
     chmod -R 777 logs/
     ```

8. **API Service Configuration**:
   - Configure the Flask API service to start automatically:
     - For Linux, create a systemd service or use supervisor
     - For Windows, create a scheduled task or service
   - Ensure the API service runs on the port specified in your config file

9. **Web Server URL Configuration**:
   - For development: Access via http://localhost/mental-health-chatbot
   - For production: Configure a proper domain with HTTPS

#### 3. Post-Installation Verification

1. **Test Database Connection**:
   - Navigate to your installation URL
   - If you see database connection errors, verify your database settings

2. **Test API Services**:
   - Visit the examples.php page to verify API connectivity
   - Check logs/api_access.log for any errors

3. **Security Checks**:
   - Verify that config/ directory is properly protected
   - Ensure logs/ directory is not publicly accessible
   - Run a security scan on your installation

4. **Create Admin Account**:
   - Navigate to signup.php and create the first user account
   - This account can be upgraded to admin via database:
     ```sql
     UPDATE users SET is_admin = 1 WHERE email = 'your_email@example.com';
     ```

## Usage

1. Navigate to the application URL in your web browser
2. Create an account or log in
3. Start a conversation with the chatbot from the main dashboard
4. Access features like mood tracking, motivation generator, and crisis resources

## Project Structure

- `index.php` - Main landing page
- `chat.php` - Primary chatbot interface
- `profile.php` - User profile management
- `api/` - Backend API endpoints for NLP processing
- `includes/` - Reusable components and utilities
- `assets/` - Static resources (CSS, JS, images)
- `components/` - UI components
- `admin/` - Administrator dashboard

## Security Features

- Password hashing and secure authentication
- Session management
- Data encryption for sensitive information
- Privacy controls for user data

## Contributing

Contributions to improve the Mental Health Chatbot are welcome. Please follow these steps:

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Open a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Disclaimer

This application is not a substitute for professional mental health care. If you're experiencing a mental health crisis, please contact a mental health professional or crisis hotline.
