<?php
require_once '../includes/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$user     = getCurrentUser();
$initials = implode('', array_map(fn($p) => strtoupper($p[0]), array_filter(explode(' ', $user['name']))));

$id = sanitize($_GET['id'] ?? '');
if (!$id) {
    header('Location: ../dashboard.php');
    exit;
}

// Fetch customer
$db = new Supabase(true);
$result = $db->select('customers', ['id' => 'eq.' . $id, 'is_deleted' => 'eq.false']);
if ($result['status'] !== 200 || empty($result['data'])) {
    header('Location: ../dashboard.php?error=not_found');
    exit;
}
$c = enrichCustomers($db, [$result['data'][0]])[0];

// Fetch activity log
$logResult = $db->select('activity_logs', [
    'customer_id' => 'eq.' . $id,
    'order'       => 'created_at.desc',
    'limit'       => '20',
]);
$activityLog = $logResult['data'] ?? [];

// Avatar
$nameInitials = implode('', array_map(fn($p) => strtoupper($p[0]), array_filter(explode(' ', $c['name']))));
$nameInitials = substr($nameInitials, 0, 2);

// Log view
$db->logActivity($id, 'viewed', null, $user['name'], 'Profile viewed');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($c['name']) ?> | Customer Management</title>
    <script>(function(){var t=localStorage.getItem('cm-theme');if(t){document.documentElement.setAttribute('data-theme',t)}else if(window.matchMedia('(prefers-color-scheme:dark)').matches){document.documentElement.setAttribute('data-theme','dark')}else{document.documentElement.setAttribute('data-theme','light')}})();</script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%23e53935'/><text y='.9em' font-size='80' x='10'>S3</text></svg>">
</head>
<body>

<div class="app-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-logo">S3</div>
            <div class="brand-text">
                <h2>Studio 3</h2>
                <span>Order Management</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="../dashboard.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="../dashboard.php" class="nav-item active" style="border-left-color:var(--accent)">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Customers
            </a>
            <?php if (hasPermission('create')): ?>
            <a href="import.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Import CSV
            </a>
            <?php endif; ?>
            <div class="nav-section-label" style="margin-top:8px">Coming Soon</div>
            <span class="nav-item" style="opacity:0.4;cursor:not-allowed">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Leads
            </span>
            <span class="nav-item" style="opacity:0.4;cursor:not-allowed">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                Orders
            </span>
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
                <a href="../logout.php" style="margin-left:auto;color:var(--text-muted);opacity:0.7" title="Logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </a>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <header class="top-header">
            <div class="header-title">
                <div class="breadcrumb" style="font-size:0.83rem">
                    <a href="../dashboard.php" style="color:var(--text-muted);text-decoration:none">Customers</a>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><polyline points="9 18 15 12 9 6"/></svg>
                    <span><?= htmlspecialchars($c['name']) ?></span>
                </div>
                <h1 style="margin-top:4px"><?= htmlspecialchars($c['name']) ?></h1>
            </div>
            <div class="header-actions">
    <?php if (hasPermission('edit')): ?>
    <button class="btn btn-secondary btn-sm" onclick="openEditModal()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Edit
    </button>
<?php endif; ?>
<?php if (hasPermission('toggle_status')): ?>
    <?php $isActive = !empty($c['is_active']); ?>
    <button class="btn btn-sm <?= $isActive ? 'btn-danger' : 'btn-success' ?>" onclick="confirmToggleStatus('<?= $c['id'] ?>', '<?= htmlspecialchars($c['name']) ?>', <?= $isActive ? 'true' : 'false' ?>)">
        <?= $isActive ? 'Deactivate' : 'Activate' ?>
    </button>
