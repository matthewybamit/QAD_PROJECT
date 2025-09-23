//assets/js/permissions.js

document.addEventListener("DOMContentLoaded", () => {
    // Elements
    const extendModal = document.getElementById("extendModal");
    const extendForm = document.getElementById("extendForm");
    const extendPermissionId = document.getElementById("extendPermissionId");

    const detailsModal = document.getElementById("detailsModal");
    const permissionDetails = document.getElementById("permissionDetails");

    // --- Extend Permission ---
    document.querySelectorAll(".extend-btn").forEach(button => {
        button.addEventListener("click", () => {
            const permissionId = button.dataset.permissionId;
            extendPermissionId.value = permissionId;

            extendModal.classList.remove("hidden");
            document.body.classList.add("overflow-hidden");
        });
    });

    window.closeExtendModal = function() {
        extendModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
    };

    // --- View Details ---
    document.querySelectorAll(".view-details-btn").forEach(button => {
        button.addEventListener("click", () => {
            const permissionData = JSON.parse(button.dataset.permission);
            renderPermissionDetails(permissionData);

            detailsModal.classList.remove("hidden");
            document.body.classList.add("overflow-hidden");
        });
    });

    window.closeDetailsModal = function() {
        detailsModal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
        permissionDetails.innerHTML = "";
    };

    // --- Render details in modal ---
    function renderPermissionDetails(permission) {
        permissionDetails.innerHTML = `
            <div class="space-y-4">
                <div>
                    <h4 class="text-sm font-medium text-gray-500">User</h4>
                    <p class="text-gray-900">${permission.user_name} (${permission.email})</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">School</h4>
                    <p class="text-gray-900">${permission.school_name}</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Reason</h4>
                    <p class="text-gray-900">${permission.reason || "N/A"}</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Status</h4>
                    <p class="text-gray-900 capitalize">${permission.status}</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Requested At</h4>
                    <p class="text-gray-900">${permission.requested_at}</p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Expires At</h4>
                    <p class="text-gray-900">${permission.expires_at || "-"}</p>
                </div>
                ${permission.approved_by_name ? `
                <div>
                    <h4 class="text-sm font-medium text-gray-500">Approved By</h4>
                    <p class="text-gray-900">${permission.approved_by_name}</p>
                </div>` : ""}
            </div>
        `;
    }
});
