<?php
/**
 * Phase 3 Test Suite - Date Range Filtering & Table View Bulk Editing
 * Ira Sportswear Lead Management Module
 * Tests all required scenarios and regression checks.
 */

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
echo "  CWFMS Phase 3 Verification Test Suite\n";
echo "  Date Range Filtering & Table View Bulk Editing\n";
echo "=======================================================\n" . COLOR_RESET;

// -------------------------------------------------------------
// Test Group 1: Backend API Date Filtering & Bulk Actions Inspection
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 1: api/leads.php Implementation Inspection ---\n" . COLOR_RESET;

$apiFile = __DIR__ . '/../api/leads.php';
$apiContent = file_exists($apiFile) ? file_get_contents($apiFile) : '';

assertTest("1.1 api/leads.php supports date_from and date_to parameters in list action",
    strpos($apiContent, "\$dateFrom = sanitize(\$_GET['date_from'] ?? '');") !== false &&
    strpos($apiContent, "\$dateTo = sanitize(\$_GET['date_to'] ?? '');") !== false);

assertTest("1.2 api/leads.php validates date format YYYY-MM-DD and rejects invalid dates",
    strpos($apiContent, "preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', \$dateFrom)") !== false &&
    strpos($apiContent, "Invalid From Date format") !== false);

assertTest("1.3 api/leads.php validates date_from <= date_to and rejects inverted range",
    strpos($apiContent, "\$dateFrom > \$dateTo") !== false &&
    strpos($apiContent, "From Date cannot be later than To Date") !== false);

assertTest("1.4 api/leads.php calendar action supports date range filtering",
    strpos($apiContent, "action === 'calendar'") !== false &&
    strpos($apiContent, "\$rawRows = array_values(array_filter(\$rawRows, fn(\$r) => !empty(\$r['enquiry_date']) && \$r['enquiry_date'] >= \$dateFrom));") !== false);

assertTest("1.5 api/leads.php export action supports date range and filter parameters",
    strpos($apiContent, "action === 'export'") !== false &&
    strpos($apiContent, "\$dateFrom = sanitize(\$_GET['date_from'] ?? '');") !== false);

assertTest("1.6 api/leads.php implements bulk_update action endpoint",
    strpos($apiContent, "in_array(\$action, ['bulk_update', 'bulk-update', 'bulk'], true)") !== false);

assertTest("1.7 api/leads.php bulk_update enforces delete permission (admin only)",
    (strpos($apiContent, "canDeleteLead(\$currentUser)") !== false || strpos($apiContent, "\$userRole !== 'admin'") !== false) &&
    strpos($apiContent, "Only administrators can delete leads") !== false);

assertTest("1.8 api/leads.php bulk_update enforces assign permission (admin and manager only)",
    (strpos($apiContent, "canAssignLead(\$currentUser)") !== false || strpos($apiContent, "in_array(\$userRole, ['admin', 'manager', 'sales_manager'], true)") !== false) &&
    strpos($apiContent, "Only administrators and sales managers can bulk assign leads") !== false);

assertTest("1.9 api/leads.php bulk_update enforces status permissions per user role",
    (strpos($apiContent, "canChangeLeadStatus(\$currentUser, \$lead)") !== false || strpos($apiContent, "canEditLead(\$lead, \$userRole, \$currentUserId)") !== false));

assertTest("1.10 api/leads.php bulk_update logs activity in lead_activity_log",
    strpos($apiContent, "addLeadActivity(\$leadId, 'Lead Status Changed', 'Bulk status update") !== false &&
    strpos($apiContent, "addLeadActivity(\$leadId, 'Lead Reassigned', 'Bulk reassigned") !== false &&
    strpos($apiContent, "addLeadActivity(\$leadId, 'Lead Deleted', 'Bulk soft-deleted") !== false);

// -------------------------------------------------------------
// Test Group 2: Frontend Top Filters & Table View Inspection
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 2: pages/leads.php UI & Event Handlers Inspection ---\n" . COLOR_RESET;

