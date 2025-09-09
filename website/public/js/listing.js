// Search only when pressing Enter
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                const params = new URLSearchParams(window.location.search);
                const searchTerm = searchInput.value.trim();

                if (searchTerm === '') {
                    params.delete('search');
                } else {
                    params.set('search', searchTerm);
                }

                params.set('page', 1); // Reset to first page on new search
                window.location.search = params.toString();
            }
        });
    }
});

// Entries per page change
document.getElementById('entriesPerPage').addEventListener('change', function() {
    const limit = this.value;
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('limit', limit);
    currentUrl.searchParams.set('page', '1'); // Reset to first page
    window.location.href = currentUrl.toString();
});

// Column sorting
document.querySelectorAll('[data-column]').forEach(header => {
    header.addEventListener('click', function() {
        const column = this.dataset.column;
        const currentUrl = new URL(window.location);
        const currentSort = currentUrl.searchParams.get('sort');
        const currentOrder = currentUrl.searchParams.get('order') || 'asc';
        
        // Toggle order if same column, otherwise default to asc
        if (currentSort === column && currentOrder === 'asc') {
            currentUrl.searchParams.set('order', 'desc');
        } else {
            currentUrl.searchParams.set('order', 'asc');
        }
        currentUrl.searchParams.set('sort', column);
        currentUrl.searchParams.set('page', '1');
        
        window.location.href = currentUrl.toString();
    });
});

// Set current values on page load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Set search input value
    const searchInput = document.getElementById('searchInput');
    const searchParam = urlParams.get('search');
    if (searchParam && searchInput) {
        searchInput.value = searchParam;
    }
    
    // Set entries per page value
    const entriesSelect = document.getElementById('entriesPerPage');
    const limitParam = urlParams.get('limit') || '10';
    if (entriesSelect) {
        entriesSelect.value = limitParam;
    }

    // Update sort arrows
    const currentSort = urlParams.get('sort');
    const currentOrder = urlParams.get('order') || 'asc';
    
    if (currentSort) {
        const sortHeader = document.querySelector(`[data-column="${currentSort}"]`);
        if (sortHeader) {
            const arrow = sortHeader.querySelector('svg');
            if (arrow) {
                arrow.classList.remove('text-gray-400');
                arrow.classList.add('text-blue-600');
                
                if (currentOrder === 'desc') {
                    arrow.style.transform = 'rotate(180deg)';
                }
            }
        }
    }
});
