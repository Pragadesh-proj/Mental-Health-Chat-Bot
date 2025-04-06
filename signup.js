document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('signupForm');
    const successAnimation = document.getElementById('successAnimation');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = new FormData(form);
        
        // Send AJAX request
        fetch('process_signup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success animation
                successAnimation.classList.add('active');
                
                // Wait for animation to complete, then redirect
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 3000); // Redirect after 3 seconds
            } else {
                // Show error message
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    });
}); 