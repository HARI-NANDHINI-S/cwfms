/**
 * Customer Management Module - Main JavaScript
 * Studio 3 Interactive | Order Management Application
 */

// ============================================================
// THEME MANAGER
// ============================================================
const ThemeManager = {
    KEY: 'cm-theme',
    init() {
        const saved = localStorage.getItem(this.KEY);
        if (saved) {
            document.documentElement.setAttribute('data-theme', saved);
        } else {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
        }
        this.updateIcons();
    },
    toggle() {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem(this.KEY, next);
        this.updateIcons();
        // Update charts if they exist
        if (typeof updateChartColors === 'function') updateChartColors();
    },
    get() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    },
    updateIcons() {
        const theme = this.get();
        document.querySelectorAll('.theme-icon-sun').forEach(el => {
            el.style.display = theme === 'light' ? 'block' : 'none';
        });
        document.querySelectorAll('.theme-icon-moon').forEach(el => {
            el.style.display = theme === 'dark' ? 'block' : 'none';
        });
        document.querySelectorAll('.theme-label-text').forEach(el => {
            el.textContent = theme === 'dark' ? 'Dark Mode' : 'Light Mode';
        });
    }
};

function toggleTheme() { ThemeManager.toggle(); }

// ============================================================
// GLOBALS
// ============================================================
const API_BASE = (function() {
    const path = window.location.pathname;
    if (path.includes('/pages/')) return '../api/';
    return 'api/';
})();

async function apiFetch(url, options = {}) {
    const res = await fetch(url, { credentials: 'same-origin', ...options });
    if (res.status === 401) {
        const loginPath = API_BASE.includes('../') ? '../login.php' : 'login.php';
        window.location.href = loginPath + '?session=expired';
        throw new Error('Session expired');
    }
    return res;
}
let customersData   = [];
let filteredData    = [];
let currentPage     = 1;
const ROWS_PER_PAGE = 10;
let editingId       = null;
let viewingId       = null;

// ============================================================
// PERMISSION HELPERS
// ============================================================
function canPerform(action) {
    const role = window.currentUserRole || 'staff';
    const permissions = {
        'admin':   ['create', 'edit', 'toggle_status', 'view', 'delete'],
        'manager': ['create', 'edit', 'toggle_status', 'view'],
        'staff':   ['create', 'view']
    };
    return (permissions[role] || permissions.staff).includes(action);
}

// ============================================================
// DOM READY
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
    initApp();
});

function initApp() {
    checkDatabaseHealth();
    if (document.getElementById('statTotal')) loadStats();
    if (document.getElementById('customersTableBody')) loadCustomers();
    bindEvents();
    initSearch();
    if (document.getElementById('countryChart')) loadChartData();
}

async function checkDatabaseHealth() {
    const statusEl = document.getElementById('dbStatus');
    const textEl = document.getElementById('dbStatusText');
    if (!statusEl) return;

    try {
        const res = await apiFetch(API_BASE + 'health.php');
        const json = await res.json();
        if (json.success && json.data?.connected) {
            statusEl.className = 'db-status connected';
            textEl.textContent = `DB Live · ${json.data.latency_ms}ms`;
            statusEl.title = `Supabase connected (${json.data.project})`;
        } else {
            statusEl.className = 'db-status disconnected';
            textEl.textContent = 'DB Offline';
            statusEl.title = json.message || 'Database unavailable';
        }
    } catch (err) {
        statusEl.className = 'db-status disconnected';
        textEl.textContent = 'DB Error';
        statusEl.title = 'Could not verify database connection';
    }
}

