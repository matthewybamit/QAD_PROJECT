<?php
// admin/debug-maintenance.php - Deep troubleshooting

echo "Deep Maintenance Mode Troubleshooting\n";
echo "=====================================\n\n";

// 1. Check all possible .env file locations
$possibleEnvFiles = [
    '.env',
    '../.env',
    '../../.env',
    '../config/.env',
    __DIR__ . '/.env',
    __DIR__ . '/../.env',
    __DIR__ . '/../../.env'
];

echo "1. Checking all .env file locations:\n";
foreach ($possibleEnvFiles as $file) {
    $realPath = realpath($file);
    if (file_exists($file)) {
        echo "✓ Found: $file";
        if ($realPath) {
            echo " -> $realPath";
        }
        echo "\n";
        
        // Read and check content
        $content = file_get_contents($file);
        if (strpos($content, 'MAINTENANCE_MODE') !== false) {
            preg_match('/MAINTENANCE_MODE\s*=\s*(.+)/', $content, $matches);
            if ($matches) {
                echo "  Contains: MAINTENANCE_MODE=" . trim($matches[1]) . "\n";
            }
        }
    } else {
        echo "✗ Not found: $file\n";
    }
}

echo "\n2. Checking environment loading:\n";

// Try loading different env paths
$envPaths = ['../config/env.php', 'config/env.php', '../../config/env.php'];
$envLoaded = false;

foreach ($envPaths as $path) {
    if (file_exists($path)) {
        echo "Loading env from: $path\n";
        require_once $path;
        $envLoaded = true;
        break;
    }
}

if (!$envLoaded) {
    echo "No env.php file found!\n";
} else {
    if (function_exists('env')) {
        echo "✓ env() function available\n";
        echo "env('MAINTENANCE_MODE'): '" . env('MAINTENANCE_MODE', 'NOT_SET') . "'\n";
        echo "env('MAINTENANCE_SECRET'): '" . env('MAINTENANCE_SECRET', 'NOT_SET') . "'\n";
    } else {
        echo "✗ env() function not available\n";
    }
}

echo "\n3. Checking AdminSecurity class:\n";

// Load security class
if (file_exists('config/security.php')) {
    require_once 'config/security.php';
    
    if (class_exists('AdminSecurity')) {
        echo "✓ AdminSecurity class loaded\n";
        
        $maintenanceMode = AdminSecurity::isMaintenanceMode();
        echo "AdminSecurity::isMaintenanceMode(): " . ($maintenanceMode ? 'true' : 'false') . "\n";
        
        $testSecret = AdminSecurity::validateMaintenanceSecret('test123');
        echo "Test secret validation works: " . ($testSecret !== null ? 'yes' : 'no') . "\n";
        
    } else {
        echo "✗ AdminSecurity class not available\n";
    }
} else {
    echo "✗ config/security.php not found\n";
}

echo "\n4. Checking $_GET parameters:\n";
echo "Current GET params: " . print_r($_GET, true);

echo "\n5. Direct .env file content check:\n";
$envFile = '.env';
if (file_exists($envFile)) {
    echo "Content of $envFile:\n";
    echo str_repeat("-", 40) . "\n";
    echo file_get_contents($envFile);
    echo str_repeat("-", 40) . "\n";
} else {
    echo ".env file not found in current directory\n";
}

echo "\n6. Recommendations:\n";

// Check if there are conflicting settings
$foundMaintenanceTrue = false;
$foundMaintenanceFalse = false;

foreach ($possibleEnvFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (preg_match('/MAINTENANCE_MODE\s*=\s*true/i', $content)) {
            $foundMaintenanceTrue = true;
            echo "⚠️  Found MAINTENANCE_MODE=true in: $file\n";
        }
        if (preg_match('/MAINTENANCE_MODE\s*=\s*false/i', $content)) {
            $foundMaintenanceFalse = true;
            echo "✓ Found MAINTENANCE_MODE=false in: $file\n";
        }
    }
}

