<?php 


// Get the school ID from the URL parameter
$schoolId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$school = null;
$error = null;
$success = null;

if ($schoolId > 0) {
    try {
        // Fetch school data
        $query = "SELECT * FROM schools WHERE id = :id";
        $stmt = $db->connection->prepare($query);
        $stmt->bindParam(':id', $schoolId, PDO::PARAM_INT);
        $stmt->execute();
        
        $school = $stmt->fetch(PDO::FETCH_ASSOC);
        
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
        $updateQuery = "UPDATE schools SET 
                       division_office = :division_office,
                       school_name = :school_name,
                       address = :address,
                       permit_no = :permit_no,
                       program_offering = :program_offering,
                       contact_phone = :contact_phone,
                       contact_email = :contact_email,
                       contact_person = :contact_person,
                       school_description = :school_description,
                       school_history = :school_history,
                       mission_statement = :mission_statement,
                       vision_statement = :vision_statement,
                       founding_year = :founding_year,
                       accreditation = :accreditation,
                       recognition = :recognition,
                       website_url = :website_url,
                       facebook_url = :facebook_url,
                       student_population = :student_population,
                       faculty_count = :faculty_count,
                       facilities = :facilities,
                       achievements = :achievements,
                       updated_at = NOW()
                       WHERE id = :id";
        
        $updateStmt = $db->connection->prepare($updateQuery);
        $updateStmt->bindParam(':division_office', $_POST['division_office']);
        $updateStmt->bindParam(':school_name', $_POST['school_name']);
        $updateStmt->bindParam(':address', $_POST['address']);
        $updateStmt->bindParam(':permit_no', $_POST['permit_no']);
        $updateStmt->bindParam(':program_offering', $_POST['program_offering']);
        $updateStmt->bindParam(':contact_phone', $_POST['contact_phone']);
        $updateStmt->bindParam(':contact_email', $_POST['contact_email']);
        $updateStmt->bindParam(':contact_person', $_POST['contact_person']);
        $updateStmt->bindParam(':school_description', $_POST['school_description'] ?? '');
        $updateStmt->bindParam(':school_history', $_POST['school_history'] ?? '');
        $updateStmt->bindParam(':mission_statement', $_POST['mission_statement'] ?? '');
        $updateStmt->bindParam(':vision_statement', $_POST['vision_statement'] ?? '');
        $updateStmt->bindParam(':founding_year', $_POST['founding_year'] ?: null);
        $updateStmt->bindParam(':accreditation', $_POST['accreditation'] ?? '');
        $updateStmt->bindParam(':recognition', $_POST['recognition'] ?? '');
        $updateStmt->bindParam(':website_url', $_POST['website_url'] ?? '');
        $updateStmt->bindParam(':facebook_url', $_POST['facebook_url'] ?? '');
        $updateStmt->bindParam(':student_population', $_POST['student_population'] ?: null);
        $updateStmt->bindParam(':faculty_count', $_POST['faculty_count'] ?: null);
        $updateStmt->bindParam(':facilities', $_POST['facilities'] ?? '');
        $updateStmt->bindParam(':achievements', $_POST['achievements'] ?? '');
        $updateStmt->bindParam(':id', $schoolId, PDO::PARAM_INT);
        
        if ($updateStmt->execute()) {
            $success = "School information updated successfully!";
            // Refresh school data
            $stmt->execute();
            $school = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to update school information";
        }
    } catch (Exception $e) {
        $error = "Update error: " . $e->getMessage();
    }
}

// Include the admin view (editable)
require 'views/profilingForm.view.php';