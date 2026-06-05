document.addEventListener("DOMContentLoaded", function () {
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