// ============================================================
// BIND EVENTS
// ============================================================
function bindEvents() {
    const addBtn = document.getElementById('addCustomerBtn');
    if (addBtn) addBtn.addEventListener('click', openAddModal);

    const form = document.getElementById('customerForm');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
        
        // Listen to changes for real-time score
        const formInputs = form.querySelectorAll('input, select');
        formInputs.forEach(input => {
            input.addEventListener('input', updateFormScoreIndicator);
            input.addEventListener('change', updateFormScoreIndicator);
        });
    }

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) closeAllModals();
        });
    });

    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', closeAllModals);
    });

    document.querySelectorAll('[data-action="cancel"]').forEach(btn => {
        btn.addEventListener('click', closeAllModals);
    });

    const statusFilter  = document.getElementById('filterStatus');
    const stateFilter   = document.getElementById('filterState');
    const countryFilter = document.getElementById('filterCountry');
    if (statusFilter)  statusFilter.addEventListener('change', applyFilters);
    if (stateFilter)   stateFilter.addEventListener('change', applyFilters);
    if (countryFilter) countryFilter.addEventListener('change', applyFilters);

    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) exportBtn.addEventListener('click', exportCSV);
}

// ============================================================
// SEARCH
// ============================================================
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    const headerSearch = document.getElementById('headerSearch');

    if (searchInput) {
        searchInput.addEventListener('input', debounce(() => applyFilters(), 300));
    }
    if (headerSearch) {
        headerSearch.addEventListener('input', debounce(e => {
            const si = document.getElementById('searchInput');
            if (si) { si.value = e.target.value; applyFilters(); }
        }, 300));
    }
}

// ============================================================
// LOAD STATS
// ============================================================
async function loadStats() {
    try {
        const res = await apiFetch(API_BASE + 'stats.php');
        const json = await res.json();
        if (json.success && json.data) {
            animateCounter('statTotal',    json.data.total    || 0);
            animateCounter('statToday',    json.data.today    || 0);
            animateCounter('statActive',   json.data.active   || 0);
            animateCounter('statInactive', json.data.inactive || 0);
        }
    } catch (err) { console.error('Stats error:', err); }
}

function animateCounter(id, target) {
    const el = document.getElementById(id);
    if (!el) return;
    if (typeof AppAnimations !== 'undefined' && AppAnimations.animateCounter) {
        AppAnimations.animateCounter(el, target);
        return;
    }
    let current = 0;
    const step = Math.max(1, Math.floor(target / 25));
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        el.textContent = current.toLocaleString();
        if (current >= target) clearInterval(timer);
    }, 40);
}

// ============================================================
// LOAD CUSTOMERS
// ============================================================
async function loadCustomers() {
    showTableSkeleton();
    try {
        const res = await apiFetch(API_BASE + 'customers.php?action=list');
        const json = await res.json();
        if (json.success) {
            customersData = json.data || [];
            filteredData  = [...customersData];
            populateFilters();
            renderTable();
        } else {
            showTableError(json.message || 'Failed to load customers');
        }
    } catch (err) {
        showTableError('Network error. Please try again.');
        console.error(err);
    }
}

// ============================================================
// POPULATE FILTER DROPDOWNS
// ============================================================
function populateFilters() {
    const states    = [...new Set(customersData.map(c => c.state).filter(Boolean))].sort();
    const countries = [...new Set(customersData.map(c => c.country).filter(Boolean))].sort();

    const stateFilter   = document.getElementById('filterState');
    const countryFilter = document.getElementById('filterCountry');

    if (stateFilter) {
        stateFilter.innerHTML = '<option value="">All States</option>' +
            states.map(s => `<option value="${s}">${s}</option>`).join('');
    }
    if (countryFilter) {
        countryFilter.innerHTML = '<option value="">All Countries</option>' +
            countries.map(c => `<option value="${c}">${c}</option>`).join('');
    }
}

// ============================================================
// APPLY FILTERS
// ============================================================
function applyFilters() {
    const searchVal  = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();
    const statusVal  = document.getElementById('filterStatus')?.value || 'all';
    const stateVal   = document.getElementById('filterState')?.value || '';
    const countryVal = document.getElementById('filterCountry')?.value || '';

    filteredData = customersData.filter(c => {
        const matchesSearch = !searchVal || [
            c.name, c.team_academy_name, c.mobile_number, c.city, c.email
        ].some(field => field && field.toLowerCase().includes(searchVal));
        const matchesState   = !stateVal   || c.state   === stateVal;
        const matchesCountry = !countryVal || c.country === countryVal;
        
        let matchesStatus = true;
        const isActive = c.is_active === true || c.is_active === 'true' || c.is_active === 1 || c.is_active === '1';
        if (statusVal === 'active') {
            matchesStatus = isActive;
        } else if (statusVal === 'inactive') {
            matchesStatus = !isActive;
        }
        
        return matchesSearch && matchesState && matchesCountry && matchesStatus;
    });

    currentPage = 1;
    renderTable();
}

