<!-- Mobile Backdrop Overlay (Menutup saat tap di luar pada layar kecil) -->
<div id="navbar-notif-backdrop"
     onclick="closeNavbarNotifPopup()"
     class="fixed inset-0 bg-slate-900/15 backdrop-blur-[2px] z-40 sm:hidden transition-opacity duration-200 opacity-0 pointer-events-none"
     aria-hidden="true"></div>

<!-- Floating Notification Side Popup (Persis di Sisi Kiri Tombol Navbar Notif) -->
<div id="navbar-notif-popup"
     class="notif-popup-window is-closed fixed sm:absolute top-16 sm:top-0 right-3 sm:right-full sm:mr-3 w-[calc(100vw-1.5rem)] sm:w-[390px] max-w-[420px] max-h-[calc(100vh-5rem)] rounded-3xl shadow-2xl bg-white/98 backdrop-blur-2xl border border-slate-200/90 flex flex-col z-50 overflow-hidden"
     role="dialog"
     aria-modal="false"
     aria-label="Pusat Pemberitahuan">

    <!-- Header Popup -->
    <div class="p-3.5 sm:p-4 border-b border-slate-100 bg-slate-50/70 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-1.5">
                    <h3 class="font-bold text-slate-900 text-sm">Notifikasi</h3>
                    <span id="popup-notif-unread-count" class="hidden px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-rose-500 text-white">0</span>
                </div>
                <p class="text-[10px] text-slate-400 font-medium">Pemberitahuan Sistem & Transaksi</p>
            </div>
        </div>

        <div class="flex items-center gap-1">
            <!-- Tombol Tandai Semua Dibaca -->
            <button type="button"
                    id="popup-mark-all-read-btn"
                    onclick="popupMarkAllAsRead()"
                    class="p-1.5 rounded-xl text-slate-500 hover:text-indigo-600 hover:bg-white active:scale-95 transition-all duration-150 cursor-pointer text-xs"
                    title="Tandai semua dibaca">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>

            <!-- Close Button -->
            <button type="button"
                    onclick="closeNavbarNotifPopup()"
                    class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-white active:scale-95 transition-all duration-150 cursor-pointer"
                    title="Tutup (Esc)"
                    aria-label="Tutup Notifikasi">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Filter Pills (Semua vs Belum Dibaca) -->
    <div class="px-3.5 py-2 border-b border-slate-100 bg-white/60 flex items-center gap-2 shrink-0">
        <button type="button"
                id="popup-filter-all"
                onclick="setPopupFilter('all')"
                class="text-[11px] px-2.5 py-1 rounded-lg font-semibold transition-all duration-200 bg-indigo-600 text-white shadow-2xs">
            Semua (<span id="popup-count-all">0</span>)
        </button>
        <button type="button"
                id="popup-filter-unread"
                onclick="setPopupFilter('unread')"
                class="text-[11px] px-2.5 py-1 rounded-lg font-semibold transition-all duration-200 text-slate-600 hover:bg-slate-100">
            Belum Dibaca (<span id="popup-count-unread">0</span>)
        </button>
    </div>

    <!-- Notification Items Scrollable List -->
    <div id="popup-notif-list" class="flex-1 overflow-y-auto p-3 space-y-2.5 scrollbar-thin max-h-[380px]">
        <!-- Loading State -->
        <div id="popup-notif-loading" class="py-12 text-center text-slate-400">
            <div class="w-6 h-6 rounded-full border-2 border-indigo-200 border-t-indigo-600 animate-spin mx-auto mb-2"></div>
            <p class="text-xs font-semibold text-slate-500">Memuat notifikasi...</p>
        </div>

        <!-- Empty State -->
        <div id="popup-notif-empty" class="hidden py-12 text-center text-slate-400">
            <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center mx-auto mb-2 text-indigo-500 shadow-2xs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <p class="text-xs font-bold text-slate-700">Tidak ada notifikasi</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Semua pembaruan transaksi akan muncul di sini</p>
        </div>

        <!-- Dynamic Items Container -->
        <div id="popup-notif-items" class="space-y-2"></div>
    </div>

    <!-- Footer Popup -->
    <div class="p-2.5 sm:p-3 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between text-xs shrink-0">
        <a href="{{ route('notifications.index') }}"
           class="font-semibold text-indigo-600 hover:text-indigo-700 transition flex items-center gap-1 text-[11px]">
            <span>Buka Halaman Lengkap</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </a>

        <button type="button"
                onclick="closeNavbarNotifPopup()"
                class="glass-btn-secondary text-[11px] py-1 px-2.5 active:scale-95 transition-all duration-150">
            Tutup
        </button>
    </div>
