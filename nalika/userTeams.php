<?php
include "connection.php";
include "auth.php";
?>
<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
    /* ── Role pills (inherit from committee) ── */

    div#teamInfoStrip {
        background: #152036;
        border-color: #081f4d;
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

    .role-member {
        background: #e2e3e5;
        color: #383d41;
    }

    .role-leader {
        background: #ffc107;
        color: #212529;
    }

    /* ── Team info strip ── */
    .team-info-strip {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
        padding: 14px 20px;
        border-radius: 10px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        margin-bottom: 16px;
        transition: all .2s;
    }

    .team-info-strip.hidden {
        display: none;
    }

    .tis-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: .9rem;
    }

    .tis-item i {
        font-size: 1.1rem;
    }

    .tis-label {
        color: #6c757d;
        font-size: .75rem;
        display: block;
    }

    .tis-val {
        font-weight: 700;
        font-size: .95rem;
    }

    /* ── Actions bar ── */
    .team-actions-bar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }

    .team-actions-bar button:disabled {
        opacity: .45;
        cursor: not-allowed;
    }

    /* ── Leader crown ── */
    .crown-icon {
        color: #ffc107;
        margin-right: 4px;
        font-size: .85rem;
    }

    /* ── Select2 / searchable dropdown override ── */
    .team-select-wrap {
        position: relative;
        min-width: 280px;
    }

    th[onclick] {
        user-select: none;
        cursor: pointer;
    }

    th[onclick]:hover {
        background-color: #f1f1f1;
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
                            <div class="col-lg-6 col-6">
                                <?php if (hasFeature("addTeam")) { ?>
                                    <button class="btn btn-success mb-3" id="btnCreateTeam">
                                        <i class="bi bi-plus-circle me-1"></i> Create Team
                                    </button>
                                <?php } ?>
                            </div>
                            <div class="col-lg-6 col-6">
                                <ul class="breadcome-menu">
                                    <li><a href="#">Home</a><span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Teams</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div class="static-table-area mg-t-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="sparkline12-list mg-b-15">
                        <div class="sparkline12-hd">

                            <!-- ── Top controls row ── -->
                            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:12px;">
                                <div class="team-select-wrap">
                                    <label class="form-label mb-1" style="font-size:.78rem;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.4px;">
                                        Select Team
                                    </label>
                                    <select id="teamSelectDropdown" class="form-select" style="min-width:280px;">
                                        <option value="" disabled selected>— Choose a team —</option>
                                    </select>
                                </div>
                                <div style="width:280px;">
                                    <label class="form-label mb-1" style="font-size:.78rem;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.4px;">
                                        Search Members
                                    </label>
                                    <div class="nk-header-search">
                                        <input type="text" id="searchInput" class="nk-search-input form-control"
                                            placeholder="Search name, role…">
                                        <i class="bi bi-search nk-search-icon"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Team info strip ── -->
                            <div class="team-info-strip hidden" id="teamInfoStrip">
                                <div class="tis-item">
                                    <i class="bi bi-people-fill text-primary"></i>
                                    <div>
                                        <span class="tis-label">Team</span>
                                        <span class="tis-val" id="tisTeamName">—</span>
                                    </div>
                                </div>
                                <div class="tis-item">
                                    <i class="bi bi-award-fill text-warning"></i>
                                    <div>
                                        <span class="tis-label">Leader</span>
                                        <span class="tis-val" id="tisLeader">—</span>
                                    </div>
                                </div>
                                <div class="tis-item">
                                    <i class="bi bi-person-fill text-success"></i>
                                    <div>
                                        <span class="tis-label">Members</span>
                                        <span class="tis-val" id="tisMemberCount">0</span>
                                    </div>
                                </div>
                            </div>

                            <!-- ── Action buttons (above table) ── -->
                            <div class="team-actions-bar">
                                <?php if (hasFeature("addTeamMember")) { ?>
                                    <button class="btn btn-primary" id="btnAddMember" disabled onclick="openAddMember()">
                                        <i class="bi bi-person-plus me-1"></i> Add Member
                                    </button>
                                <?php } ?>
                                <?php if (hasFeature("changeTeamLeader")) { ?>
                                    <button class="btn btn-warning" id="btnChangeLeader" disabled onclick="openChangeLeader()">
                                        <i class="bi bi-award me-1"></i> Change Leader
                                    </button>
                                <?php } ?>
                                <?php if (hasFeature("deleteTeam")) { ?>
                                    <button class="btn btn-danger" id="btnDeleteTeam" disabled onclick="openDeleteTeam()">
                                        <i class="bi bi-trash me-1"></i> Delete Team
                                    </button>
                                <?php } ?>
                            </div>

                            <!-- ── Members table ── -->
                            <div class="sparkline12-graph">
                                <div class="table-responsive static-table-list">
                                    <table class="table table-hover table-bordered">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>#</th>
                                                <th onclick="sortTable('name')">Full Name</th>
                                                <th onclick="sortTable('username')">Username</th>
                                                <th onclick="sortTable('role')">System Role</th>
                                                <th>Team Role</th>
                                                <th style="width:100px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="teamMembersBody">
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <i class="bi bi-arrow-up-circle me-1"></i>
                                                    Select a team to view members
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
    </div>

    <?php
    include "footer.php"; ?>

    <!-- ══ CREATE TEAM MODAL ══ -->
    <div class="modal fade" id="createTeamModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2 text-success"></i>Create New Team</h5>
                </div>
                <div class="modal-body">
                    <form id="createTeamForm">
                        <div class="form-group mb-3">
                            <label class="form-label">Team Name <span class="text-danger">*</span></label>
                            <input type="text" id="ctTeamName" class="form-control"
                                placeholder="e.g. IT Team, Research Team…" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Select Leader <span class="text-danger">*</span></label>
                            <select id="ctLeaderSelect" class="form-select" required>
                                <option value="" disabled selected>Loading users…</option>
                            </select>
                            <small style="color: #cbd5e1 !important;">Any user (including DG / MD) can be leader.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success" onclick="createTeam()">
                        <i class="bi bi-check-lg me-1"></i> Create Team
                    </button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ ADD MEMBER MODAL ══ -->
    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2 text-primary"></i>Add Member</h5>
                </div>
                <div class="modal-body">
                    <form id="addMemberForm">
                        <div class="form-group mb-3">
                            <label class="form-label">Select User <span class="text-danger">*</span></label>
                            <select id="amUserSelect" class="form-select" required>
                                <option value="" disabled selected>Select a user…</option>
                            </select>
                            <small style="color: #cbd5e1 !important;">All users shown. Same user can be in multiple teams.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Team Role <span class="text-danger">*</span></label>
                            <select id="amRoleSelect" class="form-select" required>
                                <option value="" disabled selected>Select role…</option>
                                <option value="member">Member</option>
                                <option value="trainee">Trainee</option>
                                <option value="committee">Committee</option>
                                <option value="itHead">IT Head</option>
                                <option value="researchHead">Research Head</option>
                                <option value="representative">Representative</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="addMember()">
                        <i class="bi bi-plus me-1"></i> Add
                    </button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ CHANGE LEADER MODAL ══ -->
    <div class="modal fade" id="changeLeaderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-award me-2 text-warning"></i>Change Team Leader</h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info" style="font-size:.84rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        If the selected leader is not already in this team, they will be
                        <strong>auto-added</strong> as a member.
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Select New Leader <span class="text-danger">*</span></label>
                        <select id="clLeaderSelect" class="form-select" required>
                            <option value="" disabled selected>Select a user…</option>
                        </select>
                        <small style="color: #cbd5e1 !important;">Any user (DG / MD / member) can be selected.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-warning" onclick="changeLeader()">
                        <i class="bi bi-check-lg me-1"></i> Save Leader
                    </button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ DELETE TEAM MODAL ══ -->
    <div class="modal fade" id="deleteTeamModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>Delete Team
                    </h5>
                </div>
                <div class="modal-body">
                    <p id="deleteTeamMessage">Are you sure you want to delete this team?
                        <br><small style="color: #cbd5e1 !important;">All team members will be removed from this team.</small>
                    </p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" id="confirmDeleteTeamBtn">
                        <i class="bi bi-trash me-1"></i> Yes, Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ REMOVE MEMBER MODAL ══ -->
    <div class="modal fade" id="removeMemberModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Remove Member</h5>
                </div>
                <div class="modal-body">
                    <p id="removeMemberMsg">Remove this member from the team?</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" id="confirmRemoveMemberBtn">Remove</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    <script>

        const canAddTeam          = <?= hasFeature("addTeam")          ? "true" : "false" ?>;
        const canRemoveTeamMember = <?= hasFeature("removeTeamMember") ? "true" : "false" ?>;
        const canChangeTeamLeader = <?= hasFeature("changeTeamLeader") ? "true" : "false" ?>;
        const canDeleteTeam       = <?= hasFeature("deleteTeam")       ? "true" : "false" ?>;
        const canAddTeamMember    = <?= hasFeature("addTeamMember")    ? "true" : "false" ?>;

        function setActionsEnabled(on) {
    const addBtn    = document.getElementById('btnAddMember');
    const leaderBtn = document.getElementById('btnChangeLeader');
    const delBtn    = document.getElementById('btnDeleteTeam');
    if (addBtn)    addBtn.disabled    = !on;
    if (leaderBtn) leaderBtn.disabled = !on;
    if (delBtn)    delBtn.disabled    = !on;
}

        // ── Modal instances ────────────────────────────────────────────
        const createTeamModal = new bootstrap.Modal(document.getElementById('createTeamModal'));
        const addMemberModal = new bootstrap.Modal(document.getElementById('addMemberModal'));
        const changeLeaderModal = new bootstrap.Modal(document.getElementById('changeLeaderModal'));
        const deleteTeamModal = new bootstrap.Modal(document.getElementById('deleteTeamModal'));
        const removeMemberModal = new bootstrap.Modal(document.getElementById('removeMemberModal'));

        // ── State ──────────────────────────────────────────────────────
        let allTeams = []; // [{id, name, leader_id, leader_name}, …]
        let allUsers = []; // [{id, firstName, lastName, username, role}, …]
        let currentTeamId = null;
        let currentMembers = []; // [{user_id, firstName, lastName, username, role, team_role, is_leader}, …]
        let removeMemberId = null;
        let sortDir = {};

        const roleLabels = {
            trainee: 'Trainee',
            committee: 'Committee',
            itHead: 'IT Head',
            researchHead: 'Research Head',
            representative: 'Representative',
            MD: 'Managing Director',
            DG: 'Director General',
            member: 'Member',
            leader: 'Leader',
        };
        const rolePillClass = {
            trainee: 'role-trainee',
            committee: 'role-committee',
            itHead: 'role-itHead',
            researchHead: 'role-researchHead',
            representative: 'role-representative',
            MD: 'role-MD',
            DG: 'role-DG',
            member: 'role-member',
            leader: 'role-leader',
        };

        // ── Init ───────────────────────────────────────────────────────
        (async function init() {
            await Promise.all([loadTeams(), loadUsers()]);
        })();

        // ── Load all teams ─────────────────────────────────────────────
async function loadTeams() {
    const res = await fetch('fetchTeams.php');
    allTeams = await res.json();

    const sel = document.getElementById('teamSelectDropdown');
    sel.innerHTML = '';

    allTeams.forEach(t => {
        const opt = document.createElement('option');
        opt.value = t.id;
        opt.textContent = t.name;
        sel.appendChild(opt);
    });

    // Keep current selection if available
    if (currentTeamId) {
        sel.value = currentTeamId;
    } else if (allTeams.length > 0) {
        // Prefer IT Team, otherwise first team
        const defaultTeam =
            allTeams.find(t => t.name.toLowerCase() === 'it team') ||
            allTeams[0];

        currentTeamId = defaultTeam.id;
        sel.value = defaultTeam.id;

        loadTeamMembers(defaultTeam.id);
        setActionsEnabled(true);
    }
}

        // ── Load all users ─────────────────────────────────────────────
        async function loadUsers() {
            const res = await fetch('fetchUsers.php');
            allUsers = await res.json();
        }

        // ── Team dropdown change ───────────────────────────────────────
        document.getElementById('teamSelectDropdown').addEventListener('change', function() {
            currentTeamId = parseInt(this.value);
            loadTeamMembers(currentTeamId);
            setActionsEnabled(true);
        });

        function setActionsEnabled(on) {
            document.getElementById('btnAddMember').disabled = !on;
            document.getElementById('btnChangeLeader').disabled = !on;
            document.getElementById('btnDeleteTeam').disabled = !on;
        }

        // ── Load team members ──────────────────────────────────────────
        async function loadTeamMembers(teamId) {
            document.getElementById('teamMembersBody').innerHTML =
                `<tr><td colspan="6" class="text-center text-muted py-3">Loading…</td></tr>`;

            const res = await fetch(`fetchTeamMembers.php?team_id=${teamId}`);
            const data = await res.json();
            currentMembers = data.members || [];

            // Update info strip
            const team = allTeams.find(t => t.id == teamId) || {};
            document.getElementById('tisTeamName').textContent = team.name ?? '—';
            document.getElementById('tisLeader').textContent = data.leader_name ?? '—';
            document.getElementById('tisMemberCount').textContent = currentMembers.length;
            document.getElementById('teamInfoStrip').classList.remove('hidden');

            renderMembers(currentMembers);
        }

        // ── Render members table ───────────────────────────────────────
        function renderMembers(list) {
            const tbody = document.getElementById('teamMembersBody');
            if (!list || list.length === 0) {
                tbody.innerHTML =
                    `<tr><td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-person-x me-1"></i> No members yet. Add one above.
                </td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            list.forEach((m, i) => {
                const isLeader = !!m.is_leader;
                const sysCls = rolePillClass[m.role] ?? 'role-member';
                const sysLbl = roleLabels[m.role] ?? m.role;
                const teamCls = rolePillClass[m.team_role] ?? 'role-member';
                const teamLbl = roleLabels[m.team_role] ?? m.team_role ?? 'Member';
                const crown = isLeader ? '<i class="bi bi-award-fill crown-icon"></i>' : '';
const removBtn = isLeader
    ? `<button class="btn btn-sm btn-outline-secondary" disabled title="Change leader first">
           <i class="bi bi-lock-fill"></i>
       </button>`
    : (canRemoveTeamMember
        ? `<button class="btn btn-sm btn-danger" onclick="confirmRemoveMember(${m.user_id})">
               <i class="bi bi-person-dash"></i>
           </button>`
        : '');

                tbody.innerHTML += `
            <tr${isLeader ? ' ' : ''}>
                <td>${i + 1}</td>
                <td><strong>${crown}${esc(m.firstName)} ${esc(m.lastName)}</strong></td>
                <td><small>${esc(m.username)}</small></td>
                <td><span class="role-pill ${sysCls}">${sysLbl}</span></td>
                <td><span class="role-pill ${teamCls}">${isLeader ? '👑 Leader' : teamLbl}</span></td>
                <td>${removBtn}</td>
            </tr>`;
            });
        }

        // ── Search ─────────────────────────────────────────────────────
        document.getElementById('searchInput').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            const filtered = currentMembers.filter(m =>
                (`${m.firstName} ${m.lastName}`).toLowerCase().includes(q) ||
                (m.username || '').toLowerCase().includes(q) ||
                (m.team_role || '').toLowerCase().includes(q) ||
                (m.role || '').toLowerCase().includes(q)
            );
            renderMembers(filtered);
        });

        // ── Sort ───────────────────────────────────────────────────────
        function sortTable(col) {
            sortDir[col] = !sortDir[col];
            currentMembers.sort((a, b) => {
                const va = col === 'name' ?
                    `${a.firstName} ${a.lastName}`.toLowerCase() :
                    (a[col] ?? '').toLowerCase();
                const vb = col === 'name' ?
                    `${b.firstName} ${b.lastName}`.toLowerCase() :
                    (b[col] ?? '').toLowerCase();
                return sortDir[col] ? va.localeCompare(vb) : vb.localeCompare(va);
            });
            renderMembers(currentMembers);
        }

        // ══ CREATE TEAM ════════════════════════════════════════════════
const btnCreateTeam = document.getElementById('btnCreateTeam');
if (btnCreateTeam) {
    btnCreateTeam.addEventListener('click', () => {
        document.getElementById('createTeamForm').reset();
        populateUserSelect('ctLeaderSelect');
        createTeamModal.show();
    });
}
        async function createTeam() {
            const name = document.getElementById('ctTeamName').value.trim();
            const leaderId = document.getElementById('ctLeaderSelect').value;
            if (!name || !leaderId) {
                alert('Team name and leader are required.');
                return;
            }

            const fd = new FormData();
            fd.append('name', name);
            fd.append('leader_id', leaderId);

            const res = await fetch('createTeam.php', {
                method: 'POST',
                body: fd
            });
            const json = await res.json();
            createTeamModal.hide();

            if (json.success) {
                await loadTeams();
                // Auto-select new team
                document.getElementById('teamSelectDropdown').value = json.team_id;
                currentTeamId = json.team_id;
                loadTeamMembers(json.team_id);
                setActionsEnabled(true);
                showToast(json.message, 'success');
            } else {
                showToast(json.message, 'error');
            }
        }

        // ══ ADD MEMBER ═════════════════════════════════════════════════
        function openAddMember() {
            if (!currentTeamId) return;
            document.getElementById('addMemberForm').reset();
            // Show all users; backend will handle duplicate check
            populateUserSelect('amUserSelect');
            addMemberModal.show();
        }

        async function addMember() {
            const userId = document.getElementById('amUserSelect').value;
            const teamRole = document.getElementById('amRoleSelect').value;
            if (!userId || !teamRole) {
                alert('Select a user and a role.');
                return;
            }

            const fd = new FormData();
            fd.append('team_id', currentTeamId);
            fd.append('user_id', userId);
            fd.append('team_role', teamRole);

            const res = await fetch('addTeamMember.php', {
                method: 'POST',
                body: fd
            });
            const json = await res.json();
            addMemberModal.hide();

            if (json.success) {
                loadTeamMembers(currentTeamId);
                showToast(json.message, 'success');
            } else {
                showToast(json.message, 'error');
            }
        }

        // ══ CHANGE LEADER ══════════════════════════════════════════════
        function openChangeLeader() {
            if (!currentTeamId) return;
            populateUserSelect('clLeaderSelect');
            changeLeaderModal.show();
        }

        async function changeLeader() {
            const newLeaderId = document.getElementById('clLeaderSelect').value;
            if (!newLeaderId) {
                alert('Please select a new leader.');
                return;
            }

            const fd = new FormData();
            fd.append('team_id', currentTeamId);
            fd.append('leader_id', newLeaderId);

            const res = await fetch('changeTeamLeader.php', {
                method: 'POST',
                body: fd
            });
            const json = await res.json();
            changeLeaderModal.hide();

            if (json.success) {
                await loadTeams();
                loadTeamMembers(currentTeamId);
                showToast(json.message, 'success');
            } else {
                showToast(json.message, 'error');
            }
        }

        // ══ DELETE TEAM ════════════════════════════════════════════════
        function openDeleteTeam() {
            if (!currentTeamId) return;
            const team = allTeams.find(t => t.id == currentTeamId);
            document.getElementById('deleteTeamMessage').innerHTML =
                `Are you sure you want to delete <strong>"${esc(team?.name ?? '')}"</strong>?
             <br><small class="text-muted">All members will be removed from this team (cascade).</small>`;
            deleteTeamModal.show();
        }

        document.getElementById('confirmDeleteTeamBtn').addEventListener('click', async () => {
            const fd = new FormData();
            fd.append('team_id', currentTeamId);

            const res = await fetch('deleteTeam.php', {
                method: 'POST',
                body: fd
            });
            const json = await res.json();
            deleteTeamModal.hide();

            if (json.success) {
                currentTeamId = null;
                document.getElementById('teamSelectDropdown').value = '';
                document.getElementById('teamInfoStrip').classList.add('hidden');
                document.getElementById('teamMembersBody').innerHTML =
                    `<tr><td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-arrow-up-circle me-1"></i> Select a team to view members
                </td></tr>`;
                setActionsEnabled(false);
                await loadTeams();
                showToast(json.message, 'success');
            } else {
                showToast(json.message, 'error');
            }
        });

        // ══ REMOVE MEMBER ══════════════════════════════════════════════
        function confirmRemoveMember(userId) {
            removeMemberId = userId;
            const m = currentMembers.find(x => x.user_id == userId);
            document.getElementById('removeMemberMsg').textContent =
                `Remove "${m ? m.firstName + ' ' + m.lastName : 'this member'}" from the team?`;
            removeMemberModal.show();
        }

        document.getElementById('confirmRemoveMemberBtn').addEventListener('click', async () => {
            const fd = new FormData();
            fd.append('team_id', currentTeamId);
            fd.append('user_id', removeMemberId);

            const res = await fetch('removeTeamMember.php', {
                method: 'POST',
                body: fd
            });
            const json = await res.json();
            removeMemberModal.hide();
            removeMemberId = null;

            if (json.success) {
                loadTeamMembers(currentTeamId);
                showToast(json.message, 'success');
            } else {
                showToast(json.message, 'error');
            }
        });

        // ── Populate a <select> with all users ─────────────────────────
        function populateUserSelect(selId) {
            const sel = document.getElementById(selId);
            sel.innerHTML = '<option value="">Select a user…</option>';
            allUsers.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u.id;
                opt.textContent = `${u.firstName} ${u.lastName} (@${u.username}) — ${u.role}`;
                sel.appendChild(opt);
            });
        }

        // ── Toast (matches project pattern) ───────────────────────────
        function showToast(msg, type = 'success') {
            // Remove old toast if any
            const old = document.getElementById('_projectToast');
            if (old) old.remove();

            const bg = type === 'success' ? '#198754' : '#dc3545';
            const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
            const div = document.createElement('div');
            div.id = '_projectToast';
            div.style.cssText = `
            position:fixed;bottom:24px;right:24px;z-index:9999;
            background:#1a2332;border:1px solid #293647;border-radius:10px;
            padding:12px 20px;font-size:.85rem;font-weight:500;
            display:flex;align-items:center;gap:10px;
            box-shadow:0 8px 32px rgba(0,0,0,.4);min-width:220px;`;
            div.innerHTML = `<i class="bi ${icon}" style="color:${bg};font-size:1.1rem;"></i><span>${esc(msg)}</span>`;
            document.body.appendChild(div);
            setTimeout(() => div.remove(), 3200);
        }

        // ── HTML escape ────────────────────────────────────────────────
        function esc(s) {
            return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>

</body>

</html>