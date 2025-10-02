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
                <h1 class="text-3xl font-bold text-gray-900">Permission Management</h1>
                <p class="text-gray-600 mt-2">Manage school edit permissions and user access requests.</p>
            </div>
            
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6">
                    <form method="GET" class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input type="text" name="search" placeholder="Search by user name, email, or school..." 
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <select name="status" class="border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500">
                                <option value="all" <?= ($_GET['status'] ?? '') === 'all' ? 'selected' : '' ?>>All Status</option>
                                <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="returned" <?= ($_GET['status'] ?? '') === 'returned' ? 'selected' : '' ?>>Returned</option>
                                <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="denied" <?= ($_GET['status'] ?? '') === 'denied' ? 'selected' : '' ?>>Denied</option>
                                <option value="expired" <?= ($_GET['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Expired</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b">
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">School</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
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
                                                <div class="text-sm text-gray-500 truncate max-w-xs"><?= htmlspecialchars(substr($permission['reason'], 0, 50)) ?>...</div>
                                            <?php endif; ?>
                                            <?php if (!empty($permission['admin_remarks'])): ?>
                                                <div class="mt-1 text-xs text-orange-600 bg-orange-50 px-2 py-1 rounded inline-block">
                                                    <i class="fas fa-exclamation-circle mr-1"></i>Admin feedback provided
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'returned' => 'bg-orange-100 text-orange-800',
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
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="permission_id" value="<?= $permission['id'] ?>">
                                                        <button type="submit" class="text-green-600 hover:text-green-800 text-sm" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <button class="text-orange-600 hover:text-orange-800 text-sm return-btn" 
                                                            data-permission-id="<?= $permission['id'] ?>" 
                                                            data-user-name="<?= htmlspecialchars($permission['user_name']) ?>" title="Return for Revision">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                    <button class="text-red-600 hover:text-red-800 text-sm deny-btn" 
                                                            data-permission-id="<?= $permission['id'] ?>"
                                                            data-user-name="<?= htmlspecialchars($permission['user_name']) ?>" title="Permanently Deny">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php elseif ($permission['status'] === 'approved' && $permission['expires_at'] && strtotime($permission['expires_at']) > time()): ?>
                                                    <button class="text-blue-600 hover:text-blue-800 text-sm extend-btn" 
                                                            data-permission-id="<?= $permission['id'] ?>" title="Extend">
                                                        <i class="fas fa-clock"></i>
                                                    </button>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
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
    
    <!-- Return Modal -->
    <div id="returnModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Return Request for Revision</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Return to <strong id="returnUserName"></strong> with feedback for revision.
                    </p>
                    <form id="returnForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="return">
                        <input type="hidden" name="permission_id" id="returnPermissionId">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Remarks (Required):</label>
                            <textarea name="admin_remarks" rows="4" required
                                     class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-orange-500"
                                     placeholder="Explain what needs correction..."></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50" onclick="closeReturnModal()">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">Return</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Deny Modal -->
    <div id="denyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-red-900 mb-4">Permanently Deny</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Permanently deny <strong id="denyUserName"></strong>. They cannot resubmit.
                    </p>
                    <form id="denyForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="deny">
                        <input type="hidden" name="permission_id" id="denyPermissionId">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional):</label>
                            <textarea name="admin_remarks" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50" onclick="closeDenyModal()">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Deny</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Extend Modal -->
    <div id="extendModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Extend Permission</h3>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
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
                            <button type="button" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50" onclick="closeExtendModal()">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Extend</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Details Modal -->
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
    
    <script>
    document.querySelectorAll('.return-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('returnPermissionId').value = this.dataset.permissionId;
            document.getElementById('returnUserName').textContent = this.dataset.userName;
            document.getElementById('returnModal').classList.remove('hidden');
        });
    });
    function closeReturnModal() { document.getElementById('returnModal').classList.add('hidden'); }

    document.querySelectorAll('.deny-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('denyPermissionId').value = this.dataset.permissionId;
            document.getElementById('denyUserName').textContent = this.dataset.userName;
            document.getElementById('denyModal').classList.add('hidden');
        });
    });
    function closeDenyModal() { document.getElementById('denyModal').classList.add('hidden'); }

    document.querySelectorAll('.extend-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('extendPermissionId').value = this.dataset.permissionId;
            document.getElementById('extendModal').classList.remove('hidden');
        });
    });
    function closeExtendModal() { document.getElementById('extendModal').classList.add('hidden'); }

    document.querySelectorAll('.view-details-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const p = JSON.parse(this.dataset.permission);
            document.getElementById('permissionDetails').innerHTML = `
                <div class="space-y-4">
                    <div><label class="text-sm font-medium text-gray-700">User:</label><p>${p.user_name} (${p.email})</p></div>
                    <div><label class="text-sm font-medium text-gray-700">School:</label><p>${p.school_name}</p></div>
                    <div><label class="text-sm font-medium text-gray-700">Reason:</label><p class="bg-gray-50 p-3 rounded">${p.reason || 'None'}</p></div>
                    ${p.admin_remarks ? `<div class="bg-orange-50 border border-orange-200 rounded p-3"><label class="text-sm font-medium text-orange-800">Admin Feedback:</label><p class="text-orange-900 mt-1">${p.admin_remarks}</p></div>` : ''}
                    <div><label class="text-sm font-medium text-gray-700">Status:</label><p>${p.status.charAt(0).toUpperCase() + p.status.slice(1)}</p></div>
                    ${p.approved_by_name ? `<div><label class="text-sm font-medium text-gray-700">Processed By:</label><p>${p.approved_by_name}</p></div>` : ''}
                </div>
            `;
            document.getElementById('detailsModal').classList.remove('hidden');
        });
    });
    function closeDetailsModal() { document.getElementById('detailsModal').classList.add('hidden'); }
    </script>
</body>
</html>