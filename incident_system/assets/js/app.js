const ToastManager = {
  init() {
    if (document.getElementById('toast-container')) return;
    const c = document.createElement('div');
    c.id = 'toast-container';
    document.body.appendChild(c);
  },
  show(message, type = 'info', duration = 6000) {
    this.init();
    const icons = {
      success: '<i class="fa fa-circle-check" style="color:#28a745"></i>',
      warning:
        '<i class="fa fa-triangle-exclamation" style="color:#ffc107"></i>',
      info: '<i class="fa fa-bell" style="color:#1a73e8"></i>',
    };
    const titles = {
      success: 'Incident Resolved',
      warning: 'Status Updated',
      info: 'Notification',
    };

    const t = document.createElement('div');
    t.className = `rt-toast toast-${type}`;
    t.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-body">
                <div class="toast-title">${titles[type] || 'Notification'}</div>
                <div class="toast-msg">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">✕</button>`;
    document.getElementById('toast-container').appendChild(t);
    setTimeout(() => {
      t.style.animation = 'slideOut 0.3s ease forwards';
      setTimeout(() => t.remove(), 300);
    }, duration);
  },
};

function updateBellBadge(count) {
  const topBadge = document.getElementById('topbarNotifBadge');
  if (topBadge) {
    topBadge.textContent = count > 9 ? '9+' : count;
    topBadge.style.display = count > 0 ? 'flex' : 'none';
  }

  const sidebarBadge = document.querySelector(
    '.sidebar .nav-link .badge.bg-danger'
  );
  if (sidebarBadge) {
    sidebarBadge.textContent = count > 9 ? '9+' : count;
    sidebarBadge.style.display = count > 0 ? 'inline-block' : 'none';
  } else if (count > 0) {
    const notifLink = document.querySelector(
      '.sidebar a[href*="notifications"]'
    );
    if (notifLink) {
      const badge = document.createElement('span');
      badge.className = 'badge bg-danger ms-auto';
      badge.id = 'sidebarNotifBadge';
      badge.textContent = count > 9 ? '9+' : count;
      notifLink.appendChild(badge);
    }
  }
}

function updateStatusBadge(incidentId, newStatus) {
  const row = document.querySelector(`tr[data-incident-id="${incidentId}"]`);
  if (!row) return;

  const cell = row.querySelector('.incident-status-cell');
  if (!cell) return;

  cell.className = cell.className.replace(/badge-\S+/g, '').trim();

  const slug = newStatus.toLowerCase().replace(/ /g, '-');
  cell.classList.add(`badge-${slug}`);
  cell.textContent = newStatus;

  row.style.transition = 'background 0.3s';
  row.style.background = '#fffde7';
  setTimeout(() => {
    row.style.background = '';
  }, 1500);
}

function updateStatCards(statMap) {
  const open = document.getElementById('stat-open');
  const inp = document.getElementById('stat-inprogress');
  const res = document.getElementById('stat-resolved');
  const total = document.getElementById('stat-total');

  if (open) open.textContent = statMap['Open'] || 0;
  if (inp) inp.textContent = statMap['In Progress'] || 0;
  if (res) res.textContent = statMap['Resolved'] || 0;
  if (total)
    total.textContent =
      (statMap['Open'] || 0) +
      (statMap['In Progress'] || 0) +
      (statMap['Resolved'] || 0);
}

(function startPolling() {
  if (typeof BASE_URL === 'undefined') return;

  let lastPollTime = Math.floor(Date.now() / 1000) - 5;

  function poll() {
    fetch(
      `${BASE_URL}/user/ajax_notifications.php?action=poll&since=${lastPollTime}`,
      {
        credentials: 'same-origin',
      }
    )
      .then((r) => r.json())
      .then((data) => {
        if (data.server_time) lastPollTime = data.server_time;

        const count = data.unread_count || 0;
        updateBellBadge(count);

        if (data.new && data.new.length > 0) {
          data.new.forEach((n) => {
            const msg = n.message || '';
            const type = msg.toLowerCase().includes('resolved')
              ? 'success'
              : 'warning';
            ToastManager.show(msg, type);
          });
        }

        if (data.incident_statuses && data.incident_statuses.length > 0) {
          data.incident_statuses.forEach((inc) => {
            updateStatusBadge(inc.id, inc.status);
          });
        }

        if (data.stat_counts) {
          updateStatCards(data.stat_counts);
        }
      })
      .catch(() => {});
  }

  poll();
  setInterval(poll, 5000);
})();

if (typeof BASE_URL !== 'undefined') {
  setInterval(
    () => {
      fetch(BASE_URL + '/auth/refresh_token.php', {
        credentials: 'same-origin',
      })
        .then((r) => r.json())
        .then((data) => {
          if (data.status !== 'success') {
            window.location.href = BASE_URL + '/auth/login.php';
          }
        })
        .catch(() => {});
    },
    13 * 60 * 1000
  );
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.auto-dismiss').forEach((el) => {
    setTimeout(() => {
      el.style.transition = 'opacity .5s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 500);
    }, 4000);
  });
});

function toggleSelectAll(masterCheckbox) {
  document
    .querySelectorAll('.row-checkbox')
    .forEach((cb) => (cb.checked = masterCheckbox.checked));
  updateBulkBar();
}

function updateBulkBar() {
  const selected = document.querySelectorAll('.row-checkbox:checked').length;
  const bar = document.getElementById('bulkActionBar');
  const counter = document.getElementById('selectedCount');
  if (!bar) return;
  bar.style.display = selected > 0 ? 'flex' : 'none';
  bar.classList.toggle('d-none', selected === 0);
  if (counter) counter.textContent = selected + ' selected';
}

document.addEventListener('change', (e) => {
  if (e.target.classList.contains('row-checkbox')) updateBulkBar();
});

document.addEventListener('submit', (e) => {
  const btn = e.target.querySelector('button[type="submit"]');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML =
      '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
  }
});

function toggleSidebar() {
  const sidebar = document.getElementById('mainSidebar');
  const main = document.querySelector('.main-content');
  const icon = document.getElementById('toggleIcon');
  const collapsed = sidebar.classList.toggle('collapsed');
  if (main) main.classList.toggle('expanded', collapsed);
  if (icon) icon.className = collapsed ? 'fa fa-indent' : 'fa fa-bars';
  localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
}