<!-- views/login.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrfToken ?>">
    <title><?= $pageTitle ?? 'Admin Login' ?> - School Directory Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl border border-white/20 p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto h-16 w-16 bg-blue-600 rounded-full flex items-center justify-center mb-4 shadow-lg">
                    <i class="fas fa-shield-alt text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Admin Portal</h1>
                <p class="text-blue-200 text-sm">Secure access to school directory management</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="mb-6 bg-red-500/20 border border-red-500/50 text-red-100 px-4 py-3 rounded-lg backdrop-blur">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle mr-3"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-blue-200 mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email Address
                        </label>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               required 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               class="w-full px-4 py-3 bg-white/10 border border-white/20 text-white placeholder-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent backdrop-blur"
                               placeholder="admin@yourschool.com">
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-blue-200 mb-2">
                            <i class="fas fa-lock mr-2"></i>Password
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   required 
                                   class="w-full px-4 py-3 bg-white/10 border border-white/20 text-white placeholder-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent backdrop-blur pr-12"
                                   placeholder="Enter your password">
                            <button type="button" 
                                    onclick="togglePassword()" 
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-300 hover:text-white">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 px-6 rounded-lg font-semibold hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-blue-900 transition-all duration-200 shadow-lg">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In to Admin Panel
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-white/20">
                <div class="flex items-center justify-center space-x-4 text-blue-300 text-sm">
                    <a href="/" class="hover:text-white transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Main Site
                    </a>
                    <span>|</span>
                    <a href="/health" class="hover:text-white transition-colors">
                        <i class="fas fa-heartbeat mr-1"></i>System Status
                    </a>
                </div>
            </div>
        </div>

        <!-- Security Notice -->
        <div class="mt-6 text-center">
            <p class="text-blue-300 text-xs">
                <i class="fas fa-shield-alt mr-1"></i>
                This is a secure admin area. All activities are logged and monitored.
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-focus email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });

        // Enhanced form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
            
            if (!/\S+@\S+\.\S+/.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                document.getElementById('email').focus();
                return false;
            }
        });
    </script>
</body>
</html>