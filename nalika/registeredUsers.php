<?php
include "connection.php";
include_once "auth.php";
require_once "permissions.php";

$isMemberSide = in_array(strtolower(trim($loggedRole ?? '')), ['member', 'trainee']) && stripos($loggedUsername ?? '', 'admin') === false;

// Auto-fix columns if needed
$phoneColRes = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'phone'");
if ($phoneColRes && $pRow = mysqli_fetch_assoc($phoneColRes)) {
    if (stripos($pRow['Type'], 'int') !== false) {
        mysqli_query($conn, "ALTER TABLE users MODIFY phone VARCHAR(30) NOT NULL");
    }
}
$colCard = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'card'");
if (!$colCard || mysqli_num_rows($colCard) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN card VARCHAR(50) DEFAULT 'Diamond' AFTER area");
    mysqli_query($conn, "UPDATE users SET card = 'Diamond' WHERE card IS NULL OR card = ''");
} else {
    @mysqli_query($conn, "ALTER TABLE users MODIFY COLUMN card VARCHAR(50) DEFAULT 'Diamond'");
}
$colCategory = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'category'");
if (!$colCategory || mysqli_num_rows($colCategory) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN category VARCHAR(50) DEFAULT 'B' AFTER card");
    mysqli_query($conn, "UPDATE users SET category = 'B' WHERE category IS NULL OR category = ''");
}
?>

<!doctype html>
<html lang="en">

<?php
include "header.php";
?>

<style>
    th[onclick] {
        user-select: none;
    }

    th[onclick]:hover {
        background-color: #f1f1f1;
    }

    .badge-card {
        background: rgba(13, 110, 253, 0.15);
        color: #60a5fa;
        border: 1px solid rgba(13, 110, 253, 0.3);
        font-size: 0.8rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-block;
    }

    .badge-category {
        background: rgba(168, 85, 247, 0.15);
        color: #c084fc;
        border: 1px solid rgba(168, 85, 247, 0.3);
        font-size: 0.8rem;
        font-weight: 600;
        padding: 3px 8px;
        border-radius: 6px;
        display: inline-block;
    }
</style>

<body>

    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>


    <!-- Start Welcome area -->
    <?php
    include "mainTopBar.php";
    ?>
    <!-- Static Table Start -->
    <div class="static-table-area mg-t-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="sparkline12-list mg-b-15">
                        <div class="sparkline12-hd">
                            <div class="d-flex justify-content-between align-items-center flex-wrap" style="padding-bottom: 20px;">

                                <!-- Left: Heading -->
                                <div>
                                    <h5 class="mb-0">User Management</h5>
                                </div>

                                <!-- Right: Search -->
                                <div class="mt-2 mt-lg-0" style="width: 300px;">
                                    <div class="nk-header-search">
                                        <input type="text" id="searchInput" class="nk-search-input form-control" placeholder="Search...">
                                        <i class="bi bi-search nk-search-icon"></i>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="sparkline12-graph">
                            <div class="table-responsive static-table-list">
                                <table class="table table-hover table-bordered">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>#</th>
                                            <th onclick="sortTable('fullName')" style="cursor:pointer;">Full Name</th>
                                            <th onclick="sortTable('cardCategory')" style="cursor:pointer;">Card &amp; Category</th>
                                            <th onclick="sortTable('email')" style="cursor:pointer;">Email</th>
                                            <th onclick="sortTable('phone')" style="cursor:pointer;">Phone</th>
                                            <th onclick="sortTable('area')" style="cursor:pointer;">Dars Area</th>
                                            <th onclick="sortTable('status')" style="cursor:pointer;">Status</th>
                                            <th onclick="sortTable('role')" style="cursor:pointer;">Role</th>
                                            <?php if (!$isMemberSide): ?>
                                            <th style="min-width:210px; width:210px;">Actions</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody id="userTableBody">
                                        <!-- Rows will be injected here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