// ============================================================
// RENDER TABLE
// ============================================================
function renderTable() {
    const tbody = document.getElementById('customersTableBody');
    const countEl = document.getElementById('filterCount');

    if (countEl) {
        countEl.textContent = `Showing ${filteredData.length} of ${customersData.length} customers`;
    }

    if (!filteredData.length) {
        tbody.innerHTML = `
            <tr><td colspan="7">
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                        </svg>
                    </div>
                    <h3>No customers found</h3>
                    <p>Try adjusting your search or filter, or add a new customer.</p>
                    <button class="btn btn-primary btn-sm mt-12" onclick="openAddModal()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Customer
                    </button>
                </div>
            </td></tr>`;
        renderPagination(0);
        return;
    }

    const start = (currentPage - 1) * ROWS_PER_PAGE;
    const pageData = filteredData.slice(start, start + ROWS_PER_PAGE);

    tbody.innerHTML = pageData.map(c => {
        const initials = c.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        const avatarColor = stringToColor(c.name);
        
        const isActive = c.is_active === true || c.is_active === 'true' || c.is_active === 1 || c.is_active === '1';
        const trClass = isActive ? '' : 'class="tr-inactive"';
        
        const statusHtml = isActive 
            ? `<span class="status-badge active">Active</span>`
            : `<span class="status-badge inactive">Deactivated</span>`;
            
        const score = parseInt(c.profile_score || 0);
        let scoreClass = 'score-low';
        if (score >= 80) scoreClass = 'score-high';
        else if (score >= 40) scoreClass = 'score-medium';
        
        const scoreHtml = `
            <div class="profile-score-container">
                <div class="profile-score-bar-bg">
                    <div class="profile-score-bar-fill ${scoreClass}" style="width: ${score}%"></div>
                </div>
                <div class="profile-score-val">${score}%</div>
            </div>
        `;
        
        return `
            <tr ${trClass}>
                <td>
                    <div class="customer-name-cell">
                        <div class="customer-avatar" style="background: ${avatarColor}">${initials}</div>
                        <div class="customer-name-text">
                            <div class="name">${escHtml(c.name)}</div>
                            <div class="team">${escHtml(c.team_academy_name)}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="font-mono" style="font-size:0.82rem">${escHtml(c.country_code)} ${escHtml(c.mobile_number)}</div>
                    ${c.email ? `<div style="font-size:0.73rem;color:var(--text-muted)">${escHtml(c.email)}</div>` : ''}
                </td>
                <td>
                    <div>${escHtml(c.city)}, ${escHtml(c.state)}</div>
                    <div style="font-size:0.73rem;color:var(--text-muted)">${escHtml(c.country)}</div>
                </td>
                <td>${statusHtml}</td>
                <td>${scoreHtml}</td>
                <td class="muted" style="font-size:0.73rem">${formatDate(c.created_at)}</td>
                <td>
                    <div class="d-flex gap-8">
                        <button class="btn-icon" title="View" onclick="window.location.href='pages/customer-detail.php?id=${c.id}'">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        ${canPerform('edit') ? `
                            <button class="btn-icon" title="Edit" onclick="editCustomer('${c.id}')">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </button>
                        ` : ''}
                        ${canPerform('toggle_status') ? `
                            <button class="btn btn-sm ${isActive ? 'btn-warning' : 'btn-success'}" onclick="confirmToggleStatus('${c.id}', ${JSON.stringify(c.name)}, ${isActive})">
                                ${isActive ? 'Deactivate' : 'Activate'}
                            </button>
                        ` : ''}
                        ${canPerform('delete') ? `
                            <button class="btn-icon danger" title="Delete Customer" onclick="confirmDelete('${c.id}', ${JSON.stringify(c.name)})">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M7 6V4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v2"/></svg>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>`;
    }).join('');

    renderPagination(filteredData.length);
}

