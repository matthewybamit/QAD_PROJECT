<?php
$pageTitle = 'Schools Management';
$currentPage = 'schools';
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
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Schools Management</h1>
                        <p class="text-gray-600 mt-2">Manage school profiles and information</p>
                    </div>
                    <button onclick="openCreateModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fa-solid fa-plus mr-2"></i>Add New School
                    </button>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fa-solid fa-school text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-semibold text-gray-900"><?= $stats['total'] ?></h3>
                            <p class="text-sm text-gray-500">Total Schools</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fa-solid fa-map-marker-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-semibold text-gray-900"><?= count($stats['by_division']) ?></h3>
                            <p class="text-sm text-gray-500">Division Offices</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fa-solid fa-clock text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-2xl font-semibold text-gray-900"><?= count($stats['recent_updates']) ?></h3>
                            <p class="text-sm text-gray-500">Recent Updates</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <input type="text" name="search" placeholder="Search schools..." 
                                   value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <select name="sort" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="school_name" <?= ($_GET['sort'] ?? '') === 'school_name' ? 'selected' : '' ?>>Sort: Name</option>
                                <option value="division_office" <?= ($_GET['sort'] ?? '') === 'division_office' ? 'selected' : '' ?>>Sort: Division</option>
                                <option value="updated_at" <?= ($_GET['sort'] ?? '') === 'updated_at' ? 'selected' : '' ?>>Sort: Updated</option>
                                <option value="created_at" <?= ($_GET['sort'] ?? '') === 'created_at' ? 'selected' : '' ?>>Sort: Created</option>
                            </select>
                        </div>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fa-solid fa-filter mr-2"></i>Filter
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Schools Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">School Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Division</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Program</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($schools)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fa-solid fa-school text-4xl text-gray-400 mb-4"></i>
                                        <p>No schools found</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($schools as $school): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <?php if ($school['school_logo']): ?>
                                                   <img   src="/../website/assets/logos/<?= htmlspecialchars(string: $school['school_logo']) ?>" 

                                                         class="w-10 h-10 rounded object-contain mr-3" alt="Logo">
                                                <?php endif; ?>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($school['school_name']) ?></div>
                                                    <?php if ($school['permit_no']): ?>
                                                        <div class="text-xs text-gray-500">Permit: <?= htmlspecialchars($school['permit_no']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($school['division_office']) ?></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?= htmlspecialchars($school['program_offering']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate"><?= htmlspecialchars($school['address']) ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?= $school['updated_at'] ? date('M j, Y', strtotime($school['updated_at'])) : '-' ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <button onclick='viewSchool(<?= json_encode($school) ?>)' 
                                                        class="text-blue-600 hover:text-blue-800" title="View">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                <button onclick='editSchool(<?= json_encode($school) ?>)' 
                                                        class="text-green-600 hover:text-green-800" title="Edit">
                                                    <i class="fa-solid fa-edit"></i>
                                                </button>
                                                <form method="POST" class="inline" onsubmit="return confirm('Delete this school? This action cannot be undone.')">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="school_id" value="<?= $school['id'] ?>">
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
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?= $page + 1 ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>" 
                                   class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing page <span class="font-medium"><?= $page ?></span> of <span class="font-medium"><?= $totalPages ?></span>
                                    (<?= $totalRecords ?> total schools)
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
    
    <!-- View School Modal -->
    <div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">School Details</h3>
                        <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fa-solid fa-times"></i>
                        </button>
                    </div>
                    <div id="schoolDetails"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create/Edit School Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4" id="editModalTitle">Add School</h3>
                    <form method="POST" id="schoolForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="school_id" id="schoolId">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">School Name *</label>
                                <input type="text" name="school_name" id="schoolName" required 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Division Office *</label>
                                <input type="text" name="division_office" id="divisionOffice" required 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Program Offering *</label>
                                <select name="program_offering" id="programOffering" required 
                                        class="w-full border border-gray-300 rounded-md px-3 py-2">
                                    <option value="">Select program</option>
                                    <option value="KINDERGARTEN">Kindergarten</option>
                                    <option value="ELEMENTARY">Elementary</option>
                                    <option value="JUNIOR HIGH SCHOOL">Junior High School</option>
                                    <option value="SENIOR HIGH SCHOOL">Senior High School</option>
                                    <option value="UNIVERSITY">University</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                                <textarea name="address" id="address" rows="2" required 
                                          class="w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Permit Number</label>
                                <input type="text" name="permit_no" id="permitNo" 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                                <input type="text" name="contact_person" id="contactPerson" 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                                <input type="tel" name="contact_phone" id="contactPhone" 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                                <input type="email" name="contact_email" id="contactEmail" 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" onclick="closeEditModal()" 
                                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Save School
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function openCreateModal() {
        document.getElementById('editModalTitle').textContent = 'Add New School';
        document.getElementById('formAction').value = 'create';
        document.getElementById('schoolForm').reset();
        document.getElementById('editModal').classList.remove('hidden');
    }
    
    function editSchool(school) {
        document.getElementById('editModalTitle').textContent = 'Edit School';
        document.getElementById('formAction').value = 'update';
        document.getElementById('schoolId').value = school.id;
        document.getElementById('schoolName').value = school.school_name || '';
        document.getElementById('divisionOffice').value = school.division_office || '';
        document.getElementById('programOffering').value = school.program_offering || '';
        document.getElementById('address').value = school.address || '';
        document.getElementById('permitNo').value = school.permit_no || '';
        document.getElementById('contactPerson').value = school.contact_person || '';
        document.getElementById('contactPhone').value = school.contact_phone || '';
        document.getElementById('contactEmail').value = school.contact_email || '';
        document.getElementById('editModal').classList.remove('hidden');
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
    
    function viewSchool(school) {
        let html = `
            <div class="space-y-4">
                ${school.school_logo ? `<div class="flex justify-center mb-4"><img src="/assets/logos/${school.school_logo}" class="w-24 h-24 object-contain" alt="Logo"></div>` : ''}
                <div><label class="text-sm font-medium text-gray-700">School Name:</label><p class="text-gray-900">${school.school_name}</p></div>
                <div><label class="text-sm font-medium text-gray-700">Division Office:</label><p class="text-gray-900">${school.division_office}</p></div>
                <div><label class="text-sm font-medium text-gray-700">Program:</label><p class="text-gray-900">${school.program_offering}</p></div>
                <div><label class="text-sm font-medium text-gray-700">Address:</label><p class="text-gray-900">${school.address}</p></div>
                ${school.permit_no ? `<div><label class="text-sm font-medium text-gray-700">Permit:</label><p class="text-gray-900">${school.permit_no}</p></div>` : ''}
                ${school.contact_person ? `<div><label class="text-sm font-medium text-gray-700">Contact Person:</label><p class="text-gray-900">${school.contact_person}</p></div>` : ''}
                ${school.contact_phone ? `<div><label class="text-sm font-medium text-gray-700">Phone:</label><p class="text-gray-900">${school.contact_phone}</p></div>` : ''}
                ${school.contact_email ? `<div><label class="text-sm font-medium text-gray-700">Email:</label><p class="text-gray-900">${school.contact_email}</p></div>` : ''}
                ${school.school_description ? `<div><label class="text-sm font-medium text-gray-700">Description:</label><p class="text-gray-900">${school.school_description}</p></div>` : ''}
            </div>
        `;
        document.getElementById('schoolDetails').innerHTML = html;
        document.getElementById('viewModal').classList.remove('hidden');
    }
    
    function closeViewModal() {
        document.getElementById('viewModal').classList.add('hidden');
    }
    
    // Close modals on outside click
    ['editModal', 'viewModal'].forEach(modalId => {
        document.getElementById(modalId)?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    });
    </script>
</body>
</html>