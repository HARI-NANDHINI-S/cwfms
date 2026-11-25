<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$user = getCurrentUser();
$initials = implode('', array_map(fn($p) => strtoupper($p[0]), array_filter(explode(' ', $user['name']))));
$leadStatuses = [
    'Received',
    'Analysis',
    'Awaiting Clarification',
    'Order Sheet Prepared',
    'Verified',
    'Awaiting Approval',
    'Approved',
    'Awaiting Payment',
    'Moved to Production',
    'Lost',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lead Management | Ira Sportswear</title>
    <link rel="icon" type="image/png" href="../assets/favicon.png" />
    <link rel="shortcut icon" href="../assets/favicon.ico" />
    <meta name="csrf-token" content="<?= getCsrfToken() ?>" />
    <script>(function(){var t=localStorage.getItem('cm-theme');if(t){document.documentElement.setAttribute('data-theme',t)}else if(window.matchMedia('(prefers-color-scheme:dark)').matches){document.documentElement.setAttribute('data-theme','dark')}else{document.documentElement.setAttribute('data-theme','light')}})();</script>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <style>
        .lead-page-shell {
            display:flex;
            flex-direction:column;
            gap:22px;
        }
        .lead-page-header {
            display:flex;
            align-items:flex-end;
            justify-content:space-between;
            gap:16px;
            padding:10px 2px 4px;
        }
        .lead-page-header h1 {
            margin:6px 0 0;
            font-size: clamp(2rem, 3vw, 2.6rem);
            line-height:1.1;
            letter-spacing:-0.05em;
        }
        .lead-toolbar {
            display:flex;
            flex-wrap:wrap;
            gap:18px;
            align-items:stretch;
            justify-content:space-between;
            margin-bottom:0;
            padding:18px 20px;
            background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));
            border:1px solid var(--border);
            border-radius:20px;
            box-shadow:var(--shadow-sm);
        }
        .lead-filters {
            display:grid;
            grid-template-columns: minmax(180px, 1.4fr) repeat(3, minmax(120px, 1fr)) repeat(2, minmax(130px, 1fr)) auto;
            gap:12px;
            flex:1;
            min-width:0;
            align-items:end;
        }
        @media (max-width: 1300px) {
            .lead-filters {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            }
        }
        .bulk-actions-toolbar {
            display:flex;
            align-items:center;
            gap:12px;
            flex-wrap:wrap;
            background:rgba(23, 28, 40, 0.95);
            border:1px solid rgba(255, 255, 255, 0.15);
            padding:8px 14px;
            border-radius:12px;
            box-shadow:0 4px 16px rgba(0,0,0,0.25);
        }
        .bulk-toolbar-left {
            display:flex;
            align-items:center;
            gap:8px;
        }
        .bulk-selected-badge {
            background:var(--accent, #6366f1);
            color:#fff;
            padding:4px 10px;
            border-radius:20px;
            font-size:0.75rem;
            font-weight:700;
            white-space:nowrap;
        }
        .bulk-toolbar-actions {
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:wrap;
        }
        .bulk-action-group {
            display:flex;
            align-items:center;
            gap:6px;
        }
        .lead-select-col {
            width:40px;
            text-align:center;
        }
        .lead-select-box {
            cursor:pointer;
            width:16px;
            height:16px;
            accent-color:var(--accent, #6366f1);
        }
        .filter-group {
            display:flex;
            flex-direction:column;
            gap:8px;
            min-width:0;
        }
        .filter-group label {
            font-size:0.7rem;
            text-transform:uppercase;
            letter-spacing:0.08em;
            color:var(--text-muted);
            font-weight:700;
        }
        .filter-group .form-control {
            min-height:46px;
            padding:10px 12px;
            border-radius:12px;
            border:1px solid var(--border);
            background:rgba(255,255,255,0.02);
            box-shadow:inset 0 1px 0 rgba(255,255,255,0.02);
        }
        .lead-kanban { display:grid; grid-template-columns:repeat(10, minmax(220px, 1fr)); gap:16px; overflow-x:auto; padding-bottom:8px; align-items:start; }
        .kanban-column { background:var(--surface-alt); border:1px solid var(--border); border-radius:16px; min-height:420px; padding:12px; display:flex; flex-direction:column; width:100%; }
        .kanban-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; padding-bottom:8px; border-bottom:1px solid rgba(255,255,255,0.06); }
        .kanban-card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:12px; margin-bottom:12px; box-shadow:var(--shadow-soft); transition:transform 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease; position:relative; }
        .kanban-card:hover { transform:translateY(-2px); border-color: rgba(255,255,255,0.2); box-shadow:var(--shadow-md); }
        .kanban-card[data-priority="high"] { border-left:4px solid #ef5350; }
        .kanban-card[data-priority="medium"] { border-left:4px solid #fb8c00; }
        .kanban-card[data-priority="low"] { border-left:4px solid #42a5f5; }
        .kanban-card-header { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; margin-bottom:8px; }
        .card-titles { min-width:0; flex:1; }
        .card-academy-title { font-size:0.92rem; font-weight:800; line-height:1.25; margin:0 0 2px; color:var(--text-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .card-contact-name { font-size:0.75rem; color:var(--text-muted); font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .kanban-card-body { display:flex; flex-direction:column; gap:5px; margin-top:6px; font-size:0.8rem; }
        .card-row { display:flex; align-items:center; gap:7px; color:var(--text-secondary); min-width:0; }
        .card-row .row-icon { font-size:0.85rem; flex-shrink:0; }
        .card-row .row-text { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; flex:1; min-width:0; }
        .card-row.sport-row { font-weight:700; color:var(--text-primary); }
        .card-source-tag { margin-left:auto; font-size:0.68rem; font-weight:600; color:var(--text-muted); background:rgba(255,255,255,0.04); padding:2px 6px; border-radius:4px; border:1px solid var(--border); flex-shrink:0; }
        .card-indicators { display:flex; flex-wrap:wrap; align-items:center; gap:5px; margin-top:9px; padding-top:7px; border-top:1px dashed var(--border); min-height:22px; }
        .indicator-item { display:inline-flex; align-items:center; justify-content:center; font-size:0.88rem; line-height:1; padding:3px 5px; background:var(--surface-alt); border:1px solid var(--border); border-radius:6px; cursor:help; transition:transform 0.15s ease, background 0.15s ease; }
        .indicator-item:hover { transform:scale(1.15); background:var(--surface-2); }
        .indicator-item.urgent { border-color:rgba(239,83,80,0.5); background:rgba(239,83,80,0.12); }
        .indicator-item.repeat { border-color:rgba(255,193,7,0.5); background:rgba(255,193,7,0.12); }
        .indicator-item.wa-sent { border-color:rgba(67,160,71,0.5); background:rgba(67,160,71,0.12); }
        .indicator-item.wa-delivered { border-color:rgba(0,230,118,0.5); background:rgba(0,230,118,0.15); color:#00e676; }
        .indicator-item.wa-read { border-color:rgba(0,230,118,0.8); background:rgba(0,230,118,0.25); color:#00e676; }
        .indicator-item.wa-req { border-color:rgba(66,165,245,0.5); background:rgba(66,165,245,0.12); }
        .indicator-item.wa-fail { border-color:rgba(229,57,53,0.5); background:rgba(229,57,53,0.12); }
        .card-footer-actions { margin-top:9px; display:flex; justify-content:space-between; align-items:center; gap:8px; }
        .lead-meta { display:flex; justify-content:space-between; font-size:0.75rem; color:var(--text-muted); gap:8px; }
        .status-badge { display:inline-flex; align-items:center; padding:4px 10px; border-radius:999px; font-size:0.68rem; font-weight:700; }
        .status-blue { background:rgba(66,165,245,0.12); color:#42a5f5; }
        .status-orange { background:rgba(251,140,0,0.12); color:#fb8c00; }
        .status-purple { background:rgba(171,71,188,0.12); color:#ab47bc; }
        .status-cyan { background:rgba(0,172,193,0.12); color:#00acc1; }
        .status-yellow { background:rgba(255,193,7,0.12); color:#f9a825; }
        .status-green { background:rgba(67,160,71,0.12); color:#43a047; }
        .status-red { background:rgba(229,57,53,0.12); color:#e53935; }
        .status-darkgreen { background:rgba(56,142,60,0.12); color:#2e7d32; }
        .status-lost { background:rgba(158,158,158,0.12); color:#757575; }
        .priority-badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:999px; font-size:0.68rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; }
        .priority-badge.high { background:rgba(239,83,80,0.14); color:#ef5350; border:1px solid rgba(239,83,80,0.3); }
        .priority-badge.medium { background:rgba(251,140,0,0.14); color:#fb8c00; border:1px solid rgba(251,140,0,0.3); }
        .priority-badge.low { background:rgba(66,165,245,0.14); color:#42a5f5; border:1px solid rgba(66,165,245,0.3); }
        .priority-chip { display:inline-flex; align-items:center; gap:8px; border-radius:10px; padding:8px 14px; cursor:pointer; font-size:0.82rem; font-weight:700; border:1.5px solid var(--border); transition:all 0.15s ease; }
        .priority-chip:hover { border-color:var(--text-muted); }
        .priority-chip.chip-high { border-color:rgba(239,83,80,0.4); color:#ef5350; }
        .priority-chip.chip-medium { border-color:rgba(251,140,0,0.4); color:#fb8c00; }
        .priority-chip.chip-low { border-color:rgba(66,165,245,0.4); color:#42a5f5; }
        .priority-chip:has(input:checked) { box-shadow:0 0 0 2px currentColor; }
        .customer-search-box { display:flex; flex-direction:column; gap:6px; }
        .lead-form-grid { display:grid; grid-template-columns:repeat(2, minmax(220px,1fr)); gap:16px; }
        .lead-form-section { background:var(--surface-alt); border:1px solid var(--border); border-radius:12px; padding:16px; }
        .field { display:flex; flex-direction:column; gap:6px; }
        .field input, .field select, .field textarea { width:100%; }
        .checkbox-grid { display:flex; gap:12px; flex-wrap:wrap; }
        .checkbox-chip { display:flex; align-items:center; gap:8px; border:1px solid var(--border); border-radius:999px; padding:8px 12px; background:var(--surface); }
        .table-wrapper { overflow:auto; }
        .hidden { display:none !important; }
        .lead-detail-grid { display:grid; grid-template-columns:repeat(2, minmax(180px, 1fr)); gap:12px; }
        .lead-detail-box { background:var(--surface-alt); border:1px solid var(--border); border-radius:12px; padding:12px; }
        .activity-log { display:flex; flex-direction:column; gap:10px; margin-top:12px; }
        .activity-item { border-left:3px solid var(--accent); background:var(--surface-alt); border-radius:10px; padding:12px; }
        .activity-item small { color:var(--text-muted); }
        @media (max-width: 1200px) {
            .lead-filters {
                grid-template-columns: repeat(2, minmax(180px, 1fr));
            }
        }
        @media (max-width: 900px) {
            .lead-toolbar {
                padding:16px;
            }
            .lead-filters {
                grid-template-columns: 1fr;
            }
            .lead-form-grid { grid-template-columns:1fr; }
            .lead-kanban { grid-template-columns:repeat(10,minmax(260px,1fr)); }
            .lead-detail-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<div class="app-wrapper">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <img src="../assets/ira_logo_web.png" alt="Ira Sportswear Logo" class="brand-logo-img">
            <div class="brand-text"><h2>Ira Sportswear</h2><span>Lead Management</span></div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="../dashboard.php" class="nav-item"><svg class="nav-icon" ...></svg>Customers</a>
            <a href="leads.php" class="nav-item active" style="border-left-color:var(--accent)"><svg class="nav-icon" ...></svg>Leads</a>
            <?php if (canManageUsers($user)): ?>
            <a href="users.php" class="nav-item"><svg class="nav-icon" ...></svg>User Management</a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="user-role"><?= htmlspecialchars($user['role_label'] ?? getRoleLabel($user['role'])) ?></div>
                </div>
                <a href="../logout.php" style="margin-left:auto;color:var(--text-muted)" title="Logout">...</a>
            </div>
        </div>
    </aside>

    <div class="main-content">
        <header class="top-header">
            <div class="header-title">
                <div class="breadcrumb" style="font-size:0.83rem">
                    <a href="../dashboard.php" style="color:var(--text-muted);text-decoration:none">Dashboard</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="9 18 15 12 9 6"/></svg>
                    <span>Ira Sportswear Leads</span>
                </div>
                <h1 style="margin-top:4px">Ira Sportswear Lead Management</h1>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" id="createLeadBtn">+ Create Lead</button>
            </div>
        </header>

        <div class="page-content">
            <div class="lead-page-shell">
                <div class="card">
                    <div class="lead-toolbar">
                        <div class="lead-filters">
                            <div class="filter-group"><label>Search</label><input id="leadSearch" type="text" class="form-control" placeholder="Search leads..." /></div>
                            <div class="filter-group"><label>Assigned To</label><select id="leadAssignedFilter" class="form-control"><option value="">All Sales Users</option></select></div>
                            <div class="filter-group"><label>Sport</label><select id="leadSportFilter" class="form-control"><option value="">All Sports</option><option>Cricket</option><option>Soccer</option><option>Pickleball</option><option>Tennis</option><option>Badminton</option><option>Others</option></select></div>
                            <div class="filter-group"><label>Lead Source</label><select id="leadSourceFilter" class="form-control"><option value="">All Sources</option><option>Kit Builder</option><option>Website</option><option>WhatsApp</option><option>Email</option></select></div>
                            <div class="filter-group"><label>From Date</label><input type="date" id="leadDateFrom" class="form-control" title="Enquiry From Date" /></div>
                            <div class="filter-group"><label>To Date</label><input type="date" id="leadDateTo" class="form-control" title="Enquiry To Date" /></div>
                            <div class="filter-group" style="justify-content:flex-end;">
                                <button type="button" class="btn btn-secondary btn-sm" id="clearDateFilterBtn" style="min-height:46px; padding:0 12px;" title="Reset Date Range">Clear Dates</button>
                            </div>
                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center; justify-content:flex-end;">
                            <button class="btn btn-secondary btn-sm" id="kanbanToggle">Kanban</button>
                            <button class="btn btn-secondary btn-sm" id="tableToggle">Table</button>
                            <button class="btn btn-secondary btn-sm" id="calendarToggle">Calendar</button>
                            <button class="btn btn-primary btn-sm" id="exportLeadsBtn">Export CSV</button>
                        </div>
                    </div>
                </div>

                <div class="card">
                <div class="card-header-simple">
                    <h3>Kanban Board</h3>
                </div>
                <div id="leadKanban" class="lead-kanban"></div>
            </div>

            <div class="card" style="display:none;" id="leadTableCard">
                <div class="card-header-simple" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                    <h3>Table View</h3>
                    <div id="bulkActionsToolbar" class="bulk-actions-toolbar" style="display:none;">
                        <div class="bulk-toolbar-left">
                            <span class="bulk-selected-badge" id="selectedCountBadge">0 selected</span>
                            <button type="button" class="btn btn-secondary btn-sm" id="clearSelectionBtn" style="padding:4px 10px; font-size:0.75rem;">Clear</button>
                        </div>
                        <div class="bulk-toolbar-actions">
                            <!-- Bulk Status Update -->
                            <div class="bulk-action-group">
                                <select id="bulkStatusSelect" class="form-control" style="min-height:34px; padding:4px 10px; font-size:0.8rem;">
                                    <option value="">Change Status...</option>
                                    <?php foreach ($leadStatuses as $status): ?>
                                        <option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-primary btn-sm" id="applyBulkStatusBtn">Apply</button>
                            </div>

                            <!-- Bulk Assignment (Admin & Sales Manager) -->
                            <?php if (canAssignLead($user)): ?>
                            <div class="bulk-action-group">
                                <select id="bulkAssignSelect" class="form-control" style="min-height:34px; padding:4px 10px; font-size:0.8rem;">
                                    <option value="">Assign To...</option>
                                </select>
                                <button type="button" class="btn btn-primary btn-sm" id="applyBulkAssignBtn">Assign</button>
                            </div>
                            <?php endif; ?>

                            <!-- Bulk Delete (Admin only) -->
                            <?php if (canDeleteLead($user)): ?>
                            <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" style="background:#ef5350; border-color:#e53935; color:#fff;">
                                🗑️ Delete Selected
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="lead-select-col"><input type="checkbox" id="selectAllLeads" class="lead-select-box" title="Select All"></th>
                                <th>Lead ID</th>
                                <th>Customer</th>
                                <th>Priority</th>
                                <th>Source</th>
                                <th>Sport</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Enquiry Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="leadTableBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="card" style="display:none;" id="leadCalendarCard">
                <div class="card-header-simple">
                    <h3>Calendar View</h3>
                </div>
                <div id="leadCalendar" style="display:grid; grid-template-columns:repeat(7, minmax(120px, 1fr)); gap:12px; padding:16px;"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="leadModal">
    <div class="modal" style="max-width:1000px; width:min(1000px, 92vw);">
        <div class="modal-header">
            <div><h3 id="leadModalTitle">Create Lead</h3></div>
            <button class="modal-close" onclick="closeModal('leadModal')">×</button>
        </div>
        <div class="modal-body">
            <form id="leadForm" enctype="multipart/form-data">
                <div class="lead-form-grid">
                    <div class="lead-form-section">
                        <h4>Lead Information</h4>
                        <div class="field" style="margin-top:14px">
                            <label>Customer <span class="required">*</span></label>
                            <div class="customer-search-box">
                                <input type="text" id="leadCustomerSearch" class="form-control" placeholder="Search customer by name, academy or phone..." autocomplete="off" />
                                <select id="lead_customer_id" name="customer_id" class="form-control" required>
                                    <option value="">-- Select Customer * --</option>
                                </select>
                            </div>
                            <small id="leadCustomerSelectedText" style="color:var(--text-muted); font-size:0.75rem; margin-top:2px;">Select an existing customer or search above.</small>
                        </div>
                        <div class="field" style="margin-top:14px">
                            <label>Priority <span class="required">*</span></label>
                            <div class="checkbox-grid" id="leadPriorityGroup">
                                <label class="priority-chip chip-low"><input type="radio" name="priority" value="low" /> Low (Blue)</label>
                                <label class="priority-chip chip-medium"><input type="radio" name="priority" value="medium" checked /> Medium (Orange)</label>
                                <label class="priority-chip chip-high"><input type="radio" name="priority" value="high" /> High (Red)</label>
                            </div>
                        </div>
                        <div class="field" style="margin-top:14px"><label>Lead Source <span class="required">*</span></label><div class="checkbox-grid" id="leadSourceGroup"><label class="checkbox-chip"><input type="radio" name="lead_source" value="Kit Builder" /> Kit Builder</label><label class="checkbox-chip"><input type="radio" name="lead_source" value="Website" /> Website</label><label class="checkbox-chip"><input type="radio" name="lead_source" value="WhatsApp" /> WhatsApp</label><label class="checkbox-chip"><input type="radio" name="lead_source" value="Email" /> Email</label></div></div>
                        <div class="field" style="margin-top:14px"><label>Enquiry Date <span class="required">*</span></label><input type="date" id="enquiry_date" name="enquiry_date" value="<?= date('Y-m-d') ?>" /></div>
                        <div class="field" style="margin-top:14px"><label>Products Interested <span class="required">*</span></label>
                            <div class="checkbox-grid">
                                <?php $productOptions = ['Playing T Shirt','Trouser','Training T Shirt','Shorts','Cap','Hat','Travel Polo','Travel Jacket','Travel Trouser','Sleeveless Jacket','Umpire T Shirt','Helmet Clad','Pad Clad']; foreach ($productOptions as $p): ?>
                                    <label class="checkbox-chip"><input type="checkbox" name="products_interested[]" value="<?= htmlspecialchars($p) ?>" /> <?= htmlspecialchars($p) ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="field" style="margin-top:14px"><label>Sport <span class="required">*</span></label><div class="checkbox-grid"><label class="checkbox-chip"><input type="radio" name="sport" value="Cricket" /> Cricket</label><label class="checkbox-chip"><input type="radio" name="sport" value="Soccer" /> Soccer</label><label class="checkbox-chip"><input type="radio" name="sport" value="Pickleball" /> Pickleball</label><label class="checkbox-chip"><input type="radio" name="sport" value="Tennis" /> Tennis</label><label class="checkbox-chip"><input type="radio" name="sport" value="Badminton" /> Badminton</label><label class="checkbox-chip"><input type="radio" name="sport" value="Others" /> Others</label></div></div>
                    </div>

                    <div class="lead-form-section">
                        <h4>Design Information</h4>
                        <div class="field" style="margin-top:14px"><label>Design Source <span class="required">*</span></label><div class="checkbox-grid"><label class="checkbox-chip"><input type="radio" name="design_source" value="Kit Builder" /> Kit Builder</label><label class="checkbox-chip"><input type="radio" name="design_source" value="Design Link" /> Design Link</label><label class="checkbox-chip"><input type="radio" name="design_source" value="Custom Design" /> Custom Design</label></div></div>
                        <div class="field hidden" id="submissionIdField" style="margin-top:14px"><label>Submission ID</label><input type="text" name="submission_id" placeholder="Enter submission ID" /></div>
                        <div class="field hidden" id="designLinkField" style="margin-top:14px"><label>Design Link</label><input type="url" name="design_link" placeholder="https://example.com/design" /></div>
                        <div class="field hidden" id="customDesignField" style="margin-top:14px"><label>Custom Design File</label><input type="file" name="custom_design_file[]" multiple accept=".pdf,.ai,.eps,.png,.jpg,.jpeg,.zip" /></div>
                        <div class="field" style="margin-top:14px"><label>Player Roster Received</label><div class="checkbox-grid"><label class="checkbox-chip"><input type="radio" name="player_roster_received" value="Received" /> Received</label><label class="checkbox-chip"><input type="radio" name="player_roster_received" value="Not Received" checked /> Not Received</label></div></div>
                        <div class="field hidden" id="playerListField" style="margin-top:14px"><label>Player List File</label><input type="file" name="player_list_file[]" multiple accept=".xlsx,.xls,.csv,.pdf" /></div>
                    </div>

                    <div class="lead-form-section" style="grid-column:1 / -1;">
                        <h4>Additional Information</h4>
                        <div class="field" style="margin-top:14px"><label>Notes</label><textarea name="notes" rows="4" placeholder="Special instructions, customer requirements, design comments, delivery expectations"></textarea></div>
                    </div>

                    <div class="lead-form-section">
                        <h4>Communication</h4>
                        <div class="field" style="margin-top:14px"><label>Send WhatsApp Notification</label><label class="checkbox-chip"><input type="checkbox" name="send_whatsapp_notification" value="1" /> Send confirmation to customer</label></div>
                    </div>

                    <div class="lead-form-section">
                        <h4>Assignment & Workflow</h4>
                        <div class="field" style="margin-top:14px"><label>Assigned To <span class="required">*</span></label><select id="assigned_to" name="assigned_to" class="form-control"><option value="">Select sales user</option></select></div>
                        <div class="field" style="margin-top:14px"><label>Lead Status</label><select name="lead_status" class="form-control"><?php foreach ($leadStatuses as $status): ?><option value="<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($status) ?></option><?php endforeach; ?></select></div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('leadModal')">Cancel</button>
            <button class="btn btn-primary" onclick="saveLead()">Create Lead</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="leadDetailModal">
    <div class="modal" style="max-width:900px; width:min(900px, 92vw);">
        <div class="modal-header">
            <div><h3 id="leadDetailTitle">Lead Details</h3></div>
            <button class="modal-close" onclick="closeModal('leadDetailModal')">×</button>
        </div>
        <div class="modal-body">
            <div id="leadDetailContent"></div>
            <div style="margin-top:20px;">
                <h4>Activity Timeline</h4>
                <div id="leadActivityLog" class="activity-log"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" id="deleteLeadBtn">Delete Lead</button>
            <button class="btn btn-secondary" id="editLeadBtn">Edit Lead</button>
            <button class="btn btn-primary" id="closeLeadDetailBtn">Close</button>
        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="../assets/js/animations.js"></script>
<script src="../assets/js/app.js"></script>
<script>
const LEAD_API = '../api/leads.php';
const leadStatuses = <?= json_encode($leadStatuses) ?>;
const currentUserRole = <?= json_encode(normalizeRole($user['role'] ?? 'staff')) ?>;
const currentUserRoleLabel = <?= json_encode(getRoleLabel($user['role'] ?? 'staff')) ?>;
const currentUserId = <?= json_encode($user['id'] ?? '') ?>;
const leadColumns = [
  'Received','Analysis','Awaiting Clarification','Order Sheet Prepared','Verified','Awaiting Approval','Approved','Awaiting Payment','Moved to Production','Lost'
];
let currentLeadView = 'kanban';
let allLeads = [];
let allCustomers = [];
let currentLeadId = null;
let selectedLeadIds = new Set();

function showToast(message, type='success'){ const container = document.getElementById('toastContainer'); if(!container) return; const el = document.createElement('div'); el.className='toast ' + type; el.textContent = message; container.appendChild(el); setTimeout(()=>el.classList.add('show'), 10); setTimeout(()=>{el.classList.remove('show'); setTimeout(()=>el.remove(), 300);}, 2600); }
function closeModal(id){ const modal = document.getElementById(id); if(modal){ modal.classList.remove('show'); modal.classList.remove('active'); } }
function openLeadModal(){ const modal = document.getElementById('leadModal'); if(modal){ modal.classList.add('show'); modal.classList.add('active'); } }

async function loadCustomers(){
    try {
        const res = await fetch(LEAD_API + '?action=customers-list', { credentials: 'same-origin' });
        const json = await res.json();
        if (json.success && Array.isArray(json.data)) {
            allCustomers = json.data;
            populateCustomerSelect(allCustomers);
        }
    } catch (err) {
        console.error('Failed to load customers:', err);
    }
}

function populateCustomerSelect(customers, selectedId = ''){
    const select = document.getElementById('lead_customer_id');
    if (!select) return;
    const currentVal = selectedId || select.value;
    select.innerHTML = '<option value="">-- Select Customer * --</option>' + customers.map(c => {
        const team = c.team_academy_name ? ' — ' + c.team_academy_name : '';
        const phone = c.mobile_number ? ' (' + c.mobile_number + ')' : '';
        const isSel = (c.id === currentVal) ? 'selected' : '';
        return `<option value="${escapeHtml(c.id)}" ${isSel}>${escapeHtml(c.name)}${escapeHtml(team)}${escapeHtml(phone)}</option>`;
    }).join('');
    updateSelectedCustomerHint();
}

function bindCustomerSearch(){
    const searchInput = document.getElementById('leadCustomerSearch');
    const select = document.getElementById('lead_customer_id');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const term = searchInput.value.toLowerCase().trim();
            if (!term) {
                populateCustomerSelect(allCustomers, select ? select.value : '');
                return;
            }
            const filtered = allCustomers.filter(c => 
                (c.name && c.name.toLowerCase().includes(term)) ||
                (c.team_academy_name && c.team_academy_name.toLowerCase().includes(term)) ||
                (c.mobile_number && c.mobile_number.includes(term)) ||
                (c.email && c.email.toLowerCase().includes(term))
            );
            populateCustomerSelect(filtered, select ? select.value : '');
        });
    }
    if (select) {
        select.addEventListener('change', updateSelectedCustomerHint);
    }
}

function updateSelectedCustomerHint(){
    const select = document.getElementById('lead_customer_id');
    const hint = document.getElementById('leadCustomerSelectedText');
    if (!select || !hint) return;
    const selected = allCustomers.find(c => c.id === select.value);
    if (selected) {
        hint.textContent = `Selected: ${selected.name} | Academy/Team: ${selected.team_academy_name || 'N/A'} | Phone: ${selected.mobile_number || 'N/A'}`;
        hint.style.color = 'var(--accent)';
    } else {
        hint.textContent = 'Select an existing customer or search above.';
        hint.style.color = 'var(--text-muted)';
    }
}

const demoLeadSeed = [
    { lead_source: 'Website', sport: 'Cricket', priority: 'high', design_source: 'Kit Builder', submission_id: 'SUB-1001', products_interested: ['Playing T Shirt', 'Trouser'], assigned_to: '', lead_status: 'Received', enquiry_date: new Date().toISOString().slice(0,10), notes: 'Initial website enquiry for custom cricket kit.' },
    { lead_source: 'WhatsApp', sport: 'Soccer', priority: 'medium', design_source: 'Design Link', design_link: 'https://example.com/design', products_interested: ['Training T Shirt', 'Shorts'], assigned_to: '', lead_status: 'Analysis', enquiry_date: new Date(Date.now()-86400000).toISOString().slice(0,10), notes: 'Need quick turnaround before tournament.' },
    { lead_source: 'Email', sport: 'Tennis', priority: 'low', design_source: 'Custom Design', products_interested: ['Travel Polo', 'Cap'], assigned_to: '', lead_status: 'Verified', enquiry_date: new Date(Date.now()-172800000).toISOString().slice(0,10), notes: 'Custom design with academy branding approved.' }
];

async function seedDemoLeadsIfEmpty() {
    try {
        const res = await fetch(LEAD_API + '?action=list', { credentials: 'same-origin' });
        const json = await res.json();
        if (!json || !json.success || !Array.isArray(json.data) || json.data.length > 0) return;
        if (!allCustomers.length) return;

        const defaultCustId = allCustomers[0]?.id || '';
        for (let i = 0; i < demoLeadSeed.length; i++) {
            const lead = demoLeadSeed[i];
            const custId = allCustomers[i % allCustomers.length]?.id || defaultCustId;
            const formData = new FormData();
            formData.append('customer_id', custId);
            formData.append('priority', lead.priority || 'medium');
            formData.append('lead_source', lead.lead_source);
            formData.append('enquiry_date', lead.enquiry_date);
            formData.append('sport', lead.sport);
            formData.append('design_source', lead.design_source);
            formData.append('submission_id', lead.submission_id || '');
            formData.append('design_link', lead.design_link || '');
            formData.append('notes', lead.notes || '');
            formData.append('lead_status', lead.lead_status);
            formData.append('assigned_to', lead.assigned_to || '');
            formData.append('player_roster_received', 'Not Received');
            formData.append('send_whatsapp_notification', '0');
            for (const item of lead.products_interested || []) {
                formData.append('products_interested[]', item);
            }
            await fetch(LEAD_API + '?action=create', { method: 'POST', credentials: 'same-origin', body: formData });
        }
    } catch (error) {
        console.warn('Demo seeding skipped:', error);
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    const createBtn = document.getElementById('createLeadBtn');
    if (createBtn) {
        createBtn.addEventListener('click', () => {
            currentLeadId = null;
            document.getElementById('leadModalTitle').textContent = 'Create Lead';
            const primaryBtn = document.querySelector('#leadModal .modal-footer .btn.btn-primary');
            if (primaryBtn) primaryBtn.textContent = 'Create Lead';
            document.getElementById('leadForm').reset();
            const searchInput = document.getElementById('leadCustomerSearch');
            if (searchInput) searchInput.value = '';
            populateCustomerSelect(allCustomers, '');
            const medRadio = document.querySelector('input[name="priority"][value="medium"]');
            if (medRadio) medRadio.checked = true;
            updateLeadFormVisibility();
            openLeadModal();
        });
    }

    document.getElementById('kanbanToggle').addEventListener('click', () => switchLeadView('kanban'));
    document.getElementById('tableToggle').addEventListener('click', () => switchLeadView('table'));
    document.getElementById('calendarToggle').addEventListener('click', () => switchLeadView('calendar'));
    document.getElementById('exportLeadsBtn').addEventListener('click', exportLeads);
    document.getElementById('closeLeadDetailBtn').addEventListener('click', () => closeModal('leadDetailModal'));
    document.getElementById('editLeadBtn').addEventListener('click', openEditLeadModal);
    document.getElementById('deleteLeadBtn').addEventListener('click', deleteLeadFromDetail);

    // Table Bulk Actions listeners
    bindBulkActionEvents();

    bindLeadFilters();
    bindLeadFormVisibility();
    bindCustomerSearch();
    await loadSalesUsers();
    await loadCustomers();
    await seedDemoLeadsIfEmpty();
    switchLeadView('kanban');
    renderKanban();
    renderLeadTable();
    renderLeadCalendar();

    // Deep linking URL parameters (cross-module navigation)
    const urlParams = new URLSearchParams(window.location.search);
    const targetLeadId = urlParams.get('id') || urlParams.get('lead_id');
    const targetCustId = urlParams.get('customer_id');
    const actionParam = urlParams.get('action');

    if (targetLeadId) {
        viewLead(targetLeadId);
    } else if (actionParam === 'new') {
        currentLeadId = null;
        document.getElementById('leadModalTitle').textContent = 'Create Lead';
        const primaryBtn = document.querySelector('#leadModal .modal-footer .btn.btn-primary');
        if (primaryBtn) primaryBtn.textContent = 'Create Lead';
        document.getElementById('leadForm').reset();
        if (targetCustId) {
            populateCustomerSelect(allCustomers, targetCustId);
        }
        const medRadio = document.querySelector('input[name="priority"][value="medium"]');
        if (medRadio) medRadio.checked = true;
        updateLeadFormVisibility();
        openLeadModal();
    } else if (targetCustId) {
        const searchEl = document.getElementById('leadSearch');
        const matchedCust = allCustomers.find(c => c.id === targetCustId);
        if (searchEl && matchedCust) {
            searchEl.value = matchedCust.name;
            renderKanban();
            renderLeadTable();
        }
    }
});

function bindLeadFilters(){
    ['leadSearch','leadAssignedFilter','leadSportFilter','leadSourceFilter'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', () => renderKanban());
        el.addEventListener('change', () => renderKanban());
    });

    const dateFromEl = document.getElementById('leadDateFrom');
    const dateToEl = document.getElementById('leadDateTo');
    const handleDateChange = () => {
        const dFrom = dateFromEl ? dateFromEl.value : '';
        const dTo = dateToEl ? dateToEl.value : '';
        if (dFrom && dTo && dFrom > dTo) {
            showToast('From Date cannot be later than To Date.', 'error');
            return;
        }
        renderKanban();
    };

    if (dateFromEl) dateFromEl.addEventListener('change', handleDateChange);
    if (dateToEl) dateToEl.addEventListener('change', handleDateChange);

    const clearDateBtn = document.getElementById('clearDateFilterBtn');
    if (clearDateBtn) {
        clearDateBtn.addEventListener('click', () => {
            if (dateFromEl) dateFromEl.value = '';
            if (dateToEl) dateToEl.value = '';
            renderKanban();
        });
    }
}

function bindBulkActionEvents(){
    const selectAllCb = document.getElementById('selectAllLeads');
    if (selectAllCb) {
        selectAllCb.addEventListener('change', (e) => {
            const isChecked = e.target.checked;
            const search = (document.getElementById('leadSearch')?.value || '').toLowerCase();
            const visibleLeads = allLeads.filter(lead => {
                if (!search) return true;
                return [lead.customer?.name, lead.customer?.team_academy_name, lead.assigned_user?.user_name, lead.sport, lead.lead_source, lead.submission_id].filter(Boolean).join(' ').toLowerCase().includes(search);
            });

            visibleLeads.forEach(l => {
                if (isChecked) {
                    selectedLeadIds.add(l.id);
                } else {
                    selectedLeadIds.delete(l.id);
                }
            });

            document.querySelectorAll('#leadTableBody .lead-select-box').forEach(cb => {
                cb.checked = isChecked;
            });

            updateBulkToolbarState(visibleLeads);
        });
    }

    const clearBtn = document.getElementById('clearSelectionBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            selectedLeadIds.clear();
            if (selectAllCb) {
                selectAllCb.checked = false;
                selectAllCb.indeterminate = false;
            }
            document.querySelectorAll('#leadTableBody .lead-select-box').forEach(cb => {
                cb.checked = false;
            });
            const search = (document.getElementById('leadSearch')?.value || '').toLowerCase();
            const visibleLeads = allLeads.filter(lead => {
                if (!search) return true;
                return [lead.customer?.name, lead.customer?.team_academy_name, lead.assigned_user?.user_name, lead.sport, lead.lead_source, lead.submission_id].filter(Boolean).join(' ').toLowerCase().includes(search);
            });
            updateBulkToolbarState(visibleLeads);
        });
    }

    const applyStatusBtn = document.getElementById('applyBulkStatusBtn');
    if (applyStatusBtn) {
        applyStatusBtn.addEventListener('click', async () => {
            if (selectedLeadIds.size === 0) {
                showToast('Please select at least one lead.', 'error');
                return;
            }
            const statusVal = document.getElementById('bulkStatusSelect')?.value;
            if (!statusVal) {
                showToast('Please select a status to apply.', 'error');
                return;
            }

            try {
                const res = await fetch(LEAD_API + '?action=bulk_update', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        ids: Array.from(selectedLeadIds),
                        operation: 'status',
                        lead_status: statusVal
                    })
                });
                const json = await res.json();
                if (!json.success) {
                    showToast(json.message || 'Bulk status update failed', 'error');
                    return;
                }
                showToast(json.message || `Successfully updated ${selectedLeadIds.size} leads`, 'success');
                selectedLeadIds.clear();
                const sel = document.getElementById('bulkStatusSelect');
                if (sel) sel.value = '';
                await renderKanban();
            } catch (err) {
                showToast('Error applying bulk status update', 'error');
            }
        });
    }

    const applyAssignBtn = document.getElementById('applyBulkAssignBtn');
    if (applyAssignBtn) {
        applyAssignBtn.addEventListener('click', async () => {
            if (selectedLeadIds.size === 0) {
                showToast('Please select at least one lead.', 'error');
                return;
            }
            const assignVal = document.getElementById('bulkAssignSelect')?.value;
            if (!assignVal) {
                showToast('Please select a sales user to assign.', 'error');
                return;
            }

            try {
                const res = await fetch(LEAD_API + '?action=bulk_update', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        ids: Array.from(selectedLeadIds),
                        operation: 'assign',
                        assigned_to: assignVal
                    })
                });
                const json = await res.json();
                if (!json.success) {
                    showToast(json.message || 'Bulk assignment failed', 'error');
                    return;
                }
                showToast(json.message || `Successfully assigned ${selectedLeadIds.size} leads`, 'success');
                selectedLeadIds.clear();
                const sel = document.getElementById('bulkAssignSelect');
                if (sel) sel.value = '';
                await renderKanban();
            } catch (err) {
                showToast('Error applying bulk assignment', 'error');
            }
        });
    }

    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', async () => {
            if (selectedLeadIds.size === 0) {
                showToast('Please select at least one lead.', 'error');
                return;
            }
            if (!confirm(`Are you sure you want to delete ${selectedLeadIds.size} selected lead(s)? This will soft-delete the records.`)) {
                return;
            }

            try {
                const res = await fetch(LEAD_API + '?action=bulk_update', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        ids: Array.from(selectedLeadIds),
                        operation: 'delete'
                    })
                });
                const json = await res.json();
                if (!json.success) {
                    showToast(json.message || 'Bulk delete failed', 'error');
                    return;
                }
                showToast(json.message || `Successfully deleted ${selectedLeadIds.size} leads`, 'success');
                selectedLeadIds.clear();
                await renderKanban();
            } catch (err) {
                showToast('Error executing bulk delete', 'error');
            }
        });
    }
}

function updateBulkToolbarState(visibleLeads){
    const toolbar = document.getElementById('bulkActionsToolbar');
    const badge = document.getElementById('selectedCountBadge');
    const selectAllCb = document.getElementById('selectAllLeads');

    const count = selectedLeadIds.size;
    if (badge) {
        badge.textContent = `${count} selected`;
    }

    if (toolbar) {
        toolbar.style.display = count > 0 ? 'flex' : 'none';
    }

    if (selectAllCb) {
        if (!visibleLeads || visibleLeads.length === 0) {
            selectAllCb.checked = false;
            selectAllCb.indeterminate = false;
        } else {
            const visibleIds = visibleLeads.map(l => l.id);
            const selectedVisibleCount = visibleIds.filter(id => selectedLeadIds.has(id)).length;
            if (selectedVisibleCount === 0) {
                selectAllCb.checked = false;
                selectAllCb.indeterminate = false;
            } else if (selectedVisibleCount === visibleIds.length) {
                selectAllCb.checked = true;
                selectAllCb.indeterminate = false;
            } else {
                selectAllCb.checked = false;
                selectAllCb.indeterminate = true;
            }
        }
    }
}

function bindLeadFormVisibility(){
    const inputs = document.querySelectorAll('input[name="design_source"], input[name="player_roster_received"]');
    inputs.forEach(input => input.addEventListener('change', updateLeadFormVisibility));
    updateLeadFormVisibility();
}

function updateLeadFormVisibility(){
    const design = document.querySelector('input[name="design_source"]:checked')?.value;
    document.getElementById('submissionIdField').classList.toggle('hidden', design !== 'Kit Builder');
    document.getElementById('designLinkField').classList.toggle('hidden', design !== 'Design Link');
    document.getElementById('customDesignField').classList.toggle('hidden', design !== 'Custom Design');

    const roster = document.querySelector('input[name="player_roster_received"]:checked')?.value;
    document.getElementById('playerListField').classList.toggle('hidden', roster !== 'Received');
}

async function loadSalesUsers(){
    const res = await fetch(LEAD_API + '?action=sales-users', {credentials:'same-origin'});
    const json = await res.json();
    if (!json.success) return;

    const users = json.data || [];
    const roleFormat = (role) => {
        const r = String(role || '').toLowerCase();
        if (r === 'admin') return 'Admin';
        if (r === 'manager' || r === 'sales_manager') return 'Sales Manager';
        return 'Sales Executive';
    };
    const assignments = users.map(u => `<option value="${u.id}">${escapeHtml(u.user_name)} (${roleFormat(u.role)})</option>`).join('');

    const formSelect = document.getElementById('assigned_to');
    if (formSelect) {
        if (currentUserRole === 'sales_executive') {
            formSelect.innerHTML = `<option value="${currentUserId}">Assigned to You (${currentUserRoleLabel})</option>`;
            formSelect.disabled = true;
        } else {
            formSelect.innerHTML = '<option value="">Select sales user</option>' + assignments;
            formSelect.disabled = false;
        }
    }

    const filterSelect = document.getElementById('leadAssignedFilter');
    if (filterSelect) {
        filterSelect.innerHTML = '<option value="">All Sales Users</option>' + assignments;
    }

    const bulkSelect = document.getElementById('bulkAssignSelect');
    if (bulkSelect) {
        bulkSelect.innerHTML = '<option value="">Assign To...</option>' + assignments;
    }
}

function sportIcon(sport) {
    const map = {
        'Cricket': '🏏',
        'Soccer': '⚽',
        'Football': '⚽',
        'Pickleball': '🏓',
        'Tennis': '🎾',
        'Badminton': '🏸',
        'Others': '🏅'
    };
    return map[sport] || '🏅';
}

function formatEnquiryDate(dateStr) {
    if (!dateStr) return 'N/A';
    const parts = String(dateStr).split('-');
    if (parts.length === 3) {
        const y = parts[0], m = parseInt(parts[1], 10), d = parts[2];
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        if (m >= 1 && m <= 12) {
            return `${d}-${months[m - 1]}-${y}`;
        }
    }
    const dt = new Date(dateStr);
    if (!isNaN(dt.getTime())) {
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const day = String(dt.getDate()).padStart(2, '0');
        return `${day}-${months[dt.getMonth()]}-${dt.getFullYear()}`;
    }
    return dateStr;
}

function renderCardIndicators(lead) {
    const indicators = [];

    // 1. 📎 Attachments available
    if (lead.has_attachments) {
        indicators.push(`<span class="indicator-item" title="📎 Attachments available (Design / Roster files attached)" aria-label="Attachments available">📎</span>`);
    }

    // 2. 🎨 Design uploaded
    if (lead.has_design) {
        const src = lead.design_source ? ` (${escapeHtml(lead.design_source)})` : '';
        indicators.push(`<span class="indicator-item" title="🎨 Design uploaded${src}" aria-label="Design uploaded">🎨</span>`);
    }

    // 3. 📋 Player roster received
    if (lead.player_roster_received === 'Received') {
        indicators.push(`<span class="indicator-item" title="📋 Player roster received" aria-label="Player roster received">📋</span>`);
    }

    // 4. 💬 WhatsApp notification
    const wa = lead.whatsapp_status || '';
    if (wa === 'read') {
        indicators.push(`<span class="indicator-item wa-read" title="💬 WhatsApp message read by customer" aria-label="WhatsApp read">💬</span>`);
    } else if (wa === 'delivered') {
        indicators.push(`<span class="indicator-item wa-delivered" title="💬 WhatsApp message delivered to customer" aria-label="WhatsApp delivered">💬</span>`);
    } else if (wa === 'sent') {
        indicators.push(`<span class="indicator-item wa-sent" title="💬 WhatsApp notification sent to customer" aria-label="WhatsApp sent">💬</span>`);
    } else if (wa === 'requested') {
        indicators.push(`<span class="indicator-item wa-req" title="💬 WhatsApp notification requested" aria-label="WhatsApp requested">💬</span>`);
    } else if (wa === 'failed') {
        indicators.push(`<span class="indicator-item wa-fail" title="⚠️ WhatsApp notification delivery failed" aria-label="WhatsApp delivery failed">⚠️</span>`);
    }

    // 5. 🔥 Urgent / High Priority
    if (lead.priority === 'high') {
        indicators.push(`<span class="indicator-item urgent" title="🔥 Urgent: High priority lead" aria-label="Urgent">🔥</span>`);
    }

    // 6. ⭐ Repeat customer
    if (lead.is_repeat_customer) {
        indicators.push(`<span class="indicator-item repeat" title="⭐ Repeat customer: Previous enquiries on record" aria-label="Repeat customer">⭐</span>`);
    }

    return indicators.join('');
}

async function renderKanban(){
    const search = (document.getElementById('leadSearch')?.value || '').toLowerCase();
    const assigned = document.getElementById('leadAssignedFilter')?.value || '';
    const sport = document.getElementById('leadSportFilter')?.value || '';
    const source = document.getElementById('leadSourceFilter')?.value || '';
    const dateFrom = document.getElementById('leadDateFrom')?.value || '';
    const dateTo = document.getElementById('leadDateTo')?.value || '';

    if (dateFrom && dateTo && dateFrom > dateTo) {
        showToast('From Date cannot be later than To Date.', 'error');
        return;
    }

    const params = new URLSearchParams();
    if (assigned) params.set('assigned_to', assigned);
    if (sport) params.set('sport', sport);
    if (source) params.set('lead_source', source);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);

    const res = await fetch(LEAD_API + '?action=list&' + params.toString(), {credentials:'same-origin'});
    const json = await res.json();
    allLeads = json.data || [];
    const leads = allLeads.filter(lead => {
        const q = !search || [lead.customer?.name, lead.customer?.team_academy_name, lead.assigned_user?.user_name, lead.sport, lead.lead_source, lead.submission_id, lead.notes].filter(Boolean).join(' ').toLowerCase().includes(search);
        return q;
    });

    const board = document.getElementById('leadKanban');
    board.innerHTML = leadColumns.map(status => {
        const statusLeads = leads.filter(l => l.lead_status === status);
        return `
            <div class="kanban-column" data-status="${status}">
                <div class="kanban-header">
                    <strong>${status}</strong>
                    <span class="status-badge ${statusColor(status)}">${statusLeads.length}</span>
                </div>
                ${statusLeads.length ? statusLeads.map(lead => {
                    const priorityVal = escapeHtml(lead.priority || 'medium');
                    const academyName = lead.customer && lead.customer.team_academy_name ? lead.customer.team_academy_name.trim() : '';
                    const contactName = lead.customer && lead.customer.name ? lead.customer.name.trim() : 'Customer';
                    const primaryTitle = academyName || contactName;
                    const secondaryTitle = academyName ? contactName : '';
                    const productsList = (lead.products_interested || []).join(', ') || 'No products selected';
                    const indicatorsHtml = renderCardIndicators(lead);

                    return `
                    <div class="kanban-card" data-priority="${priorityVal}" draggable="true" data-id="${lead.id}">
                        <div class="kanban-card-header">
                            <div class="card-titles">
                                <h4 class="card-academy-title" title="${escapeHtml(primaryTitle)}">
                                    ${lead.customer_id ? `<a href="customer-detail.php?id=${encodeURIComponent(lead.customer_id)}" style="color:inherit;text-decoration:none;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='inherit'">${escapeHtml(primaryTitle)}</a>` : escapeHtml(primaryTitle)}
                                </h4>
                                ${secondaryTitle ? `<div class="card-contact-name" title="${escapeHtml(secondaryTitle)}">👤 ${escapeHtml(secondaryTitle)}</div>` : ''}
                            </div>
                            <span class="priority-badge ${priorityVal}">${priorityVal.toUpperCase()}</span>
                        </div>

                        <div class="kanban-card-body">
                            <div class="card-row sport-row">
                                <span class="row-icon">${sportIcon(lead.sport)}</span>
                                <span class="row-text">${escapeHtml(lead.sport || 'Sport')}</span>
                                <span class="card-source-tag">${escapeHtml(lead.lead_source || '')}</span>
                            </div>
                            <div class="card-row" title="${escapeHtml(productsList)}">
                                <span class="row-icon">👕</span>
                                <span class="row-text">${escapeHtml(productsList)}</span>
                            </div>
                            <div class="card-row">
                                <span class="row-icon">📅</span>
                                <span class="row-text">${formatEnquiryDate(lead.enquiry_date)}</span>
                            </div>
                            <div class="card-row">
                                <span class="row-icon">👤</span>
                                <span class="row-text">${escapeHtml(lead.assigned_user?.user_name || 'Unassigned')}</span>
                            </div>
                        </div>

                        ${indicatorsHtml ? `<div class="card-indicators">${indicatorsHtml}</div>` : ''}

                        <div class="card-footer-actions">
                            <span class="status-badge ${statusColor(lead.lead_status)}">${escapeHtml(lead.lead_status)}</span>
                            <button class="btn btn-secondary btn-sm" onclick="viewLead('${lead.id}')">View</button>
                        </div>
                    </div>
                    `;
                }).join('') : `<div style="padding:12px;color:var(--text-muted);">No leads in this stage.</div>`}
            </div>
        `;
    }).join('');

    board.querySelectorAll('.kanban-card').forEach(card => {
        card.addEventListener('dragstart', e => e.dataTransfer.setData('text/plain', card.dataset.id));
    });

    board.querySelectorAll('.kanban-column').forEach(column => {
        column.addEventListener('dragover', e => e.preventDefault());
        column.addEventListener('drop', async e => {
            e.preventDefault();
            const leadId = e.dataTransfer.getData('text/plain');
            const newStatus = column.dataset.status;
            if (!leadId || !newStatus) return;

            const targetLead = allLeads.find(l => l.id === leadId);
            if (currentUserRole === 'sales_executive' && targetLead && targetLead.assigned_to !== currentUserId) {
                showToast('Permission denied: Sales Executives can only update status on leads assigned to them.', 'error');
                return;
            }

            try {
                const res = await fetch(LEAD_API, {method:'PUT', credentials:'same-origin', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id:leadId, lead_status:newStatus})});
                const json = await res.json();
                if (!json.success) { 
                    showToast(json.message || 'Status update failed', 'error'); 
                    renderKanban();
                    return; 
                }
                showToast('Lead status updated', 'success');
                renderKanban();
                renderLeadTable();
                renderLeadCalendar();
            } catch (err) {
                showToast('Network error while updating status', 'error');
                renderKanban();
            }
        });
    });

    renderLeadTable();
    renderLeadCalendar();
}

async function renderLeadTable(){
    const tbody = document.getElementById('leadTableBody');
    const search = (document.getElementById('leadSearch')?.value || '').toLowerCase();
    const leads = allLeads.filter(lead => {
        if (!search) return true;
        return [lead.customer?.name, lead.customer?.team_academy_name, lead.assigned_user?.user_name, lead.sport, lead.lead_source, lead.submission_id].filter(Boolean).join(' ').toLowerCase().includes(search);
    });

    // Prune removed lead IDs from selection
    const visibleIdSet = new Set(allLeads.map(l => l.id));
    for (const id of selectedLeadIds) {
        if (!visibleIdSet.has(id)) {
            selectedLeadIds.delete(id);
        }
    }

    tbody.innerHTML = leads.length ? leads.map(lead => {
        const priorityVal = escapeHtml(lead.priority || 'medium');
        const teamName = lead.customer && lead.customer.team_academy_name;
        const custDisplay = (lead.customer && lead.customer.name)
            ? `<a href="customer-detail.php?id=${encodeURIComponent(lead.customer_id)}" style="color:var(--text-primary);font-weight:600;text-decoration:none;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-primary)'">${escapeHtml(lead.customer.name)}${teamName ? ' <span style=\"color:var(--text-muted);font-weight:normal;\">(' + escapeHtml(teamName) + ')</span>' : ''}</a>`
            : (lead.customer_id ? `<a href="customer-detail.php?id=${encodeURIComponent(lead.customer_id)}" style="color:var(--accent);">View Profile ↗</a>` : 'Customer');
        const isChecked = selectedLeadIds.has(lead.id) ? 'checked' : '';
        return `
        <tr>
            <td class="lead-select-col"><input type="checkbox" class="lead-select-box" value="${escapeHtml(lead.id)}" ${isChecked}></td>
            <td>${escapeHtml(lead.id?.slice(0,8) || '')}</td>
            <td>${custDisplay}</td>
            <td><span class="priority-badge ${priorityVal}">${priorityVal.toUpperCase()}</span></td>
            <td>${escapeHtml(lead.lead_source || '')}</td>
            <td>${escapeHtml(lead.sport || '')}</td>
            <td>${escapeHtml(lead.assigned_user?.user_name || 'Unassigned')}</td>
            <td><span class="status-badge ${statusColor(lead.lead_status)}">${escapeHtml(lead.lead_status)}</span></td>
            <td>${escapeHtml(lead.enquiry_date || '')}</td>
            <td><button class="btn btn-secondary btn-sm" onclick="viewLead('${lead.id}')">View</button></td>
        </tr>
        `;
    }).join('') : `<tr><td colspan="10"><div class="empty-state"><h3>No leads found</h3><p>Try changing filters or create a new lead.</p></div></td></tr>`;

    // Bind individual row checkboxes
    tbody.querySelectorAll('.lead-select-box').forEach(cb => {
        cb.addEventListener('change', (e) => {
            const id = e.target.value;
            if (e.target.checked) {
                selectedLeadIds.add(id);
            } else {
                selectedLeadIds.delete(id);
            }
            updateBulkToolbarState(leads);
        });
    });

    updateBulkToolbarState(leads);
}

async function renderLeadCalendar(){
    const calendar = document.getElementById('leadCalendar');
    const leads = allLeads.filter(lead => lead.enquiry_date);
    const start = new Date();
    start.setDate(1);
    const monthDayCount = new Date(start.getFullYear(), start.getMonth()+1, 0).getDate();
    const headings = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const cells = [];
    headings.forEach(h => cells.push(`<div class="calendar-head">${h}</div>`));
    for (let i = 0; i < (new Date(start.getFullYear(), start.getMonth(), 1).getDay()); i++) cells.push('<div class="calendar-empty"></div>');
    for (let d = 1; d <= monthDayCount; d++) {
        const dateStr = new Date(start.getFullYear(), start.getMonth(), d).toISOString().slice(0,10);
        const dayLeads = leads.filter(lead => lead.enquiry_date === dateStr);
        cells.push(`<div class="calendar-day" style="border:1px solid var(--border); border-radius:8px; padding:8px; min-height:90px;">
            <strong>${d}</strong>
            ${dayLeads.slice(0,3).map(lead => `<div style="font-size:0.72rem; margin-top:6px; background:var(--surface-2); border-radius:6px; padding:4px 6px;">${escapeHtml(lead.customer?.name || 'Lead')}</div>`).join('')}
        </div>`);
    }
    calendar.innerHTML = cells.join('');
}

function switchLeadView(view){
    currentLeadView = view;
    const kanban = document.getElementById('leadKanban').closest('.card');
    const table = document.getElementById('leadTableCard');
    const calendar = document.getElementById('leadCalendarCard');
    kanban.style.display = view === 'kanban' ? 'block' : 'none';
    table.style.display = view === 'table' ? 'block' : 'none';
    calendar.style.display = view === 'calendar' ? 'block' : 'none';
}

function statusColor(status){
  const map = {
    'Received':'status-blue','Analysis':'status-orange','Awaiting Clarification':'status-yellow','Order Sheet Prepared':'status-purple','Verified':'status-cyan','Awaiting Approval':'status-yellow','Approved':'status-green','Awaiting Payment':'status-red','Moved to Production':'status-darkgreen','Lost':'status-lost'
  };
  return map[status] || 'status-blue';
}

function escapeHtml(str){ return String(str ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch])); }

