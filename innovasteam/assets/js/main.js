// ============================================================
// INNOVA-STEAM — main.js
// ============================================================

// ── Sidebar toggle (mobile) ──────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');

  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('show');
    });
  }

  if (overlay) {
    overlay.addEventListener('click', () => {
      sidebar?.classList.remove('open');
      overlay.classList.remove('show');
    });
  }

  // Auto-close flash messages
  const flash = document.querySelector('.flash-auto-close');
  if (flash) {
    setTimeout(() => {
      flash.style.opacity = '0';
      flash.style.transition = 'opacity .5s';
      setTimeout(() => flash.remove(), 500);
    }, 4000);
  }

  // Confirm actions
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', (e) => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });
});

// ── API helper ───────────────────────────────────────────────
async function apiPost(url, data) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

// ── Progress bar animate on load ─────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.progress-fill[data-pct]').forEach(bar => {
    const pct = parseFloat(bar.dataset.pct) || 0;
    bar.style.width = '0%';
    requestAnimationFrame(() => {
      setTimeout(() => { bar.style.width = pct + '%'; }, 100);
    });
  });
});

// ── Toast notifications ──────────────────────────────────────
function showToast(message, type = 'info', duration = 3500) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;';
    document.body.appendChild(container);
  }

  const colors = {
    success: '#22c55e',
    error:   '#ef4444',
    warning: '#f59e0b',
    info:    '#3b82f6',
  };

  const toast = document.createElement('div');
  toast.style.cssText = `
    background:#12121a;border:1px solid ${colors[type] || colors.info}40;
    color:#fff;padding:12px 18px;border-radius:10px;font-size:13.5px;
    border-left:3px solid ${colors[type] || colors.info};
    box-shadow:0 4px 20px rgba(0,0,0,.4);
    animation:slideIn .3s ease;max-width:320px;
  `;
  toast.textContent = message;

  const style = document.createElement('style');
  style.textContent = '@keyframes slideIn{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}';
  document.head.appendChild(style);

  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity .4s';
    setTimeout(() => toast.remove(), 400);
  }, duration);
}
