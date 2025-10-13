<?php
$pageTitle = 'Users Management';
$currentPage = 'users';
require_once 'partials/admin_head.php';
?>

<body class="bg-gray-100">
    <?php require_once 'partials/admin_nav.php'; ?>
    
    <div class="flex">
        <?php require_once 'partials/admin_sidebar.php'; ?>
        
        <main class="flex-1 p-8">
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
            
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Users Management</h1>
                <p class="text-gray-600 mt-2">Manage user accounts and permissions</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fa-solid fa-users text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-semibold text-gray-900"><?= $stats['total'] ?></h3>
                            <p class="text-sm text-gray-500">Total Users</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fa-solid fa-check-circle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-semibold text-gray-900"><?= $stats['active'] ?></h3>
                            <p class="text-sm text-gray-500">Active</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fa-solid fa-ban text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-semibold text-gray-900"><?= $stats['suspended'] ?></h3>
                            <p class="text-sm text-gray-500">Suspended</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fa-solid fa-key text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-semibold text-gray-900"><?= $stats['with_permissions'] ?></h3>
                            <p class="text-sm text-gray-500">With Permissions</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fa-solid fa-user-plus text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-semibold text-gray-900"><?= $stats['new_this_month'] ?></h3>
                            <p class="text-sm text-gray-500">New This Month</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <input type="text" name="search" placeholder="Search users by name or email..." 
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="all" <?= ($_GET['status'] ?? '') === 'all' ? 'selected' : '' ?>>All Status</option>
                                <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="suspended" <?= ($_GET['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fa-solid fa-filter mr-2"></i>Filter
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requests</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active Permissions</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fa-solid fa-users text-4xl text-gray-400 mb-4"></i>
                                        <p>No users found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <?php if ($user['avatar']): ?>
                                                    <img class="w-10 h-10 rounded-full mr-3" src="<?= htmlspecialchars($user['avatar']) ?>" alt="">
                                                <?php else: ?>
                                                    <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm mr-3">
                                                        <?= substr($user['name'], 0, 1) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['name']) ?></div>
                                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($user['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= ucfirst($user['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?= (int)$user['total_requests'] ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if ((int)$user['active_permissions'] > 0): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?= (int)$user['active_permissions'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400">0</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?= date('M j, Y', strtotime($user['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <button onclick='viewUser(<?= json_encode($user) ?>)' 
                                                        class="text-blue-600 hover:text-blue-800" title="View Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Suspend this user? They will lose all active permissions.')">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                        <input type="hidden" name="action" value="suspend">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="text-orange-600 hover:text-orange-800" title="Suspend">
                                                            <i class="fa-solid fa-ban"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                        <input type="hidden" name="action" value="activate">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="text-green-600 hover:text-green-800" title="Activate">
                                                            <i class="fa-solid fa-check-circle"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if ((int)$user['active_permissions'] > 0): ?>
                                                    <form method="POST" class="inline" onsubmit="return confirm('Revoke all permissions for this user?')">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                        <input type="hidden" name="action" value="revoke_permissions">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="text-purple-600 hover:text-purple-800" title="Revoke Permissions">
                                                            <i class="fa-solid fa-key"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" class="inline" onsubmit="return confirm('Delete this user permanently? This cannot be undone.')">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Page <span class="font-medium"><?= $page ?></span> of <span class="font-medium"><?= $totalPages ?></span>
                                    (<?= $totalRecords ?> total users)
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <a href="?page=<?= $i ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?= $i === $page ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- User Details Modal -->
    <div id="userDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">User Details</h3>
                        <button onclick="closeUserDetailsModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    <div id="userDetailsContent"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function viewUser(user) {
        let html = `
            <div class="space-y-4">
                <div class="flex items-center mb-4">
                    ${user.avatar ? 
                        `<img class="w-20 h-20 rounded-full mr-4" src="${user.avatar}" alt="">` :
                        `<div class="w-20 h-20 rounded-full bg-blue-600 flex items-center justify-center text-white text-3xl mr-4">
                            ${user.name.charAt(0)}
                        </div>`
                    }
                    <div>
                        <h4 class="text-xl font-semibold text-gray-900">${user.name}</h4>
                        <p class="text-gray-600">${user.email}</p>
                    </div>
                </div>
                
                <div class="border-t pt-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700">Status:</label>
                            <p class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                    ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
                                </span>
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Member Since:</label>
                            <p class="text-gray-900 mt-1">${new Date(user.created_at).toLocaleDateString()}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Total Requests:</label>
                            <p class="text-gray-900 mt-1">${parseInt(user.total_requests) || 0}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700">Active Permissions:</label>
                            <p class="text-gray-900 mt-1">
                                ${parseInt(user.active_permissions) > 0 ? 
                                    `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        ${parseInt(user.active_permissions)}
                                    </span>` :
                                    '<span class="text-gray-400">None</span>'
                                }
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-4">
                    <h5 class="text-sm font-medium text-gray-700 mb-2">Quick Actions:</h5>
                    <div class="flex flex-wrap gap-2">
                        <a href="/admin/permissions?search=${encodeURIComponent(user.email)}" 
                           class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fa-solid fa-key mr-2"></i>View Permissions
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        document.getElementById('userDetailsContent').innerHTML = html;
        document.getElementById('userDetailsModal').classList.remove('hidden');
    }
    
    function closeUserDetailsModal() {
        document.getElementById('userDetailsModal').classList.add('hidden');
    }
    
    document.getElementById('userDetailsModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeUserDetailsModal();
    });
    </script>
</body>
</html>