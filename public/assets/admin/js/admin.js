(function () {
    var logoutBtn = document.querySelector('[data-action="logout"]');

    if (logoutBtn) {
        logoutBtn.addEventListener('click', function () {
            window.location.href = '/admin/logout';
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

    var listingStatus = document.querySelector('[data-role="listing-status"]');
    var listingQuantity = document.querySelector('[data-role="listing-quantity"]');
    var listingActive = document.querySelector('[data-role="listing-active"]');

    if (listingStatus && listingQuantity && listingActive) {
        listingStatus.addEventListener('change', function () {
            if (listingStatus.value !== 'active') {
                return;
            }

            listingActive.checked = true;

            if (Number(listingQuantity.value) < 1) {
                listingQuantity.value = '1';
            }
        });
    }

    var confirmModalElement = document.getElementById('adminConfirmModal');
    var confirmSubmit = document.getElementById('adminConfirmSubmit');
    var confirmTitle = document.getElementById('adminConfirmTitle');
    var confirmMessage = document.getElementById('adminConfirmMessage');
    var pendingConfirmForm = null;

    if (confirmModalElement && confirmSubmit && window.bootstrap) {
        var confirmModal = new window.bootstrap.Modal(confirmModalElement);

        document.querySelectorAll('[data-confirm-delete]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                pendingConfirmForm = form;

                if (confirmTitle) {
                    confirmTitle.textContent = form.getAttribute('data-confirm-title') || 'Delete listing?';
                }

                if (confirmMessage) {
                    confirmMessage.textContent = form.getAttribute('data-confirm-message') || 'This action cannot be undone.';
                }

                var label = form.getAttribute('data-confirm-label') || 'Delete';
                var labelElement = confirmSubmit.querySelector('span');

                if (labelElement) {
                    labelElement.textContent = label;
                }

                confirmModal.show();
            });
        });

        confirmSubmit.addEventListener('click', function () {
            if (!pendingConfirmForm) {
                return;
            }

            var form = pendingConfirmForm;
            pendingConfirmForm = null;
            confirmModal.hide();
            form.submit();
        });

        confirmModalElement.addEventListener('hidden.bs.modal', function () {
            pendingConfirmForm = null;
        });
    }
})();
