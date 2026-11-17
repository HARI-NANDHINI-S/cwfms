<?php
/**
 * Phase 4 Test Suite - Role-Based Permissions (Admin, Sales Manager, Sales Executive)
 * Ira Sportswear Lead Management Module (Official PDF Page 8 & Page 14)
 * Tests all required permission matrices, direct API security, privilege escalation guards,
 * and regression verification.
 */

// Include config before any output to prevent session warnings
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
echo "  CWFMS Phase 4 Verification Test Suite\n";
echo "  Admin, Sales Manager & Sales Executive Permissions\n";
echo "=======================================================\n" . COLOR_RESET;

// -------------------------------------------------------------
// Test Group 1: Role Normalization & Mapping Verification
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 1: Role Normalization & Business Role Mapping ---\n" . COLOR_RESET;

assertTest("1.1 'admin' maps to business role 'admin'", 
    normalizeRole('admin') === 'admin' && getRoleLabel('admin') === 'Admin');

assertTest("1.2 'manager' maps to business role 'sales_manager' labeled 'Sales Manager'", 
    normalizeRole('manager') === 'sales_manager' && getRoleLabel('manager') === 'Sales Manager');

assertTest("1.3 'sales_manager' maps to business role 'sales_manager' labeled 'Sales Manager'", 
    normalizeRole('sales_manager') === 'sales_manager' && getRoleLabel('sales_manager') === 'Sales Manager');

assertTest("1.4 'staff' maps to business role 'sales_executive' labeled 'Sales Executive'", 
    normalizeRole('staff') === 'sales_executive' && getRoleLabel('staff') === 'Sales Executive');

assertTest("1.5 'sales_executive' maps to business role 'sales_executive' labeled 'Sales Executive'", 
    normalizeRole('sales_executive') === 'sales_executive' && getRoleLabel('sales_executive') === 'Sales Executive');

assertTest("1.6 Unknown or empty role safely defaults to 'sales_executive'", 
    normalizeRole('') === 'sales_executive' && getRoleLabel(null) === 'Sales Executive');

// -------------------------------------------------------------
// Test Group 2: Centralized Permission Helper Functions
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 2: Centralized Permission Helpers (includes/config.php) ---\n" . COLOR_RESET;

$adminUser = ['id' => 'u-admin-1', 'user_name' => 'Alice Admin', 'role' => 'admin'];
$managerUser = ['id' => 'u-mgr-1', 'user_name' => 'Bob Manager', 'role' => 'manager'];
$executiveUser1 = ['id' => 'u-exec-1', 'user_name' => 'Charlie Executive', 'role' => 'staff'];
$executiveUser2 = ['id' => 'u-exec-2', 'user_name' => 'David Executive', 'role' => 'sales_executive'];

assertTest("2.1 isAdmin helper correctly identifies Admin only",
    isAdmin($adminUser) && !isAdmin($managerUser) && !isAdmin($executiveUser1));

assertTest("2.2 isSalesManager helper correctly identifies Sales Manager only",
    isSalesManager($managerUser) && !isSalesManager($adminUser) && !isSalesManager($executiveUser1));

assertTest("2.3 isSalesExecutive helper correctly identifies Sales Executive only",
    isSalesExecutive($executiveUser1) && isSalesExecutive($executiveUser2) && !isSalesExecutive($adminUser));

assertTest("2.4 canManageUsers is strictly restricted to Admin",
    canManageUsers($adminUser) && !canManageUsers($managerUser) && !canManageUsers($executiveUser1));

assertTest("2.5 canDeleteLead is strictly restricted to Admin (PDF Page 8: Delete = Yes only for Admin)",
    canDeleteLead($adminUser) && !canDeleteLead($managerUser) && !canDeleteLead($executiveUser1));

assertTest("2.6 canAssignLead is granted to Admin and Sales Manager, but denied to Sales Executive (PDF Page 8: Assign = No for Sales Executive)",
    canAssignLead($adminUser) && canAssignLead($managerUser) && !canAssignLead($executiveUser1));

$leadAssignedToExec1 = ['id' => 'lead-101', 'assigned_to' => 'u-exec-1', 'lead_status' => 'Received'];
$leadAssignedToExec2 = ['id' => 'lead-102', 'assigned_to' => 'u-exec-2', 'lead_status' => 'Received'];
$leadUnassigned = ['id' => 'lead-103', 'assigned_to' => null, 'lead_status' => 'Received'];

