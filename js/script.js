document.addEventListener("DOMContentLoaded", function () {
    // Smooth scrolling for on-page navigation links.
    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
        link.addEventListener("click", function (event) {
            var targetId = this.getAttribute("href");

            if (!targetId || targetId === "#") {
                return;
            }

            var target = document.querySelector(targetId);

            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    });

    // Basic mobile navigation toggle.
    var navToggle = document.querySelector("[data-nav-toggle]");
    var navMenu = document.querySelector("[data-nav-menu]");

    if (navToggle && navMenu) {
        navToggle.addEventListener("click", function () {
            var isOpen = navMenu.classList.toggle("is-open");
            navToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
        });

        navMenu.querySelectorAll("a").forEach(function (link) {
            link.addEventListener("click", function () {
                navMenu.classList.remove("is-open");
                navToggle.setAttribute("aria-expanded", "false");
            });
        });
    }

    // Simple featured product search interaction.
    var searchInput = document.querySelector("[data-search-input]");
    var searchStatus = document.querySelector("[data-search-status]");
    var productCards = document.querySelectorAll("[data-product-card]");

    if (searchInput && searchStatus && productCards.length) {
        searchInput.addEventListener("input", function () {
            var query = searchInput.value.trim().toLowerCase();
            var visibleCount = 0;

            productCards.forEach(function (card) {
                var productName = card.getAttribute("data-product-name") || "";
                var matches = productName.indexOf(query) !== -1;

                card.classList.toggle("is-hidden", !matches);

                if (matches) {
                    visibleCount += 1;
                }
            });

            if (query === "") {
                searchStatus.textContent = "Showing " + visibleCount + " featured products ready for local deals.";
                return;
            }

            if (visibleCount === 0) {
                searchStatus.textContent = 'No featured products matched "' + query + '".';
                return;
            }

            searchStatus.textContent = "Found " + visibleCount + ' result(s) for "' + query + '".';
        });
    }

    // Small pointer-based hover motion for the main buttons.
    if (window.matchMedia("(hover: hover)").matches) {
        document.querySelectorAll(".btn-animated").forEach(function (button) {
            button.addEventListener("mousemove", function (event) {
                var rect = button.getBoundingClientRect();
                var offsetX = event.clientX - rect.left - rect.width / 2;
                var offsetY = event.clientY - rect.top - rect.height / 2;

                button.style.transform = "translate(" + offsetX * 0.04 + "px, " + offsetY * 0.04 + "px)";
            });

            button.addEventListener("mouseleave", function () {
                button.style.transform = "";
            });
        });
    }
});