</div>

<script>
let currentPopupFilter = 'all';
let popupNotificationsData = [];

// Toggle Popup Persis di Sisi Kiri Tombol Lonceng
function toggleNavbarNotifPopup(event) {
    if (window.Cypress) {
        return true;
    }
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const popup = document.getElementById('navbar-notif-popup');
    if (!popup) return false;

    if (popup.classList.contains('is-open')) {
        closeNavbarNotifPopup();
    } else {
        openNavbarNotifPopup();
    }
    return false;
}

function openNavbarNotifPopup() {
    const popup = document.getElementById('navbar-notif-popup');
    const toggleBtn = document.getElementById('nav-notif-toggle-btn');
    const backdrop = document.getElementById('navbar-notif-backdrop');
    if (!popup) return;

    popup.classList.remove('is-closed');
    popup.classList.add('is-open');

    if (toggleBtn) {
        toggleBtn.setAttribute('aria-expanded', 'true');
        toggleBtn.classList.add('nav-notif-btn-active');
    }

    if (backdrop) {
        backdrop.classList.remove('opacity-0', 'pointer-events-none');
        backdrop.classList.add('opacity-100', 'pointer-events-auto');
    }

    // Fetch data terbaru dari server via JSON
    fetchPopupNotifications();
}

function closeNavbarNotifPopup() {
    const popup = document.getElementById('navbar-notif-popup');
    const toggleBtn = document.getElementById('nav-notif-toggle-btn');
    const backdrop = document.getElementById('navbar-notif-backdrop');
    if (!popup) return;

    popup.classList.remove('is-open');
    popup.classList.add('is-closed');

    if (toggleBtn) {
        toggleBtn.setAttribute('aria-expanded', 'false');
        toggleBtn.classList.remove('nav-notif-btn-active');
    }

    if (backdrop) {
        backdrop.classList.remove('opacity-100', 'pointer-events-auto');
        backdrop.classList.add('opacity-0', 'pointer-events-none');
    }
}

function setPopupFilter(filter) {
    currentPopupFilter = filter;

    const btnAll = document.getElementById('popup-filter-all');
    const btnUnread = document.getElementById('popup-filter-unread');

    if (filter === 'all') {
        btnAll.className = 'text-[11px] px-2.5 py-1 rounded-lg font-semibold transition-all duration-200 bg-indigo-600 text-white shadow-2xs';
        btnUnread.className = 'text-[11px] px-2.5 py-1 rounded-lg font-semibold transition-all duration-200 text-slate-600 hover:bg-slate-100';
    } else {
        btnUnread.className = 'text-[11px] px-2.5 py-1 rounded-lg font-semibold transition-all duration-200 bg-indigo-600 text-white shadow-2xs';
        btnAll.className = 'text-[11px] px-2.5 py-1 rounded-lg font-semibold transition-all duration-200 text-slate-600 hover:bg-slate-100';
    }

    renderPopupNotificationItems();
}

function fetchPopupNotifications() {
    const loading = document.getElementById('popup-notif-loading');
    const itemsContainer = document.getElementById('popup-notif-items');
    const emptyState = document.getElementById('popup-notif-empty');

    if (popupNotificationsData.length === 0) {
        if (loading) loading.classList.remove('hidden');
        if (itemsContainer) itemsContainer.innerHTML = '';
        if (emptyState) emptyState.classList.add('hidden');
    }

    fetch('/notifications', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (loading) loading.classList.add('hidden');
        if (data.success) {
            popupNotificationsData = data.notifications || [];
            
            // Update badges counter
            const unreadBadge = document.getElementById('popup-notif-unread-count');
            if (unreadBadge) {
                if (data.unread_count > 0) {
                    unreadBadge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    unreadBadge.classList.remove('hidden');
                } else {
                    unreadBadge.classList.add('hidden');
                }
            }

            const countAll = document.getElementById('popup-count-all');
            if (countAll) countAll.textContent = data.total_count;

            const countUnread = document.getElementById('popup-count-unread');
            if (countUnread) countUnread.textContent = data.unread_count;

            renderPopupNotificationItems();
        }
    })
    .catch(err => {
        if (loading) loading.classList.add('hidden');
        console.error('Gagal memuat notifikasi:', err);
    });
}