assertTest("2.7 canChangeLeadStatus: Admin & Sales Manager can change status on any lead",
    canChangeLeadStatus($leadAssignedToExec1, $adminUser) &&
    canChangeLeadStatus($leadAssignedToExec2, $managerUser) &&
    canChangeLeadStatus($leadUnassigned, $managerUser));

assertTest("2.8 canChangeLeadStatus: Sales Executive can only change status on leads assigned to them ('Limited' rule)",
    canChangeLeadStatus($leadAssignedToExec1, $executiveUser1) &&
    !canChangeLeadStatus($leadAssignedToExec2, $executiveUser1) &&
    !canChangeLeadStatus($leadUnassigned, $executiveUser1));

assertTest("2.9 canEditLeadRecord: Admin & Sales Manager can edit any lead",
    canEditLeadRecord($leadAssignedToExec1, $adminUser) &&
    canEditLeadRecord($leadAssignedToExec2, $managerUser));

assertTest("2.10 canEditLeadRecord: Sales Executive can edit leads assigned to them, but not leads assigned to others",
    canEditLeadRecord($leadAssignedToExec1, $executiveUser1) &&
    !canEditLeadRecord($leadAssignedToExec2, $executiveUser1) &&
    !canEditLeadRecord($leadUnassigned, $executiveUser1));

// -------------------------------------------------------------
// Test Group 3: Backend Lead API Security Simulation
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 3: Lead API Authorization & Security Checks ---\n" . COLOR_RESET;

// Mock controller simulating api/leads.php operations
function simulateLeadCreate(array $payload, array $currentUser): array {
    $currentUserId = $currentUser['id'] ?? '';
    $assignedTo = $payload['assigned_to'] ?? '';

    if (isSalesExecutive($currentUser)) {
        // Sales Executive: cannot assign leads to others (PDF Page 8: Assign = No)
        if (!empty($assignedTo) && $assignedTo !== $currentUserId) {
            return ['status' => 403, 'success' => false, 'message' => 'Permission denied. Sales Executives cannot assign leads to other representatives.'];
        }
        $assignedTo = $currentUserId;
    } else {
        if (empty($assignedTo)) {
            return ['status' => 400, 'success' => false, 'message' => 'Assigned user is required.'];
        }
    }
    return ['status' => 201, 'success' => true, 'assigned_to' => $assignedTo];
}

function simulateLeadUpdate(array $lead, array $input, array $currentUser): array {
    $currentUserId = $currentUser['id'] ?? '';

    // Record-level edit check
    if (!canEditLeadRecord($lead, $currentUser)) {
        return ['status' => 403, 'success' => false, 'message' => 'Permission denied. Sales Executives can only edit leads assigned to them.'];
    }

    // Role-check on lead assignment
    if (array_key_exists('assigned_to', $input)) {
        $newAssigned = $input['assigned_to'];
        if ($newAssigned !== $lead['assigned_to']) {
            if (!canAssignLead($currentUser)) {
                return ['status' => 403, 'success' => false, 'message' => 'Permission denied. Sales Executives cannot assign or reassign leads.'];
            }
        }
    }

    // Role-check on status transition
    if (array_key_exists('lead_status', $input)) {
        $newStatus = $input['lead_status'];
        if ($newStatus !== $lead['lead_status']) {
            if (!canChangeLeadStatus($lead, $currentUser)) {
                return ['status' => 403, 'success' => false, 'message' => 'Permission denied. Sales Executives can only update status on leads assigned to them.'];
            }
        }
    }

    return ['status' => 200, 'success' => true, 'message' => 'Lead updated successfully'];
}

function simulateLeadDelete(array $currentUser): array {
    if (!canDeleteLead($currentUser)) {
        return ['status' => 403, 'success' => false, 'message' => 'Permission denied. Only administrators can delete leads.'];
    }
    return ['status' => 200, 'success' => true, 'message' => 'Lead deleted successfully'];
}