include "footer.php"; ?>


    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                </div>

                <div class="modal-body">
                    <form id="editUserForm">

                        <!-- Hidden User ID -->
                        <input type="hidden" id="editUserId" name="id">

                        <div class="row">
                            <div class="col-sm-6 mb-3 text-start">
                                <label class="form-label" for="edit-firstName">First Name</label>
                                <input type="text" name="firstName" id="edit-firstName" class="form-control" placeholder="Enter first name" required />
                            </div>
                            <div class="col-sm-6 mb-3 text-start">
                                <label class="form-label" for="edit-lastName">Last Name</label>
                                <input type="text" name="lastName" id="edit-lastName" class="form-control" placeholder="Enter last name" required />
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 mb-3 text-start">
                                <label class="form-label" for="editCard">Card</label>
                                <select name="card" id="editCard" class="form-control" required>
                                    <option value="Diamond">Diamond</option>
                                    <option value="Gold">Gold</option>
                                    <option value="Silver">Silver</option>
                                    <option value="Metal">Metal</option>
                                </select>
                            </div>
                            <div class="col-sm-6 mb-3 text-start">
                                <label class="form-label" for="editCategory">Category</label>
                                <select name="category" id="editCategory" class="form-control" required>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control">
                        </div>

                        <div class="form-group mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone" id="editPhone" class="form-control">
                        </div>

                        <div class="form-group mb-3">
                            <label>Dars Area</label>
                            <select name="area" id="editArea" class="form-control">
                                <option value="" disabled selected>Select your Dars Area</option>
                                <?php
                                $areasResult = mysqli_query($conn, "SELECT areaName FROM dars_areas ORDER BY areaName ASC");
                                $areasList = [];
                                if ($areasResult && mysqli_num_rows($areasResult) > 0) {
                                    while ($row = mysqli_fetch_assoc($areasResult)) {
                                        $areasList[] = $row['areaName'];
                                    }
                                }
                                if (empty($areasList)) {
                                    $areasList = [
                                        "Gulshan Colony",
                                        "PM Colony",
                                        "Asifabad Colony",
                                        "Anwar Chowk",
                                        "Rawalpindi"
                                    ];
                                }
                                foreach ($areasList as $aName):
                                ?>
                                  <option value="<?php echo htmlspecialchars($aName); ?>"><?php echo htmlspecialchars($aName); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label>Role</label>
                            <select name="type" id="editRole" class="form-control" required>
                                <option value="" disabled>Select type</option>
                                <option value="member">Member</option>
                                <option value="trainee">Trainee</option>
                                <option value="committee">Committee Member</option>
                                <option value="itHead">IT Head</option>
                                <option value="researchHead">Research Head</option>
                                <option value="representative">Representative</option>
                                <option value="admin">Admin</option>
                                <option value="MD">MD</option>
                                <option value="DG">DG</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label>Status</label>
                            <select name="status" id="editStatus" class="form-control">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>

                    </form>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="updateUser()"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p id="deleteMessage">Are you sure you want to delete this user?</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        let currentSort = {
            column: null,
            asc: true
        };

        function sortTable(column) {
            if (currentSort.column === column) {
                currentSort.asc = !currentSort.asc;
            } else {
                currentSort.column = column;
                currentSort.asc = true;
            }

            users.sort((a, b) => {
                let valA = getSortValue(a, column);
                let valB = getSortValue(b, column);

                if (valA < valB) return currentSort.asc ? -1 : 1;
                if (valA > valB) return currentSort.asc ? 1 : -1;
                return 0;
            });

            renderUsers(users);
        }

        function getSortValue(user, column) {
            switch (column) {
                case 'fullName':
                    return `${user.firstName} ${user.lastName}`.toLowerCase();
                case 'cardCategory':
                    return `${user.card || ''} ${user.category || ''}`.toLowerCase();
                case 'email':
                    return user.email.toLowerCase();
                case 'phone':
                    return user.phone || '';
                case 'area':
                    return (user.area || '').toLowerCase();
                case 'status':
                    return (user.status || '').toLowerCase();
                case 'role':
                    return (user.role || user.roles || '').toLowerCase();
                default:
                    return '';
            }
        }

        // Real-time search
        document.getElementById('searchInput').addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();

            const filtered = users.filter(user => {
                const fullName  = (user.firstName + ' ' + user.lastName).toLowerCase();
                const firstName = (user.firstName || '').toLowerCase();
                const lastName  = (user.lastName  || '').toLowerCase();
                const email     = (user.email     || '').toLowerCase();
                const area      = (user.area      || '').toLowerCase();
                const cardCat   = ((user.card || '') + ' ' + (user.category || '')).toLowerCase();
                return fullName.includes(query) || firstName.includes(query) ||
                       lastName.includes(query) || email.includes(query) || area.includes(query) || cardCat.includes(query);
            });

            renderUsers(filtered);
        });
    </script>

    <script>
        let selectedUserId = null;
        let deleteModal = null;

        // Initialize modal once
        document.addEventListener("DOMContentLoaded", function() {
            deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        });

        // Open modal Delete confirmation
        function deleteUser(id) {
            selectedUserId = id;
            const user = (typeof users !== 'undefined') ? users.find(u => u.id == id) : null;
            const displayName = user ? `${user.firstName} ${user.lastName}` : `User #${id}`;

            document.getElementById("deleteMessage").innerText =
                `Are you sure you want to delete ${displayName}?`;

            deleteModal.show();
        }

        // Confirm delete
        document.getElementById("confirmDeleteBtn").addEventListener("click", function() {
            if (!selectedUserId) return;

            fetch(`deleteUser.php?id=${selectedUserId}`)
                .then(response => response.text())
                .then(data => {
                    console.log(data); // "Deleted"

                    loadUsers(); // refresh table
                    deleteModal.hide(); // close modal
                })
                .catch(error => {
                    console.error("Delete error:", error);
                });

            selectedUserId = null;
        });
    </script>

    <script>
        let users = []; // GLOBAL

        const roleLabels = {
            admin: "Admin",
            administrator: "Administrator",
            MD: "MD",
            DG: "DG",
            member: "Member",
            trainee: "Trainee",
            committee: "Committee Member",
            itHead: "IT Head",
            researchHead: "Research Head",
            representative: "Representative"
        };

        async function loadUsers() {
            try {
                const response = await fetch("fetchUsers.php");
                users = await response.json();
                renderUsers(users);
            } catch (error) {
                console.error("Error loading users:", error);
            }
        }

        function viewUser(id) {
            window.location.href = `userProfile.php?id=${id}`;
        }

        let editModal;

        document.addEventListener("DOMContentLoaded", function() {
            editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        });

        function editUser(id) {
            const user = users.find(u => u.id == id);
            if (!user) return;
            document.getElementById('editUserId').value = user.id;
            document.getElementById('edit-firstName').value = user.firstName || '';
            document.getElementById('edit-lastName').value = user.lastName || '';
            document.getElementById('editCard').value = user.card || 'Diamond';
            document.getElementById('editCategory').value = user.category || 'B';
            document.getElementById('editEmail').value = user.email || '';
            document.getElementById('editPhone').value = (user.phone == '2147483647') ? '' : (user.phone || '');
            document.getElementById('editArea').value = user.area || '';
            document.getElementById('editRole').value = user.role || user.roles || 'member';
            if (document.getElementById('editStatus')) {
                document.getElementById('editStatus').value = user.status || 'Active';
            }

            // Show the modal
            editModal.show();
        }

        function updateUser() {
            const form = document.getElementById('editUserForm');
            const fn = (document.getElementById('edit-firstName').value || '').trim();
            const ln = (document.getElementById('edit-lastName').value || '').trim();
            const ph = (document.getElementById('editPhone').value || '').trim();

            if (/[0-9]/.test(fn)) {
                alert("First Name: Only letters allowed. No digits permitted.");
                document.getElementById('edit-firstName').focus();
                return;
            }
            if (/[0-9]/.test(ln)) {
                alert("Last Name: Only letters allowed. No digits permitted.");
                document.getElementById('edit-lastName').focus();
                return;
            }
            if (/[a-zA-Z]/.test(ph)) {
                alert("Phone: Only numbers allowed. No letters permitted.");
                document.getElementById('editPhone').focus();
                return;
            }

            const data = new FormData(form);

            fetch('updateUser.php', {
                    method: 'POST',
                    body: data
                })
                .then(res => res.text())
                .then(res => {
                    // Hide the edit modal first
                    editModal.hide();

                    // Insert modal HTML into body
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = res;
                    document.body.appendChild(tempDiv.firstElementChild);

                    // Show the success modal
                    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                    successModal.show();

                    // When success modal is hidden, refresh table and remove modal from DOM
                    document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                        loadUsers(); // refresh table
                        document.getElementById('successModal').remove(); // clean up
                    });
                })
                .catch(err => console.error(err));
        }

        loadUsers();

        const isMemberSide = <?php echo $isMemberSide ? 'true' : 'false'; ?>;

        function renderUsers(list) {
            const tableBody = document.getElementById("userTableBody");
            tableBody.innerHTML = "";

            const totalCols = isMemberSide ? 8 : 9;

            if (!list || list.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="${totalCols}" class="text-center">No users found</td></tr>`;
                return;
            }

            list.forEach((user, index) => {
                const actionCell = isMemberSide ? '' : `
                    <td>
                        <div class="d-flex gap-1 align-items-center flex-nowrap">
                            <a href="userProfile.php?id=${user.id}" class="btn btn-sm btn-info text-white" style="font-size:0.75rem; padding:4px 9px; font-weight:600;" title="View Profile">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})" style="font-size:0.75rem; padding:4px 9px; font-weight:600;" title="Edit User">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})" style="font-size:0.75rem; padding:4px 9px; font-weight:600;" title="Delete User">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </td>`;

                tableBody.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${user.firstName} ${user.lastName}</td>
                    <td><span class="badge-card">${user.card || 'Diamond'}</span> <span style="opacity:0.5; margin:0 3px;">|</span> <span class="badge-category">${user.category || 'B'}</span></td>
                    <td>${user.email}</td>
                    <td>${user.phone == '2147483647' ? '<span class="text-warning" title="Number truncated by old INT limit. Click Edit to enter real number.">2147483647 <i class="bi bi-exclamation-triangle-fill text-warning ms-1" style="font-size:0.75rem;"></i></span>' : (user.phone || '-')}</td>
                    <td>${user.area ?? '-'}</td>
                    <td>
                        <span class="badge ${user.status === 'Active' ? 'badge-success' : 'badge-secondary'}">
                            ${user.status ?? 'N/A'}
                        </span>
                    </td>
                    <td>${roleLabels[user.role] ?? roleLabels[user.roles] ?? user.role ?? '-'}</td>
                    ${actionCell}
                </tr>`;
            });
        }
    </script>
</body>
</html>