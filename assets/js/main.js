/**
 * OBU Student Marketplace - Main JavaScript
 * Themes: Light, Bright, Dark
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Theme Management
    initThemeManager();

    // 2. Auto-dismiss Flash Alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            if (window.bootstrap && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 400);
            }
        }, 5000);
    });

    // 3. Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});

/**
 * Handle Theme switching across Light, Bright, and Dark modes
 */
function initThemeManager() {
    // Check localStorage, cookie, or HTML data-theme
    const savedTheme = localStorage.getItem('obu_theme') || document.documentElement.getAttribute('data-theme') || 'bright';
    setTheme(savedTheme, false);

    // Attach listeners to theme switches
    document.querySelectorAll('[data-set-theme]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const theme = this.getAttribute('data-set-theme');
            setTheme(theme, true);
        });
    });
}

function setTheme(theme, persist) {
    if (!['light', 'bright', 'dark'].includes(theme)) {
        theme = 'bright';
    }
    document.documentElement.setAttribute('data-theme', theme);

    if (persist) {
        localStorage.setItem('obu_theme', theme);
        document.cookie = "obu_theme=" + theme + ";path=/;max-age=" + (86400 * 365);

        // Update active class on dropdown items
        document.querySelectorAll('[data-set-theme]').forEach(btn => {
            if (btn.getAttribute('data-set-theme') === theme) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Notify server asynchronously if user is logged in
        if (window.OBU_BASE_URL) {
            fetch(window.OBU_BASE_URL + '/actions/user-settings-action.php?action=set_theme&theme=' + encodeURIComponent(theme), {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).catch(() => {});
        }
    }
}
