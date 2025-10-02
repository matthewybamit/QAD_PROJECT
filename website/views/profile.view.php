<?php require_once 'partials/head.php'; ?>

<body class="bg-gray-50">
    <?php require_once 'partials/nav.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="max-w-4xl mx-auto">
            
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                <div class="bg-blue-900 h-32"></div>
                <div class="px-6 py-6 -mt-16">
                    <div class="flex items-center">
                        <?php if ($currentUser['avatar']): ?>
                            <img class="w-24 h-24 rounded-full border-4 border-white object-cover" src="<?= htmlspecialchars($currentUser['avatar']) ?>" alt="Profile">
                        <?php else: ?>
                            <div class="w-24 h-24 rounded-full bg-blue-600 flex items-center justify-center text-white text-3xl font-medium border-4 border-white">
                                <?= substr($currentUser['name'], 0, 1) ?>
                            </div>
                        <?php endif; ?>
                        <div class="ml-6 mb-6">
                            <h1 class="text-2xl font-bold text-white"><?= htmlspecialchars($currentUser['name']) ?></h1>
                            <p class="text-gray-600"><?= htmlspecialchars($currentUser['email']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $activeRequestsCount = 0;
            $returnedRequests = [];
            if (!empty($userPermissions) && is_array($userPermissions)) {
                foreach ($userPermissions as $p) {
                    if (isset($p['status']) && $p['status'] === 'pending') {
                        $activeRequestsCount++;
                    }
                    if (isset($p['status']) && $p['status'] === 'approved' && !empty($p['expires_at']) && strtotime($p['expires_at']) > time()) {
                        $activeRequestsCount++;
                    }
                    if (isset($p['status']) && $p['status'] === 'returned') {
                        $returnedRequests[] = $p;
                    }
                }
            }
            $requestLimitReached = ($activeRequestsCount >= 3);
            ?>

            <?php if (!empty($returnedRequests)): ?>
                <div class="mb-6 bg-orange-50 border-l-4 border-orange-500 p-4 rounded-lg shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-orange-800">
                                <?= count($returnedRequests) ?> Request<?= count($returnedRequests) > 1 ? 's' : '' ?> Returned
                            </h3>
                            <p class="mt-1 text-sm text-orange-700">Review admin feedback below and resubmit with corrections.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-6">Profile Information</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <div class="mt-1 p-3 bg-gray-50 rounded-md"><?= htmlspecialchars($currentUser['name']) ?></div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <div class="mt-1 p-3 bg-gray-50 rounded-md"><?= htmlspecialchars($currentUser['email']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-6">Schools You Can Edit</h2>
                        <?php if (empty($editableSchools)): ?>
                            <div class="text-center py-4">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">No edit permissions currently.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($editableSchools as $school): ?>
                                    <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                                        <div class="flex-1">
                                            <h3 class="font-medium text-gray-900"><?= htmlspecialchars($school['school_name']) ?></h3>
                                            <p class="text-sm text-gray-600">
                                                Expires: <?= date('M j, g:i A', strtotime($school['expires_at'])) ?>
                                            </p>
                                        </div>
                                        <a href="/school/<?= $school['id'] ?>" class="ml-4 px-3 py-1.5 text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700">
                                            Edit
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mt-6 bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Request School Edit Permission</h2>
                    <?php if ($requestLimitReached): ?>
                        <p class="text-sm text-red-600 font-medium">Maximum 3 active requests reached. Cancel one to free a slot.</p>
                    <?php else: ?>
                        <form method="POST" action="/request-permission" class="space-y-4">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Select School</label>
                                <select name="school_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Choose a school...</option>
                                    <?php foreach ($allSchools as $school): ?>
                                        <option value="<?= $school['id'] ?>"><?= htmlspecialchars($school['school_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Reason for Request</label>
                                <textarea name="reason" rows="4" required minlength="10" maxlength="500" 
                                         class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
                                         placeholder="Provide detailed reason (minimum 10 characters)..."></textarea>
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                Submit Request
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($userPermissions)): ?>
                <div class="mt-6 bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-6">Recent Permission Requests</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">School</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                                        <th class="px-6 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($userPermissions as $permission): ?>
                                        <tr>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($permission['school_name']) ?></div>
                                                <?php if (!empty($permission['reason'])): ?>
                                                    <div class="text-sm text-gray-500 truncate max-w-xs"><?= htmlspecialchars(substr($permission['reason'], 0, 80)) ?><?= strlen($permission['reason']) > 80 ? '...' : '' ?></div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($permission['admin_remarks']) && $permission['status'] === 'returned'): ?>
                                                    <div class="mt-2 p-3 bg-orange-50 border-l-4 border-orange-400 rounded">
                                                        <p class="text-xs font-semibold text-orange-800 mb-1">
                                                            <svg class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Admin Feedback:
                                                        </p>
                                                        <p class="text-sm text-orange-900"><?= htmlspecialchars($permission['admin_remarks']) ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $statusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'returned' => 'bg-orange-100 text-orange-800',
                                                    'approved' => 'bg-green-100 text-green-800',
                                                    'denied' => 'bg-red-100 text-red-800',
                                                    'expired' => 'bg-gray-100 text-gray-800'
                                                ];
                                                $status = $permission['status'] ?? 'pending';
                                                $badgeClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                                ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $badgeClass ?>">
                                                    <?= ucfirst($status) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= date('M j, Y g:i A', strtotime($permission['requested_at'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?= !empty($permission['expires_at']) ? date('M j, g:i A', strtotime($permission['expires_at'])) : '-' ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <?php if ($permission['status'] === 'returned'): ?>
                                                    <button onclick="openResubmitModal(<?= (int)$permission['id'] ?>, '<?= htmlspecialchars($permission['school_name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($permission['reason'], ENT_QUOTES) ?>')" 
                                                            class="text-orange-600 hover:text-orange-800 mr-3">
                                                        Resubmit
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if (in_array($permission['status'] ?? '', ['pending', 'expired', 'returned'])): ?>
                                                    <form method="POST" action="" onsubmit="return confirm('Cancel this request?');" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                                        <input type="hidden" name="cancel_request_id" value="<?= (int)$permission['id'] ?>">
                                                        <button type="submit" name="cancel_request" class="text-red-600 hover:text-red-800">Cancel</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Resubmit Modal -->
    <div id="resubmitModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Resubmit Request</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Review admin feedback and update your request for <strong id="resubmitSchoolName"></strong>.
                    </p>
                    <form method="POST" action="/profile" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="request_id" id="resubmitRequestId">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Updated Reason</label>
                            <textarea name="updated_reason" id="resubmitReason" rows="5" required minlength="10" maxlength="500"
                                     class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                     placeholder="Address admin concerns and provide additional details..."></textarea>
                            <p class="mt-1 text-xs text-gray-500">Address all points mentioned in admin feedback.</p>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeResubmitModal()" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Resubmit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openResubmitModal(requestId, schoolName, currentReason) {
        document.getElementById('resubmitRequestId').value = requestId;
        document.getElementById('resubmitSchoolName').textContent = schoolName;
        document.getElementById('resubmitReason').value = currentReason;
        document.getElementById('resubmitModal').classList.remove('hidden');
    }

    function closeResubmitModal() {
        document.getElementById('resubmitModal').classList.add('hidden');
    }

    document.getElementById('resubmitModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeResubmitModal();
    });
    </script>
</body>
</html>