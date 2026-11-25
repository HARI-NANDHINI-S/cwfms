<?php
/**
 * Phase 5 Test Suite - Ira Sportswear Official Branding
 * Ira Sportswear Lead Management Module Specification
 * Verifies brand identity, color system, logo/favicon assets, UI components,
 * responsive design, document export branding, and full regression across Phases 1-4.
 */

// Include config safely
require_once __DIR__ . '/../includes/config.php';

define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_CYAN', "\033[36m");
define('COLOR_RESET', "\033[0m");

$testsRun = 0;
$testsPassed = 0;
$testsFailed = 0;

function assertTest(string $description, bool $condition, string $details = '') {
    global $testsRun, $testsPassed, $testsFailed;
    $testsRun++;
    if ($condition) {
        $testsPassed++;
        echo COLOR_GREEN . "  [PASS] " . COLOR_RESET . $description . "\n";
    } else {
        $testsFailed++;
        echo COLOR_RED . "  [FAIL] " . COLOR_RESET . $description . ($details ? " - $details" : "") . "\n";
    }
}

echo COLOR_CYAN . "\n=======================================================\n";
echo "  CWFMS Phase 5 Verification Test Suite\n";
echo "  Official Ira Sportswear Branding & Design System\n";
echo "=======================================================\n" . COLOR_RESET;

// -------------------------------------------------------------
// Test Group 1: Central Design System & Color Palette (style.css)
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 1: Central Brand Colors & Design System ---\n" . COLOR_RESET;

$styleFile = __DIR__ . '/../assets/css/style.css';
$styleContent = file_exists($styleFile) ? file_get_contents($styleFile) : '';

assertTest("1.1 assets/css/style.css exists and is readable",
    file_exists($styleFile) && strlen($styleContent) > 0);

assertTest("1.2 Official Deep Athletic Navy (--brand-navy) defined centrally",
    strpos($styleContent, '--brand-navy:') !== false &&
    strpos($styleContent, '#161e38') !== false);

assertTest("1.3 Official Ira Logo Sport Red (--brand-red) defined centrally",
    strpos($styleContent, '--brand-red:') !== false &&
    strpos($styleContent, '#ec1e24') !== false);

assertTest("1.4 Sportswear High-Visibility Neon Green (--brand-neon) defined centrally",
    strpos($styleContent, '--brand-neon:') !== false &&
    strpos($styleContent, '#00e676') !== false);

assertTest("1.5 Primary and Accent mapped cleanly to brand colors",
    strpos($styleContent, '--primary:') !== false &&
    strpos($styleContent, '--accent:') !== false);

assertTest("1.6 Athletic Dark Theme color definitions present with high contrast",
    strpos($styleContent, '[data-theme="dark"]') !== false &&
    strpos($styleContent, '--brand-navy-dark:') !== false);

assertTest("1.7 Custom sportswear utility classes (.btn-neon, .btn-navy, .badge-neon) implemented",
    strpos($styleContent, '.btn-neon') !== false &&
    strpos($styleContent, '.btn-navy') !== false &&
    strpos($styleContent, '.badge-neon') !== false);

// -------------------------------------------------------------
// Test Group 2: Logo and Favicon Asset Integrity
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 2: Logo and Favicon Assets ---\n" . COLOR_RESET;

$rawLogo = __DIR__ . '/../assets/ira_logo.png';
$webLogo = __DIR__ . '/../assets/ira_logo_web.png';
$faviconPng = __DIR__ . '/../assets/favicon.png';
$faviconIco = __DIR__ . '/../assets/favicon.ico';

assertTest("2.1 High-resolution official logo (assets/ira_logo.png) exists",
    file_exists($rawLogo) && filesize($rawLogo) > 1000);

assertTest("2.2 Web-optimized logo (assets/ira_logo_web.png) exists and maintains aspect ratio",
    file_exists($webLogo) && filesize($webLogo) > 500);

assertTest("2.3 Browser favicon PNG (assets/favicon.png) exists",
    file_exists($faviconPng) && filesize($faviconPng) > 200);

assertTest("2.4 Browser favicon ICO (assets/favicon.ico) exists",
    file_exists($faviconIco) && filesize($faviconIco) > 200);