function saveLead(){
    const form = document.getElementById('leadForm');
    const isEdit = !!currentLeadId;

    const customerSelect = document.getElementById('lead_customer_id');
    const customerId = customerSelect ? customerSelect.value : '';
    if (!customerId) {
        showToast('Please select a customer.', 'error');
        if (customerSelect) customerSelect.focus();
        return;
    }

    const priorityInput = form.querySelector('input[name="priority"]:checked');
    const priority = priorityInput ? priorityInput.value : 'medium';

    const payload = new FormData(form);
    payload.append('action', isEdit ? 'update' : 'create');
    payload.set('customer_id', customerId);
    payload.set('priority', priority);

    if (isEdit) {
        const targetLead = allLeads.find(l => l.id === currentLeadId);
        if (currentUserRole === 'sales_executive' && targetLead && targetLead.assigned_to !== currentUserId) {
            showToast('Permission denied. Sales Executives can only edit leads assigned to them.', 'error');
            return;
        }

        const body = {
            id: currentLeadId,
            customer_id: customerId,
            priority: priority,
            lead_source: form.querySelector('input[name="lead_source"]:checked')?.value || '',
            enquiry_date: form.elements['enquiry_date'].value,
            products_interested: Array.from(form.querySelectorAll('input[name="products_interested[]"]:checked')).map(item => item.value),
            sport: form.querySelector('input[name="sport"]:checked')?.value || '',
            design_source: form.querySelector('input[name="design_source"]:checked')?.value || '',
            submission_id: form.elements['submission_id'].value,
            design_link: form.elements['design_link'].value,
            player_roster_received: form.querySelector('input[name="player_roster_received"]:checked')?.value || 'Not Received',
            notes: form.elements['notes'].value,
            send_whatsapp_notification: form.querySelector('input[name="send_whatsapp_notification"]').checked ? true : false,
            assigned_to: (currentUserRole === 'sales_executive' && targetLead) ? targetLead.assigned_to : form.elements['assigned_to'].value,
            lead_status: form.elements['lead_status'].value
        };

        fetch(LEAD_API, {
            method: 'PUT',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body)
        })
        .then(res => res.json())
        .then(json => {
            if (!json.success) { showToast(json.message || 'Could not update lead', 'error'); return; }
            showToast('Lead updated successfully', 'success');
            closeModal('leadModal');
            form.reset();
            currentLeadId = null;
            document.getElementById('leadModalTitle').textContent = 'Create Lead';
            document.querySelector('#leadModal .modal-footer .btn.btn-primary').textContent = 'Create Lead';
            renderKanban();
        })
        .catch(() => showToast('Failed to update lead', 'error'));
        return;
    }

    if (currentUserRole === 'sales_executive') {
        payload.set('assigned_to', currentUserId);
    }

    fetch(LEAD_API + '?action=create', {method:'POST', credentials:'same-origin', body: payload})
      .then(res => res.json())
      .then(json => {
          if (!json.success) { showToast(json.message || 'Could not create lead', 'error'); return; }
          showToast('Lead created successfully', 'success');
          closeModal('leadModal');
          form.reset();
          renderKanban();
      })
      .catch(() => showToast('Failed to create lead', 'error'));
}