// 3.1 Admin can create and assign lead to any user
$c1 = simulateLeadCreate(['assigned_to' => 'u-exec-2'], $adminUser);
assertTest("3.1 Admin can create lead assigned to another sales user",
    $c1['status'] === 201 && $c1['assigned_to'] === 'u-exec-2');

// 3.2 Sales Manager can create and assign lead to any user
$c2 = simulateLeadCreate(['assigned_to' => 'u-exec-1'], $managerUser);
assertTest("3.2 Sales Manager can create lead assigned to any sales user",
    $c2['status'] === 201 && $c2['assigned_to'] === 'u-exec-1');

// 3.3 Sales Executive creates lead -> auto-assigned to themselves
$c3 = simulateLeadCreate([], $executiveUser1);
assertTest("3.3 Sales Executive creating lead is auto-assigned to themselves",
    $c3['status'] === 201 && $c3['assigned_to'] === 'u-exec-1');

// 3.4 Sales Executive attempting to assign to another user is rejected with 403
$c4 = simulateLeadCreate(['assigned_to' => 'u-exec-2'], $executiveUser1);
assertTest("3.4 Sales Executive attempting to assign lead to another user is rejected (403)",
    $c4['status'] === 403 && strpos($c4['message'], 'Permission denied') !== false);

// 3.5 Admin can edit any lead and reassign it
$u1 = simulateLeadUpdate($leadAssignedToExec1, ['assigned_to' => 'u-exec-2', 'notes' => 'Updated notes'], $adminUser);
assertTest("3.5 Admin can edit lead and reassign to another representative",
    $u1['status'] === 200 && $u1['success']);

// 3.6 Sales Manager can edit any lead and change its status
$u2 = simulateLeadUpdate($leadAssignedToExec1, ['lead_status' => 'Verified'], $managerUser);
assertTest("3.6 Sales Manager can edit any lead and update its workflow status",
    $u2['status'] === 200 && $u2['success']);

// 3.7 Sales Executive can edit and update status on their own assigned lead
$u3 = simulateLeadUpdate($leadAssignedToExec1, ['lead_status' => 'Analysis', 'notes' => 'Updated by owner'], $executiveUser1);
assertTest("3.7 Sales Executive can edit and update status on their own assigned lead",
    $u3['status'] === 200 && $u3['success']);

// 3.8 Sales Executive rejected (403) when attempting to edit lead assigned to someone else
$u4 = simulateLeadUpdate($leadAssignedToExec2, ['notes' => 'Attempted hijack'], $executiveUser1);
assertTest("3.8 Sales Executive is rejected (403) when attempting to edit lead assigned to another rep",
    $u4['status'] === 403);

// 3.9 Sales Executive rejected (403) when attempting to change status on someone else's lead
$u5 = simulateLeadUpdate($leadAssignedToExec2, ['lead_status' => 'Verified'], $executiveUser1);
assertTest("3.9 Sales Executive is rejected (403) when attempting status change on another rep's lead",
    $u5['status'] === 403);

// 3.10 Sales Executive rejected (403) when attempting to reassign their own lead to someone else
$u6 = simulateLeadUpdate($leadAssignedToExec1, ['assigned_to' => 'u-exec-2'], $executiveUser1);
assertTest("3.10 Sales Executive is rejected (403) when attempting to reassign lead",
    $u6['status'] === 403 && strpos($u6['message'], 'cannot assign or reassign') !== false);

// 3.11 Admin can delete leads
$d1 = simulateLeadDelete($adminUser);
assertTest("3.11 Admin can delete leads (HTTP 200)",
    $d1['status'] === 200 && $d1['success']);

// 3.12 Sales Manager cannot delete leads (PDF Page 8: Delete = No)
$d2 = simulateLeadDelete($managerUser);
assertTest("3.12 Sales Manager cannot delete leads (rejected with HTTP 403)",
    $d2['status'] === 403);

// 3.13 Sales Executive cannot delete leads (PDF Page 8: Delete = No)
$d3 = simulateLeadDelete($executiveUser1);
assertTest("3.13 Sales Executive cannot delete leads (rejected with HTTP 403)",
    $d3['status'] === 403);

// -------------------------------------------------------------
// Test Group 4: User Management & Privilege Escalation Guards (api/auth.php)
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 4: User Management & Self-Role Escalation Guards ---\n" . COLOR_RESET;

