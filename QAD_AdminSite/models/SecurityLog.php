<?php
// models/SecurityLog.php

class SecurityLog {
    private $db;
    
    public function __construct($database) {
        $this->db = $database; // PDO connection
    }
    
    /**
     * Log a security event
     */
    public function log($userId, $action, $details, $ipAddress, $userAgent) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO security_logs (user_id, action, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $action, $details, $ipAddress, $userAgent]);
            
            return $this->db->lastInsertId();
            
        } catch (PDOException $e) {
            error_log("Security log error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent security logs
     */
    public function getRecentLogs($limit = 50, $userId = null) {
        try {
            $query = "
                SELECT sl.*, u.name as user_name, u.email as user_email
                FROM security_logs sl
                LEFT JOIN users u ON sl.user_id = u.id
            ";
            
            $params = [];
            if ($userId) {
                $query .= " WHERE sl.user_id = ?";
                $params[] = $userId;
            }
            
            $query .= " ORDER BY sl.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("getRecentLogs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get security alerts (suspicious activities)
     */
    public function getSecurityAlerts($hours = 24) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    action,
                    COUNT(*) as count,
                    ip_address,
                    MAX(created_at) as last_occurrence,
                    GROUP_CONCAT(DISTINCT user_id) as user_ids
                FROM security_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                AND action IN ('FAILED_LOGIN', 'IP_BLOCKED', 'CSRF_VIOLATION', 'RATE_LIMIT_EXCEEDED', 'SESSION_HIJACK_ATTEMPT')
                GROUP BY action, ip_address
                HAVING count > 3
                ORDER BY count DESC, last_occurrence DESC
                LIMIT 20
            ");
            $stmt->execute([$hours]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("getSecurityAlerts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get failed login attempts for a specific user
     */
    public function getFailedLoginAttempts($userId, $hours = 24) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM security_logs 
                WHERE user_id = ? 
                AND action = 'FAILED_LOGIN'
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            ");
            $stmt->execute([$userId, $hours]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
        } catch (PDOException $e) {
            error_log("getFailedLoginAttempts error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get suspicious IP addresses
     */
    public function getSuspiciousIPs($hours = 24, $threshold = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    ip_address,
                    COUNT(*) as incident_count,
                    MAX(created_at) as last_incident,
                    GROUP_CONCAT(DISTINCT action) as actions,
                    COUNT(DISTINCT user_id) as affected_users
                FROM security_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                AND action IN ('FAILED_LOGIN', 'IP_BLOCKED', 'CSRF_VIOLATION', 'RATE_LIMIT_EXCEEDED')
                GROUP BY ip_address
                HAVING incident_count >= ?
                ORDER BY incident_count DESC, last_incident DESC
            ");
            $stmt->execute([$hours, $threshold]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("getSuspiciousIPs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get login statistics
     */
    public function getLoginStats($days = 7) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(created_at) as date,
                    SUM(CASE WHEN action = 'ADMIN_LOGIN_SUCCESS' THEN 1 ELSE 0 END) as successful_logins,
                    SUM(CASE WHEN action = 'FAILED_LOGIN' THEN 1 ELSE 0 END) as failed_logins
                FROM security_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND action IN ('ADMIN_LOGIN_SUCCESS', 'FAILED_LOGIN')
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute([$days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("getLoginStats error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get user activity summary
     */
    public function getUserActivity($userId, $days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    action,
                    COUNT(*) as count,
                    MAX(created_at) as last_occurrence
                FROM security_logs 
                WHERE user_id = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY action
                ORDER BY count DESC, last_occurrence DESC
            ");
            $stmt->execute([$userId, $days]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("getUserActivity error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean old log entries
     */
    public function cleanOldLogs($days = 90) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM security_logs 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$days]);
            
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("cleanOldLogs error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get security event statistics
     */
    public function getEventStatistics($hours = 24) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    action,
                    COUNT(*) as count
                FROM security_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                GROUP BY action
                ORDER BY count DESC
            ");
            $stmt->execute([$hours]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("getEventStatistics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search security logs
     */
    public function searchLogs($search = '', $action = '', $startDate = '', $endDate = '', $limit = 100) {
        try {
            $query = "
                SELECT sl.*, u.name as user_name, u.email as user_email
                FROM security_logs sl
                LEFT JOIN users u ON sl.user_id = u.id
                WHERE 1=1
            ";
            $params = [];
            
            if (!empty($search)) {
                $query .= " AND (sl.details LIKE ? OR sl.ip_address LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
                $searchTerm = "%{$search}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($action)) {
                $query .= " AND sl.action = ?";
                $params[] = $action;
            }
            
            if (!empty($startDate)) {
                $query .= " AND sl.created_at >= ?";
                $params[] = $startDate;
            }
            
            if (!empty($endDate)) {
                $query .= " AND sl.created_at <= ?";
                $params[] = $endDate;
            }
            
            $query .= " ORDER BY sl.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("searchLogs error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get unique actions list
     */
    public function getActions() {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT action 
                FROM security_logs 
                ORDER BY action ASC
            ");
            $stmt->execute();
            
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'action');
            
        } catch (PDOException $e) {
            error_log("getActions error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if IP address has recent suspicious activity
     */
    public function isIPSuspicious($ipAddress, $hours = 1, $threshold = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM security_logs 
                WHERE ip_address = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                AND action IN ('FAILED_LOGIN', 'CSRF_VIOLATION', 'RATE_LIMIT_EXCEEDED')
            ");
            $stmt->execute([$ipAddress, $hours]);
            
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            return $count >= $threshold;
            
        } catch (PDOException $e) {
            error_log("isIPSuspicious error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get geolocation data for IP addresses (if available)
     */
    public function getIPLocations($hours = 24) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT ip_address, COUNT(*) as frequency
                FROM security_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                AND ip_address IS NOT NULL
                AND ip_address != ''
                GROUP BY ip_address
                ORDER BY frequency DESC
                LIMIT 50
            ");
            $stmt->execute([$hours]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("getIPLocations error: " . $e->getMessage());
            return [];
        }
    }
}
?>