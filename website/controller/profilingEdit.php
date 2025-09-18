<?php
// controller/profilingEdit.php
require_once 'config/db.php';
require_once 'models/GoogleAuth.php';

if (!GoogleAuth::isLoggedIn()) {
    header("Location: /login");
    exit;
}

$currentUser = GoogleAuth::getCurrentUser();
$schoolId = $_GET['id'] ?? null;

if (!$schoolId) {
    $_SESSION['flash_message'] = "Invalid school ID.";
    $_SESSION['flash_type'] = "error";
    header("Location: /profile");
    exit;
}

// ✅ Check if user has valid edit permission for this school
$stmt = $db->connection->prepare("
    SELECT * FROM school_edit_permissions
    WHERE user_id = ? 
      AND school_id = ? 
      AND status = 'approved'
      AND expires_at > NOW()
    LIMIT 1
");
$stmt->execute([$currentUser['id'], $schoolId]);
$permission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$permission) {
    $_SESSION['flash_message'] = "You don't have permission to edit this school or your permission expired.";
    $_SESSION['flash_type'] = "error";
    header("Location: /profile");
    exit;
}

// ✅ Fetch school details
$stmt = $db->connection->prepare("SELECT * FROM schools WHERE id = ?");
$stmt->execute([$schoolId]);
$school = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$school) {
    $_SESSION['flash_message'] = "School not found.";
    $_SESSION['flash_type'] = "error";
    header("Location: /profile");
    exit;
}

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schoolName = $_POST['school_name'];
    $address = $_POST['address'];

    // Update school record
    $stmt = $db->connection->prepare("
        UPDATE schools SET school_name = ?, address = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$schoolName, $address, $schoolId]);

    // ❌ Remove the permission after first edit
    $stmt = $db->connection->prepare("
        DELETE FROM school_edit_permissions WHERE id = ?
    ");
    $stmt->execute([$permission['id']]);

    $_SESSION['flash_message'] = "School updated successfully. Your edit permission has been consumed.";
    $_SESSION['flash_type'] = "success";
    header("Location: /profile");
    exit;
}

require_once 'views/profilingEdit.view.php';
