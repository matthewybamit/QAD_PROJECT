<?php

// views/security.view.php
$pageTitle = 'Security';
$currentPage = 'security';
require_once 'partials/admin_head.php';
?>
<body class="bg-gray-100">
<?php require_once 'partials/admin_nav.php'; ?>
<div class="flex">
    <?php require_once 'partials/admin_sidebar.php'; ?>

    <main class="flex-1 p-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Security Management</h1>

        <!-- Flash Messages -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- IP Whitelist -->
        <section class="mb-10">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fa-solid fa-shield-halved mr-2 text-blue-600"></i> IP Whitelist
            </h2>
            <form method="POST" class="flex space-x-2 mb-4">
                <input type="hidden" name="csrf_token" value="<?= AdminSecurity::generateCSRFToken() ?>">
                <input type="hidden" name="action" value="add_ip">
                <input type="text" name="ip_address" placeholder="IP Address" class="border rounded px-3 py-2 flex-1" required>
                <input type="text" name="description" placeholder="Description" class="border rounded px-3 py-2 flex-1">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add</button>
            </form>

            <?php if (!empty($ipWhitelist)): ?>
                <table class="min-w-full bg-white shadow rounded">
                    <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="px-4 py-2 text-left">IP Address</th>
                        <th class="px-4 py-2 text-left">Description</th>
                        <th class="px-4 py-2 text-left">Added By</th>
                        <th class="px-4 py-2 text-left">Created At</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ipWhitelist as $ip): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2"><?= htmlspecialchars($ip['ip_address']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($ip['description']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($ip['created_by_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($ip['created_at']) ?></td>
                            <td class="px-4 py-2 text-right">
                                <form method="POST" onsubmit="return confirm('Remove this IP?')">
                                    <input type="hidden" name="csrf_token" value="<?= AdminSecurity::generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="remove_ip">
                                    <input type="hidden" name="ip_id" value="<?= $ip['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-500">No IPs whitelisted yet.</p>
            <?php endif; ?>
        </section>

        <!-- Active Sessions -->
        <section class="mb-10">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fa-solid fa-user-lock mr-2 text-purple-600"></i> Active Sessions
            </h2>
            <?php if (!empty($activeSessions)): ?>
                <table class="min-w-full bg-white shadow rounded">
                    <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="px-4 py-2 text-left">User</th>
                        <th class="px-4 py-2 text-left">IP Address</th>
                        <th class="px-4 py-2 text-left">Last Activity</th>
                        <th class="px-4 py-2 text-left">Expires At</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($activeSessions as $s): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2"><?= htmlspecialchars($s['user_name']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($s['ip_address']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($s['last_activity']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($s['expires_at']) ?></td>
                            <td class="px-4 py-2 text-right">
                                <form method="POST" onsubmit="return confirm('Terminate this session?')">
                                    <input type="hidden" name="csrf_token" value="<?= AdminSecurity::generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="terminate_session">
                                    <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-ban"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-500">No active sessions.</p>
            <?php endif; ?>
        </section>

        <!-- Suspicious Activity -->
        <section class="mb-10">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fa-solid fa-triangle-exclamation mr-2 text-yellow-600"></i> Suspicious Activity (last 24h)
            </h2>
            <?php if (!empty($suspiciousActivity)): ?>
                <table class="min-w-full bg-white shadow rounded">
                    <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="px-4 py-2 text-left">IP Address</th>
                        <th class="px-4 py-2 text-left">Incident Count</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                        <th class="px-4 py-2 text-left">Last Incident</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($suspiciousActivity as $sa): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2"><?= htmlspecialchars($sa['ip_address']) ?></td>
                            <td class="px-4 py-2"><?= (int)$sa['incident_count'] ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($sa['actions']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($sa['last_incident']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-gray-500">No suspicious activity detected.</p>
            <?php endif; ?>
        </section>

        <!-- Security Logs -->
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fa-solid fa-clipboard-list mr-2 text-gray-600"></i> Security Logs
            </h2>
            <?php if (!empty($securityLogs)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white shadow rounded">
                        <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="px-4 py-2 text-left">Time</th>
                            <th class="px-4 py-2 text-left">User</th>
                            <th class="px-4 py-2 text-left">Action</th>
                            <th class="px-4 py-2 text-left">Details</th>
                            <th class="px-4 py-2 text-left">IP</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($securityLogs as $log): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2"><?= htmlspecialchars($log['created_at']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($log['action']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($log['details']) ?></td>
                                <td class="px-4 py-2"><?= htmlspecialchars($log['ip_address']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500">No logs available.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

<script src="/assets/js/security.js"></script>

</body>
</html>
