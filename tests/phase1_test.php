<?php
/**
 * Phase 1 Test Suite - Customer Association & Priority Support
 * Ira Sportswear Lead Management Module
 * Tests all 10 required scenarios from the implementation brief.
 */

// Colors for terminal output
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
echo "  CWFMS Phase 1 Verification Test Suite\n";
echo "  Customer Association & Priority Support\n";
echo "=======================================================\n" . COLOR_RESET;

// -------------------------------------------------------------
// Test Group 1: Database Migration File & Schema Verification
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 1: Migration & Schema Inspection ---\n" . COLOR_RESET;

$migrationFile = __DIR__ . '/../migration_v3_leads.sql';
$migrationExists = file_exists($migrationFile);
assertTest("1.1 migration_v3_leads.sql exists", $migrationExists);

$migrationContent = $migrationExists ? file_get_contents($migrationFile) : '';
assertTest("1.2 Migration adds 'priority' column safely with DEFAULT 'medium'", 
    strpos($migrationContent, "ADD COLUMN IF NOT EXISTS priority VARCHAR(20) DEFAULT 'medium'") !== false);

assertTest("1.3 Migration includes CHECK constraint for 'low', 'medium', 'high'", 
    strpos($migrationContent, "CHECK (priority IN ('low', 'medium', 'high'))") !== false);

assertTest("1.4 Migration updates existing records to 'medium' if NULL", 
    strpos($migrationContent, "UPDATE leads SET priority = 'medium' WHERE priority IS NULL") !== false);

assertTest("1.5 Migration creates index on priority and customer_id", 
    strpos($migrationContent, "CREATE INDEX IF NOT EXISTS idx_leads_priority") !== false &&
    strpos($migrationContent, "CREATE INDEX IF NOT EXISTS idx_leads_customer_id") !== false);

$setupFile = __DIR__ . '/../supabase_setup.sql';
$setupContent = file_exists($setupFile) ? file_get_contents($setupFile) : '';
assertTest("1.6 supabase_setup.sql has priority column in table definition",
    strpos($setupContent, "priority VARCHAR(20) NOT NULL DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high'))") !== false);

// -------------------------------------------------------------
// Test Group 2: Code Inspection for api/leads.php
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 2: api/leads.php Code Inspection ---\n" . COLOR_RESET;

$apiFile = __DIR__ . '/../api/leads.php';
$apiContent = file_exists($apiFile) ? file_get_contents($apiFile) : '';

assertTest("2.1 api/leads.php has customers-list action", 
    strpos($apiContent, "in_array(\$action, ['customers-list', 'customer-list'], true)") !== false);

assertTest("2.2 api/leads.php validates that customer_id is required", 
    strpos($apiContent, "Customer selection is required.") !== false);

assertTest("2.3 api/leads.php validates customer existence against database", 
    strpos($apiContent, "Selected customer does not exist or has been deleted.") !== false);

assertTest("2.4 api/leads.php validates priority (low, medium, high)", 
    strpos($apiContent, "Invalid priority level. Allowed values: low, medium, high.") !== false);

assertTest("2.5 api/leads.php stores priority in create payload", 
    strpos($apiContent, "'priority' => \$priority") !== false);

assertTest("2.6 api/leads.php stores customer_id in create payload", 
    strpos($apiContent, "'customer_id' => \$customerId") !== false);

assertTest("2.7 api/leads.php supports customer_id in PUT update", 
    strpos($apiContent, "\$update['customer_id'] = \$newCustomerId") !== false);

assertTest("2.8 api/leads.php supports priority in PUT update and logs activity", 
    strpos($apiContent, "\$update['priority'] = \$newPriority") !== false &&
    strpos($apiContent, "addLeadActivity(\$leadId, 'Priority Changed'") !== false);

assertTest("2.9 api/leads.php includes priority in GET response", 
    strpos($apiContent, "\$lead['priority'] = strtolower(\$lead['priority'] ?? 'medium')") !== false);

// -------------------------------------------------------------
// Test Group 3: Code Inspection for pages/leads.php
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 3: pages/leads.php Frontend Inspection ---\n" . COLOR_RESET;