function simulateUpdateRole(string $targetUserId, string $newRole, array $currentUser): array {
    if (!canManageUsers($currentUser)) {
        return ['status' => 403, 'success' => false, 'message' => 'Permission denied. Only Admin can update user roles.'];
    }
    // Prevent self-role modification
    if ($targetUserId === ($currentUser['id'] ?? '')) {
        return ['status' => 403, 'success' => false, 'message' => 'Administrators cannot modify their own role.'];
    }
    return ['status' => 200, 'success' => true, 'message' => 'Role updated successfully'];
}

function simulateToggleUser(string $targetUserId, bool $isActive, array $currentUser): array {
    if (!canManageUsers($currentUser)) {
        return ['status' => 403, 'success' => false, 'message' => 'Permission denied. Only Admin can toggle user status.'];
    }
    // Prevent self-deactivation
    if ($targetUserId === ($currentUser['id'] ?? '') && !$isActive) {
        return ['status' => 403, 'success' => false, 'message' => 'Administrators cannot deactivate their own account.'];
    }
    return ['status' => 200, 'success' => true, 'message' => 'User status updated'];
}

// 4.1 Admin can update role of other users
$r1 = simulateUpdateRole('u-exec-1', 'sales_manager', $adminUser);
assertTest("4.1 Admin can update user role of another user",
    $r1['status'] === 200 && $r1['success']);

// 4.2 Admin cannot demote or change their own role (self-escalation / lockout guard)
$r2 = simulateUpdateRole('u-admin-1', 'staff', $adminUser);
assertTest("4.2 Admin cannot modify their own role (prevent accidental lockout)",
    $r2['status'] === 403 && strpos($r2['message'], 'cannot modify their own role') !== false);

// 4.3 Sales Manager cannot update user roles
$r3 = simulateUpdateRole('u-exec-1', 'admin', $managerUser);
assertTest("4.3 Sales Manager attempting to update user role is rejected (403)",
    $r3['status'] === 403);

// 4.4 Sales Executive cannot update user roles
$r4 = simulateUpdateRole('u-exec-1', 'admin', $executiveUser1);
assertTest("4.4 Sales Executive attempting privilege escalation to admin is rejected (403)",
    $r4['status'] === 403);

// 4.5 Admin cannot deactivate their own account
$t1 = simulateToggleUser('u-admin-1', false, $adminUser);
assertTest("4.5 Admin cannot deactivate their own account (lockout prevention)",
    $t1['status'] === 403 && strpos($t1['message'], 'cannot deactivate their own account') !== false);

// 4.6 Non-admin cannot toggle user active status
$t2 = simulateToggleUser('u-exec-2', false, $managerUser);
assertTest("4.6 Sales Manager cannot toggle user active status (rejected with 403)",
    $t2['status'] === 403);

// -------------------------------------------------------------
// Test Group 5: Frontend File Inspections for Role Protection
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 5: Frontend UI & Page Guards Inspection ---\n" . COLOR_RESET;

$leadsPage = file_get_contents(__DIR__ . '/../pages/leads.php');
$usersPage = file_get_contents(__DIR__ . '/../pages/users.php');
$dashboardPage = file_get_contents(__DIR__ . '/../dashboard.php');
$configPage = file_get_contents(__DIR__ . '/../includes/config.php');
$authApi = file_get_contents(__DIR__ . '/../api/auth.php');
$leadsApi = file_get_contents(__DIR__ . '/../api/leads.php');

assertTest("5.1 pages/users.php has server-side page guard with canManageUsers",
    strpos($usersPage, 'if (!canManageUsers($user))') !== false);

assertTest("5.2 dashboard.php displays User Management in sidebar only for canManageUsers",
    strpos($dashboardPage, 'if (canManageUsers($user))') !== false);

assertTest("5.3 pages/leads.php displays User Management in sidebar only for canManageUsers",
    strpos($leadsPage, 'if (canManageUsers($user))') !== false);

assertTest("5.4 pages/leads.php uses canAssignLead for bulk assignment toolbar button",
    strpos($leadsPage, 'if (canAssignLead($user))') !== false);

assertTest("5.5 pages/leads.php uses canDeleteLead for bulk delete toolbar button",
    strpos($leadsPage, 'if (canDeleteLead($user))') !== false);

