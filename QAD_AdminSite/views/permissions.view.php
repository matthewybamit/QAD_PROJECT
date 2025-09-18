<!-- admin/views/permissions.php -->
<?php
$pageTitle = 'Permission Management';
$currentPage = 'permissions';
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
            
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Permission Management</h1>
                <p class="text-gray-600 mt-2">Manage school edit permissions and user access requests.</p>
            </div>
            
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6">
                    <form method="GET" class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input type="text" 
                                   name="search" 
                                   placeholder="Search by user name, email, or school..." 
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <select name="status" class="border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="all" <?= ($_GET['status'] ?? '') === 'all' ? 'selected' : '' ?>>All Status</option>
                                <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="denied" <?= ($_GET['status'] ?? '') === 'denied' ? 'selected' : '' ?>>Denied</option>
                                <option value="expired" <?= ($_GET['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Expired</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Permissions Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Permission Requests</h2>
                </div>
                
                <?php if (empty($permissions)): ?>
                    <div class="p-8 text-center">
                        <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No permission requests found.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">School</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($permissions as $permission): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <?php if ($permission['avatar']): ?>
                                                    <img class="w-8 h-8 rounded-full mr-3" src="<?= htmlspecialchars($permission['avatar']) ?>" alt="">
                                                <?php else: ?>
                                                    <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm mr-3">
                                                        <?= substr($permission['user_name'], 0, 1) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($permission['user_name']) ?></div>
                                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($permission['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($permission['school_name']) ?></div>
                                            <?php if ($permission['reason']): ?>
                                                <div class="text-sm text-gray-500 truncate max-w-xs" title="<?= htmlspecialchars($permission['reason']) ?>">
                                                    <?= htmlspecialchars(substr($permission['reason'], 0, 50)) ?>...
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'approved' => 'bg-green-100 text-green-800',
                                                'denied' => 'bg-red-100 text-red-800',
                                                'expired' => 'bg-gray-100 text-gray-800'
                                            ];
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColors[$permission['status']] ?>">
                                                <?= ucfirst($permission['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?= date('M j, Y g:i A', strtotime($permission['requested_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?= $permission['expires_at'] ? date('M j, g:i A', strtotime($permission['expires_at'])) : '-' ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <?php if ($permission['status'] === 'pending'): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?= AdminSecurity::generateCSRFToken() ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="permission_id" value="<?= $permission['id'] ?>">
                                                        <button type="submit" class="text-green-600 hover:text-green-800 text-sm" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?= AdminSecurity::generateCSRFToken() ?>">
                                                        <input type="hidden" name="action" value="deny">
                                                        <input type="hidden" name="permission_id" value="<?= $permission['id'] ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm" title="Deny">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php elseif ($permission['status'] === 'approved' && $permission['expires_at'] && strtotime($permission['expires_at']) > time()): ?>
                                                    <button class="text-blue-600 hover:text-blue-800 text-sm extend-btn" 
                                                            data-permission-id="<?= $permission['id'] ?>" title="Extend">
                                                        <i class="fas fa-clock"></i>
                                                    </button>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?= AdminSecurity::generateCSRFToken() ?>">
                                                        <input type="hidden" name="action" value="revoke">
                                                        <input type="hidden" name="permission_id" value="<?= $permission['id'] ?>">
                                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm" title="Revoke">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <button class="text-gray-600 hover:text-gray-800 text-sm view-details-btn" 
                                                        data-permission="<?= htmlspecialchars(json_encode($permission)) ?>" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- Extend Permission Modal -->
    <div id="extendModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Extend Permission</h3>
                    <form id="extendForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= AdminSecurity::generateCSRFToken() ?>">
                        <input type="hidden" name="action" value="extend">
                        <input type="hidden" name="permission_id" id="extendPermissionId">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Extend by (hours):</label>
                            <select name="extend_hours" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="1">1 hour</option>
                                <option value="6">6 hours</option>
                                <option value="12">12 hours</option>
                                <option value="24" selected>24 hours</option>
                                <option value="48">48 hours</option>
                            </select>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50" onclick="closeExtendModal()">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Extend
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Permission Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Permission Details</h3>
                        <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div id="permissionDetails"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/admin/assets/js/permissions.js"></script>
</body>
</html>