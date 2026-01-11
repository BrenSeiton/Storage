// Function to load module content via AJAX
function loadModule(url) {
    // Show loading overlay
    showLoadingOverlay();

    // Fetch the content
    fetch(url + (url.includes('?') ? '&' : '?') + 'ajax=1')
        .then(response => response.text())
        .then(html => {
            const mainContent = document.querySelector('.main-content');
            mainContent.innerHTML = html;
            // Re-initialize event listeners for new content
            initContentLinks();
            hideLoadingOverlay();
        })
        .catch(error => {
            const mainContent = document.querySelector('.main-content');
            mainContent.innerHTML = `
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Failed to load module. Please try again.</p>
                    <button onclick="loadModule('${url}')">Retry</button>
                </div>
            `;
            hideLoadingOverlay();
            console.error('Error loading module:', error);
        });
}

// Function to show loading overlay
function showLoadingOverlay() {
    let overlay = document.querySelector('.loading-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <i class="fas fa-spinner fa-spin"></i>
            <p>Loading module...</p>
        `;
        document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
}

// Function to hide loading overlay
function hideLoadingOverlay() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

// Function to initialize sidebar navigation
function initSidebarNavigation() {
    const navItems = document.querySelectorAll('.sidebar-nav .nav-item');

    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            if (href) {
                // Remove active class from all items
                navItems.forEach(nav => nav.classList.remove('active'));
                // Add active class to clicked item
                this.classList.add('active');
                // Load the module
                loadModule(href);
            }
        });
    });
}

// Function to initialize links within main content
function initContentLinks() {
    const mainContent = document.querySelector('.main-content');
    const links = mainContent.querySelectorAll('a[href]');

    links.forEach(link => {
        const href = link.getAttribute('href');
        // Only intercept local PHP files, not external links or logout
        if (href && href.endsWith('.php') && !href.includes('logout.php') && !href.startsWith('http')) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                loadModule(href);
            });
        }
    });

    // Handle forms
    const forms = mainContent.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            showLoadingOverlay();
            const formData = new FormData(form);
            const action = form.getAttribute('action') || window.location.href;

            fetch(action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.redirected) {
                    // If redirected, load the redirect URL
                    loadModule(response.url);
                } else {
                    return response.text();
                }
            })
            .then(html => {
                if (html) {
                    mainContent.innerHTML = html;
                    initContentLinks(); // Re-initialize for new content
                }
                hideLoadingOverlay();
            })
            .catch(error => {
                console.error('Form submission error:', error);
                mainContent.innerHTML = `
                    <div class="error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>Failed to submit form. Please try again.</p>
                        <button onclick="document.querySelector('form').submit()">Retry</button>
                    </div>
                `;
                hideLoadingOverlay();
            });
        });
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initSidebarNavigation();
    initContentLinks();
    hideLoadingOverlay(); // Ensure overlay is hidden on load
});