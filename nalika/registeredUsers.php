<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
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
                                            <th onclick="sortTable('username')" style="cursor:pointer;">Username</th>
                                            <th onclick="sortTable('age')" style="cursor:pointer;">Age</th>
                                            <th onclick="sortTable('gender')" style="cursor:pointer;">Gender</th>
                                            <th onclick="sortTable('email')" style="cursor:pointer;">Email</th>
                                            <th onclick="sortTable('phone')" style="cursor:pointer;">Phone</th>
                                            <th onclick="sortTable('area')" style="cursor:pointer;">Dars Area</th>
                                            <th onclick="sortTable('status')" style="cursor:pointer;">Status</th>
                                            <th onclick="sortTable('role')" style="cursor:pointer;">Role</th>
                                            <th style="width:180px;">Actions</th>
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

                        <div class="form-group mb-3">
                            <label>Age</label>
                            <input type="number" name="age" id="editAge" class="form-control">
                        </div>

                        <div class="form-group mb-3">
                            <label>Gender</label>
                            <select name="gender" id="editGender" class="form-control">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
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
                            </select>
                        </div>

                    </form>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="updateUser()">Update</button>
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
                case 'username':
                    return user.username.toLowerCase();
                case 'age':
                    return parseInt(user.age) || 0;
                case 'gender':
                    return (user.gender || '').toLowerCase();
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
                return fullName.startsWith(query) || firstName.startsWith(query) ||
                       lastName.startsWith(query) || email.startsWith(query) || area.includes(query);
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

            // Optional: show username in modal
            const row = event.target.closest("tr");
            const username = row.children[1].innerText;

            document.getElementById("deleteMessage").innerText =
                `Are you sure you want to delete ${username}?`;

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
            alert("View user ID: " + id);
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
            document.getElementById('editAge').value = user.age || '';
            document.getElementById('editGender').value = user.gender || 'Male';
            document.getElementById('editEmail').value = user.email || '';
            document.getElementById('editPhone').value = user.phone || '';
            document.getElementById('editArea').value = user.area || '';
            document.getElementById('editRole').value = user.role || user.roles || '';

            // Show the modal
            editModal.show();
        }

        function updateUser() {
            const form = document.getElementById('editUserForm');
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

        function renderUsers(list) {
            const tableBody = document.getElementById("userTableBody");
            tableBody.innerHTML = "";

            if (!list || list.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="11" class="text-center">No users found</td></tr>`;
                return;
            }

            list.forEach((user, index) => {
                const canEditUser   = <?php echo hasFeature("editUser")   ? 'true' : 'false'; ?>;
                const canDeleteUser = <?php echo hasFeature("deleteUser") ? 'true' : 'false'; ?>;

                tableBody.innerHTML += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${user.firstName} ${user.lastName}</td>
                    <td>${user.username}</td>
                    <td>${user.age ?? '-'}</td>
                    <td>${user.gender ?? '-'}</td>
                    <td>${user.email}</td>
                    <td>${user.phone ?? '-'}</td>
                    <td>${user.area ?? '-'}</td>
                    <td>
                        <span class="badge ${user.status === 'Active' ? 'badge-success' : 'badge-secondary'}">
                            ${user.status ?? 'N/A'}
                        </span>
                    </td>
                    <td>${roleLabels[user.role] ?? roleLabels[user.roles] ?? user.role ?? '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="viewUser(${user.id})">View</button>
                        ${canEditUser   ? `<button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">Edit</button>`   : ''}
                        ${canDeleteUser ? `<button class="btn btn-sm btn-danger"  onclick="deleteUser(${user.id})">Delete</button>` : ''}
                    </td>
                </tr>`;
            });
        }
    </script>
</body>
</html>