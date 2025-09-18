<?php
class Permission {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Check if user has valid edit permission for school
     */
    public function canEditSchool($userId, $schoolId) {
        $stmt = $this->db->prepare("
            SELECT * 
            FROM school_edit_permissions 
            WHERE user_id = ? 
              AND school_id = ? 
              AND status = 'approved'
              AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$userId, $schoolId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
