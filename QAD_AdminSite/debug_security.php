<?php
// debug_security.php - Run this to see what's actually in your database
// Place this in your admin folder and access via browser

require_once __DIR__ . '/config/admin_db.php';

echo "<html><head><style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
h2 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th { background: #4CAF50; color: white; padding: 10px; text-align: left; }
td { padding: 8px; border-bottom: 1px solid #ddd; }
tr:hover { background: #f5f5f5; }
.count { font-size: 24px; font-weight: bold; color: #4CAF50; }
.empty { color: #999; font-style: italic; }
.error { color: #f44336; background: #ffebee; padding: 10px; border-radius: 4px; }
</style></head><body>";

echo "<h1>🔍 Security System Debug Report</h1>";

try {
    // 1. Check admin_security_logs actions
    echo "<div class='section'>";
    echo "<h2>1. Security Log Actions (Last 24 hours)</h2>";
    $stmt = $db->prepare("
        SELECT action, COUNT(*) as count 
        FROM admin_security_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY action 
        ORDER BY count DESC
    ");
    $stmt->execute();
    $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($actions)) {
        echo "<p class='empty'>No security logs in the last 24 hours</p>";
    } else {
        echo "<table><tr><th>Action</th><th>Count</th></tr>";
        foreach ($actions as $action) {
            echo "<tr><td>{$action['action']}</td><td class='count'>{$action['count']}</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // 2. Check ALL actions ever logged
    echo "<div class='section'>";
    echo "<h2>2. All Unique Actions (Ever Logged)</h2>";
    $stmt = $db->prepare("
        SELECT DISTINCT action, COUNT(*) as total_count
        FROM admin_security_logs 
        GROUP BY action 
        ORDER BY total_count DESC
    ");
    $stmt->execute();
    $allActions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allActions)) {
        echo "<p class='empty'>No security logs found</p>";
    } else {
        echo "<table><tr><th>Action</th><th>Total Count</th></tr>";
        foreach ($allActions as $action) {
            echo "<tr><td>{$action['action']}</td><td>{$action['total_count']}</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // 3. Check suspicious activity raw query
    echo "<div class='section'>";
    echo "<h2>3. Suspicious Activity (Raw Data)</h2>";
    $stmt = $db->prepare("
        SELECT 
            ip_address,
            COUNT(*) as incident_count,
            MAX(created_at) as last_incident,
            GROUP_CONCAT(DISTINCT action SEPARATOR ', ') as actions
        FROM admin_security_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND (
            action LIKE '%FAILED%' 
            OR action LIKE '%BLOCKED%' 
            OR action LIKE '%VIOLATION%' 
            OR action LIKE '%EXCEEDED%'
        )
        GROUP BY ip_address
        ORDER BY incident_count DESC
    ");
    $stmt->execute();
    $suspicious = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($suspicious)) {
        echo "<p class='empty'>No suspicious activity detected in last 24 hours</p>";
    } else {
        echo "<table><tr><th>IP</th><th>Incidents</th><th>Actions</th><th>Last Incident</th></tr>";
        foreach ($suspicious as $s) {
            echo "<tr>";
            echo "<td>{$s['ip_address']}</td>";
            echo "<td class='count'>{$s['incident_count']}</td>";
            echo "<td>{$s['actions']}</td>";
            echo "<td>{$s['last_incident']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // 4. Check active sessions
    echo "<div class='section'>";
    echo "<h2>4. Active Sessions</h2>";
    $stmt = $db->prepare("
        SELECT 
            asess.*, 
            au.name as user_name, 
            au.email,
            TIMESTAMPDIFF(MINUTE, asess.last_activity, NOW()) as minutes_idle
        FROM admin_sessions asess
        JOIN admin_users au ON asess.user_id = au.id
        WHERE asess.expires_at > NOW()
        ORDER BY asess.last_activity DESC
    ");
    $stmt->execute();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sessions)) {
        echo "<p class='empty'>No active sessions</p>";
    } else {
        echo "<table><tr><th>User</th><th>IP</th><th>Last Activity</th><th>Idle Time</th><th>Expires</th></tr>";
        foreach ($sessions as $s) {
            echo "<tr>";
            echo "<td>{$s['user_name']}<br><small>{$s['email']}</small></td>";
            echo "<td>{$s['ip_address']}</td>";
            echo "<td>{$s['last_activity']}</td>";
            echo "<td>{$s['minutes_idle']} min ago</td>";
            echo "<td>{$s['expires_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // 5. Check locked accounts
    echo "<div class='section'>";
    echo "<h2>5. Locked Accounts</h2>";
    $stmt = $db->prepare("
        SELECT 
            au.id,
            au.name,
            au.email,
            al.failed_attempts,
            al.locked_until,
            al.last_attempt,
            CASE 
                WHEN al.locked_until > NOW() THEN 'LOCKED'
                ELSE 'UNLOCKED'
            END as status
        FROM admin_lockouts al
        JOIN admin_users au ON au.id = al.admin_user_id
        WHERE al.failed_attempts > 0
        ORDER BY al.last_attempt DESC
    ");
    $stmt->execute();
    $lockouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($lockouts)) {
        echo "<p class='empty'>No failed login attempts recorded</p>";
    } else {
        echo "<table><tr><th>User</th><th>Failed Attempts</th><th>Status</th><th>Last Attempt</th><th>Locked Until</th></tr>";
        foreach ($lockouts as $l) {
            $statusColor = $l['status'] === 'LOCKED' ? 'red' : 'green';
            echo "<tr>";
            echo "<td>{$l['name']}<br><small>{$l['email']}</small></td>";
            echo "<td class='count'>{$l['failed_attempts']}</td>";
            echo "<td style='color: {$statusColor}; font-weight: bold;'>{$l['status']}</td>";
            echo "<td>{$l['last_attempt']}</td>";
            echo "<td>" . ($l['locked_until'] ?: 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // 6. Check IP whitelist/blacklist
    echo "<div class='section'>";
    echo "<h2>6. IP Whitelist</h2>";
    $stmt = $db->prepare("SELECT COUNT(*) FROM ip_whitelist");
    $stmt->execute();
    $whitelistCount = $stmt->fetchColumn();
    echo "<p class='count'>{$whitelistCount} IPs whitelisted</p>";
    
    if ($whitelistCount > 0) {
        $stmt = $db->prepare("
            SELECT iw.*, au.name as created_by_name 
            FROM ip_whitelist iw
            LEFT JOIN admin_users au ON iw.created_by = au.id
            ORDER BY iw.created_at DESC
        ");
        $stmt->execute();
        $whitelist = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table><tr><th>IP</th><th>Description</th><th>Created By</th><th>Created At</th></tr>";
        foreach ($whitelist as $ip) {
            echo "<tr>";
            echo "<td>{$ip['ip_address']}</td>";
            echo "<td>{$ip['description']}</td>";
            echo "<td>{$ip['created_by_name']}</td>";
            echo "<td>{$ip['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    echo "<div class='section'>";
    echo "<h2>7. IP Blacklist</h2>";
    $stmt = $db->prepare("SELECT COUNT(*) FROM ip_blacklist");
    $stmt->execute();
    $blacklistCount = $stmt->fetchColumn();
    echo "<p class='count'>{$blacklistCount} IPs blacklisted</p>";
    
    if ($blacklistCount > 0) {
        $stmt = $db->prepare("
            SELECT ib.*, au.name as blocked_by_name 
            FROM ip_blacklist ib
            LEFT JOIN admin_users au ON ib.blocked_by = au.id
            ORDER BY ib.blocked_at DESC
        ");
        $stmt->execute();
        $blacklist = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table><tr><th>IP</th><th>Reason</th><th>Blocked By</th><th>Blocked At</th></tr>";
        foreach ($blacklist as $ip) {
            echo "<tr>";
            echo "<td>{$ip['ip_address']}</td>";
            echo "<td>{$ip['reason']}</td>";
            echo "<td>" . ($ip['blocked_by_name'] ?? 'System') . "</td>";
            echo "<td>{$ip['blocked_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // 7. Recent logs sample
    echo "<div class='section'>";
    echo "<h2>8. Recent Security Logs (Last 10)</h2>";
    $stmt = $db->prepare("
        SELECT sl.*, au.name as user_name 
        FROM admin_security_logs sl
        LEFT JOIN admin_users au ON sl.admin_user_id = au.id
        ORDER BY sl.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recentLogs)) {
        echo "<p class='empty'>No logs found</p>";
    } else {
        echo "<table><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr>";
        foreach ($recentLogs as $log) {
            echo "<tr>";
            echo "<td>{$log['created_at']}</td>";
            echo "<td>" . ($log['user_name'] ?? 'System') . "</td>";
            echo "<td><strong>{$log['action']}</strong></td>";
            echo "<td>{$log['details']}</td>";
            echo "<td>{$log['ip_address']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // Summary
    echo "<div class='section'>";
    echo "<h2>📊 Summary</h2>";
    $stmt = $db->prepare("SELECT COUNT(*) FROM admin_security_logs");
    $stmt->execute();
    $totalLogs = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM admin_sessions WHERE expires_at > NOW()");
    $stmt->execute();
    $activeSessions = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM admin_lockouts WHERE failed_attempts > 0");
    $stmt->execute();
    $failedAttempts = $stmt->fetchColumn();
    
    echo "<ul>";
    echo "<li><strong>Total Security Logs:</strong> {$totalLogs}</li>";
    echo "<li><strong>Active Sessions:</strong> {$activeSessions}</li>";
    echo "<li><strong>Accounts with Failed Attempts:</strong> {$failedAttempts}</li>";
    echo "<li><strong>IP Whitelist:</strong> {$whitelistCount}</li>";
    echo "<li><strong>IP Blacklist:</strong> {$blacklistCount}</li>";
    echo "</ul>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='section'>";
echo "<p><em>Generated: " . date('Y-m-d H:i:s') . "</em></p>";
echo "<p><a href='security.php'>← Back to Security Management</a></p>";
echo "</div>";

echo "</body></html>";