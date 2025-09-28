<nav class="bg-blue-900 shadow-lg">
  <div class="max-w-9xl mx-auto px-10 sm:px-12 lg:px-16">
    <div class="flex justify-between items-center h-24">
      
<div class="flex items-center space-x-3 sm:space-x-5">
  <!-- Logo: smaller on mobile, normal on desktop -->
  <img src="/assets/images/QAD_LOGO.png" alt="DepEd Logo" 
       class="h-10 w-auto sm:h-16">

  <div class="leading-tight">
    <!-- Title: smaller on mobile, original size on desktop -->
    <span class="block text-lg sm:text-2xl font-bold text-white tracking-wide">
      Department of Education
    </span>
    <span class="block text-sm sm:text-base text-yellow-400">
      Republic of the Philippines
    </span>
  </div>
</div>


      <div class="flex items-center space-x-4">
        <div class="relative inline-block text-left">
          <button id="userMenuButton" class="flex items-center space-x-3 focus:outline-none">
            <div class="w-10 h-10 rounded-full bg-blue-700 flex items-center justify-center">
              <span class="text-white font-medium">
                <?php echo isset($_SESSION['user_name']) ? substr($_SESSION['user_name'], 0, 1) : 'G'; ?>
              </span>
            </div>
            <div class="hidden md:block text-right">
              <div class="text-white"><?php echo $_SESSION['user_name'] ?? 'Guest'; ?></div>
              <div class="text-blue-200 text-sm"><?php echo $_SESSION['user_email'] ?? ''; ?></div>
            </div>
            <svg class="w-5 h-5 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <div id="userMenuDropdown" 
              class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 hidden z-50">
            <?php if (isset($_SESSION['user_name'])): ?>
            <div class="px-4 py-2 border-b">
              <p class="text-sm text-gray-500">Signed in as</p>
              <p class="text-sm font-medium text-gray-900 truncate">
                <?php echo $_SESSION['user_email'] ?? ''; ?>
              </p>
            </div>
            <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Your Profile</a>
            <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">Settings</a>
            <div class="border-t">
              <a href="/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Sign out</a>
            </div>
            <?php else: ?>
            <div class="px-4 py-2 border-b">
              <p class="text-sm font-medium text-gray-900">You are a guest</p>
            </div>
            <a href="/login" class="block px-4 py-2 text-sm text-blue-600 hover:bg-blue-50">Login</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- Sub Navigation Links -->
<div class="bg-white shadow relative">
  <div class="max-w-9xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Mobile menu button -->
    <div class="flex lg:hidden justify-end py-4">
      <button id="mobile-menu-button" class="text-gray-600 hover:text-gray-900">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
      </button>
    </div>

    <!-- Navigation Links - Desktop -->
    <div id="nav-links" class="hidden lg:flex lg:justify-center lg:space-x-8 lg:h-12 lg:items-center">
      <a href="/" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Home</a>
      <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">About</a>
      <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Programs and Projects</a>

      <!-- Dropdown: Authorized Private School -->
      <div class="relative group">
        <button class="text-gray-800 font-medium hover:text-blue-600 flex items-center space-x-1">
          <span>Authorized Private School</span>
          <svg class="w-4 h-4 text-gray-600 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <!-- Dropdown Menu -->
        <div class="absolute left-0 mt-2 w-64 bg-white shadow-lg rounded-md opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-20">
          <a href="/listing" class="block px-4 py-2 text-gray-700 hover:bg-blue-100">Homeschool</a>
          <a href="/listing" class="block px-4 py-2 text-gray-700 hover:bg-blue-100">Private Schools with DepEd Permit</a>
          <a href="/listing" class="block px-4 py-2 text-gray-700 hover:bg-blue-100">Private Schools with DepEd Recognition</a>
          <a href="/listing" class="block px-4 py-2 text-gray-700 hover:bg-blue-100">Private Schools with SHS Provisional Permit</a>
          <a href="/listing" class="block px-4 py-2 text-gray-700 hover:bg-blue-100">SHS-VP Schools</a>
        </div>
      </div>

      <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">References</a>
      <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Resources</a>
      <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Contact Us</a>
    </div>

    <!-- Mobile Navigation Menu -->
    <div id="mobile-menu" class="hidden lg:hidden pb-4">
      <div class="flex flex-col space-y-2">
        <a href="/" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Home</a>
        <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">About</a>
        <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Programs and Projects</a>
        
        <!-- Mobile Dropdown -->
        <div class="relative">
          <button id="mobile-dropdown-button" class="w-full text-left py-2 text-gray-800 font-medium hover:text-blue-600 flex items-center justify-between">
            <span>Authorized Private School</span>
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div id="mobile-dropdown-menu" class="hidden bg-gray-50 px-4 py-2 space-y-2">
            <a href="/listing" class="block py-2 text-gray-700 hover:text-blue-600">Homeschool</a>
            <a href="/listing" class="block py-2 text-gray-700 hover:text-blue-600">Private Schools with DepEd Permit</a>
            <a href="/listing" class="block py-2 text-gray-700 hover:text-blue-600">Private Schools with DepEd Recognition</a>
            <a href="/listing" class="block py-2 text-gray-700 hover:text-blue-600">Private Schools with SHS Provisional Permit</a>
            <a href="/listing" class="block py-2 text-gray-700 hover:text-blue-600">SHS-VP Schools</a>
          </div>
        </div>

        <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">References</a>
        <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Resources</a>
        <a href="#" class="block py-2 text-gray-800 font-medium hover:text-blue-600">Contact Us</a>
      </div>
    </div>
  </div>
</div>

<script>
  // Mobile menu toggle
  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');
  const mobileDropdownButton = document.getElementById('mobile-dropdown-button');
  const mobileDropdownMenu = document.getElementById('mobile-dropdown-menu');

  mobileMenuButton.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
  });

  mobileDropdownButton.addEventListener('click', () => {
    mobileDropdownMenu.classList.toggle('hidden');
  });

  // User profile dropdown toggle
  const userMenuButton = document.getElementById("userMenuButton");
  const userMenuDropdown = document.getElementById("userMenuDropdown");

  userMenuButton.addEventListener("click", () => {
    userMenuDropdown.classList.toggle("hidden");
  });

  // Close dropdown when clicking outside
  document.addEventListener("click", (e) => {
    if (!userMenuButton.contains(e.target) && !userMenuDropdown.contains(e.target)) {
      userMenuDropdown.classList.add("hidden");
    }
  });
</script>