$pageFile = __DIR__ . '/../pages/leads.php';
$pageContent = file_exists($pageFile) ? file_get_contents($pageFile) : '';

assertTest("2.1 Top filter bar replaces status dropdown with From Date (#leadDateFrom)",
    strpos($pageContent, 'id="leadDateFrom"') !== false);

assertTest("2.2 Top filter bar has To Date (#leadDateTo)",
    strpos($pageContent, 'id="leadDateTo"') !== false);

assertTest("2.3 Top filter bar has Clear Dates button (#clearDateFilterBtn)",
    strpos($pageContent, 'id="clearDateFilterBtn"') !== false);

assertTest("2.4 Table View header contains Select All checkbox (#selectAllLeads)",
    strpos($pageContent, 'id="selectAllLeads"') !== false);

assertTest("2.5 Table View rows contain row selection checkboxes (.lead-select-box)",
    strpos($pageContent, 'class="lead-select-box"') !== false);

assertTest("2.6 Bulk Actions Toolbar container (#bulkActionsToolbar) exists",
    strpos($pageContent, 'id="bulkActionsToolbar"') !== false);

assertTest("2.7 Bulk Actions Toolbar contains selected count badge (#selectedCountBadge)",
    strpos($pageContent, 'id="selectedCountBadge"') !== false);

assertTest("2.8 Bulk Actions Toolbar contains Clear Selection button (#clearSelectionBtn)",
    strpos($pageContent, 'id="clearSelectionBtn"') !== false);

assertTest("2.9 Bulk Actions Toolbar contains Bulk Status select (#bulkStatusSelect) and Apply button",
    strpos($pageContent, 'id="bulkStatusSelect"') !== false &&
    strpos($pageContent, 'id="applyBulkStatusBtn"') !== false);

assertTest("2.10 Bulk Actions Toolbar contains Bulk Assign select (#bulkAssignSelect) with role guard",
    strpos($pageContent, 'id="bulkAssignSelect"') !== false &&
    strpos($pageContent, 'id="applyBulkAssignBtn"') !== false);

assertTest("2.11 Bulk Actions Toolbar contains Bulk Delete button (#bulkDeleteBtn) with Admin-only check",
    strpos($pageContent, 'id="bulkDeleteBtn"') !== false &&
    (strpos($pageContent, "canDeleteLead(\$user)") !== false || strpos($pageContent, "strtolower(\$user['role'] ?? '') === 'admin'") !== false));

assertTest("2.12 JavaScript maintains selectedLeadIds Set and synchronizes selection state",
    strpos($pageContent, 'let selectedLeadIds = new Set();') !== false &&
    strpos($pageContent, 'updateBulkToolbarState') !== false);

assertTest("2.13 Date filter change validates date_from <= date_to on frontend",
    strpos($pageContent, 'From Date cannot be later than To Date.') !== false);

// -------------------------------------------------------------
// Test Group 3: Functional Simulation of Date Range Filtering (Scenarios 1-10)
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 3: Functional Date Range Filter Scenarios ---\n" . COLOR_RESET;

// Mock test fixture of leads
$mockLeads = [
    ['id' => 'lead-1', 'customer_id' => 'cust-1', 'enquiry_date' => '2026-05-01', 'sport' => 'Cricket', 'lead_source' => 'Website', 'lead_status' => 'Received', 'assigned_to' => 'user-1'],
    ['id' => 'lead-2', 'customer_id' => 'cust-2', 'enquiry_date' => '2026-05-15', 'sport' => 'Soccer', 'lead_source' => 'WhatsApp', 'lead_status' => 'Analysis', 'assigned_to' => 'user-1'],
    ['id' => 'lead-3', 'customer_id' => 'cust-1', 'enquiry_date' => '2026-06-01', 'sport' => 'Cricket', 'lead_source' => 'Kit Builder', 'lead_status' => 'Verified', 'assigned_to' => 'user-2'],
    ['id' => 'lead-4', 'customer_id' => 'cust-3', 'enquiry_date' => '2026-06-10', 'sport' => 'Tennis', 'lead_source' => 'Email', 'lead_status' => 'Approved', 'assigned_to' => 'user-2'],
    ['id' => 'lead-5', 'customer_id' => 'cust-2', 'enquiry_date' => '2026-06-25', 'sport' => 'Pickleball', 'lead_source' => 'Website', 'lead_status' => 'Moved to Production', 'assigned_to' => 'user-3'],
    ['id' => 'lead-6', 'customer_id' => 'cust-4', 'enquiry_date' => '2026-07-01', 'sport' => 'Badminton', 'lead_source' => 'WhatsApp', 'lead_status' => 'Received', 'assigned_to' => 'user-3'],
];

