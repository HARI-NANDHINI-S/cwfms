<?php
// ============================================================
// api/auth.php - Authentication & Role Management
// ============================================================
require_once '../includes/config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method !== 'GET' || $action !== 'me') {
    requireApiAuth();
}

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

// ============================================================
// GET: Current session info
// ============================================================
// GET: Current session info
// ============================================================
if ($method === 'GET' && $action === 'me') {
    if (!isset($_SESSION['user'])) {
        jsonResponse(false, 'Not authenticated', null, 401);
    }
    $user = getCurrentUser();
    jsonResponse(true, 'Session active', $user);
}

// ============================================================
// GET: List all users (admin only)
// ============================================================
if ($method === 'GET' && $action === 'users') {
    $user = getCurrentUser();
    if (!canManageUsers($user)) {
        jsonResponse(false, 'Permission denied. Only Admin can access user management.', null, 403);
    }

    $db = new Supabase(true);
    $result = $db->select('user_roles', ['order' => 'created_at.asc']);

    if ($result['status'] === 200) {
        $users = $result['data'] ?? [];
        foreach ($users as &$u) {
            $u['role_label'] = getRoleLabel($u['role'] ?? 'staff');
            $u['business_role'] = normalizeRole($u['role'] ?? 'staff');
        }
        unset($u);
        jsonResponse(true, 'Users fetched', $users);
    } else {
        jsonResponse(false, 'Failed to fetch users', null, 500);
    }
}

// ============================================================
// POST: Update user role (admin only)
// ============================================================
if ($method === 'POST' && $action === 'update_role') {
    $currentUser = getCurrentUser();
    if (!canManageUsers($currentUser)) {
        jsonResponse(false, 'Permission denied. Only Admin can update user roles.', null, 403);
    }

    $userId = sanitize($input['user_id'] ?? '');
    $role   = strtolower(sanitize($input['role'] ?? ''));

    if ($userId === ($currentUser['id'] ?? '')) {
        jsonResponse(false, 'Administrators cannot modify their own role.', null, 403);
    }

    $validRoles = ['admin', 'manager', 'staff', 'sales_manager', 'sales_executive'];
    if (!$userId || !in_array($role, $validRoles, true)) {
        jsonResponse(false, 'Invalid input. Allowed roles: admin, manager, staff, sales_manager, sales_executive.', null, 400);
    }

    $db = new Supabase(true);
    $result = $db->update('user_roles', ['role' => $role], ['id' => 'eq.' . $userId]);

    if ($result['status'] >= 200 && $result['status'] < 300) {
        jsonResponse(true, 'Role updated successfully');
    } else {
        jsonResponse(false, 'Failed to update role', null, 500);
    }
}

// ============================================================
// POST: Create new user (admin only)
// ============================================================
if ($method === 'POST' && $action === 'create_user') {
    $currentUser = getCurrentUser();
    if (!canManageUsers($currentUser)) {
        jsonResponse(false, 'Permission denied. Only Admin can create users.', null, 403);
    }

    $email = strtolower(sanitize($input['email'] ?? ''));
    $name  = sanitize($input['name'] ?? '');
    $role  = strtolower(sanitize($input['role'] ?? 'staff'));

    if (!$email || !$name || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Valid name and email required', null, 400);
    }

    $validRoles = ['admin', 'manager', 'staff', 'sales_manager', 'sales_executive'];
    if (!in_array($role, $validRoles, true)) {
        jsonResponse(false, 'Invalid role. Allowed roles: admin, manager, staff, sales_manager, sales_executive.', null, 400);
    }

    $db = new Supabase(true);
    $result = $db->insert('user_roles', [
        'user_email' => $email,
        'user_name'  => $name,
        'role'       => $role,
        'is_active'  => true,
    ]);

    if ($result['status'] >= 200 && $result['status'] < 300) {
        jsonResponse(true, 'User created successfully', $result['data'][0] ?? null);
    } else {
        jsonResponse(false, 'Failed to create user (email may already exist)', null, 500);
    }
}

// ============================================================
// POST: Toggle user active status
// ============================================================
if ($method === 'POST' && $action === 'toggle_user') {
    $currentUser = getCurrentUser();
    if (!canManageUsers($currentUser)) {
        jsonResponse(false, 'Permission denied. Only Admin can toggle user status.', null, 403);
    }

    $userId   = sanitize($input['user_id'] ?? '');
    $isActive = (bool)($input['is_active'] ?? false);

    if (!$userId) jsonResponse(false, 'User ID required', null, 400);

    if ($userId === ($currentUser['id'] ?? '') && !$isActive) {
        jsonResponse(false, 'Administrators cannot deactivate their own account.', null, 403);
    }

    $db = new Supabase(true);
    $result = $db->update('user_roles', ['is_active' => $isActive], ['id' => 'eq.' . $userId]);

    if ($result['status'] >= 200 && $result['status'] < 300) {
        jsonResponse(true, 'User status updated');
    } else {
        jsonResponse(false, 'Failed to update status', null, 500);
    }
}

jsonResponse(false, 'Invalid request', null, 405);
