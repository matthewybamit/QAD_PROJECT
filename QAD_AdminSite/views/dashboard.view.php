<?php
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
require_once 'partials/admin_head.php';
?>

<body class="bg-gray-100">
    <?php require_once 'partials/admin_nav.php'; ?>
    
    <div class="flex">
        <?php require_once 'partials/admin_sidebar.php'; ?>
        
        <main class="flex-1 p-8">
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <!-- Dashboard Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                <p class="text-gray-600 mt-2">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>. Here's what's happening.</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?= $stats['total_users'] ?? 0 ?></h3>
                            <p class="text-sm text-gray-500">Total Users</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-school text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?= $stats['total_schools'] ?? 0 ?></h3>
                            <p class="text-sm text-gray-500">Schools</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?= $stats['pending_permissions'] ?? 0 ?></h3>
                            <p class="text-sm text-gray-500">Pending Permissions</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-user-shield text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?= $stats['active_sessions'] ?? 0 ?></h3>
                            <p class="text-sm text-gray-500">Active Sessions</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-exclamation-triangle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900"><?= $stats['security_incidents'] ?? 0 ?></h3>
                            <p class="text-sm text-gray-500">Security Incidents</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Activity -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Recent Activity</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recentActivity)): ?>
                            <p class="text-gray-500 text-center py-4">No recent activity</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach (array_slice($recentActivity, 0, 8) as $activity): ?>
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <?php
                                            $iconClass = match($activity['action']) {
                                                'ADMIN_LOGIN' => 'text-green-500 fas fa-sign-in-alt',
                                                'ADMIN_LOGOUT' => 'text-gray-500 fas fa-sign-out-alt',
                                                'PERMISSION_APPROVED' => 'text-green-500 fas fa-check',
                                                'PERMISSION_DENIED' => 'text-red-500 fas fa-times',
                                                'FAILED_LOGIN' => 'text-red-500 fas fa-exclamation-triangle',
                                                'IP_BLOCKED' => 'text-red-500 fas fa-ban',
                                                default => 'text-blue-500 fas fa-info-circle'
                                            };
                                            ?>
                                            <i class="<?= $iconClass ?>"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-900">
                                                <?= htmlspecialchars($activity['details']) ?>
                                            </p>
                                            <div class="flex items-center space-x-2 text-xs text-gray-500 mt-1">
                                                <span><?= $activity['user_name'] ? htmlspecialchars($activity['user_name']) : 'System' ?></span>
                                                <span>•</span>
                                                <span><?= $activity['ip_address'] ?></span>
                                                <span>•</span>
                                                <span><?= date('M j, g:i A', strtotime($activity['created_at'])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Pending Permissions -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-semibold text-gray-900">Pending Permissions</h2>
                            <a href="/admin/permissions" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All</a>
                        </div>
                    </div>
                    <div class="p-6">
                        <?php if (empty($pendingPermissions)): ?>
                            <p class="text-gray-500 text-center py-4">No pending permissions</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($pendingPermissions as $permission): ?>
                                    <div class="border rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h3 class="font-medium text-gray-900"><?= htmlspecialchars($permission['user_name']) ?></h3>
                                                <p class="text-sm text-gray-600"><?= htmlspecialchars($permission['school_name']) ?></p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    Requested: <?= date('M j, g:i A', strtotime($permission['requested_at'])) ?>
                                                </p>
                                            </div>
                                            <div class="flex space-x-2">
                                                <form method="POST" action="/admin/permissions" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= AdminSecurity::generateCSRFToken() ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="permission_id" value="<?= $permission['id'] ?>">
                                                    <button type="submit" class="text-green-600 hover:text-green-800 text-sm">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="/admin/permissions" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= AdminSecurity::generateCSRFToken() ?>">
                                                    <input type="hidden" name="action" value="deny">
                                                    <input type="hidden" name="permission_id" value="<?= $permission['id'] ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <?php if ($permission['reason']): ?>
                                            <p class="text-sm text-gray-700 mt-2 bg-gray-50 p-2 rounded">
                                                <?= htmlspecialchars($permission['reason']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="/admin/assets/js/admin.js"></script>
</body>
</html>