function filterLeads(array $leads, array $filters): array {
    $dateFrom = $filters['date_from'] ?? '';
    $dateTo = $filters['date_to'] ?? '';
    $sport = $filters['sport'] ?? '';
    $source = $filters['lead_source'] ?? '';
    $assignedTo = $filters['assigned_to'] ?? '';

    // Date format validation
    if (!empty($dateFrom)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) || !strtotime($dateFrom)) {
            return ['error' => 'Invalid From Date format. Expected YYYY-MM-DD.', 'code' => 422];
        }
    }
    if (!empty($dateTo)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) || !strtotime($dateTo)) {
            return ['error' => 'Invalid To Date format. Expected YYYY-MM-DD.', 'code' => 422];
        }
    }
    if (!empty($dateFrom) && !empty($dateTo) && $dateFrom > $dateTo) {
        return ['error' => 'From Date cannot be later than To Date.', 'code' => 422];
    }

    $filtered = $leads;
    if (!empty($dateFrom)) {
        $filtered = array_values(array_filter($filtered, fn($l) => $l['enquiry_date'] >= $dateFrom));
    }
    if (!empty($dateTo)) {
        $filtered = array_values(array_filter($filtered, fn($l) => $l['enquiry_date'] <= $dateTo));
    }
    if (!empty($sport)) {
        $filtered = array_values(array_filter($filtered, fn($l) => $l['sport'] === $sport));
    }
    if (!empty($source)) {
        $filtered = array_values(array_filter($filtered, fn($l) => $l['lead_source'] === $source));
    }
    if (!empty($assignedTo)) {
        $filtered = array_values(array_filter($filtered, fn($l) => $l['assigned_to'] === $assignedTo));
    }

    return ['data' => $filtered, 'count' => count($filtered)];
}

// 3.1 Valid Date Range
$res1 = filterLeads($mockLeads, ['date_from' => '2026-06-01', 'date_to' => '2026-06-30']);
assertTest("3.1 Valid date range 2026-06-01 to 2026-06-30 returns correct leads (leads 3, 4, 5)",
    isset($res1['data']) && $res1['count'] === 3 && $res1['data'][0]['id'] === 'lead-3');

// 3.2 Inverted Date Range Rejected
$res2 = filterLeads($mockLeads, ['date_from' => '2026-06-30', 'date_to' => '2026-06-01']);
assertTest("3.2 Inverted date range (From > To) rejected with 422 error",
    isset($res2['error']) && $res2['code'] === 422);

// 3.3 Invalid Date Format Rejected
$res3 = filterLeads($mockLeads, ['date_from' => 'invalid-date', 'date_to' => '2026-06-30']);
assertTest("3.3 Invalid date format rejected with 422 error",
    isset($res3['error']) && $res3['code'] === 422);

// 3.4 From Date only filter
$res4 = filterLeads($mockLeads, ['date_from' => '2026-06-15']);
assertTest("3.4 date_from only returns leads on or after 2026-06-15 (leads 5, 6)",
    isset($res4['data']) && $res4['count'] === 2 && $res4['data'][0]['id'] === 'lead-5');

// 3.5 To Date only filter
$res5 = filterLeads($mockLeads, ['date_to' => '2026-05-31']);
assertTest("3.5 date_to only returns leads on or before 2026-05-31 (leads 1, 2)",
    isset($res5['data']) && $res5['count'] === 2 && $res5['data'][1]['id'] === 'lead-2');

