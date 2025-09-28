<?php 

// Get the school ID from URL parameter
$schoolId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$school = null;
$error = null;

if ($schoolId > 0) {
    try {
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
require_once 'views/profilingFormUser.view.php';
?>

