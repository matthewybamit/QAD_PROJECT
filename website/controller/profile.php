<?php
// controllers/profile.php

require_once 'models/GoogleAuth.php';
require_once 'models/SchoolEditPermissions.php';

if (!GoogleAuth::isLoggedIn()) {
    header("Location: /login");
    exit;
}

$currentUser = GoogleAuth::getCurrentUser();
$editPermissions = new SchoolEditPermissions($db->connection);

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// --- Handle cancel request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_request'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['flash_message'] = "Invalid CSRF token.";
        $_SESSION['flash_type'] = "error";
        header('Location: /profile');
        exit;
    }
    
    $requestId = (int)$_POST['cancel_request_id'];
    try {
        $editPermissions->cancelRequest($requestId, $currentUser['id']);
        $_SESSION['flash_message'] = "Request successfully cancelled.";
        $_SESSION['flash_type'] = "success";
    } catch (Exception $e) {
        $_SESSION['flash_message'] = $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }
    header('Location: /profile');
    exit;
}

// --- Handle resubmit request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id']) && !isset($_POST['cancel_request'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = "Invalid CSRF token.";
        $_SESSION['flash_type'] = "error";
        header('Location: /profile');
        exit;
    }
    
    $requestId = (int)$_POST['request_id'];
    $updatedReason = trim($_POST['updated_reason'] ?? '');
    
    if (strlen($updatedReason) < 10) {
        $_SESSION['flash_message'] = "Updated reason must be at least 10 characters.";
        $_SESSION['flash_type'] = "error";
        header('Location: /profile');
        exit;
    }
    
    try {
        $result = $editPermissions->resubmitRequest($requestId, $currentUser['id'], $updatedReason);
        $_SESSION['flash_message'] = $result['message'];
        $_SESSION['flash_type'] = $result['success'] ? 'success' : 'error';
    } catch (Exception $e) {
        $_SESSION['flash_message'] = "Error: " . $e->getMessage();
        $_SESSION['flash_type'] = "error";
    }
    
    header('Location: /profile');
    exit;
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
        SELECT s.*, sep.id as permission_id, sep.expires_at 
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