// ============================================================
// PAGINATION
// ============================================================
function renderPagination(total) {
    const container = document.getElementById('pagination');
    if (!container) return;
    const totalPages = Math.ceil(total / ROWS_PER_PAGE);
    const pageInfoEl = document.getElementById('pageInfo');
    if (pageInfoEl) {
        const start = total === 0 ? 0 : (currentPage - 1) * ROWS_PER_PAGE + 1;
        const end   = Math.min(currentPage * ROWS_PER_PAGE, total);
        pageInfoEl.textContent = total === 0 ? 'No records' : `${start}–${end} of ${total}`;
    }
    if (totalPages <= 1) { container.innerHTML = ''; return; }

    let html = `<button class="page-btn" onclick="goToPage(${currentPage-1})" ${currentPage===1?'disabled':''}><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12"><polyline points="15 18 9 12 15 6"/></svg></button>`;
    const pages = getPageRange(currentPage, totalPages);
    pages.forEach(p => {
        if (p === '...') {
            html += `<span class="page-btn" style="border:none;cursor:default">…</span>`;
        } else {
            html += `<button class="page-btn ${p===currentPage?'active':''}" onclick="goToPage(${p})">${p}</button>`;
        }
    });
    html += `<button class="page-btn" onclick="goToPage(${currentPage+1})" ${currentPage===totalPages?'disabled':''}><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="12"><polyline points="9 18 15 12 9 6"/></svg></button>`;
    container.innerHTML = html;
}

function getPageRange(current, total) {
    if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
    if (current <= 4) return [1, 2, 3, 4, 5, '...', total];
    if (current >= total - 3) return [1, '...', total-4, total-3, total-2, total-1, total];
    return [1, '...', current-1, current, current+1, '...', total];
}

function goToPage(page) {
    const totalPages = Math.ceil(filteredData.length / ROWS_PER_PAGE);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    renderTable();
    document.querySelector('.card')?.scrollIntoView({behavior: 'smooth', block: 'start'});
}

// ============================================================
// MODAL: ADD CUSTOMER
// ============================================================
function openAddModal() {
    editingId = null;
    document.getElementById('modalTitle').textContent  = 'Add New Customer';
    document.getElementById('modalSubtitle').textContent = 'Fill in the details to create a new customer profile';
    document.getElementById('customerForm').reset();
    clearFormErrors();
    updateFormScoreIndicator();
    document.getElementById('customerModal').classList.add('active');
}

// ============================================================
// MODAL: EDIT CUSTOMER
// ============================================================
async function editCustomer(id) {
    const customer = customersData.find(c => c.id === id);
    if (!customer) return;

    editingId = id;
    document.getElementById('modalTitle').textContent  = 'Edit Customer';
    document.getElementById('modalSubtitle').textContent = `Editing profile for ${customer.name}`;

    document.getElementById('fieldName').value          = customer.name || '';
    document.getElementById('fieldTeam').value          = customer.team_academy_name || '';
    document.getElementById('fieldEmail').value         = customer.email || '';
    document.getElementById('fieldCountryCode').value   = customer.country_code || '+91';
    document.getElementById('fieldMobile').value        = customer.mobile_number || '';
    document.getElementById('fieldAddress1').value      = customer.address_line_1 || '';
    document.getElementById('fieldAddress2').value      = customer.address_line_2 || '';
    document.getElementById('fieldCity').value          = customer.city || '';
    document.getElementById('fieldState').value         = customer.state || '';
    document.getElementById('fieldPincode').value       = customer.pincode || '';
    document.getElementById('fieldCountry').value       = customer.country || '';

    clearFormErrors();
    updateFormScoreIndicator();
    document.getElementById('customerModal').classList.add('active');
}

