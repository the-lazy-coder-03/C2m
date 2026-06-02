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