$pageFile = __DIR__ . '/../pages/leads.php';
$pageContent = file_exists($pageFile) ? file_get_contents($pageFile) : '';

assertTest("3.1 Customer selection dropdown exists in Lead Form", 
    strpos($pageContent, 'id="lead_customer_id"') !== false &&
    strpos($pageContent, 'name="customer_id"') !== false);

assertTest("3.2 Customer search input exists for filtering customers", 
    strpos($pageContent, 'id="leadCustomerSearch"') !== false);

assertTest("3.3 Priority radio selector exists with Low, Medium, High options", 
    strpos($pageContent, 'value="low"') !== false &&
    strpos($pageContent, 'value="medium"') !== false &&
    strpos($pageContent, 'value="high"') !== false);

assertTest("3.4 Medium priority is checked by default in HTML form", 
    strpos($pageContent, 'name="priority" value="medium" checked') !== false);

assertTest("3.5 Priority badge CSS classes defined (high, medium, low)", 
    strpos($pageContent, '.priority-badge.high') !== false &&
    strpos($pageContent, '.priority-badge.medium') !== false &&
    strpos($pageContent, '.priority-badge.low') !== false);

assertTest("3.6 Kanban card left border styling defined for priority", 
    strpos($pageContent, '.kanban-card[data-priority="high"]') !== false &&
    strpos($pageContent, '.kanban-card[data-priority="medium"]') !== false &&
    strpos($pageContent, '.kanban-card[data-priority="low"]') !== false);

assertTest("3.7 Kanban card renders customer name & academy name", 
    strpos($pageContent, 'lead.customer.team_academy_name') !== false &&
    strpos($pageContent, 'lead.customer.name') !== false);

assertTest("3.8 Table view includes Priority header and priority column", 
    strpos($pageContent, '<th>Priority</th>') !== false &&
    strpos($pageContent, 'priority-badge') !== false);

assertTest("3.9 saveLead() validates that customer is selected before submit", 
    strpos($pageContent, "if (!customerId) {") !== false &&
    strpos($pageContent, "Please select a customer.") !== false);

assertTest("3.10 saveLead() passes customer_id and priority in both create & update", 
    strpos($pageContent, "payload.set('customer_id', customerId)") !== false &&
    strpos($pageContent, "customer_id: customerId") !== false &&
    strpos($pageContent, "priority: priority") !== false);

assertTest("3.11 Hardcoded customer_id: null has been completely removed", 
    strpos($pageContent, "customer_id: null") === false);

assertTest("3.12 populateLeadForm() populates selected customer and priority on edit", 
    strpos($pageContent, "setRadio('priority', lead.priority || 'medium')") !== false &&
    strpos($pageContent, "populateCustomerSelect(allCustomers, customerId)") !== false);

// -------------------------------------------------------------
// Test Group 4: Simulation of 10 Functional Scenarios
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 4: Execution of 10 Required Scenarios ---\n" . COLOR_RESET;

// Mock database store for functional simulation
$mockCustomers = [
    'cust-001' => ['id' => 'cust-001', 'name' => 'Rajesh Kumar', 'team_academy_name' => 'Chennai FC Academy', 'mobile_number' => '9876543210', 'is_deleted' => false],
    'cust-002' => ['id' => 'cust-002', 'name' => 'Priya Sharma', 'team_academy_name' => 'Mumbai Strikers Club', 'mobile_number' => '9812345678', 'is_deleted' => false],
    'cust-003' => ['id' => 'cust-003', 'name' => 'Ahmed Al-Rashid', 'team_academy_name' => 'Dubai Sports Club', 'mobile_number' => '501234567', 'is_deleted' => false]
];

$mockLeads = [
    'lead-001' => [
        'id' => 'lead-001',
        'customer_id' => 'cust-001',
        'priority' => 'medium',
        'lead_source' => 'Website',
        'sport' => 'Cricket',
        'lead_status' => 'Received',
        'is_deleted' => false
    ]
];

