<?php
require_once 'includes/config.php';

// Auth guard
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$initials = implode('', array_map(fn($p) => strtoupper($p[0]), array_filter(explode(' ', $user['name']))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Ira Sportswear Lead Management</title>
    <meta name="description" content="Ira Sportswear Lead Management - Customer profiles, leads, pipelines, and athletic team orders">
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="shortcut icon" href="assets/favicon.ico">
    <meta name="csrf-token" content="<?= getCsrfToken() ?>">
    <script>
        (function(){var t=localStorage.getItem('cm-theme');if(t){document.documentElement.setAttribute('data-theme',t)}else if(window.matchMedia('(prefers-color-scheme:dark)').matches){document.documentElement.setAttribute('data-theme','dark')}else{document.documentElement.setAttribute('data-theme','light')}})();
    </script>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="app-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="assets/ira_logo_web.png" alt="Ira Sportswear Logo" class="brand-logo-img">
            <div class="brand-text">
                <h2>Ira Sportswear</h2>
                <span>Lead Management</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Public</div>
            <a href="index.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Home
            </a>
            <a href="about.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                About
            </a>
            <div class="nav-section-label" style="margin-top:8px">Management</div>

            <a href="dashboard.php" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                Dashboard
            </a>

            <a href="dashboard.php" class="nav-item active" style="border-left-color:var(--accent)">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Customers
                <span class="nav-badge" id="navBadge">–</span>
            </a>

            <?php if (hasPermission('create')): ?>
            <a href="pages/import.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="17 8 12 3 7 8"/>
                    <line x1="12" y1="3" x2="12" y2="15"/>
                </svg>
                Import CSV
            </a>
            <?php endif; ?>

            <div class="nav-section-label" style="margin-top:8px">Operations</div>

            <a href="pages/leads.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Leads
            </a>
            <span class="nav-item" style="opacity:0.4;cursor:not-allowed">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Orders
            </span>
            <span class="nav-item" style="opacity:0.4;cursor:not-allowed">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Payments
            </span>

            <?php if (canManageUsers($user)): ?>
            <div class="nav-section-label" style="margin-top:8px">Admin</div>
            <a href="pages/users.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                User Management
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <!-- Theme Toggle -->
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme" style="width:100%;margin-bottom:10px">
                <svg class="theme-icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                <svg class="theme-icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <span class="theme-label-text">Light Mode</span>
                <div class="toggle-track"></div>
            </button>
            <div class="user-card">
                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="user-role">
                        <?= htmlspecialchars($user['role_label'] ?? getRoleLabel($user['role'])) ?>
                    </div>
                </div>
                <a href="logout.php" style="margin-left:auto;color:var(--text-muted);opacity:0.7" title="Logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- TOP HEADER -->
        <header class="top-header">
            <div class="header-title">
                <h1>Customer & Lead Dashboard</h1>
                <div class="breadcrumb">
                    <span>Ira Sportswear</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="9 18 15 12 9 6"/></svg>
                    <span>Customers</span>
                    <span class="db-status checking" id="dbStatus" title="Database connection status">
                        <span class="db-status-dot"></span>
                        <span id="dbStatusText">Checking…</span>
                    </span>
                </div>
            </div>
            <div class="header-actions">
                <div class="header-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="headerSearch" placeholder="Quick search customers…" autocomplete="off">
                </div>
                <?php if (hasPermission('create')): ?>
                <button class="btn btn-primary" id="addCustomerBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Customer
                </button>
                <?php endif; ?>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <div class="page-content">

            <!-- PAGE HEADER -->
            <div class="page-header">
                <div class="page-header-left">
                    <h2>All Customers</h2>
                    <p>Manage customer profiles, contact information, and shipping addresses</p>
                </div>
                <div class="d-flex gap-8">
                    <button class="btn btn-secondary btn-sm" id="exportBtn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Export CSV
                    </button>
                </div>
            </div>

            <!-- STATS GRID -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value" id="statTotal">–</div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value" id="statActive">–</div>
                        <div class="stat-label">Active Customers</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value" id="statInactive">–</div>
                        <div class="stat-label">Inactive Customers</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value" id="statToday">–</div>
                        <div class="stat-label">Added Today</div>
                    </div>
                </div>
            </div>

            <!-- CHARTS -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h3>Monthly Registrations</h3>
                        <span class="badge badge-default">Last 6 Months</span>
                    </div>
                    <div class="chart-card-body">
                        <canvas id="monthChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h3>Customers by Country</h3>
                    </div>
                    <div class="chart-card-body">
                        <canvas id="countryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- CUSTOMER TABLE CARD -->
            <div class="card">
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="search-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" id="searchInput" placeholder="Search by name, mobile, city…" autocomplete="off">
                    </div>
                    <select class="filter-select" id="filterStatus">
                        <option value="all">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Deactivated</option>
                    </select>
                    <select class="filter-select" id="filterState"><option value="">All States</option></select>
                    <select class="filter-select" id="filterCountry"><option value="">All Countries</option></select>
                    <button class="btn btn-ghost btn-sm" onclick="resetFilters()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>
                        Reset
                    </button>
                    <span class="filter-count" id="filterCount">Loading…</span>
                </div>

                <!-- Table -->
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Profile Score</th>
                                <th>Created</th>
                                <th style="width:200px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="customersTableBody">
                            <tr><td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="28" height="28"><circle cx="12" cy="12" r="10"/></svg></div>
                                    <h3>Loading…</h3>
                                </div>
                            </td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Card Footer: Pagination -->
                <div class="card-footer">
                    <span id="pageInfo" class="text-muted" style="font-size:0.8rem"></span>
                    <div class="pagination" id="pagination"></div>
                </div>
            </div>

        </div><!-- /page-content -->
    </div><!-- /main-content -->
</div><!-- /app-wrapper -->

<!-- MODAL: ADD / EDIT CUSTOMER -->
<div class="modal-overlay" id="customerModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div>
                <h3 id="modalTitle">Add New Customer</h3>
                <p id="modalSubtitle">Fill in the details to create a new customer profile</p>
            </div>
            <button class="modal-close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="customerForm" novalidate>
            <div class="modal-body">
                <!-- Live Profile Score Indicator -->
                <div class="form-score-box">
                    <div class="form-score-text">
                        <div class="form-score-title">Profile Completeness Score</div>
                        <div class="form-score-desc">Fill in more information to reach 100% completeness.</div>
                    </div>
                    <div class="form-score-gauge-mini">
                        <svg width="48" height="48" viewBox="0 0 48 48">
                            <circle class="bg-ring" cx="24" cy="24" r="20"></circle>
                            <circle class="fill-ring" id="formScoreRing" cx="24" cy="24" r="20" style="stroke: var(--success);"></circle>
                        </svg>
                        <div class="score-percent" id="formScorePercent">5%</div>
                    </div>
                </div>

                <div class="section-divider">
                    <h4><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Basic Information</h4>
                </div>
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="fieldName">Customer Name <span class="required">*</span></label>
                        <input type="text" class="form-control" id="fieldName" name="name" placeholder="e.g. Rajesh Kumar" maxlength="255" autocomplete="off">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="fieldTeam">Team / Academy Name <span class="required">*</span></label>
                        <input type="text" class="form-control" id="fieldTeam" name="team_academy_name" placeholder="e.g. Chennai FC Academy" maxlength="255" autocomplete="off">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="fieldEmail">Email Address <span class="optional">(Optional)</span></label>
                        <input type="email" class="form-control" id="fieldEmail" name="email" placeholder="customer@example.com" maxlength="255" autocomplete="off">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mobile Number <span class="required">*</span></label>
                        <div class="phone-group">
                            <select class="form-control" id="fieldCountryCode" name="country_code">
                                <option value="+91">🇮🇳 +91 (India)</option>
                                <option value="+971">🇦🇪 +971 (UAE)</option>
                                <option value="+1">🇺🇸 +1 (USA)</option>
                                <option value="+44">🇬🇧 +44 (UK)</option>
                                <option value="+61">🇦🇺 +61 (Australia)</option>
                                <option value="+65">🇸🇬 +65 (Singapore)</option>
                                <option value="+60">🇲🇾 +60 (Malaysia)</option>
                                <option value="+94">🇱🇰 +94 (Sri Lanka)</option>
                                <option value="+880">🇧🇩 +880 (Bangladesh)</option>
                                <option value="+977">🇳🇵 +977 (Nepal)</option>
                                <option value="+33">🇫🇷 +33 (France)</option>
                                <option value="+49">🇩🇪 +49 (Germany)</option>
                                <option value="+81">🇯🇵 +81 (Japan)</option>
                                <option value="+86">🇨🇳 +86 (China)</option>
                                <option value="+966">🇸🇦 +966 (Saudi Arabia)</option>
                            </select>
                            <input type="tel" class="form-control" id="fieldMobile" name="mobile_number" placeholder="9876543210" maxlength="15" autocomplete="off" pattern="[0-9]{7,15}">
                        </div>
                        <span class="form-error"></span>
                        <span class="form-hint">Numbers only, no spaces or dashes</span>
                    </div>
                </div>

                <div class="section-divider">
                    <h4><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Shipping Address</h4>
                </div>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label" for="fieldAddress1">Address Line 1 <span class="required">*</span></label>
                        <input type="text" class="form-control" id="fieldAddress1" name="address_line_1" placeholder="Street address, building name" maxlength="500">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label" for="fieldAddress2">Address Line 2 <span class="optional">(Optional)</span></label>
                        <input type="text" class="form-control" id="fieldAddress2" name="address_line_2" placeholder="Landmark, area, apartment" maxlength="500">
                        <span class="form-error"></span>
                    </div>
                </div>
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label class="form-label" for="fieldCity">City <span class="required">*</span></label>
                        <input type="text" class="form-control" id="fieldCity" name="city" placeholder="e.g. Coimbatore" maxlength="100">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="fieldState">State / Province <span class="required">*</span></label>
                        <input type="text" class="form-control" id="fieldState" name="state" placeholder="e.g. Tamil Nadu" maxlength="100">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="fieldPincode">Pincode <span class="required">*</span></label>
                        <input type="text" class="form-control" id="fieldPincode" name="pincode" placeholder="e.g. 641002" maxlength="20" pattern="[0-9]+">
                        <span class="form-error"></span>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="fieldCountry">Country <span class="required">*</span></label>
                        <select class="form-control" id="fieldCountry" name="country">
                            <option value="">— Select Country —</option>
                            <option value="India">India</option>
                            <option value="UAE">UAE (United Arab Emirates)</option>
                            <option value="USA">USA (United States)</option>
                            <option value="UK">United Kingdom</option>
                            <option value="Australia">Australia</option>
                            <option value="Singapore">Singapore</option>
                            <option value="Malaysia">Malaysia</option>
                            <option value="Sri Lanka">Sri Lanka</option>
                            <option value="Bangladesh">Bangladesh</option>
                            <option value="Nepal">Nepal</option>
                            <option value="Canada">Canada</option>
                            <option value="France">France</option>
                            <option value="Germany">Germany</option>
                            <option value="Japan">Japan</option>
                            <option value="China">China</option>
                            <option value="Saudi Arabia">Saudi Arabia</option>
                            <option value="Other">Other</option>
                        </select>
                        <span class="form-error"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-action="cancel">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Save Customer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: VIEW CUSTOMER -->
<div class="modal-overlay" id="viewModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div><h3>Customer Profile</h3><p>Complete customer information</p></div>
            <button class="modal-close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="modal-body" id="viewContent" style="padding:0"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" data-action="cancel">Close</button>
        </div>
    </div>
</div>

<!-- MODAL: TOGGLE STATUS CONFIRM -->
<div class="modal-overlay" id="statusModal">
    <div class="modal modal-sm">
        <div class="modal-body" style="text-align:center;padding:32px 28px">
            <div class="status-toggle-icon" style="margin-bottom: 16px; font-size: 2.5rem; color: var(--accent);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <h3 style="font-size:1.05rem;margin-bottom:8px" id="statusModalTitle">Change Status?</h3>
            <p style="color:var(--text-secondary);font-size:0.875rem;margin-bottom:24px" id="statusModalMessage">
                Are you sure you want to change the status of <strong id="statusCustomerName"></strong>?
            </p>
            <div style="display:flex;gap:10px;justify-content:center">
                <button class="btn btn-secondary" data-action="cancel">Cancel</button>
                <button class="btn btn-primary" id="confirmStatusBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: DELETE CONFIRM -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal modal-sm">
        <div class="modal-body" style="text-align:center;padding:32px 28px">
            <div class="status-toggle-icon" style="margin-bottom: 16px; font-size: 2.5rem; color: var(--danger);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="48" height="48">
                    <path d="M3 6h18"/>
                    <path d="M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6"/>
                    <path d="M10 11v6"/>
                    <path d="M14 11v6"/>
                </svg>
            </div>
            <h3 style="font-size:1.05rem;margin-bottom:8px" id="deleteModalTitle">Delete Customer?</h3>
            <p style="color:var(--text-secondary);font-size:0.875rem;margin-bottom:24px" id="deleteModalMessage">
                Are you sure you want to delete <strong id="deleteCustomerName"></strong>?
            </p>
            <div style="display:flex;gap:10px;justify-content:center">
                <button class="btn btn-secondary" data-action="cancel">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toastContainer"></div>

<script>
    // System Config
    window.currentUserRole = <?= json_encode($user['role'] ?? 'staff') ?>;
</script>
<script src="assets/js/animations.js"></script>
<script src="assets/js/app.js"></script>
<script>
// Reset filters
function resetFilters() {
    document.getElementById('searchInput').value   = '';
    document.getElementById('headerSearch').value  = '';
    document.getElementById('filterStatus').value  = 'all';
    document.getElementById('filterState').value   = '';
    document.getElementById('filterCountry').value = '';
    applyFilters();
}

// Update nav badge with total
function updateNavBadge(total) {
    const badge = document.getElementById('navBadge');
    if (badge) badge.textContent = total;
}

// Override loadCustomers to also update badge
const _origLoad = loadCustomers;
loadCustomers = async function() {
    await _origLoad.call(this);
    updateNavBadge(customersData.length);
};
</script>

</body>
</html>