async function viewLead(id){
    currentLeadId = id;
    const res = await fetch(LEAD_API + '?action=get&id=' + encodeURIComponent(id), {credentials:'same-origin'});
    const json = await res.json();
    if (!json.success || !json.data) {
        showToast(json.message || 'Lead details unavailable', 'error');
        return;
    }

    const lead = json.data;
    const custDisplay = (lead.customer && lead.customer.name)
        ? `<a href="customer-detail.php?id=${encodeURIComponent(lead.customer_id)}" style="color:var(--accent);text-decoration:underline;font-weight:600;" title="View Full Customer Profile">${escapeHtml(lead.customer.name)}${lead.customer.team_academy_name ? ' — ' + escapeHtml(lead.customer.team_academy_name) : ''} ↗</a>`
        : (lead.customer_id ? `<a href="customer-detail.php?id=${encodeURIComponent(lead.customer_id)}" style="color:var(--accent);">View Customer Profile ↗</a>` : 'N/A');
    const priorityVal = escapeHtml(lead.priority || 'medium');

    const customFiles = Array.isArray(lead.custom_design_file) ? lead.custom_design_file : [];
    const playerFiles = Array.isArray(lead.player_list_file) ? lead.player_list_file : [];
    let attachmentsHtml = '';
    if (customFiles.length || playerFiles.length) {
        attachmentsHtml = '<div class="lead-detail-box" style="grid-column:1 / -1;"><strong>Uploaded Attachments (Secure Authenticated Storage)</strong><div style="display:flex; flex-direction:column; gap:8px; margin-top:8px;">';
        customFiles.forEach((f, idx) => {
            if (f && f.name) {
                const downloadUrl = `../api/leads.php?action=attachment&lead_id=${encodeURIComponent(lead.id)}&type=design&index=${idx}`;
                attachmentsHtml += `
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:8px 12px; background:var(--surface); border:1px solid var(--border); border-radius:8px; font-size:0.85rem;">
                        <div style="display:flex; align-items:center; gap:8px; overflow:hidden;">
                            <span style="font-size:1.1rem">🎨</span>
                            <span style="font-weight:600; color:var(--text-primary)">Custom Design:</span>
                            <code style="word-break:break-all;">${escapeHtml(f.name)}</code>
                            <span style="color:var(--text-muted); font-size:0.75rem;">(${f.size ? Math.round(f.size/1024) + ' KB' : ''})</span>
                        </div>
                        <div style="display:flex; gap:6px; flex-shrink:0;">
                            <a href="${downloadUrl}" target="_blank" class="btn btn-ghost btn-sm" style="font-size:0.75rem; padding:4px 10px; text-decoration:none;" title="Secure Authenticated View / Download">
                                View / Download ↗
                            </a>
                        </div>
                    </div>`;
            }
        });
        playerFiles.forEach((f, idx) => {
            if (f && f.name) {
                const downloadUrl = `../api/leads.php?action=attachment&lead_id=${encodeURIComponent(lead.id)}&type=roster&index=${idx}&download=1`;
                attachmentsHtml += `
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding:8px 12px; background:var(--surface); border:1px solid var(--border); border-radius:8px; font-size:0.85rem;">
                        <div style="display:flex; align-items:center; gap:8px; overflow:hidden;">
                            <span style="font-size:1.1rem">📋</span>
                            <span style="font-weight:600; color:var(--text-primary)">Player Roster:</span>
                            <code style="word-break:break-all;">${escapeHtml(f.name)}</code>
                            <span style="color:var(--text-muted); font-size:0.75rem;">(${f.size ? Math.round(f.size/1024) + ' KB' : ''})</span>
                        </div>
                        <div style="display:flex; gap:6px; flex-shrink:0;">
                            <a href="${downloadUrl}" class="btn btn-ghost btn-sm" style="font-size:0.75rem; padding:4px 10px; text-decoration:none;" title="Download Roster Spreadsheet">
                                Download ⤓
                            </a>
                        </div>
                    </div>`;
            }
        });
        attachmentsHtml += '</div></div>';
    }

    const detailContent = document.getElementById('leadDetailContent');
    detailContent.innerHTML = `
        <div class="lead-detail-grid">
            <div class="lead-detail-box"><strong>Customer</strong><div>${custDisplay}</div></div>
            <div class="lead-detail-box"><strong>Priority</strong><div><span class="priority-badge ${priorityVal}">${priorityVal.toUpperCase()}</span></div></div>
            <div class="lead-detail-box"><strong>Lead Source</strong><div>${escapeHtml(lead.lead_source || 'N/A')}</div></div>
            <div class="lead-detail-box"><strong>Sport</strong><div>${escapeHtml(lead.sport || 'N/A')}</div></div>
            <div class="lead-detail-box"><strong>Status</strong><div><span class="status-badge ${statusColor(lead.lead_status)}">${escapeHtml(lead.lead_status || 'N/A')}</span></div></div>
            <div class="lead-detail-box"><strong>Assigned To</strong><div>${escapeHtml(lead.assigned_user?.user_name || 'Unassigned')}</div></div>
            <div class="lead-detail-box"><strong>Enquiry Date</strong><div>${escapeHtml(lead.enquiry_date || 'N/A')}</div></div>
            <div class="lead-detail-box"><strong>Submission ID</strong><div>${escapeHtml(lead.submission_id || 'N/A')}</div></div>
            <div class="lead-detail-box"><strong>Design Source</strong><div>${escapeHtml(lead.design_source || 'N/A')}</div></div>
            <div class="lead-detail-box" style="grid-column:1 / -1;"><strong>Products Interested</strong><div>${escapeHtml((lead.products_interested || []).join(', ') || 'N/A')}</div></div>
            <div class="lead-detail-box" style="grid-column:1 / -1;"><strong>Notes</strong><div>${escapeHtml(lead.notes || 'No notes')}</div></div>
            ${attachmentsHtml}
        </div>

        <!-- WhatsApp Communication Card (Phase 6) -->
        <div class="whatsapp-card" style="margin-top:16px; background:var(--surface-2); border:1px solid var(--border); border-radius:12px; padding:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:1.15rem">💬</span>
                    <strong style="font-size:0.92rem; color:var(--text-primary)">WhatsApp Customer Communication</strong>
                    <span id="waModalStatusBadge">${waStatusBadge(lead.whatsapp_status)}</span>
                </div>
                <div>
                    <span style="font-size:0.78rem; color:var(--text-muted);">Customer Phone:</span>
                    <strong style="font-size:0.85rem; color:var(--text-primary); margin-left:4px;">${escapeHtml(lead.customer?.country_code || '+91')} ${escapeHtml(lead.customer?.mobile_number || 'N/A')}</strong>
                </div>
            </div>

            ${(lead.can_edit !== false && (currentUserRole !== 'sales_executive' || lead.assigned_to === currentUserId)) ? `
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:10px;">
                    <select id="modalWaTemplateSelect" class="form-control" style="max-width:320px; font-size:0.84rem;" onchange="updateWaPreview(this.value, '${escapeHtml(lead.id)}')">
                        <option value="ira_lead_acknowledgement">Enquiry Acknowledgement (PDF Page 6)</option>
                        <option value="ira_clarification_request">Clarification Request</option>
                        <option value="ira_order_sheet_update">Order Sheet Prepared</option>
                        <option value="ira_order_approved">Order Approved</option>
                        <option value="ira_moved_to_production">Moved to Production</option>
                    </select>
                    <button type="button" class="btn btn-neon btn-sm" id="sendWaModalBtn" onclick="sendWhatsAppFromModal('${escapeHtml(lead.id)}')">
                        Send WhatsApp Notification
                    </button>
                </div>
                <div id="waPreviewBox" style="margin-top:8px; font-size:0.82rem; background:rgba(0,230,118,0.06); border:1px solid rgba(0,230,118,0.25); border-radius:8px; padding:10px; color:var(--text-primary); line-height:1.4;">
                    <strong>Message Preview:</strong> <span id="waPreviewText"></span>
                </div>
            ` : '<div style="font-size:0.8rem; color:var(--text-muted); padding:8px 0;">WhatsApp message actions restricted to assigned representative or managers.</div>'}

            <div style="margin-top:14px; border-top:1px dashed var(--border); padding-top:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <strong style="font-size:0.82rem; color:var(--text-secondary)">Message Delivery History</strong>
                    <button type="button" class="btn btn-ghost btn-sm" style="font-size:0.75rem; padding:2px 8px;" onclick="loadWaHistory('${escapeHtml(lead.id)}')">↻ Refresh</button>
                </div>
                <div id="leadWaHistory" style="display:flex; flex-direction:column; gap:6px;">
                    <span style="font-size:0.78rem; color:var(--text-muted)">Loading delivery history...</span>
                </div>
            </div>
        </div>
    `;
    document.getElementById('leadDetailTitle').textContent = 'Lead ' + (lead.id || '').slice(0,8);
    updateWaPreview('ira_lead_acknowledgement', lead.id);
    loadWaHistory(lead.id);

    const activityContainer = document.getElementById('leadActivityLog');
    const activityRes = await fetch(LEAD_API + '?action=activity&lead_id=' + encodeURIComponent(id), {credentials:'same-origin'});
    const activityJson = await activityRes.json();
    const activities = activityJson.data || [];
    activityContainer.innerHTML = activities.length ? activities.map(item => `
        <div class="activity-item">
            <strong>${escapeHtml(item.action || 'Update')}</strong>
            <div>${escapeHtml(item.details || 'No description')}</div>
            <small>${escapeHtml(item.created_at || '')}</small>
        </div>
    `).join('') : '<div class="activity-item">No activity recorded yet.</div>';

    const modal = document.getElementById('leadDetailModal');
    if (modal) {
        const delBtn = document.getElementById('deleteLeadBtn');
        if (delBtn) {
            delBtn.style.display = (lead.can_delete !== undefined ? lead.can_delete : currentUserRole === 'admin') ? 'inline-block' : 'none';
        }
        const editBtn = document.getElementById('editLeadBtn');
        if (editBtn) {
            const canEdit = (lead.can_edit !== undefined ? lead.can_edit : (currentUserRole !== 'sales_executive' || lead.assigned_to === currentUserId));
            editBtn.style.display = canEdit ? 'inline-block' : 'none';
        }
        modal.classList.add('show');
        modal.classList.add('active');
    }
}

