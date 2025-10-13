<?php
//controllers/Schools.php
require_once '../website/models/SchoolQuery.php';

class AdminSchools {
    private $db;
    private $adminAuth;
    private $schoolQuery;
    
    public function __construct($database, $adminAuth) {
        $this->db = $database;
        $this->adminAuth = $adminAuth;
        $this->schoolQuery = new SchoolQuery($database);
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    public function index() {
        $currentUser = $this->adminAuth->getCurrentUser();
        
        // Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSchoolAction();
        }
        
        // Get schools with pagination and filters
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $search = $_GET['search'] ?? '';
        $division = $_GET['division'] ?? '';
        $program = $_GET['program'] ?? '';
        
        $params = [
            'page' => $page,
            'limit' => $limit,
            'search' => $search,
            'sort' => $_GET['sort'] ?? 'school_name',
            'order' => $_GET['order'] ?? 'ASC'
        ];
        
        $result = $this->schoolQuery->getSchools($params);
        $schools = $result['schools'];
        $totalPages = $result['totalPages'];
        $totalRecords = $result['totalRecords'];
        
        // Get stats
        $stats = $this->getSchoolStats();
        
        // Get divisions for filter
        $divisions = $this->getDivisions();
        
        $csrfToken = $_SESSION['csrf_token'];
        require_once 'views/schools.view.php';
    }
    
    private function handleSchoolAction() {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            $_SESSION['error'] = 'Invalid security token';
            header('Location: /schools');
            exit;
        }
        
        $action = $_POST['action'] ?? '';
        $currentUser = $this->adminAuth->getCurrentUser();
        
        switch ($action) {
            case 'delete':
                $this->deleteSchool((int)$_POST['school_id'], $currentUser['id']);
                break;
            case 'create':
                $this->createSchool($_POST, $currentUser['id']);
                break;
            case 'update':
                $this->updateSchool((int)$_POST['school_id'], $_POST, $currentUser['id']);
                break;
        }
        
        header('Location: /schools');
        exit;
    }
    
    private function deleteSchool($schoolId, $adminId) {
        try {
            // Check if school has active permissions
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM school_edit_permissions 
                WHERE school_id = ? AND status = 'approved' AND expires_at > NOW()
            ");
            $stmt->execute([$schoolId]);
            $activePermissions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($activePermissions > 0) {
                $_SESSION['error'] = "Cannot delete school with active edit permissions";
                return;
            }
            
            // Get school name for logging
            $school = $this->schoolQuery->getSchoolById($schoolId);
            
            // Delete school
            $this->schoolQuery->deleteSchool($schoolId);
            
            // Log action
            AdminSecurity::logSecurityEvent(
                $adminId,
                'SCHOOL_DELETED',
                "Deleted school: {$school['school_name']} (ID: {$schoolId})",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $_SESSION['success'] = "School deleted successfully";
        } catch (Exception $e) {
            error_log("Delete school error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to delete school";
        }
    }
    
    private function createSchool($data, $adminId) {
        try {
            $schoolId = $this->schoolQuery->createSchool($data);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'SCHOOL_CREATED',
                "Created new school: {$data['school_name']} (ID: {$schoolId})",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $_SESSION['success'] = "School created successfully";
        } catch (Exception $e) {
            error_log("Create school error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to create school: " . $e->getMessage();
        }
    }
    
    private function updateSchool($schoolId, $data, $adminId) {
        try {
            $school = $this->schoolQuery->getSchoolById($schoolId);
            $this->schoolQuery->updateSchool($schoolId, $data);
            
            AdminSecurity::logSecurityEvent(
                $adminId,
                'SCHOOL_UPDATED',
                "Updated school: {$school['school_name']} (ID: {$schoolId})",
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            );
            
            $_SESSION['success'] = "School updated successfully";
        } catch (Exception $e) {
            error_log("Update school error: " . $e->getMessage());
            $_SESSION['error'] = "Failed to update school";
        }
    }
    
    private function getSchoolStats() {
        try {
            return [
                'total' => $this->getTotalSchools(),
                'by_division' => $this->schoolQuery->getSchoolCountByDivision(),
                'recent_updates' => $this->getRecentUpdates()
            ];
        } catch (Exception $e) {
            error_log("School stats error: " . $e->getMessage());
            return ['total' => 0, 'by_division' => [], 'recent_updates' => []];
        }
    }
    
    private function getTotalSchools() {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM schools");
        $stmt->execute();
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    private function getRecentUpdates($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT id, school_name, updated_at 
            FROM schools 
            ORDER BY updated_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getDivisions() {
        $stmt = $this->db->prepare("
            SELECT DISTINCT division_office 
            FROM schools 
            WHERE division_office IS NOT NULL 
            ORDER BY division_office
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

$adminSchools = new AdminSchools($db, $adminAuth);
$adminSchools->index();