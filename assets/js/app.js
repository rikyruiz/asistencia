document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }

    const alertCloseButtons = document.querySelectorAll('[data-alert-close]');
    alertCloseButtons.forEach(button => {
        button.addEventListener('click', function() {
            const alert = button.closest('[data-alert]');
            if (alert) {
                alert.style.display = 'none';
            }
        });
    });

    const imageUploads = document.querySelectorAll('input[type="file"][accept*="image"]');
    imageUploads.forEach(input => {
        input.addEventListener('change', function(e) {
            const files = e.target.files;
            const preview = document.getElementById(input.dataset.preview);

            if (preview && files.length > 0) {
                preview.innerHTML = '';
                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-24 h-24 object-cover rounded-lg';
                        preview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
            }
        });
    });

    const forms = document.querySelectorAll('form[data-confirm]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const message = form.dataset.confirm || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    function formatCurrency(amount, currency = 'USD') {
        const formatter = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency,
        });
        return formatter.format(amount);
    }

    const priceInputs = document.querySelectorAll('input[data-currency]');
    priceInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = parseFloat(input.value);
            if (!isNaN(value)) {
                const formatted = formatCurrency(value, input.dataset.currency);
                const display = input.nextElementSibling;
                if (display && display.classList.contains('price-display')) {
                    display.textContent = formatted;
                }
            }
        });
    });

    window.showNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg glass-card max-w-sm animate-slide-in`;

        const typeClasses = {
            'success': 'border-green-500 text-green-700',
            'error': 'border-red-500 text-red-700',
            'warning': 'border-yellow-500 text-yellow-700',
            'info': 'border-blue-500 text-blue-700'
        };

        notification.classList.add(...(typeClasses[type] || typeClasses.info).split(' '));
        notification.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-xl">&times;</button>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    };

    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        const searchInput = searchForm.querySelector('input[name="search"]');
        let searchTimeout;

        searchInput?.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const query = searchInput.value;
                if (query.length >= 3) {
                    fetch(`/api/search?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            console.log('Search results:', data);
                        });
                }
            }, 300);
        });
    }
});