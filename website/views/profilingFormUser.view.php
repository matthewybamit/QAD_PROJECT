<?php require_once 'partials/head.php'; ?>

<body>
<?php require_once 'partials/nav.php'; ?>

<?php
// Get the school ID from URL parameter
$schoolId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$school = null;
$error = null;

if ($schoolId > 0) {
    try {
        $query = "SELECT * FROM schools WHERE id = :id";
        $stmt = $db->connection->prepare($query);
        $stmt->bindParam(':id', $schoolId, PDO::PARAM_INT);
        $stmt->execute();
        
        $school = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$school) {
            $error = "School not found";
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
} else {
    $error = "Invalid school ID";
}
?>

<?php if ($error): ?>
<div class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="text-center">
        <div class="mb-4">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900">School Not Found</h3>
        <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($error) ?></p>
        <div class="mt-6">
            <a href="/listing" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                Back to Schools
            </a>
        </div>
    </div>
</div>
<?php else: ?>

<!-- Hero Section -->
<div class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-red-600 overflow-hidden ">
    <!-- Background Image Overlay -->
    <div class="absolute inset-0 bg-black opacity-40"></div>
    
    <!-- Decorative Wave -->

    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex flex-col lg:flex-row items-center justify-between">
            <!-- School Logo and Info -->
            <div class="text-center lg:text-left mb-8 lg:mb-0">
                <!-- Logo Placeholder -->
<div class="w-20 h-20 mx-auto lg:mx-0 mb-4 bg-white rounded-full flex items-center justify-center shadow-lg overflow-hidden">
    <?php if (!empty($school['school_logo'])): ?>
        <img src="/assets/images/<?= htmlspecialchars($school['school_logo']) ?>" 
             alt="<?= htmlspecialchars($school['school_name']) ?> Logo" 
             class="w-full h-full object-cover rounded-full">
    <?php else: ?>
        <svg class="w-12 h-12 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10.496 2.132a1 1 0 00-.992 0l-7 4A1 1 0 003 8v7a1 1 0 100 2h14a1 1 0 100-2V8a1 1 0 00.496-1.868l-7-4zM6 9a1 1 0 000 2h2a1 1 0 100-2H6z" clip-rule="evenodd"></path>
        </svg>
    <?php endif; ?>
</div>

                
                <h1 class="text-3xl lg:text-4xl font-bold text-white mb-2">
                    <?= strtoupper(htmlspecialchars($school['school_name'])) ?>
                </h1>
                
                <div class="flex items-center justify-center lg:justify-start text-white mb-4">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-lg"><?= htmlspecialchars($school['address']) ?></span>
                </div>
            </div>
            
    <!-- Decorative Wave Image -->
  
            <!-- Back Button -->
            <div class="flex-shrink-0 relative z-10">
                <a href="/listing" class="inline-flex items-center px-6 mb-15 py-3 border border-white text-white bg-transparent hover:bg-white hover:text-blue-600 font-medium rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Schools
                </a>
            </div>
        </div>
    </div>
      <div class="absolute bottom-0 left-0 right-0">
        <img src="/assets/images/wave.png" alt="wave" class="w-6000 h-50" />
    </div>
</div>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
<!-- School Profile Section -->
<section id="school-profile" class="mb-12">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 flex flex-col md:flex-row items-start">
        
        <!-- Left Column: Vertical Title -->
        <aside class="flex items-center md:flex-col md:items-start md:pr-8 mb-6 md:mb-0 border-r-4 border-black pr-6">
            <h2 class="text-3xl font-extrabold text-gray-900 uppercase tracking-wider leading-tight">
                School <br /> Profile
            </h2>
        </aside>
        
        <!-- Right Column: Content -->
        <div class="flex-1 md:pl-8">
            
            <!-- Description -->
            <article class="mb-6">
                <p class="text-gray-700 leading-relaxed text-lg">
                    <?= !empty($school['school_description']) ? 
                        htmlspecialchars($school['school_description']) : 
                        'A distinguished educational institution committed to academic excellence and student development.' ?>
                </p>
            </article>
            
            <!-- Key Information Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Founded -->
                <?php if (!empty($school['founding_year'])): ?>
                <div class="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="text-sm text-gray-600 uppercase tracking-wide">Founded</div>
                    <div class="text-2xl font-bold text-blue-600"><?= $school['founding_year'] ?></div>
                </div>
                <?php endif; ?>
                
                <!-- Students -->
                <?php if (!empty($school['student_population'])): ?>
                <div class="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                    <div class="text-sm text-gray-600 uppercase tracking-wide">Students</div>
                    <div class="text-2xl font-bold text-green-600"><?= number_format($school['student_population']) ?></div>
                </div>
                <?php endif; ?>
                
                <!-- Faculty -->
                <?php if (!empty($school['faculty_count'])): ?>
                <div class="text-center p-4 bg-purple-50 rounded-lg border border-purple-200">
                    <div class="text-sm text-gray-600 uppercase tracking-wide">Faculty</div>
                    <div class="text-2xl font-bold text-purple-600"><?= number_format($school['faculty_count']) ?></div>
                </div>
                <?php endif; ?>
                
                <!-- Program Level -->
                <?php if (!empty($school['program_offering'])): ?>
                <div class="text-center p-4 bg-orange-50 rounded-lg border border-orange-200">
                    <div class="text-sm text-gray-600 uppercase tracking-wide">Program Level</div>
                    <div class="text-xl font-bold text-orange-600 uppercase">
                        <?= htmlspecialchars($school['program_offering']) ?>
                    </div>
                </div>
                <?php endif; ?>
            
            </div>
        </div>
    </div>
</section>

    <!-- School History Section -->
    <div class="mb-12">
        <div class="flex items-center mb-8">
            <div class="h-px bg-gray-300 flex-1"></div>
            <div class="px-6">
                <h2 class="text-2xl font-bold text-gray-900 uppercase tracking-wider">School History</h2>
            </div>
            <div class="h-px bg-gray-300 flex-1"></div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            <div class="prose max-w-none">
                <p class="text-gray-700 leading-relaxed">
                    <?= !empty($school['school_history']) ? 
                        nl2br(htmlspecialchars($school['school_history'])) : 
                        'This institution has a rich history of providing quality education and contributing to the development of our community. Through the years, we have maintained our commitment to academic excellence and student success.' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Mission & Vision Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        <!-- Mission -->
        <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg transform rotate-1"></div>
            <div class="relative bg-white rounded-lg shadow-lg border border-gray-200 p-8">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 uppercase tracking-wider">Mission</h3>
                </div>
                <p class="text-gray-700 leading-relaxed text-center">
                    <?= !empty($school['mission_statement']) ? 
                        htmlspecialchars($school['mission_statement']) : 
                        'To provide quality education that empowers students to achieve their full potential and become productive members of society.' ?>
                </p>
            </div>
        </div>

        <!-- Vision -->
        <div class="relative">
            <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-green-600 rounded-lg transform -rotate-1"></div>
            <div class="relative bg-white rounded-lg shadow-lg border border-gray-200 p-8">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 uppercase tracking-wider">Vision</h3>
                </div>
                <p class="text-gray-700 leading-relaxed text-center">
                    <?= !empty($school['vision_statement']) ? 
                        htmlspecialchars($school['vision_statement']) : 
                        'To be a leading educational institution recognized for excellence in teaching, learning, and community service.' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Contact Information & Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Contact Information -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                </svg>
                Contact Information
            </h3>
            
            <div class="space-y-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-gray-400 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <div class="font-medium text-gray-900">Address</div>
                        <div class="text-gray-600"><?= htmlspecialchars($school['address']) ?></div>
                    </div>
                </div>

                <?php if (!empty($school['contact_phone'])): ?>
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-gray-400 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                    </svg>
                    <div>
                        <div class="font-medium text-gray-900">Phone</div>
                        <div class="text-gray-600"><?= htmlspecialchars($school['contact_phone']) ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($school['contact_email'])): ?>
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-gray-400 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                    </svg>
                    <div>
                        <div class="font-medium text-gray-900">Email</div>
                        <div class="text-gray-600">
                            <a href="mailto:<?= htmlspecialchars($school['contact_email']) ?>" class="text-blue-600 hover:text-blue-800">
                                <?= htmlspecialchars($school['contact_email']) ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex items-start">
                    <svg class="w-5 h-5 text-gray-400 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <div class="font-medium text-gray-900">Contact Person</div>
                        <div class="text-gray-600"><?= htmlspecialchars($school['contact_person']) ?></div>
                    </div>
                </div>

                <?php if (!empty($school['website_url'])): ?>
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-gray-400 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.559-.499-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.559.499.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.497-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <div class="font-medium text-gray-900">Website</div>
                        <div class="text-gray-600">
                            <a href="<?= htmlspecialchars($school['website_url']) ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                                <?= htmlspecialchars($school['website_url']) ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Additional Details -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-3 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                School Details
            </h3>
            
            <div class="space-y-4">
                <div>
                    <div class="font-medium text-gray-900">Division Office</div>
                    <div class="text-gray-600"><?= htmlspecialchars($school['division_office']) ?></div>
                </div>

                <?php if (!empty($school['permit_no'])): ?>
                <div>
                    <div class="font-medium text-gray-900">Permit Number(s)</div>
                    <div class="space-y-1 mt-1">
                        <?php foreach (explode(',', $school['permit_no']) as $permit): ?>
                            <span class="inline-block text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                <?= htmlspecialchars(trim($permit)) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($school['accreditation'])): ?>
                <div>
                    <div class="font-medium text-gray-900">Accreditation</div>
                    <div class="text-gray-600"><?= htmlspecialchars($school['accreditation']) ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($school['facilities'])): ?>
                <div>
                    <div class="font-medium text-gray-900">Facilities</div>
                    <div class="text-gray-600"><?= htmlspecialchars($school['facilities']) ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($school['achievements'])): ?>
                <div>
                    <div class="font-medium text-gray-900">Recent Achievements</div>
                    <div class="text-gray-600"><?= htmlspecialchars($school['achievements']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

</body>
</html>