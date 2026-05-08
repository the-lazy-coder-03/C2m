document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-password-toggle]").forEach(function (button) {
        var input = button.closest(".password-toggle-wrap").querySelector("[data-password-input]");

        if (!input) {
            return;
        }

        button.addEventListener("click", function () {
            var shouldShow = input.type === "password";

            input.type = shouldShow ? "text" : "password";
            button.textContent = shouldShow ? "Hide" : "Show";
            button.setAttribute("aria-label", shouldShow ? "Hide password" : "Show password");
        });
    });

    var imageInput = document.querySelector("[data-image-input]");
    var previewGrid = document.querySelector("[data-image-preview]");

    if (!imageInput || !previewGrid) {
        return;
    }

    imageInput.addEventListener("change", function () {
        var files = Array.from(imageInput.files || []);
        var maxImages = parseInt(imageInput.getAttribute("data-max-images") || "8", 10);
        var maxBytes = parseInt(imageInput.getAttribute("data-max-bytes") || "0", 10);

        previewGrid.innerHTML = "";

        if (files.length === 0) {
            return;
        }

        if (files.length > maxImages) {
            imageInput.value = "";
            previewGrid.innerHTML = '<div class="alert alert-warning mb-0">Please choose ' + maxImages + " photos or fewer.</div>";
            return;
        }

        files.forEach(function (file, index) {
            var card = document.createElement("div");
            card.className = "image-preview-card";

            if (maxBytes > 0 && file.size > maxBytes) {
                card.innerHTML = "<span>Image is too large</span>";
                previewGrid.appendChild(card);
                return;
            }

            if (!file.type.match(/^image\/(jpeg|png|webp)$/)) {
                card.innerHTML = "<span>Invalid image type</span>";
                previewGrid.appendChild(card);
                return;
            }

            var image = document.createElement("img");
            image.src = URL.createObjectURL(file);
            image.alt = file.name;
            image.onload = function () {
                URL.revokeObjectURL(image.src);
            };

            var label = document.createElement("span");
            label.textContent = index === 0 ? "Main photo" : "Photo " + (index + 1);

            card.appendChild(image);
            card.appendChild(label);
            previewGrid.appendChild(card);
        });
    });
});
