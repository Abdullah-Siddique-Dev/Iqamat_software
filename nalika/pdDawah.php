<?php
include "connection.php";
include "auth.php";
// $loggedUserId, $loggedRole, $loggedArea set by auth.php
?>
<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
/* ── Layout shells (matching Quran attendance theme) ── */
.dw-top-bar {
    display: flex; align-items: center;
    justify-content: space-between;
    flex-wrap: wrap; gap: 10px; margin-bottom: 18px;
}
.dw-top-bar h5 { margin: 0; font-size: 1.05rem; font-weight: 700; }

/* ── Stats pills ── */
.stats-row {
    display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px;
}
.stat-pill {
    display: flex; align-items: center; gap: 8px;
    border: 1px solid #293647; border-radius: 10px; padding: 8px 16px;
    font-size: .82rem;
}
.stat-pill .stat-val { font-size: 1.1rem; font-weight: 700; }
.stat-pill.green .stat-val { color: #198754; }
.stat-pill.blue  .stat-val { color: #0d6efd; }
.stat-pill.gold  .stat-val { color: #ffc107; }
.stat-pill.purple .stat-val { color: #6f42c1; }

/* ── Add button ── */
.btn-add-dawah {
    display: inline-flex; align-items: center; gap: 8px;
    background: #0d6efd; color: #fff; border: none;
    border-radius: 10px; padding: 9px 20px;
    font-size: .88rem; font-weight: 600; cursor: pointer;
    transition: background .15s, transform .1s;
}
.btn-add-dawah:hover { background: #0b5ed7; transform: translateY(-1px); }

/* ── Modal overlay ── */
.dw-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 9998;
    background: rgba(0,0,0,.55); backdrop-filter: blur(3px);
    align-items: center; justify-content: center;
}
.dw-modal-overlay.show { display: flex; }

.dw-modal {
    background: #1a2332; border: 1px solid #293647; border-radius: 16px;
    padding: 28px 28px 24px; width: 100%; max-width: 520px;
    box-shadow: 0 24px 64px rgba(0,0,0,.5);
    animation: modalSlideIn .25s cubic-bezier(.22,1,.36,1);
    max-height: 90vh; overflow-y: auto;
}
@keyframes modalSlideIn {
    from { transform: translateY(30px); opacity: 0; }
    to   { transform: translateY(0);    opacity: 1; }
}
.dw-modal h6 {
    font-size: 1rem; font-weight: 700; margin: 0 0 20px;
    display: flex; align-items: center; gap: 8px;
}
.dw-modal .form-label {
    font-size: .75rem; font-weight: 700; color: #6c757d;
    text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px;
}
.dw-modal .form-control,
.dw-modal .form-select {
    font-size: .88rem; border-radius: 8px;
    border: 1.5px solid #293647; padding: 8px 12px;
    background: #0f1923; color: #fff;
    transition: border-color .18s, box-shadow .18s;
}
.dw-modal .form-control:focus,
.dw-modal .form-select:focus {
    border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13,110,253,.15);
    outline: none; background: #0f1923; color: #fff;
}
.dw-modal .form-select option { background: #0f1923; }
.dw-modal-footer {
    display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;
}
.btn-cancel {
    background: transparent; border: 1.5px solid #293647;
    border-radius: 8px; padding: 7px 18px;
    font-size: .85rem; font-weight: 600; color: #6c757d;
    cursor: pointer; transition: border-color .15s, color .15s;
}
.btn-cancel:hover { border-color: #dc3545; color: #dc3545; }
.btn-save {
    background: #0d6efd; color: #fff; border: none;
    border-radius: 8px; padding: 8px 22px;
    font-size: .85rem; font-weight: 600; cursor: pointer;
    transition: background .15s;
}
.btn-save:hover { background: #0b5ed7; }

/* ── Table area ── */
.dw-table-wrap {
    border: 1px solid #293647; border-radius: 12px; overflow: hidden;
    margin-top: 18px;
}
.dw-table-wrap table {
    width: 100%; border-collapse: collapse; font-size: .84rem;
}
.dw-table-wrap thead tr {
    background: #0f1923; border-bottom: 1.5px solid #293647;
}
.dw-table-wrap th {
    padding: 12px 14px; font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px; color: #6c757d;
    white-space: nowrap;
}
.dw-table-wrap td {
    padding: 11px 14px; border-bottom: 1px solid #1e2d40;
    vertical-align: middle;
}
.dw-table-wrap tbody tr:last-child td { border-bottom: none; }
.dw-table-wrap tbody tr:hover { background: rgba(13,110,253,.06); }

/* Type badges */
.badge-type {
    display: inline-flex; align-items: center; gap: 5px;
    border-radius: 6px; padding: 3px 10px;
    font-size: .72rem; font-weight: 700;
}
.badge-personal { background: rgba(13,110,253,.15); color: #4da3ff; }
.badge-collective { background: rgba(111,66,193,.15); color: #a07cfc; }

.badge-mode {
    display: inline-flex; align-items: center; gap: 4px;
    border-radius: 6px; padding: 2px 8px;
    font-size: .68rem; font-weight: 700;
}
.badge-physical { background: rgba(25,135,84,.15); color: #5dd798; }
.badge-online   { background: rgba(255,193,7,.12);  color: #ffc107; }

/* Action buttons */
.btn-tbl {
    border: none; border-radius: 7px; padding: 5px 10px;
    font-size: .78rem; font-weight: 600; cursor: pointer;
    transition: opacity .15s;
}
.btn-tbl:hover { opacity: .8; }
.btn-edit { background: rgba(13,110,253,.18); color: #4da3ff; }
.btn-del  { background: rgba(220,53,69,.15);  color: #f87171; }

/* Empty state */
.dw-empty {
    text-align: center; padding: 48px 20px; color: #6c757d;
}
.dw-empty i { font-size: 2.5rem; margin-bottom: 12px; display: block; }
.dw-empty p { margin: 0; font-size: .88rem; }

/* Search / filter bar */
.dw-filter-bar {
    display: flex; gap: 10px; flex-wrap: wrap; align-items: center;
    margin-bottom: 14px;
}
.dw-filter-bar .form-control,
.dw-filter-bar .form-select {
    font-size: .84rem; border-radius: 8px;
    border: 1.5px solid #293647; padding: 7px 12px;
    background: transparent; color: inherit;
    min-width: 150px;
    transition: border-color .18s;
}
.dw-filter-bar .form-control:focus,
.dw-filter-bar .form-select:focus {
    border-color: #0d6efd; outline: none;
}

/* Toast */
.dw-toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    background: #1a2332; border: 1px solid #293647; border-radius: 10px;
    padding: 12px 20px; font-size: .85rem; font-weight: 500;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,.4);
    transform: translateY(80px); opacity: 0;
    transition: transform .3s cubic-bezier(.22,1,.36,1), opacity .3s;
    pointer-events: none; min-width: 220px;
}
.dw-toast.show { transform: translateY(0); opacity: 1; }
.dw-toast.toast-success i { color: #198754; }
.dw-toast.toast-error   i { color: #dc3545; }

/* Delete confirm modal */
.dw-confirm {
    background: #1a2332; border: 1px solid #293647; border-radius: 14px;
    padding: 24px 24px 20px; width: 100%; max-width: 360px;
    box-shadow: 0 24px 64px rgba(0,0,0,.5);
    animation: modalSlideIn .22s cubic-bezier(.22,1,.36,1);
    text-align: center;
}
.dw-confirm .confirm-icon { font-size: 2rem; color: #dc3545; margin-bottom: 10px; }
.dw-confirm h6 { font-size: .95rem; font-weight: 700; margin-bottom: 6px; }
.dw-confirm p  { font-size: .82rem; color: #6c757d; margin-bottom: 18px; }
.dw-confirm-btns { display: flex; gap: 10px; justify-content: center; }
.btn-confirm-del {
    background: #dc3545; color: #fff; border: none;
    border-radius: 8px; padding: 8px 22px;
    font-size: .85rem; font-weight: 600; cursor: pointer;
    transition: background .15s;
}
.btn-confirm-del:hover { background: #bb2d3b; }

/* Responsive table */
@media (max-width: 768px) {
    .dw-table-wrap { overflow-x: auto; }
    .dw-modal { margin: 12px; padding: 20px; }
}
</style>

<body>
    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>
    <?php include "mainTopBar.php"; ?>

    <div class="breadcome-area">
        <div class="container-fluid"><div class="row"><div class="col-lg-12">
            <div class="breadcome-list single-page-breadcome"><div class="row">
                <div class="col-6">
                    <h6 class="mb-0" style="font-size:.9rem;color:#6c757d;">Dawah Records</h6>
                </div>
                <div class="col-6">
                    <ul class="breadcome-menu">
                        <li><a href="#">Home</a> <span class="bread-slash">/</span></li>
                        <li><span class="bread-blod">Dawah</span></li>
                    </ul>
                </div>
            </div></div>
        </div></div></div>
    </div>

    <div class="static-table-area mg-t-15">
        <div class="container-fluid"><div class="row"><div class="col-lg-12">
            <div class="sparkline12-list mg-b-15"><div class="sparkline12-hd">

                <!-- TOP BAR -->
                <div class="dw-top-bar">
                    <h5>
                        <i class="bi bi-megaphone-fill me-2 text-primary" style="font-size:larger;"></i>
                        My Dawah Records
                    </h5>
                    <?php if (hasFeature("addDawah")) { ?>
                    <button class="btn-add-dawah" onclick="openModal()">
                        <i class="bi bi-plus-lg"></i> Add Dawah
                    </button>
                    <?php } ?>
                </div>

                <!-- STATS -->
                <div class="stats-row" id="statsRow">
                    <div class="stat-pill blue">
                        <i class="bi bi-collection-fill"></i>
                        <div><div class="stat-val" id="statTotal">—</div><div>Total</div></div>
                    </div>
                    <div class="stat-pill green">
                        <i class="bi bi-person-fill"></i>
                        <div><div class="stat-val" id="statPersonal">—</div><div>Personal</div></div>
                    </div>
                    <div class="stat-pill purple">
                        <i class="bi bi-people-fill"></i>
                        <div><div class="stat-val" id="statCollective">—</div><div>Collective</div></div>
                    </div>
                    <div class="stat-pill gold">
                        <i class="bi bi-calendar-month"></i>
                        <div><div class="stat-val" id="statThisMonth">—</div><div>This Month</div></div>
                    </div>
                </div>

                <!-- FILTER BAR -->
                <div class="dw-filter-bar">
                    <input type="text" id="searchInput" class="form-control"
                           placeholder="🔍  Search name, location, remarks…" oninput="filterTable()">
                    <select id="filterType" class="form-select" onchange="filterTable()">
                        <option value="">All Types</option>
                        <option value="Personal">Personal</option>
                        <option value="Collective">Collective</option>
                    </select>
                    <select id="filterMode" class="form-select" onchange="filterTable()">
                        <option value="">All Modes</option>
                        <option value="Physical">Physical</option>
                        <option value="Online">Online</option>
                    </select>
                </div>

                <!-- TABLE -->
                <div class="dw-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Mode</th>
                                <th>Person Name</th>
                                <th>Contact</th>
                                <th>Location</th>
                                <th>Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="dawahTableBody">
                            <tr><td colspan="9">
                                <div class="dw-empty">
                                    <i class="bi bi-hourglass-split"></i>
                                    <p>Loading records…</p>
                                </div>
                            </td></tr>
                        </tbody>
                    </table>
                </div>

            </div></div>
        </div></div></div>
    </div>

    <?php include "footer.php"; ?>

    <!-- ══ ADD / EDIT MODAL ══ -->
    <div class="dw-modal-overlay" id="dawahModalOverlay" onclick="overlayClose(event)">
        <div class="dw-modal" id="dawahModal">
            <h6 id="modalTitle">
                <i class="bi bi-megaphone-fill text-primary"></i>
                Add Dawah Record
            </h6>

            <input type="hidden" id="editId" value="">

            <div class="mb-3">
                <label class="form-label">Dawah Type</label>
                <select id="dawahType" class="form-select" onchange="onTypeChange()">
                    <option value="Personal">Personal</option>
                    <option value="Collective">Collective</option>
                </select>
            </div>

            <div class="mb-3" id="modeRow">
                <label class="form-label">Dawah Mode</label>
                <select id="dawahMode" class="form-select">
                    <option value="Physical">Physical</option>
                    <option value="Online">Online</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Person Name <span style="color:#dc3545">*</span></label>
                <input type="text" id="personName" class="form-control"
                       placeholder="Name of person you gave Dawah to" maxlength="150">
            </div>

            <div class="mb-3">
                <label class="form-label">Contact</label>
                <input type="text" id="personContact" class="form-control"
                       placeholder="Phone / Email (optional)" maxlength="120">
            </div>

            <div class="mb-3">
                <label class="form-label">Location <span style="color:#dc3545">*</span></label>
                <input type="text" id="location" class="form-control"
                       placeholder="Where did the Dawah take place?" maxlength="200">
            </div>

            <div class="mb-3">
                <label class="form-label">Date <span style="color:#dc3545">*</span></label>
                <input type="date" id="dawahDate" class="form-control">
            </div>

            <div class="mb-1">
                <label class="form-label">Remarks / Notes</label>
                <textarea id="remarks" class="form-control" rows="3"
                          placeholder="How did it go? Any follow-up needed?" maxlength="1000"></textarea>
            </div>

            <div class="dw-modal-footer">
                <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button class="btn-save" onclick="saveDawah()">
                    <i class="bi bi-check-lg me-1"></i> Save
                </button>
            </div>
        </div>
    </div>

    <!-- ══ DELETE CONFIRM MODAL ══ -->
    <div class="dw-modal-overlay" id="deleteOverlay" onclick="delOverlayClose(event)">
        <div class="dw-confirm">
            <div class="confirm-icon"><i class="bi bi-trash3-fill"></i></div>
            <h6>Delete Dawah Record?</h6>
            <p>This action cannot be undone.</p>
            <div class="dw-confirm-btns">
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-confirm-del" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="dw-toast" id="dwToast">
        <i class="bi bi-check-circle-fill" id="dwToastIcon"></i>
        <span id="dwToastMsg">Saved</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    <script>
    const USER_ID = <?= json_encode($loggedUserId) ?>;

    let allRecords  = [];
    let deleteId    = null;

    // ── Date helper ──────────────────────────────────────────────────────────
    function todayStr() { return new Date().toISOString().slice(0,10); }

    // ── Type combobox change ─────────────────────────────────────────────────
    function onTypeChange() {
        const type = document.getElementById('dawahType').value;
        const modeRow = document.getElementById('modeRow');
        if (type === 'Collective') {
            modeRow.style.display = 'none';
            document.getElementById('dawahMode').value = 'Physical';
        } else {
            modeRow.style.display = '';
        }
    }

    // ── Modal open / close ───────────────────────────────────────────────────
    function openModal(record = null) {
        document.getElementById('editId').value      = record ? record.id : '';
        document.getElementById('modalTitle').innerHTML = record
            ? '<i class="bi bi-pencil-fill text-warning"></i> Edit Dawah Record'
            : '<i class="bi bi-megaphone-fill text-primary"></i> Add Dawah Record';

        document.getElementById('dawahType').value    = record ? record.dawah_type    : 'Personal';
        document.getElementById('dawahMode').value    = record ? record.dawah_mode    : 'Physical';
        document.getElementById('personName').value   = record ? record.person_name   : '';
        document.getElementById('personContact').value= record ? record.person_contact: '';
        document.getElementById('location').value     = record ? record.location      : '';
        document.getElementById('dawahDate').value    = record ? record.dawah_date    : todayStr();
        document.getElementById('remarks').value      = record ? record.remarks       : '';

        onTypeChange();
        document.getElementById('dawahModalOverlay').classList.add('show');
        document.getElementById('personName').focus();
    }

    function closeModal() {
        document.getElementById('dawahModalOverlay').classList.remove('show');
    }

    function overlayClose(e) {
        if (e.target === document.getElementById('dawahModalOverlay')) closeModal();
    }

    // ── Save (create / update) ───────────────────────────────────────────────
    async function saveDawah() {
        const id          = document.getElementById('editId').value;
        const dawahType   = document.getElementById('dawahType').value;
        const dawahMode   = dawahType === 'Collective' ? 'Physical' : document.getElementById('dawahMode').value;
        const personName  = document.getElementById('personName').value.trim();
        const contact     = document.getElementById('personContact').value.trim();
        const location    = document.getElementById('location').value.trim();
        const dawahDate   = document.getElementById('dawahDate').value;
        const remarks     = document.getElementById('remarks').value.trim();

        if (!personName) { showToast('Person name is required.', 'error'); return; }
        if (!location)   { showToast('Location is required.', 'error'); return; }
        if (!dawahDate)  { showToast('Date is required.', 'error'); return; }

        const payload = { user_id: USER_ID, dawah_type: dawahType, dawah_mode: dawahMode,
                          person_name: personName, person_contact: contact,
                          location, dawah_date: dawahDate, remarks };
        if (id) payload.id = id;

        try {
            const res  = await fetch(id ? 'updateDawah.php' : 'addDawah.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message ?? 'Error');
            closeModal();
            showToast(id ? 'Record updated.' : 'Dawah recorded!', 'success');
            await loadRecords();
        } catch(e) {
            showToast(e.message, 'error');
        }
    }

    // ── Delete ───────────────────────────────────────────────────────────────
    function deleteDawah(id) {
        deleteId = id;
        document.getElementById('deleteOverlay').classList.add('show');
    }
    function closeDeleteModal() {
        document.getElementById('deleteOverlay').classList.remove('show');
        deleteId = null;
    }
    function delOverlayClose(e) {
        if (e.target === document.getElementById('deleteOverlay')) closeDeleteModal();
    }
    async function confirmDelete() {
        if (!deleteId) return;
        try {
            const res  = await fetch(`deleteDawah.php?id=${deleteId}&user_id=${USER_ID}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message ?? 'Delete failed');
            closeDeleteModal();
            showToast('Record deleted.', 'success');
            await loadRecords();
        } catch(e) {
            showToast(e.message, 'error');
        }
    }

    // ── Load records ─────────────────────────────────────────────────────────
    async function loadRecords() {
        try {
            const res  = await fetch(`fetchDawah.php?user_id=${USER_ID}`);
            allRecords = await res.json();
            updateStats();
            filterTable();
        } catch(e) {
            document.getElementById('dawahTableBody').innerHTML =
                `<tr><td colspan="9"><div class="dw-empty"><i class="bi bi-wifi-off"></i><p>Failed to load records.</p></div></td></tr>`;
        }
    }

    // ── Stats ────────────────────────────────────────────────────────────────
    function updateStats() {
        const now = new Date();
        const ym  = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
        document.getElementById('statTotal').textContent =
            allRecords.length;
        document.getElementById('statPersonal').textContent =
            allRecords.filter(r => r.dawah_type === 'Personal').length;
        document.getElementById('statCollective').textContent =
            allRecords.filter(r => r.dawah_type === 'Collective').length;
        document.getElementById('statThisMonth').textContent =
            allRecords.filter(r => r.dawah_date && r.dawah_date.startsWith(ym)).length;
    }

    // ── Filter / render table ─────────────────────────────────────────────────
    function filterTable() {
        const q    = document.getElementById('searchInput').value.toLowerCase();
        const type = document.getElementById('filterType').value;
        const mode = document.getElementById('filterMode').value;

        const filtered = allRecords.filter(r => {
            const matchQ = !q
                || (r.person_name   ?? '').toLowerCase().includes(q)
                || (r.location      ?? '').toLowerCase().includes(q)
                || (r.remarks       ?? '').toLowerCase().includes(q)
                || (r.person_contact?? '').toLowerCase().includes(q);
            const matchType = !type || r.dawah_type === type;
            const matchMode = !mode || r.dawah_mode === mode;
            return matchQ && matchType && matchMode;
        });

        renderTable(filtered);
    }

    function renderTable(list) {
        const tbody = document.getElementById('dawahTableBody');
        if (!list || list.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9">
                <div class="dw-empty">
                    <i class="bi bi-megaphone"></i>
                    <p>No Dawah records found. Click <strong>Add Dawah</strong> to start.</p>
                </div></td></tr>`;
            return;
        }

        tbody.innerHTML = list.map((r, i) => {
            const typeBadge = r.dawah_type === 'Personal'
                ? `<span class="badge-type badge-personal"><i class="bi bi-person-fill"></i> Personal</span>`
                : `<span class="badge-type badge-collective"><i class="bi bi-people-fill"></i> Collective</span>`;

            const modeBadge = r.dawah_mode === 'Online'
                ? `<span class="badge-mode badge-online"><i class="bi bi-wifi"></i> Online</span>`
                : `<span class="badge-mode badge-physical"><i class="bi bi-geo-alt-fill"></i> Physical</span>`;

            const date = r.dawah_date
                ? new Date(r.dawah_date).toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'})
                : '—';

            const remarks = r.remarks
                ? `<span title="${escHtml(r.remarks)}" style="cursor:help;">
                    ${escHtml(r.remarks.length > 40 ? r.remarks.slice(0,40)+'…' : r.remarks)}
                   </span>`
                : '<span style="color:#6c757d">—</span>';

            return `<tr>
                <td style="color:#6c757d;font-size:.78rem;">${i+1}</td>
                <td style="white-space:nowrap;">${date}</td>
                <td>${typeBadge}</td>
                <td>${modeBadge}</td>
                <td style="font-weight:600;">${escHtml(r.person_name ?? '—')}</td>
                <td style="color:#6c757d;">${escHtml(r.person_contact ?? '—')}</td>
                <td>${escHtml(r.location ?? '—')}</td>
                <td style="max-width:200px;">${remarks}</td>
                <td style="white-space:nowrap;">
                <?php if (hasFeature("editDawah")) { ?>
                    <button class="btn-tbl btn-edit" onclick='openModal(${JSON.stringify(r)})'>
                        <i class="bi bi-pencil-fill"></i> Edit
                    </button>
                    <?php } ?>
                    &nbsp;
                    <?php if (hasFeature("deleteDawah")) { ?>
                    <button class="btn-tbl btn-del" onclick="deleteDawah(${r.id})">
                        <i class="bi bi-trash3-fill"></i> Del
                    </button>
                    <?php } ?>
                </td>
            </tr>`;
        }).join('');
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Toast ────────────────────────────────────────────────────────────────
    let toastTimer;
    function showToast(msg, type = 'success') {
        const t  = document.getElementById('dwToast');
        const ic = document.getElementById('dwToastIcon');
        const tx = document.getElementById('dwToastMsg');
        t.className = `dw-toast toast-${type}`;
        ic.className = type === 'success'
            ? 'bi bi-check-circle-fill'
            : 'bi bi-exclamation-circle-fill';
        tx.textContent = msg;
        void t.offsetWidth;
        t.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
    }

    // ── Init ─────────────────────────────────────────────────────────────────
    window.addEventListener('DOMContentLoaded', () => {
        document.getElementById('dawahDate').value = todayStr();
        loadRecords();
    });
    </script>
</body>
</html>