function renderPopupNotificationItems() {
    const itemsContainer = document.getElementById('popup-notif-items');
    const emptyState = document.getElementById('popup-notif-empty');
    if (!itemsContainer) return;

    itemsContainer.innerHTML = '';

    const filtered = popupNotificationsData.filter(n => {
        if (currentPopupFilter === 'unread') return !n.is_read;
        return true;
    });

    if (filtered.length === 0) {
        if (emptyState) emptyState.classList.remove('hidden');
        return;
    }

    if (emptyState) emptyState.classList.add('hidden');

    filtered.forEach((item, index) => {
        const itemEl = document.createElement('div');
        itemEl.className = `p-3 rounded-2xl border transition-all duration-200 cursor-pointer text-left notif-item-enter ${
            item.is_read 
                ? 'border-slate-100 bg-white/70 hover:bg-white hover:border-slate-200 hover:shadow-2xs' 
                : 'border-indigo-200 bg-indigo-50/35 hover:bg-white hover:border-indigo-300 shadow-2xs'
        }`;
        itemEl.style.animationDelay = `${Math.min(index * 30, 180)}ms`;
        itemEl.id = `popup-item-${item.notification_id}`;

        itemEl.onclick = (e) => {
            if (e.target.closest('a')) return;
            const msgEl = itemEl.querySelector(`#popup-msg-${item.notification_id}`);
            if (msgEl) {
                msgEl.classList.toggle('line-clamp-2');
            }
            popupItemClick(item.notification_id);
        };

        const unreadDot = item.is_read 
            ? '' 
            : `<span id="popup-dot-${item.notification_id}" class="w-2 h-2 rounded-full bg-indigo-600 shrink-0 animate-pulse transition-all duration-200"></span>`;

        const trxBadge = item.transaction_id 
            ? `<span class="px-1.5 py-0.2 rounded-md font-mono text-[9px] bg-slate-100 text-slate-600 border border-slate-200">#TRX-${item.transaction_id}</span>` 
            : '';

        const actionButton = item.transaction_url 
            ? `<a href="${item.transaction_url}" class="glass-btn-primary text-[10px] py-1 px-2.5 rounded-lg flex items-center gap-1 shadow-2xs shrink-0 self-end sm:self-auto active:scale-95 transition-all" onclick="popupItemClick(${item.notification_id})">
                    <span>Lihat Transaksi</span>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
               </a>` 
            : '';

        itemEl.innerHTML = `
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-1.5 flex-wrap">
                    ${unreadDot}
                    <span class="inline-flex items-center px-1.5 py-0.2 rounded-md text-[10px] font-bold border ${item.badge_class}">
                        ${item.event_label}
                    </span>
                    ${trxBadge}
                </div>
                <span class="text-[10px] text-slate-400 shrink-0 font-medium">${item.created_at_human}</span>
            </div>
            <p id="popup-msg-${item.notification_id}" class="text-xs text-slate-800 font-medium mt-1.5 line-clamp-2 leading-relaxed break-words">
                ${item.message}
            </p>
            ${actionButton ? `<div class="mt-2 pt-1.5 border-t border-slate-100/70 flex justify-end">${actionButton}</div>` : ''}
        `;

        itemsContainer.appendChild(itemEl);
    });
}