// ============================================================
// MODAL: VIEW CUSTOMER
// ============================================================
function viewCustomer(id) {
    const c = customersData.find(c => c.id === id);
    if (!c) return;
    const initials = c.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);

    document.getElementById('viewContent').innerHTML = `
        <div class="profile-header">
            <div class="profile-avatar-lg">${initials}</div>
            <div class="profile-header-info">
                <h2>${escHtml(c.name)}</h2>
                <div class="team">${escHtml(c.team_academy_name)}</div>
                <div class="meta">ID: ${c.id?.slice(0,8).toUpperCase()} · Created ${formatDate(c.created_at)} · Updated ${formatDate(c.updated_at)}</div>
            </div>
        </div>
        <div style="padding:22px">
            <div class="section-divider"><h4><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Contact Information</h4></div>
            <div class="info-grid">
                <div class="info-item"><div class="info-label">Full Name</div><div class="info-value fw-600">${escHtml(c.name)}</div></div>
                <div class="info-item"><div class="info-label">Team / Academy</div><div class="info-value">${escHtml(c.team_academy_name)}</div></div>
                <div class="info-item"><div class="info-label">Email</div><div class="info-value">${c.email ? `<a href="mailto:${escHtml(c.email)}" style="color:var(--accent)">${escHtml(c.email)}</a>` : '<span class="text-muted">—</span>'}</div></div>
                <div class="info-item"><div class="info-label">Mobile</div><div class="info-value font-mono">${escHtml(c.country_code)} ${escHtml(c.mobile_number)}</div></div>
            </div>
            <div class="section-divider"><h4><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Shipping Address</h4></div>
            <div class="info-grid">
                <div class="info-item"><div class="info-label">Address</div><div class="info-value">${escHtml(c.address_line_1)}</div></div>
                ${c.address_line_2 ? `<div class="info-item"><div class="info-label">Address 2</div><div class="info-value">${escHtml(c.address_line_2)}</div></div>` : ''}
                <div class="info-item"><div class="info-label">City</div><div class="info-value">${escHtml(c.city)}</div></div>
                <div class="info-item"><div class="info-label">State</div><div class="info-value">${escHtml(c.state)}</div></div>
                <div class="info-item"><div class="info-label">Pincode</div><div class="info-value font-mono">${escHtml(c.pincode)}</div></div>
                <div class="info-item"><div class="info-label">Country</div><div class="info-value">${escHtml(c.country)}</div></div>
            </div>
        </div>`;
    document.getElementById('viewModal').classList.add('active');
}

// ============================================================
// PROFILE SCORING (Client-side live updates)
// ============================================================
function calculateFormProfileScore() {
    let score = 0;
    
    const name = document.getElementById('fieldName')?.value.trim() || '';
    const team = document.getElementById('fieldTeam')?.value.trim() || '';
    const email = document.getElementById('fieldEmail')?.value.trim() || '';
    const mobile = document.getElementById('fieldMobile')?.value.trim() || '';
    const addr1 = document.getElementById('fieldAddress1')?.value.trim() || '';
    const addr2 = document.getElementById('fieldAddress2')?.value.trim() || '';
    const city = document.getElementById('fieldCity')?.value.trim() || '';
    const state = document.getElementById('fieldState')?.value.trim() || '';
    const pincode = document.getElementById('fieldPincode')?.value.trim() || '';
    const country = document.getElementById('fieldCountry')?.value.trim() || '';

    if (name.length >= 3) score += 15;
    if (team.length > 0) score += 10;
    if (email.length > 0) score += 15;
    if (mobile.length >= 7) score += 15;
    if (addr1.length > 0) score += 10;
    if (addr2.length > 0) score += 5;
    if (city.length > 0) score += 10;
    if (state.length > 0) score += 5;
    if (pincode.length > 0) score += 5;
    if (country.length > 0) score += 5;
    score += 5; // Active by default during form filling

    return score;
}

function updateFormScoreIndicator() {
    const score = calculateFormProfileScore();
    const percentEl = document.getElementById('formScorePercent');
    const ringEl = document.getElementById('formScoreRing');
    
    if (percentEl) percentEl.textContent = `${score}%`;
    if (ringEl) {
        const strokeDashoffset = 125.66 - (score / 100) * 125.66;
        ringEl.style.strokeDashoffset = strokeDashoffset;
        
        if (score < 40) {
            ringEl.style.stroke = '#ef4444';
        } else if (score < 80) {
            ringEl.style.stroke = '#f59e0b';
        } else {
            ringEl.style.stroke = '#10b981';
        }
    }
}