function openEditLeadModal(){
    if (!currentLeadId) return;
    const lead = allLeads.find(item => item.id === currentLeadId);
    if (!lead) return;
    if (currentUserRole === 'sales_executive' && lead.assigned_to !== currentUserId) {
        showToast('Permission denied. Sales Executives can only edit leads assigned to them.', 'error');
        return;
    }
    document.getElementById('leadModalTitle').textContent = 'Edit Lead';
    const createButton = document.querySelector('#leadModal .modal-footer .btn.btn-primary');
    if (createButton) createButton.textContent = 'Update Lead';
    populateLeadForm(lead);
    closeModal('leadDetailModal');
    openLeadModal();
}

function populateLeadForm(lead){
    const form = document.getElementById('leadForm');
    form.reset();

    const setRadio = (name, value) => {
        const field = form.querySelector(`input[name="${name}"][value="${value}"]`);
        if (field) field.checked = true;
    };

    setRadio('lead_source', lead.lead_source || '');
    setRadio('sport', lead.sport || '');
    setRadio('design_source', lead.design_source || '');
    setRadio('player_roster_received', lead.player_roster_received || 'Not Received');
    setRadio('priority', lead.priority || 'medium');

    form.elements['enquiry_date'].value = lead.enquiry_date || '';
    form.elements['submission_id'].value = lead.submission_id || '';
    form.elements['design_link'].value = lead.design_link || '';
    form.elements['notes'].value = lead.notes || '';
    form.elements['assigned_to'].value = lead.assigned_to || '';
    form.elements['lead_status'].value = lead.lead_status || 'Received';
    form.querySelector('input[name="send_whatsapp_notification"]').checked = !!lead.send_whatsapp_notification;

    const customerId = lead.customer_id || (lead.customer && lead.customer.id) || '';
    populateCustomerSelect(allCustomers, customerId);
    const searchInput = document.getElementById('leadCustomerSearch');
    if (searchInput) {
        const found = allCustomers.find(c => c.id === customerId);
        searchInput.value = found ? found.name : '';
    }
    updateSelectedCustomerHint();

    const products = lead.products_interested || [];
    form.querySelectorAll('input[name="products_interested[]"]').forEach(input => {
        input.checked = products.includes(input.value);
    });

    updateLeadFormVisibility();
}

