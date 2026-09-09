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
            // Build base path from PHP (injected into a data attribute on <html>)
            var basePath = document.documentElement.getAttribute("data-basepath") || "";
            var csrf = "";
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) csrf = meta.getAttribute("content");
            fetch(basePath + "/src/api/auth.php?action=logout", {
                method: "POST",
                headers: { "X-CSRF-Token": csrf }
            })
                .then(function (r) {
                    if (!r.ok) throw new Error("HTTP " + r.status);
                    return r.json();
                })
                .then(function (data) {
                    window.location.href = basePath + "/index.php?page=login";
                })
                .catch(function () {
                    // Fallback: redirect to login even if API fails
                    window.location.href = basePath + "/index.php?page=login";
                });
        });
    }

    // --- KPI count-up animation ---
    // Uses data-target attribute for numeric values (avoids NaN from parsing
    // formatted DOM text like "₦25,000"). Falls back to text parsing only
    // when data-target is absent, with strict sanitization.
    var kpiCards = document.querySelectorAll(".kpi-count");
    if (kpiCards.length) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    var el = entry.target;
                    var target = null;
                    var isCurrency = false;
                    var currencySymbol = "";

                    // PRIMARY: use data-target attribute (set by PHP or previous run)
                    var dataTarget = el.getAttribute("data-target");
                    if (dataTarget !== null && dataTarget !== "") {
                        target = parseFloat(String(dataTarget).replace(/[^0-9.-]/g, ""));
                        isCurrency = el.getAttribute("data-currency") !== null || dataTarget.indexOf("₦") === 0;
                        if (isCurrency) currencySymbol = "₦";
                    }

                    // FALLBACK: parse text content only if no data-target
                    if (isNaN(target) || target === null) {
                        var text = el.textContent || "";
                        // Detect currency symbol
                        if (text.indexOf("₦") !== -1) { isCurrency = true; currencySymbol = "₦"; }
                        else if (text.indexOf("$") !== -1) { isCurrency = true; currencySymbol = "$"; }
                        else if (text.indexOf("€") !== -1) { isCurrency = true; currencySymbol = "€"; }
                        else if (text.indexOf("£") !== -1) { isCurrency = true; currencySymbol = "£"; }
                        // Strip everything except digits, decimal point, and minus sign
                        var cleaned = String(text).replace(/[^0-9.\-]/g, "");
                        target = parseFloat(cleaned);
                    }

                    // If still NaN, skip animation (non-numeric KPI like "N/A")
                    if (isNaN(target) || target === null) { observer.unobserve(el); return; }

                    // Store clean numeric target in data-target for future use
                    el.setAttribute("data-target", target);

                    var isDecimal = target % 1 !== 0;
                    var duration = 1500;
                    var startTime = null;

                    function animate(timestamp) {
                        if (!startTime) startTime = timestamp;
                        var progress = Math.min((timestamp - startTime) / duration, 1);
                        // Ease-out curve for natural feel
                        var eased = 1 - Math.pow(1 - progress, 3);
                        var current = target * eased;

                        if (isCurrency) {
                            el.textContent = currencySymbol + Math.floor(current).toLocaleString();
                        } else if (isDecimal) {
                            el.textContent = current.toFixed(2);
                        } else {
                            el.textContent = Math.floor(current).toLocaleString();
                        }

                        if (progress < 1) {
                            requestAnimationFrame(animate);
                        } else {
                            // Ensure final value is exact
                            if (isCurrency) {
                                el.textContent = currencySymbol + Math.floor(target).toLocaleString();
                            } else if (isDecimal) {
                                el.textContent = target.toFixed(2);
                            } else {
                                el.textContent = Math.floor(target).toLocaleString();
                            }
                        }
                    }
                    requestAnimationFrame(animate);
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.5 });
        kpiCards.forEach(function (card) { observer.observe(card); });
    }
})();