<?php endif; ?>
<?php if (hasPermission('delete')): ?>
<button class="btn btn-sm btn-danger" onclick="confirmDelete('<?php echo $c['id']; ?>', <?php echo json_encode($c['name']); ?>)">Delete</button>
<?php endif; ?>
            </div>
        </header>

        <div class="page-content">
            <div class="profile-page-grid">

                <!-- LEFT COLUMN -->
                <div class="profile-left">

                    <!-- Profile Card -->
                    <div class="card profile-card-full">
                        <div class="profile-banner"></div>
                        <div class="profile-card-body">
                            <div class="profile-avatar-wrap">
                                <div class="profile-avatar-xl"><?= htmlspecialchars($nameInitials) ?></div>
                            </div>
                            <h2 class="profile-name"><?= htmlspecialchars($c['name']) ?></h2>
                            <p class="profile-team"><?= htmlspecialchars($c['team_academy_name']) ?></p>
                            <div style="margin-bottom: 12px;">
                                <?php if (!empty($c['is_active'])): ?>
                                    <span class="status-badge active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge inactive">Deactivated</span>
                                <?php endif; ?>
                            </div>
                            <div class="profile-id-badge">ID: <?= strtoupper(substr($c['id'], 0, 8)) ?></div>
                        </div>

                        <div class="profile-meta-list">
                            <div class="profile-meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.63 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.56A16 16 0 0 0 16 16.59l.97-.97a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                <span><?= htmlspecialchars($c['country_code']) ?> <?= htmlspecialchars($c['mobile_number']) ?></span>
                            </div>
                            <?php if ($c['email']): ?>
                            <div class="profile-meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                <a href="mailto:<?= htmlspecialchars($c['email']) ?>" style="color:var(--accent)"><?= htmlspecialchars($c['email']) ?></a>
                            </div>
                            <?php endif; ?>
                            <div class="profile-meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span><?= htmlspecialchars($c['city']) ?>, <?= htmlspecialchars($c['state']) ?>, <?= htmlspecialchars($c['country']) ?></span>
                            </div>
                            <div class="profile-meta-item">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span>Added <?= date('d M Y', strtotime($c['created_at'])) ?><?= !empty($c['updated_at']) ? ' · Updated ' . date('d M Y', strtotime($c['updated_at'])) : '' ?></span>
                            </div>
                        </div>

                        <div class="profile-card-actions">
                            <a href="tel:<?= htmlspecialchars($c['country_code'] . $c['mobile_number']) ?>" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.63 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.56A16 16 0 0 0 16 16.59l.97-.97a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                Call
                            </a>
                            <?php if ($c['email']): ?>
                            <a href="mailto:<?= htmlspecialchars($c['email']) ?>" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                Email
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Profile Completeness Score Card -->
                    <div class="card" style="margin-top: 16px; padding: 24px; text-align: center;">
                        <h3 style="font-size: 0.95rem; margin-bottom: 16px; color: var(--text-secondary); text-align: left; display: flex; align-items: center; gap: 6px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                            Profile Completeness
                        </h3>
                        <?php 
                        $score = $c['profile_score'] ?? 0;
                        // Circumference of detail gauge circle: 2 * PI * r = 2 * 3.1416 * 50 = 314.16
                        $dashoffset = 314.16 - ($score / 100) * 314.16;
                        
                        $scoreColor = '#ef4444'; // low
                        if ($score >= 80) $scoreColor = '#10b981'; // high
                        elseif ($score >= 40) $scoreColor = '#f59e0b'; // medium
                        ?>
                        <div class="profile-score-gauge">
                            <svg width="130" height="130" viewBox="0 0 130 130">
                                <circle class="bg-ring" cx="65" cy="65" r="50"></circle>
                                <circle class="fill-ring" cx="65" cy="65" r="50" style="stroke: <?= $scoreColor ?>; stroke-dashoffset: <?= $dashoffset ?>;"></circle>
                            </svg>
                            <div class="score-text">
                                <div class="score-percent"><?= $score ?>%</div>
                                <div class="score-label">Complete</div>
                            </div>
                        </div>
                        <p style="font-size: 0.78rem; color: var(--text-muted); margin-top: 16px; line-height: 1.4;">
                            <?php if ($score === 100): ?>
                                This customer profile is fully complete!
                            <?php else: ?>
                                Complete all optional and required fields in the profile form to reach 100% score.
                            <?php endif; ?>
                        </p>
                    </div>

                </div><!-- /profile-left -->

                <!-- RIGHT COLUMN -->
                <div class="profile-right">

                    <!-- Contact Details Card -->
                    <div class="card">
                        <div class="card-header-simple">
                            <h3>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                Contact Information
                            </h3>
                        </div>
                        <div class="info-grid-full">
                            <div class="info-row">
                                <div class="info-label">Full Name</div>
                                <div class="info-value fw-600"><?= htmlspecialchars($c['name']) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Team / Academy</div>
                                <div class="info-value"><?= htmlspecialchars($c['team_academy_name']) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Email Address</div>
                                <div class="info-value">
                                    <?php if ($c['email']): ?>
                                    <a href="mailto:<?= htmlspecialchars($c['email']) ?>" style="color:var(--accent)"><?= htmlspecialchars($c['email']) ?></a>
                                    <?php else: ?><span class="text-muted">Not provided</span><?php endif; ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Mobile Number</div>
                                <div class="info-value font-mono"><?= htmlspecialchars($c['country_code']) ?> <?= htmlspecialchars($c['mobile_number']) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address Card -->
                    <div class="card" style="margin-top:16px">
                        <div class="card-header-simple">
                            <h3>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                Shipping Address
                            </h3>
                        </div>
                        <div class="info-grid-full">
                            <div class="info-row">
                                <div class="info-label">Address Line 1</div>
                                <div class="info-value"><?= htmlspecialchars($c['address_line_1']) ?></div>
                            </div>
                            <?php if ($c['address_line_2']): ?>
                            <div class="info-row">
                                <div class="info-label">Address Line 2</div>
                                <div class="info-value"><?= htmlspecialchars($c['address_line_2']) ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="info-row">
                                <div class="info-label">City</div>
                                <div class="info-value"><?= htmlspecialchars($c['city']) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">State / Province</div>
                                <div class="info-value"><?= htmlspecialchars($c['state']) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Pincode / ZIP</div>
                                <div class="info-value font-mono"><?= htmlspecialchars($c['pincode']) ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Country</div>
                                <div class="info-value"><?= htmlspecialchars($c['country']) ?></div>
                            </div>
                        </div>

                        <!-- Full address block -->
                        <div style="padding:0 20px 20px">
                            <div class="address-block">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" style="flex-shrink:0;margin-top:2px"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <div>
                                    <?= htmlspecialchars($c['address_line_1']) ?><?= $c['address_line_2'] ? ', ' . htmlspecialchars($c['address_line_2']) : '' ?><br>
                                    <?= htmlspecialchars($c['city']) ?>, <?= htmlspecialchars($c['state']) ?> – <?= htmlspecialchars($c['pincode']) ?><br>
                                    <?= htmlspecialchars($c['country']) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Linked Records Placeholder -->
                    <div class="card" style="margin-top:16px">
                        <div class="card-header-simple">
                            <h3>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                Linked Records
                            </h3>
                        </div>
                        <div style="padding:20px">
                            <div class="linked-records-grid">
                                <div class="linked-record-item">
                                    <div class="linked-count">—</div>
                                    <div class="linked-label">Orders</div>
                                    <div class="linked-sub">Phase 2</div>
                                </div>
                                <div class="linked-record-item">
                                    <div class="linked-count">—</div>
                                    <div class="linked-label">Leads</div>
                                    <div class="linked-sub">Phase 2</div>
                                </div>
                                <div class="linked-record-item">
                                    <div class="linked-count">—</div>
                                    <div class="linked-label">Payments</div>
                                    <div class="linked-sub">Phase 3</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Log -->
                    <div class="card" style="margin-top:16px">
                        <div class="card-header-simple">
                            <h3>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                                Activity Log
                            </h3>
                        </div>
                        <div class="activity-timeline">
                            <?php if (empty($activityLog)): ?>
                            <div style="padding:20px;color:var(--text-muted);font-size:0.83rem;text-align:center">No activity recorded yet.</div>
                            <?php else: ?>
                            <?php foreach ($activityLog as $log):
                                $actionIcons = [
                                    'created'     => 'green',
                                    'updated'     => 'blue',
                                    'activated'   => 'orange',
                                    'deactivated' => 'orange',
                                    'deleted'     => 'red',
                                    'viewed'      => 'gray',
                                ];
                                $color = $actionIcons[$log['action']] ?? 'gray';
                            ?>
                            <div class="activity-item">
                                <div class="activity-dot <?= $color ?>"></div>
                                <div class="activity-body">
                                    <div class="activity-title">
                                        <strong><?= ucfirst(htmlspecialchars($log['action'])) ?></strong>
                                        <?php if (!empty($log['description'])): ?> — <?= htmlspecialchars($log['description']) ?><?php endif; ?>
                                    </div>
                                    <div class="activity-meta">
                                        <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div><!-- /profile-right -->
            </div>
        </div><!-- /page-content -->
    </div><!-- /main-content -->
