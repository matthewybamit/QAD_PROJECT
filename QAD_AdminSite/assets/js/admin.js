/**
 * Admin Dashboard JavaScript
 * Handles common admin functionality, form interactions, and UI enhancements
 */

class AdminDashboard {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupFormValidation();
        this.autoRefreshData();
    }

    setupEventListeners() {
        // Sidebar toggle
        this.setupSidebarToggle();
        
        // Form submissions
        this.setupFormSubmissions();
        
        // Confirmation dialogs
        this.setupConfirmationDialogs();
        
        // Search functionality
        this.setupSearch();
        
        // Auto-dismiss alerts
        this.setupAlertDismissal();
        
        // Table interactions
        this.setupTableInteractions();
    }

    setupSidebarToggle() {
        const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
            });

            // Restore sidebar state
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }
        }
    }

    setupFormSubmissions() {
        // Handle CSRF token refresh for long sessions
        const forms = document.querySelectorAll('form[data-csrf-refresh]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                this.refreshCSRFToken(form);
            });
        });

        // Handle AJAX form submissions
        const ajaxForms = document.querySelectorAll('form[data-ajax]');
        ajaxForms.forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleAjaxForm(form);
            });
        });
    }

    setupConfirmationDialogs() {
        // Dangerous actions requiring confirmation
        const dangerousButtons = document.querySelectorAll('[data-confirm]');
        dangerousButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const message = button.dataset.confirm || 'Are you sure you want to perform this action?';
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        });

        // Delete confirmations
        const deleteButtons = document.querySelectorAll('[data-action="delete"]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const itemName = button.dataset.itemName || 'this item';
                if (!confirm(`Are you sure you want to delete ${itemName}? This action cannot be undone.`)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    }

    setupSearch() {
        const searchInputs = document.querySelectorAll('[data-search-table]');
        searchInputs.forEach(input => {
            const targetTable = document.querySelector(input.dataset.searchTable);
            if (targetTable) {
                input.addEventListener('input', () => {
                    this.filterTable(targetTable, input.value);
                });
            }
        });
    }

    filterTable(table, searchTerm) {
        const rows = table.querySelectorAll('tbody tr');
        const term = searchTerm.toLowerCase();

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });

        // Update "no results" message
        const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
        this.toggleNoResultsMessage(table, visibleRows.length === 0);
    }

    toggleNoResultsMessage(table, show) {
        let noResultsRow = table.querySelector('.no-results-row');
        
        if (show && !noResultsRow) {
            const tbody = table.querySelector('tbody');
            const colCount = table.querySelectorAll('thead th').length;
            noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results-row';
            noResultsRow.innerHTML = `<td colspan="${colCount}" class="text-center py-4 text-gray-500">No results found</td>`;
            tbody.appendChild(noResultsRow);
        } else if (!show && noResultsRow) {
            noResultsRow.remove();
        }
    }

    setupAlertDismissal() {
        // Auto-dismiss success messages
        const successAlerts = document.querySelectorAll('.bg-green-50, .bg-blue-50');
        successAlerts.forEach(alert => {
            setTimeout(() => {
                this.fadeOut(alert);
            }, 5000);
        });

        // Manual dismiss buttons
        const dismissButtons = document.querySelectorAll('[data-dismiss-alert]');
        dismissButtons.forEach(button => {
            button.addEventListener('click', () => {
                const alert = button.closest('.alert, .bg-green-50, .bg-red-50, .bg-blue-50, .bg-yellow-50');
                if (alert) {
                    this.fadeOut(alert);
                }
            });
        });
    }

    fadeOut(element) {
        element.style.transition = 'opacity 0.5s ease-out';
        element.style.opacity = '0';
        setTimeout(() => {
            element.remove();
        }, 500);
    }

    setupTableInteractions() {
        // Sort functionality
        const sortableHeaders = document.querySelectorAll('[data-sort]');
        sortableHeaders.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortTable(header);
            });
        });

        // Row selection
        const selectAllCheckboxes = document.querySelectorAll('[data-select-all]');
        selectAllCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const targetTable = document.querySelector(checkbox.dataset.selectAll);
                const rowCheckboxes = targetTable.querySelectorAll('tbody input[type="checkbox"]');
                rowCheckboxes.forEach(rowCheckbox => {
                    rowCheckbox.checked = e.target.checked;
                });
                this.updateBulkActions();
            });
        });

        // Individual row selection
        const rowCheckboxes = document.querySelectorAll('tbody input[type="checkbox"]');
        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateBulkActions();
            });
        });
    }

    sortTable(header) {
        const table = header.closest('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const column = header.dataset.sort;
        const currentDirection = header.dataset.sortDirection || 'asc';
        const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';

        // Clear other sort indicators
        table.querySelectorAll('[data-sort]').forEach(h => {
            h.classList.remove('sort-asc', 'sort-desc');
            h.removeAttribute('data-sort-direction');
        });

        // Set new sort direction
        header.dataset.sortDirection = newDirection;
        header.classList.add(`sort-${newDirection}`);

        // Sort rows
        rows.sort((a, b) => {
            const aValue = this.getCellValue(a, column);
            const bValue = this.getCellValue(b, column);
            
            if (newDirection === 'asc') {
                return aValue.localeCompare(bValue, undefined, { numeric: true });
            } else {
                return bValue.localeCompare(aValue, undefined, { numeric: true });
            }
        });

        // Reorder DOM
        rows.forEach(row => tbody.appendChild(row));
    }

    getCellValue(row, column) {
        const cell = row.querySelector(`[data-sort-value="${column}"]`) || 
                    row.children[parseInt(column)] ||
                    row.querySelector(`td:nth-child(${parseInt(column) + 1})`);
        return cell ? cell.textContent.trim() : '';
    }

    updateBulkActions() {
        const selectedCheckboxes = document.querySelectorAll('tbody input[type="checkbox"]:checked');
        const bulkActions = document.querySelectorAll('[data-bulk-action]');
        
        bulkActions.forEach(action => {
            action.style.display = selectedCheckboxes.length > 0 ? 'inline-block' : 'none';
        });

        // Update counter
        const counter = document.querySelector('[data-selection-counter]');
        if (counter) {
            counter.textContent = selectedCheckboxes.length;
        }
    }

    initializeComponents() {
        // Initialize tooltips if using a tooltip library
        this.initTooltips();
        
        // Initialize date pickers
        this.initDatePickers();
        
        // Initialize charts if data is available
        this.initCharts();
        
        // Initialize real-time updates
        this.initRealTimeUpdates();
    }

    initTooltips() {
        const tooltipElements = document.querySelectorAll('[title], [data-tooltip]');
        tooltipElements.forEach(element => {
            // Simple tooltip implementation
            element.addEventListener('mouseenter', (e) => {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip absolute bg-gray-800 text-white px-2 py-1 rounded text-sm z-50';
                tooltip.textContent = element.title || element.dataset.tooltip;
                document.body.appendChild(tooltip);
                
                const rect = element.getBoundingClientRect();
                tooltip.style.left = rect.left + 'px';
                tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
                
                element._tooltip = tooltip;
            });
            
            element.addEventListener('mouseleave', (e) => {
                if (element._tooltip) {
                    element._tooltip.remove();
                    delete element._tooltip;
                }
            });
        });
    }

    initDatePickers() {
        const dateInputs = document.querySelectorAll('input[type="date"], input[data-datepicker]');
        dateInputs.forEach(input => {
            // Add date validation
            input.addEventListener('change', () => {
                this.validateDate(input);
            });
        });
    }

    validateDate(input) {
        const date = new Date(input.value);
        const today = new Date();
        
        if (input.dataset.minDate === 'today' && date < today) {
            this.showError(input, 'Date cannot be in the past');
        }
    }

    initCharts() {
        // Placeholder for chart initialization
        // This would integrate with Chart.js or another charting library
        const chartElements = document.querySelectorAll('[data-chart]');
        chartElements.forEach(element => {
            // Initialize chart based on data attributes
            console.log('Chart initialization placeholder for', element);
        });
    }

    initRealTimeUpdates() {
        // Update timestamps to relative format
        this.updateTimestamps();
        
        // Set interval to update timestamps every minute
        setInterval(() => {
            this.updateTimestamps();
        }, 60000);
    }

    updateTimestamps() {
        const timestamps = document.querySelectorAll('[data-timestamp]');
        timestamps.forEach(element => {
            const timestamp = element.dataset.timestamp;
            const date = new Date(timestamp);
            element.textContent = this.timeAgo(date);
        });
    }

    timeAgo(date) {
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60
        };
        
        for (let [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return `${interval} ${unit}${interval > 1 ? 's' : ''} ago`;
            }
        }
        
        return 'Just now';
    }

    setupFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }

    validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showError(field, 'This field is required');
                isValid = false;
            } else {
                this.clearError(field);
            }
        });

        // Email validation
        const emailFields = form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showError(field, 'Please enter a valid email address');
                isValid = false;
            }
        });

        return isValid;
    }

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    showError(field, message) {
        this.clearError(field);
        
        field.classList.add('border-red-500');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-500 text-sm mt-1 field-error';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }

    clearError(field) {
        field.classList.remove('border-red-500');
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    handleAjaxForm(form) {
        const formData = new FormData(form);
        const url = form.action || window.location.href;
        
        // Show loading state
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = 'Processing...';
        
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification('Success', data.message, 'success');
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                this.showNotification('Error', data.message || 'An error occurred', 'error');
            }
        })
        .catch(error => {
            this.showNotification('Error', 'Network error occurred', 'error');
            console.error('Ajax form error:', error);
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        });
    }

    showNotification(title, message, type = 'info') {
        const notification = document.createElement('div');
        const bgColor = {
            success: 'bg-green-50 border-green-200 text-green-800',
            error: 'bg-red-50 border-red-200 text-red-800',
            warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
            info: 'bg-blue-50 border-blue-200 text-blue-800'
        };
        
        notification.className = `fixed top-4 right-4 p-4 rounded-md border z-50 ${bgColor[type]} notification`;
        notification.innerHTML = `
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="font-semibold">${title}</h4>
                    <p class="text-sm">${message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-500 hover:text-gray-700">×</button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    refreshCSRFToken(form) {
        // This would make an AJAX call to get a fresh CSRF token
        // Implementation depends on your backend setup
        console.log('CSRF token refresh for form', form);
    }

    autoRefreshData() {
        // Auto-refresh dashboard data every 5 minutes
        setInterval(() => {
            this.refreshDashboardStats();
        }, 300000);
    }

    refreshDashboardStats() {
        // Only refresh if we're on the dashboard page
        if (!document.querySelector('[data-page="dashboard"]')) {
            return;
        }

        fetch(window.location.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.stats) {
                this.updateStats(data.stats);
            }
        })
        .catch(error => {
            console.error('Auto-refresh error:', error);
        });
    }

    updateStats(stats) {
        Object.keys(stats).forEach(key => {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) {
                element.textContent = stats[key];
            }
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdminDashboard();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminDashboard;
}