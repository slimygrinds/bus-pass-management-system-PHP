/**
 * Main JavaScript File
 * 
 * Contains general UI interactions: sidebar toggle, confirmation dialogs,
 * password visibility toggle, and other utility functions.
 * 
 * @project Bus Pass Management System
 * @semester BCA Semester 5
 */

// ==========================================
// DOM Ready
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    initSidebarToggle();
    initPasswordToggles();
    initConfirmDialogs();
    initAutoHideAlerts();
});

// ==========================================
// Sidebar Toggle
// ==========================================

/**
 * Initialize sidebar toggle — sidebar hidden by default, shown on hamburger click.
 * Adds/removes 'sidebar-open' on <body> to push the content wrapper.
 */
function initSidebarToggle() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');

    if (!toggleBtn || !sidebar) return;

    // Sidebar starts CLOSED — no class added on load
    function openSidebar() {
        sidebar.classList.add('show');
        document.body.classList.add('sidebar-open');
        if (overlay) overlay.classList.add('show');
    }

    function closeSidebar() {
        sidebar.classList.remove('show');
        document.body.classList.remove('sidebar-open');
        if (overlay) overlay.classList.remove('show');
    }

    toggleBtn.addEventListener('click', function (e) {
        e.preventDefault();
        sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
    });

    // Close on overlay click
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Close on Esc key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            closeSidebar();
        }
    });
}

// ==========================================
// Password Show/Hide Toggle
// ==========================================

/**
 * Initialize all password toggle buttons on the page
 */
function initPasswordToggles() {
    const toggleBtns = document.querySelectorAll('.password-toggle');
    
    toggleBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordField = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordField) {
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    passwordField.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        });
    });
}

// ==========================================
// Confirmation Dialogs
// ==========================================

/**
 * Initialize confirmation dialogs for delete/action buttons
 * Usage: Add class "confirm-action" and data-message="Your message" to button
 */
function initConfirmDialogs() {
    const confirmBtns = document.querySelectorAll('.confirm-action');
    
    confirmBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            const message = this.getAttribute('data-message') || 'Are you sure you want to perform this action?';
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// ==========================================
// Auto-Hide Alerts
// ==========================================

/**
 * Auto-hide success/info alerts after 5 seconds
 */
function initAutoHideAlerts() {
    const alerts = document.querySelectorAll('.alert-success, .alert-info');
    
    alerts.forEach(function(alert) {
        setTimeout(function() {
            // Use Bootstrap's built-in alert close
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

// ==========================================
// Search Filter for Tables
// ==========================================

/**
 * Filter table rows based on search input
 * @param {string} inputId - ID of search input
 * @param {string} tableId - ID of table to filter
 */
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// ==========================================
// Loading Spinner
// ==========================================

/**
 * Show a loading spinner on a button
 * @param {HTMLElement} button - The button element
 * @param {string} loadingText - Text to show while loading
 */
function showButtonLoading(button, loadingText) {
    button.disabled = true;
    button.setAttribute('data-original-text', button.innerHTML);
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + (loadingText || 'Loading...');
}

/**
 * Restore button to original state
 * @param {HTMLElement} button - The button element
 */
function hideButtonLoading(button) {
    button.disabled = false;
    button.innerHTML = button.getAttribute('data-original-text');
}
