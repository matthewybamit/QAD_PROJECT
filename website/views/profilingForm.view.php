<?php require_once 'partials/head.php'; ?>

<body class="bg-gradient-to-br from-white-50 to-blue-50 min-h-screen flex flex-col lg:flex-row">

    <!-- Sidebar - Hide on mobile, show on large screens -->
    <div class="hidden lg:block">
        <?php include 'partials/profilingNav.php'; ?>
    </div>

    <!-- Mobile Header - Show only on mobile -->
    <div class="lg:hidden bg-blue-900 text-white p-4 sticky top-0 z-50">
        <button id="mobile-menu-button" class="text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </div>

    <!-- Mobile Navigation Menu -->
    <div id="mobile-menu" class="lg:hidden fixed inset-0 bg-blue-900 bg-opacity-95 z-40 transform -translate-x-full transition-transform duration-200">
        <?php include 'partials/profilingNav.php'; ?>
    </div>

    <!-- Main Content -->
    <main class="flex-1 p-4 md:p-6 lg:p-10">
        <div class="max-w-5xl mx-auto">

            <!-- Alerts -->
            <?php if (isset($error)): ?>
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm">
                    <h3 class="text-sm font-semibold text-red-800">Error</h3>
                    <p class="mt-1 text-sm text-red-700"><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-sm">
                    <h3 class="text-sm font-semibold text-green-800">Success</h3>
                    <p class="mt-1 text-sm text-green-700"><?= htmlspecialchars($success) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($school): ?>

                <!-- School Header with Logo -->
                <div class="px-4 md:px-8 py-6 md:py-10 border-b-4 border-blue-500 bg-gradient-to-r from-blue-50 to-white">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex flex-col md:flex-row md:items-center md:space-x-6">
                            <!-- Logo section responsive adjustments -->
                            <div class="flex-shrink-0 mb-4 md:mb-0">
                                <?php if (!empty($school['school_logo'])): ?>
                                    <img src="/assets/logos/<?= htmlspecialchars($school['school_logo']) ?>" 
                                         alt="<?= htmlspecialchars($school['school_name']) ?> Logo" 
                                         class="w-20 h-20 object-contain rounded-lg border-2 border-gray-200 bg-white p-2">
                                <?php else: ?>
                                    <div class="w-20 h-20 bg-gray-200 rounded-lg border-2 border-gray-300 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h1 class="text-2xl md:text-3xl font-extrabold text-gray-900"><?= htmlspecialchars($school['school_name']) ?></h1>
                                <p class="mt-1 text-gray-600">ID: <?= $school['id'] ?></p>
                            </div>
                        </div>
                        <!-- Back button responsive positioning -->
                        <a href="/listing" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white shadow-sm hover:bg-gray-50 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Listing
                        </a>
                    </div>
                </div>  

                <!-- Main Form with file upload -->
                <form method="POST" enctype="multipart/form-data" class="p-4 md:p-8 space-y-8 md:space-y-12">

                    <!-- School Logo Upload Section -->
                    <section id="logo" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <h2 class="text-xl font-bold text-blue-700 mb-4">School Logo</h2>
                        <div class="flex items-start space-x-6">
                            <!-- Current Logo Preview -->
                            <div class="flex-shrink-0">
                                <div id="logo-preview" class="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-gray-50">
                                    <?php if (!empty($school['school_logo'])): ?>
                                        <img id="current-logo" src="/assets/logos/<?= htmlspecialchars($school['school_logo']) ?>" 
                                             alt="Current Logo" 
                                             class="w-full h-full object-contain rounded-lg">
                                    <?php else: ?>
                                        <div id="no-logo-placeholder" class="text-center text-gray-400">
                                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <p class="text-sm">No Logo</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Logo Upload Controls -->
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Logo</label>
                                <input type="file" name="school_logo" id="school-logo-input" 
                                       accept="image/*" 
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="mt-2 text-xs text-gray-500">
                                    Supported formats: JPG, PNG, GIF. Max size: 2MB. Recommended: 200x200px square format.
                                </p>
                                <?php if (!empty($school['school_logo'])): ?>
                                    <div class="mt-3">
                                        <p class="text-sm text-gray-600">Current logo: <strong><?= htmlspecialchars($school['school_logo']) ?></strong></p>
                                        <label class="inline-flex items-center mt-2">
                                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-red-600">Remove current logo</span>
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <!-- Mission & Vision -->
                    <section id="mission" class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                        <div class="bg-gray-50 p-6 rounded-xl shadow-sm">
                            <h2 class="text-xl font-bold text-blue-700 mb-2">Mission</h2>
                            <textarea name="mission_statement" rows="4" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($school['mission_statement'] ?? '') ?></textarea>
                        </div>
                        <div class="bg-gray-50 p-6 rounded-xl shadow-sm">
                            <h2 class="text-xl font-bold text-blue-700 mb-2">Vision</h2>
                            <textarea name="vision_statement" rows="4" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($school['vision_statement'] ?? '') ?></textarea>
                        </div>
                    </section>

                    <!-- Basic Info Section -->
                    <section id="basic">
                        <h2 class="text-xl md:text-2xl font-bold text-gray-900 border-l-4 border-blue-600 pl-3 mb-4 md:mb-6">Basic Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Division Office</label>
                                <input type="text" name="division_office" value="<?= htmlspecialchars($school['division_office']) ?>" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Founding Year</label>
                                <input type="number" name="founding_year" value="<?= htmlspecialchars($school['founding_year'] ?? '') ?>" placeholder="e.g., 1998" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Program Offering</label>
                                <select name="program_offering" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                                    <option value="KINDERGARTEN" <?= $school['program_offering'] === 'KINDERGARTEN' ? 'selected' : '' ?>>Kindergarten</option>
                                    <option value="ELEMENTARY" <?= $school['program_offering'] === 'ELEMENTARY' ? 'selected' : '' ?>>Elementary</option>
                                    <option value="JUNIOR HIGH SCHOOL" <?= $school['program_offering'] === 'JUNIOR HIGH SCHOOL' ? 'selected' : '' ?>>Junior High School</option>
                                    <option value="SENIOR HIGH SCHOOL" <?= $school['program_offering'] === 'SENIOR HIGH SCHOOL' ? 'selected' : '' ?>>Senior High School</option>
                                    <option value="UNIVERSITY" <?= $school['program_offering'] === 'UNIVERSITY' ? 'selected' : '' ?>>University</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Student Population</label>
                                <input type="number" name="student_population" value="<?= htmlspecialchars($school['student_population'] ?? '') ?>" placeholder="e.g., 2500" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">School Name</label>
                                <input type="text" name="school_name" value="<?= htmlspecialchars($school['school_name']) ?>" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">School Description</label>
                                <textarea name="school_description" rows="3" placeholder="Brief description of the school..." class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($school['school_description'] ?? '') ?></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <textarea name="address" rows="3" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($school['address']) ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Website URL</label>
                                <input type="url" name="website_url" value="<?= htmlspecialchars($school['website_url'] ?? '') ?>" placeholder="https://example.com" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Facebook URL</label>
                                <input type="url" name="facebook_url" value="<?= htmlspecialchars($school['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/schoolpage" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Permit Number(s)</label>
                                <input type="text" name="permit_no" value="<?= htmlspecialchars($school['permit_no']) ?>" placeholder="e.g., K-0285 s. 2024 R-1, E-0221 s. 2024 R-1" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                                <p class="mt-1 text-xs text-gray-500">Separate multiple permits with commas</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Accreditation & Recognition</label>
                                <input type="text" name="accreditation" value="<?= htmlspecialchars($school['accreditation'] ?? '') ?>" placeholder="e.g., TESDA Accredited, JICA Partnership" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">School History</label>
                                <textarea name="school_history" rows="4" placeholder="History and background of the institution..." class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($school['school_history'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </section>

                    <!-- Executive Officials -->
                    <section id="exec">
                        <h2 class="text-2xl font-bold text-gray-900 border-l-4 border-blue-600 pl-3 mb-6">Executive Officials</h2>
                        <div class="overflow-x-auto bg-gray-50 rounded-xl shadow-sm">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-blue-100">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Name</th>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Position</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr>
                                        <td class="px-4 py-2">John Doe</td>
                                        <td class="px-4 py-2">President</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2">Jane Smith</td>
                                        <td class="px-4 py-2">Vice President</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Program Offerings -->
                    <section id="programs">
                        <h2 class="text-2xl font-bold text-gray-900 border-l-4 border-blue-600 pl-3 mb-6">Program Offerings</h2>
                        <div class="bg-gray-50 p-6 rounded-xl shadow-sm text-gray-600">Dynamic tables can go here...</div>
                    </section>

                    <!-- Facilities -->
                    <section id="facilities">
                        <h2 class="text-2xl font-bold text-gray-900 border-l-4 border-blue-600 pl-3 mb-6">School Facilities</h2>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Faculty Count</label>
                                <input type="number" name="faculty_count" value="<?= htmlspecialchars($school['faculty_count'] ?? '') ?>" placeholder="e.g., 150" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Recognition/Awards</label>
                                <input type="text" name="recognition" value="<?= htmlspecialchars($school['recognition'] ?? '') ?>" placeholder="e.g., Model Technology-Based Institution" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Facilities & Infrastructure</label>
                                <textarea name="facilities" rows="3" placeholder="List of available facilities (classrooms, laboratories, library, etc.)" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($school['facilities'] ?? '') ?></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Recent Achievements</label>
                                <textarea name="achievements" rows="3" placeholder="Recent awards, recognitions, and achievements..." class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($school['achievements'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </section>

                    <!-- Contact Info -->
                    <section id="contact">
                        <h2 class="text-2xl font-bold text-gray-900 border-l-4 border-blue-600 pl-3 mb-6">Contact Information</h2>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                                <input type="text" name="contact_person" value="<?= htmlspecialchars($school['contact_person']) ?>" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" id="contact_phone" name="contact_phone" value="<?= htmlspecialchars($school['contact_phone']) ?>" placeholder="(0927)-1514-308" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="contact_email" value="<?= htmlspecialchars($school['contact_email']) ?>" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </section>

                    <!-- Record Metadata -->
                    <?php if (isset($school['created_at']) || isset($school['updated_at'])): ?>
                    <section id="record" class="pt-6 border-t border-gray-200">
                        <h2 class="text-2xl font-bold text-gray-900 border-l-4 border-blue-600 pl-3 mb-6">Record Info</h2>
                        <div class="grid md:grid-cols-2 gap-6 text-sm text-gray-600">
                            <?php if (isset($school['created_at'])): ?>
                                <p><strong>Created:</strong> <?= date('F j, Y g:i A', strtotime($school['created_at'])) ?></p>
                            <?php endif; ?>
                            <?php if (isset($school['updated_at'])): ?>
                                <p><strong>Last Updated:</strong> <?= date('F j, Y g:i A', strtotime($school['updated_at'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>

                    <!-- Floating Action Bar - Adjust for mobile -->
                    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 flex justify-end space-x-4 z-40">
                        <button type="button" onclick="history.back()" 
                                class="px-4 md:px-6 py-2 text-sm font-medium rounded-md border border-gray-300 bg-white hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 md:px-6 py-2 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">
                            Save Changes
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <!-- JS -->
    <script src="/public/js/hoveringInputs.js"></script>
    <!-- Logo Preview Script -->
    <script src="/public/js/logoPreview.js"></script>   

    <!-- Add Mobile Menu JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('-translate-x-full');
        });
    });
    </script>
</body>
</html>