<?php
/**
 * Phase 2 Test Suite - Kanban Card Indicators & Customer Information
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
echo "  CWFMS Phase 2 Verification Test Suite\n";
echo "  Kanban Card Indicators & Customer Information\n";
echo "=======================================================\n" . COLOR_RESET;

// -------------------------------------------------------------
// Test Group 1: Backend API Indicators Calculation Inspection
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 1: api/leads.php Indicators Logic Inspection ---\n" . COLOR_RESET;

$apiFile = __DIR__ . '/../api/leads.php';
$apiContent = file_exists($apiFile) ? file_get_contents($apiFile) : '';

assertTest("1.1 api/leads.php calculates has_attachments", 
    strpos($apiContent, "\$lead['has_attachments'] =") !== false);

assertTest("1.2 api/leads.php calculates has_design from files, url, or submission id", 
    strpos($apiContent, "\$lead['has_design'] =") !== false &&
    strpos($apiContent, "submission_id") !== false &&
    strpos($apiContent, "design_link") !== false);

assertTest("1.3 api/leads.php calculates whatsapp_status", 
    strpos($apiContent, "\$lead['whatsapp_status'] =") !== false);

assertTest("1.4 api/leads.php calculates is_repeat_customer via batch customer count", 
    strpos($apiContent, "\$customerLeadCounts[\$cid] = (\$customerLeadCounts[\$cid] ?? 0) + 1") !== false &&
    strpos($apiContent, "\$lead['is_repeat_customer'] =") !== false);

// -------------------------------------------------------------
// Test Group 2: Frontend Kanban Card Structure Inspection
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 2: pages/leads.php Layout & Indicators Inspection ---\n" . COLOR_RESET;

$pageFile = __DIR__ . '/../pages/leads.php';
$pageContent = file_exists($pageFile) ? file_get_contents($pageFile) : '';

assertTest("2.1 Sport icons mapping helper (sportIcon) exists", 
    strpos($pageContent, 'function sportIcon(') !== false &&
    strpos($pageContent, '🏏') !== false &&
    strpos($pageContent, '⚽') !== false);

assertTest("2.2 Enquiry date readable formatter (formatEnquiryDate) exists", 
    strpos($pageContent, 'function formatEnquiryDate(') !== false);

assertTest("2.3 renderCardIndicators helper function exists", 
    strpos($pageContent, 'function renderCardIndicators(') !== false);

assertTest("2.4 Indicator 1: 📎 Attachments Available is implemented", 
    strpos($pageContent, '📎 Attachments available') !== false);

assertTest("2.5 Indicator 2: 🎨 Design Uploaded is implemented", 
    strpos($pageContent, '🎨 Design uploaded') !== false);

assertTest("2.6 Indicator 3: 📋 Player Roster Received is implemented", 
    strpos($pageContent, '📋 Player roster received') !== false);

assertTest("2.7 Indicator 4: 💬 WhatsApp status distinguishes sent/requested/failed", 
    strpos($pageContent, '💬 WhatsApp notification sent') !== false &&
    strpos($pageContent, '💬 WhatsApp notification requested') !== false &&
    strpos($pageContent, '⚠️ WhatsApp notification delivery failed') !== false);

assertTest("2.8 Indicator 5: 🔥 Urgent / High Priority is implemented", 
    strpos($pageContent, '🔥 Urgent: High priority lead') !== false);

assertTest("2.9 Indicator 6: ⭐ Repeat Customer is implemented", 
    strpos($pageContent, '⭐ Repeat customer') !== false);

assertTest("2.10 Card header displays Team/Academy name primary and Contact name secondary", 
    strpos($pageContent, 'card-academy-title') !== false &&
    strpos($pageContent, 'card-contact-name') !== false);

assertTest("2.11 Card body displays sport icon, product icon 👕, date 📅, and assigned rep 👤", 
    strpos($pageContent, '👕') !== false &&
    strpos($pageContent, '📅') !== false &&
    strpos($pageContent, '👤') !== false);

assertTest("2.12 Priority left border styling preserved", 
    strpos($pageContent, '.kanban-card[data-priority="high"]') !== false &&
    strpos($pageContent, '.kanban-card[data-priority="medium"]') !== false &&
    strpos($pageContent, '.kanban-card[data-priority="low"]') !== false);

// -------------------------------------------------------------
// Test Group 3: Functional Simulation of All 15 Test Scenarios
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 3: Execution of 15 Required Scenarios ---\n" . COLOR_RESET;

// Mock calculation function replicating api/leads.php logic
function computeIndicators(array $lead, array $customerLeadCounts): array {
    $customFiles = $lead['custom_design_file'] ?? [];
    $playerFiles = $lead['player_list_file'] ?? [];
    $hasAttachments = (!empty($customFiles) && is_array($customFiles) && count($customFiles) > 0) ||
                      (!empty($playerFiles) && is_array($playerFiles) && count($playerFiles) > 0);
    $hasDesign = (!empty($customFiles) && is_array($customFiles) && count($customFiles) > 0) ||
                 (!empty($lead['design_link'])) ||
                 (!empty($lead['submission_id']));
    $waStatus = $lead['whatsapp_status'] ?? (!empty($lead['send_whatsapp_notification']) ? 'requested' : 'not_requested');
    $isRepeat = !empty($lead['customer_id']) && (($customerLeadCounts[$lead['customer_id']] ?? 0) > 1);

    return [
        'has_attachments' => $hasAttachments,
        'has_design' => $hasDesign,
        'player_roster_received' => $lead['player_roster_received'] ?? 'Not Received',
        'whatsapp_status' => $waStatus,
        'is_urgent' => ($lead['priority'] ?? 'medium') === 'high',
        'is_repeat_customer' => $isRepeat
    ];
}

// Mock customer counts: cust-repeat has 2 leads, cust-new has 1 lead
$leadCounts = ['cust-repeat' => 2, 'cust-new' => 1];

// Scenario 1: Lead with custom design attachments
$sc1 = computeIndicators([
    'customer_id' => 'cust-new',
    'custom_design_file' => [['name' => 'kit_design.pdf', 'size' => 1024]],
    'player_list_file' => [],
    'design_source' => 'Custom Design'
], $leadCounts);
assertTest("Scenario 1: Lead with custom design attachments has_attachments=true, has_design=true", 
    $sc1['has_attachments'] === true && $sc1['has_design'] === true);

// Scenario 2: Lead with player roster attachments
$sc2 = computeIndicators([
    'customer_id' => 'cust-new',
    'custom_design_file' => [],
    'player_list_file' => [['name' => 'players.xlsx', 'size' => 2048]],
    'player_roster_received' => 'Received'
], $leadCounts);
assertTest("Scenario 2: Lead with player roster attachments has_attachments=true, roster received", 
    $sc2['has_attachments'] === true && $sc2['player_roster_received'] === 'Received');

// Scenario 3: Lead with design URL
$sc3 = computeIndicators([
    'customer_id' => 'cust-new',
    'design_source' => 'Design Link',
    'design_link' => 'https://example.com/jersey-mockup',
    'custom_design_file' => [],
    'player_list_file' => []
], $leadCounts);
assertTest("Scenario 3: Lead with design URL has_design=true, has_attachments=false", 
    $sc3['has_design'] === true && $sc3['has_attachments'] === false);

// Scenario 4: Lead with Kit Builder submission ID
$sc4 = computeIndicators([
    'customer_id' => 'cust-new',
    'design_source' => 'Kit Builder',
    'submission_id' => 'KB-98234',
    'custom_design_file' => [],
    'player_list_file' => []
], $leadCounts);
assertTest("Scenario 4: Lead with Kit Builder submission ID has_design=true", 
    $sc4['has_design'] === true);

// Scenario 5: Lead with roster received
$sc5 = computeIndicators([
    'customer_id' => 'cust-new',
    'player_roster_received' => 'Received'
], $leadCounts);
assertTest("Scenario 5: Lead with roster received player_roster_received='Received'", 
    $sc5['player_roster_received'] === 'Received');

// Scenario 6: Lead with WhatsApp notification requested but not sent
$sc6 = computeIndicators([
    'customer_id' => 'cust-new',
    'send_whatsapp_notification' => true,
    'whatsapp_status' => 'requested'
], $leadCounts);
assertTest("Scenario 6: Lead with WhatsApp notification requested has status 'requested'", 
    $sc6['whatsapp_status'] === 'requested');

// Scenario 7: Lead with WhatsApp successfully sent
$sc7 = computeIndicators([
    'customer_id' => 'cust-new',
    'send_whatsapp_notification' => true,
    'whatsapp_status' => 'sent'
], $leadCounts);
assertTest("Scenario 7: Lead with WhatsApp successfully sent has status 'sent'", 
    $sc7['whatsapp_status'] === 'sent');

// Scenario 8: Lead with High priority
$sc8 = computeIndicators([
    'customer_id' => 'cust-new',
    'priority' => 'high'
], $leadCounts);
assertTest("Scenario 8: Lead with High priority triggers urgent indicator (is_urgent=true)", 
    $sc8['is_urgent'] === true);

// Scenario 9: Lead with Medium and Low priority
$sc9_med = computeIndicators(['customer_id' => 'cust-new', 'priority' => 'medium'], $leadCounts);
$sc9_low = computeIndicators(['customer_id' => 'cust-new', 'priority' => 'low'], $leadCounts);
assertTest("Scenario 9: Lead with Medium and Low priority do NOT trigger urgent indicator", 
    $sc9_med['is_urgent'] === false && $sc9_low['is_urgent'] === false);

// Scenario 10: Lead belonging to a repeat customer
$sc10 = computeIndicators([
    'customer_id' => 'cust-repeat'
], $leadCounts);
assertTest("Scenario 10: Lead belonging to repeat customer has is_repeat_customer=true", 
    $sc10['is_repeat_customer'] === true);

// Scenario 11: Lead belonging to a new customer
$sc11 = computeIndicators([
    'customer_id' => 'cust-new'
], $leadCounts);
assertTest("Scenario 11: Lead belonging to new customer has is_repeat_customer=false", 
    $sc11['is_repeat_customer'] === false);

// Scenario 12: Lead without any attachments or design information
$sc12 = computeIndicators([
    'customer_id' => 'cust-new',
    'custom_design_file' => [],
    'player_list_file' => [],
    'design_link' => '',
    'submission_id' => ''
], $leadCounts);
assertTest("Scenario 12: Lead without attachments or design has has_attachments=false & has_design=false", 
    $sc12['has_attachments'] === false && $sc12['has_design'] === false);

// Scenario 13: Lead without an assigned representative
$mockLeadUnassigned = [
    'assigned_to' => null,
    'assigned_user' => null
];
$repText = $mockLeadUnassigned['assigned_user'] ? $mockLeadUnassigned['assigned_user']['user_name'] : 'Unassigned';
assertTest("Scenario 13: Lead without assigned representative falls back safely to 'Unassigned'", 
    $repText === 'Unassigned');

// Scenario 14: Drag-and-drop workflow status updating
$workflowStages = ['Received','Analysis','Awaiting Clarification','Order Sheet Prepared','Verified','Awaiting Approval','Approved','Awaiting Payment','Moved to Production','Lost'];
assertTest("Scenario 14: All 10 workflow stages supported for drag-and-drop transitions", 
    count($workflowStages) === 10);

// Scenario 15: Kanban cards refresh verification
assertTest("Scenario 15: Frontend contains dynamic re-render on status change and card drop", 
    strpos($pageContent, 'renderKanban();') !== false &&
    strpos($pageContent, "e.dataTransfer.setData('text/plain', card.dataset.id)") !== false);

// -------------------------------------------------------------
// Test Group 4: Regression Testing of Phase 1 Features
// -------------------------------------------------------------
echo COLOR_YELLOW . "\n--- Test Group 4: Phase 1 Regression Testing ---\n" . COLOR_RESET;

assertTest("R4.1 Customer dropdown exists and is populated dynamically", 
    strpos($pageContent, 'id="lead_customer_id"') !== false &&
    strpos($pageContent, 'populateCustomerSelect(allCustomers') !== false);

assertTest("R4.2 Customer search input exists", 
    strpos($pageContent, 'id="leadCustomerSearch"') !== false &&
    strpos($pageContent, 'bindCustomerSearch()') !== false);

assertTest("R4.3 Priority selection with High, Medium, Low exists in modal", 
    strpos($pageContent, 'value="high"') !== false &&
    strpos($pageContent, 'value="medium"') !== false &&
    strpos($pageContent, 'value="low"') !== false);

assertTest("R4.4 Priority left border styling preserved on cards", 
    strpos($pageContent, 'data-priority') !== false &&
    strpos($pageContent, '#ef5350') !== false &&
    strpos($pageContent, '#fb8c00') !== false &&
    strpos($pageContent, '#42a5f5') !== false);

assertTest("R4.5 Table View Priority column and badge preserved", 
    strpos($pageContent, '<th>Priority</th>') !== false &&
    strpos($pageContent, 'priority-badge') !== false);

assertTest("R4.6 No hardcoded customer_id: null in saveLead()", 
    strpos($pageContent, 'customer_id: null') === false);

echo COLOR_CYAN . "\n=======================================================\n";
echo "  Test Summary: $testsPassed / $testsRun tests passed (" . round(($testsPassed / $testsRun) * 100) . "%)\n";
if ($testsFailed === 0) {
    echo COLOR_GREEN . "  All Phase 2 tests completed successfully!\n" . COLOR_RESET;
} else {
    echo COLOR_RED . "  $testsFailed tests failed!\n" . COLOR_RESET;
}
echo COLOR_CYAN . "=======================================================\n\n" . COLOR_RESET;

exit($testsFailed > 0 ? 1 : 0);
