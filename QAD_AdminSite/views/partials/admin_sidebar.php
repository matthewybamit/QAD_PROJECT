<?php
// views/partials/admin_sidebar.php
// Fixed sidebar: stays on the left, scrolls internally if content is long.
// Hidden on small screens (mobile) — shows from md and up. Adjust as needed.
?>
<div id="sidebar" class="fixed top-16 left-0 w-64 bg-gray-800 text-white z-40 overflow-y-auto h-[calc(100vh-4rem)]">
    <div class="p-4">
        <nav class="mt-8">
            <div class="space-y-2">
                <a href="/dashboard" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors <?= ($currentPage === 'dashboard') ? 'bg-gray-700 text-white' : '' ?>">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    <span>Dashboard</span>
                </a>

                <a href="/permissions" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors <?= ($currentPage === 'permissions') ? 'bg-gray-700 text-white' : '' ?>">
                    <i class="fas fa-key mr-3"></i>
                    <span>Permissions</span>
                    <?php if (isset($stats['pending_permissions']) && $stats['pending_permissions'] > 0): ?>
                        <span class="ml-auto bg-blue-500 text-white text-xs rounded-full px-2 py-1">
                            <?= $stats['pending_permissions'] ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- <a href="/users" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors <?= ($currentPage === 'users') ? 'bg-gray-700 text-white' : '' ?>">
                    <i class="fas fa-users mr-3"></i>
                    <span>Users</span>
                </a> -->

                <a href="/schools" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors <?= ($currentPage === 'schools') ? 'bg-gray-700 text-white' : '' ?>">
                    <i class="fas fa-school mr-3"></i>
                    <span>Schools</span>
                </a>

                <a href="/security" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors <?= ($currentPage === 'security') ? 'bg-gray-700 text-white' : '' ?>">
                    <i class="fas fa-shield-alt mr-3"></i>
                    <span>Security</span>
                    <?php if (isset($stats['security_incidents']) && $stats['security_incidents'] > 0): ?>
                        <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1 security-alert">
                            <?= $stats['security_incidents'] ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- <a href="/logs" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700 hover:text-white rounded-md transition-colors <?= ($currentPage === 'logs') ? 'bg-gray-700 text-white' : '' ?>">
                    <i class="fas fa-file-alt mr-3"></i>
                    <span>Audit Logs</span>
                </a> -->
            </div>
        </nav>
    </div>
</div>


  <div class="md:ml-64 flex flex-col min-h-screen">
      <!-- Optional topbar (keeps inside flow) -->
      <header class="bg-white shadow-sm sticky top-0 z-40">

          </div>
      </header>