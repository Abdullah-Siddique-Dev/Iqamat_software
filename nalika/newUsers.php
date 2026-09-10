<?php
include "connection.php";
include "auth.php";
require_once "permissions.php";

$isMemberSide = in_array(strtolower(trim($loggedRole ?? '')), ['member', 'trainee']) && stripos($loggedUsername ?? '', 'admin') === false;
if ($isMemberSide || !hasPermission("newUsers")) {
    header("Location: dashboard.php");
    exit();
}
?>

<!doctype html>
<html lang="en">

<?php include "header.php"; ?>

<body>

    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>
    <?php include "mainTopBar.php"; ?>

    <div class="static-table-area mg-t-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="sparkline12-list mg-b-15">
                        <div class="sparkline12-hd">
                            <div class="d-flex justify-content-between align-items-center flex-wrap" style="padding-bottom: 20px;">
                                <div>
                                    <h5 class="mb-0">New Users — Pending Approval</h5>
                                </div>
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
                                            <th>Full Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Area</th>
                                            <th>Role</th>
                                            <th>Registered</th>
                                            <th style="width:140px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="newUserTableBody">
                                        <!-- injected by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include "footer.php"; ?>

    <!-- Confirmation Modal (shared for approve & reject) -->
    <div class="modal fade" id="confirmActionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmActionTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmActionMessage">Are you sure?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn" id="confirmActionBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let newUsers = [];
        let pendingAction = null; // { id, type: 'approve' | 'reject' }
        let confirmModal;

        document.addEventListener("DOMContentLoaded", function () {
            confirmModal = new bootstrap.Modal(document.getElementById('confirmActionModal'));
            loadNewUsers();
        });

        async function loadNewUsers() {
            try {
                const response = await fetch("fetchNewUsers.php");
                newUsers = await response.json();
                renderNewUsers(newUsers);
            } catch (error) {
                console.error("Error loading new users:", error);
            }
        }

        function buildNewUserRow(user, index) {
            const fullName = `${user.firstName} ${user.lastName}`;
            return `
                <tr id="row-${user.id}">
                    <td>${index + 1}</td>
                    <td>${fullName}</td>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td>${user.phone ?? '-'}</td>
                    <td>${user.area ?? '-'}</td>
                    <td>${user.role ?? '-'}</td>
                    <td>${user.created_at ?? '-'}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-success me-1" title="Approve" onclick="askConfirm(${user.id}, 'approve', '${fullName}')">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" title="Reject" onclick="askConfirm(${user.id}, 'reject', '${fullName}')">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </td>
                </tr>`;
        }

        function renderNewUsers(list) {
            const tableBody = document.getElementById("newUserTableBody");
            tableBody.innerHTML = "";

            if (!list || list.length === 0) {
                tableBody.innerHTML = `<tr><td colspan="9" class="text-center">No pending users 🎉</td></tr>`;
                return;
            }

            list.forEach((user, index) => {
                tableBody.innerHTML += buildNewUserRow(user, index);
            });
        }

        // Search
        document.getElementById('searchInput').addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const filtered = newUsers.filter(user => {
                const fullName = (user.firstName + ' ' + user.lastName).toLowerCase();
                const email = (user.email || '').toLowerCase();
                const username = (user.username || '').toLowerCase();
                return fullName.includes(query) || email.includes(query) || username.includes(query);
            });
            renderNewUsers(filtered);
        });

        function askConfirm(id, type, fullName) {
            pendingAction = { id, type };

            const title = document.getElementById('confirmActionTitle');
            const message = document.getElementById('confirmActionMessage');
            const btn = document.getElementById('confirmActionBtn');

            if (type === 'approve') {
                title.innerText = "Approve User";
                message.innerText = `Are you sure you want to approve ${fullName}? They will be able to sign in immediately.`;
                btn.className = "btn btn-success";
                btn.innerText = "Approve";
            } else {
                title.innerText = "Reject User";
                message.innerText = `Are you sure you want to reject ${fullName}? They will not be able to sign in.`;
                btn.className = "btn btn-danger";
                btn.innerText = "Reject";
            }

            confirmModal.show();
        }

        document.getElementById('confirmActionBtn').addEventListener('click', function () {
            if (!pendingAction) return;

            const { id, type } = pendingAction;
            const url = type === 'approve' ? 'approveUser.php' : 'rejectUser.php';

            const formData = new FormData();
            formData.append('id', id);

            fetch(url, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    confirmModal.hide();
                    if (data.success) {
                        document.getElementById(`row-${id}`)?.remove();
                        newUsers = newUsers.filter(u => u.id != id);
                        if (newUsers.length === 0) renderNewUsers([]);
                    } else {
                        alert(data.message || "Something went wrong.");
                    }
                    pendingAction = null;
                })
                .catch(err => {
                    console.error(err);
                    confirmModal.hide();
                });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>
</body>

</html>