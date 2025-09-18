
<!-- admin/views/partials/admin_nav.php -->
<nav class="bg-gray-900 border-b border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-shield-alt text-blue-400 text-xl"></i>
                    <span class="ml-2 text-white font-semibold">Admin Panel</span>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <div class="relative">
                    <button class="text-gray-300 hover:text-white p-2" id="notificationBtn">
                        <i class="fas fa-bell"></i>
                        <?php if (isset($stats['security_incidents']) && $stats['security_incidents'] > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center security-alert">
                            <?= $stats['security_incidents'] ?>
                        </span>
                        <?php endif; ?>
                    </button>
                </div>
                
                <!-- User Menu -->
                <div class="relative">
                    <button class="flex items-center text-sm text-gray-300 hover:text-white" id="userMenuBtn">
                        <span><?= htmlspecialchars($currentUser['name']) ?></span>
                        <i class="fas fa-chevron-down ml-2"></i>
                    </button>
                    <div id="userMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                        <a href="/admin/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                        <a href="/admin/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                        <hr class="my-1">
                        <a href="/admin/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>