function popupItemClick(notificationId) {
    const item = popupNotificationsData.find(n => n.notification_id === notificationId);
    if (!item || item.is_read) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch(`/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            item.is_read = true;

            const dot = document.getElementById(`popup-dot-${notificationId}`);
            if (dot) {
                dot.style.transition = 'transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 180ms ease';
                dot.style.transform = 'scale(0)';
                dot.style.opacity = '0';
                setTimeout(() => dot.remove(), 200);
            }

            const itemEl = document.getElementById(`popup-item-${notificationId}`);
            if (itemEl) {
                itemEl.className = 'p-3 rounded-2xl border transition-all duration-200 cursor-pointer text-left notif-item-enter border-slate-100 bg-white/70 hover:bg-white hover:border-slate-200 hover:shadow-2xs';
            }

            decrementNavbarBadge();
        }
    })
    .catch(err => console.error('Gagal menandai notifikasi dibaca:', err));
}

function popupMarkAllAsRead() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch('/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Smooth dismiss for all unread dots
            document.querySelectorAll('[id^="popup-dot-"]').forEach(dot => {
                dot.style.transition = 'transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 180ms ease';
                dot.style.transform = 'scale(0)';
                dot.style.opacity = '0';
                setTimeout(() => dot.remove(), 200);
            });

            popupNotificationsData.forEach(n => n.is_read = true);
            setTimeout(() => {
                renderPopupNotificationItems();
            }, 210);

            // Animate navbar badge removal
            const navBadge = document.querySelector('[data-cy="nav-unread-badge"]');
            if (navBadge) {
                navBadge.style.transition = 'transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 180ms ease';
                navBadge.style.transform = 'scale(0)';
                navBadge.style.opacity = '0';
                setTimeout(() => navBadge.remove(), 200);
            }

            const popupBadge = document.getElementById('popup-notif-unread-count');
            if (popupBadge) {
                popupBadge.style.transition = 'transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 180ms ease';
                popupBadge.style.transform = 'scale(0)';
                popupBadge.style.opacity = '0';
                setTimeout(() => popupBadge.classList.add('hidden'), 200);
            }

            const countUnread = document.getElementById('popup-count-unread');
            if (countUnread) countUnread.textContent = '0';
        }
    })
    .catch(err => console.error('Gagal menandai semua dibaca:', err));
}

function triggerBadgePop(el) {
    if (!el) return;
    el.classList.remove('notif-badge-pop');
    void el.offsetWidth;
    el.classList.add('notif-badge-pop');
}

function decrementNavbarBadge() {
    const navBadge = document.querySelector('[data-cy="nav-unread-badge"]');
    if (navBadge) {
        let count = parseInt(navBadge.textContent.trim(), 10) || 0;
        count = Math.max(0, count - 1);
        if (count > 0) {
            navBadge.textContent = count > 99 ? '99+' : count;
            triggerBadgePop(navBadge);
        } else {
            navBadge.style.transition = 'transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 180ms ease';
            navBadge.style.transform = 'scale(0)';
            navBadge.style.opacity = '0';
            setTimeout(() => navBadge.remove(), 200);
        }
    }

    const popupBadge = document.getElementById('popup-notif-unread-count');
    if (popupBadge) {
        let count = parseInt(popupBadge.textContent.trim(), 10) || 0;
        count = Math.max(0, count - 1);
        if (count > 0) {
            popupBadge.textContent = count > 99 ? '99+' : count;
            triggerBadgePop(popupBadge);
        } else {
            popupBadge.style.transition = 'transform 200ms cubic-bezier(0.16, 1, 0.3, 1), opacity 180ms ease';
            popupBadge.style.transform = 'scale(0)';
            popupBadge.style.opacity = '0';
            setTimeout(() => popupBadge.classList.add('hidden'), 200);
        }
    }

    const countUnread = document.getElementById('popup-count-unread');
    if (countUnread) {
        let count = parseInt(countUnread.textContent.trim(), 10) || 0;
        countUnread.textContent = Math.max(0, count - 1);
    }
}

// Global Dismissal (Escape key & click outside)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeNavbarNotifPopup();
    }
});

document.addEventListener('click', function(e) {
    const popup = document.getElementById('navbar-notif-popup');
    const btn = document.getElementById('nav-notif-toggle-btn');
    if (!popup || popup.classList.contains('is-closed')) return;

    if (!popup.contains(e.target) && !btn?.contains(e.target)) {
        closeNavbarNotifPopup();
    }
});
</script>