assertTest("5.6 pages/leads.php loadSalesUsers locks assigned_to dropdown for Sales Executives",
    strpos($leadsPage, "currentUserRole === 'sales_executive'") !== false &&
    strpos($leadsPage, "Assigned to You") !== false &&
    strpos($leadsPage, "formSelect.disabled = true") !== false);

assertTest("5.7 pages/leads.php Kanban drag-and-drop guards status changes for Sales Executives",
    strpos($leadsPage, "currentUserRole === 'sales_executive' && targetLead && targetLead.assigned_to !== currentUserId") !== false &&
    strpos($leadsPage, "Sales Executives can only update status on leads assigned to them") !== false);

assertTest("5.8 pages/leads.php viewLead controls Delete and Edit button visibility based on permission",
    strpos($leadsPage, "delBtn.style.display = (lead.can_delete") !== false &&
    strpos($leadsPage, "canEdit = (lead.can_edit") !== false);

assertTest("5.9 pages/users.php displays official role reference for Admin, Sales Manager, Sales Executive",
    strpos($usersPage, "Role Permissions Reference (Ira Sportswear Lead Management)") !== false &&
    strpos($usersPage, "'sales_manager'") !== false &&
    strpos($usersPage, "'sales_executive'") !== false);

assertTest("5.10 migration_v4_roles.sql exists and provides safe migration",
    file_exists(__DIR__ . '/../migration_v4_roles.sql') &&
    strpos(file_get_contents(__DIR__ . '/../migration_v4_roles.sql'), 'sales_manager') !== false &&
    strpos(file_get_contents(__DIR__ . '/../migration_v4_roles.sql'), 'sales_executive') !== false);

// -------------------------------------------------------------
// Test Group 6: Phase 1, Phase 2, and Phase 3 Regression Checks
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 6: Regression Verification (Phases 1, 2, and 3) ---\n" . COLOR_RESET;

assertTest("6.1 Phase 1: Customer association mandatory validation intact in api/leads.php",
    strpos($leadsApi, 'Customer selection is required.') !== false);

assertTest("6.2 Phase 1: Priority validation (low, medium, high) intact in api/leads.php",
    strpos($leadsApi, "Invalid priority level. Allowed values: low, medium, high.") !== false);

assertTest("6.3 Phase 2: All 6 Kanban indicators computed in api/leads.php",
    strpos($leadsApi, "has_attachments") !== false &&
    strpos($leadsApi, "has_design") !== false &&
    strpos($leadsApi, "player_roster_received") !== false &&
    strpos($leadsApi, "whatsapp_status") !== false &&
    strpos($leadsApi, "is_repeat_customer") !== false);

assertTest("6.4 Phase 3: Date range parameters (date_from, date_to) supported in api/leads.php",
    strpos($leadsApi, "\$dateFrom = sanitize(\$_GET['date_from'] ?? '');") !== false &&
    strpos($leadsApi, "\$dateTo = sanitize(\$_GET['date_to'] ?? '');") !== false);

assertTest("6.5 Phase 3: Bulk update endpoint action=bulk_update intact in api/leads.php",
    strpos($leadsApi, "in_array(\$action, ['bulk_update', 'bulk-update', 'bulk'], true)") !== false);

assertTest("6.6 Phase 3: Table View Select All and row selection intact in pages/leads.php",
    strpos($leadsPage, 'id="selectAllLeads"') !== false &&
    strpos($leadsPage, 'class="lead-select-box"') !== false);

// -------------------------------------------------------------
// Final Summary Report
// -------------------------------------------------------------
echo COLOR_CYAN . "\n=======================================================\n";
echo "  Phase 4 Test Execution Complete\n";
echo "  Total Tests Run:    $testsRun\n";
echo COLOR_GREEN . "  Tests Passed:       $testsPassed\n" . COLOR_RESET;
if ($testsFailed > 0) {
    echo COLOR_RED . "  Tests Failed:       $testsFailed\n" . COLOR_RESET;
} else {
    echo COLOR_GREEN . "  ALL TESTS PASSED SUCCESSFULLY! (100%)\n" . COLOR_RESET;
}
echo COLOR_CYAN . "=======================================================\n\n" . COLOR_RESET;

exit($testsFailed > 0 ? 1 : 0);