async function deleteLeadFromDetail(){
    if (!currentLeadId) return;
    if (!confirm('Delete this lead permanently?')) return;
    const res = await fetch(LEAD_API + '?id=' + encodeURIComponent(currentLeadId), {method:'DELETE', credentials:'same-origin'});
    const json = await res.json();
    if (!json.success) {
        showToast(json.message || 'Delete failed', 'error');
        return;
    }
    showToast('Lead deleted successfully', 'success');
    closeModal('leadDetailModal');
    await renderKanban();
}

function exportLeads(){
    const assigned = document.getElementById('leadAssignedFilter')?.value || '';
    const sport = document.getElementById('leadSportFilter')?.value || '';
    const source = document.getElementById('leadSourceFilter')?.value || '';
    const dateFrom = document.getElementById('leadDateFrom')?.value || '';
    const dateTo = document.getElementById('leadDateTo')?.value || '';

    const params = new URLSearchParams({ action: 'export' });
    if (assigned) params.set('assigned_to', assigned);
    if (sport) params.set('sport', sport);
    if (source) params.set('lead_source', source);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);

    window.open(LEAD_API + '?' + params.toString(), '_blank');
}

function waStatusBadge(status) {
    const s = String(status || 'not_requested').toLowerCase();
    const map = {
        'read': '<span class="badge badge-neon" style="font-size:0.72rem">READ 👁️</span>',
        'delivered': '<span class="badge" style="background:rgba(0,230,118,0.15);color:#00a84d;border:1px solid rgba(0,230,118,0.4);font-size:0.72rem">DELIVERED 📬</span>',
        'sent': '<span class="badge badge-info" style="font-size:0.72rem">SENT ✉️</span>',
        'requested': '<span class="badge badge-warning" style="font-size:0.72rem">REQUESTED 🕒</span>',
        'failed': '<span class="badge badge-danger" style="font-size:0.72rem">FAILED ⚠️</span>',
        'not_requested': '<span class="badge badge-default" style="font-size:0.72rem">NOT REQUESTED</span>'
    };
    return map[s] || `<span class="badge badge-default">${escapeHtml(s.toUpperCase())}</span>`;
}

