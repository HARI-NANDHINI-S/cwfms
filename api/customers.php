<?php
// ============================================================
// api/customers.php - Customer CRUD & Relational Leads API
// Ira Sportswear / Studio 3 Interactive - CWFMS
// ============================================================
require_once '../includes/config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

requireApiAuth();

$db = new Supabase(true);
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$hasActiveColumn = dbHasColumn('customers', 'is_active');
$hasScoreColumn  = dbHasColumn('customers', 'profile_score');

// ============================================================
// GET: List customers
// ============================================================
if ($method === 'GET' && $action === 'list') {
    $statusFilter = sanitize($_GET['status'] ?? 'all');

    $params = [
        'order'      => 'created_at.desc',
        'select'     => '*',
        'is_deleted' => 'eq.false',
    ];

    if ($hasActiveColumn) {
        if ($statusFilter === 'active') {
            $params['is_active'] = 'eq.true';
        } elseif ($statusFilter === 'inactive') {
            $params['is_active'] = 'eq.false';
        }
    }

    $result = $db->select('customers', $params);

    if ($db->isSuccess($result)) {
        $customers = enrichCustomers($db, $result['data'] ?? []);

        if (!$hasActiveColumn && $statusFilter !== 'all') {
            $customers = array_values(array_filter($customers, function ($c) use ($statusFilter) {
                $active = !empty($c['is_active']);
                return $statusFilter === 'active' ? $active : !$active;
            }));
        }

        jsonResponse(true, 'Customers fetched', $customers);
    } else {
        jsonResponse(false, $result['message'] ?? 'Failed to fetch customers', null, $result['status'] ?: 500);
    }
}

// ============================================================
// GET: Single customer detail
// ============================================================
if ($method === 'GET' && $action === 'get') {
    $id = sanitize($_GET['id'] ?? '');
    if (!$id) {
        jsonResponse(false, 'Customer ID required', null, 400);
    }
    $result = $db->select('customers', ['id' => 'eq.' . $id, 'is_deleted' => 'eq.false']);
    if ($result['status'] !== 200 || empty($result['data'])) {
        jsonResponse(false, 'Customer not found', null, 404);
    }
    $enriched = enrichCustomers($db, $result['data']);
    jsonResponse(true, 'Customer fetched', $enriched[0]);
}

// ============================================================
// GET: Customer-linked leads
// ============================================================
if ($method === 'GET' && $action === 'leads') {
    $customerId = sanitize($_GET['customer_id'] ?? '');
    if (!$customerId) {
        jsonResponse(false, 'Customer ID required', null, 400);
    }

    $currentUser = getCurrentUser();
    $userRole = normalizeRole($currentUser['role'] ?? 'staff');
    $leadParams = [
        'customer_id' => 'eq.' . $customerId,
        'is_deleted'  => 'eq.false',
        'order'       => 'enquiry_date.desc',
        'select'      => '*',
    ];
    if ($userRole === 'sales_executive') {
        $leadParams['assigned_to'] = 'eq.' . ($currentUser['id'] ?? '');
    }

    $result = $db->select('leads', $leadParams);

    if ($db->isSuccess($result)) {
        $leads = $result['data'] ?? [];
        // Resolve assigned sales rep names
        $usersRes = $db->select('user_roles', ['select' => 'id,user_name']);
        $usersMap = [];
        if ($db->isSuccess($usersRes)) {
            foreach ($usersRes['data'] ?? [] as $u) {
                $usersMap[$u['id']] = $u['user_name'];
            }
        }
        foreach ($leads as &$l) {
            $l['assigned_user_name'] = $usersMap[$l['assigned_to'] ?? ''] ?? 'Unassigned';
            $l['products_interested'] = is_string($l['products_interested'] ?? null)
                ? (json_decode($l['products_interested'], true) ?: [])
                : ($l['products_interested'] ?? []);
        }
        unset($l);
        jsonResponse(true, 'Customer leads fetched', $leads);
    } else {
        jsonResponse(false, 'Failed to fetch customer leads', null, 500);
    }
}

