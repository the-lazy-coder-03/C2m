(function () {
    var toggleBtn = document.querySelector('[data-action="toggle-theme"]');
    var logoutBtn = document.querySelector('[data-action="logout"]');
    var searchInput = document.querySelector('[data-role="search"]');
    var storageKey = 'admin_theme';

    function applyTheme(theme) {
        document.body.classList.toggle('theme-dark', theme === 'dark');
    }

    var savedTheme = null;
    try {
        savedTheme = localStorage.getItem(storageKey);
    } catch (e) {
        savedTheme = null;
    }
    if (savedTheme) {
        applyTheme(savedTheme);
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            var isDark = document.body.classList.contains('theme-dark');
            var nextTheme = isDark ? 'light' : 'dark';
            applyTheme(nextTheme);
            try {
                localStorage.setItem(storageKey, nextTheme);
            } catch (e) {}
        });
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            window.location.href = 'logout.php';
        });
    }

    if (searchInput) {
        document.addEventListener('keydown', function (event) {
            if (event.key === '/' && document.activeElement !== searchInput) {
                event.preventDefault();
                searchInput.focus();
            }
        });

        searchInput.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                searchInput.blur();
            }
        });
    }

    var sidebarCollapse = document.getElementById('sidebarCollapse');
    var sidebar = document.getElementById('sidebar');

    if (sidebarCollapse && sidebar) {
        sidebarCollapse.addEventListener('click', function () {
            sidebar.classList.toggle('active');
        });
    }

    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        var input = button.closest('.password-toggle-wrap').querySelector('[data-password-input]');

        if (!input) {
            return;
        }

        button.addEventListener('click', function () {
            var shouldShow = input.type === 'password';

            input.type = shouldShow ? 'text' : 'password';
            button.textContent = shouldShow ? 'Hide' : 'Show';
            button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
        });
    });
})();
