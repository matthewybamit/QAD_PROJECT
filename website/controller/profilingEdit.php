<?php
// controller/profilingEdit.php

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

// ✅ Permission check
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
    $schoolName   = $_POST['school_name'] ?? '';
    $address      = $_POST['address'] ?? '';
    $mission      = $_POST['mission_statement'] ?? '';
    $vision       = $_POST['vision_statement'] ?? '';
    $division     = $_POST['division_office'] ?? '';
    $foundingYear = $_POST['founding_year'] ?? null;
    $program      = $_POST['program_offering'] ?? '';
    $population   = $_POST['student_population'] ?? null;
    $description  = $_POST['school_description'] ?? '';
    $website      = $_POST['website_url'] ?? '';
    $facebook     = $_POST['facebook_url'] ?? '';
    $permitNo     = $_POST['permit_no'] ?? '';
    $accreditation= $_POST['accreditation'] ?? '';
    $history      = $_POST['school_history'] ?? '';
    $facultyCount = $_POST['faculty_count'] ?? null;
    $recognition  = $_POST['recognition'] ?? '';
    $facilities   = $_POST['facilities'] ?? '';
    $achievements = $_POST['achievements'] ?? '';
    $contactPerson= $_POST['contact_person'] ?? '';
    $contactPhone = $_POST['contact_phone'] ?? '';
    $contactEmail = $_POST['contact_email'] ?? '';

    $newLogo = $school['school_logo'];

    // ✅ Handle logo upload
    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
        $fileTmp  = $_FILES['school_logo']['tmp_name'];
        $fileName = uniqid("logo_") . "_" . basename($_FILES['school_logo']['name']);
        $filePath = __DIR__ . '/../assets/logos/' . $fileName;

        // Validate file type (basic check)
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array(mime_content_type($fileTmp), $allowed)) {
            if (move_uploaded_file($fileTmp, $filePath)) {
                $newLogo = $fileName;
                // Optionally delete old logo file
                if (!empty($school['school_logo']) && file_exists(__DIR__ . '/../assets/logos/' . $school['school_logo'])) {
                    unlink(__DIR__ . '/../assets/logos/' . $school['school_logo']);
                }
            }
        } else {
            $_SESSION['flash_message'] = "Invalid logo file type.";
            $_SESSION['flash_type'] = "error";
            header("Location: /profilingEdit.php?id=" . $schoolId);
            exit;
        }
    }

    // ✅ Handle logo removal
    if (!empty($_POST['remove_logo'])) {
        if (!empty($school['school_logo']) && file_exists(__DIR__ . '/../assets/logos/' . $school['school_logo'])) {
            unlink(__DIR__ . '/../assets/logos/' . $school['school_logo']);
        }
        $newLogo = null;
    }

    // ✅ Update school record
    $stmt = $db->connection->prepare("
        UPDATE schools 
        SET school_name = ?, address = ?, mission_statement = ?, vision_statement = ?, 
            division_office = ?, founding_year = ?, program_offering = ?, student_population = ?, 
            school_description = ?, website_url = ?, facebook_url = ?, permit_no = ?, 
            accreditation = ?, school_history = ?, faculty_count = ?, recognition = ?, 
            facilities = ?, achievements = ?, contact_person = ?, contact_phone = ?, 
            contact_email = ?, school_logo = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        $schoolName, $address, $mission, $vision,
        $division, $foundingYear, $program, $population,
        $description, $website, $facebook, $permitNo,
        $accreditation, $history, $facultyCount, $recognition,
        $facilities, $achievements, $contactPerson, $contactPhone,
        $contactEmail, $newLogo, $schoolId
    ]);

    // ✅ Consume permission after saving
    $stmt = $db->connection->prepare("DELETE FROM school_edit_permissions WHERE id = ?");
    $stmt->execute([$permission['id']]);

    $_SESSION['flash_message'] = "School updated successfully (logo and details saved). Permission consumed.";
    $_SESSION['flash_type'] = "success";
    header("Location: /profile");
    exit;
}

require_once 'views/profilingEdit.view.php';
