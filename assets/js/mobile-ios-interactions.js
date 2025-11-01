/**
 * iOS-Style Mobile Interactions for Marketplace
 * Following Apple's Human Interface Guidelines
 */

// Mobile menu toggle
function toggleMobileMenu() {
    const menu = document.getElementById('mobileNavMenu');
    if (menu.classList.contains('active')) {
        menu.classList.remove('active');
        setTimeout(() => {
            menu.style.display = 'none';
        }, 300);
    } else {
        menu.style.display = 'flex';
        setTimeout(() => {
            menu.classList.add('active');
        }, 10);
    }
}

// Close mobile menu when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenu = document.getElementById('mobileNavMenu');
    if (mobileMenu) {
        mobileMenu.addEventListener('click', function(e) {
            if (e.target === this) {
                toggleMobileMenu();
            }
        });
    }
});

// Pull to refresh functionality
let startY = 0;
let isPulling = false;

function initPullToRefresh() {
    if (window.innerWidth <= 768) {
        document.addEventListener('touchstart', function(e) {
            if (window.scrollY === 0) {
                startY = e.touches[0].pageY;
                isPulling = true;
            }
        });

        document.addEventListener('touchmove', function(e) {
            if (isPulling) {
                const currentY = e.touches[0].pageY;
                const diff = currentY - startY;
                const pullIndicator = document.getElementById('pullToRefresh');

                if (diff > 50 && pullIndicator) {
                    pullIndicator.classList.add('active');
                    pullIndicator.innerHTML = '↻ Release to refresh';
                }
            }
        });

        document.addEventListener('touchend', function(e) {
            if (isPulling) {
                const pullIndicator = document.getElementById('pullToRefresh');
                if (pullIndicator && pullIndicator.classList.contains('active')) {
                    pullIndicator.innerHTML = '✓ Refreshing...';
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }
                isPulling = false;
                if (pullIndicator) {
                    setTimeout(() => {
                        pullIndicator.classList.remove('active');
                    }, 2000);
                }
            }
        });
    }
}

// View switching for mobile
function switchView(view) {
    const gridView = document.getElementById('commodityGrid');
    const listView = document.getElementById('commodityList');
    const chartView = document.getElementById('commodityChart');
    const buttons = document.querySelectorAll('.segmented-control-item');

    // Reset all buttons
    buttons.forEach(btn => btn.classList.remove('active'));

    // Hide all views
    if (gridView) gridView.style.display = 'none';
    if (listView) listView.style.display = 'none';
    if (chartView) chartView.style.display = 'none';

    // Show selected view and activate button
    switch(view) {
        case 'grid':
            if (gridView) gridView.style.display = 'grid';
            if (buttons[0]) buttons[0].classList.add('active');
            break;
        case 'list':
            if (listView) listView.style.display = 'block';
            if (buttons[1]) buttons[1].classList.add('active');
            break;
        case 'chart':
            if (chartView) {
                chartView.style.display = 'block';
            } else if (gridView) {
                gridView.style.display = 'grid'; // Default to grid if chart not available
            }
            if (buttons[2]) buttons[2].classList.add('active');
            break;
    }

    // Add haptic feedback
    if (window.navigator && window.navigator.vibrate) {
        window.navigator.vibrate(10);
    }
}

// Swipe gesture support
let touchStartX = 0;
let touchStartY = 0;
let touchEndX = 0;
let touchEndY = 0;

function handleSwipe(element, leftAction, rightAction) {
    element.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    }, { passive: true });

    element.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;

        const diffX = touchEndX - touchStartX;
        const diffY = touchEndY - touchStartY;

        // Only register horizontal swipes
        if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
            if (diffX > 0 && rightAction) {
                rightAction();
            } else if (diffX < 0 && leftAction) {
                leftAction();
            }
        }
    }, { passive: true });
}

// Add haptic feedback on iOS (if supported)
function addHapticFeedback(element) {
    if (window.navigator && window.navigator.vibrate) {
        element.addEventListener('click', function() {
            window.navigator.vibrate(10);
        });
    }
}

