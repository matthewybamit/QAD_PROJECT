
<?php require_once 'partials/head.php'; ?>

<body class="bg-gray-50">
    <?php require_once 'partials/nav.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
        <div class="max-w-3xl mx-auto">
            <!-- Profile Header -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="bg-blue-700 h-32"></div>
                <div class="px-6 py-6 -mt-16">
                    <div class="flex items-center">
                        <div class="w-24 h-24 rounded-full bg-blue-600 flex items-center justify-center text-white text-3xl font-medium border-4 border-white">
                            <?php echo substr($_SESSION['user_name'], 0, 1); ?>
                        </div>
                        <div class="ml-6">
                            <h1 class="text-2xl font-bold text-gray-900"><?php echo $_SESSION['user_name']; ?></h1>
                            <p class="text-gray-600"><?php echo $_SESSION['user_email']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="mt-6 bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900">Profile Information</h2>
                    <div class="mt-6 grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-md"><?php echo $_SESSION['user_name']; ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-md"><?php echo $_SESSION['user_email']; ?></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-md"><?php echo ucfirst($_SESSION['user_role'] ?? 'User'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>