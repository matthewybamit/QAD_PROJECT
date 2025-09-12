<?php 

require_once 'models/SchoolQuery.php';

// Get the school ID from the URL parameter
$schoolId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$school = null;
$error = null;
$success = null;

// Initialize SchoolQuery
$schoolQuery = new SchoolQuery($db);

if ($schoolId > 0) {
    try {
        $school = $schoolQuery->getSchoolById($schoolId);
        
        if (!$school) {
            $error = "School not found";
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
} else {
    $error = "Invalid school ID";
}

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $school) {
    try {
        // Handle logo upload first
        $logoResult = null;
        $newLogoFilename = $school['school_logo']; // Keep existing logo by default
        
        // Check if removing logo
        if (isset($_POST['remove_logo']) && $_POST['remove_logo'] == '1') {
            $logoResult = $schoolQuery->handleLogoUpload($schoolId, null, true);
            if ($logoResult['success']) {
                $newLogoFilename = null;
                $success = $logoResult['message'];
            } else {
                $error = $logoResult['message'];
                require 'views/profilingForm.view.php';
                exit();
            }
        }
        // Handle logo upload
        elseif (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $logoResult = $schoolQuery->handleLogoUpload($schoolId, $_FILES['school_logo']);
            
            if ($logoResult['success'] && $logoResult['filename']) {
                $newLogoFilename = $logoResult['filename'];
                $success = $logoResult['message'];
            } elseif (!$logoResult['success']) {
                $error = $logoResult['message'];
                require 'views/profilingForm.view.php';
                exit();
            }
        }

        // Prepare data for update
        $updateData = [
            'division_office' => $_POST['division_office'] ?? '',
            'school_name' => $_POST['school_name'] ?? '',
            'address' => $_POST['address'] ?? '',
            'permit_no' => $_POST['permit_no'] ?? '',
            'program_offering' => $_POST['program_offering'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'contact_email' => $_POST['contact_email'] ?? '',
            'contact_person' => $_POST['contact_person'] ?? '',
            'school_description' => $_POST['school_description'] ?? '',
            'school_history' => $_POST['school_history'] ?? '',
            'mission_statement' => $_POST['mission_statement'] ?? '',
            'vision_statement' => $_POST['vision_statement'] ?? '',
            'founding_year' => !empty($_POST['founding_year']) ? (int)$_POST['founding_year'] : null,
            'accreditation' => $_POST['accreditation'] ?? '',
            'recognition' => $_POST['recognition'] ?? '',
            'website_url' => $_POST['website_url'] ?? '',
            'facebook_url' => $_POST['facebook_url'] ?? '',
            'student_population' => !empty($_POST['student_population']) ? (int)$_POST['student_population'] : null,
            'faculty_count' => !empty($_POST['faculty_count']) ? (int)$_POST['faculty_count'] : null,
            'facilities' => $_POST['facilities'] ?? '',
            'achievements' => $_POST['achievements'] ?? '',
            'school_logo' => $newLogoFilename
        ];

        // Update school information
        if ($schoolQuery->updateSchool($schoolId, $updateData)) {
            if (!$success) { // Only set success message if not already set by logo upload
                $success = "School information updated successfully!";
            } else {
                $success .= " School information also updated successfully!";
            }
            
            // Refresh school data
            $school = $schoolQuery->getSchoolById($schoolId);
        } else {
            $error = "Failed to update school information";
        }
        
    } catch (Exception $e) {
        $error = "Update error: " . $e->getMessage();
    }
}

// Include the admin view (editable)
require 'views/profilingForm.view.php';