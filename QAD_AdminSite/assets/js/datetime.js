// assets/js/datetime.js
function updateDateTime() {
    const dateTimeElement = document.getElementById('dateTime');
    if (!dateTimeElement) return;

    const now = new Date();
    
    // Format: Mon, Jan 15, 2024 | 3:45:30 PM
    const options = {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    };
    
    const formattedDate = now.toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
    
    const formattedTime = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true
    });
    
    dateTimeElement.textContent = `${formattedDate} | ${formattedTime}`;
}

// Update immediately on load
document.addEventListener('DOMContentLoaded', function() {
    updateDateTime();
    // Update every second
    setInterval(updateDateTime, 1000);
});