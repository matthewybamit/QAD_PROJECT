<?php
// views/security.view.php
$pageTitle = 'Security Management';
$currentPage = 'security';
require_once 'partials/admin_head.php';
?>
<body class="bg-gray-100">
<?php require_once 'partials/admin_nav.php'; ?>
<div class="flex">
    <?php require_once 'partials/admin_sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Security Management</h1>
                <div class="flex gap-2">
                    <button onclick="refreshStats()" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                        <i class="fa-solid fa-rotate mr-2"></i>Refresh
                    </button>
                    <form method="POST" class="inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="export_logs">
                        <input type="hidden" name="format" value="csv">
                        <input type="hidden" name="days" value="7">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            <i class="fa-solid fa-download mr-2"></i>Export Logs
                        </button>
                    </form>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded flex items-center">
                    <i class="fa-solid fa-check-circle mr-2"></i>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded flex items-center">
                    <i class="fa-solid fa-exclamation-triangle mr-2"></i>
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Security Statistics Dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Logins (24h)</p>
                            <p class="text-2xl font-bold text-green-600"><?= $securityStats['logins_24h'] ?? 0 ?></p>
                        </div>
                        <i class="fa-solid fa-user-check text-3xl text-green-600 opacity-20"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Failed Logins (24h)</p>
                            <p class="text-2xl font-bold text-red-600"><?= $securityStats['failed_logins_24h'] ?? 0 ?></p>
                        </div>
                        <i class="fa-solid fa-user-xmark text-3xl text-red-600 opacity-20"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">CSRF Violations (24h)</p>
                            <p class="text-2xl font-bold text-orange-600"><?= $securityStats['csrf_violations_24h'] ?? 0 ?></p>
                        </div>
                        <i class="fa-solid fa-shield-halved text-3xl text-orange-600 opacity-20"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Rate Limits (24h)</p>
                            <p class="text-2xl font-bold text-purple-600"><?= $securityStats['rate_limits_24h'] ?? 0 ?></p>
                        </div>
                        <i class="fa-solid fa-gauge-high text-3xl text-purple-600 opacity-20"></i>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Active Sessions</p>
                            <p class="text-2xl font-bold text-blue-600"><?= count($activeSessions) ?></p>
                        </div>
                        <i class="fa-solid fa-users text-3xl text-blue-600 opacity-20"></i>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button onclick="switchTab('overview')" class="tab-btn border-b-2 border-blue-600 py-4 px-1 text-sm font-medium text-blue-600">
                        <i class="fa-solid fa-chart-line mr-2"></i>Overview
                    </button>
                    <button onclick="switchTab('ip-management')" class="tab-btn border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fa-solid fa-network-wired mr-2"></i>IP Management
                    </button>
                    <button onclick="switchTab('sessions')" class="tab-btn border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fa-solid fa-user-lock mr-2"></i>Sessions
                    </button>
                    <button onclick="switchTab('accounts')" class="tab-btn border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fa-solid fa-users-gear mr-2"></i>Accounts
                    </button>
                    <button onclick="switchTab('logs')" class="tab-btn border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        <i class="fa-solid fa-clipboard-list mr-2"></i>Security Logs
                    </button>
                </nav>
            </div>

            <!-- Tab: Overview -->
            <div id="tab-overview" class="tab-content">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Suspicious Activity -->
                    <section class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fa-solid fa-triangle-exclamation mr-2 text-yellow-600"></i>
                                Suspicious Activity (24h)
                            </h2>
                        </div>
                        <div class="p-6 max-h-96 overflow-y-auto">
                            <?php if (!empty($suspiciousActivity)): ?>
                                <div class="space-y-3">
                                    <?php foreach (array_slice($suspiciousActivity, 0, 5) as $sa): ?>
                                        <div class="flex items-center justify-between p-3 bg-yellow-50 rounded border border-yellow-200">
                                            <div class="flex-1">
                                                <p class="font-semibold text-gray-900"><?= htmlspecialchars($sa['ip_address']) ?></p>
                                                <p class="text-sm text-gray-600"><?= (int)$sa['incident_count'] ?> incidents - <?= htmlspecialchars($sa['actions']) ?></p>
                                                <p class="text-xs text-gray-500"><?= htmlspecialchars($sa['last_incident']) ?></p>
                                            </div>
                                            <form method="POST" class="ml-4">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="action" value="add_ip_blacklist">
                                                <input type="hidden" name="ip_address" value="<?= htmlspecialchars($sa['ip_address']) ?>">
                                                <input type="hidden" name="reason" value="Suspicious activity detected">
                                                <button type="submit" class="text-red-600 hover:text-red-800" title="Block IP">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 text-center py-4">No suspicious activity detected</p>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Recent Blocks -->
                    <section class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fa-solid fa-ban mr-2 text-red-600"></i>
                                Recent Blocks (7 days)
                            </h2>
                        </div>
                        <div class="p-6 max-h-96 overflow-y-auto">
                            <?php if (!empty($recentBlocks)): ?>
                                <div class="space-y-2">
                                    <?php foreach (array_slice($recentBlocks, 0, 5) as $block): ?>
                                        <div class="p-3 bg-red-50 rounded border border-red-200">
                                            <div class="flex items-center justify-between">
                                                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded">
                                                    <?= htmlspecialchars($block['action']) ?>
                                                </span>
                                                <span class="text-xs text-gray-500"><?= htmlspecialchars($block['created_at']) ?></span>
                                            </div>
                                            <p class="text-sm text-gray-900 mt-2"><?= htmlspecialchars($block['ip_address']) ?></p>
                                            <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($block['details']) ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 text-center py-4">No recent blocks</p>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Rate Limit Stats -->
                    <section class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fa-solid fa-gauge-high mr-2 text-purple-600"></i>
                                Rate Limit Activity (1h)
                            </h2>
                        </div>
                        <div class="p-6 max-h-96 overflow-y-auto">
                            <?php if (!empty($rateLimitStats)): ?>
                                <div class="space-y-2">
                                    <?php foreach ($rateLimitStats as $stat): ?>
                                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded">
                                            <span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($stat['ip_address']) ?></span>
                                            <div class="flex items-center gap-3">
                                                <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded">
                                                    <?= (int)$stat['hits'] ?> hits
                                                </span>
                                                <span class="text-xs text-gray-500"><?= htmlspecialchars($stat['last_hit']) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 text-center py-4">No rate limit hits</p>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Locked Accounts -->
                    <section class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fa-solid fa-lock mr-2 text-red-600"></i>
                                Locked Accounts
                            </h2>
                        </div>
                        <div class="p-6 max-h-96 overflow-y-auto">
                            <?php if (!empty($lockedAccounts)): ?>
                                <div class="space-y-3">
                                    <?php foreach ($lockedAccounts as $account): ?>
                                        <div class="p-3 bg-red-50 rounded border border-red-200">
                                            <div class="flex items-center justify-between mb-2">
                                                <p class="font-semibold text-gray-900"><?= htmlspecialchars($account['name']) ?></p>
                                                <form method="POST" onsubmit="return confirm('Unlock this account?')">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                    <input type="hidden" name="action" value="unlock_account">
                                                    <input type="hidden" name="user_id" value="<?= $account['id'] ?>">
                                                    <button type="submit" class="text-green-600 hover:text-green-800" title="Unlock Account">
                                                        <i class="fa-solid fa-unlock"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <p class="text-sm text-gray-600"><?= htmlspecialchars($account['email']) ?></p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Locked until: <?= htmlspecialchars($account['locked_until']) ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 text-center py-4">No locked accounts</p>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Tab: IP Management -->
            <div id="tab-ip-management" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- IP Whitelist -->
                    <section class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fa-solid fa-shield-halved mr-2 text-green-600"></i>
                                IP Whitelist
                            </h2>
                        </div>
                        <div class="p-6">
                            <form method="POST" class="mb-4">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="add_ip_whitelist">
                                <div class="flex gap-2">
                                    <input type="text" name="ip_address" placeholder="IP Address" 
                                           class="border rounded px-3 py-2 flex-1" required>
                                    <input type="text" name="description" placeholder="Description" 
                                           class="border rounded px-3 py-2 flex-1">
                                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </form>

                            <?php if (!empty($ipWhitelist)): ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full">
                                        <thead>
                                        <tr class="border-b">
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">IP</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Description</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($ipWhitelist as $ip): ?>
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="px-4 py-2 text-sm"><?= htmlspecialchars($ip['ip_address']) ?></td>
                                                <td class="px-4 py-2 text-sm"><?= htmlspecialchars($ip['description']) ?></td>
                                                <td class="px-4 py-2 text-right">
                                                    <form method="POST" onsubmit="return confirm('Remove this IP?')">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                        <input type="hidden" name="action" value="remove_ip_whitelist">
                                                        <input type="hidden" name="ip_id" value="<?= $ip['id'] ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-800">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 text-center py-4">No IPs whitelisted</p>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- IP Blacklist -->
                    <section class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fa-solid fa-ban mr-2 text-red-600"></i>
                                IP Blacklist
                            </h2>
                        </div>
                        <div class="p-6">
                            <form method="POST" class="mb-4">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="add_ip_blacklist">
                                <div class="flex gap-2">
                                    <input type="text" name="ip_address" placeholder="IP Address" 
                                           class="border rounded px-3 py-2 flex-1" required>
                                    <input type="text" name="reason" placeholder="Reason" 
                                           class="border rounded px-3 py-2 flex-1">
                                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                                        <i class="fa-solid fa-plus"></i>
                                    </button>
                                </div>
                            </form>

                            <?php if (!empty($ipBlacklist)): ?>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full">
                                        <thead>
                                        <tr class="border-b">
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">IP</th>
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Reason</th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($ipBlacklist as $ip): ?>
                                            <tr class="border-b hover:bg-gray-50">
                                                <td class="px-4 py-2 text-sm"><?= htmlspecialchars($ip['ip_address']) ?></td>
                                                <td class="px-4 py-2 text-sm"><?= htmlspecialchars($ip['reason']) ?></td>
                                                <td class="px-4 py-2 text-right">
                                                    <form method="POST" onsubmit="return confirm('Unblock this IP?')">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                        <input type="hidden" name="action" value="remove_ip_blacklist">
                                                        <input type="hidden" name="ip_id" value="<?= $ip['id'] ?>">
                                                        <button type="submit" class="text-green-600 hover:text-green-800">
                                                            <i class="fa-solid fa-unlock"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 text-center py-4">No IPs blacklisted</p>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Tab: Sessions -->
            <div id="tab-sessions" class="tab-content hidden">
                <section class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fa-solid fa-user-lock mr-2 text-purple-600"></i>
                            Active Sessions
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($activeSessions)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                    <tr class="bg-gray-50 border-b">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">User</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">IP Address</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Last Activity</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Expires</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">User Agent</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($activeSessions as $s): ?>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <div>
                                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($s['user_name']) ?></p>
                                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($s['email']) ?></p>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($s['ip_address']) ?></td>
                                            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($s['last_activity']) ?></td>
                                            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($s['expires_at']) ?></td>
                                            <td class="px-4 py-3 text-xs text-gray-600 max-w-xs truncate"><?= htmlspecialchars($s['user_agent']) ?></td>
                                            <td class="px-4 py-3 text-right">
                                                <form method="POST" onsubmit="return confirm('Terminate this session?')">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                    <input type="hidden" name="action" value="terminate_session">
                                                    <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Terminate Session">
                                                        <i class="fa-solid fa-ban"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-8">No active sessions</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- Tab: Accounts -->
            <div id="tab-accounts" class="tab-content hidden">
                <section class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fa-solid fa-users-gear mr-2 text-orange-600"></i>
                            Failed Login Attempts
                        </h2>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($failedLoginAttempts)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                    <tr class="bg-gray-50 border-b">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">User</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Attempts</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Last Attempt</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Status</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($failedLoginAttempts as $attempt): ?>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <div>
                                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($attempt['name']) ?></p>
                                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($attempt['email']) ?></p>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded">
                                                    <?= (int)$attempt['failed_attempts'] ?> attempts
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($attempt['last_attempt']) ?></td>
                                            <td class="px-4 py-3">
                                                <?php if (!empty($attempt['locked_until']) && strtotime($attempt['locked_until']) > time()): ?>
                                                    <span class="px-2 py-1 bg-red-600 text-white text-xs font-medium rounded">
                                                        LOCKED
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded">
                                                        Active
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <div class="flex gap-2 justify-end">
                                                    <form method="POST" onsubmit="return confirm('Clear failed attempts?')">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                        <input type="hidden" name="action" value="clear_failed_attempts">
                                                        <input type="hidden" name="user_id" value="<?= $attempt['admin_user_id'] ?>">
                                                        <button type="submit" class="text-blue-600 hover:text-blue-800" title="Clear Attempts">
                                                            <i class="fa-solid fa-eraser"></i>
                                                        </button>
                                                    </form>
                                                    <?php if (!empty($attempt['locked_until']) && strtotime($attempt['locked_until']) > time()): ?>
                                                        <form method="POST" onsubmit="return confirm('Unlock this account?')">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                            <input type="hidden" name="action" value="unlock_account">
                                                            <input type="hidden" name="user_id" value="<?= $attempt['admin_user_id'] ?>">
                                                            <button type="submit" class="text-green-600 hover:text-green-800" title="Unlock Account">
                                                                <i class="fa-solid fa-unlock"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-8">No failed login attempts</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- Tab: Logs -->
            <div id="tab-logs" class="tab-content hidden">
                <section class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                            <i class="fa-solid fa-clipboard-list mr-2 text-gray-600"></i>
                            Security Logs (Last 50)
                        </h2>
                        <div class="flex gap-2">
                            <select id="logFilter" class="border rounded px-3 py-1 text-sm" onchange="filterLogs()">
                                <option value="">All Actions</option>
                                <option value="LOGIN">Logins</option>
                                <option value="LOGOUT">Logouts</option>
                                <option value="FAILED">Failed Attempts</option>
                                <option value="IP">IP Actions</option>
                                <option value="SESSION">Sessions</option>
                            </select>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($securityLogs)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full" id="logsTable">
                                    <thead>
                                    <tr class="bg-gray-50 border-b">
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Time</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">User</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Action</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Details</th>
                                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">IP</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($securityLogs as $log): ?>
                                        <tr class="border-b hover:bg-gray-50 log-row" data-action="<?= htmlspecialchars($log['action']) ?>">
                                            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($log['created_at']) ?></td>
                                            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                                            <td class="px-4 py-3">
                                                <?php
                                                $actionClass = 'bg-gray-100 text-gray-800';
                                                if (strpos($log['action'], 'SUCCESS') !== false) $actionClass = 'bg-green-100 text-green-800';
                                                if (strpos($log['action'], 'FAILED') !== false) $actionClass = 'bg-red-100 text-red-800';
                                                if (strpos($log['action'], 'BLOCKED') !== false || strpos($log['action'], 'LOCKED') !== false) $actionClass = 'bg-red-100 text-red-800';
                                                if (strpos($log['action'], 'VIOLATION') !== false) $actionClass = 'bg-orange-100 text-orange-800';
                                                ?>
                                                <span class="px-2 py-1 <?= $actionClass ?> text-xs font-medium rounded">
                                                    <?= htmlspecialchars($log['action']) ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($log['details']) ?></td>
                                            <td class="px-4 py-3 text-sm"><?= htmlspecialchars($log['ip_address']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-8">No logs available</p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>

<script>
// Tab switching
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Remove active state from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-blue-600', 'text-blue-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.remove('hidden');
    
    // Set active state on clicked button
    event.target.closest('.tab-btn').classList.remove('border-transparent', 'text-gray-500');
    event.target.closest('.tab-btn').classList.add('border-blue-600', 'text-blue-600');
}

// Log filtering
function filterLogs() {
    const filter = document.getElementById('logFilter').value;
    const rows = document.querySelectorAll('.log-row');
    
    rows.forEach(row => {
        const action = row.getAttribute('data-action');
        if (!filter || action.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Refresh stats
function refreshStats() {
    location.reload();
}

// Auto-refresh every 30 seconds (optional)
// setInterval(refreshStats, 30000);
</script>

</body>
</html>