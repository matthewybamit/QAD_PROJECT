<?php
// models/SchoolEditPermissions.php
class SchoolEditPermissions {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->cleanupExpiredPermissions();
    }
    
    /**
     * Request edit permission for a school
     */
    public function requestEditPermission($userId, $schoolId, $reason) {
        try {
            $this->cleanupExpiredPermissions();

            // Check if user already has an active request
            $stmt = $this->pdo->prepare("
                SELECT * FROM school_edit_permissions 
                WHERE user_id = ? AND school_id = ? 
                AND status IN ('pending', 'approved')
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->execute([$userId, $schoolId]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'You already have an active request for this school.'];
            }
            
            // Create new permission request
            $stmt = $this->pdo->prepare("
                INSERT INTO school_edit_permissions (user_id, school_id, reason, status, requested_at) 
                VALUES (?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$userId, $schoolId, $reason]);
            
            $this->logActivity($userId, 'permission_requested', "Requested edit permission for school ID: $schoolId");
            
            return ['success' => true, 'message' => 'Permission request submitted successfully. Admin will review shortly.'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error occurred.'];
        }
    }

    /**
     * Cancel request
     */
    public function cancelRequest($requestId, $userId) {
        $this->cleanupExpiredPermissions();

        $stmt = $this->pdo->prepare("
            SELECT id, status FROM school_edit_permissions
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$requestId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception("Request not found.");
        }

        // Allow cancellation if pending, expired, or returned
        if (!in_array($row['status'], ['pending', 'expired', 'returned'])) {
            throw new Exception("Only pending, expired, or returned requests can be cancelled.");
        }

        $deleteStmt = $this->pdo->prepare("DELETE FROM school_edit_permissions WHERE id = ?");
        $deleteStmt->execute([$requestId]);

        $this->logActivity($userId, 'permission_cancelled', "Cancelled request ID: $requestId");

        return true;
    }

    /**
     * Resubmit a returned request with updated reason
     */
    public function resubmitRequest($requestId, $userId, $updatedReason) {
        try {
            $this->cleanupExpiredPermissions();

            // Verify request exists and is in returned status
            $stmt = $this->pdo->prepare("
                SELECT * FROM school_edit_permissions
                WHERE id = ? AND user_id = ? AND status = 'returned'
            ");
            $stmt->execute([$requestId, $userId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                throw new Exception("Request not found or cannot be resubmitted.");
            }

            // Update request back to pending with new reason
            $stmt = $this->pdo->prepare("
                UPDATE school_edit_permissions 
                SET status = 'pending', 
                    reason = ?, 
                    admin_remarks = NULL,
                    requested_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$updatedReason, $requestId]);

            $this->logActivity($userId, 'permission_resubmitted', "Resubmitted request ID: $requestId");

            return ['success' => true, 'message' => 'Request resubmitted successfully.'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Approve permission (admin only) - 24 hour access
     */
    public function approvePermission($permissionId, $adminId) {
        try {
            $this->cleanupExpiredPermissions();

            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $stmt = $this->pdo->prepare("
                UPDATE school_edit_permissions 
                SET status = 'approved', 
                    approved_at = NOW(), 
                    expires_at = ?, 
                    approved_by = ?,
                    admin_remarks = NULL
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$expiresAt, $adminId, $permissionId]);
            
            if ($stmt->rowCount() > 0) {
                $stmt = $this->pdo->prepare("SELECT user_id, school_id FROM school_edit_permissions WHERE id = ?");
                $stmt->execute([$permissionId]);
                $request = $stmt->fetch();
                
                $this->logActivity($adminId, 'permission_approved', "Approved edit permission for user {$request['user_id']}, school {$request['school_id']}");
                return true;
            }
            return false;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Return permission with remarks (admin only)
     */
    public function returnPermission($permissionId, $adminId, $remarks) {
        try {
            $this->cleanupExpiredPermissions();

            if (empty($remarks)) {
                throw new Exception("Remarks are required when returning a request.");
            }

            $stmt = $this->pdo->prepare("
                UPDATE school_edit_permissions 
                SET status = 'returned', 
                    approved_by = ?,
                    admin_remarks = ?,
                    approved_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$adminId, $remarks, $permissionId]);
            
            if ($stmt->rowCount() > 0) {
                $this->logActivity($adminId, 'permission_returned', "Returned permission request ID: $permissionId with remarks");
                return true;
            }
            return false;
            
        } catch (Exception $e) {
            error_log("Return permission error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deny permission (admin only) - Keep for permanent rejection
     */
    public function denyPermission($permissionId, $adminId, $remarks = null) {
        try {
            $this->cleanupExpiredPermissions();

            $stmt = $this->pdo->prepare("
                UPDATE school_edit_permissions 
                SET status = 'denied', 
                    approved_by = ?,
                    admin_remarks = ?
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$adminId, $remarks, $permissionId]);
            
            if ($stmt->rowCount() > 0) {
                $this->logActivity($adminId, 'permission_denied', "Denied permission request ID: $permissionId");
                return true;
            }
            return false;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Check if user can edit specific school
     */
    public function canUserEditSchool($userId, $schoolId) {
        try {
            $this->cleanupExpiredPermissions();
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM school_edit_permissions 
                WHERE user_id = ? AND school_id = ? 
                AND status = 'approved' AND expires_at > NOW()
            ");
            $stmt->execute([$userId, $schoolId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get pending requests (admin only)
     */
    public function getPendingRequests() {
        try {
            $this->cleanupExpiredPermissions();

            $stmt = $this->pdo->prepare("
                SELECT sep.*, u.name as user_name, u.email as user_email, s.school_name 
                FROM school_edit_permissions sep
                JOIN users u ON sep.user_id = u.id
                JOIN schools s ON sep.school_id = s.id
                WHERE sep.status = 'pending'
                ORDER BY sep.requested_at ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get user's permission history
     */
    public function getUserPermissions($userId) {
        try {
            $this->cleanupExpiredPermissions();

            $stmt = $this->pdo->prepare("
                SELECT sep.*, s.school_name
                FROM school_edit_permissions sep
                JOIN schools s ON sep.school_id = s.id
                WHERE sep.user_id = ?
                ORDER BY sep.requested_at DESC
                LIMIT 10
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Clean up expired permissions
     */
    public function cleanupExpiredPermissions() {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE school_edit_permissions 
                SET status = 'expired' 
                WHERE status = 'approved' AND expires_at <= NOW()
            ");
            $stmt->execute();
        } catch (PDOException $e) {
            error_log('Failed to cleanup expired permissions: ' . $e->getMessage());
        }
    }
    
    /**
     * Log security-related activities
     */
    private function logActivity($userId, $action, $details) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $action,
                $details,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (PDOException $e) {
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
}
?>