// ============================================================

function confirmToggleStatus(id, name, currentStatus) {
    const statusModal = document.getElementById('statusModal');
    const titleEl = document.getElementById('statusModalTitle');
    const msgEl = document.getElementById('statusModalMessage');
    const confirmBtn = document.getElementById('confirmStatusBtn');
    const nameEl = document.getElementById('statusCustomerName');

    if (!statusModal || !confirmBtn) return;

    const newStatus = !currentStatus;
    const statusWord = newStatus ? 'Activate' : 'Deactivate';

    titleEl.textContent = `${statusWord} Customer?`;
    if (nameEl) nameEl.textContent = name;
    msgEl.innerHTML = `Are you sure you want to <strong>${statusWord.toLowerCase()}</strong> the customer profile for <strong>${escHtml(name)}</strong>?`;

    confirmBtn.className = newStatus ? 'btn btn-primary' : 'btn btn-warning';
    confirmBtn.onclick = () => toggleCustomerStatus(id, newStatus);
    statusModal.classList.add('active');
}

async function toggleCustomerStatus(id, newStatus) {
    const btn = document.getElementById('confirmStatusBtn');
    btn.disabled = true;
    const statusWord = newStatus ? 'Activating' : 'Deactivating';
    btn.innerHTML = `<svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> ${statusWord}…`;
    try {
        const res = await apiFetch(API_BASE + 'customers.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'toggle_status', id, is_active: newStatus})
        });
        const json = await res.json();
        if (json.success) {
            showToast('Customer status updated successfully', 'success');
            closeAllModals();
            await loadCustomers();
            loadStats();
            if (typeof loadChartData === 'function') loadChartData();
        } else {
            showToast(json.message || 'Status update failed', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
        console.error(err);
    }
    btn.disabled = false;
    btn.innerHTML = 'Confirm';
}
// ============================================================

// ============================================================
// FORM SUBMIT
// ============================================================
async function handleFormSubmit(e) {
    e.preventDefault();
    if (!validateForm()) return;

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = `<svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Saving…`;

    const data = {
        action:            editingId ? 'update' : 'create',
        id:                editingId || null,
        name:              document.getElementById('fieldName').value.trim(),
        team_academy_name: document.getElementById('fieldTeam').value.trim(),
        email:             document.getElementById('fieldEmail').value.trim() || null,
        country_code:      document.getElementById('fieldCountryCode').value,
        mobile_number:     document.getElementById('fieldMobile').value.trim(),
        address_line_1:    document.getElementById('fieldAddress1').value.trim(),
        address_line_2:    document.getElementById('fieldAddress2').value.trim() || null,
        city:              document.getElementById('fieldCity').value.trim(),
        state:             document.getElementById('fieldState').value.trim(),
        pincode:           document.getElementById('fieldPincode').value.trim(),
        country:           document.getElementById('fieldCountry').value.trim(),
    };

    try {
        const res = await apiFetch(API_BASE + 'customers.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            showToast(editingId ? 'Customer updated successfully' : 'Customer created successfully', 'success');
            closeAllModals();
            await loadCustomers();
            loadStats();
            if (typeof loadChartData === 'function') loadChartData();
        } else { showToast(json.message || 'Save failed', 'error'); }
    } catch (err) { showToast('Network error. Please try again.', 'error'); }

    submitBtn.disabled = false;
    submitBtn.innerHTML = editingId
        ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Update Customer`
        : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> Save Customer`;
}

// ============================================================
// FORM VALIDATION
// ============================================================
function validateForm() {
    clearFormErrors();
    let valid = true;
    const rules = [
        { id: 'fieldName',    label: 'Customer Name',   required: true, min: 3 },
        { id: 'fieldTeam',    label: 'Team/Academy',    required: true },
        { id: 'fieldEmail',   label: 'Email',           required: false, email: true },
        { id: 'fieldCountryCode', label: 'Country Code', required: true },
        { id: 'fieldMobile',  label: 'Mobile Number',   required: true, numeric: true },
        { id: 'fieldAddress1', label: 'Address Line 1', required: true },
        { id: 'fieldCity',    label: 'City',            required: true },
        { id: 'fieldState',   label: 'State',           required: true },
        { id: 'fieldPincode', label: 'Pincode',         required: true, numeric: true },
        { id: 'fieldCountry', label: 'Country',         required: true },
    ];
    rules.forEach(rule => {
        const el = document.getElementById(rule.id);
        if (!el) return;
        const val = el.value.trim();
        if (rule.required && !val) { setError(el, `${rule.label} is required`); valid = false; }
        else if (val && rule.min && val.length < rule.min) { setError(el, `${rule.label} must be at least ${rule.min} characters`); valid = false; }
        else if (val && rule.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) { setError(el, 'Please enter a valid email address'); valid = false; }
        else if (val && rule.numeric && !/^[0-9]+$/.test(val)) { setError(el, `${rule.label} must contain numbers only`); valid = false; }
        else if (val && rule.id === 'fieldMobile' && (val.length < 7 || val.length > 15)) { setError(el, 'Mobile number must be between 7 and 15 digits'); valid = false; }
        else if (val) { el.classList.add('is-valid'); }
    });
    return valid;
}

function setError(el, msg) {
    el.classList.add('is-invalid');
    const errEl = el.closest('.form-group')?.querySelector('.form-error');
    if (errEl) errEl.textContent = msg;
}

function clearFormErrors() {
    document.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid', 'is-valid'));
    document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
}

// ============================================================
// CLOSE MODALS
// ============================================================
function closeAllModals() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
    clearFormErrors();
}

