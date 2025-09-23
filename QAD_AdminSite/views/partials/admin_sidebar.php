
<!-- views/partials/admin_sidebar.php -->
<div class="bg-gray-800 text-white w-64 min-h-screen sidebar-transition" id="sidebar">
    <div class="p-4">
        <nav class="mt-8">
            <div class="space-y-2">
                <a href="/dashboard" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors <?= $currentPage === 'dashboard' ? 'bg-gray-700 text-white' : '' ?>">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
                
                <a href="/permissions" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors <?= $currentPage === 'permissions' ? 'bg-gray-700 text-white' : '' ?>">
                    <i class="fas fa-key mr-3"></i>
                    Permissions
                    <?php if (isset($stats['pending_permissions']) && $stats['pending_permissions'] > 0): ?>
                    <span class="ml-auto bg-blue-500 text-white text-xs rounded-full px-2 py-1">
                        <?= $stats['pending_permissions'] ?>
                    </span>
                    <?php endif; ?>
                </a>
                
                <a href="/users" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors <?= $currentPage === 'users' ? 'bg-gray-700 text-white' : '' ?>">
                    <i class="fas fa-users mr-3"></i>
                    Users
                </a>
                
                <a href="/schools" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors <?= $currentPage === 'schools' ? 'bg-gray-700 text-white' : '' ?>">
                    <i class="fas fa-school mr-3"></i>
                    Schools
                </a>
                
                <a href="/security" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors <?= $currentPage === 'security' ? 'bg-gray-700 text-white' : '' ?>">
                    <i class="fas fa-shield-alt mr-3"></i>
                    Security
                    <?php if (isset($stats['security_incidents']) && $stats['security_incidents'] > 0): ?>
                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1 security-alert">
                        <?= $stats['security_incidents'] ?>
                    </span>
                    <?php endif; ?>
                </a>
                
                <a href="/logs" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors <?= $currentPage === 'logs' ? 'bg-gray-700 text-white' : '' ?>">
                    <i class="fas fa-file-alt mr-3"></i>
                    Audit Logs
                </a>
            </div>
        </nav>
    </div>
</div>