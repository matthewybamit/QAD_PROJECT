<?php 

// controller/admin/permissions.php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/GoogleAuth.php';
require_once __DIR__ . '/../../models/SchoolEditPermissions.php';
require_once __DIR__ . '/../../models/AdminSecurity.php';

// Strict admin verification
if (!GoogleAuth::isLoggedIn() || !GoogleAuth::isAdmin()) {
    $_SESSION['error'] = 'Access denied.';
    header('Location: /');
    exit;
}

$currentUser = GoogleAuth::getCurrentUser();
$adminSecurity = new AdminSecurity($db->connection);

// Enhanced security check
if (!$adminSecurity->verifyAdminAccess($currentUser['id'])) {
    session_destroy();
    header('Location: /login');
    exit;
}

$editPermissions = new SchoolEditPermissions($db->connection);

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token.';
        header('Location: /admin/permissions');
        exit;
    }

    $action = $_POST['action'] ?? '';
    $permissionId = filter_var($_POST['permission_id'] ?? 0, FILTER_VALIDATE_INT);

    if ($permissionId && in_array($action, ['approve', 'deny'])) {
        if ($action === 'approve') {
            $success = $editPermissions->approvePermission($permissionId, $currentUser['id']);
            $message = $success ? 'Permission approved! User has 24-hour access.' : 'Failed to approve permission.';
            $type = $success ? 'success' : 'error';
            
            if ($success) {
                $adminSecurity->logAdminActivity($currentUser['id'], 'permission_approved', "Approved permission ID: $permissionId");
            }
        } elseif ($action === 'deny') {
            $success = $editPermissions->denyPermission($permissionId, $currentUser['id']);
            $message = $success ? 'Permission request denied.' : 'Failed to deny permission.';
            $type = $success ? 'success' : 'error';
            
            if ($success) {
                $adminSecurity->logAdminActivity($currentUser['id'], 'permission_denied', "Denied permission ID: $permissionId");
            }
        }
        
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
        header('Location: /admin/permissions');
        exit;
    }
}

// Log admin access
$adminSecurity->logAdminActivity($currentUser['id'], 'permissions_page_access', 'Accessed permissions management');

// Get flash messages
$message = $_SESSION['flash_message'] ?? null;
$messageType = $_SESSION['flash_type'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Get data for the view
$pendingRequests = $editPermissions->getPendingRequests();

// Get statistics
$stats = [];
try {
    $stmt = $db->connection->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' AND expires_at > NOW() THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as total_approved,
            SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as total_denied
        FROM school_edit_permissions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats = ['total' => 0, 'pending' => 0, 'active' => 0, 'total_approved' => 0, 'total_denied' => 0];
}

// Get recent activity
$recentActivity = [];
try {
    $stmt = $db->connection->prepare("
        SELECT sep.*, u.name as user_name, s.school_name, admin.name as admin_name
        FROM school_edit_permissions sep
        JOIN users u ON sep.user_id = u.id
        JOIN schools s ON sep.school_id = s.id
        LEFT JOIN users admin ON sep.approved_by = admin.id
        WHERE sep.status IN ('approved', 'denied')
        ORDER BY sep.updated_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentActivity = [];
}
require_once __DIR__ . '/../../views/admin/permissions.view.php';