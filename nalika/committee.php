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

<?php include "header.php"; ?>

<style>
    th[onclick] {
        user-select: none;
    }

    th[onclick]:hover {
        background-color: #f1f1f1;
    }

    /* Role badges */

    input#editRoleName {
        background: transparent !important;
        ;
    }

    .role-pill {
        padding: 3px 11px;
        border-radius: 20px;
        font-size: .72rem;
        font-weight: 700;
        display: inline-block;
    }

    .role-trainee {
        background: #cce5ff;
        color: #004085;
    }

    .role-committee {
        background: #d4edda;
        color: #155724;
    }

    .role-itHead {
        background: #e2d9f3;
        color: #4a235a;
    }

    .role-researchHead {
        background: #fff3cd;
        color: #856404;
    }

    .role-representative {
        background: #f8d7da;
        color: #721c24;
    }

    .role-MD {
        background: #d1ecf1;
        color: #0c5460;
    }

    .role-DG {
        background: #ffeeba;
        color: #856404;
    }
</style>

<body>
    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>
    <?php include "mainTopBar.php"; ?>

    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcome-list single-page-breadcome">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <div class="breadcome-heading">
                                    <?php if (hasFeature("addCommittee")) { ?>
                                        <button class="btn btn-success mb-3" id="addMemberBtn">
                                            <i class="bi bi-person-plus me-1"></i> Add to Committee
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <ul class="breadcome-menu">
                                    <li><a href="#">Home</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Committee</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Table -->
    <div class="static-table-area mg-t-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="sparkline12-list mg-b-15">
                        <div class="sparkline12-hd">
                            <div class="d-flex justify-content-between align-items-center flex-wrap"
                                style="padding-bottom:20px;">
                                <div>
                                    <h5 class="mb-0">Committee Members</h5>
                                </div>
                                <div class="mt-2 mt-lg-0" style="width:300px;">
                                    <div class="nk-header-search">
                                        <input type="text" id="searchInput"
                                            class="nk-search-input form-control"
                                            placeholder="Search...">
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
                                            <th onclick="sortTable('email')" style="cursor:pointer;">Email</th>
                                            <th onclick="sortTable('phone')" style="cursor:pointer;">Phone</th>
                                            <th onclick="sortTable('area')" style="cursor:pointer;">Area</th>
                                            <th onclick="sortTable('role')" style="cursor:pointer;">Role</th>
                                            <?php if (hasFeature("editCommittee") || hasFeature("deleteCommittee")) { ?>
                                                <th style="width:180px;">Actions</th>
                                            <?php } ?>
                                        </tr>
                                    </thead>
                                    <tbody id="committeeTableBody">
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-3">
                                                Loading…
                                            </td>
                                        </tr>
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

    <!-- ══ ADD TO COMMITTEE MODAL ══ -->
    <div class="modal fade" id="addCommitteeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Member to Committee</h5>
                </div>
                <div class="modal-body">
                    <form id="addCommitteeForm">

                        <div class="form-group mb-3">
                            <label class="form-label">Select Member <span class="text-danger">*</span></label>
                            <select name="user_id" id="addUserSelect" class="form-control" required>
                                <option value="" disabled selected>Loading members…</option>
                            </select>
                            <small class="text-muted">Only members not already in committee are shown.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Assign Role <span class="text-danger">*</span></label>
                            <select name="role" id="addRoleSelect" class="form-control" required>
                                <option value="" disabled selected>Select role</option>
                                <option value="trainee">Trainee</option>
                                <option value="committee">Committee Member</option>
                                <option value="itHead">IT Head</option>
                                <option value="researchHead">Research Head</option>
                                <option value="representative">Representative</option>
                                <option value="MD">Managing Director</option>
                                <option value="DG">Director General</option>
                            </select>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="addToCommittee()">Add</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ EDIT ROLE MODAL ══ -->
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Role</h5>
                </div>
                <div class="modal-body">
                    <form id="editRoleForm">
                        <input type="hidden" id="editRoleUserId" name="id">

                        <div class="form-group mb-3">
                            <label class="form-label">Member</label>
                            <input type="text" id="editRoleName" class="form-control" readonly
                                style="background:#f8f9fa;">
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" id="editRoleSelect" class="form-control" required>
                                <option value="trainee">Trainee</option>
                                <option value="committee">Committee Member</option>
                                <option value="itHead">IT Head</option>
                                <option value="researchHead">Research Head</option>
                                <option value="representative">Representative</option>
                                <option value="MD">Managing Director</option>
                                <option value="DG">Director General</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="updateRole()">Update</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ DELETE (Remove from Committee) MODAL ══ -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Remove from Committee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteMessage">Are you sure you want to remove this member from the committee?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Remove</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        const canEditCommittee = <?= hasFeature("editCommittee")   ? "true" : "false" ?>;
        const canDeleteCommittee = <?= hasFeature("deleteCommittee") ? "true" : "false" ?>;

        // ── Modal instances ──
        const addCommitteeModal = new bootstrap.Modal(document.getElementById('addCommitteeModal'));
        const editRoleModal = new bootstrap.Modal(document.getElementById('editRoleModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        // ── State ──
        let committeeList = []; // non-member users
        let allMembersList = []; // all users (for add combobox)
        let selectedRemoveId = null;
        let sortDirection = {};

        const roleLabels = {
            trainee: 'Trainee',
            committee: 'Committee Member',
            itHead: 'IT Head',
            researchHead: 'Research Head',
            representative: 'Representative',
            MD: 'Managing Director',
            DG: 'Director General',
        };

        const rolePillClass = {
            trainee: 'role-trainee',
            committee: 'role-committee',
            itHead: 'role-itHead',
            researchHead: 'role-researchHead',
            representative: 'role-representative',
            MD: 'role-MD',
            DG: 'role-DG',
        };

        // ── Open Add modal ──
        const addMemberBtn = document.getElementById('addMemberBtn');

        if (addMemberBtn) {
            addMemberBtn.addEventListener('click', () => {
                document.getElementById('addCommitteeForm').reset();
                populateAddUserDropdown();
                addCommitteeModal.show();
            });
        }

        // ── Populate add-user combobox with members only ──
        function populateAddUserDropdown() {
            const sel = document.getElementById('addUserSelect');
            // Only show users whose role is 'member' (not yet in committee)
            const members = allMembersList.filter(u => u.role === 'member');
            sel.innerHTML = members.length > 0 ?
                '<option value="" disabled selected>Select a member</option>' :
                '<option value="" disabled selected>No members available</option>';
            members.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.textContent = `${u.firstName} ${u.lastName} (@${u.username})`;
                sel.appendChild(opt);
            });
        }

        // ── Load all users from server ──
        async function loadAllUsers() {
            try {
                const res = await fetch('fetchUsers.php');
                allMembersList = await res.json();

                // Filter committee members: role === 'committee'
                committeeList = allMembersList.filter(u => u.role === 'committee');
                renderTable(committeeList);
            } catch (e) {
                console.error('Error loading users:', e);
                document.getElementById('committeeTableBody').innerHTML =
                    `<tr><td colspan="8" class="text-center text-danger">Failed to load data.</td></tr>`;
            }
        }

        // ── Render table ──
        function renderTable(list) {
            const tbody = document.getElementById('committeeTableBody');
            tbody.innerHTML = '';

            if (!list || list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-3">No committee members found.</td></tr>`;
                return;
            }

            list.forEach((u, i) => {
                const pillCls = rolePillClass[u.role] ?? 'role-committee';
                const lbl = roleLabels[u.role] ?? u.role;
                tbody.innerHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td><strong>${u.firstName} ${u.lastName}</strong></td>
                    <td><small class="text">${u.username}</small></td>
                    <td>${u.email}</td>
                    <td>${u.phone ?? '—'}</td>
                    <td>${(u.areaName && !/^\d+$/.test(u.areaName)) ? u.areaName : ((u.area && !/^\d+$/.test(u.area)) ? u.area : (u.areaName || u.area || '—'))}</td>
                    <td><span class="role-pill ${pillCls}">${lbl}</span></td>
<td>
    ${canEditCommittee ? `
    <button class="btn btn-sm btn-primary" onclick="openEditRole(${u.id})">
        <i class="bi bi-pencil-fill"></i> Edit Role
    </button>` : ''}
    ${canDeleteCommittee ? `
    <button class="btn btn-sm btn-danger" onclick="confirmRemove(${u.id})">
        <i class="bi bi-person-dash"></i> Remove
    </button>` : ''}
</td>
                </tr>`;
            });
        }

        // ── Real-time search ──
        document.getElementById('searchInput').addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            const filtered = committeeList.filter(u =>
                (u.firstName + ' ' + u.lastName).toLowerCase().includes(q) ||
                (u.username || '').toLowerCase().includes(q) ||
                (u.email || '').toLowerCase().includes(q) ||
                (u.role || '').toLowerCase().includes(q)
            );
            renderTable(filtered);
        });

        // ── Sort ──
        function sortTable(column) {
            sortDirection[column] = !sortDirection[column];
            committeeList.sort((a, b) => {
                let va = column === 'fullName' ?
                    (a.firstName + ' ' + a.lastName).toLowerCase() :
                    (a[column] ?? '').toString().toLowerCase();
                let vb = column === 'fullName' ?
                    (b.firstName + ' ' + b.lastName).toLowerCase() :
                    (b[column] ?? '').toString().toLowerCase();
                if (va < vb) return sortDirection[column] ? -1 : 1;
                if (va > vb) return sortDirection[column] ? 1 : -1;
                return 0;
            });
            renderTable(committeeList);
        }

        // ── ADD to committee (update role) ──
        function addToCommittee() {
            const userId = document.getElementById('addUserSelect').value;
            const role = document.getElementById('addRoleSelect').value;

            if (!userId || !role) {
                alert('Please select a member and a role.');
                return;
            }

            const data = new FormData();
            data.append('id', userId);
            data.append('role', role);

            fetch('updateUserRole.php', {
                    method: 'POST',
                    body: data
                })
                .then(r => r.text())
                .then(msg => {
                    addCommitteeModal.hide();
                    showSuccess(msg, loadAllUsers);
                })
                .catch(console.error);
        }

        // ── EDIT role modal ──
        function openEditRole(id) {
            const u = allMembersList.find(x => x.id == id);
            if (!u) return;

            document.getElementById('editRoleUserId').value = u.id;
            document.getElementById('editRoleName').value = `${u.firstName} ${u.lastName}`;
            document.getElementById('editRoleSelect').value = u.role;

            editRoleModal.show();
        }

        // ── UPDATE role ──
        function updateRole() {
            const form = document.getElementById('editRoleForm');
            const data = new FormData(form);

            fetch('updateUserRole.php', {
                    method: 'POST',
                    body: data
                })
                .then(r => r.text())
                .then(msg => {
                    editRoleModal.hide();
                    showSuccess(msg, loadAllUsers);
                })
                .catch(console.error);
        }

        // ── REMOVE from committee (set role back to 'member') ──
        function confirmRemove(id) {
            selectedRemoveId = id;
            const u = allMembersList.find(x => x.id == id);
            document.getElementById('deleteMessage').textContent =
                `Are you sure you want to remove "${u ? u.firstName + ' ' + u.lastName : 'this member'}" from the committee? Their role will be set back to Member.`;
            deleteModal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (!selectedRemoveId) return;

            const data = new FormData();
            data.append('id', selectedRemoveId);
            data.append('role', 'member'); // demote back to member

            fetch('updateUserRole.php', {
                    method: 'POST',
                    body: data
                })
                .then(r => r.text())
                .then(() => {
                    deleteModal.hide();
                    selectedRemoveId = null;
                    loadAllUsers();
                })
                .catch(console.error);
        });

        // ── Success modal (same pattern as rest of project) ──
        function showSuccess(msg, callback) {
            const successHTML = `
                <div class="modal fade" id="successModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-4">
                                <h5>${msg}</h5>
                                <button class="btn btn-primary mt-3" data-bs-dismiss="modal">OK</button>
                            </div>
                        </div>
                    </div>
                </div>`;
            const div = document.createElement('div');
            div.innerHTML = successHTML;
            document.body.appendChild(div.firstElementChild);

            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
            document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                document.getElementById('successModal').remove();
                if (callback) callback();
            });
        }

        // ── Init ──
        loadAllUsers();
    </script>

</body>

</html>