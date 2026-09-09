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
            document.body.classList.add("drawer-open");
        });
    }
    if (sidebar && closeBtn) {
        closeBtn.addEventListener("click", function () {
            sidebar.classList.remove("active");
            document.body.classList.remove("drawer-open");
        });
    }

    // Tap the scrim (mobile) to close the drawer
    var scrim = document.createElement("div");
    scrim.className = "drawer-scrim";
    scrim.setAttribute("aria-hidden", "true");
    document.body.appendChild(scrim);
    scrim.addEventListener("click", function () {
        sidebar && sidebar.classList.remove("active");
        document.body.classList.remove("drawer-open");
    });

    // --- Logout button handler ---
    // Handles all logout buttons: sidebar and topbar variants
    var logoutBtn = document.getElementById("adminLogoutBtn") ||
                    document.getElementById("teacherLogoutBtn") ||
                    document.getElementById("studentLogoutBtn") ||
                    document.getElementById("adminLogoutBtnTop") ||
                    document.getElementById("teacherLogoutBtnTop") ||
                    document.getElementById("studentLogoutBtnTop");
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
                    // Robust parse: server renders values like "₦25,000" or "1,204".
                    // parseFloat("₦25,000") is NaN and parseFloat("25,000") is 25 —
                    // strip everything except digits and the decimal point first.
                    var raw = el.getAttribute("data-target") || el.textContent;
                    var target = parseFloat(String(raw).replace(/[^0-9.]/g, ""));
                    var isCurrency = el.textContent.indexOf("₦") !== -1 || el.getAttribute("data-currency");
                    if (isNaN(target)) { observer.unobserve(el); return; }
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