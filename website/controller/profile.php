<?php 
// controller/profile.php - Enhanced with one-time permission system
require_once 'config/db.php';
require_once 'models/GoogleAuth.php';
require_once 'models/SchoolEditPermissions.php';

if (!GoogleAuth::isLoggedIn()) {
    header('Location: /login');
    exit;
}

$currentUser = GoogleAuth::getCurrentUser();
$editPermissions = new SchoolEditPermissions($db->connection);

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Flash messages
$message = $_SESSION['flash_message'] ?? null;
$messageType = $_SESSION['flash_type'] ?? null;
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

$userPermissions = [];
$editableSchools = [];

try {
    // Get user's permission history
    $userPermissions = $editPermissions->getUserPermissions($currentUser['id']);
    
    // Get schools user can currently edit (only unused + not expired)
    $stmt = $db->connection->prepare("
        SELECT s.*, sep.expires_at 
        FROM schools s
        JOIN school_edit_permissions sep ON s.id = sep.school_id
        WHERE sep.user_id = ? 
          AND sep.status = 'approved' 
          AND sep.used = 0
          AND sep.expires_at > NOW()
        ORDER BY s.school_name
    ");
    $stmt->execute([$currentUser['id']]);
    $editableSchools = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all schools for request dropdown
    $stmt = $db->connection->prepare("SELECT id, school_name FROM schools ORDER BY school_name");
    $stmt->execute();
    $allSchools = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $message = "Database error occurred.";
    $messageType = "error";
}

require_once 'views/profile.view.php';