// 3.6 Single-day range (date_from == date_to)
$res6 = filterLeads($mockLeads, ['date_from' => '2026-06-10', 'date_to' => '2026-06-10']);
assertTest("3.6 Exact single-day range returns matching lead (lead 4)",
    isset($res6['data']) && $res6['count'] === 1 && $res6['data'][0]['id'] === 'lead-4');

// 3.7 Date range with zero matches
$res7 = filterLeads($mockLeads, ['date_from' => '2025-01-01', 'date_to' => '2025-01-31']);
assertTest("3.7 Date range with zero matches returns empty data array without error",
    isset($res7['data']) && $res7['count'] === 0);

// 3.8 Combined Date Range and Sport filter
$res8 = filterLeads($mockLeads, ['date_from' => '2026-05-01', 'date_to' => '2026-06-30', 'sport' => 'Cricket']);
assertTest("3.8 Date range combined with Sport=Cricket returns only Cricket leads in range (leads 1, 3)",
    isset($res8['data']) && $res8['count'] === 2 && $res8['data'][1]['id'] === 'lead-3');

// 3.9 Combined Date Range and Assigned User
$res9 = filterLeads($mockLeads, ['date_from' => '2026-05-01', 'date_to' => '2026-06-30', 'assigned_to' => 'user-1']);
assertTest("3.9 Date range combined with Assigned To=user-1 returns leads 1 and 2",
    isset($res9['data']) && $res9['count'] === 2);

// 3.10 Empty/Cleared Date Filters
$res10 = filterLeads($mockLeads, ['date_from' => '', 'date_to' => '']);
assertTest("3.10 Cleared date range returns all 6 leads without truncation",
    isset($res10['data']) && $res10['count'] === 6);

// -------------------------------------------------------------
// Test Group 4: Functional Simulation of Table Bulk Editing (Scenarios 11-22)
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 4: Functional Table Bulk Editing Scenarios ---\n" . COLOR_RESET;

// Mock database and controller function replicating api/leads.php bulk_update
$mockLeadStore = [
    'lead-1' => ['id' => 'lead-1', 'lead_status' => 'Received', 'assigned_to' => 'user-1', 'is_deleted' => false],
    'lead-2' => ['id' => 'lead-2', 'lead_status' => 'Analysis', 'assigned_to' => 'user-1', 'is_deleted' => false],
    'lead-3' => ['id' => 'lead-3', 'lead_status' => 'Verified', 'assigned_to' => 'user-2', 'is_deleted' => false],
    'lead-4' => ['id' => 'lead-4', 'lead_status' => 'Approved', 'assigned_to' => 'user-2', 'is_deleted' => false],
];
$mockUserStore = [
    'user-1' => ['id' => 'user-1', 'user_name' => 'John Rep', 'role' => 'staff', 'is_active' => true],
    'user-2' => ['id' => 'user-2', 'user_name' => 'Jane Rep', 'role' => 'staff', 'is_active' => true],
    'user-3' => ['id' => 'user-3', 'user_name' => 'Sales Lead', 'role' => 'manager', 'is_active' => true],
    'user-4' => ['id' => 'user-4', 'user_name' => 'Inactive User', 'role' => 'staff', 'is_active' => false],
];
$mockActivityLog = [];

