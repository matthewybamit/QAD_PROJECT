<!-- views/partials/admin_nav.php -->
<nav class="fixed top-0 left-0 right-0 z-50 bg-gray-900 border-b border-gray-700 h-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-shield-alt text-blue-400 text-xl"></i>
                    <span class="ml-2 text-white font-semibold">Admin Panel</span>
                </div>
            </div>

            <!-- Center: Date/Time -->
            <div id="dateTime" class="hidden md:flex items-center text-gray-300 font-mono text-sm"></div>

            <div class="flex items-center space-x-4">
                <!-- Notifications Dropdown -->
                <div class="relative">
                    <button class="text-gray-300 hover:text-white p-2 relative" id="notificationBtn">
                        <i class="fas fa-bell text-lg"></i>
                        <?php 
                        $totalNotifications = 0;
                        if (isset($stats['security_incidents'])) $totalNotifications += (int)$stats['security_incidents'];
                        if (isset($stats['pending_permissions'])) $totalNotifications += (int)$stats['pending_permissions'];
                        ?>
                        <?php if ($totalNotifications > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center animate-pulse">
                            <?= $totalNotifications > 9 ? '9+' : $totalNotifications ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Notifications Dropdown Menu -->
                    <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                        <div class="p-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            <?php if (isset($stats['security_incidents']) && $stats['security_incidents'] > 0): ?>
                                <a href="/admin/security" class="block p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-exclamation-triangle text-red-500 text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <p class="text-sm font-medium text-gray-900">Security Incidents</p>
                                            <p class="text-sm text-gray-500"><?= $stats['security_incidents'] ?> security incident<?= $stats['security_incidents'] > 1 ? 's' : '' ?> detected today</p>
                                            <p class="text-xs text-blue-600 hover:text-blue-800 mt-1">View Details →</p>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                            
                            <?php if (isset($stats['pending_permissions']) && $stats['pending_permissions'] > 0): ?>
                                <a href="/admin/permissions" class="block p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-key text-blue-500 text-sm"></i>
                                            </div>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <p class="text-sm font-medium text-gray-900">Pending Permissions</p>
                                            <p class="text-sm text-gray-500"><?= $stats['pending_permissions'] ?> permission request<?= $stats['pending_permissions'] > 1 ? 's' : '' ?> awaiting approval</p>
                                            <p class="text-xs text-blue-600 hover:text-blue-800 mt-1">Review Requests →</p>
                                        </div>
                                    </div>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($totalNotifications === 0): ?>
                                <div class="p-8 text-center">
                                    <i class="fas fa-check-circle text-green-500 text-3xl mb-3"></i>
                                    <p class="text-gray-500">All caught up!</p>
                                    <p class="text-sm text-gray-400">No new notifications</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($totalNotifications > 0): ?>
                        <div class="p-3 border-t border-gray-200 bg-gray-50">
                            <button onclick="markAllAsRead()" class="text-sm text-blue-600 hover:text-blue-800 w-full text-center">
                                Mark all as read
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="relative">
                    <button class="flex items-center text-sm text-gray-300 hover:text-white focus:outline-none focus:text-white transition-colors" id="userMenuBtn">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center mr-2">
                            <span class="text-white text-sm font-medium">
                                <?= strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)) ?>
                            </span>
                        </div>
                        <span class="hidden md:block"><?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?></span>
                        <i class="fas fa-chevron-down ml-2 text-xs"></i>
                    </button>
                    
                    <!-- User Dropdown Menu -->
                    <div id="userMenu" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?></p>
                            <p class="text-sm text-gray-500 truncate"><?= htmlspecialchars($currentUser['email'] ?? '') ?></p>
                        </div>
                        
                        <div class="py-1">
                            <a href="/admin/profile" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                <i class="fas fa-user mr-3 text-gray-400 w-4"></i>
                                My Profile
                            </a>
                            <a href="/admin/settings" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                <i class="fas fa-cog mr-3 text-gray-400 w-4"></i>
                                Settings
                            </a>
                            <a href="/admin/security" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                                <i class="fas fa-history mr-3 text-gray-400 w-4"></i>
                                Activity Log
                            </a>
                        </div>
                        
                        <div class="border-t border-gray-200 py-1">
                            <form method="POST" action="/admin/logout" class="inline w-full">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <i class="fas fa-sign-out-alt mr-3 text-red-500 w-4"></i>
                                    Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>


    <!-- Page Header (optional shadowed header) -->
    <header class="bg-white shadow-sm sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-xl font-semibold text-gray-900">
                <?= $pageTitle ?? 'Dashboard' ?>
            </h1>
        </div>
    </header>

<script src="/assets/js/notification.js"></script>
<script src="/assets/js/datetime.js"></script>
