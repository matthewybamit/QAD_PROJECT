<?php 





// Get the school ID from the URL parameter
$schoolId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$school = null;
$error = null;

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
require_once 'views/profilingForm.view.php';