(function () {
  'use strict';

  // Sidebar toggle (mobile)
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const closeBtn = document.getElementById('sidebarClose');
  if (toggleBtn) toggleBtn.addEventListener('click', () => sidebar.classList.add('open'));
  if (closeBtn) closeBtn.addEventListener('click', () => sidebar.classList.remove('open'));

  // Fetch wrapper that always sends the CSRF token and expects the app's
  // { success, message, data, errors } JSON envelope.
  window.spFetch = async function (url, options = {}) {
    options.headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': window.CSRF_TOKEN }, options.headers || {});
    if (options.body && !(options.body instanceof FormData) && typeof options.body !== 'string') {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(options.body);
    }
    const res = await fetch(url, options);
    let json;
    try { json = await res.json(); } catch (e) { json = { success: res.ok, message: '', data: null, errors: [] }; }
    return json;
  };

  // Notification dropdown
  const bell = document.getElementById('notifBell');
  if (bell) {
    bell.addEventListener('shown.bs.dropdown', async function () {
      const list = document.getElementById('notifList');
      const result = await window.spFetch(window.APP_URL + '/notifications/dropdown');
      if (!result.success || !result.data.items.length) {
        list.innerHTML = '<div class="p-3 text-muted small text-center">You are all caught up.</div>';
        return;
      }
      list.innerHTML = result.data.items.map(function (n) {
        const cls = n.is_read == 0 ? 'unread' : '';
        return '<a class="notif-item ' + cls + '" href="' + (n.link_url || '#') + '">' +
          '<div class="notif-title">' + escapeHtml(n.title) + '</div>' +
          '<div class="small text-muted">' + escapeHtml(n.message) + '</div></a>';
      }).join('');
    });
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
  }

  // Mark-all-read buttons anywhere on the page
  document.querySelectorAll('[data-mark-all-read]').forEach(function (btn) {
    btn.addEventListener('click', async function (e) {
      e.preventDefault();
      await window.spFetch(window.APP_URL + '/notifications/read-all', { method: 'POST' });
      window.location.reload();
    });
  });

  // Disable submit buttons on submit to guard against duplicate submissions (PRG pattern aid)
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      const btn = form.querySelector('button[type="submit"]');
      if (btn && !btn.disabled) {
        btn.disabled = true;
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Please wait...';
        setTimeout(() => { btn.disabled = false; if (btn.dataset.originalText) btn.innerHTML = btn.dataset.originalText; }, 8000);
      }
    });
  });

  // Confirm-deletion modals via data-confirm attribute
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
      if (!window.confirm(el.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });

  // Quick tag creation via AJAX on the expense form
  window.spCreateTag = async function (name) {
    const result = await window.spFetch(window.APP_URL + '/tags', {
      method: 'POST',
      body: { name },
    });
    return result;
  };

  // Live split preview
  window.spPreviewSplit = async function (payload) {
    return window.spFetch(window.APP_URL + '/expenses/preview-split', { method: 'POST', body: payload });
  };
})();