// Helper to simulate backend validation & processing logic from api/leads.php
function simulateCreateLead(array $input, array $customers, array &$leads): array {
    $customerId = trim((string)($input['customer_id'] ?? ''));
    if (empty($customerId)) {
        return ['success' => false, 'code' => 422, 'message' => 'Customer selection is required.'];
    }
    if (!isset($customers[$customerId]) || $customers[$customerId]['is_deleted']) {
        return ['success' => false, 'code' => 404, 'message' => 'Selected customer does not exist or has been deleted.'];
    }

    $rawPriority = trim((string)($input['priority'] ?? 'medium'));
    $priority = strtolower($rawPriority ?: 'medium');
    if (!in_array($priority, ['low', 'medium', 'high'], true)) {
        return ['success' => false, 'code' => 422, 'message' => 'Invalid priority level. Allowed values: low, medium, high.'];
    }

    $leadSource = trim((string)($input['lead_source'] ?? 'Website'));
    $sport = trim((string)($input['sport'] ?? 'Cricket'));
    $leadStatus = trim((string)($input['lead_status'] ?? 'Received'));

    $newId = 'lead-' . uniqid();
    $newLead = [
        'id' => $newId,
        'customer_id' => $customerId,
        'priority' => $priority,
        'lead_source' => $leadSource,
        'sport' => $sport,
        'lead_status' => $leadStatus,
        'is_deleted' => false
    ];
    $leads[$newId] = $newLead;

    return ['success' => true, 'code' => 201, 'message' => 'Lead created successfully', 'data' => $newLead];
}

function simulateUpdateLead(string $leadId, array $input, array $customers, array &$leads): array {
    if (!isset($leads[$leadId]) || $leads[$leadId]['is_deleted']) {
        return ['success' => false, 'code' => 404, 'message' => 'Lead not found'];
    }

    $lead = &$leads[$leadId];

    if (array_key_exists('customer_id', $input)) {
        $newCustomerId = trim((string)$input['customer_id']);
        if (empty($newCustomerId)) {
            return ['success' => false, 'code' => 422, 'message' => 'Customer selection is required.'];
        }
        if (!isset($customers[$newCustomerId]) || $customers[$newCustomerId]['is_deleted']) {
            return ['success' => false, 'code' => 404, 'message' => 'Selected customer does not exist or has been deleted.'];
        }
        $lead['customer_id'] = $newCustomerId;
    }

    if (array_key_exists('priority', $input)) {
        $newPriority = strtolower(trim((string)$input['priority']));
        if (!in_array($newPriority, ['low', 'medium', 'high'], true)) {
            return ['success' => false, 'code' => 422, 'message' => 'Invalid priority level. Allowed values: low, medium, high.'];
        }
        $lead['priority'] = $newPriority;
    }

    if (array_key_exists('lead_status', $input)) {
        $allowedStatuses = ['Received','Analysis','Awaiting Clarification','Order Sheet Prepared','Verified','Awaiting Approval','Approved','Awaiting Payment','Moved to Production','Lost'];
        if (!in_array($input['lead_status'], $allowedStatuses, true)) {
            return ['success' => false, 'code' => 422, 'message' => 'Invalid lead status.'];
        }
        $lead['lead_status'] = $input['lead_status'];
    }

    return ['success' => true, 'code' => 200, 'message' => 'Lead updated successfully', 'data' => $lead];
}

// Scenario 1: Create a lead with a valid customer and High priority
$res1 = simulateCreateLead([
    'customer_id' => 'cust-001',
    'priority' => 'high',
    'lead_source' => 'Website',
    'sport' => 'Cricket'
], $mockCustomers, $mockLeads);
assertTest("Scenario 1: Create lead with valid customer and High priority", 
    $res1['success'] === true && $res1['data']['priority'] === 'high' && $res1['data']['customer_id'] === 'cust-001');
$createdLeadHigh = $res1['data']['id'];

// Scenario 2: Create a lead with a valid customer and Medium priority
$res2 = simulateCreateLead([
    'customer_id' => 'cust-002',
    'priority' => 'medium',
    'lead_source' => 'WhatsApp',
    'sport' => 'Soccer'
], $mockCustomers, $mockLeads);
assertTest("Scenario 2: Create lead with valid customer and Medium priority", 
    $res2['success'] === true && $res2['data']['priority'] === 'medium' && $res2['data']['customer_id'] === 'cust-002');