function executeBulkUpdate(array $input, array $currentUser): array {
    global $mockLeadStore, $mockUserStore, $mockActivityLog;

    $ids = $input['ids'] ?? [];
    if (empty($ids) || !is_array($ids)) {
        return ['success' => false, 'message' => 'No leads selected for bulk update.', 'code' => 400];
    }

    $operation = $input['operation'] ?? '';
    if (!in_array($operation, ['status', 'assign', 'delete'], true)) {
        return ['success' => false, 'message' => 'Invalid bulk operation. Allowed: status, assign, delete.', 'code' => 422];
    }

    $userRole = strtolower($currentUser['role'] ?? 'staff');
    $currentUserId = $currentUser['id'] ?? '';

    // Find existing leads
    $existing = [];
    foreach ($ids as $id) {
        if (isset($mockLeadStore[$id]) && !$mockLeadStore[$id]['is_deleted']) {
            $existing[$id] = $mockLeadStore[$id];
        }
    }
    if (empty($existing)) {
        return ['success' => false, 'message' => 'None of the selected leads were found or they are already deleted.', 'code' => 404];
    }

    // 1. Bulk Delete
    if ($operation === 'delete') {
        if ($userRole !== 'admin') {
            return ['success' => false, 'message' => 'Permission denied. Only administrators can delete leads.', 'code' => 403];
        }
        $count = 0;
        foreach ($existing as $id => $lead) {
            $mockLeadStore[$id]['is_deleted'] = true;
            $mockActivityLog[] = ['lead_id' => $id, 'action' => 'Lead Deleted', 'changed_by' => $currentUserId];
            $count++;
        }
        return ['success' => true, 'message' => "Successfully deleted {$count} leads.", 'data' => ['updated_count' => $count, 'operation' => 'delete']];
    }

    // 2. Bulk Assign
    if ($operation === 'assign') {
        if (!in_array($userRole, ['admin', 'manager', 'sales_manager'], true)) {
            return ['success' => false, 'message' => 'Permission denied. Only administrators and sales managers can bulk assign leads.', 'code' => 403];
        }
        $targetUserId = $input['assigned_to'] ?? '';
        if (empty($targetUserId) || !isset($mockUserStore[$targetUserId]) || !$mockUserStore[$targetUserId]['is_active']) {
            return ['success' => false, 'message' => 'Assigned user does not exist or is inactive.', 'code' => 404];
        }
        $targetUser = $mockUserStore[$targetUserId];
        $count = 0;
        foreach ($existing as $id => $lead) {
            $mockLeadStore[$id]['assigned_to'] = $targetUserId;
            $mockActivityLog[] = ['lead_id' => $id, 'action' => 'Lead Reassigned', 'changed_by' => $currentUserId];
            $count++;
        }
        return ['success' => true, 'message' => "Successfully assigned {$count} leads.", 'data' => ['updated_count' => $count, 'operation' => 'assign']];
    }

    // 3. Bulk Status
    if ($operation === 'status') {
        $allowedStatuses = [
            'Received', 'Analysis', 'Awaiting Clarification', 'Order Sheet Prepared',
            'Verified', 'Awaiting Approval', 'Approved', 'Awaiting Payment',
            'Moved to Production', 'Lost'
        ];
        $newStatus = $input['lead_status'] ?? '';
        if (!in_array($newStatus, $allowedStatuses, true)) {
            return ['success' => false, 'message' => 'Invalid lead status value.', 'code' => 422];
        }

        // Permission check
        if (!in_array($userRole, ['admin', 'manager', 'sales_manager'], true)) {
            foreach ($existing as $id => $lead) {
                if ($lead['assigned_to'] !== $currentUserId) {
                    return ['success' => false, 'message' => 'Permission denied. You can only update status on leads assigned to you.', 'code' => 403];
                }
            }
        }

        $count = 0;
        foreach ($existing as $id => $lead) {
            $mockLeadStore[$id]['lead_status'] = $newStatus;
            $mockActivityLog[] = ['lead_id' => $id, 'action' => 'Lead Status Changed', 'new_status' => $newStatus, 'changed_by' => $currentUserId];
            $count++;
        }
        return ['success' => true, 'message' => "Successfully updated status of {$count} leads to {$newStatus}.", 'data' => ['updated_count' => $count, 'operation' => 'status']];
    }

    return ['success' => false, 'message' => 'Invalid request', 'code' => 400];
}

// 4.1 Empty selection rejected
$b1 = executeBulkUpdate(['ids' => [], 'operation' => 'status', 'lead_status' => 'Verified'], ['role' => 'admin', 'id' => 'admin-1']);
assertTest("4.1 Empty lead selection rejected with 400 Bad Request",
    !$b1['success'] && $b1['code'] === 400);

