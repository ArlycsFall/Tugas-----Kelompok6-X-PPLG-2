document.addEventListener('DOMContentLoaded', function() {
    // Logout confirmation
    document.querySelectorAll('.logout-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin logout?')) {
                e.preventDefault();
            }
        });
    });

    // Profile dropdown functionality
    const profileDropdown = document.getElementById('profileDropdown');
    
    window.toggleDropdown = function() {
        profileDropdown.classList.toggle("show");
    }
    
    // Close dropdown when clicking outside
    window.addEventListener('click', function(event) {
        if (!event.target.matches('.profile') && !event.target.closest('.profile')) {
            if (profileDropdown && profileDropdown.classList.contains("show")) {
                profileDropdown.classList.remove("show");
            }
        }
    });

    // Mobile menu functionality
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    
    if (menuToggle && sidebar && overlay) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
        
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            }
        });
    }
});


//             const avatar = document.getElementById('userAvatar');
// if (!avatar.style.backgroundImage) {
//     let initials = '';
//     if (currentStudent.username) {
//         initials = currentStudent.username.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
//     }
//     avatar.textContent = initials || 404;
// }