<!-- views/partials/admin_head.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= $csrfToken ?? '' ?>">
    <title><?= $pageTitle ?? 'Admin Panel' ?> - School Directory Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="/assets/css/all.min.css">
    <style>
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
        .content-blur { backdrop-filter: blur(8px); }
        .security-alert { animation: pulse 2s infinite; }
        .loading-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>