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
    @import url('https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=DM+Sans:wght@400;500&display=swap');

    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
        gap: 20px;
        padding: 16px 0;
        font-family: 'DM Sans', sans-serif;
    }

    .dars-card {
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background: #fff;
        transition: transform .22s ease, box-shadow .22s ease;
    }

    .dars-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.13);
    }

    .card-visual {
        background: #1b2a47;
        padding: 20px 18px 16px;
        position: relative;
        overflow: hidden;
        min-height: 110px;
    }

    .geo-bg {
        position: absolute;
        top: 0;
        right: 0;
        width: 160px;
        height: 110px;
        opacity: .13;
        pointer-events: none;
    }

    .area-initial-circle {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.12);
        border: 1.5px solid rgba(255, 255, 255, 0.22);
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Lora', serif;
        font-size: 20px;
        font-weight: 600;
        color: #fff;
        margin-bottom: 10px;
    }

    .card-visual h5 {
        font-family: 'Lora', serif;
        font-size: 1rem;
        font-weight: 600;
        color: #fff;
        margin: 0 0 4px;
        position: relative;
    }

    .card-visual .sub-label {
        font-size: 11px;
        color: rgba(255, 255, 255, .45);
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
    }

    .type-chip {
        position: absolute;
        top: 14px;
        right: 14px;
        padding: 3px 11px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .5px;
    }

    .chip-weekly   { background: #d4edda; color: #155724; }
    .chip-monthly  { background: #fff3cd; color: #7d5a00; }
    .chip-biweekly { background: #cce5ff; color: #004085; }

    .card-body-inner { padding: 14px 16px 8px; }

    .info-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 7px 0;
        border-bottom: 1px solid #f1f3f5;
        font-size: 13px;
    }
    .info-item:last-child { border-bottom: none; }

    .info-icon {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .info-icon svg {
        width: 13px; height: 13px; stroke: #6c757d;
        fill: none; stroke-width: 1.8;
        stroke-linecap: round; stroke-linejoin: round;
    }

    .info-label { font-size: 10px; color: #adb5bd; display: block; margin-bottom: 1px; }
    .info-val   { color: #212529; font-weight: 500; }

    .card-actions { padding: 10px 14px 12px; display: flex; gap: 7px; }

    .btn-card-detail {
        flex: 1.2; padding: 7px 0; border-radius: 8px;
        background: #253a5c; color: rgba(255,255,255,.8); border: none;
        font-size: 12px; font-weight: 500; cursor: pointer; font-family: inherit;
        display: flex; align-items: center; justify-content: center; gap: 5px;
        transition: background .18s; text-decoration: none;
    }
    .btn-card-detail:hover { background: #2e4570; color: #fff; }

    .btn-card-edit {
        flex: 1; padding: 7px 0; border-radius: 8px;
        color: #fff; border: none; font-size: 12px; font-weight: 500;
        cursor: pointer; font-family: inherit;
        display: flex; align-items: center; justify-content: center; gap: 5px;
        transition: opacity .18s;
    }
    .btn-card-edit:hover { opacity: .82; }

    .btn-card-del {
        width: 34px; height: 34px; border-radius: 8px;
        background: #fff1f1; border: 1px solid #f5c6c6;
        color: #c0392b; font-size: 13px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; transition: background .18s;
    }
    .btn-card-del:hover { background: #ffe0e0; }

    .empty-state { text-align: center; padding: 50px 20px; color: #adb5bd; grid-column: 1/-1; }
    .empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; }
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
                                     <?php if (hasFeature("addArea")) { ?>
                                    <button class="btn btn-success mb-3" id="addNewAreaBtn">New Dars Area</button>
                                <?php } ?>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <ul class="breadcome-menu">
                                    <li><a href="#">Home</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Dars Areas</span></li>
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
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="sparkline12-list mg-b-15">
                        <div class="sparkline12-hd">
                            <div class="d-flex justify-content-between align-items-center flex-wrap" style="padding-bottom: 20px;">
                                <div>
                                    <h5 class="mb-0">Dars Areas</h5>
                                </div>
                                <div class="d-flex gap-2 flex-wrap mt-2 mt-lg-0">
                                    <select id="filterType" class="form-control" style="width:150px;font-size:.9rem;">
                                        <option value="">All Types</option>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="Bi-weekly">Bi-weekly</option>
                                    </select>
                                    <div class="nk-header-search">
                                        <input type="text" id="searchInput" class="nk-search-input form-control" placeholder="Search...">
                                        <i class="bi bi-search nk-search-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="cards-grid" id="darsAreaGrid">
                            <!-- Cards rendered here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
include "footer.php"; ?>

    <!-- ══ ADD MODAL ══ -->
    <div class="modal fade" id="addAreaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Dars Area</h5>
                </div>
                <div class="modal-body">
                    <form id="addAreaForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Area Name <span class="text-danger">*</span></label>
                                    <input type="text" name="areaName" class="form-control" placeholder='e.g. "Area C"' required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Dars Type <span class="text-danger">*</span></label>
                                    <select name="darsType" class="form-control" required>
                                        <option value="">Select type…</option>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="Bi-weekly">Bi-weekly</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Day &amp; Time</label>
                                    <input type="text" name="dayTime" class="form-control" placeholder='e.g. "Every Friday – 7:00 PM"'>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Start Date</label>
                                    <input type="date" name="startDate" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group mb-2">
                                    <label>Location</label>
                                    <input type="text" name="location" class="form-control" placeholder="Street / Neighbourhood">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label>Map Link</label>
                                    <input type="url" name="mapLink" class="form-control" placeholder="https://maps.google.com/…">
                                </div>
                            </div>

                            <!-- ── Representative: now a user select ── -->
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Representative</label>
                                    <select name="representative_id" id="add-representative_id" class="form-control">
                                        <option value="">Loading users…</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Contact Person</label>
                                    <input type="text" name="contactName" class="form-control" placeholder="Contact name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Contact Phone</label>
                                    <input type="text" name="contactPhone" class="form-control" placeholder="+92 …">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="addDarsArea()">Add</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ EDIT MODAL ══ -->
    <div class="modal fade" id="editAreaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Dars Area</h5>
                </div>
                <div class="modal-body">
                    <form id="editAreaForm">
                        <input type="hidden" id="editAreaId" name="id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Area Name <span class="text-danger">*</span></label>
                                    <input type="text" name="areaName" id="edit-areaName" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Dars Type <span class="text-danger">*</span></label>
                                    <select name="darsType" id="edit-darsType" class="form-control" required>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="Bi-weekly">Bi-weekly</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Day &amp; Time</label>
                                    <input type="text" name="dayTime" id="edit-dayTime" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Start Date</label>
                                    <input type="date" name="startDate" id="edit-startDate" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group mb-2">
                                    <label>Location</label>
                                    <input type="text" name="location" id="edit-location" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-2">
                                    <label>Map Link</label>
                                    <input type="url" name="mapLink" id="edit-mapLink" class="form-control">
                                </div>
                            </div>

                            <!-- ── Representative: now a user select ── -->
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Representative</label>
                                    <select name="representative_id" id="edit-representative_id" class="form-control">
                                        <option value="">Loading users…</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Contact Person</label>
                                    <input type="text" name="contactName" id="edit-contactName" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-2">
                                    <label>Contact Phone</label>
                                    <input type="text" name="contactPhone" id="edit-contactPhone" class="form-control">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="updateDarsArea()">Update</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ DELETE MODAL ══ -->
    <div class="modal fade" id="deleteAreaModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteAreaMessage">Are you sure you want to delete this area?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteAreaBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        
        const canAddArea    = <?= hasFeature("addArea")    ? "true" : "false" ?>;
const canEditArea   = <?= hasFeature("editArea")   ? "true" : "false" ?>;
const canDeleteArea = <?= hasFeature("deleteArea") ? "true" : "false" ?>;

        const addAreaModal    = new bootstrap.Modal(document.getElementById('addAreaModal'));
        const editAreaModal   = new bootstrap.Modal(document.getElementById('editAreaModal'));
        const deleteAreaModal = new bootstrap.Modal(document.getElementById('deleteAreaModal'));
        
const addNewAreaBtn = document.getElementById('addNewAreaBtn');
if (addNewAreaBtn) {
    addNewAreaBtn.addEventListener('click', () => {
        document.getElementById('addAreaForm').reset();
        document.getElementById('add-representative_id').value = '';
        addAreaModal.show();
    });
}



        let darsAreaList   = [];
        let selectedDeleteId = null;
        let allUsers       = [];   // populated once on page load

        // ── Load all users for representative dropdowns ──────────
        async function loadUsers() {
            try {
                const res = await fetch('fetchUsers.php');
                allUsers  = await res.json();
                populateUserSelects();
            } catch(e) { console.error('Error loading users:', e); }
        }

        function populateUserSelects() {
            const opts = '<option value="">— None —</option>' +
                allUsers.map(u =>
                    `<option value="${u.id}">${u.firstName} ${u.lastName} (@${u.username})</option>`
                ).join('');
            document.getElementById('add-representative_id').innerHTML = opts;
            document.getElementById('edit-representative_id').innerHTML = opts;
        }



        // ── ADD ───────────────────────────────────────────────────
        function addDarsArea() {
            const form = document.getElementById('addAreaForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = new FormData(form);

            fetch('addDarsArea.php', { method: 'POST', body: data })
                .then(r => r.text())
                .then(msg => { addAreaModal.hide(); showSuccess(msg, loadDarsAreas); })
                .catch(console.error);
        }

        // ── LOAD areas ────────────────────────────────────────────
        async function loadDarsAreas() {
            try {
                const res    = await fetch('fetchDarsAreas.php');
                darsAreaList = await res.json();
                applyFilters();
            } catch(e) { console.error('Error loading Dars Areas:', e); }
        }

        // ── RENDER cards ──────────────────────────────────────────
        const GEO_SVG = `<svg class="geo-bg" viewBox="0 0 160 110" xmlns="http://www.w3.org/2000/svg">
  <polygon points="80,5 155,45 155,85 80,105 5,85 5,45" fill="none" stroke="#fff" stroke-width="1"/>
  <polygon points="80,20 140,50 140,80 80,95 20,80 20,50" fill="none" stroke="#fff" stroke-width=".7"/>
  <line x1="5" y1="45" x2="155" y2="45" stroke="#fff" stroke-width=".5"/>
  <line x1="5" y1="85" x2="155" y2="85" stroke="#fff" stroke-width=".5"/>
  <line x1="80" y1="5" x2="80" y2="105" stroke="#fff" stroke-width=".5"/>
  <polygon points="80,20 100,35 100,65 80,75 60,65 60,35" fill="none" stroke="#fff" stroke-width=".6"/>
</svg>`;

        const TYPE_CONFIG = {
            'Weekly'   : { bg: '#1b2a47', chip: 'chip-weekly'   },
            'Monthly'  : { bg: '#2d1b47', chip: 'chip-monthly'  },
            'Bi-weekly': { bg: '#0f3d30', chip: 'chip-biweekly' },
        };

        function renderCards(list) {
            const grid = document.getElementById('darsAreaGrid');
            grid.innerHTML = '';

            if (!list || list.length === 0) {
                grid.innerHTML = `<div class="empty-state"><i class="bi bi-geo-alt"></i><p>No Dars Areas found.</p></div>`;
                return;
            }

            list.forEach(area => {
                const cfg      = TYPE_CONFIG[area.darsType] || TYPE_CONFIG['Weekly'];
                const initial  = (area.areaName || '?')[0].toUpperCase();
                const chipCls  = cfg.chip;
                const startFmt = area.startDate
                    ? new Date(area.startDate).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' })
                    : '—';

                // Representative display: use joined name from fetchDarsAreas
                const repName = area.representative || '—';

                const mapHtml = area.mapLink
                    ? `<a href="${area.mapLink}" target="_blank" style="color:#0d6efd;text-decoration:none;">Open Map ↗</a>`
                    : '—';
                const safeName = (area.areaName || '').replace(/'/g, "\\'");

                const iconStats = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>`;
                const iconClock = `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`;
                const iconCal   = `<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`;
                const iconPin   = `<svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>`;
                const iconMap   = `<svg viewBox="0 0 24 24"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>`;
                const iconUser  = `<svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`;
                const iconPhone = `<svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2A19.79 19.79 0 013.09 4.18 2 2 0 015.07 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L9.09 9.91a16 16 0 006.08 6.08l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>`;
                const iconEdit  = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`;
                const iconTrash = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>`;

               grid.innerHTML += `
    <div class="dars-card">
      <a href="darsAreaStats.php?id=${area.id}">
        <div class="card-visual" style="background:${cfg.bg};">
            ${GEO_SVG}
            <span class="type-chip ${chipCls}">${area.darsType}</span>
            <div class="area-initial-circle">${initial}</div>
            <h5>${area.areaName}</h5>
            <span class="sub-label">Dars Area</span>
        </div>
      </a>
      <div class="card-body-inner">
        <div class="info-item">
            <div class="info-icon">${iconClock}</div>
            <div><span class="info-label">Day &amp; Time</span><span class="info-val">${area.dayTime || '—'}</span></div>
        </div>
        <div class="info-item">
            <div class="info-icon">${iconCal}</div>
            <div><span class="info-label">Start Date</span><span class="info-val">${startFmt}</span></div>
        </div>
        <div class="info-item">
            <div class="info-icon">${iconPin}</div>
            <div><span class="info-label">Location</span><span class="info-val">${area.location || '—'}</span></div>
        </div>
        <div class="info-item">
            <div class="info-icon">${iconMap}</div>
            <div><span class="info-label">Map</span><span class="info-val">${mapHtml}</span></div>
        </div>
        <div class="info-item">
            <div class="info-icon">${iconUser}</div>
            <div><span class="info-label">Representative</span><span class="info-val">${repName}</span></div>
        </div>
        <div class="info-item">
            <div class="info-icon">${iconPhone}</div>
            <div>
                <span class="info-label">Contact</span>
                <span class="info-val">${area.contactName || '—'}
                    ${area.contactPhone ? `<small style="color:#6c757d;font-weight:400;">${area.contactPhone}</small>` : ''}
                </span>
            </div>
        </div>
      </div>
      <div class="card-actions">
        <a href="darsAreaStats.php?id=${area.id}" class="btn-card-detail">
            ${iconStats} Details
        </a>
        ${canEditArea ? `
        <button class="btn-card-edit" style="background:${cfg.bg};" onclick="editDarsArea(${area.id})">
            ${iconEdit} Edit
        </button>` : ''}
        ${canDeleteArea ? `
        <button class="btn-card-del" onclick="confirmDeleteDarsArea(${area.id}, '${safeName}')">
            ${iconTrash}
        </button>` : ''}
      </div>
    </div>`;
            });
        }

        // ── Filter & Search ───────────────────────────────────────
        function applyFilters() {
            const query   = document.getElementById('searchInput').value.toLowerCase().trim();
            const typeVal = document.getElementById('filterType').value;

            const filtered = darsAreaList.filter(a => {
                const repName = (a.representative || '').toLowerCase();
                const matchSearch = !query ||
                    (a.areaName   || '').toLowerCase().includes(query) ||
                    repName.includes(query) ||
                    (a.location   || '').toLowerCase().includes(query) ||
                    (a.contactName|| '').toLowerCase().includes(query);
                const matchType = !typeVal || a.darsType === typeVal;
                return matchSearch && matchType;
            });

            renderCards(filtered);
        }

        document.getElementById('searchInput').addEventListener('input', applyFilters);
        document.getElementById('filterType').addEventListener('change', applyFilters);

        // ── EDIT ──────────────────────────────────────────────────
        function editDarsArea(id) {
            const area = darsAreaList.find(a => a.id == id);
            if (!area) return;

            document.getElementById('editAreaId').value             = area.id;
            document.getElementById('edit-areaName').value          = area.areaName       || '';
            document.getElementById('edit-darsType').value          = area.darsType       || 'Weekly';
            document.getElementById('edit-dayTime').value           = area.dayTime        || '';
            document.getElementById('edit-startDate').value         = area.startDate      || '';
            document.getElementById('edit-location').value          = area.location       || '';
            document.getElementById('edit-mapLink').value           = area.mapLink        || '';
            // pre-select the current representative by id
            document.getElementById('edit-representative_id').value = area.representative_id || '';
            document.getElementById('edit-contactName').value       = area.contactName    || '';
            document.getElementById('edit-contactPhone').value      = area.contactPhone   || '';

            editAreaModal.show();
        }

        // ── UPDATE ────────────────────────────────────────────────
        function updateDarsArea() {
            const form = document.getElementById('editAreaForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            const data = new FormData(form);

            fetch('updateDarsArea.php', { method: 'POST', body: data })
                .then(r => r.text())
                .then(msg => { editAreaModal.hide(); showSuccess(msg, loadDarsAreas); })
                .catch(console.error);
        }

        // ── DELETE confirm ────────────────────────────────────────
        function confirmDeleteDarsArea(id, name) {
            selectedDeleteId = id;
            document.getElementById('deleteAreaMessage').innerText =
                `Are you sure you want to delete "${name}"?`;
            deleteAreaModal.show();
        }

        document.getElementById('confirmDeleteAreaBtn').addEventListener('click', function() {
            if (!selectedDeleteId) return;
            fetch(`deleteDarsArea.php?id=${selectedDeleteId}`)
                .then(r => r.text())
                .then(() => {
                    deleteAreaModal.hide();
                    selectedDeleteId = null;
                    loadDarsAreas();
                })
                .catch(console.error);
        });

        // ── Success modal ─────────────────────────────────────────
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

        // ── Init ──────────────────────────────────────────────────
        loadUsers();      // load user list for representative dropdowns
        loadDarsAreas();  // load and render area cards
    </script>

</body>
</html>