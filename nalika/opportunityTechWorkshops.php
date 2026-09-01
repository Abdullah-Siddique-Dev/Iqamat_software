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

    .badge-free {
        background: #d4edda;
        color: #155724;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 700;
    }

    .badge-paid {
        background: #fff3cd;
        color: #856404;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 700;
    }
</style>

<body>
    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>
    <?php include "mainTopBar.php"; ?>

    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="breadcome-list single-page-breadcome">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <div class="breadcome-heading">
                                    <?php if (hasFeature("addTechnicalWorkshop")) { ?>
                                        <button class="btn btn-success mb-3" id="addNewTechWorkshopBtn">New Technical Workshop</button>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <ul class="breadcome-menu">
                                    <li><a href="#">Home</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Technical Workshops</span></li>
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
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="sparkline12-list mg-b-15">
                        <div class="sparkline12-hd">
                            <div class="d-flex justify-content-between align-items-center flex-wrap" style="padding-bottom:20px;">
                                <div>
                                    <h5 class="mb-0">Upcoming Technical Workshops</h5>
                                </div>
                                <div class="mt-2 mt-lg-0" style="width:300px;">
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
                                            <th onclick="sortTable('topic')" style="cursor:pointer;">Topic</th>
                                            <th onclick="sortTable('skills')" style="cursor:pointer;">Skills</th>
                                            <th onclick="sortTable('organisier')" style="cursor:pointer;">Organiser</th>
                                            <th onclick="sortTable('dateTime')" style="cursor:pointer;">Date/Time</th>
                                            <th onclick="sortTable('duration')" style="cursor:pointer;">Duration</th>
                                            <th onclick="sortTable('frequency')" style="cursor:pointer;">Frequency</th>
                                            <th onclick="sortTable('phone')" style="cursor:pointer;">Phone</th>
                                            <th onclick="sortTable('location')" style="cursor:pointer;">Location</th>
                                            <th onclick="sortTable('feeType')" style="cursor:pointer;">Fee</th>
                                            <?php if (hasFeature("editTechnicalWorkshop") || hasFeature("deleteTechnicalWorkshop")) { ?>
                                                <th style="width:180px;">Actions</th>
                                            <?php } ?>
                                        </tr>
                                    </thead>
                                    <tbody id="techWorkshopTableBody">
                                        <!-- Rows rendered here -->
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

    <!-- ══ ADD MODAL ══ -->
    <div class="modal fade" id="addTechWorkshopModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Technical Workshop</h5>
                </div>
                <div class="modal-body">
                    <form id="addTechWorkshopForm">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Topic <span class="text-danger">*</span></label>
                                    <input type="text" name="topic" class="form-control" placeholder="Enter Topic" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Skills Covered <span class="text-danger">*</span></label>
                                    <input type="text" name="skills" class="form-control" placeholder="e.g. Python, Excel, Networking" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Organiser <span class="text-danger">*</span></label>
                                    <input type="text" name="organisier" class="form-control" placeholder="Organiser name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Date / Time</label>
                                    <input type="datetime-local" name="dateTime" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label>Duration <span class="text-danger">*</span></label>
                                    <input type="number" name="durationValue" class="form-control" placeholder="e.g. 2" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label>Duration Unit</label>
                                    <select name="durationUnit" class="form-control">
                                        <option value="Days">Days</option>
                                        <option value="Weeks">Weeks</option>
                                        <option value="Months" selected>Months</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label>Frequency (per week)</label>
                                    <input type="number" name="frequency" class="form-control" placeholder="e.g. 2" min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Phone</label>
                                    <input type="text" name="phone" class="form-control" placeholder="+92 …">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Location</label>
                                    <input type="text" name="location" class="form-control" placeholder="Venue / Address">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Fee Type <span class="text-danger">*</span></label>
                                    <select name="feeType" class="form-control" id="add-feeType" required onchange="toggleAddFeeAmount()">
                                        <option value="Free">Free</option>
                                        <option value="Paid">Paid</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="add-feeAmountWrap" style="display:none;">
                                <div class="form-group mb-3">
                                    <label>Fee Amount</label>
                                    <input type="text" name="feeAmount" id="add-feeAmount" class="form-control" placeholder="e.g. 5000 PKR">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="addTechWorkshop()">Add</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ EDIT MODAL ══ -->
    <div class="modal fade" id="editTechWorkshopModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Technical Workshop</h5>
                </div>
                <div class="modal-body">
                    <form id="editTechWorkshopForm">
                        <input type="hidden" id="editTechWorkshopId" name="id">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Topic <span class="text-danger">*</span></label>
                                    <input type="text" name="topic" id="edit-topic" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Skills Covered <span class="text-danger">*</span></label>
                                    <input type="text" name="skills" id="edit-skills" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Organiser <span class="text-danger">*</span></label>
                                    <input type="text" name="organisier" id="edit-organisier" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Date / Time</label>
                                    <input type="datetime-local" name="dateTime" id="edit-dateTime" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label>Duration</label>
                                    <input type="number" name="durationValue" id="edit-durationValue" class="form-control" min="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label>Duration Unit</label>
                                    <select name="durationUnit" id="edit-durationUnit" class="form-control">
                                        <option value="Days">Days</option>
                                        <option value="Weeks">Weeks</option>
                                        <option value="Months">Months</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label>Frequency (per week)</label>
                                    <input type="number" name="frequency" id="edit-frequency" class="form-control" min="1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Phone</label>
                                    <input type="text" name="phone" id="edit-phone" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Location</label>
                                    <input type="text" name="location" id="edit-location" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Fee Type</label>
                                    <select name="feeType" id="edit-feeType" class="form-control" onchange="toggleEditFeeAmount()">
                                        <option value="Free">Free</option>
                                        <option value="Paid">Paid</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6" id="edit-feeAmountWrap" style="display:none;">
                                <div class="form-group mb-3">
                                    <label>Fee Amount</label>
                                    <input type="text" name="feeAmount" id="edit-feeAmount" class="form-control" placeholder="e.g. 5000 PKR">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="updateTechWorkshop()">Update</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ DELETE MODAL ══ -->
    <div class="modal fade" id="deleteTechWorkshopModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteTechWorkshopMessage">Are you sure you want to delete this workshop?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteTechWorkshopBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        const canAddTechnicalWorkshop = <?= hasFeature("addTechnicalWorkshop")    ? "true" : "false" ?>;
        const canDeleteTechnicalWorkshop = <?= hasFeature("deleteTechnicalWorkshop") ? "true" : "false" ?>;
        const canEditTechnicalWorkshop = <?= hasFeature("editTechnicalWorkshop")   ? "true" : "false" ?>;

        // ── Modal instances ──
        const addTechWorkshopModal = new bootstrap.Modal(document.getElementById('addTechWorkshopModal'));
        const editTechWorkshopModal = new bootstrap.Modal(document.getElementById('editTechWorkshopModal'));
        const deleteTechWorkshopModal = new bootstrap.Modal(document.getElementById('deleteTechWorkshopModal'));

        let techWorkshopList = [];
        let selectedTechWorkshopId = null;
        let sortDirection = {};

        // ── Open Add modal ──
        const addNewTechWorkshopBtn = document.getElementById('addNewTechWorkshopBtn');
        if (addNewTechWorkshopBtn) {
            addNewTechWorkshopBtn.addEventListener('click', () => {
                document.getElementById('addTechWorkshopForm').reset();
                document.getElementById('add-feeAmountWrap').style.display = 'none';
                addTechWorkshopModal.show();
            });
        }

        // ── Fee toggle helpers ──
        function toggleAddFeeAmount() {
            const val = document.getElementById('add-feeType').value;
            document.getElementById('add-feeAmountWrap').style.display = val === 'Paid' ? 'block' : 'none';
            if (val === 'Free') document.getElementById('add-feeAmount').value = '';
        }

        function toggleEditFeeAmount() {
            const val = document.getElementById('edit-feeType').value;
            document.getElementById('edit-feeAmountWrap').style.display = val === 'Paid' ? 'block' : 'none';
            if (val === 'Free') document.getElementById('edit-feeAmount').value = '';
        }

        // ── ADD ──
        function addTechWorkshop() {
            const form = document.getElementById('addTechWorkshopForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            const data = new FormData(form);

            fetch('addOpportunityTechWorkshop.php', {
                    method: 'POST',
                    body: data
                })
                .then(r => r.text())
                .then(msg => {
                    addTechWorkshopModal.hide();
                    showSuccess(msg, loadTechWorkshops);
                })
                .catch(console.error);
        }

        // ── LOAD ──
        async function loadTechWorkshops() {
            try {
                const res = await fetch('fetchOpportunityTechWorkshops.php');
                techWorkshopList = await res.json();
                renderTechWorkshops(techWorkshopList);
            } catch (e) {
                console.error('Error loading Technical Workshops:', e);
            }
        }

        // ── RENDER ──
        function renderTechWorkshops(list) {
            const tbody = document.getElementById('techWorkshopTableBody');
            tbody.innerHTML = '';

            if (!list || list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="11" class="text-center">No Technical Workshops found</td></tr>`;
                return;
            }

            list.forEach((w, i) => {
                const feePill = w.feeType === 'Free' ?
                    `<span class="badge-free">Free</span>` :
                    `<span class="badge-paid">Paid${w.feeAmount ? ' – ' + w.feeAmount : ''}</span>`;

                const durationStr = w.durationValue ?
                    `${w.durationValue} ${w.durationUnit}${w.frequency ? ', ' + w.frequency + 'x/week' : ''}` :
                    '—';

                tbody.innerHTML += `
                <tr>
                    <td>${i + 1}</td>
                    <td>${w.topic}</td>
                    <td>${w.skills ?? '—'}</td>
                    <td>${w.organisier}</td>
                    <td>${formatDateTime(w.dateTime)}</td>
                    <td>${w.durationValue ? w.durationValue + ' ' + w.durationUnit : '—'}</td>
                    <td>${w.frequency ? w.frequency + 'x / week' : '—'}</td>
                    <td>${w.phone ?? '—'}</td>
                    <td>${w.location ?? '—'}</td>
                    <td>${feePill}</td>
<td>
    ${canEditTechnicalWorkshop ? `
    <button class="btn btn-sm btn-primary" onclick="editTechWorkshop(${w.id})">Edit</button>` : ''}
    ${canDeleteTechnicalWorkshop ? `
    <button class="btn btn-sm btn-danger" onclick="deleteTechWorkshop(${w.id})">Delete</button>` : ''}
</td>
                </tr>`;
            });
        }

        // ── Date/time formatter ──
        function formatDateTime(dateTime) {
            if (!dateTime) return '—';
            const date = new Date(dateTime);
            const day = String(date.getDate()).padStart(2, '0');
            const mon = String(date.getMonth() + 1).padStart(2, '0');
            const yr = date.getFullYear();
            let hrs = date.getHours();
            const min = String(date.getMinutes()).padStart(2, '0');
            const ampm = hrs >= 12 ? 'PM' : 'AM';
            hrs = hrs % 12 || 12;
            return `${day}-${mon}-${yr} ${hrs}:${min} ${ampm}`;
        }

        // ── Search ──
        document.getElementById('searchInput').addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            const filtered = techWorkshopList.filter(w =>
                w.topic.toLowerCase().startsWith(q) ||
                w.organisier.toLowerCase().startsWith(q) ||
                (w.skills ?? '').toLowerCase().includes(q) ||
                (w.phone ?? '').startsWith(q) ||
                (w.location ?? '').toLowerCase().startsWith(q)
            );
            renderTechWorkshops(filtered);
        });

        // ── Sort ──
        function sortTable(column) {
            sortDirection[column] = !sortDirection[column];
            techWorkshopList.sort((a, b) => {
                let va = a[column] ?? '';
                let vb = b[column] ?? '';
                if (column === 'dateTime') {
                    va = new Date(va).getTime() || 0;
                    vb = new Date(vb).getTime() || 0;
                } else {
                    va = va.toString().toLowerCase();
                    vb = vb.toString().toLowerCase();
                }
                if (va < vb) return sortDirection[column] ? -1 : 1;
                if (va > vb) return sortDirection[column] ? 1 : -1;
                return 0;
            });
            renderTechWorkshops(techWorkshopList);
        }

        // ── EDIT ──
        function editTechWorkshop(id) {
            const w = techWorkshopList.find(x => x.id == id);
            if (!w) return;

            document.getElementById('editTechWorkshopId').value = w.id;
            document.getElementById('edit-topic').value = w.topic || '';
            document.getElementById('edit-skills').value = w.skills || '';
            document.getElementById('edit-organisier').value = w.organisier || '';
            document.getElementById('edit-dateTime').value = w.dateTime ?
                w.dateTime.replace(' ', 'T').slice(0, 16) : '';
            document.getElementById('edit-durationValue').value = w.durationValue || '';
            document.getElementById('edit-durationUnit').value = w.durationUnit || 'Months';
            document.getElementById('edit-frequency').value = w.frequency || '';
            document.getElementById('edit-phone').value = w.phone || '';
            document.getElementById('edit-location').value = w.location || '';
            document.getElementById('edit-feeType').value = w.feeType || 'Free';
            document.getElementById('edit-feeAmount').value = w.feeAmount || '';
            toggleEditFeeAmount();

            editTechWorkshopModal.show();
        }

        // ── UPDATE ──
        function updateTechWorkshop() {
            const form = document.getElementById('editTechWorkshopForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            const data = new FormData(form);

            fetch('updateOpportunityTechWorkshop.php', {
                    method: 'POST',
                    body: data
                })
                .then(r => r.text())
                .then(msg => {
                    editTechWorkshopModal.hide();
                    showSuccess(msg, loadTechWorkshops);
                })
                .catch(console.error);
        }

        // ── DELETE ──
        function deleteTechWorkshop(id) {
            selectedTechWorkshopId = id;
            const w = techWorkshopList.find(x => x.id == id);
            document.getElementById('deleteTechWorkshopMessage').innerText =
                `Are you sure you want to delete "${w ? w.topic : 'this workshop'}"?`;
            deleteTechWorkshopModal.show();
        }

        document.getElementById('confirmDeleteTechWorkshopBtn').addEventListener('click', function() {
            if (!selectedTechWorkshopId) return;
            fetch(`deleteOpportunityTechWorkshop.php?id=${selectedTechWorkshopId}`)
                .then(r => r.text())
                .then(() => {
                    deleteTechWorkshopModal.hide();
                    selectedTechWorkshopId = null;
                    loadTechWorkshops();
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

            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
            document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                document.getElementById('successModal').remove();
                if (callback) callback();
            });
        }

        // ── Init ──
        loadTechWorkshops();
    </script>

</body>

</html>