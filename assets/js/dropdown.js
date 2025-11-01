/**
 * Enhanced dropdown functionality
 * Fixes hover issues with dropdowns
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get all dropdown containers
    const adminDropdown = document.querySelector('.admin-dropdown');
    const userDropdown = document.querySelector('.user-dropdown');

    // Function to handle dropdown
    function setupDropdown(dropdownElement) {
        if (!dropdownElement) return;

        const button = dropdownElement.querySelector('.admin-dropdown-btn, .user-dropdown-btn');
        const content = dropdownElement.querySelector('.admin-dropdown-content, .user-dropdown-content');

        if (!button || !content) return;

        let timeoutId;

        // Show dropdown on mouse enter
        dropdownElement.addEventListener('mouseenter', function() {
            clearTimeout(timeoutId);
            content.style.display = 'block';
        });

        // Hide dropdown on mouse leave with delay
        dropdownElement.addEventListener('mouseleave', function() {
            timeoutId = setTimeout(function() {
                content.style.display = 'none';
            }, 100); // Small delay to allow moving to dropdown content
        });

        // Keep dropdown open when hovering over content
        content.addEventListener('mouseenter', function() {
            clearTimeout(timeoutId);
            content.style.display = 'block';
        });

        content.addEventListener('mouseleave', function() {
            timeoutId = setTimeout(function() {
                content.style.display = 'none';
            }, 100);
        });

        // Toggle on click for mobile/touch devices
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const isVisible = content.style.display === 'block';

            // Close all dropdowns first
            document.querySelectorAll('.admin-dropdown-content, .user-dropdown-content').forEach(dd => {
                dd.style.display = 'none';
            });

            // Toggle current dropdown
            content.style.display = isVisible ? 'none' : 'block';
        });
    }

    // Setup dropdowns
    setupDropdown(adminDropdown);
    setupDropdown(userDropdown);

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.admin-dropdown') && !e.target.closest('.user-dropdown')) {
            document.querySelectorAll('.admin-dropdown-content, .user-dropdown-content').forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }
    });

    // Prevent dropdown from closing when clicking inside
    document.querySelectorAll('.admin-dropdown-content, .user-dropdown-content').forEach(content => {
        content.addEventListener('click', function(e) {
            if (e.target.tagName !== 'A') {
                e.stopPropagation();
            }
        });
    });
});