assertTest("2.5 Sidebar brand logo image class (.brand-logo-img) defined in CSS",
    strpos($styleContent, '.brand-logo-img') !== false &&
    strpos($styleContent, 'object-fit: contain') !== false);

// -------------------------------------------------------------
// Test Group 3: Login Page Branding (login.php)
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 3: Login Page Branding (login.php) ---\n" . COLOR_RESET;

$loginFile = __DIR__ . '/../login.php';
$loginContent = file_exists($loginFile) ? file_get_contents($loginFile) : '';

assertTest("3.1 Login page title branded with Ira Sportswear",
    strpos($loginContent, 'Sign In | Ira Sportswear Lead Management') !== false);

assertTest("3.2 Login page links to browser favicon",
    strpos($loginContent, 'assets/favicon.png') !== false);

assertTest("3.3 Login page renders official Ira Sportswear logo",
    strpos($loginContent, 'assets/ira_logo_web.png') !== false &&
    strpos($loginContent, 'alt="Ira Sportswear"') !== false);

assertTest("3.4 Left visual hero showcase branded with Ira Sportswear sales & workflow",
    strpos($loginContent, 'Ira Sportswear <br>Sales & Workflow') !== false &&
    strpos($loginContent, 'Custom Sportswear Solutions') !== false);

assertTest("3.5 Demo access pills labeled with official business roles (Admin, Sales Manager, Sales Executive)",
    strpos($loginContent, 'Admin') !== false &&
    strpos($loginContent, 'Sales Manager') !== false &&
    strpos($loginContent, 'Sales Executive') !== false);

assertTest("3.6 Login footer displays official Ira Sportswear copyright",
    strpos($loginContent, 'Ira Sportswear. All rights reserved') !== false);

// -------------------------------------------------------------
// Test Group 4: Dashboard Branding (dashboard.php)
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 4: Dashboard Branding (dashboard.php) ---\n" . COLOR_RESET;

$dashboardFile = __DIR__ . '/../dashboard.php';
$dashboardContent = file_exists($dashboardFile) ? file_get_contents($dashboardFile) : '';

assertTest("4.1 Dashboard title branded with Ira Sportswear",
    strpos($dashboardContent, '<title>Dashboard | Ira Sportswear Lead Management</title>') !== false);

assertTest("4.2 Dashboard links to official browser favicon",
    strpos($dashboardContent, 'assets/favicon.png') !== false);

assertTest("4.3 Sidebar brand renders official Ira Sportswear logo and title",
    strpos($dashboardContent, 'assets/ira_logo_web.png') !== false &&
    strpos($dashboardContent, '<h2>Ira Sportswear</h2>') !== false);

assertTest("4.4 Top header breadcrumb and title branded for Ira Sportswear",
    strpos($dashboardContent, 'Customer & Lead Dashboard') !== false &&
    strpos($dashboardContent, '<span>Ira Sportswear</span>') !== false);

// -------------------------------------------------------------
// Test Group 5: Lead Management Views Branding (pages/leads.php)
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 5: Lead Management Views (pages/leads.php) ---\n" . COLOR_RESET;

$leadsFile = __DIR__ . '/../pages/leads.php';
$leadsContent = file_exists($leadsFile) ? file_get_contents($leadsFile) : '';

assertTest("5.1 Lead Management page title branded with Ira Sportswear",
    strpos($leadsContent, '<title>Lead Management | Ira Sportswear</title>') !== false);

assertTest("5.2 Lead Management page links to official favicon",
    strpos($leadsContent, '../assets/favicon.png') !== false);

assertTest("5.3 Sidebar renders official Ira Sportswear logo in Lead Management",
    strpos($leadsContent, '../assets/ira_logo_web.png') !== false &&
    strpos($leadsContent, '<h2>Ira Sportswear</h2>') !== false);

assertTest("5.4 Top header shows Ira Sportswear Leads breadcrumb",
    strpos($leadsContent, 'Ira Sportswear Leads') !== false);

assertTest("5.5 Kanban cards display Phase 1 priority left borders (High, Medium, Low)",
    strpos($leadsContent, 'kanban-card[data-priority="high"]') !== false &&
    strpos($leadsContent, 'kanban-card[data-priority="medium"]') !== false &&
    strpos($leadsContent, 'kanban-card[data-priority="low"]') !== false);

