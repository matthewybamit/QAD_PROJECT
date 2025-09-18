<?php
// admin/fix-maintenance.php - Fix maintenance mode issues

echo "Maintenance Mode Fix\n";
echo "====================\n\n";

// Try to load environment
$envPaths = [
    '../.env',
    '../../.env', 
    '../config/.env',
    '.env'
];

$envFound = false;
$envPath = null;

foreach ($envPaths as $path) {
    if (file_exists($path)) {
        $envFound = true;
        $envPath = $path;
        echo "Found .env file at: $path\n";
        break;
    }
}

if (!$envFound) {
    echo "No .env file found. Checking these locations:\n";
    foreach ($envPaths as $path) {
        echo "- $path (not found)\n";
    }
    echo "\nYou need to create a .env file with:\n";
    echo "MAINTENANCE_MODE=false\n";
    exit;
}

// Read the .env file
$envContent = file_get_contents($envPath);
$envLines = explode("\n", $envContent);

$hasMaintenanceMode = false;
$maintenanceValue = null;
$hasMaintenanceSecret = false;
$secretValue = null;

foreach ($envLines as $line) {
    $line = trim($line);
    if (strpos($line, 'MAINTENANCE_MODE=') === 0) {
        $hasMaintenanceMode = true;
        $maintenanceValue = str_replace('MAINTENANCE_MODE=', '', $line);
    }
    if (strpos($line, 'MAINTENANCE_SECRET=') === 0) {
        $hasMaintenanceSecret = true;
        $secretValue = str_replace('MAINTENANCE_SECRET=', '', $line);
    }
}

echo "\nCurrent Configuration:\n";
echo "- MAINTENANCE_MODE: " . ($hasMaintenanceMode ? $maintenanceValue : 'not set') . "\n";
echo "- MAINTENANCE_SECRET: " . ($hasMaintenanceSecret ? $secretValue : 'not set') . "\n\n";

// Load env function if available
if (file_exists('../config/env.php')) {
    require_once '../config/env.php';
} elseif (file_exists('config/env.php')) {
    require_once 'config/env.php';
}

if (function_exists('env')) {
    echo "Environment function loaded successfully\n";
    echo "- env('MAINTENANCE_MODE'): " . (env('MAINTENANCE_MODE', 'not set')) . "\n";
    echo "- env('MAINTENANCE_SECRET'): " . (env('MAINTENANCE_SECRET', 'not set')) . "\n\n";
} else {
    echo "Warning: env() function not available\n\n";
}

// Check if maintenance mode is causing issues
if ($hasMaintenanceMode && strtolower($maintenanceValue) === 'true') {
    echo "🔧 MAINTENANCE MODE IS ENABLED\n";
    echo "This is why you're seeing the maintenance page.\n\n";
    
    echo "Solutions:\n";
    echo "1. Disable maintenance mode (recommended):\n";
    echo "   Change MAINTENANCE_MODE=true to MAINTENANCE_MODE=false in $envPath\n\n";
    
    if ($hasMaintenanceSecret && !empty($secretValue)) {
        echo "2. Or use the maintenance secret to bypass:\n";
        echo "   Visit: http://localhost/QAD_PROJECT/QAD_AdminSite/?secret=$secretValue\n\n";
    } else {
        echo "2. Or add a maintenance secret to your .env file:\n";
        echo "   Add: MAINTENANCE_SECRET=your_secret_here\n";
        echo "   Then visit: http://localhost/QAD_PROJECT/QAD_AdminSite/?secret=your_secret_here\n\n";
    }
} else {
    echo "✅ Maintenance mode is disabled or not set.\n";
    echo "The issue might be elsewhere. Try accessing the admin panel directly.\n\n";
}

