<?php require_once 'partials/head.php'; ?>

<body class="bg-gradient-to-br from-white-50 to-blue-50 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="hidden md:block w-64 bg-white shadow-xl border-r border-gray-200 h-screen sticky top-0">
        <div class="px-6 py-6 border-b">
            <h2 class="text-lg font-bold text-blue-700">School Profile</h2>
            <p class="text-sm text-gray-500">Manage details</p>
        </div>
        <nav class="p-4 space-y-2 ">
            <a href="#mission" class="block px-3 py-2 rounded-md hover:bg-blue-50">Mission & Vision</a>
            <a href="#basic" class="block px-3 py-2 rounded-md hover:bg-blue-50">Basic Information</a>
            <a href="#exec" class="block px-3 py-2 rounded-md hover:bg-blue-50">Executive Officials</a>
            <a href="#programs" class="block px-3 py-2 rounded-md hover:bg-blue-50">Program Offerings</a>
            <a href="#facilities" class="block px-3 py-2 rounded-md hover:bg-blue-50">Facilities</a>
            <a href="#contact" class="block px-3 py-2 rounded-md hover:bg-blue-50">Contact</a>
            <a href="#record" class="block px-3 py-2 rounded-md hover:bg-blue-50">Record Info</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 p-6 md:p-10">
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

                <!-- School Header -->
                <div class="px-8 py-10 border-b-4 border-blue-500 bg-gradient-to-r from-blue-50 to-white flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900"><?= htmlspecialchars($school['school_name']) ?></h1>
                        <p class="mt-1 text-gray-600">ID: <?= $school['id'] ?></p>
                    </div>
                    <a href="/" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white shadow-sm hover:bg-gray-50 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Listing
                    </a>
                </div>

                <!-- Main Form -->
                <form method="POST" class="p-8 space-y-12">

                    <!-- Mission & Vision -->
                    <section id="mission" class="grid md:grid-cols-2 gap-8">
                        <div class="bg-gray-50 p-6 rounded-xl shadow-sm">
                            <h2 class="text-xl font-bold text-blue-700 mb-2">Mission</h2>
                            <textarea name="mission_statement" rows="4" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($school['mission_statement'] ?? '') ?></textarea>
                        </div>
                        <div class="bg-gray-50 p-6 rounded-xl shadow-sm">
                            <h2 class="text-xl font-bold text-blue-700 mb-2">Vision</h2>
                            <textarea name="vision_statement" rows="4" class="w-full rounded-md border border-gray-300 p-3 focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($school['vision_statement'] ?? '') ?></textarea>
                        </div>
                    </section>

                    <!-- Basic Info -->
                    <section id="basic">
                        <h2 class="text-2xl font-bold text-gray-900 border-l-4 border-blue-600 pl-3 mb-6">Basic Information</h2>
                        <div class="grid md:grid-cols-2 gap-6">
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

                    <!-- Floating Action Bar -->
                    <div class="sticky bottom-0 bg-white border-t border-gray-200 py-4 px-6 flex justify-end space-x-4">
                        <button type="button" onclick="history.back()" class="px-6 py-2.5 text-sm font-medium rounded-md border border-gray-300 bg-white hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-6 py-2.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700">Save Changes</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <!-- JS -->
    <script src="/public/js/hoveringInputs.js"></script>

</body>
</html>