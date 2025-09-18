<?php

// Include the admin permissions view
?>
<?php require_once __DIR__ . '/../../partials/head.php'; ?>

<body class="bg-gray-50">
    <?php require_once __DIR__ . '/../../partials/nav.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Permission Management</h1>
                    <p class="mt-2 text-gray-600">Review and manage school edit permission requests</p>
                </div>
                <div class="flex space-x-3">
                    <a href="/admin/security" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        Security Logs
                    </a>
                    <a href="/listing" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        All Schools
                    </a>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50  text-red-800' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5">
                        <p class="text-sm font-medium text-gray-500">Total (30d)</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= $stats['total'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5">
                        <p class="text-sm font-medium text-gray-500">Pending</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= $stats['pending'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5">
                        <p class="text-sm font-medium text-gray-500">Active Now</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= $stats['active'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-600 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5">
                        <p class="text-sm font-medium text-gray-500">Approved</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= $stats['total_approved'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5">
                        <p class="text-sm font-medium text-gray-500">Denied</p>
                        <p class="text-2xl font-semibold text-gray-900"><?= $stats['total_denied'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Pending Requests -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        Pending Requests 
                        <?php if (count($pendingRequests) > 0): ?>
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <?= count($pendingRequests) ?>
                            </span>
                        <?php endif; ?>
                    </h2>
                </div>
                
                <?php if (empty($pendingRequests)): ?>
                    <div class="px-6 py-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No pending requests</h3>
                        <p class="mt-1 text-sm text-gray-500">All permission requests have been processed.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($pendingRequests as $request): ?>
                            <div class="px-6 py-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <!-- User Info -->
                                        <div class="flex items-center mb-3">
                                            <div class="flex-shrink-0">
                                                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-medium">
                                                    <?= substr($request['user_name'], 0, 1) ?>
                                                </div>
                                            </div>
                                            <div class="ml-3 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    <?= htmlspecialchars($request['user_name']) ?>
                                                </p>
                                                <p class="text-sm text-gray-600 truncate">
                                                    <?= htmlspecialchars($request['user_email']) ?>
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <!-- Request Details -->
                                        <div class="mb-4">
                                            <h4 class="text-lg font-medium text-gray-900 mb-1">
                                                <?= htmlspecialchars($request['school_name']) ?>
                                            </h4>
                                            <p class="text-sm text-gray-600 mb-2">
                                                Requested: <?= date('M j, Y g:i A', strtotime($request['requested_at'])) ?>
                                            </p>
                                            
                                            <?php if ($request['reason']): ?>
                                                <div class="bg-gray-50 rounded-lg p-3">
                                                    <p class="text-sm font-medium text-gray-700 mb-1">Reason:</p>
                                                    <p class="text-sm text-gray-900"><?= htmlspecialchars($request['reason']) ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex-shrink-0 ml-6">
                                        <div class="flex space-x-2">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="permission_id" value="<?= $request['id'] ?>">
                                                <button type="submit" 
                                                        onclick="return confirm('Grant 24-hour edit permission to <?= htmlspecialchars($request['user_name']) ?>?')"
                                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                    Approve
                                                </button>
                                            </form>
                                            
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="action" value="deny">
                                                <input type="hidden" name="permission_id" value="<?= $request['id'] ?>">
                                                <button type="submit" 
                                                        onclick="return confirm('Deny this permission request?')"
                                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                    <svg class="h-4 w-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                    Deny
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Recent Activity</h2>
                </div>
                <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
                    <?php if (empty($recentActivity)): ?>
                        <div class="px-6 py-8 text-center">
                            <p class="text-sm text-gray-500">No recent activity</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <?php if ($activity['status'] === 'approved'): ?>
                                            <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                                        <?php else: ?>
                                            <div class="w-2 h-2 bg-red-400 rounded-full"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-3 min-w-0 flex-1">
                                        <p class="text-sm text-gray-900">
                                            <span class="font-medium"><?= htmlspecialchars($activity['user_name']) ?></span>
                                            <?= $activity['status'] === 'approved' ? 'approved' : 'denied' ?> for
                                            <span class="font-medium"><?= htmlspecialchars($activity['school_name']) ?></span>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            by <?= htmlspecialchars($activity['admin_name']) ?> • 
                                            <?= date('M j, g:i A', strtotime($activity['updated_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>