</div><!-- /app-wrapper -->

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
                <button class="btn btn-secondary" onclick="closeAllModals()">Cancel</button>
                <button class="btn btn-primary" id="confirmStatusBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="customerModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div>
                <h3 id="modalTitle">Edit Customer</h3>
                <p id="modalSubtitle">Update customer profile</p>
            </div>
            <button class="modal-close" onclick="closeAllModals()">
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

                <div class="section-divider"><h4><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Basic Information</h4></div>
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="fieldName">Customer Name <span class="required">*</span></label>
                        <input type="text" class="form-control" id="fieldName" name="name" value="<?= htmlspecialchars($c['name']) ?>" maxlength="255">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="fieldTeam">Team / Academy Name <span class="required">*</span></label>
                        <input type="text" class="form-control" id="fieldTeam" name="team_academy_name" value="<?= htmlspecialchars($c['team_academy_name']) ?>" maxlength="255">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="fieldEmail">Email Address <span class="optional">(Optional)</span></label>
                        <input type="email" class="form-control" id="fieldEmail" name="email" value="<?= htmlspecialchars($c['email'] ?? '') ?>" maxlength="255">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mobile Number <span class="required">*</span></label>
                        <div class="phone-group">
                            <select class="form-control" id="fieldCountryCode" name="country_code">
                                <?php
                                $codes = ['+91'=>'🇮🇳 +91 (India)','+971'=>'🇦🇪 +971 (UAE)','+1'=>'🇺🇸 +1 (USA)','+44'=>'🇬🇧 +44 (UK)','+61'=>'🇦🇺 +61 (Australia)','+65'=>'🇸🇬 +65 (Singapore)','+60'=>'🇲🇾 +60 (Malaysia)','+94'=>'🇱🇰 +94 (Sri Lanka)','+880'=>'🇧🇩 +880 (Bangladesh)','+977'=>'🇳🇵 +977 (Nepal)','+33'=>'🇫🇷 +33 (France)','+49'=>'🇩🇪 +49 (Germany)','+81'=>'🇯🇵 +81 (Japan)','+86'=>'🇨🇳 +86 (China)','+966'=>'🇸🇦 +966 (Saudi Arabia)'];
                                foreach ($codes as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $c['country_code'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="tel" class="form-control" id="fieldMobile" name="mobile_number" value="<?= htmlspecialchars($c['mobile_number']) ?>" maxlength="15" pattern="[0-9]{7,15}">
                        </div>
                        <span class="form-error"></span>
                    </div>
                </div>
                <div class="section-divider"><h4><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Shipping Address</h4></div>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label" for="fieldAddress1">Address Line 1 <span class="required">*</span></label>
                        <input type="text" class="form-control" id="fieldAddress1" name="address_line_1" value="<?= htmlspecialchars($c['address_line_1']) ?>" maxlength="500">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group full-width">
                        <label class="form-label" for="fieldAddress2">Address Line 2 <span class="optional">(Optional)</span></label>
                        <input type="text" class="form-control" id="fieldAddress2" name="address_line_2" value="<?= htmlspecialchars($c['address_line_2'] ?? '') ?>" maxlength="500">
                    </div>
                </div>
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label class="form-label" for="fieldCity">City <span class="required">*</span></label>
                        <input type="text" class="form-control" id="fieldCity" name="city" value="<?= htmlspecialchars($c['city']) ?>" maxlength="100">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="fieldState">State / Province <span class="required">*</span></label>
                        <input type="text" class="form-control" id="fieldState" name="state" value="<?= htmlspecialchars($c['state']) ?>" maxlength="100">
                        <span class="form-error"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="fieldPincode">Pincode <span class="required">*</span></label>
                        <input type="text" class="form-control" id="fieldPincode" name="pincode" value="<?= htmlspecialchars($c['pincode']) ?>" maxlength="20">
                        <span class="form-error"></span>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="fieldCountry">Country <span class="required">*</span></label>
                        <select class="form-control" id="fieldCountry" name="country">
                            <option value="">— Select Country —</option>
                            <?php
                            $countries = ['India','UAE','USA','United Kingdom','Australia','Singapore','Malaysia','Sri Lanka','Bangladesh','Nepal','Canada','France','Germany','Japan','China','Saudi Arabia','Other'];
                            foreach ($countries as $country): ?>
                            <option value="<?= $country ?>" <?= $c['country'] === $country ? 'selected' : '' ?>><?= $country ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-error"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAllModals()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Update Customer
                </button>
            </div>
        </form>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script src="../assets/js/animations.js"></script>
<script src="../assets/js/app.js"></script>
<script>
const CUSTOMER_ID = '<?= $c['id'] ?>';

function openEditModal() {
    editingId = CUSTOMER_ID;
    updateFormScoreIndicator();
    document.getElementById('customerModal').classList.add('active');
}

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
    confirmBtn.onclick = async () => {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Updating…';
        try {
            const res = await apiFetch('../api/customers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'toggle_status', id, is_active: newStatus})
            });
            const json = await res.json();
            if (json.success) {
                showToast(`Customer status updated successfully`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(json.message || 'Status update failed', 'error');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirm';
            }
        } catch (e) {
            showToast('Network error', 'error');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirm';
        }
    };
    statusModal.classList.add('active');
}
    const statusModal = document.getElementById('statusModal');
    const confirmBtn = document.getElementById('confirmStatusBtn');
    const nameEl = document.getElementById('statusCustomerName');
    
    if (!statusModal || !confirmBtn) return;
    
    const newStatus = !currentStatus;
    const statusWord = newStatus ? 'Activate' : 'Deactivate';
    
    titleEl.textContent = `${statusWord} Customer?`;
    if (nameEl) nameEl.textContent = name;
    msgEl.innerHTML = `Are you sure you want to <strong>${statusWord.toLowerCase()}</strong> the customer profile for <strong>${escHtml(name)}</strong>?`;
    
    confirmBtn.className = newStatus ? 'btn btn-primary' : 'btn btn-warning';
    confirmBtn.onclick = async () => {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Updating…';
        try {
            const res = await apiFetch('../api/customers.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'toggle_status', id, is_active: newStatus})
            });
            const json = await res.json();
            if (json.success) {
                showToast(`Customer status updated successfully`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(json.message || 'Status update failed', 'error');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirm';
            }
        } catch (e) {
            showToast('Network error', 'error');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirm';
        }
    };
    statusModal.classList.add('active');
}