function updateWaPreview(templateName, leadId) {
    const box = document.getElementById('waPreviewText');
    if (!box) return;

    const lead = allLeads.find(l => l.id === leadId);
    const customerName = lead?.customer?.name || 'Customer';
    const ref = (leadId || '').slice(0, 8);
    const rep = lead?.assigned_user?.user_name || 'Ira Sales Team';
    const sport = lead?.sport || 'Sportswear';
    const team = lead?.customer?.team_academy_name || customerName;

    const previews = {
        'ira_lead_acknowledgement': `Hello ${customerName}, Thank you for contacting Ira Sportswear. We have received your enquiry (Ref: ${ref}) and our team is currently reviewing your requirements. Your assigned sales representative ${rep} will contact you shortly. Thank you. Team Ira Sportswear`,
        'ira_clarification_request': `Hello ${customerName}, regarding your Ira Sportswear order (Ref: ${ref}), our design team needs additional clarification: Design specifications & player sizes. Please reply with the details.`,
        'ira_order_sheet_update': `Hello ${customerName}, your ${sport} apparel order sheet has been prepared and under verification. Our team will follow up for final approval. Team Ira Sportswear`,
        'ira_order_approved': `Hello ${customerName}, your custom sportswear order (Ref: ${ref}) has been approved! We are scheduling production.`,
        'ira_moved_to_production': `Hello ${customerName}, great news! The custom ${sport} uniforms for ${team} have been moved to production. Thank you for choosing Ira Sportswear.`
    };

    box.textContent = previews[templateName] || 'Template message';
}

