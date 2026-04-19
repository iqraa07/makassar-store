    </div><!-- end page-content -->
</div><!-- end main-content -->
</div><!-- end app-layout -->

<!-- ─── Scripts ─── -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
// ══════════════════════════════════════════════
// MAKASSASTORE — Global App JS v3
// ══════════════════════════════════════════════

// ─── Live Clock ───
function updateClock() {
    const now = new Date();
    const H = String(now.getHours()).padStart(2,'0');
    const M = String(now.getMinutes()).padStart(2,'0');
    const S = String(now.getSeconds()).padStart(2,'0');
    const days   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const el = document.getElementById('live-time');
    const de = document.getElementById('live-date');
    if (el) el.textContent = `${H}:${M}:${S}`;
    if (de) de.textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}
setInterval(updateClock, 1000);
updateClock();

// ─── Sidebar Toggle ───
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

// ─── User Dropdown ───
function toggleUserMenu() {
    document.getElementById('userDropdown').classList.toggle('open');
}
document.addEventListener('click', e => {
    if (!e.target.closest('#userDropdown')) {
        document.getElementById('userDropdown')?.classList.remove('open');
    }
});

// ─── Toast Notification ───
function showToast(type, title, msg, duration = 3500) {
    const icons = { success:'fa-circle-check', error:'fa-circle-xmark', warning:'fa-triangle-exclamation', info:'fa-circle-info' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fa-solid ${icons[type]||icons.info} toast-icon"></i>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            ${msg ? `<div class="toast-msg">${msg}</div>` : ''}
        </div>
        <button onclick="dismissToast(this.parentElement)" style="background:none;border:none;color:var(--text-muted);cursor:pointer;margin-left:8px;font-size:14px;flex-shrink:0"><i class="fa-solid fa-xmark"></i></button>
    `;
    const container = document.getElementById('toast-container');
    container.appendChild(toast);
    // Auto remove with fade-out
    setTimeout(() => dismissToast(toast), duration);
}
function dismissToast(el) {
    if (!el || !el.parentNode) return;
    el.style.transition = 'all 0.3s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateX(60px)';
    el.style.maxHeight = el.offsetHeight + 'px';
    setTimeout(() => {
        el.style.maxHeight = '0';
        el.style.marginBottom = '0';
        el.style.padding = '0';
        el.style.overflow = 'hidden';
    }, 200);
    setTimeout(() => el.remove(), 450);
}

// ─── Format Rupiah ───
function formatRp(angka) {
    return 'Rp ' + Number(angka).toLocaleString('id-ID');
}

// ─── Confirm Dialog ───
function confirmAction(msg) {
    return confirm(msg || 'Yakin ingin melanjutkan?');
}

// ─── Modal Helpers ───
function openModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('show'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('show'); document.body.style.overflow = ''; }
}
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('show');
        document.body.style.overflow = '';
    }
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.show').forEach(m => {
            m.classList.remove('show');
            document.body.style.overflow = '';
        });
    }
});

// ─── Ajax Helper (Fixed Content-Type) ───
async function fetchJSON(url, options = {}) {
    try {
        const { headers: optHeaders, ...restOptions } = options;
        const res = await fetch(url, {
            ...restOptions,
            headers: { ...(optHeaders || {}) }
        });
        return await res.json();
    } catch (err) {
        console.error('Fetch error:', err);
        return { success: false, message: 'Koneksi error' };
    }
}

// ─── Debounce ───
function debounce(fn, delay) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
}

// ─── Number Count-Up Animation ───
function countUp(el, target, duration = 1200, prefix = '', suffix = '') {
    if (!el) return;
    const start    = 0;
    const startTime = performance.now();
    const isFloat  = target % 1 !== 0;

    function ease(t) { return t < 0.5 ? 2*t*t : -1+(4-2*t)*t; }

    function update(now) {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const value = start + (target - start) * ease(progress);
        el.textContent = prefix + (isFloat
            ? value.toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:0})
            : Math.floor(value).toLocaleString('id-ID')) + suffix;
        if (progress < 1) requestAnimationFrame(update);
        else el.textContent = prefix + target.toLocaleString('id-ID') + suffix;
    }
    requestAnimationFrame(update);
}

// Auto-trigger count-up on .stat-value[data-count]
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.stat-value[data-count]').forEach(el => {
        const raw    = parseFloat(el.dataset.count) || 0;
        const prefix = el.dataset.prefix || '';
        const suffix = el.dataset.suffix || '';
        el.textContent = prefix + '0' + suffix;
        setTimeout(() => countUp(el, raw, 1200, prefix, suffix), 200);
    });
});

// ─── Ripple Effect on Buttons ───
document.addEventListener('click', e => {
    const btn = e.target.closest('.btn');
    if (!btn) return;
    const rect   = btn.getBoundingClientRect();
    const size   = Math.max(rect.width, rect.height) * 2;
    const x      = e.clientX - rect.left - size/2;
    const y      = e.clientY - rect.top  - size/2;
    const ripple = document.createElement('span');
    ripple.className = 'ripple';
    ripple.style.cssText = `width:${size}px;height:${size}px;left:${x}px;top:${y}px;`;
    btn.appendChild(ripple);
    setTimeout(() => ripple.remove(), 600);
});

// ─── Smart Notification: stok kritis ───
(function() {
    const badge = document.querySelector('.nav-badge');
    if (badge && parseInt(badge.textContent) > 0) {
        badge.style.animation = 'badgePulse 1.5s ease-in-out infinite';
        const style = document.createElement('style');
        style.textContent = `@keyframes badgePulse {0%,100%{transform:scale(1)}50%{transform:scale(1.2)}}`;
        document.head.appendChild(style);
    }
})();

// ── Mobile Sidebar: tutup saat klik di luar ──
(function() {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    document.addEventListener('click', function(e) {
        if (window.innerWidth > 768) return;
        if (sidebar.classList.contains('open') &&
            !sidebar.contains(e.target) &&
            !e.target.closest('#menu-btn')) {
            sidebar.classList.remove('open');
        }
    });
})();
</script>
</body>
</html>
