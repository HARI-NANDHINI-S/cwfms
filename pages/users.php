<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user'])) { header('Location: ../login.php'); exit; }

$user = getCurrentUser();
if ($user['role'] !== 'admin') { header('Location: ../dashboard.php?error=permission'); exit; }

$initials = implode('', array_map(fn($p) => strtoupper($p[0]), array_filter(explode(' ', $user['name']))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Customer Management</title>
    <script>(function(){var t=localStorage.getItem('cm-theme');if(t){document.documentElement.setAttribute('data-theme',t)}else if(window.matchMedia('(prefers-color-scheme:dark)').matches){document.documentElement.setAttribute('data-theme','dark')}else{document.documentElement.setAttribute('data-theme','light')}})();</script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23e53935'/><text y='.9em' font-size='80' x='10'>S3</text></svg>">
    <style>
        .role-badge { font-size: 0.7rem; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
        .role-badge.admin   { background: rgba(229,57,53,0.12); color: var(--accent); }
        .role-badge.manager { background: rgba(30,136,229,0.12); color: #1e88e5; }
        .role-badge.staff   { background: rgba(67,160,71,0.12); color: #43a047; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
        .status-dot.active { background: #43a047; }
        .status-dot.inactive { background: var(--text-muted); }
    </style>
</head>
<body>
<div class="app-wrapper">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">S3</div>
            <div class="brand-text"><h2>Studio 3</h2><span>Order Management</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="../dashboard.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                Customers
            </a>
            <a href="import.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Import CSV
            </a>
            <div class="nav-section-label">Admin</div>
            <a href="users.php" class="nav-item active" style="border-left-color:var(--accent)">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                User Management
            </a>
        </nav>
        <div class="theme-toggle-wrap">
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
                <svg class="theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                <svg class="theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <span class="theme-label-text">Light Mode</span>
                <div class="toggle-track"></div>
            </button>
        </div>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="user-role"><?= htmlspecialchars($user['role']) ?></div>
                </div>
                <a href="../logout.php" style="margin-left:auto;color:var(--text-muted)" title="Logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </a>
            </div>
        </div>
    </aside>

    <div class="main-content">
        <header class="top-header">
            <div class="header-title">
                <div class="breadcrumb" style="font-size:0.83rem">
                    <a href="../dashboard.php" style="color:var(--text-muted);text-decoration:none">Dashboard</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="9 18 15 12 9 6"/></svg>
                    <span>User Management</span>
                </div>
                <h1 style="margin-top:4px">User Management</h1>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary btn-sm" onclick="openAddUserModal()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add User
                </button>
            </div>
        </header>

        <div class="page-content">
            <div class="card">
                <div class="card-header-simple">
                    <h3>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        System Users
                    </h3>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th style="width:140px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr><td colspan="6"><div class="empty-state"><h3>Loading…</h3></div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Permissions Reference -->
            <div class="card" style="margin-top:20px">
                <div class="card-header-simple"><h3>Role Permissions Reference</h3></div>
                <div style="padding:20px">
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px">
                        <?php
                        $roles = [
                            'admin'   => ['Create', 'Edit', 'Delete', 'View', 'User Management'],
                            'manager' => ['Create', 'Edit', 'View'],
                            'staff'   => ['Create', 'View'],
                        ];
                        $colors = ['admin' => 'red', 'manager' => 'blue', 'staff' => 'green'];
                        foreach ($roles as $role => $perms): ?>
                        <div class="stat-card" style="flex-direction:column;align-items:flex-start;gap:10px;padding:16px">
                            <div style="display:flex;align-items:center;gap:8px">
                                <div class="stat-icon <?= $colors[$role] ?>" style="width:32px;height:32px;font-size:0.7rem;font-weight:700"><?= strtoupper($role[0]) ?></div>
                                <div style="font-weight:600;font-size:0.9rem"><?= ucfirst($role) ?></div>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:4px">
                                <?php foreach ($perms as $perm): ?>
                                <div style="display:flex;align-items:center;gap:6px;font-size:0.8rem;color:var(--text-secondary)">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#43a047" stroke-width="2.5" width="12"><polyline points="20 6 9 17 4 12"/></svg>
                                    <?= $perm ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal modal-sm">
        <div class="modal-header">
            <div><h3>Add New User</h3><p>Create a system user account</p></div>
            <button class="modal-close" onclick="closeModal('addUserModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" class="form-control" id="newUserName" placeholder="e.g. John Doe">
            </div>
            <div class="form-group">
                <label class="form-label">Email Address <span class="required">*</span></label>
                <input type="email" class="form-control" id="newUserEmail" placeholder="user@studio3.com">
            </div>
            <div class="form-group">
                <label class="form-label">Role <span class="required">*</span></label>
                <select class="form-control" id="newUserRole">
                    <option value="staff">Staff – Create, View</option>
                    <option value="manager">Manager – Create, Edit, View</option>
                    <option value="admin">Admin – Full Access</option>
                </select>
            </div>
            <div style="background:rgba(251,140,0,0.08);border:1px solid rgba(251,140,0,0.2);border-radius:8px;padding:10px 12px;font-size:0.78rem;color:#e65100;margin-top:8px">
                <strong>Note:</strong> Default password is <code>password123</code>. Ask users to change it in a production environment.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('addUserModal')">Cancel</button>
            <button class="btn btn-primary" onclick="createUser()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Create User
            </button>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="../assets/js/animations.js"></script>
<script src="../assets/js/app.js"></script>
<script>
async function loadUsers() {
    const res  = await apiFetch('../api/auth.php?action=users');
    const json = await res.json();
    if (!json.success) { showToast('Failed to load users', 'error'); return; }

    const tbody = document.getElementById('usersTableBody');
    const users = json.data;

    if (!users.length) {
        tbody.innerHTML = `<tr><td colspan="6"><div class="empty-state"><h3>No users found</h3></div></td></tr>`;
        return;
    }

    tbody.innerHTML = users.map(u => `
        <tr>
            <td>
                <div class="customer-name-cell">
                    <div class="customer-avatar" style="background:#37474f">${u.user_name.split(' ').map(n=>n[0]).join('').toUpperCase().slice(0,2)}</div>
                    <div class="customer-name-text"><div class="name">${escHtml(u.user_name)}</div></div>
                </div>
            </td>
            <td style="font-size:0.82rem">${escHtml(u.user_email)}</td>
            <td><span class="role-badge ${u.role}">${u.role}</span></td>
            <td>
                <div style="display:flex;align-items:center;gap:6px;font-size:0.8rem">
                    <span class="status-dot ${u.is_active ? 'active' : 'inactive'}"></span>
                    ${u.is_active ? 'Active' : 'Inactive'}
                </div>
            </td>
            <td class="muted" style="font-size:0.75rem">${new Date(u.created_at).toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'})}</td>
            <td>
                <div class="d-flex gap-8">
                    <select class="filter-select" style="font-size:0.75rem;padding:4px 8px;height:30px"
                            onchange="updateRole('${u.id}', this.value)">
                        <option value="staff" ${u.role==='staff'?'selected':''}>Staff</option>
                        <option value="manager" ${u.role==='manager'?'selected':''}>Manager</option>
                        <option value="admin" ${u.role==='admin'?'selected':''}>Admin</option>
                    </select>
                    <button class="btn-icon ${u.is_active ? 'danger' : ''}" title="${u.is_active ? 'Deactivate' : 'Activate'}"
                            onclick="toggleUser('${u.id}', ${!u.is_active})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14">
                            ${u.is_active
                                ? '<circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>'
                                : '<polyline points="20 6 9 17 4 12"/>'}
                        </svg>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function updateRole(userId, role) {
    const res  = await apiFetch('../api/auth.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'update_role', user_id:userId, role})
    });
    const json = await res.json();
    showToast(json.message, json.success ? 'success' : 'error');
}

async function toggleUser(userId, isActive) {
    const res  = await apiFetch('../api/auth.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'toggle_user', user_id:userId, is_active:isActive})
    });
    const json = await res.json();
    if (json.success) { loadUsers(); showToast('User status updated', 'success'); }
    else showToast('Failed', 'error');
}

async function createUser() {
    const name  = document.getElementById('newUserName').value.trim();
    const email = document.getElementById('newUserEmail').value.trim();
    const role  = document.getElementById('newUserRole').value;
    if (!name || !email) { showToast('Name and email required', 'error'); return; }

    const res  = await apiFetch('../api/auth.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({action:'create_user', name, email, role})
    });
    const json = await res.json();
    if (json.success) {
        closeModal('addUserModal');
        loadUsers();
        showToast('User created successfully', 'success');
    } else {
        showToast(json.message || 'Failed to create user', 'error');
    }
}

function openAddUserModal() { document.getElementById('addUserModal').classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

loadUsers();
</script>
</body>
</html>