async function sendWhatsAppFromModal(leadId) {
    const select = document.getElementById('modalWaTemplateSelect');
    const templateName = select ? select.value : 'ira_lead_acknowledgement';

    if (!confirm('Send this official WhatsApp notification to the customer?')) {
        return;
    }

    const btn = document.getElementById('sendWaModalBtn');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Sending...';
    }

    try {
        const res = await fetch('../api/whatsapp.php?action=send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ lead_id: leadId, template_name: templateName })
        });
        const json = await res.json();

        if (json.success) {
            showToast('WhatsApp notification sent successfully!', 'success');
            const badge = document.getElementById('waModalStatusBadge');
            if (badge) badge.innerHTML = waStatusBadge('sent');
            await loadWaHistory(leadId);
            await renderKanban();
        } else {
            showToast(json.message || 'Failed to send WhatsApp message', 'error');
            const badge = document.getElementById('waModalStatusBadge');
            if (badge) badge.innerHTML = waStatusBadge('failed');
            await loadWaHistory(leadId);
        }
    } catch (e) {
        showToast('Network error while sending WhatsApp message', 'error');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Send WhatsApp Notification';
        }
    }
}

async function loadWaHistory(leadId) {
    const container = document.getElementById('leadWaHistory');
    if (!container) return;

    try {
        const res = await fetch('../api/whatsapp.php?action=history&lead_id=' + encodeURIComponent(leadId), {
            credentials: 'same-origin'
        });
        const json = await res.json();
        const logs = json.data || [];

        if (!logs.length) {
            container.innerHTML = '<span style="font-size:0.78rem; color:var(--text-muted);">No WhatsApp messages recorded for this lead yet.</span>';
            return;
        }

        container.innerHTML = logs.map(m => `
            <div style="background:var(--surface); border:1px solid var(--border); border-radius:8px; padding:8px 12px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <div style="font-weight:700; font-size:0.8rem; color:var(--text-primary)">
                        ${escapeHtml(m.template_name || 'Template')}
                        <span style="font-size:0.7rem; font-weight:400; color:var(--text-muted); margin-left:6px">→ ${escapeHtml(m.recipient_phone || '')}</span>
                    </div>
                    <div style="font-size:0.72rem; color:var(--text-muted); margin-top:2px">
                        ${escapeHtml(m.trigger_type === 'automated' ? '⚡ Automated trigger' : '👤 Manual trigger')} • ${escapeHtml(m.created_at || '')}
                        ${m.error_message ? `<span style="color:#ef5350; margin-left:6px">${escapeHtml(m.error_message)}</span>` : ''}
                    </div>
                </div>
                <div>${waStatusBadge(m.status)}</div>
            </div>
        `).join('');
    } catch (e) {
        container.innerHTML = '<span style="font-size:0.78rem; color:var(--text-muted);">Delivery history unavailable</span>';
    }
}
</script>
</body>
</html>