if ($foundMaintenanceTrue && $foundMaintenanceFalse) {
    echo "\n❌ CONFLICT DETECTED: Multiple .env files with different MAINTENANCE_MODE settings\n";
    echo "Solution: Make sure all .env files have MAINTENANCE_MODE=false\n";
} elseif ($foundMaintenanceTrue) {
    echo "\n❌ MAINTENANCE_MODE is set to true somewhere\n";
    echo "Solution: Change all instances to MAINTENANCE_MODE=false\n";
} elseif (!$foundMaintenanceTrue && !$foundMaintenanceFalse) {
    echo "\n⚠️  No MAINTENANCE_MODE setting found in any .env file\n";
    echo "Solution: Add MAINTENANCE_MODE=false to your main .env file\n";
}

echo "\nQuick fix command:\n";
echo "Run this in your terminal:\n";
echo "echo 'MAINTENANCE_MODE=false' >> .env\n";
echo "Or edit .env manually and ensure it contains: MAINTENANCE_MODE=false\n";

?>

<?php
// admin/force-disable-maintenance.php - Force disable maintenance mode

echo "Force Disable Maintenance Mode\n";
echo "==============================\n\n";

// Find and modify all .env files
$envFiles = ['.env', '../.env', '../../.env'];
$modified = [];

foreach ($envFiles as $file) {
    if (file_exists($file)) {
        echo "Processing: $file\n";
        
        $content = file_get_contents($file);
        $original = $content;
        
        // Replace any maintenance mode settings
        $content = preg_replace('/MAINTENANCE_MODE\s*=\s*true/i', 'MAINTENANCE_MODE=false', $content);
        $content = preg_replace('/MAINTENANCE_MODE\s*=\s*TRUE/i', 'MAINTENANCE_MODE=false', $content);
        $content = preg_replace('/MAINTENANCE_MODE\s*=\s*1/', 'MAINTENANCE_MODE=false', $content);
        
        // If no MAINTENANCE_MODE setting exists, add it
        if (strpos($content, 'MAINTENANCE_MODE') === false) {
            $content .= "\n# Force disable maintenance mode\nMAINTENANCE_MODE=false\n";
        }
        
        if ($content !== $original) {
            if (file_put_contents($file, $content)) {
                echo "✓ Modified: $file\n";
                $modified[] = $file;
            } else {
                echo "✗ Failed to modify: $file\n";
            }
        } else {
            echo "- No changes needed: $file\n";
        }
    }
}

if (empty($modified)) {
    echo "\nNo files were modified. Creating new .env file...\n";
    $newContent = "# Admin Panel Configuration\nMAINTENANCE_MODE=false\nMAINTENANCE_SECRET=disabled\n";
    
    if (file_put_contents('.env', $newContent)) {
        echo "✓ Created .env file with maintenance disabled\n";
    } else {
        echo "✗ Failed to create .env file\n";
    }
} else {
    echo "\nModified files: " . implode(', ', $modified) . "\n";
}

echo "\nClearing any cached environment variables...\n";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ OPCache cleared\n";
}

// Test the fix
echo "\nTesting the fix...\n";
try {
    if (file_exists('config/env.php')) {
        // Clear any previously loaded env
        unset($_ENV, $_SERVER['ENV_LOADED']);
        require_once 'config/env.php';
        
        if (function_exists('env')) {
            $mode = env('MAINTENANCE_MODE', 'not_set');
            echo "Current MAINTENANCE_MODE value: '$mode'\n";
            
            if ($mode === 'false' || $mode === false || $mode === '0') {
                echo "✓ Maintenance mode is now disabled\n";
            } else {
                echo "⚠️  Maintenance mode value is: '$mode' (should be 'false')\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error testing: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Try accessing your admin panel now:\n";
echo "http://localhost/QAD_PROJECT/QAD_AdminSite/\n";
echo "\nIf you still see maintenance mode, there might be:\n";
echo "1. Browser caching - try Ctrl+F5 or incognito mode\n";
echo "2. Server-side caching - restart your web server\n";
echo "3. Multiple .env files loading in different order\n";
?>