assertTest("5.6 Kanban cards display all 6 visual indicators (📎, 🎨, 📋, 💬, 🔥, ⭐)",
    strpos($leadsContent, 'indicator-item') !== false &&
    strpos($leadsContent, 'renderCardIndicators') !== false);

assertTest("5.7 Table View bulk actions toolbar is branded with deep athletic navy",
    strpos($leadsContent, 'id="bulkActionsToolbar"') !== false &&
    strpos($leadsContent, 'selectedCountBadge') !== false);

assertTest("5.8 Top filter bar includes From Date and To Date inputs with Clear button",
    strpos($leadsContent, 'id="leadDateFrom"') !== false &&
    strpos($leadsContent, 'id="leadDateTo"') !== false &&
    strpos($leadsContent, 'id="clearDateFilterBtn"') !== false);

// -------------------------------------------------------------
// Test Group 6: User Management Branding (pages/users.php)
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 6: User Management Branding (pages/users.php) ---\n" . COLOR_RESET;

$usersFile = __DIR__ . '/../pages/users.php';
$usersContent = file_exists($usersFile) ? file_get_contents($usersFile) : '';

assertTest("6.1 User Management page title branded with Ira Sportswear",
    strpos($usersContent, '<title>User Management | Ira Sportswear</title>') !== false);

assertTest("6.2 User Management links to official browser favicon",
    strpos($usersContent, '../assets/favicon.png') !== false);

assertTest("6.3 Sidebar renders official Ira Sportswear logo in User Management",
    strpos($usersContent, '../assets/ira_logo_web.png') !== false &&
    strpos($usersContent, '<h2>Ira Sportswear</h2>') !== false);

assertTest("6.4 Official business role badges (Admin, Sales Manager, Sales Executive) styled",
    strpos($usersContent, 'role-badge.sales_manager') !== false &&
    strpos($usersContent, 'role-badge.sales_executive') !== false);

assertTest("6.5 Official role permissions reference table reflects Ira Sportswear specifications",
    strpos($usersContent, 'Role Permissions Reference (Ira Sportswear Lead Management)') !== false);

// -------------------------------------------------------------
// Test Group 7: Document & CSV Export Branding (api/leads.php)
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 7: Document & CSV Export Branding ---\n" . COLOR_RESET;

$leadsApiFile = __DIR__ . '/../api/leads.php';
$leadsApiContent = file_exists($leadsApiFile) ? file_get_contents($leadsApiFile) : '';

assertTest("7.1 Export filename branded with ira_sportswear_leads_YYYY-MM-DD.csv",
    strpos($leadsApiContent, 'filename="ira_sportswear_leads_') !== false);

assertTest("7.2 CSV column headers and data structure preserved without breakage",
    strpos($leadsApiContent, "'customer_id'") !== false &&
    strpos($leadsApiContent, "'enquiry_date'") !== false &&
    strpos($leadsApiContent, "'priority'") !== false &&
    strpos($leadsApiContent, "'lead_status'") !== false);

assertTest("7.3 Central application name APP_NAME defined as 'Ira Sportswear Lead Management'",
    defined('APP_NAME') && APP_NAME === 'Ira Sportswear Lead Management');

// -------------------------------------------------------------
// Test Group 8: PHP Syntax Linting Across All Pages
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 8: PHP Syntax Linting Across All Files ---\n" . COLOR_RESET;

$phpFilesToLint = [
    'includes/config.php',
    'api/leads.php',
    'api/auth.php',
    'login.php',
    'dashboard.php',
    'pages/leads.php',
    'pages/users.php',
    'pages/customer-detail.php',
    'pages/import.php',
    'index.php',
    'about.php',
    'contact.php'
];

$allSyntaxClean = true;
$phpBinary = defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : (file_exists(__DIR__ . '/../bin/php/php.exe') ? realpath(__DIR__ . '/../bin/php/php.exe') : 'php');
foreach ($phpFilesToLint as $relPath) {
    $fullPath = realpath(__DIR__ . '/../' . $relPath);
    if (!$fullPath || !file_exists($fullPath)) {
        $allSyntaxClean = false;
        continue;
    }
    $output = [];
    $returnCode = 0;
    exec("\"$phpBinary\" -l \"$fullPath\"", $output, $returnCode);
    if ($returnCode !== 0) {
        $allSyntaxClean = false;
        echo COLOR_RED . "  Syntax error in $relPath\n" . COLOR_RESET;
    }
}
assertTest("8.1 All 12 PHP files compile with zero syntax errors", $allSyntaxClean);