// ============================================================
// EXPORT CSV
// ============================================================
function exportCSV() {
    if (!filteredData.length) { showToast('No data to export', 'warning'); return; }
    const headers = ['ID','Name','Team/Academy','Email','Country Code','Mobile','Address 1','Address 2','City','State','Pincode','Country','Status','Profile Score','Created At'];
    const rows = filteredData.map(c => {
        const isActive = c.is_active === true || c.is_active === 'true' || c.is_active === 1 || c.is_active === '1';
        return [
            c.id, c.name, c.team_academy_name, c.email || '',
            c.country_code, c.mobile_number, c.address_line_1, c.address_line_2 || '',
            c.city, c.state, c.pincode, c.country,
            (isActive ? 'Active' : 'Deactivated'),
            (c.profile_score || 0) + '%',
            formatDate(c.created_at)
        ].map(v => `"${String(v).replace(/"/g, '""')}"`);
    });
    const csv = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `customers_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
    URL.revokeObjectURL(a.href);
    showToast(`Exported ${filteredData.length} records`, 'success');
}

// ============================================================
// TABLE STATES
// ============================================================
function showTableSkeleton() {
    const tbody = document.getElementById('customersTableBody');
    if (!tbody) return;
    tbody.innerHTML = Array(5).fill(`<tr>${Array(7).fill(`<td><div class="skeleton skeleton-cell" style="width:80%;height:14px"></div></td>`).join('')}</tr>`).join('');
}

function showTableError(msg) {
    const tbody = document.getElementById('customersTableBody');
    if (!tbody) return;
    tbody.innerHTML = `<tr><td colspan="7">
        <div class="empty-state">
            <div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
            <h3>Something went wrong</h3>
            <p>${escHtml(msg)}</p>
            <button class="btn btn-secondary btn-sm" onclick="loadCustomers()">Try Again</button>
        </div>
    </td></tr>`;
}

// ============================================================
// TOAST
// ============================================================
function showToast(message, type = 'info') {
    const icons = {
        success: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`,
        error:   `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
        warning: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
        info:    `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`,
    };
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = (icons[type] || icons.info) + `<span>${escHtml(message)}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'toastOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// ============================================================
// CHARTS (Dashboard only)
// ============================================================
let countryChartInstance = null;
let monthChartInstance = null;

async function loadChartData() {
    try {
        const res = await apiFetch(API_BASE + 'chart-data.php');
        const json = await res.json();
        if (json.success && json.data) {
            renderCountryChart(json.data.by_country);
            renderMonthChart(json.data.by_month);
        }
    } catch (err) { console.error('Chart data error:', err); }
}

function getChartColors() {
    const isDark = ThemeManager.get() === 'dark';
    return {
        grid: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)',
        text: isDark ? '#94a3b8' : '#64748b',
        bg: isDark ? '#1e293b' : '#ffffff',
        bars: ['#ef5350','#42a5f5','#66bb6a','#ffa726','#ab47bc','#26c6da','#ec407a','#8d6e63','#78909c','#d4e157'],
    };
}