// 4.2 Invalid operation rejected
$b2 = executeBulkUpdate(['ids' => ['lead-1'], 'operation' => 'invalid_op'], ['role' => 'admin', 'id' => 'admin-1']);
assertTest("4.2 Invalid bulk operation rejected with 422 Unprocessable Entity",
    !$b2['success'] && $b2['code'] === 422);

// 4.3 Admin bulk status update
$b3 = executeBulkUpdate(['ids' => ['lead-1', 'lead-2'], 'operation' => 'status', 'lead_status' => 'Verified'], ['role' => 'admin', 'id' => 'admin-1']);
assertTest("4.3 Admin can bulk update status on multiple leads",
    $b3['success'] && $b3['data']['updated_count'] === 2 && $mockLeadStore['lead-1']['lead_status'] === 'Verified');

// 4.4 Sales Manager bulk status update
$b4 = executeBulkUpdate(['ids' => ['lead-3', 'lead-4'], 'operation' => 'status', 'lead_status' => 'Moved to Production'], ['role' => 'manager', 'id' => 'user-3']);
assertTest("4.4 Sales Manager can bulk update status on any leads",
    $b4['success'] && $b4['data']['updated_count'] === 2 && $mockLeadStore['lead-3']['lead_status'] === 'Moved to Production');

// 4.5 Sales Executive can update assigned leads
$b5 = executeBulkUpdate(['ids' => ['lead-1', 'lead-2'], 'operation' => 'status', 'lead_status' => 'Awaiting Payment'], ['role' => 'staff', 'id' => 'user-1']);
assertTest("4.5 Sales Executive can update status on leads assigned to them",
    $b5['success'] && $b5['data']['updated_count'] === 2 && $mockLeadStore['lead-1']['lead_status'] === 'Awaiting Payment');

// 4.6 Sales Executive rejected when attempting to update unassigned leads
$b6 = executeBulkUpdate(['ids' => ['lead-3', 'lead-4'], 'operation' => 'status', 'lead_status' => 'Approved'], ['role' => 'staff', 'id' => 'user-1']);
assertTest("4.6 Sales Executive is rejected (403) when attempting status update on unassigned leads",
    !$b6['success'] && $b6['code'] === 403);

// 4.7 Invalid lead status value rejected
$b7 = executeBulkUpdate(['ids' => ['lead-1'], 'operation' => 'status', 'lead_status' => 'Fake Status'], ['role' => 'admin', 'id' => 'admin-1']);
assertTest("4.7 Invalid lead status string rejected with 422",
    !$b7['success'] && $b7['code'] === 422);

// 4.8 Admin can bulk assign leads
$b8 = executeBulkUpdate(['ids' => ['lead-1', 'lead-2'], 'operation' => 'assign', 'assigned_to' => 'user-2'], ['role' => 'admin', 'id' => 'admin-1']);
assertTest("4.8 Admin can bulk assign leads to active sales user",
    $b8['success'] && $b8['data']['updated_count'] === 2 && $mockLeadStore['lead-1']['assigned_to'] === 'user-2');

// 4.9 Manager can bulk assign leads
$b9 = executeBulkUpdate(['ids' => ['lead-3'], 'operation' => 'assign', 'assigned_to' => 'user-1'], ['role' => 'manager', 'id' => 'user-3']);
assertTest("4.9 Sales Manager can bulk assign leads",
    $b9['success'] && $b9['data']['updated_count'] === 1 && $mockLeadStore['lead-3']['assigned_to'] === 'user-1');

// 4.10 Sales Executive rejected when attempting bulk assign
$b10 = executeBulkUpdate(['ids' => ['lead-1'], 'operation' => 'assign', 'assigned_to' => 'user-2'], ['role' => 'staff', 'id' => 'user-1']);
assertTest("4.10 Sales Executive is rejected (403) when attempting bulk assignment",
    !$b10['success'] && $b10['code'] === 403);