// Offer to automatically fix
if ($hasMaintenanceMode && strtolower($maintenanceValue) === 'true') {
    echo "Would you like me to automatically disable maintenance mode? (y/n): ";
    $input = trim(fgets(STDIN));
    
    if (strtolower($input) === 'y') {
        // Replace maintenance mode value
        $newContent = str_replace('MAINTENANCE_MODE=true', 'MAINTENANCE_MODE=false', $envContent);
        $newContent = str_replace('MAINTENANCE_MODE=TRUE', 'MAINTENANCE_MODE=false', $newContent);
        
        if (file_put_contents($envPath, $newContent)) {
            echo "✅ Successfully disabled maintenance mode in $envPath\n";
            echo "You can now access the admin panel normally.\n";
        } else {
            echo "❌ Failed to write to $envPath\n";
            echo "Please manually change MAINTENANCE_MODE=true to MAINTENANCE_MODE=false\n";
        }
    }
} elseif (!$hasMaintenanceMode) {
    echo "Adding MAINTENANCE_MODE=false to your .env file...\n";
    $newContent = $envContent . "\n# Admin Panel Configuration\nMAINTENANCE_MODE=false\n";
    
    if (file_put_contents($envPath, $newContent)) {
        echo "✅ Added MAINTENANCE_MODE=false to $envPath\n";
    } else {
        echo "❌ Failed to write to $envPath\n";
        echo "Please manually add: MAINTENANCE_MODE=false\n";
    }
}

echo "\n" . str_repeat("=", 40) . "\n";
echo "Next Steps:\n";
echo "1. Try accessing the admin panel again\n";
echo "2. If still having issues, check other environment variables\n";
echo "3. Run: php quick-setup.php to verify everything is working\n";

?>

<?php
// admin/bypass-maintenance.php - Temporary bypass script

echo "Maintenance Mode Bypass\n";
echo "======================\n\n";

// Generate a random secret
$secret = bin2hex(random_bytes(16));

echo "Temporary maintenance secret generated: $secret\n\n";

echo "To bypass maintenance mode, use this URL:\n";
echo "http://localhost/QAD_PROJECT/QAD_AdminSite/?secret=$secret\n\n";

echo "Or add this to your .env file:\n";
echo "MAINTENANCE_SECRET=$secret\n\n";

// Try to temporarily set the secret in session for this script
$_GET['secret'] = $secret;

echo "Testing access...\n";

// Simple test
if (file_exists('config/env.php')) {
    require_once 'config/env.php';
    if (function_exists('env')) {
        $maintenanceMode = env('MAINTENANCE_MODE', false);
        echo "Maintenance mode from env: " . ($maintenanceMode ? 'true' : 'false') . "\n";
        
        if ($maintenanceMode) {
            echo "Maintenance mode is active. Use the secret above to bypass.\n";
        } else {
            echo "Maintenance mode is not active. The issue may be elsewhere.\n";
        }
    }
}
?>

<?php
// admin/disable-maintenance.php - Directly disable maintenance mode

echo "Disable Maintenance Mode\n";
echo "========================\n\n";

$envFiles = ['../.env', '../../.env', '.env'];
$found = false;

foreach ($envFiles as $envFile) {
    if (file_exists($envFile)) {
        echo "Found .env file: $envFile\n";
        
        $content = file_get_contents($envFile);
        
        // Replace maintenance mode settings
        $newContent = preg_replace('/MAINTENANCE_MODE\s*=\s*true/i', 'MAINTENANCE_MODE=false', $content);
        
        // If no MAINTENANCE_MODE line exists, add it
        if (strpos($content, 'MAINTENANCE_MODE') === false) {
            $newContent .= "\nMAINTENANCE_MODE=false\n";
        }
        
        if (file_put_contents($envFile, $newContent)) {
            echo "✅ Maintenance mode disabled in $envFile\n";
            $found = true;
            break;
        } else {
            echo "❌ Failed to write to $envFile\n";
        }
    }
}

if (!$found) {
    echo "No .env file found. Creating one...\n";
    $envContent = "# Environment Configuration\nMAINTENANCE_MODE=false\n";
    
    if (file_put_contents('../.env', $envContent)) {
        echo "✅ Created .env file with maintenance mode disabled\n";
    } else {
        echo "❌ Failed to create .env file\n";
    }
}

echo "\nTry accessing your admin panel now.\n";
?>