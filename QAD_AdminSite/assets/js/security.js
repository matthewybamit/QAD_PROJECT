// assets/js/security.js

document.addEventListener("DOMContentLoaded", () => {
    // --- Confirmations ---
    document.querySelectorAll("form[onsubmit]").forEach(form => {
        form.addEventListener("submit", (e) => {
            const message = form.getAttribute("onsubmit").replace("return", "").trim();
            if (!confirm(message.replace("()", "").replace(";", ""))) {
                e.preventDefault();
            }
        });
    });

    // --- Success/Error auto-hide ---
    setTimeout(() => {
        document.querySelectorAll(".bg-green-50, .bg-red-50").forEach(msg => {
            msg.classList.add("opacity-0", "transition", "duration-500");
            setTimeout(() => msg.remove(), 500);
        });
    }, 4000);

    // --- (Optional) Future AJAX Hook ---
    // Example for async session termination
    document.querySelectorAll("form[action][data-ajax]").forEach(form => {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const res = await fetch(form.action || window.location.href, {
                method: "POST",
                body: formData,
            });
            const html = await res.text();
            document.open();
            document.write(html);
            document.close();
        });
    });
});