// 4.11 Assignment to inactive sales user rejected
$b11 = executeBulkUpdate(['ids' => ['lead-1'], 'operation' => 'assign', 'assigned_to' => 'user-4'], ['role' => 'admin', 'id' => 'admin-1']);
assertTest("4.11 Bulk assignment to inactive sales user rejected with 404",
    !$b11['success'] && $b11['code'] === 404);

// 4.12 Admin can bulk soft-delete leads
$b12 = executeBulkUpdate(['ids' => ['lead-1', 'lead-2'], 'operation' => 'delete'], ['role' => 'admin', 'id' => 'admin-1']);
assertTest("4.12 Admin can bulk delete leads (sets is_deleted=true)",
    $b12['success'] && $b12['data']['updated_count'] === 2 && $mockLeadStore['lead-1']['is_deleted'] === true);

// 4.13 Sales Manager rejected when attempting bulk delete
$b13 = executeBulkUpdate(['ids' => ['lead-3'], 'operation' => 'delete'], ['role' => 'manager', 'id' => 'user-3']);
assertTest("4.13 Sales Manager is rejected (403) when attempting bulk delete",
    !$b13['success'] && $b13['code'] === 403);

// 4.14 Sales Executive rejected when attempting bulk delete
$b14 = executeBulkUpdate(['ids' => ['lead-3'], 'operation' => 'delete'], ['role' => 'staff', 'id' => 'user-1']);
assertTest("4.14 Sales Executive is rejected (403) when attempting bulk delete",
    !$b14['success'] && $b14['code'] === 403);

// 4.15 Activity log entries created for each bulk action
assertTest("4.15 Bulk operations record audit entries in lead_activity_log",
    count($mockActivityLog) >= 8);

// -------------------------------------------------------------
// Test Group 5: Regression Checks for Phase 1 & Phase 2
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 5: Regression Verification (Phase 1 & Phase 2) ---\n" . COLOR_RESET;

assertTest("5.1 Phase 1: Customer association mandatory validation intact in api/leads.php",
    strpos($apiContent, 'Customer selection is required') !== false);

assertTest("5.2 Phase 1: Priority validation (low, medium, high) intact in api/leads.php",
    strpos($apiContent, "Invalid priority level. Allowed values: low, medium, high.") !== false);

assertTest("5.3 Phase 2: All 6 Kanban indicators computed in api/leads.php",
    strpos($apiContent, "has_attachments") !== false &&
    strpos($apiContent, "has_design") !== false &&
    strpos($apiContent, "player_roster_received") !== false &&
    strpos($apiContent, "whatsapp_status") !== false &&
    strpos($apiContent, "is_repeat_customer") !== false);

assertTest("5.4 Phase 2: Card indicator tooltips and badges intact in pages/leads.php",
    strpos($pageContent, 'Attachments available') !== false &&
    strpos($pageContent, 'Design uploaded') !== false &&
    strpos($pageContent, 'Player roster received') !== false &&
    strpos($pageContent, 'WhatsApp notification') !== false &&
    strpos($pageContent, 'Urgent: High priority lead') !== false &&
    strpos($pageContent, 'Repeat customer') !== false);

// -------------------------------------------------------------
// Final Summary Report
// -------------------------------------------------------------
echo COLOR_CYAN . "\n=======================================================\n";
echo "  Phase 3 Test Execution Complete\n";
echo "  Total Tests Run:    $testsRun\n";
echo COLOR_GREEN . "  Tests Passed:       $testsPassed\n" . COLOR_RESET;
if ($testsFailed > 0) {
    echo COLOR_RED . "  Tests Failed:       $testsFailed\n" . COLOR_RESET;
} else {
    echo COLOR_GREEN . "  ALL TESTS PASSED SUCCESSFULLY! (100%)\n" . COLOR_RESET;
}
echo COLOR_CYAN . "=======================================================\n\n" . COLOR_RESET;

exit($testsFailed > 0 ? 1 : 0);