// Enhanced table scrolling on mobile
function enhanceTableScrolling() {
    if (window.innerWidth <= 768) {
        document.querySelectorAll('table').forEach(table => {
            // Skip if already wrapped
            if (table.parentElement.classList.contains('table-scroll-wrapper')) {
                return;
            }

            // Add scroll wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'table-scroll-wrapper';
            wrapper.style.position = 'relative';
            wrapper.style.overflow = 'auto';
            wrapper.style.webkitOverflowScrolling = 'touch';

            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);

            // Add scroll shadow indicators
            wrapper.addEventListener('scroll', function() {
                if (this.scrollLeft > 0) {
                    this.style.boxShadow = 'inset 10px 0 10px -10px rgba(0,0,0,0.1)';
                } else {
                    this.style.boxShadow = 'none';
                }
            });
        });
    }
}

// iOS-style bounce effect
function addIOSBounce() {
    if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        document.body.style.cssText += '-webkit-overflow-scrolling: touch;';
    }
}

// Adjust for safe areas on iPhone X and newer
function adjustForSafeArea() {
    const viewport = window.visualViewport;
    if (viewport && window.innerWidth <= 768) {
        document.documentElement.style.setProperty('--safe-area-inset-bottom', `${viewport.height - window.innerHeight}px`);
    }
}

// Show loading overlay
function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.add('active');
    }
}

// Hide loading overlay
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('active');
    }
}

// Set date range for quick date buttons
function setDateRange(range) {
    const today = new Date();
    let startDate, endDate;

    switch(range) {
        case 'today':
            startDate = today;
            endDate = today;
            break;
        case 'week':
            endDate = today;
            startDate = new Date(today);
            startDate.setDate(startDate.getDate() - 6);
            break;
        case 'twoweeks':
            endDate = today;
            startDate = new Date(today);
            startDate.setDate(startDate.getDate() - 13);
            break;
        case 'month':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = today;
            break;
        case 'lastmonth':
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            startDate = lastMonth;
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        case 'all':
            // These should be set by the page based on available data
            const startInput = document.querySelector('input[name="start_date"]');
            const endInput = document.querySelector('input[name="end_date"]');
            if (startInput && startInput.min) startDate = new Date(startInput.min);
            if (endInput && endInput.max) endDate = new Date(endInput.max);
            break;
    }

    // Format dates as YYYY-MM-DD
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    // Set the date inputs
    const startInput = document.querySelector('input[name="start_date"]');
    const endInput = document.querySelector('input[name="end_date"]');

    if (startInput && startDate) startInput.value = formatDate(startDate);
    if (endInput && endDate) endInput.value = formatDate(endDate);

    // Submit the form
    const form = document.getElementById('filterForm');
    if (form) {
        showLoading();
        form.submit();
    }
}

// Initialize all mobile features
function initMobileFeatures() {
    // Initialize pull to refresh
    initPullToRefresh();

    // Add iOS bounce effect
    addIOSBounce();

    // Enhance table scrolling
    enhanceTableScrolling();

    // Add haptic feedback to interactive elements
    document.querySelectorAll('button, .mobile-tab, .commodity-card, .quick-date-btn, .ios-list-item').forEach(addHapticFeedback);

    // Add swipe gestures to cards
    if (window.innerWidth <= 768) {
        document.querySelectorAll('.commodity-card, .ios-list-item').forEach(card => {
            handleSwipe(card, null, null); // Can add actions later
        });
    }

    // Adjust for safe areas
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', adjustForSafeArea);
        adjustForSafeArea();
    }

    // Prevent zooming on form inputs on iOS
    document.addEventListener('touchstart', function(event) {
        if (event.touches.length > 1) {
            event.preventDefault();
        }
    }, { passive: false });

    // Add smooth scrolling for iOS
    if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        document.body.style.webkitOverflowScrolling = 'touch';
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileFeatures);
} else {
    initMobileFeatures();
}