/**
 * Command Palette - Ctrl+K / Cmd+K
 * Search-over-actions overlay for admin dashboard.
 */
(function () {
    "use strict";

    var commands = [
        { label: 'Add Student', icon: 'fa-user-plus', href: 'index.php?page=admin-users' },
        { label: 'Add Teacher', icon: 'fa-chalkboard-teacher', href: 'index.php?page=admin-teachers' },
        { label: 'Create Course', icon: 'fa-book-open', href: 'index.php?page=admin-courses' },
        { label: 'Schedule Live Class', icon: 'fa-video', href: 'index.php?page=admin-live' },
        { label: 'Send Announcement', icon: 'fa-bullhorn', href: 'index.php?page=admin-notifications' },
        { label: 'View Payments', icon: 'fa-credit-card', href: 'index.php?page=admin-payments' },
        { label: 'Issue Certificate', icon: 'fa-certificate', href: 'index.php?page=admin-certificates' },
        { label: 'Create Coupon', icon: 'fa-ticket-alt', href: 'index.php?page=admin-coupons' },
        { label: 'Review Applications', icon: 'fa-user-check', href: 'index.php?page=admin-applications' },
        { label: 'Community Hub', icon: 'fa-comments', href: 'index.php?page=admin-community' },
        { label: 'AI Moderation', icon: 'fa-shield-alt', href: 'index.php?page=admin-moderation' },
        { label: 'AI Control Center', icon: 'fa-robot', href: 'index.php?page=admin-ai' },
        { label: 'Analytics', icon: 'fa-chart-pie', href: 'index.php?page=admin-analytics' },
        { label: 'Reports', icon: 'fa-chart-bar', href: 'index.php?page=admin-reports' },
        { label: 'Activity Log', icon: 'fa-history', href: 'index.php?page=admin-activity' },
        { label: 'Settings', icon: 'fa-cog', href: 'index.php?page=admin-settings' }
    ];

    var palette = null, input = null, results = null;

    function createPalette() {
        palette = document.createElement('div');
        palette.className = 'cmd-palette';
        palette.setAttribute('role', 'dialog');
        palette.setAttribute('aria-label', 'Command palette');
        palette.innerHTML =
            '<div class="cmd-dialog">' +
              '<div class="cmd-header"><i class="fas fa-search"></i>' +
              '<input type="text" id="cmdInput" placeholder="Type a command..." autocomplete="off">' +
              '<span class="cmd-shortcut" aria-label="Close">Esc</span></div>' +
              '<div class="cmd-results" id="cmdResults"></div>' +
              '<div class="cmd-footer">Keyboard: Ctrl+K</div>' +
            '</div>';
        document.body.appendChild(palette);
        input = document.getElementById('cmdInput');
        results = document.getElementById('cmdResults');
        renderResults(commands);
        input.addEventListener('input', onInput);
        input.addEventListener('keydown', onKeyDown);
        palette.addEventListener('click', function(e) { if (e.target === palette) closePalette(); });
    }

    function renderResults(items) {
        if (!items || !items.length) {
            results.innerHTML = '<div class="cmd-item">No commands found</div>';
            return;
        }
        var html = '';
        for (var i = 0; i < items.length; i++) {
            html += '<div class="cmd-item' + (i === 0 ? ' active' : '') + '" data-cmd="' + i + '">' +
                    '<i class="fas ' + (items[i].icon || 'fa-circle') + '"></i><span>' + items[i].label + '</span></div>';
        }
        results.innerHTML = html;
        var els = results.querySelectorAll('.cmd-item');
        for (var j = 0; j < els.length; j++) {
            (function(el, idx, cmds) {
                el.onclick = function() { window.location.href = cmds[idx].href; };
            })(els[j], j, items);
        }
    }

    function onInput(e) {
        var val = e.target.value.toLowerCase().trim();
        renderResults(commands.filter(function(c) { return c.label.toLowerCase().indexOf(val) !== -1; }));
    }

    function onKeyDown(e) {
        if (e.key === 'Escape') closePalette();
        else if (e.key === 'Enter') {
            var active = results.querySelector('.cmd-item.active');
            if (active) {
                var idx = parseInt(active.getAttribute('data-cmd'), 10);
                window.location.href = commands[idx].href;
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            var items = results.querySelectorAll('.cmd-item');
            var cur = results.querySelector('.cmd-item.active');
            var idx = Array.prototype.indexOf.call(items, cur);
            var next = (idx + 1 + items.length) % items.length;
            if (items[idx]) items[idx].classList.remove('active');
            if (items[next]) items[next].classList.add('active');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            var items = results.querySelectorAll('.cmd-item');
            var cur = results.querySelector('.cmd-item.active');
            var idx = Array.prototype.indexOf.call(items, cur);
            var next = (idx - 1 + items.length) % items.length;
            if (items[idx]) items[idx].classList.remove('active');
            if (items[next]) items[next].classList.add('active');
        }
    }

    function closePalette() {
        if (palette) palette.classList.remove('open');
    }

    // Global hotkey: Ctrl+K (Cmd+K on Mac)
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (!palette) createPalette();
            palette.classList.add('open');
            input.focus();
            input.select();
        }
    });
})();