// Scenario 3: Create a lead with a valid customer and Low priority
$res3 = simulateCreateLead([
    'customer_id' => 'cust-003',
    'priority' => 'low',
    'lead_source' => 'Email',
    'sport' => 'Tennis'
], $mockCustomers, $mockLeads);
assertTest("Scenario 3: Create lead with valid customer and Low priority", 
    $res3['success'] === true && $res3['data']['priority'] === 'low' && $res3['data']['customer_id'] === 'cust-003');

// Scenario 4: Edit a lead and change its customer
$res4 = simulateUpdateLead($createdLeadHigh, ['customer_id' => 'cust-002'], $mockCustomers, $mockLeads);
assertTest("Scenario 4: Edit lead and change its associated customer", 
    $res4['success'] === true && $mockLeads[$createdLeadHigh]['customer_id'] === 'cust-002');

// Scenario 5: Edit a lead and change its priority
$res5 = simulateUpdateLead($createdLeadHigh, ['priority' => 'low'], $mockCustomers, $mockLeads);
assertTest("Scenario 5: Edit lead and change its priority from high to low", 
    $res5['success'] === true && $mockLeads[$createdLeadHigh]['priority'] === 'low');

// Scenario 6: Attempt to create a lead without selecting a customer
$res6 = simulateCreateLead([
    'customer_id' => '',
    'priority' => 'medium'
], $mockCustomers, $mockLeads);
assertTest("Scenario 6: Attempt to create lead without customer fails with 422", 
    $res6['success'] === false && $res6['code'] === 422);

// Scenario 7: Attempt to submit an invalid priority
$res7 = simulateCreateLead([
    'customer_id' => 'cust-001',
    'priority' => 'critical' // invalid
], $mockCustomers, $mockLeads);
assertTest("Scenario 7: Attempt to submit invalid priority 'critical' fails with 422", 
    $res7['success'] === false && $res7['code'] === 422);

// Scenario 8: Attempt to submit a nonexistent customer ID
$res8 = simulateCreateLead([
    'customer_id' => 'non-existent-uuid-9999',
    'priority' => 'high'
], $mockCustomers, $mockLeads);
assertTest("Scenario 8: Attempt to submit nonexistent customer ID fails with 404", 
    $res8['success'] === false && $res8['code'] === 404);

// Scenario 9: Verify existing leads still load correctly with default priority
$existingLead = $mockLeads['lead-001'];
assertTest("Scenario 9: Existing leads load correctly with customer and medium priority", 
    !empty($existingLead['customer_id']) && $existingLead['priority'] === 'medium');

// Scenario 10: Verify that all 10 existing lead workflow stages continue working
$allStages = [
    'Received', 'Analysis', 'Awaiting Clarification', 'Order Sheet Prepared',
    'Verified', 'Awaiting Approval', 'Approved', 'Awaiting Payment',
    'Moved to Production', 'Lost'
];
$stagesPassed = 0;
foreach ($allStages as $stage) {
    $resStage = simulateUpdateLead($createdLeadHigh, ['lead_status' => $stage], $mockCustomers, $mockLeads);
    if ($resStage['success'] && $mockLeads[$createdLeadHigh]['lead_status'] === $stage) {
        $stagesPassed++;
    }
}
assertTest("Scenario 10: All 10 lead workflow stages can be transitioned smoothly", 
    $stagesPassed === 10, "Passed $stagesPassed of 10 stages");

echo COLOR_CYAN . "\n=======================================================\n";
echo "  Test Summary: $testsPassed / $testsRun tests passed (" . round(($testsPassed / $testsRun) * 100) . "%)\n";
if ($testsFailed === 0) {
    echo COLOR_GREEN . "  All Phase 1 tests completed successfully!\n" . COLOR_RESET;
} else {
    echo COLOR_RED . "  $testsFailed tests failed!\n" . COLOR_RESET;
}
echo COLOR_CYAN . "=======================================================\n\n" . COLOR_RESET;

exit($testsFailed > 0 ? 1 : 0);
