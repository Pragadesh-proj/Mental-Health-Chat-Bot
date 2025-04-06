<div class="admin-sidebar">
    <div class="logo">
        <h2>Admin Panel</h2>
    </div>
    <ul class="nav-links">
        <li>
            <a href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="users.php">
                <i class="fas fa-users"></i>
                <span>Users Management</span>
            </a>
        </li>
        <li>
            <a href="feedback.php">
                <i class="fas fa-comments"></i>
                <span>Feedback</span>
            </a>
        </li>
        <li>
            <a href="reports.php" class="active">
                <i class="fas fa-chart-bar"></i>
                <span>Analytics & Reports</span>
            </a>
        </li>
    </ul>
    <div class="logout">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<style>
    /* Update/add these styles in your existing CSS */
    .admin-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 250px;
        height: 100vh;
        background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 20px 0;
        box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        display: flex;
        flex-direction: column;
    }

    .nav-links {
        flex: 1;
        overflow-y: auto;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .nav-links::-webkit-scrollbar {
        width: 5px;
    }

    .nav-links::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 5px;
    }

    .main-content {
        margin-left: 250px;
        padding: 30px;
        min-height: 100vh;
        background-color: var(--bg-light);
        transition: margin-left 0.3s ease;
    }

    /* Update mobile responsive styles */
    @media (max-width: 768px) {
        .admin-sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .admin-sidebar.active {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
            padding: 20px;
        }

        /* Add hamburger menu button for mobile */
        .menu-toggle {
            display: block;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
    }
</style>

<!-- Add this JavaScript just before the closing </body> tag -->
<script>
    // Add mobile menu toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.createElement('div');
        menuToggle.className = 'menu-toggle d-md-none';
        menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(menuToggle);

        const sidebar = document.querySelector('.admin-sidebar');
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    });
</script> 