document.addEventListener("DOMContentLoaded", async () => {
    console.log("Manage Users JS running");

    try {
        const response = await fetch("/backend/get-users.php", {
            method: "GET",
            headers: { "Accept": "application/json" }
        });

        const data = await response.json();
        console.log("Fetched users:", data);

        // Helper to populate a table body
        function populateTable(tableSelector, users, role) {
            const tbody = document.querySelector(tableSelector + " tbody");
            if (!tbody) {
                console.warn(`Table body not found for selector: ${tableSelector}`);
                return;
            }
            tbody.innerHTML = ""; // Clear existing rows

            users.forEach(user => {
                const tr = document.createElement("tr");

                // Determine name, email, ID, and startDate based on role
                let name = "", email = "", id = "", startDate = "";
                switch (role) {
                    case "admin":
                        name = `${user.AdminFName} ${user.AdminSName}`;
                        email = user.AdminEmail;
                        id = user.AdminID;
                        startDate = user.AdminStartDate;
                        break;
                    case "vet":
                        name = `${user.VetFName} ${user.VetSName}`;
                        email = user.VetEmail;
                        id = user.VetID;
                        startDate = user.VetStartDate;
                        break;
                    case "client":
                        name = `${user.ClientFName} ${user.ClientLName}`;
                        email = user.ClientEmail;
                        id = user.ClientID;
                        startDate = user.ClientStartDate;
                        break;
                }

                // Create row with buttons
                tr.innerHTML = `
                    <td>${id}</td>
                    <td>${name}</td>
                    <td>${email}</td>
                    <td>${startDate}</td>
                    <td class="manage-user-action">
                        <button class="edit-btn" data-id="${id}" data-role="${role}" data-name="${name}" data-email="${email}">Edit</button>
                        <button class="delete-btn" data-id="${id}" data-role="${role}">Delete</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            // Attach delete button handlers
            const deleteButtons = tbody.querySelectorAll(".delete-btn");
            deleteButtons.forEach(btn => {
                btn.addEventListener("click", () => handleDelete(btn));
            });

            // Attach edit button handlers
            const editButtons = tbody.querySelectorAll(".edit-btn");
            editButtons.forEach(btn => {
                btn.addEventListener("click", () => handleEdit(btn));
            });
        }

        // Populate tables
        populateTable("#admin-table", data.admins, "admin");
        populateTable("#vet-table", data.vets, "vet");
        populateTable("#client-table", data.clients, "client");

        // --- Search functionality (filter in-memory users by ID / name / email) ---
        const searchInput = document.querySelector(".admin-search-input");
        const searchBtn = document.querySelector(".admin-search-btn");

        function safeLower(s) {
            return (s || "").toString().toLowerCase();
        }

        function filterAndPopulate(term) {
            const q = (term || "").toString().trim().toLowerCase();

            // If blank, restore full lists
            if (!q) {
                populateTable("#admin-table", data.admins, "admin");
                populateTable("#vet-table", data.vets, "vet");
                populateTable("#client-table", data.clients, "client");
                return;
            }

            // Filter helpers per role
            const adminFiltered = (data.admins || []).filter(u => {
                const id = safeLower(u.AdminID);
                const name = safeLower(`${u.AdminFName || ''} ${u.AdminSName || ''}`);
                const email = safeLower(u.AdminEmail);
                return id.includes(q) || name.includes(q) || email.includes(q);
            });

            const vetFiltered = (data.vets || []).filter(u => {
                const id = safeLower(u.VetID);
                const name = safeLower(`${u.VetFName || ''} ${u.VetSName || ''}`);
                const email = safeLower(u.VetEmail);
                return id.includes(q) || name.includes(q) || email.includes(q);
            });

            const clientFiltered = (data.clients || []).filter(u => {
                const id = safeLower(u.ClientID);
                const name = safeLower(`${u.ClientFName || ''} ${u.ClientLName || ''}`);
                const email = safeLower(u.ClientEmail);
                return id.includes(q) || name.includes(q) || email.includes(q);
            });

            populateTable("#admin-table", adminFiltered, "admin");
            populateTable("#vet-table", vetFiltered, "vet");
            populateTable("#client-table", clientFiltered, "client");
        }

        // Live search as user types
        if (searchInput) {
            searchInput.addEventListener('input', (e) => filterAndPopulate(e.target.value));
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    filterAndPopulate(searchInput.value);
                }
            });
        }

        // Button click (some markup nests the icon/button; guard existence)
        if (searchBtn) {
            searchBtn.addEventListener('click', (e) => {
                // Prevent accidentally submitting if button is inside a form
                e.preventDefault();
                filterAndPopulate(searchInput ? searchInput.value : '');
            });
        }

    } catch (error) {
        console.error("Error fetching users:", error);
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "Failed to load user data. Check console for details."
        });
    }

    // DELETE handler
    async function handleDelete(button) {
        const id = button.dataset.id;
        const role = button.dataset.role;

        const confirmed = await Swal.fire({
            icon: "warning",
            title: `Delete ${role}?`,
            text: "This action cannot be undone!",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "Cancel"
        });

        if (!confirmed.isConfirmed) return;

        try {
            const res = await fetch("/backend/delete-user.php", {
                method: "DELETE",
                headers: { "Content-Type": "application/json", "Accept": "application/json" },
                body: JSON.stringify({ id, role })
            });

            const result = await res.json();
            console.log("Delete response:", result);

            if (res.ok && result.status === "success") {
                const tr = button.closest("tr");
                tr.remove();
                Swal.fire({ icon: "success", title: "Deleted!", text: result.message, timer: 1200, showConfirmButton: false });
            } else {
                // Check for common MySQL foreign-key error indicators and show a friendlier message
                const msg = (result && result.message) ? result.message.toString().toLowerCase() : "";
                if (msg.includes("cannot delete or update a parent row") || msg.includes("foreign key constraint") || msg.includes("foreign key")) {
                    // Show the exact message requested when a FK constraint prevents deletion
                    Swal.fire({ icon: "error", title: "Invalid action!", text: "Cannot delete test users" });
                } else {
                    Swal.fire({ icon: "error", title: "Error", text: result.message || "Failed to delete user." });
                }
            }
        } catch (err) {
            console.error("Delete error:", err);
            Swal.fire({ icon: "error", title: "Error", text: "Network or server error while deleting user." });
        }
    }

    // EDIT handler
    async function handleEdit(button) {
        const id = button.dataset.id;
        const role = button.dataset.role;
        const currentName = button.dataset.name;
        const currentEmail = button.dataset.email;

        const { value: formValues } = await Swal.fire({
            title: `Edit ${role}`,
            html: `
                <input id="swal-name" class="swal2-input" placeholder="Name" value="${currentName}">
                <input id="swal-email" class="swal2-input" placeholder="Email" value="${currentEmail}">
                <select id="swal-role" class="swal2-select">
                    <option value="client" ${role==="client"?"selected":""}>Client</option>
                    <option value="admin" ${role==="admin"?"selected":""}>Admin</option>
                    <option value="vet" ${role==="vet"?"selected":""}>Vet</option>
                </select>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: "Save",
            preConfirm: () => {
                return {
                    name: document.getElementById("swal-name").value,
                    email: document.getElementById("swal-email").value,
                    newRole: document.getElementById("swal-role").value
                }
            }
        });

        if (!formValues) return;

        try {
            const res = await fetch("/backend/edit-user.php", {
                method: "POST",
                headers: { "Content-Type": "application/json", "Accept": "application/json" },
                body: JSON.stringify({
                    id,
                    role,
                    name: formValues.name,
                    email: formValues.email,
                    newRole: formValues.newRole
                })
            });

            const result = await res.json();
            console.log("Edit response:", result);

            if (res.ok && result.status === "success") {
                Swal.fire({ icon: "success", title: "Updated!", text: result.message, timer: 1200, showConfirmButton: false });
                // Optionally reload users
                location.reload();
            } else {
                Swal.fire({ icon: "error", title: "Error", text: result.message || "Failed to update user." });
            }
        } catch (err) {
            console.error("Edit error:", err);
            Swal.fire({ icon: "error", title: "Error", text: "Network or server error while updating user." });
        }
    }
});
