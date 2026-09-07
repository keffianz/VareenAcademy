/**
 * VAREN Academy Admin - Shared Dashboard Script
 * Handles: sidebar toggle, logout button, KPI count-up animation
 */
(function () {
    "use strict";

        // --- Sidebar toggle (mobile drawer) ---
    // Handles adminSidebar, studentSidebar, and teacherSidebar
    var toggleBtn = document.getElementById("sidebarToggle");
    var closeBtn = document.getElementById("sidebarClose");
    var sidebar = document.getElementById("adminSidebar") ||
                  document.getElementById("studentSidebar") ||
                  document.getElementById("teacherSidebar") ||
                  null;

    if (sidebar && toggleBtn) {
        toggleBtn.addEventListener("click", function () {
            sidebar.classList.add("active");
        });
    }
    if (sidebar && closeBtn) {
        closeBtn.addEventListener("click", function () {
            sidebar.classList.remove("active");
        });
    }

    // --- Logout button handler ---
    var logoutBtn = document.getElementById("adminLogoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", function () {
            // Try API logout first, fall back to direct redirect
            var csrf = "";
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) csrf = meta.getAttribute("content");
            fetch("/lms_vareen/src/api/auth.php?action=logout", {
                method: "POST",
                headers: { "X-CSRF-Token": csrf }
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    window.location.href = "/lms_vareen/index.php?page=login";
                })
                .catch(function () {
                    window.location.href = "/lms_vareen/index.php?page=login";
                });
        });
    }

    // --- KPI count-up animation ---
    var kpiCards = document.querySelectorAll(".kpi-count");
    if (kpiCards.length) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var el = entry.target;
                    var target = parseFloat(el.getAttribute("data-target") || el.textContent);
                    var isCurrency = el.textContent.indexOf("₦") !== -1 || el.getAttribute("data-currency");
                    var isDecimal = target % 1 !== 0;
                    var start = 0;
                    var duration = 1500;
                    var startTime = null;
                    function animate(timestamp) {
                        if (!startTime) startTime = timestamp;
                        var progress = Math.min((timestamp - startTime) / duration, 1);
                        var value = Math.floor(target * progress);
                        if (isCurrency) el.textContent = "₦" + value.toLocaleString();
                        else if (isDecimal) el.textContent = target.toFixed(2);
                        else el.textContent = value.toLocaleString();
                        if (progress < 1) requestAnimationFrame(animate);
                    }
                    // Store original target text
                    var origText = el.textContent;
                    el.setAttribute("data-target", isCurrency ? target : (isDecimal ? target : Math.floor(target)));
                    requestAnimationFrame(animate);
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.5 });
        kpiCards.forEach(function (card) { observer.observe(card); });
    }
})();