function renderCountryChart(data) {
    const ctx = document.getElementById('countryChart');
    if (!ctx) return;
    const colors = getChartColors();
    if (countryChartInstance) countryChartInstance.destroy();
    countryChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values,
                backgroundColor: colors.bars.slice(0, data.labels.length),
                borderWidth: 2,
                borderColor: colors.bg,
                hoverOffset: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: colors.text, padding: 14, font: { family: 'Inter', size: 11, weight: '500' }, usePointStyle: true, pointStyleWidth: 8 } },
            },
            cutout: '60%',
        }
    });
}

function renderMonthChart(data) {
    const ctx = document.getElementById('monthChart');
    if (!ctx) return;
    const colors = getChartColors();
    if (monthChartInstance) monthChartInstance.destroy();
    monthChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'New Customers',
                data: data.values,
                backgroundColor: 'rgba(229,57,53,0.75)',
                borderColor: '#e53935',
                borderWidth: 1,
                borderRadius: 6,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: colors.grid }, ticks: { color: colors.text, font: { family: 'Inter', size: 11 }, stepSize: 1 } },
                x: { grid: { display: false }, ticks: { color: colors.text, font: { family: 'Inter', size: 11 } } },
            },
        }
    });
}

function updateChartColors() {
    if (countryChartInstance || monthChartInstance) {
        // Re-render with new colors by reloading chart data
        loadChartData();
    }
}

// ============================================================
// HELPERS
// ============================================================
function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-IN', {day: '2-digit', month: 'short', year: 'numeric'});
}

function stringToColor(str) {
    const colors = ['#1e293b','#374151','#1e40af','#065f46','#6b21a8','#166534','#9a3412','#334155'];
    let hash = 0;
    for (let i = 0; i < str.length; i++) hash = str.charCodeAt(i) + ((hash << 5) - hash);
    return colors[Math.abs(hash) % colors.length];
}

function debounce(fn, delay) {
    let timer;
    return (...args) => { clearTimeout(timer); timer = setTimeout(() => fn(...args), delay); };
}

// ============================================================
// DELETE CUSTOMER
// ============================================================
function confirmDelete(id, name) {
    const modal = document.getElementById('deleteModal');
    const nameEl = document.getElementById('deleteCustomerName');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (!modal || !confirmBtn) return;

    if (nameEl) nameEl.textContent = name;
    confirmBtn.onclick = () => deleteCustomer(id, name);
    modal.classList.add('active');
}

async function deleteCustomer(id, name) {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = `<svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Deleting…`;
    }

    try {
        const res = await apiFetch(API_BASE + `customer.php?action=delete&id=${id}`, {
            method: 'DELETE'
        });
        const json = await res.json();
        if (json.success) {
            showToast(`Customer "${name}" deleted successfully`, 'success');
            closeAllModals();
            // If on detail page, redirect to dashboard
            if (window.location.pathname.includes('customer-detail.php')) {
                setTimeout(() => { window.location.href = '../dashboard.php'; }, 1000);
            } else {
                await loadCustomers();
                loadStats();
                if (typeof loadChartData === 'function') loadChartData();
            }
        } else {
            showToast(json.message || 'Delete failed', 'error');
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'error');
        console.error(err);
    }

    if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg> Delete Customer`;
    }
}

// Spin + toast-out animations
const style = document.createElement('style');
style.textContent = `
    .spin { animation: spin 0.8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    @keyframes toastOut { to { opacity: 0; transform: translateX(20px); } }
    .mt-12 { margin-top: 12px; }
`;
document.head.appendChild(style);
