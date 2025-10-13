// assets/js/notification.js
document.addEventListener('DOMContentLoaded', function() {
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');

    // Notification dropdown toggle
    if (notificationBtn && notificationDropdown) {
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('hidden');
            // Close user menu if open
            if (userMenu) {
                userMenu.classList.add('hidden');
            }
        });
    }

    // User menu dropdown toggle
    if (userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('hidden');
            // Close notification dropdown if open
            if (notificationDropdown) {
                notificationDropdown.classList.add('hidden');
            }
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (notificationDropdown && !notificationBtn.contains(e.target) && !notificationDropdown.contains(e.target)) {
            notificationDropdown.classList.add('hidden');
        }
        if (userMenu && !userMenuBtn.contains(e.target) && !userMenu.contains(e.target)) {
            userMenu.classList.add('hidden');
        }
    });

    // Close dropdowns on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (notificationDropdown) notificationDropdown.classList.add('hidden');
            if (userMenu) userMenu.classList.add('hidden');
        }
    });

    // Auto-refresh notifications every 30 seconds
    setInterval(refreshNotifications, 30000);
});

// Mark all notifications as read
function markAllAsRead() {
    // Hide notification badge
    const badge = document.querySelector('.security-alert');
    if (badge) {
        badge.style.display = 'none';
    }

    // Clear notification dropdown content
    const notificationDropdown = document.getElementById('notificationDropdown');
    if (notificationDropdown) {
        const contentDiv = notificationDropdown.querySelector('.max-h-96');
        if (contentDiv) {
            contentDiv.innerHTML = `
                <div class="p-8 text-center">
                    <i class="fas fa-check-circle text-green-500 text-3xl mb-3"></i>
                    <p class="text-gray-500">All caught up!</p>
                    <p class="text-sm text-gray-400">No new notifications</p>
                </div>
            `;
        }
        
        // Hide the "mark all as read" button
        const footer = notificationDropdown.querySelector('.border-t');
        if (footer) {
            footer.style.display = 'none';
        }
    }

    // Optional: Send to backend to persist
    fetch('/admin/api/notifications/mark-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ mark_all: true })
    }).catch(err => console.error('Failed to mark notifications as read:', err));
}

// Refresh notifications from server
function refreshNotifications() {
    fetch('/admin/api/notifications')
        .then(response => response.json())
        .then(data => {
            updateNotificationBadge(data);
            updateNotificationDropdown(data);
        })
        .catch(err => console.error('Failed to refresh notifications:', err));
}

// Update notification badge count
function updateNotificationBadge(data) {
    const badge = document.querySelector('#notificationBtn .security-alert');
    const totalCount = (data.security_incidents || 0) + (data.pending_permissions || 0);
    
    if (badge) {
        if (totalCount > 0) {
            badge.textContent = totalCount > 9 ? '9+' : totalCount;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Update notification dropdown content
function updateNotificationDropdown(data) {
    const dropdown = document.getElementById('notificationDropdown');
    if (!dropdown) return;

    const contentDiv = dropdown.querySelector('.max-h-96');
    if (!contentDiv) return;

    let html = '';
    let hasNotifications = false;

    // Security incidents notification
    if (data.security_incidents && data.security_incidents > 0) {
        hasNotifications = true;
        html += `
            <div class="p-4 border-b border-gray-100 hover:bg-gray-50">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-500 text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900">Security Incidents</p>
                        <p class="text-sm text-gray-500">${data.security_incidents} security incident${data.security_incidents > 1 ? 's' : ''} detected today</p>
                        <p class="text-xs text-gray-400 mt-1">
                            <a href="/admin/security" class="text-blue-600 hover:text-blue-800">View Details →</a>
                        </p>
                    </div>
                </div>
            </div>
        `;
    }

    // Pending permissions notification
    if (data.pending_permissions && data.pending_permissions > 0) {
        hasNotifications = true;
        html += `
            <div class="p-4 border-b border-gray-100 hover:bg-gray-50">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-key text-blue-500 text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900">Pending Permissions</p>
                        <p class="text-sm text-gray-500">${data.pending_permissions} permission request${data.pending_permissions > 1 ? 's' : ''} awaiting approval</p>
                        <p class="text-xs text-gray-400 mt-1">
                            <a href="/admin/permissions" class="text-blue-600 hover:text-blue-800">Review Requests →</a>
                        </p>
                    </div>
                </div>
            </div>
        `;
    }

    // No notifications
    if (!hasNotifications) {
        html = `
            <div class="p-8 text-center">
                <i class="fas fa-check-circle text-green-500 text-3xl mb-3"></i>
                <p class="text-gray-500">All caught up!</p>
                <p class="text-sm text-gray-400">No new notifications</p>
            </div>
        `;
    }

    contentDiv.innerHTML = html;

    // Show/hide footer
    const footer = dropdown.querySelector('.border-t');
    if (footer) {
        footer.style.display = hasNotifications ? 'block' : 'none';
    }
}