// ============================================================
// POST: Create / Update / Toggle Status / Delete
// ============================================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['action'])) {
        jsonResponse(false, 'Invalid request', null, 400);
    }

    $postAction = $input['action'];
    $user = getCurrentUser();

    // --------------------------------
    // CREATE
    // --------------------------------
    if ($postAction === 'create') {
        if (!hasPermission('create')) {
            jsonResponse(false, 'Permission denied', null, 403);
        }

        $errors = validateCustomerInput($input);
        if (!empty($errors)) {
            jsonResponse(false, implode(', ', $errors), null, 422);
        }

        $dupeCheck = $db->select('customers', [
            'mobile_number' => 'eq.' . sanitize($input['mobile_number']),
            'country_code'  => 'eq.' . sanitize($input['country_code']),
            'is_deleted'    => 'eq.false',
        ]);

        if ($dupeCheck['status'] === 200 && !empty($dupeCheck['data'])) {
            jsonResponse(false, 'A customer with this mobile number already exists', null, 409);
        }

        $data = buildCustomerData($input);
        if ($hasActiveColumn) {
            $data['is_active'] = true;
        }
        if ($hasScoreColumn) {
            $data['profile_score'] = calculateProfileScore($data, true);
        }

        $result = $db->insert('customers', $data);

        if ($db->isSuccess($result)) {
            $newCustomer = is_array($result['data']) ? ($result['data'][0] ?? $result['data']) : $result['data'];
            $db->logActivity($newCustomer['id'] ?? null, 'created', null, $user['name'], 'Customer created');
            if (!$hasActiveColumn) {
                $db->logActivity($newCustomer['id'] ?? null, 'activated', null, $user['name'], 'Customer activated');
            }
            $newCustomer = enrichCustomers($db, [$newCustomer])[0];
            jsonResponse(true, 'Customer created successfully', $newCustomer);
        } else {
            jsonResponse(false, $result['message'] ?? 'Failed to create customer. Please try again.', null, $result['status'] ?: 500);
        }
    }

    // --------------------------------
    // UPDATE
    // --------------------------------
    if ($postAction === 'update') {
        if (!hasPermission('edit')) {
            jsonResponse(false, 'Permission denied. Sales Executives cannot edit customer profiles.', null, 403);
        }

        $id = sanitize($input['id'] ?? '');
        if (!$id) jsonResponse(false, 'Customer ID required', null, 400);

        $errors = validateCustomerInput($input, $id);
        if (!empty($errors)) {
            jsonResponse(false, implode(', ', $errors), null, 422);
        }

        $dupeCheck = $db->select('customers', [
            'mobile_number' => 'eq.' . sanitize($input['mobile_number']),
            'country_code'  => 'eq.' . sanitize($input['country_code']),
            'is_deleted'    => 'eq.false',
            'id'            => 'neq.' . $id,
        ]);

        if ($dupeCheck['status'] === 200 && !empty($dupeCheck['data'])) {
            jsonResponse(false, 'Another customer with this mobile number already exists', null, 409);
        }

        $data = buildCustomerData($input);
        $data['updated_at'] = date('Y-m-d H:i:s');

        $activeStatus = getCustomerActiveStatus($db, $id);
        if ($hasScoreColumn) {
            $data['profile_score'] = calculateProfileScore($data, $activeStatus);
        }

        $result = $db->update('customers', $data, ['id' => 'eq.' . $id]);

        if ($db->isSuccess($result)) {
            $updatedCustomer = is_array($result['data']) ? ($result['data'][0] ?? $result['data']) : $result['data'];
            $db->logActivity($id, 'updated', null, $user['name'], 'Customer updated');
            $updatedCustomer = enrichCustomers($db, [$updatedCustomer])[0];
            jsonResponse(true, 'Customer updated successfully', $updatedCustomer);
        } else {
            jsonResponse(false, $result['message'] ?? 'Failed to update customer', null, $result['status'] ?: 500);
        }
    }

    // --------------------------------
    // TOGGLE STATUS
    // --------------------------------
    if ($postAction === 'toggle_status') {
        if (!hasPermission('toggle_status')) {
            jsonResponse(false, 'Permission denied', null, 403);
        }

        $id = sanitize($input['id'] ?? '');
        if (!$id) jsonResponse(false, 'Customer ID required', null, 400);

        if (!array_key_exists('is_active', $input)) {
            jsonResponse(false, 'Status value required', null, 400);
        }

        $newStatus = filter_var($input['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($newStatus === null) {
            jsonResponse(false, 'Invalid status value', null, 400);
        }

        $currentCustomer = $db->select('customers', ['id' => 'eq.' . $id, 'is_deleted' => 'eq.false', 'select' => '*']);
        if ($currentCustomer['status'] !== 200 || empty($currentCustomer['data'])) {
            jsonResponse(false, 'Customer not found', null, 404);
        }

        $custData = $currentCustomer['data'][0];

        if ($hasActiveColumn) {
            $scoreData = [
                'name'              => $custData['name'],
                'team_academy_name' => $custData['team_academy_name'],
                'email'             => $custData['email'],
                'mobile_number'     => $custData['mobile_number'],
                'address_line_1'    => $custData['address_line_1'],
                'address_line_2'    => $custData['address_line_2'],
                'city'              => $custData['city'],
                'state'             => $custData['state'],
                'pincode'           => $custData['pincode'],
                'country'           => $custData['country'],
            ];

            $updateData = [
                'is_active'  => $newStatus,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($hasScoreColumn) {
                $updateData['profile_score'] = calculateProfileScore($scoreData, $newStatus);
            }

            $result = $db->update('customers', $updateData, ['id' => 'eq.' . $id]);
            if (!$db->isSuccess($result)) {
                jsonResponse(false, $result['message'] ?? 'Failed to update customer status', null, $result['status'] ?: 500);
            }
            $custData = $result['data'][0] ?? array_merge($custData, $updateData);
        }

        $statusText = $newStatus ? 'activated' : 'deactivated';
        $db->logActivity($id, $statusText, ['is_active' => $newStatus], $user['name'], 'Customer ' . $statusText);

        $custData['is_active'] = $newStatus;
        if (!$hasScoreColumn) {
            $custData['profile_score'] = calculateProfileScore($custData, $newStatus);
        }

        jsonResponse(true, 'Customer ' . $statusText . ' successfully', $custData);
    }

    // --------------------------------
    // DELETE (Soft delete)
    // --------------------------------
    if ($postAction === 'delete') {
        if (!hasPermission('delete')) {
            jsonResponse(false, 'Permission denied. Only administrators can delete customers.', null, 403);
        }

        $id = sanitize($input['id'] ?? '');
        if (!$id) jsonResponse(false, 'Customer ID required', null, 400);

        $currentCustomer = $db->select('customers', ['id' => 'eq.' . $id, 'is_deleted' => 'eq.false']);
        if (!$db->isSuccess($currentCustomer) || empty($currentCustomer['data'])) {
            jsonResponse(false, 'Customer not found or already deleted', null, 404);
        }

        $result = $db->update('customers', [
            'is_deleted' => true,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => 'eq.' . $id]);

        if ($db->isSuccess($result)) {
            $db->logActivity($id, 'deleted', null, $user['name'] ?? 'Admin', 'Customer soft deleted');
            jsonResponse(true, 'Customer deleted successfully');
        } else {
            jsonResponse(false, $result['message'] ?? 'Failed to delete customer', null, 500);
        }
    }
}

// ============================================================
// DELETE: Direct HTTP DELETE support
// ============================================================
if ($method === 'DELETE') {
    if (!hasPermission('delete')) {
        jsonResponse(false, 'Permission denied. Only administrators can delete customers.', null, 403);
    }

    $id = sanitize($_GET['id'] ?? '');
    if (!$id) jsonResponse(false, 'Customer ID required', null, 400);

    $currentCustomer = $db->select('customers', ['id' => 'eq.' . $id, 'is_deleted' => 'eq.false']);
    if (!$db->isSuccess($currentCustomer) || empty($currentCustomer['data'])) {
        jsonResponse(false, 'Customer not found or already deleted', null, 404);
    }

    $result = $db->update('customers', [
        'is_deleted' => true,
        'updated_at' => date('Y-m-d H:i:s'),
    ], ['id' => 'eq.' . $id]);

    if ($db->isSuccess($result)) {
        $db->logActivity($id, 'deleted', null, $currentUser['name'] ?? 'Admin', 'Customer soft deleted');
        jsonResponse(true, 'Customer deleted successfully');
    } else {
        jsonResponse(false, $result['message'] ?? 'Failed to delete customer', null, 500);
    }
}

function buildCustomerData($input) {
    return [
        'name'              => sanitize($input['name']),
        'team_academy_name' => sanitize($input['team_academy_name']),
        'email'             => !empty($input['email']) ? strtolower(sanitize($input['email'])) : null,
        'country_code'      => sanitize($input['country_code']),
        'mobile_number'     => sanitize($input['mobile_number']),
        'address_line_1'    => sanitize($input['address_line_1']),
        'address_line_2'    => !empty($input['address_line_2']) ? sanitize($input['address_line_2']) : null,
        'city'              => sanitize($input['city']),
        'state'             => sanitize($input['state']),
        'pincode'           => sanitize($input['pincode']),
        'country'           => sanitize($input['country']),
    ];
}

function validateCustomerInput($input, $id = null) {
    $errors = [];

    if (empty($input['name']) || strlen(trim($input['name'])) < 3) {
        $errors[] = 'Customer name must be at least 3 characters';
    }
    if (empty($input['team_academy_name'])) {
        $errors[] = 'Team/Academy name is required';
    }
    if (!empty($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    if (empty($input['country_code'])) {
        $errors[] = 'Country code is required';
    }
    if (empty($input['mobile_number']) || !preg_match('/^[0-9]{7,15}$/', (string)$input['mobile_number'])) {
        $errors[] = 'Valid mobile number is required (7-15 digits)';
    }
    if (empty($input['address_line_1'])) {
        $errors[] = 'Address Line 1 is required';
    }
    if (empty($input['city'])) {
        $errors[] = 'City is required';
    }
    if (empty($input['state'])) {
        $errors[] = 'State is required';
    }
    if (empty($input['pincode']) || !preg_match('/^[0-9]+$/', (string)$input['pincode'])) {
        $errors[] = 'Valid pincode is required (numeric only)';
    }
    if (empty($input['country'])) {
        $errors[] = 'Country is required';
    }

    return $errors;
}

jsonResponse(false, 'Invalid request method', null, 405);