// Bind real-time form scoring
const detailsForm = document.getElementById('customerForm');
if (detailsForm) {
    const formInputs = detailsForm.querySelectorAll('input, select');
    formInputs.forEach(input => {
        input.addEventListener('input', updateFormScoreIndicator);
        input.addEventListener('change', updateFormScoreIndicator);
    });
}

function closeAllModals() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
}

// Override form submit for page context
document.getElementById('customerForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    if (!validateForm()) return;
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = `<svg class="spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Saving…`;

    const data = {
        action: 'update', id: CUSTOMER_ID,
        name: document.getElementById('fieldName').value.trim(),
        team_academy_name: document.getElementById('fieldTeam').value.trim(),
        email: document.getElementById('fieldEmail').value.trim() || null,
        country_code: document.getElementById('fieldCountryCode').value,
        mobile_number: document.getElementById('fieldMobile').value.trim(),
        address_line_1: document.getElementById('fieldAddress1').value.trim(),
        address_line_2: document.getElementById('fieldAddress2').value.trim() || null,
        city: document.getElementById('fieldCity').value.trim(),
        state: document.getElementById('fieldState').value.trim(),
        pincode: document.getElementById('fieldPincode').value.trim(),
        country: document.getElementById('fieldCountry').value.trim(),
    };

    try {
        const res = await apiFetch('../api/customers.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const json = await res.json();
        if (json.success) {
            showToast('Customer updated successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(json.message || 'Update failed', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Update Customer';
        }
    } catch (err) {
        showToast('Network error', 'error');
        submitBtn.disabled = false;
    }
});
</script>
</body>
</html>
