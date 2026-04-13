<?php if(!defined('OSTSCPINC')) die('Доступ запрещён'); ?>

    </main>

    <footer class="border-t border-gray-200 px-6 py-4 text-center text-sm text-gray-400">
        Copyright &copy; 2006-<?=date('Y')?> TicketHub. All Rights Reserved.
    </footer>

</div><!-- /lg:ml-64 -->

<?php if(is_object($thisuser) && $thisuser->isStaff()) { ?>
<div class="hidden">
    <img src="autocron.php" alt="" width="1" height="1" border="0" />
</div>
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js"></script>

<script src="js/lib/Sortable.min.js"></script>
<script src="js/lib/moment.min.js"></script>
<script src="js/lib/moment-ru.js"></script>
<script src="js/lib/fullcalendar.min.js"></script>

<script src="js/tasks.js"></script>
<script src="js/task-kanban.js"></script>
<script src="js/task-calendar.js"></script>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    if (typeof flatpickr !== 'undefined') {
        flatpickr.localize(flatpickr.l10ns.ru);
        flatpickr('.datepicker', {
            dateFormat: 'm/d/Y',
            altInput: true,
            altFormat: 'd.m.Y',
            locale: 'ru',
            allowInput: true,
            animate: true,
            monthSelectorType: 'dropdown',
            prevArrow: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>',
            nextArrow: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>'
        });
    }
});
</script>

<div id="th-modal-overlay" class="fixed inset-0 flex items-center justify-center" style="display:none;z-index:9999;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);">
    <div id="th-modal-box" class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4" style="transform:scale(0.95);opacity:0;transition:transform 0.2s ease,opacity 0.2s ease;">
        <div class="flex items-center gap-3 px-5 pt-5">
            <div id="th-modal-icon" class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"></div>
            <h3 id="th-modal-title" class="text-base font-semibold text-gray-900"></h3>
        </div>
        <div class="px-5 py-4">
            <p id="th-modal-message" class="text-sm text-gray-600" style="white-space:pre-line;line-height:1.6;"></p>
        </div>
        <div id="th-modal-actions" class="flex items-center justify-end gap-2 px-5 py-4 border-t border-gray-100"></div>
    </div>
</div>

<script>
(function() {
    var _lastClicked = null;
    var _bypassConfirm = false;
    var _onConfirm = null;
    var _onCancel = null;

    document.addEventListener('click', function(e) {
        var el = e.target.closest('button, input[type="submit"], a, [onclick]');
        if (el && !el.closest('#th-modal-overlay')) {
            _lastClicked = el;
        }
    }, true);

    var svgInfo  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>';
    var svgWarn  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
    var svgError = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';

    var btnBase = 'display:inline-flex;align-items:center;justify-content:center;padding:0.5rem 1.25rem;border-radius:0.5rem;font-size:0.875rem;font-weight:500;cursor:pointer;transition:opacity 0.15s;';
    var btnPrimary  = btnBase + 'border:none;color:#fff;background:#4f46e5;';
    var btnDanger   = btnBase + 'border:none;color:#fff;background:#dc2626;';
    var btnCancel   = btnBase + 'border:1px solid #d1d5db;color:#374151;background:#fff;';

    function isDestructive(msg) {
        return /удалить|удаление|delete|remove|drop|убрать/i.test(msg);
    }

    function isErrorMessage(msg) {
        return /ошибка|error|fail|invalid|unable|cannot/i.test(msg);
    }

    function closeModal() {
        var box = document.getElementById('th-modal-box');
        var overlay = document.getElementById('th-modal-overlay');
        if (!overlay || overlay.style.display === 'none') return;
        box.style.transform = 'scale(0.95)';
        box.style.opacity = '0';
        setTimeout(function() { overlay.style.display = 'none'; }, 200);
    }

    function handleAction(type) {
        closeModal();
        var fn = (type === 'confirm') ? _onConfirm : _onCancel;
        _onConfirm = null;
        _onCancel = null;
        if (fn) fn();
    }

    function showModal(type, message, onConfirm, onCancel) {
        var overlay = document.getElementById('th-modal-overlay');
        var box     = document.getElementById('th-modal-box');
        var iconEl  = document.getElementById('th-modal-icon');
        var titleEl = document.getElementById('th-modal-title');
        var msgEl   = document.getElementById('th-modal-message');
        var actEl   = document.getElementById('th-modal-actions');
        if (!overlay) return;

        _onConfirm = onConfirm || null;
        _onCancel  = onCancel  || null;

        var destructive = (type === 'confirm') && isDestructive(message);

        if (type === 'confirm') {
            iconEl.style.background = destructive ? '#fee2e2' : '#fef3c7';
            iconEl.innerHTML = destructive ? svgError : svgWarn;
            titleEl.textContent = destructive ? 'Подтверждение удаления' : 'Подтверждение';
        } else if (type === 'error') {
            iconEl.style.background = '#fee2e2';
            iconEl.innerHTML = svgError;
            titleEl.textContent = 'Ошибка';
        } else {
            iconEl.style.background = '#dbeafe';
            iconEl.innerHTML = svgInfo;
            titleEl.textContent = 'Уведомление';
        }

        msgEl.textContent = message;

        if (type === 'confirm') {
            actEl.innerHTML =
                '<button id="th-modal-cancel" style="' + btnCancel + '">Отмена</button>' +
                '<button id="th-modal-confirm" style="' + (destructive ? btnDanger : btnPrimary) + '">' +
                (destructive ? 'Удалить' : 'Подтвердить') + '</button>';
        } else {
            actEl.innerHTML = '<button id="th-modal-ok" style="' + btnPrimary + '">OK</button>';
        }

        overlay.style.display = 'flex';
        requestAnimationFrame(function() {
            box.style.transform = 'scale(1)';
            box.style.opacity = '1';
        });

        var focusBtn = actEl.querySelector('#th-modal-confirm, #th-modal-ok');
        if (focusBtn) setTimeout(function() { focusBtn.focus(); }, 50);
    }

    document.getElementById('th-modal-overlay').addEventListener('click', function(e) {
        if (e.target === this) { handleAction('cancel'); return; }
        var btn = e.target.closest('#th-modal-ok, #th-modal-confirm, #th-modal-cancel');
        if (!btn) return;
        handleAction(btn.id === 'th-modal-cancel' ? 'cancel' : 'confirm');
    });

    document.addEventListener('keydown', function(e) {
        var overlay = document.getElementById('th-modal-overlay');
        if (!overlay || overlay.style.display === 'none') return;
        if (e.key === 'Escape') {
            e.preventDefault();
            handleAction('cancel');
        } else if (e.key === 'Enter') {
            e.preventDefault();
            handleAction('confirm');
        }
    });

    window.alert = function(message) {
        showModal(isErrorMessage(message) ? 'error' : 'alert', message);
    };

    window.confirm = function(message) {
        if (_bypassConfirm) {
            _bypassConfirm = false;
            return true;
        }
        var trigger = _lastClicked;
        showModal('confirm', message, function() {
            _bypassConfirm = true;
            if (trigger) trigger.click();
        });
        return false;
    };

    window.thAlert   = function(msg, type) { showModal(type || 'alert', msg); };
    window.thConfirm = function(msg, onOk, onCancel) { showModal('confirm', msg, onOk, onCancel); };
})();
</script>

</body>
</html>