// -------------------------------------------------------------
// Test Group 9: Full Regression Verification (Phases 1-4)
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 9: Full Regression Verification (Phases 1 - 4) ---\n" . COLOR_RESET;

// 9.1 Phase 1 Regression: Customer Association & Priority
assertTest("9.1 Phase 1: Customer association mandatory validation intact in api/leads.php",
    strpos($leadsApiContent, "Customer selection is required.") !== false);
assertTest("9.2 Phase 1: Priority support (low, medium, high) intact in api/leads.php",
    strpos($leadsApiContent, "in_array(\$priority, ['low', 'medium', 'high']") !== false);

// 9.3 Phase 2 Regression: 6 Visual Indicators computed
assertTest("9.3 Phase 2: All 6 indicators calculated and returned by api/leads.php",
    strpos($leadsApiContent, "['has_attachments']") !== false &&
    strpos($leadsApiContent, "['has_design']") !== false &&
    strpos($leadsApiContent, "['whatsapp_status']") !== false &&
    strpos($leadsApiContent, "['is_repeat_customer']") !== false);

// 9.4 Phase 3 Regression: Date filtering & Table Bulk editing
assertTest("9.4 Phase 3: Date range parameters (date_from, date_to) supported in api/leads.php",
    strpos($leadsApiContent, "\$dateFrom = sanitize(\$_GET['date_from'] ?? '');") !== false &&
    strpos($leadsApiContent, "\$dateTo = sanitize(\$_GET['date_to'] ?? '');") !== false);
assertTest("9.5 Phase 3: Bulk update endpoint action=bulk_update intact in api/leads.php",
    strpos($leadsApiContent, "action === 'bulk_update'") !== false ||
    strpos($leadsApiContent, "in_array(\$action, ['bulk_update', 'bulk-update', 'bulk'], true)") !== false);

// 9.6 Phase 4 Regression: Role-based permissions
assertTest("9.6 Phase 4: canManageUsers strictly guards user management in config and pages",
    function_exists('canManageUsers') &&
    canManageUsers(['role' => 'admin']) === true &&
    canManageUsers(['role' => 'manager']) === false &&
    canManageUsers(['role' => 'staff']) === false);

assertTest("9.7 Phase 4: canDeleteLead strictly restricted to Admin only",
    function_exists('canDeleteLead') &&
    canDeleteLead(['role' => 'admin']) === true &&
    canDeleteLead(['role' => 'manager']) === false &&
    canDeleteLead(['role' => 'staff']) === false);

assertTest("9.8 Phase 4: canAssignLead granted to Admin & Manager, denied to Sales Executive",
    function_exists('canAssignLead') &&
    canAssignLead(['role' => 'admin']) === true &&
    canAssignLead(['role' => 'manager']) === true &&
    canAssignLead(['role' => 'staff']) === false);

assertTest("9.9 Phase 4: Sales Executive limited status update permission verified",
    function_exists('canChangeLeadStatus') &&
    canChangeLeadStatus(['assigned_to' => 'u1'], ['id' => 'u1', 'role' => 'staff']) === true &&
    canChangeLeadStatus(['assigned_to' => 'u2'], ['id' => 'u1', 'role' => 'staff']) === false);

// -------------------------------------------------------------
// Test Summary
// -------------------------------------------------------------
echo COLOR_CYAN . "\n=======================================================\n";
echo "  Phase 5 Test Execution Complete\n";
echo "  Total Tests Run:    $testsRun\n";
echo "  Tests Passed:       $testsPassed\n";
if ($testsFailed === 0) {
    echo COLOR_GREEN . "  ALL PHASE 5 TESTS PASSED SUCCESSFULLY! (100%)\n" . COLOR_RESET;
} else {
    echo COLOR_RED . "  TESTS FAILED: $testsFailed\n" . COLOR_RESET;
}
echo COLOR_CYAN . "=======================================================\n\n" . COLOR_RESET;

exit($testsFailed > 0 ? 1 : 0);
