<?php require_once 'partials/head.php'; ?>
<?php
require_once 'models/SchoolQuery.php';

// $schoolQuery = new SchoolQuery($db);
// $result = $schoolQuery->getSchools([
//     'search' => $_GET['search'] ?? '',
//     'limit'  => (int)($_GET['limit'] ?? 10),
//     'page'   => (int)($_GET['page'] ?? 1),
//     'sort'   => $_GET['sort'] ?? 'school_name',
//     'order'  => $_GET['order'] ?? 'asc'
// ]);

// $schools       = $result['schools'];
// $totalRecords  = $result['totalRecords'];
// $totalPages    = $result['totalPages'];
// $page          = (int)($_GET['page'] ?? 1);
// $limit         = (int)($_GET['limit'] ?? 10);
// $search        = trim($_GET['search'] ?? '');
// $sort          = $_GET['sort'] ?? 'school_name';
// $order         = $_GET['order'] ?? 'asc';
?>

<body class="bg-gray-50">
    <?php require_once 'partials/nav.php'; ?>

    <!-- Main Container with responsive padding -->
    <div class="px-4 sm:px-6 lg:px-8 pt-24 pb-6">
        <!-- Page Title - Responsive text size -->
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-800 mb-4 text-center sm:text-left">
            PRIVATE SCHOOLS WITH GOVERNMENT AUTHORITY AS SY 2024-2025
        </h1>

        <div class="text-lg sm:text-xl text-[#007AFF] font-bold mb-6">
            <?php echo "As of " . date('F j, Y'); ?>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <!-- Header Controls - Stack on mobile -->
            <div class="p-4 border-b border-gray-200 bg-gray-50 space-y-4 sm:space-y-0">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <!-- Entries per page -->
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <span>Show</span>
                        <select id="entriesPerPage" class="border border-gray-300 rounded px-2 py-1">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>entries</span>
                    </div>
                    
                    <!-- Search - Full width on mobile -->
                    <div class="flex items-center space-x-2 text-sm text-gray-600 w-full sm:w-auto">
                        <label class="whitespace-nowrap">Search:</label>
                        <input type="text" id="searchInput" 
                               class="border border-gray-300 rounded px-3 py-1 w-full sm:w-auto" 
                               placeholder="Search...">
                    </div>
                </div>
            </div>

            <!-- Table Container with horizontal scroll -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-r border-gray-200 cursor-pointer hover:bg-blue-500 transition-colors duration-150" data-column="division_office">
                                <div class="flex items-center space-x-1">
                                    <span>Division Office</span>
                                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 12l5-5 5 5H5z"/>
                                    </svg>
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-r border-gray-200 cursor-pointer hover:bg-blue-500 transition-colors duration-150" data-column="school_name">
                                <div class="flex items-center space-x-1">
                                    <span>Name of School</span>
                                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 12l5-5 5 5H5z"/>
                                    </svg>
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-r border-gray-200 cursor-pointer hover:bg-blue-500 transition-colors duration-150" data-column="address">
                                <div class="flex items-center space-x-1">
                                    <span>Address</span>
                                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 12l5-5 5 5H5z"/>
                                    </svg>
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-r border-gray-200 cursor-pointer hover:bg-blue-500 transition-colors duration-150" data-column="permit_no">
                                <div class="flex items-center space-x-1">
                                    <span>Permit No.</span>
                                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 12l5-5 5 5H5z"/>
                                    </svg>
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-r border-gray-200 cursor-pointer hover:bg-blue-500 transition-colors duration-150" data-column="program_offering">
                                <div class="flex items-center space-x-1">
                                    <span>Program Offering</span>
                                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 12l5-5 5 5H5z"/>
                                    </svg>
                                </div>
                            </th>
                              <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-r border-gray-200 cursor-pointer hover:bg-blue-500 transition-colors duration-150" data-column="program_offering">
                                Contact Details
                            </th>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-r border-gray-200 cursor-pointer hover:bg-blue-500 transition-colors duration-150" data-column="program_offering">
                                <div class="flex items-center space-x-1">
                                    <span>Contact Person</span>
                                    <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M5 12l5-5 5 5H5z"/>
                                    </svg>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($schools as $school): ?>
                        <tr class="hover:bg-blue-50 transition-colors duration-150">
                            <!-- Responsive table cells -->
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-200">
                                <div class="sm:hidden font-medium text-gray-500 mb-1">Division Office:</div>
                                <?= htmlspecialchars($school['division_office']) ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-200">
                                <div class="sm:hidden font-medium text-gray-500 mb-1">School Name:</div>
                                <a href="/school/<?= $school['id'] ?>" 
                                   class="text-blue-600 hover:text-blue-800 hover:underline">
                                    <?= htmlspecialchars($school['school_name']) ?>
                                </a>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-200 max-w-xs">
                                <div class="line-clamp-2">
                                    <?= htmlspecialchars($school['address']) ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-200">
                                <?php if (!empty($school['permit_no'])): ?>
                                    <div class="space-y-1">
                                        <?php 
                                        $permits = explode(',', $school['permit_no']);
                                        foreach ($permits as $permit): 
                                        ?>
                                            <div class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                                <?= htmlspecialchars(trim($permit)) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-200">
                                <span class="inline-block text-xs font-medium text-gray-800 uppercase tracking-wide">
                                    <?= htmlspecialchars($school['program_offering']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 border-r border-gray-200">
                                <div class="space-y-1">
                                    <?php if (!empty($school['contact_phone'])): ?>
                                        <div class="text-xs"><?= htmlspecialchars($school['contact_phone']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($school['contact_email'])): ?>
                                        <div class="text-xs">
                                            <a href="mailto:<?= htmlspecialchars($school['contact_email']) ?>" class="text-blue-600 hover:text-blue-800 underline">
                                                <?= htmlspecialchars($school['contact_email']) ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                <?= htmlspecialchars($school['contact_person']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (empty($schools)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center space-y-2">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span>No schools found</span>
                                    <?php if (!empty($search)): ?>
                                        <span class="text-sm">Try adjusting your search terms</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer - Stack pagination on mobile -->
            <div class="px-4 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-600 text-center sm:text-left">
                        Showing <?= empty($schools) ? 0 : min(($page - 1) * $limit + 1, $totalRecords) ?> to 
                        <?= min($page * $limit, $totalRecords) ?> of <?= number_format($totalRecords) ?> entries
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <nav class="flex items-center space-x-1 w-full sm:w-auto justify-center">
                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= strtolower($order) ?>" 
                           class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded border border-gray-300 transition-colors duration-150">
                            Previous
                        </a>
                        <?php else: ?>
                        <span class="px-3 py-1 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">
                            Previous
                        </span>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                        <a href="?page=<?= $i ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= strtolower($order) ?>" 
                           class="px-3 py-1 text-sm border transition-colors duration-150 <?= $i == $page ? 'bg-blue-500 text-white border-blue-500' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-100 border-gray-300' ?> rounded">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>

                        <!-- Next Button -->
                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>&order=<?= strtolower($order) ?>" 
                           class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded border border-gray-300 transition-colors duration-150">
                            Next
                        </a>
                        <?php else: ?>
                        <span class="px-3 py-1 text-sm text-gray-400 border border-gray-200 rounded cursor-not-allowed">
                            Next
                        </span>
                        <?php endif; ?>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="/public/js/listing.js"></script>
</body>
</html>
