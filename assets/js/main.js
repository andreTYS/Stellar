// ============================================================
// INNOVA-STEAM — main.js
// ============================================================

// ── Theme toggle ─────────────────────────────────────────────
function toggleTheme() {
  const curr = document.documentElement.getAttribute('data-theme') || 'light';
  const next = curr === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('is-theme', next);
}

// ── Sidebar toggle (mobile) ──────────────────────────────────
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  if (!sidebar) return;
  const isOpen = sidebar.classList.toggle('open');
  if (overlay) overlay.classList.toggle('visible', isOpen);
  document.body.style.overflow = isOpen ? 'hidden' : '';
}

function closeSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  if (sidebar) sidebar.classList.remove('open');
  if (overlay) overlay.classList.remove('visible');
  document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', () => {
  // Close sidebar on nav item click (mobile)
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
      if (window.innerWidth <= 768) closeSidebar();
    });
  });

  // Close sidebar on Escape key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeSidebar();
  });

  // Auto-close flash messages after 4s
  const flash = document.querySelector('.flash-banner, .flash-auto-close');
  if (flash) {
    setTimeout(() => {
      flash.style.transition = 'opacity .5s';
      flash.style.opacity = '0';
      setTimeout(() => flash.remove(), 500);
    }, 4000);
  }

  // Confirm actions
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

  // Animate progress bars on load
  document.querySelectorAll('.progress-fill[data-pct]').forEach(bar => {
    const pct = parseFloat(bar.dataset.pct) || 0;
    bar.style.width = '0%';
    requestAnimationFrame(() => {
      setTimeout(() => { bar.style.width = pct + '%'; }, 80);
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

// ── Toast notifications ──────────────────────────────────────
function showToast(message, type = 'info', duration = 3500) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.style.cssText = 'position:fixed;bottom:24px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:8px;pointer-events:none';
    document.body.appendChild(container);
  }

  const palette = { success:'#22c55e', error:'#ef4444', warning:'#f59e0b', info:'#4361ee' };
  const color = palette[type] || palette.info;

  const toast = document.createElement('div');
  toast.style.cssText = `
    background:#1a1f36;border:1px solid ${color}30;color:#e2e8f0;
    padding:12px 18px;border-radius:12px;font-size:13px;font-weight:500;
    border-left:3px solid ${color};box-shadow:0 8px 32px rgba(0,0,0,.3);
    pointer-events:auto;max-width:320px;line-height:1.4;
    animation:toastIn .25s cubic-bezier(.22,1,.36,1);
  `;
  toast.textContent = message;

  if (!document.getElementById('toast-styles')) {
    const s = document.createElement('style');
    s.id = 'toast-styles';
    s.textContent = '@keyframes toastIn{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}';
    document.head.appendChild(s);
  }

  container.appendChild(toast);
  setTimeout(() => {
    toast.style.transition = 'opacity .4s, transform .4s';
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(110%)';
    setTimeout(() => toast.remove(), 400);
  }, duration);
}
